# 8. Cross-module existence ports (coupling limited to the schema)

- **Status**: accepted
- **Decision date**: 2026-06-28
- **First applied**: writing stock (existence of the product and the shops)

## Context

Writing a stock entry assumes the product and the shop(s) exist (see
[ADR 5](0005-references-by-identity.md), existence check in the handler). But
`Product` belongs to the `Catalog` module and `Shop` to the `Network` module.
How does `Inventory` verify this existence **without** depending on the classes
of the other modules?


## Decision

`Inventory` **declares its own ports** in its Application layer —
`ProductExistence`, `ShopExistence` — expressing exactly its need ("does this
identifier exist?"), without assuming anything about status.

- Their infrastructure adapters query the `product` / `shop` tables in **native
  SQL, by table name**.
- `ShopExistence` checks in **batch** (`WHERE id IN (:ids)`): N shops in one
  query.
- Dependency inversion: it is `Inventory` that owns the abstraction; no `use` of
  a `Catalog`/`Network` class.

## Consequences

**Positive**

- Modules **decoupled at the class level**: the need is expressed on the
  consumer side.
- Correct semantics: existence is independent of status (a closed shop exists) —
  no false `404`.
- Batch existence check, frugal in queries.

**Negative / limitations**

- **Residual coupling to the shared schema**: a table name (`product`, `shop`)
  is known outside its module — accepted, and **isolated within a single
  adapter**.
- Should the modules need to be physically isolated (one database per module), a
  contract published by the owning module would be required (see
  [roadmap](../roadmap.md)).
