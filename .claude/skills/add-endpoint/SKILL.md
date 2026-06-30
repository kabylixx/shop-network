---
name: add-endpoint
description: Scaffold or extend an HTTP API endpoint following this project's hexagonal vertical-slice pattern — Action → Request → Command/Query → Handler → repository (write) or Finder port (read) → View/Paginated, plus tests and the docs/api.md update. Use when adding or modifying a REST endpoint.
---

# Add an endpoint

This repo has a very regular vertical slice. Follow the existing pattern exactly: read the canonical
example for the side you're building, then mirror it.

First decide: **write** (mutates state, returns the created/affected resource) or **read** (queries,
returns a View / Paginated list).

## Write endpoint — canonical example: `POST /api/products`

Create, in this order:

1. `src/<Module>/Infrastructure/Http/<UseCase>Request.php` — public props + `#[Assert\*]`. This is the
   HTTP boundary: fields are nullable so validation produces clean 422s. Ref `CreateProductRequest`.
2. `src/<Module>/Application/<UseCase>/<UseCase>Command.php` — `final readonly` DTO of validated intent.
3. `src/<Module>/Application/<UseCase>/<UseCase>CommandHandler.php` — `final readonly`; injects the
   Domain `*Repository` (+ any `*Existence` ports). Generates ids (`XxxId::generate()`), builds the
   aggregate (`Aggregate::create(...)`), checks referenced-aggregate existence and throws a domain
   `NotFoundException` (→ 404) on a miss, then `repository->save(...)`. Returns the aggregate (or View).
4. Domain (only if a new aggregate): `src/<Module>/Domain/<Aggregate>.php` (private ctor + `::create()`,
   getters, no setters, invariants in the ctor), `<Aggregate>Id.php` (extends `Uuid`, `::generate()` v7),
   `<Aggregate>Repository.php` (write port) and the adapter
   `src/<Module>/Infrastructure/Persistence/Doctrine/Doctrine<Aggregate>Repository.php`.
5. If a new id type: `src/<Module>/Infrastructure/Persistence/Doctrine/<Aggregate>IdType.php` (extends
   `AbstractUidType`) and register it in `config/packages/doctrine.yaml`.
6. `src/<Module>/Application/<Aggregate>View.php` — `final readonly` read model with a `fromXxx()`
   factory; the Action serializes it.
7. `src/<Module>/Infrastructure/Http/<UseCase>Action.php` — `#[Route('/api/...', methods: ['POST'])]`;
   deserialize the Request, `validator->validate()` → throw `ValidationFailedException` if any
   violation, invoke the handler, return `new JsonResponse($view, Response::HTTP_CREATED)`.
8. A migration (`migrations/`) for the new table/columns **and** the matching `#[ORM\Index]` / mapping
   on the entity — keep `doctrine:schema:validate` in sync.

## Read endpoint — canonical example: `GET /api/shops`

1. `<UseCase>Request.php` — query params, `#[Assert\*]` + `#[MapQueryString]`; use `#[Assert\Callback]`
   for cross-field rules (cf. the geo all-or-nothing trio). Ref `SearchShopsRequest`.
2. `Application/<UseCase>/<UseCase>Query.php` — `final readonly`, carries value objects (`Pagination`,
   `SearchArea`…), not raw strings.
3. `Application/<UseCase>/<UseCase>QueryHandler.php` — injects the Application `*Finder` port and
   delegates `finder->...($query)`. No aggregate load, no write.
4. `Application/<...>Finder.php` — read port returning `Paginated<View>` (or `View[]`).
5. `Infrastructure/Persistence/Doctrine/Doctrine<...>Finder.php` — implements it via raw DBAL
   (`Connection::createQueryBuilder`), maps rows with `View::fromRow(...)`, paginates with
   `Pagination::offset()` / `limit`, wraps in `Paginated::fromPagination(...)`.
6. `<...>View.php` — read model with a `fromRow()` factory.
7. `<UseCase>Action.php` — `#[Route(..., methods: ['GET'])]`, returns `new JsonResponse($paginated)`.

Reuse `src/Shared/Application/{Pagination,Paginated}.php` for any listing.

## Wiring

- Bind every interface → adapter in `config/services.yaml` (finders go under the `# Read` section).
- Routes are attribute-based; constrain `{id}` path params with `Requirement::UUID`.

## Tests (required)

Tests are part of the slice — don't skip them. You need:

- a **functional** test for the endpoint's HTTP behavior (its contract), and
- a **unit** test for any pure domain logic / value-object invariants introduced.

For *how* to write them (groups, seeding, assertions, what to test vs. not), follow the project's
test conventions: **delegate to the `tester` agent**, or see `.claude/agents/tester.md`.

## Docs (required — do not skip)

- Update `docs/api.md` with the endpoint contract (params, body, responses, error codes, example).
- Add the route to the API table in `README.md` if it's a new one.

## Verify

`make test` (or `make test-unit` for fast feedback) and GrumPHP green (PHPStan L8 / CS-Fixer / Deptrac).
