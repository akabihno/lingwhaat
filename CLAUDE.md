# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

LingWhaat is a language-detection system for ~59 languages. It is two cooperating apps:

- **PHP / Symfony 7.4 app** (`src/`, PHP 8.5) — the web API + all the async data pipelines (Wiktionary/Wikipedia parsing, pattern indexing, manuscript decoding, scoring). PSR-4 root `App\` → `src/`.
- **Python ML service** (`ml_service/`, FastAPI + PyTorch) — trains and serves per-language IPA/word prediction models. The PHP app calls it over HTTP at `127.0.0.1:8000` (see `IpaPredictorConstants`); routes like `predict-word`, `train-ipa`.

It runs on a **self-hosted k3s cluster** (namespace `lingwhaat`). See `README.md` for full cluster/registry/ArgoCD setup and `README_DEV.md` for local dev + Psalm + ML training details.

## Infrastructure topology (read before touching data)

- **Database is AWS RDS MySQL 8.4, off-cluster** (`DATABASE_URL`). The app user `web` can run DML (SELECT/INSERT/UPDATE/DELETE) but **lacks DDL — no `TRUNCATE`** — and cannot kill queries. For `TRUNCATE`/DDL or `CALL mysql.rds_kill(...)`, connect as RDS master user `root` (password in secret `MYSQL_ROOT_PASSWORD`, also present in the `web` pod env) via PDO with `PDO::MYSQL_ATTR_SSL_CA => /ssl-certs/global-bundle.pem` (RDS requires SSL).
- **Elasticsearch is a single node** and is the recurring capacity bottleneck; when overloaded it drops out of its Service and clients report "No alive nodes." The pattern-index and scoring pipelines are ES-heavy.
- **Redis** backs Symfony Lock (`LOCK_DSN`) and cache — locks coordinate across worker pods.
- Container images use the mutable tag `registry.local:30500/lingwhaat-php:latest`. Pushing a new image does **not** update running pods — you must recreate them (`kubectl rollout restart` or scale 0→up) to pull it.

## Async architecture (Messenger + Scheduler)

This is the core of the codebase and spans many files:

- **One Doctrine-backed Messenger transport per pipeline**, all sharing the single RDS table `messenger_messages`, distinguished by `queue_name` (transports/routing in `config/packages/messenger.yaml`; DSNs in env). Transports: `async`, `wiktionary_async`, `wikipedia_async`, `wikipedia_pattern_index_async`, `selection_async`, `manuscript_language_score_async`, `manuscript_language_atbash_score_async`, `words_popularity_async`, `failed`.
- **Each transport has its own k8s Deployment** (`k8s/workers.yaml`) running `messenger:consume <transport>`. Several use a wrapper script (`worker-wrapper` ConfigMap) that runs N `messenger:consume` per pod and tears the pod down if any worker dies. Tuning is via env (`WORKER_CONCURRENCY`, `WORKER_MEMORY_LIMIT`, `WORKER_SLEEP`).
- A **`scheduler` Deployment** consumes `scheduler_default`; `src/Schedule.php` enqueues recurring dispatch messages (mostly every 1–5 min, with jitter). Because transports live in shared RDS, **running local workers/scheduler would steal rows from production** — that is why local dev (below) runs neither.
- **Dispatch → fan-out pattern**: a `*DispatchMessage` handler selects work and fans out per-item messages onto the same transport. Deduplication of in-flight work is done with cursor/offset tables (e.g. `wikipedia_pattern_index_offset`) and/or Redis locks held for a TTL (see `Manuscript*ScoreDispatchMessageHandler` — the lock is intentionally not released so the next tick doesn't re-fan rows still being processed). Message/handler pairs live in `src/Message/` and `src/MessageHandler/`.

## Per-language entity model

Every supported language has its own Doctrine entity `src/Entity/<Language>LanguageEntity.php` (~70 of them) and matching repository. `src/Constant/LanguageMappings.php` is the code↔name↔entity registry. **Do not hand-add a language** — use the maker:

```bash
kubectl exec -it -n lingwhaat deploy/web -- php bin/console make:language finnish fi
```

which scaffolds the entity/repository, updates `LanguageMappings`, generates a migration, grants DB access, and updates the README.

## Common commands

Local dev (web + redis + ml only, pointed at prod RDS, **no scheduler/workers**):

```bash
docker compose --profile dev up --build -d      # start
docker compose --profile dev logs -f web
docker compose exec web php bin/console <cmd>    # run console safely (no scheduler)
```

> Never use the `local` or `rds` compose profiles for routine work — both include the scheduler and messenger workers and will interfere with the live deployment via the shared RDS `messenger_messages` table.

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

Ad-hoc SQL / diagnostics against RDS (note the `--`):

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

The Wikipedia "canonical pattern" pipeline is the heaviest subsystem (`Service/Search/`, `WikipediaPatternIndex*`). It slides a fixed **window size** over Wikipedia article text per language, writing patterns into per-language ES indices (`wiki_patterns_<lang>_<ts>` behind aliases), then searches decoded manuscripts against that corpus and evicts spent docs (index → search → evict) to keep the ES index bounded to roughly the in-flight batch. The window size originates from `WikipediaPatternIndexDispatchMessage` (the scheduler constructs it with the default), flows through the dispatch handler into per-language messages, and is baked into ES doc `_id`s — changing it triggers an index rebuild, so a window-size change means re-sweeping the corpus. Kickoff is `app:wikipedia-pattern-index-dispatch` (async) or `app:wikipedia-pattern-index` (console, per language). Manuscript decoding is kicked off via `app:manuscript-alphabet-decode`. `wikipedia_pattern_index_offset` holds the per-language sweep cursor; truncating it restarts indexing from scratch.
