---
name: add-adr
description: Record a new Architecture Decision Record (ADR) under docs/adr/ following the project's format, and update the index. Use when an architectural decision has been made and should be documented.
---

# Add an ADR

The project documents every architectural decision as a numbered ADR in `docs/adr/`.

## Steps

1. **Next number**: `ls docs/adr/` → take the highest `NNNN-` and add 1 (zero-padded to 4 digits).
2. **Slug**: a short English kebab-case summary of the decision (e.g. `native-sql-geo-search`).
3. **Create** `docs/adr/NNNN-<slug>.md` using the format below (English). Template reference:
   `docs/adr/0007-stock-aggregate-upsert.md`.
4. **Cross-link** related ADRs inline: `[ADR N](000N-other-slug.md)`.
5. **Index**: add a row to `docs/adr/README.md` (number + link, one-line title, date, status).

## Format

```markdown
# N. <Title>

- **Status**: accepted
- **Decision date**: YYYY-MM-DD
- **First applied**: <feature / context where it first lands>

## Context

<the problem, the forces and constraints at play>

## Decision

<what we decided, concretely>

## Consequences

**Positive**

- ...

**Negative / limitations**

- ...
```

Keep it concise and decision-focused: an ADR records a decision *made*. Future or undecided paths
belong in `docs/roadmap.md`, not here.
