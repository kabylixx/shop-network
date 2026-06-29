# Architecture Decision Records

The project's architecture decisions, in the lightweight **Context → Decision →
Consequences** format. An ADR records **one** decision *made*; future paths not
yet decided live in the [roadmap](../roadmap.md), not here.

Each ADR is self-contained: its **decision date** situates when the choice was
made, and "First application" describes the capability that motivated it. These
decisions were formalized retroactively during the project's finishing pass; they
are **ordered by their appearance in development** — each decision precedes the
feature that applies it.

| # | Decision | Date | Status |
|---|----------|------|--------|
| [0001](0001-pragmatic-hexagonal-architecture.md) | Pragmatic hexagonal architecture (modules, CQRS-light) | 2026-06-27 | accepted |
| [0002](0002-no-api-platform.md) | No API Platform | 2026-06-27 | accepted |
| [0003](0003-mysql-database-choice.md) | Choosing MySQL 8 as the DBMS (geo + accent-insensitive search) | 2026-06-27 | accepted |
| [0004](0004-typed-uuid-identities.md) | Per-aggregate typed identities, in UUID v7 | 2026-06-27 | accepted |
| [0005](0005-references-by-identity.md) | References by identity, without foreign keys; existence in handler | 2026-06-27 | accepted |
| [0006](0006-native-sql-geo-search.md) | Geographic search via `ST_Distance_Sphere` (native SQL) | 2026-06-27 | accepted |
| [0007](0007-stock-aggregate-upsert.md) | `Stock`, an independent aggregate; write as upsert by pair | 2026-06-28 | accepted |
| [0008](0008-cross-module-existence-ports.md) | Cross-module existence ports (coupling limited to the schema) | 2026-06-28 | accepted |
