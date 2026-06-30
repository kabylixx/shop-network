# Shop Network — guide for AI agents

Server-side REST/JSON API (PHP / Symfony, **no API Platform**) for a shop network:
product catalog, shops with geolocation, and per-shop stock.

**Stack**: PHP 8.5 · Symfony 8.1 · MySQL 8.4 · FrankenPHP · Docker.

> Read this before changing code — it captures the house style. The *why* lives in the ADRs
> (`docs/adr/`), the endpoint contract in `docs/api.md`, the overview in `README.md`. Don't
> duplicate them here.

## Architecture

Pragmatic hexagonal, one bounded context, organized in **modules** × **layers**:

- Modules: `Catalog`, `Network`, `Inventory`, `Shared`.
- Layers (per module): `Domain` / `Application` / `Infrastructure`.
- Dependency rules, enforced by Deptrac (`deptrac.yaml`):
  - `Domain` → nothing
  - `Application` → `Domain`
  - `Infrastructure` → `Domain` + `Application`

Tests mirror `src/` under `tests/`.

## House conventions

- **Typed aggregate IDs**: each aggregate has its own id value object (`ProductId`, `ShopId`,
  `ManagerId`, `StockId`) extending `Symfony\Component\Uid\Uuid` (UUID v7). **Generated in the
  Application layer / handler** (`XxxId::generate()`), rebuilt with `::fromString()`. Mapped to
  `BINARY(16)` via a Doctrine `*IdType` (`AbstractUidType`), registered in
  `config/packages/doctrine.yaml`.
- **CQRS-light**:
  - *Write* through a Domain `*Repository` (e.g. `ProductRepository`) — write-oriented.
  - *Read* through an Application `*Finder` **port** (e.g. `ShopFinder`) returning `*View` read
    models / `Paginated`, never the aggregate. Read adapters use raw DBAL (`Connection`).
- **References by identity, no foreign key**: an aggregate stores other aggregates' ids, not ORM
  associations. Existence of a referenced aggregate is checked **in the handler** →
  `Shared\Domain\NotFoundException` → **404** (never a 500 on a missing FK). Cross-module checks go
  through `*Existence` ports (DBAL, schema coupling only — no class coupling).
- **HTTP slice**: an `*Action` (`#[Route]`) deserializes + validates a `*Request` (`#[Assert\*]`,
  `#[MapQueryString]` for GET) → invokes a `*CommandHandler` / `*QueryHandler` → returns
  `JsonResponse(View | Paginated)`. **Validation runs before existence** (a request that is both
  invalid and references a missing resource returns 422, not 404).
- **Errors**: RFC 7807 (`application/problem+json`) via `ProblemDetailsListener`
  (`src/Shared/Infrastructure/Http/`). It maps only known cases (validation → 422, NotFound → 404,
  malformed JSON → 400, HttpException → its status); anything else falls through to a real 500.
- **Shared**: `Pagination` / `Paginated` (Application), geo value objects `Coordinates` /
  `SearchArea` (Domain) with constructor invariants.

## Style

- **English everywhere** — code, comments, docs, commit messages.
- **Conventional commits** (Karma): `feat|fix|docs|style|refactor|test|chore`, single-line subject,
  no trailing period (enforced by GrumPHP).
- Changes land via **pull requests**.

## Commands

| Command | Effect |
| --- | --- |
| `make start-and-seed` | Full startup with demo data (start + seed) |
| `make start` | Build + up + install + migrate |
| `make test` | Full PHPUnit suite (prepares the test DB) |
| `make test-unit` | Unit tests only — `#[Group('unit')]`, no DB, fast |
| `make test-functional` | Functional tests — `#[Group('functional')]`, prepares the test DB |
| `make migrate` | Doctrine migrations |
| `make sh` | Shell in the app container |

GrumPHP runs **PHPStan (level 8)**, **PHP-CS-Fixer (`@Symfony`)** and **Deptrac** on pre-commit (via
Docker); all must be green. CI re-runs them plus the test suite.

## Pointers

- `README.md` — overview & one-command run.
- `docs/api.md` — per-endpoint contract (**keep it in sync** when you touch an endpoint).
- `docs/adr/` — the *why* behind every architectural choice; read before architectural changes.
- `.claude/skills/add-endpoint` — recipe to add or extend an endpoint.
- `.claude/skills/add-adr` — recipe to record a new ADR.
