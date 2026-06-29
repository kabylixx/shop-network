# Possible evolutions

Evolution paths **not yet decided** at this stage (an ADR records a decision *made*;
these paths are future, so they live here and not in [`docs/adr/`](adr/)).
Each one references, where relevant, the current decision it extends.

## Authentication & caller identity (`Identity`)

The API is open today: "the client" of the brief is implicit. In production, we
would introduce authentication (token / OAuth2 via `symfony/security`) and a
notion of `Identity` (the authenticated merchant) propagated through the
Application layer. It would become the **multi-tenant consistency boundary**: a
product, a shop, a stock belong to a merchant, and every command/query would be
filtered by this identity (access control living in the handler, not just at
routing). The hexagonal boundary (see [ADR 1](adr/0001-pragmatic-hexagonal-architecture.md))
makes the addition local: aggregates would carry a `MerchantId`, without
disrupting the existing business logic.

## Command bus

Today the Actions invoke their handler directly
(`CreateProductCommandHandler`), which is explicit and free of indirection. A bus
(via `symfony/messenger`) would become relevant for applying cross-cutting
concerns uniformly (transactional boundary, domain event dispatch, logging) or
for processing commands asynchronously (transport / queue). Key point: the
addition would happen **without touching the domain** — the handlers and the
domain stay unchanged — which illustrates the value of the hexagonal boundary.

## Read-side caching (HTTP, then Redis)

Read endpoints (`GET /api/products`, `GET /api/stock`, shop search…) hit the
database on every call. At higher traffic we would cache the read side, which the
CQRS-light split makes clean: the read ports (`ProductFinder`, `ShopFinder`,
`StockFinder`) are separate from the write repositories, so a cache wraps **only
the reads** without touching the write path. First step, cheap and standard: HTTP
caching (`ETag` / `Cache-Control`) on the listing responses. Next step: a **Redis**
read-through cache behind the finders, invalidated by the write commands (a stock
upsert evicts the affected product/shop keys). The read/write port separation also
lets the cached read source change later without the Application layer noticing.

## Generated OpenAPI documentation

The endpoint contract is hand-written in [`docs/api.md`](api.md), which can drift
from the code. We deliberately avoided API Platform (see
[ADR 2](adr/0002-no-api-platform.md)), but OpenAPI generation does not require it:
a library such as `nelmio/api-doc-bundle` derives an `openapi.json` from the
existing attributes (routes, `#[MapRequestPayload]` / `#[MapQueryString]` request
objects, validation constraints), served at a `/api/doc` endpoint. A CI step would
regenerate the spec and **fail the build — or open a PR — when an endpoint changes
without its documentation**, keeping the contract machine-readable and always in
sync.

## Translatable error messages

Each violation already exposes a stable `code`, which lets the **client** handle
i18n according to its user's locale (the recommended approach for an API: the
server message stays an indicative default, the `code` is the contract). If the
API had to serve already-localized messages, we would enable i18n on the
**server side** via the Symfony Translator (constraint messages as translation
keys, per-language catalogs, locale negotiation on the `Accept-Language` header).

## Application error codes

Today the violations' `code` is Symfony's native identifier (a UUID per
constraint type). We could expose our **own error code registry**
(e.g. `PRODUCT_NAME_REQUIRED`, `INVALID_URL`): one code = one precise error, which
**simplifies the error dictionary on the front-end side** (direct code → localized
message mapping). Implementation: derive a readable code from the constraint type
in the listener, with per-field override possible via the constraints' `payload`
option.

## Spatial index for high-volume geo search

The current query (`ST_Distance_Sphere` on scalar `latitude`/`longitude`
columns, see [ADR 6](adr/0006-native-sql-geo-search.md)) does a full scan, with no
perceptible cost at the target scale. For very large volumes, we would add a
`POINT SRID 4326` column with a **spatial index** and a *bounding box* pre-filter
(`MBRContains`) before the exact distance computation, or even a move to
**PostGIS** (`geography` + GiST index) — the alternative ruled out at this stage
(see [ADR 3](adr/0003-mysql-database-choice.md)). An evolution isolated to the
infrastructure: the `ShopFinder` port and the domain are unchanged.

## Search by address (geocoding)

Raw entry of `lat`/`lng` is impractical for a human. A geocoding service
(address → coordinates) on the server side would enable a "near such an address"
search, reusing the existing geographic filter as is.

## Cross-module existence check via a published contract

`Inventory` today checks the existence of a product / a shop by querying their
tables directly (coupling limited to the schema, see
[ADR 8](adr/0008-cross-module-existence-ports.md)). If the modules had to be more
isolated (separate deployments, a database per module), the owning module would
publish a **dedicated read port** (e.g. `Catalog` exposing a `ProductExistence`
contract), consumed by `Inventory` — removing the shared schema in favor of an
explicit application contract.
