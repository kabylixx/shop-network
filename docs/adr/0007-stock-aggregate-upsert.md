# 7. `Stock`, an independent aggregate; writes as per-couple upsert

- **Status**: accepted
- **Decision date**: 2026-06-28
- **First applied**: setting stock, extended to reading it

## Context

Stock links products and shops. Modeling it as a collection carried by the
`Shop` (or `Product`) aggregate would be harmful: a shop may hold thousands of
stock lines, which would then be loaded along with the aggregate. We also need
to decide on the write semantics (`PUT` of a list of couples) and the read
semantics (should a product's quantities be summed across shops?).

## Decision

`Stock` is an **independent aggregate**, in line with the "small aggregates
referenced by identity" rule:

- Its own identifier `StockId`; references `ProductId` and `ShopId` **by
  identity** (see [ADR 5](0005-references-by-identity.md)).
- **Uniqueness of the `(shop, product)` couple** enforced by a database
  constraint.
- Quantity = value object (VO) **`Quantity`** (integer ≥ 0; `0` = product
  referenced as out of stock).

**Write** (`PUT /api/products/{id}/stock`): **per-couple upsert** within a
single transaction — each line creates or replaces the quantity of the couple;
shops absent from the body keep their stock; an unknown identifier rejects the
whole request (all-or-nothing).

**Read**: the result is **broken down by shop, never summed** — a product
present in two shops yields two lines (a `GROUP BY` + `SUM` would contradict the
"detail per shop" need). The read is deliberately **status-agnostic**: a closed
shop keeps its stock visible. Two **distinct use cases** share the
`StockFinder`:

- `GetStockByShops` (`/api/stock`) — **lenient filter**: an unknown shop is
  ignored (`WHERE shop_id IN (...)` semantics), no `404`.
- `GetShopProducts` (`/api/shops/{id}/products`) — the shop is a **resource**:
  if it does not exist → `404` (via [ADR 8](0008-cross-module-existence-ports.md)).

## Consequences

**Positive**

- No loading of thousands of lines via `Shop`/`Product`; consistency held at the
  couple level.
- Predictable write semantics (idempotent per-couple upsert, atomic).
- Read aligned with the business need (detail per shop), and an explicit
  filter/resource asymmetry ("one Action = one handler").

**Negative / limitations**

- The `(shop, product)` uniqueness is enforced in the database; any write
  outside the application must respect it.
- "Never summed" is a business choice: a future need for an aggregate (total per
  product) would be a **new** read, not a modification of this one.
