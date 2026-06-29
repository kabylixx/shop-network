# 3. Choosing MySQL 8 as the DBMS

- **Status**: accepted
- **Decision date**: 2026-06-27
- **First applied**: persistence foundation (geo search and search by name)

## Context

The type of database is **free** (brief: "you may use whatever type of database
you wish"). Two read requirements drive the choice:

1. a **geographic search** by proximity (radius filter + distance sort, see
   [ADR 6](0006-native-sql-geo-search.md));
2. a **search by name** that is case-insensitive **and accent-insensitive**
   ("robe" must match "Robe", "Hélène" must match "helene").

The reflex for geo would be PostgreSQL + PostGIS. But at the targeted scale
(hundreds to thousands of shops/products), the stake is not spatial
performance — it is the **robustness/effort/risk ratio** over the lifetime of a test.

## Decision

**MySQL 8** as the single DBMS, relying on its **native** functions to
cover both needs without any extension or additional dependency:

- **Geo**: `ST_Distance_Sphere` (spherical distance in meters, native to MySQL 8) —
  no Doctrine spatial library, no extension to enable.
- **Search by name**: the **`utf8mb4_0900_ai_ci`** collation (*accent-insensitive,
  case-insensitive*) — accent/case insensitivity is handled by the database, without
  any normalized column or application-level denormalization.

## Discarded alternatives

| Option | Why discarded |
|--------|------------------|
| **PostgreSQL + PostGIS** | The geo reference (GiST index, accurate `ST_Distance`), but a spatial index is **useless at the targeted scale**; high cost/risk (extension to enable including in the test database, manual SQL migrations, DBAL 4 compatibility footgun). "Wow effect" without real benefit here. |
| **PostgreSQL + Haversine** | Robust and simple, but the distance formula has to be **written and maintained** yourself, whereas MySQL offers a correct native function. |
| **MySQL POINT + spatial index** | Brings the spatial index (*bounding box* pre-filter), but the same cost/risk as PostGIS (`POINT SRID 4326` column, manual migrations) for zero gain at the targeted volume. |
| **MySQL + Haversine** | Dominated by `ST_Distance_Sphere` (native function) — rewriting the formula brings nothing. |

Verdict: `ST_Distance_Sphere` on MySQL 8 = **best ratio** (correct native
function, zero extension, trivial test database).

## Consequences

**Positive**

- A single DBMS, **with no extension**, covers both geo and text search; the test
  database is trivial to provision.
- Accent/case insensitivity is declarative (collation), not code to be tested.
- No Doctrine spatial dependency and no manual SQL migrations.

**Negative / limitations**

- No spatial index: the geo search does a **full scan** (accepted at the targeted
  scale — details and switchover threshold in [ADR 6](0006-native-sql-geo-search.md)).
- Moving to high volume (MySQL spatial index, or PostGIS) is a change
  isolated to the infrastructure (see [roadmap](../roadmap.md)).
- Known footgun of `ST_Distance_Sphere`: the argument order is
  `POINT(longitude, latitude)` — locked down by the tests.
