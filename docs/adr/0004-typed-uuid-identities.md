# 4. Typed per-aggregate identities, as UUID v7

- **Status**: accepted
- **Decision date**: 2026-06-27
- **First applied**: product identifier, generalized to shops, managers and stock

## Context

Every aggregate needs an identifier. Two classic risks: mixing up
identifiers of different aggregates (passing a shop id where a product id is
expected) and exposing a persistence detail (auto-increment) in the
HTTP contract.

## Decision

A **per-aggregate typed identity Value Object**: `ProductId`, `ShopId`,
`ManagerId`, `StockId`, each extending `Symfony\Component\Uid\Uuid`
(`symfony/uid`).

- **UUID v7** (time-ordered): identifiers follow creation order,
  which yields naturally ordered indexes/paginations.
- **Generated in the Application layer** (the handler), not by the database nor by
  the client: the identity is part of the business decision to create.
- Stored as **`BINARY(16)`** via custom per-aggregate Doctrine types.

## Consequences

**Positive**

- **Type-safety**: the signature `setStock(ProductId $p, ShopId $s)` makes
  swapping the identifiers impossible — an error caught at compile time,
  not at runtime.
- **Opaque and unguessable** identifiers in the API; no coupling to the
  storage schema.
- Time ordering useful for pagination and index locality (v7 vs v4).

**Negative / limitations**

- `BINARY(16)` is less readable than an integer in SQL debugging (requires
  `HEX()` / conversion) — a minor cost, accepted.
- One Doctrine type + one VO per aggregate: a deliberate choice in favor of strong typing.
