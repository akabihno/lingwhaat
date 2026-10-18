# Run locally in Docker against the production RDS (DEV mode)

This starts only the web app (+ a local Redis for locks/cache + the ml-service),
pointed at the **same RDS** the k3s deployment uses. It deliberately does **not**
start the scheduler or any messenger workers, so:

- No jobs from `src/Schedule.php` ever run locally.
- No queue consumers run locally. (Messenger transports are Doctrine-backed and
  live in the shared RDS `messenger_messages` table, so a local worker would
  steal/delete rows the k3s workers are processing.)

`.env` already points `DATABASE_URL` at RDS and mounts `ssl-certs/`, so no extra
config is needed.

## Prerequisites (macOS)
The services use `network_mode: host`, which on Docker Desktop for Mac must be
enabled: Settings → Resources → Network → "Enable host networking".

## Launch
```bash
docker compose --profile dev up --build -d
```
This starts: `web`, `redis`, `ml`, and a one-shot `composer` (installs deps).
The app is served by Apache on the host (port 80 / 443).

Tail logs / stop:
```bash
docker compose --profile dev logs -f web
docker compose --profile dev down
```

Run a console command in the running web container (safe — no scheduler):
```bash
docker compose exec web php bin/console <command>
```

> Note: never use the `local` or `rds` profiles for this purpose — both include
> the scheduler and messenger workers and **will** interfere with the live
> deployment.

# Static analysis with Psalm

Psalm (`vimeo/psalm`) with the Symfony plugin (`psalm/plugin-symfony`) is wired up
in `psalm.xml`. The Symfony plugin reads the compiled DI container at
`var/cache/dev/App_KernelDevDebugContainer.xml`, so the dev cache must be warm
first (it is whenever the `web` container has booted in `APP_ENV=dev`).

Run it inside the `web` container (it has PHP 8.5 + the bind-mounted vendor):
```bash
# warm the container the plugin reads, if needed:
docker compose exec web php bin/console cache:warmup

# run analysis (memory bumped; Psalm is memory-hungry):
docker compose exec web php -d memory_limit=2G vendor/bin/psalm --no-progress
# or via the composer script:
docker compose exec web composer psalm
```

Generate / refresh a baseline of currently-accepted issues:
```bash
docker compose exec web composer psalm:baseline
```

# Add new language:

Execute (creates entity/repository, updates LanguageMappings, runs migration, grants DB access, updates README):
kubectl exec -it -n lingwhaat deploy/web -- php bin/console make:language finnish fi

Generate docs for language:
kubectl exec -it -n lingwhaat deploy/web -- utils/generate_docs.php finnish
kubectl exec -it -n lingwhaat deploy/web -- php utils/generate_docs.php hebrew true // for languages with right-to-left writing

Or, for paginated:
kubectl exec -it -n lingwhaat deploy/web -- php utils/generate_docs_paginated.php vietnamese

# Prepare to run search against Wikipedia canonical patterns with sliding window:

Get 100K Wikipedia articles for language:
INSERT INTO lingwhaat.wikipedia_pattern_parse_schedule SET language_code = 'fi';

Set popularity score for language:
INSERT INTO lingwhaat.words_popularity_score_set_schedule SET language_code = 'fi';

Match manuscripts against the canonical patterns of every language's articles (continuous, batch by batch, in memory):
kubectl exec -it -n lingwhaat deploy/web -- php bin/console app:wikipedia-pattern-index-dispatch

To re-sweep the whole corpus (e.g. after adding a manuscript), reset the per-language cursors first:
TRUNCATE lingwhaat.wikipedia_pattern_index_offset;

Rebuild the word search tables (word_search / word_search_token) for all languages, or one with `-l fi`:
kubectl exec -it -n lingwhaat deploy/web -- su -s /bin/sh www-data -c 'php bin/console app:word-search-rebuild'


Also note: for future deployments, if a pod gets scheduled back onto raspberrypi and hits ImagePullBackOff, just delete that pod and the scheduler will place it on another    
  node. You can also add a node preference annotation to the web deployment to avoid raspberrypi for now, if you'd like.


Schedule manuscript alphabet decoding:
kubectl exec -it -n lingwhaat deploy/web -- php bin/console app:manuscript-alphabet-decode --language-code=en --window-size=18