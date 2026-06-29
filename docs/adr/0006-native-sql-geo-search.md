# 6. Geolocation search via `ST_Distance_Sphere` (native SQL)

- **Status**: accepted
- **Decision date**: 2026-06-27
- **First applied**: searching for shops by proximity

## Context

Searching for shops by proximity to a point must filter those within a given
radius, sort them from nearest to farthest, and return their distance.
Computing a geographic distance in PHP (the haversine formula) and then
sorting/filtering in memory would require loading every shop and would
**duplicate** logic that the database already knows how to do.

## Decision

Delegate **computation, filtering, and sorting to MySQL** through a native DBAL
query using `ST_Distance_Sphere` (the database choice itself, and the
alternatives that were ruled out — notably PostGIS — are recorded in
[ADR 3](0003-mysql-database-choice.md)):

```sql
ST_Distance_Sphere(POINT(longitude, latitude), POINT(:lng, :lat))
```

- The result is exposed in a **read model** `ShopView` enriched with a
  `distance` (in meters) — CQRS-light, without hydrating the `Shop` aggregate
  (see [ADR 1](0001-pragmatic-hexagonal-architecture.md)).
- The search center and radius are carried by a value object (VO)
  **`SearchArea`** (`Coordinates` + radius in meters), which makes the illegal
  state "center without radius" **non-representable** and guarantees its
  invariants (coordinate bounds, radius > 0).
- Geolocation is **optional**: without it, sorting is by name and `distance` is
  `null`.
- `closed` shops are excluded from this search.

## Consequences

**Positive**

- **Single source of truth** for distance: no formula duplicated in PHP.
- Filtering and sorting executed as close to the data as possible, over the full
  indexable set.
- `SearchArea` prevents an inconsistent call from the moment the query is built.

**Negative / limitations**

- Since `latitude`/`longitude` are stored as **scalar columns**, `POINT()` is
  rebuilt per row → **full scan**, with no spatial index. This is optimal at the
  intended scale (hundreds to thousands of shops); moving to high volume
  (spatial index / PostGIS) is covered in the [roadmap](../roadmap.md).
- Coupling to MySQL's spatial dialect, isolated within the infrastructure
  adapter.
