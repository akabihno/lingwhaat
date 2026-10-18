# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

LingWhaat is a language-detection system for ~59 languages: a **PHP / Symfony 7.4 app** (`src/`, PHP 8.5) — the web API + all the async data pipelines (Wiktionary/Wikipedia parsing, pattern indexing, manuscript decoding, scoring). PSR-4 root `App\` → `src/`. (The Python ML service for IPA transliteration and the LLM-based word categorization and phrase selection were removed on 2026-10-18.)

It runs on a **self-hosted k3s cluster** (namespace `lingwhaat`). See `README.md` for full cluster/registry/ArgoCD setup and `README_DEV.md` for local dev + Psalm.

## Infrastructure topology (read before touching data)

- **Database is production MySQL 8.4 running on the `raspberrypi` node itself, outside k3s** (moved off AWS RDS on 2026-09-29). It is the docker compose service `db` (container `database`; profile `mysql` starts only it) with host networking on `192.168.0.196:3306`, no TLS, data in `/mnt/stuff/mysql/data`, config `./my.cnf`. Pods connect via `DATABASE_URL` in secret `lingwhaat-secrets`. **Never `down`/recreate the `db` service casually — it is production.**
- The app user `web` has **per-table grants**: SELECT/INSERT/UPDATE on most tables, DELETE on only a few (e.g. `messenger_messages`), and no `TRUNCATE`/DDL on most tables. For DDL, `TRUNCATE` or `KILL`, use root inside the container on raspberrypi: `docker exec -it database sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot lingwhaat'`.
- **Elasticsearch is a single node** and is the recurring capacity bottleneck; when overloaded it drops out of its Service and clients report "No alive nodes." It is being phased out: application logs go to stderr, the pattern-index pipeline matches in memory, and word lookups are moving to the `word_search` MySQL tables (`WORD_SEARCH_BACKEND` / `WORD_LOOKUP_BACKEND`); the wildcard and positional searches in `PatternSearchService` still use `words_index`.
- **Redis** backs Symfony Lock (`LOCK_DSN`) and cache — locks coordinate across worker pods.
- Container images use the mutable tag `registry.local:30500/lingwhaat-php:latest`. Pushing a new image does **not** update running pods — you must recreate them (`kubectl rollout restart` or scale 0→up) to pull it.

## Async architecture (Messenger + Scheduler)

This is the core of the codebase and spans many files:

- **One Doctrine-backed Messenger transport per pipeline**, all sharing the single production table `messenger_messages`, distinguished by `queue_name` (transports/routing in `config/packages/messenger.yaml`; DSNs in env). Transports: `async`, `wiktionary_async`, `wikipedia_async`, `wikipedia_pattern_index_async`, `manuscript_language_score_async`, `manuscript_language_atbash_score_async`, `words_popularity_async`, `failed`.
- **Each transport has its own k8s Deployment** (`k8s/workers.yaml`) running `messenger:consume <transport>`. Several use a wrapper script (`worker-wrapper` ConfigMap) that runs N `messenger:consume` per pod and tears the pod down if any worker dies. Tuning is via env (`WORKER_CONCURRENCY`, `WORKER_MEMORY_LIMIT`, `WORKER_SLEEP`).
- A **`scheduler` Deployment** consumes `scheduler_default`; `src/Schedule.php` enqueues recurring dispatch messages (mostly every 1–5 min, with jitter). Because transports live in the shared production database, **running local workers/scheduler would steal rows from production** — that is why local dev (below) runs neither.
- **Dispatch → fan-out pattern**: a `*DispatchMessage` handler selects work and fans out per-item messages onto the same transport. Deduplication of in-flight work is done with cursor/offset tables (e.g. `wikipedia_pattern_index_offset`) and/or Redis locks held for a TTL (see `Manuscript*ScoreDispatchMessageHandler` — the lock is intentionally not released so the next tick doesn't re-fan rows still being processed). Message/handler pairs live in `src/Message/` and `src/MessageHandler/`.

## Per-language entity model

Every supported language has its own Doctrine entity `src/Entity/<Language>LanguageEntity.php` (~70 of them) and matching repository. `src/Constant/LanguageMappings.php` is the code↔name↔entity registry. **Do not hand-add a language** — use the maker:

```bash
kubectl exec -it -n lingwhaat deploy/web -- php bin/console make:language finnish fi
```

which scaffolds the entity/repository, updates `LanguageMappings`, generates a migration, grants DB access, and updates the README.

## Common commands

Local dev (web + redis + ml only, pointed at the production database via `DATABASE_URL` in `.env`, **no scheduler/workers**):

```bash
docker compose --profile dev up --build -d      # start
docker compose --profile dev logs -f web
docker compose exec web php bin/console <cmd>    # run console safely (no scheduler)
```

> Never use the `local` or `rds` compose profiles for routine work — both include the scheduler and messenger workers and will interfere with the live deployment via the shared `messenger_messages` table (`local` also includes `db`, the production database container).

Static analysis (there is **no PHPUnit test suite**; Psalm is the quality gate):

```bash
docker compose exec web php bin/console cache:warmup                      # plugin reads dev DI container
docker compose exec web php -d memory_limit=2G vendor/bin/psalm --no-progress   # or: composer psalm
```

Lint a changed file with a PHP 8.5 runtime (the codebase uses 8.5 syntax such as the `|>` pipe operator, so an older CLI will misreport): prefer the web container — `docker compose exec web php -l <file>`.

Migrations:

```bash
kubectl exec -n lingwhaat deploy/web -- php bin/console doctrine:migrations:migrate --no-interaction
```

Ad-hoc SQL / diagnostics as `web` (note the `--`; `dbal:run-sql` reports `SHOW ...` as "0 rows affected", so query `information_schema` instead):

```bash
kubectl exec -n lingwhaat deploy/web -- php bin/console dbal:run-sql -- "<SQL>"
```

Deploy an update (maintainer normally does this; commit/push only when asked):

```bash
docker build -f Dockerfile-php -t registry.local:30500/lingwhaat-php:latest .
docker push registry.local:30500/lingwhaat-php:latest
kubectl rollout restart -n lingwhaat deployment/<name>   # required — :latest is mutable
```

## Pattern-index / manuscript pipeline (domain specifics)

The Wikipedia "canonical pattern" pipeline is the heaviest subsystem (`Service/Search/`, `WikipediaPatternIndex*`). Per language, each batch loads every manuscript window into a `ManuscriptWindowMatcher`, then `WikipediaCorpusScanner` slides a fixed **window size** over the next batch of article text and offers each window's canonical pattern to the matcher; hits (first 5 per manuscript window, in `article_id, local_position` order) are appended to `manuscript_pattern_match_result`. Nothing is stored per batch — this replaced a per-batch Elasticsearch scratch index (index → search → evict). The window size originates from `WikipediaPatternIndexDispatchMessage` (the scheduler constructs it with the default) and flows through the dispatch handler into per-language messages; the cursor records it, so a window-size change restarts the sweep. Kickoff is `app:wikipedia-pattern-index-dispatch`. Manuscript decoding is kicked off via `app:manuscript-alphabet-decode`. `wikipedia_pattern_index_offset` holds the per-language sweep cursor; truncating it restarts the sweep from scratch (needed for a newly added manuscript to be matched against already-swept articles).
