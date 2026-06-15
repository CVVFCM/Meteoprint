# CLAUDE.md

Guidance for working in this repository.

## What this is

**meteoprint** — a Symfony 8.1 app for generating printable weather reports
(GPL-3.0-or-later, author Yohan Giarelli). Early-stage skeleton: framework and
infrastructure are wired up, but `src/` has no entities, controllers, or
business logic yet (only an empty `AppFixtures`).

### Planned scope

A simple weather website — **no map, no complex features**. Core goal: generate
a **highly readable, printable summary** of today's and tomorrow's weather.

- **Data source**: [Open-Meteo](https://open-meteo.com/) (via `symfony/http-client`).
- **On-demand generation**: a report is requested by the user, then computed
  asynchronously. Use **Messenger** to handle the (deliberately delayed) fetch +
  render, and **Mercure** to push the result to the browser when ready.
- **Frontend**: AssetMapper + importmap only (no Node/bundler). Keep it light;
  the print stylesheet is a first-class concern.

## Stack

- **PHP 8.4+** (runtime container uses PHP 8.5), Symfony 8.1.*
- **Doctrine ORM 3** + migrations + fixtures (dev/test), **PostgreSQL 16**
- **FrankenPHP** (Caddy) as the runtime — see `Dockerfile`, `.infra/docker/php/Caddyfile`
- **Mercure** for real-time updates, **Messenger** (sync transport + scheduler)
- **AssetMapper** + importmap for frontend (`assets/`, `importmap.php`); no Node build
- Translations via `symfony/translation`

## Running (Docker, via Makefile)

Everything runs in containers; the `Makefile` wraps `docker compose`.

```bash
make run        # first run: TLS cert + pull + build + up + DB reset + assets. Then just starts containers
make up         # start containers only
make reset      # reset/create the database (runs `composer reset` in php container)
make cli        # bash shell in the php container
make cs         # fix code style (php-cs-fixer + twig-cs-fixer)
make clean      # stop containers, remove all data/volumes/vendor
```

Run `bin/console` and `composer` **inside the php container** (`make cli`, or
`docker compose exec php ...`). The entrypoint waits for the DB and auto-runs
pending migrations on container start.

App served at `https://localhost` (ports 80/443 configurable via `HTTP_PORT`/`HTTPS_PORT`).

## Layout

- `src/` — app code, PSR-4 `App\` (Controller, Entity, Repository, DataFixtures)
- `config/packages/` — per-bundle config
- `migrations/` — Doctrine migrations
- `templates/` — Twig
- `assets/` — JS/CSS served via AssetMapper
- `.infra/docker/` — Caddyfile, entrypoint, TLS certs

## Conventions

- `services.yaml` uses autowire + autoconfigure; classes in `src/` are auto-registered.
- Code style is enforced by php-cs-fixer + twig-cs-fixer (`make cs`) — run before committing.
- `APP_ENV=dev` locally; container image builds `prod`.

## Notes

- No test suite exists yet (`tests/` autoload namespace `App\Tests\` is declared but empty).
- Doctrine ORM 3 / DBAL: use attributes for entity mapping (no annotations).
