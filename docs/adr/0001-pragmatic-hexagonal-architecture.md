# 1. Pragmatic hexagonal architecture

- **Status**: accepted
- **Decision date**: 2026-06-27
- **First applied**: project foundation, from the very first catalog endpoint

## Context

The project exposes a server API covering three business concerns — the product
catalog, the shop network, the stock — within a **single bounded context**. We
want to isolate the business logic from the infrastructure (Doctrine, HTTP,
MySQL) so that it stays testable and the technical choices remain replaceable,
**without** paying the cost of a canonical hexagonal architecture (one
project/deployment per module, interfaces for everything).

## Decision

A **pragmatic hexagonal** architecture, organized into **modules**
(`Catalog`, `Network`, `Inventory`, plus `Shared`), each split into three
layers:

- **Domain** — aggregates, Value Objects, ports (interfaces); no framework
  dependency.
- **Application** — commands/queries and their handlers, read models, read
  ports (CQRS-light).
- **Infrastructure** — Doctrine/DBAL adapters, HTTP Actions, mapping.

Conventions adopted, in a "just enough" spirit:

- **CQRS-light**: writes go through a `…Repository` (Domain) that hydrates
  the aggregate; reads go through a dedicated *finder* (Application) that returns
  **read models** without hydrating the aggregate.
- **Minimalism of abstractions**: we introduce a Value Object, a port or
  an indirection only when it protects an invariant or a real decoupling — not
  out of reflex (see [ADR 5](0005-references-by-identity.md) for `address`
  kept as a `string`).

The boundaries between layers are **automatically verified** by Deptrac
(Domain → Application → Infrastructure), wired into the QA pipeline.

## Consequences

**Positive**

- Business logic testable without infrastructure; adapters replaceable.
- Boundaries explicit and guaranteed by tooling (Deptrac), not merely
  by convention.
- Cross-cutting changes possible **without touching the domain** — e.g. adding
  a command bus (see [roadmap](../roadmap.md)).

**Negative / limitations**

- Some ceremony (commands/handlers/ports) for simple cases; accepted
  as the price of separation.
- Modules in the **same** deployment and the **same** database: the isolation is
  logical, not physical (see [ADR 8](0008-cross-module-existence-ports.md) for
  the residual cross-module coupling).
