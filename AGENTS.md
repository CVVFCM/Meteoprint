# CLAUDE.md

Guidance for working in this repository.

## What this is

**meteoprint** — a Symfony 8.1 app for generating printable weather reports.

### Scope

A simple weather website — **no map, no complex features**. Core goal: generate
a **highly readable, printable summary** of today's and tomorrow's weather.

- **Data source**: [Open-Meteo](https://open-meteo.com/) (via `symfony/http-client`).
- **On-demand generation**: a report is requested by the user, then computed
  asynchronously. Use **Messenger** to handle the (deliberately delayed) fetch +
  render, and **Mercure** to push the result to the browser when ready.
- **Frontend**: AssetMapper + importmap only (no Node/bundler). Keep it light;
  the print stylesheet is a first-class concern.

## Architecture — Light DDD

Bridge pattern for third-party integrations. Each external service lives under `src/Bridge/`:

- **`src/Bridge/Ffvoile/`** — French Sailing Federation integration
- **`src/Bridge/OpenMeteo/`** — Weather data integration

**Layer separation:**
- **Bridge** — external service adapters (SDK, provider-specific types)
- **Entity** — domain model (core business entities)
- **Message/MessageHandler** — application layer (CQRS commands)
- **Controller** — presentation layer (HTTP handlers)
- **Command** — CLI entry points (orchestration, stays in Bridge subnamespace)

**ValueObjects:**
- Provider-specific VOs live in their Bridge (`FFVClubId` → `Bridge/Ffvoile/`)
- Shared VOs stay in `src/ValueObject/` (`Geo`, `ForecastSlot`)

## Stack

- **PHP 8.5+**, Symfony 8.1.*
- **Doctrine ORM 3** + migrations + fixtures (dev/test), **PostgreSQL 18**
- **FrankenPHP** (Caddy) as the runtime — see `Dockerfile`, `.infra/docker/php/Caddyfile`
- **Mercure** for real-time updates, **Messenger** (sync transport + scheduler)
- **AssetMapper** + importmap for frontend (`assets/`, `importmap.php`); no Node build
- Translations via `symfony/translation`

## Running (Docker, via Makefile)

**Use Makefile, not direct `docker compose` commands** (except `docker compose logs`).

```bash
make run          # First run: TLS + pull + build + up + DB reset + assets. Then just starts containers
make up           # Start containers only
make reset        # Reset/create the database
make cli          # Bash shell in the php container
make test         # Run PHPUnit tests
make reset-test   # Reset test DB + run tests
make cs           # Fix code style (php-cs-fixer + twig-cs-fixer)
make clean        # Stop containers, remove all data/volumes/vendor
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
- `APP_ENV=dev` locally; container image builds `prod`.
- Doctrine ORM 3 / DBAL: use attributes for entity mapping (no annotations).
- **Exceptions**: every custom exception extends a native PHP exception
  (`\RuntimeException`, `\InvalidArgumentException`, …) **and** implements
  `App\Exception\ExceptionInterface`, so callers can catch any project error via
  that interface.
- **Third-party integrations** live under the `App\Bridge\` namespace
  (e.g. the Open-Meteo SDK at `App\Bridge\OpenMeteo\`).
- **Code quality & typing** — use every quality keyword the language offers:
  - `declare(strict_types=1);` in **every** PHP file.
  - Classes are **`final` by default**. Exceptions: Doctrine entities (lazy-loading
    proxies — non-final unless using PHP 8.4 native lazy objects), interfaces, and
    deliberate extension points.
  - **`readonly`** on every constructor-promoted property and any property assigned
    once. When **all** of a class's state is read-only, declare the whole class
    `final readonly` (and drop the now-redundant per-property `readonly`) — this
    covers value objects, DTOs, and stateless services/controllers. Not possible
    when extending a non-readonly parent (Symfony form types, native exceptions): keep
    per-property `readonly` there.
  - **Full type coverage**: every parameter, return, and property is typed natively;
    fall back to `mixed` only when unavoidable.
  - Document **array shapes** with PHPDoc generics (`list<T>`, `array<K, V>`,
    `array{...}`) wherever a bare `array` appears — PHPStan heavy requires it.
  - Typehint the **narrowest interface** (`UrlGeneratorInterface`, `FormFactoryInterface`, …).
  - Native **backed enums** for closed sets.
  - **Typed class constants** (PHP 8.3+): `private const int MAX = 8;`, `public const self DEFAULT = self::X;`.
- **Route names** are bare and meaningful (`homepage`, `forecast`, `geocode_search`) —
  do NOT prefix with `app_` (it carries no information).
- **HTTP verbs** use `Request::METHOD_*` constants, never string literals
  (`methods: [Request::METHOD_GET]`, `'method' => Request::METHOD_POST`). Exception:
  the `App\Bridge\` SDKs stay decoupled from HttpFoundation and pass the verb as a
  string to the HTTP client.
- **Controllers** do NOT extend `AbstractController`. They are invokable (ADR),
  `final`, and receive collaborators via constructor injection (`FormFactoryInterface`,
  Twig `Environment`, `UrlGeneratorInterface`, …), returning `Response` /
  `JsonResponse` / `RedirectResponse` directly. `src/Controller/` is registered with
  the `controller.service_arguments` tag in `services.yaml`. (Set the 422 status on an
  invalid submitted form by hand — there is no base class to do it.)

## Linters — MANDATORY

All produced code MUST pass every linter the CI runs (`.github/workflows/ci.yaml`).
A change that fails any linter is not finished. Run via Makefile after every change
and fix violations — never leave or baseline failures in project code.
Authoritative list lives in `ci.yaml`; currently:

- **PHPStan** (heavy: level max + bleedingEdge): `make stan`
- **PHP CS Fixer**: `make cs`
- **Twig CS Fixer**: `make cs`
- **PHPUnit**: `make test` (`make reset-test` before. Once is enough)
- Container/YAML/Twig/Translations: `make cli` then `bin/console lint:*`
- **ESLint** (`npx eslint assets`), **StyleLint** (`npx stylelint assets/**/*.css`),
  **hadolint** on the `Dockerfile`

## Notes

- Tests live in `tests/` (PSR-4 `App\Tests\`), run with `bin/phpunit`.
