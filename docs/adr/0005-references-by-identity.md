# 5. References by identity, no foreign key; existence checked in the handler

- **Status**: accepted
- **Decision date**: 2026-06-27
- **First applied**: shop → manager association, reapplied to stock → product/shop

## Context

Aggregates reference one another: a shop has a manager, a stock line
concerns a product and a shop. The temptation is to set up Doctrine
associations (`@ManyToOne`) and SQL foreign keys. But an aggregate is a
**consistency boundary**: loading it to traverse into another blurs
those boundaries and pulls in entire object graphs.

## Decision

Aggregates reference one another **by identity** (`ManagerId`, `ProductId`,
`ShopId`), never by ORM association nor SQL foreign key.

- The **existence** of the target is checked **in the handler** before the write
  (write port for the manager; dedicated existence ports for the stock, see
  [ADR 8](0008-cross-module-existence-ports.md)).
- A missing target raises a **domain exception** extending the shared
  marker `Shared\Domain\NotFoundException`, translated into a **`404`** by the
  Problem Details listener (RFC 7807) — where a foreign key violation would have produced
  a `500`.
- **Validation takes precedence over existence**: a request that is both malformed and
  references a nonexistent target returns `422` (validation runs before
  the existence check).

Corollary of minimalism (see [ADR 1](0001-pragmatic-hexagonal-architecture.md)):
`address` stays a **`string`**, not a Value Object. A VO is justified when it
protects an invariant or groups coherent fields (as with
`Coordinates`); wrapping a free-text address, whose only rule is its
length (already covered by validation and the column), would be ceremony
without any guarantee.

## Consequences

**Positive**

- Clean aggregate boundaries; no unintended transitive loading.
- Explicit business errors (`404`) instead of technical errors (`500`).
- Application layer decoupled from HTTP: the `exception → status` mapping lives in
  a listener.

**Negative / limitations**

- Referential integrity is carried by the **code** (check in the handler),
  not by the database: no FK safeguard in case of a write outside the application.
- One existence check = one extra query; negligible compared to
  the clarity gained, and batchable (see [ADR 8](0008-cross-module-existence-ports.md)).
