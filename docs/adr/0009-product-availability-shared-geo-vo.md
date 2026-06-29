# 9. Geo value objects in `Shared\Domain`; a cross-module read composing the modules

- **Status**: accepted
- **Decision date**: 2026-06-29
- **First applied**: `GET /api/products/{id}/availability` (availability of a product in stores)

## Context

Product availability — "where, and near me, is this product in stock?", the
retail *find in store* feature — is a **read that crosses the three modules**:
catalog (`Catalog`), stock (`Inventory`), and shop geolocation (`Network`). It
reuses the geolocation search (see [ADR 6](0006-native-sql-geo-search.md)), until
now specific to `Network`: the value objects `Coordinates` and `SearchArea`
lived in that module.

A **second consumer** (`Inventory`) therefore appears for these VOs. Two options
present themselves: **duplicate** them in `Inventory` (a copy of an existing VO),
or make `Inventory` depend on `Network`'s **Application** layer (`SearchArea` was
an artifact of the `SearchShops` use case) — an unrelated coupling of use cases.

## Decision

- **Promote `Coordinates` and `SearchArea` into `Shared\Domain`**: a single
  source of truth, no module "owning" a VO that the other borrows. The rule is
  the same as for typed identities (see
  [ADR 4](0004-typed-uuid-identities.md)): it is the **domain VOs** that cross
  modules, never use case artifacts.
- Availability is a **cross-module read** in CQRS-light (see
  [ADR 1](0001-pragmatic-hexagonal-architecture.md)): a
  `ProductAvailabilityFinder` port (Application `Inventory`) + an
  `AvailabilityView` read model, whose DBAL adapter **joins `stock` and `shop` by
  table name** — the same schema coupling as the existence ports (see
  [ADR 8](0008-cross-module-existence-ports.md)), without a class dependency on
  `Network`.
- Direct reuse of existing decisions: `ST_Distance_Sphere`
  (see [ADR 6](0006-native-sql-geo-search.md)) and the existence check in the
  handler → `404` (see [ADR 5](0005-references-by-identity.md)). The product is
  an addressed **resource**; geolocation is **optional** (an all-or-nothing
  trio); only `open` shops where the product is in stock (`quantity > 0`) are
  returned.

## Consequences

**Positive**

- **Single geo core**: no more duplication of `Coordinates`/`SearchArea`.
- The **model proves its worth**: the aggregates remain small, independent
  boundaries, and it is the *read* that composes them for the business need —
  without introducing an association or a "god object" aggregate.
- Cross-module dependencies kept minimal: shared **domain VOs** (`Shared`) and a
  shared **schema** (join by table name), never a class dependency between the
  Application layers of distinct modules.

**Negative / limitations**

- `Shared\Domain` grows — accepted: these are genuinely cross-cutting VOs, not a
  catch-all. The bar remains "a cross-cutting VO is promoted at the 2nd
  consumer".
- The appearance of a **Domain → Domain** cross-module dependency
  (`Network\Domain\Shop` and `Inventory` toward `Shared\Domain`), allowed because
  `Shared` is the core that everyone depends on (verified by Deptrac: 0
  violations).
