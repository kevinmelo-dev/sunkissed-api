# 0010. Color and size as manageable entities, not enums or value objects

- **Status:** Accepted
- **Date:** 2026-06-04

## Context

Product variants are defined by a (product, color, size) combination. The question is how
to represent color and size: as PHP enums or value objects baked into the codebase, or as
database rows managed by the store admin.

Using PHP enums or domain value objects would mean that adding a new color or size requires
a code change and a deploy. For a swimwear store that regularly adds seasonal colorways and
new size options, this is an unacceptable constraint: the admin must be able to add "Rosa
Chiclete" or "XXL" without involving a developer.

The alternative — a free text field on the variant — was also considered but rejected
because it produces inconsistent spellings ("rosa", "Rosa", "ROSA") and makes filtering and
display ordering impossible without fragile normalization.

## Decision

We will model `colors` and `sizes` as separate database tables with full CRUD managed by
the store admin.

- `colors`: id, name (unique), hex (nullable, for UI swatch display), active flag.
- `sizes`: id, name (unique), sort_order (smallint), active flag.
- `product_variants` references both via foreign keys.
- `sizes.sort_order` is a domain rule: the display order of sizes is not alphabetical
  (XS < S < M < L < XL < XXL) and must be managed explicitly.
- `ProductType` (kit/single) remains a PHP enum because it is structural — adding a new
  product type requires code changes regardless.

## Consequences

- Store admin can add colors and sizes without a deploy.
- `sizes.sort_order` must be set correctly by the admin; incorrect ordering is a data
  problem, not a code problem.
- Two additional CRUD use cases (manage colors, manage sizes) are needed in the admin API.
- The domain holds `Color` and `Size` as entities with ids; `ProductVariant` references
  them by id, not by embedding the full entity, keeping aggregates bounded.
- `ProductType` remains an enum, distinguishing structural decisions (code) from
  operational data (database).
