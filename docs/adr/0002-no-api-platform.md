# 2. No API Platform

- **Status**: accepted
- **Decision date**: 2026-06-27
- **First applied**: project HTTP foundation, from the very first catalog endpoint

## Context

API Platform industrializes the creation of REST/GraphQL APIs on Symfony+Doctrine
(entity exposure, pagination, filters, content negotiation, errors). The
project's specification **explicitly forbids** its use.

## Decision

Build the API **by hand**, with the basic Symfony building blocks:

- **Invokable Actions** (one class = one endpoint), no generated CRUD
  controllers.
- Input deserialization/validation via `#[MapRequestPayload]` /
  `#[MapQueryString]` + the Validator component.
- **Controlled representations** on output (read models / `JsonResponse`), never
  the direct exposure of an aggregate.
- Errors in **RFC 7807** format (`application/problem+json`) via a dedicated
  listener.
- Shared in-house pagination (`Pagination` / `Paginated` in `Shared`).

## Consequences

**Positive**

- **Full control** over the HTTP contract: response shape, status codes, error
  format, endpoint semantics.
- No leakage of the persistence structure into the API.
- Demonstrates an understanding of the mechanisms that a high-level framework
  automates.

**Negative / limitations**

- We reimplement what API Platform would provide (pagination, filters,
  OpenAPI documentation) — **limited to the strict need**, so the cost is contained.
- No generated OpenAPI documentation; the contract is described in
  [`docs/api.md`](../api.md).
