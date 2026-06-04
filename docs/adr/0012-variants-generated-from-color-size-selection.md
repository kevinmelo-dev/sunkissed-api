# 0012. Variants generated from a product's color × size selection

- **Status:** Accepted
- **Date:** 2026-06-04

## Context

ADR-0003 established that a kit (or standalone piece) is a **product with variants**,
where each variant is a unique size × color combination and carries its own SKU, price,
and stock position in the movement ledger (ADR-0004).

What ADR-0003 left open is **how variants come into existence**. Two approaches were
considered:

1. **Manual creation** — the admin creates each variant individually, specifying color,
   size, SKU, price, and images one at a time.
2. **Generated from a selection** — the admin picks a set of colors and a set of sizes
   on the product; the system computes the Cartesian product and generates all
   combinations at once.

Option 1 was rejected as the common case for the store: a kit offered in 2 colors and
3 sizes would require 6 separate form submissions, each prone to omission or
inconsistency. The manual path remains meaningful only for edge cases (e.g., a
one-of-a-kind piece), which option 2 also handles — a single color and a single size
still produces one variant.

## Decision

We will generate variants from the **Cartesian product of the product's selected colors
and sizes**. A product stores a set of associated colors and a set of associated sizes
(referencing the catalogue entities from ADR-0010); variants are derived from those
sets.

The rules for editing the sets after the initial generation are:

- **Adding** a color or size generates the missing variant combinations only, leaving
  existing variants untouched.
- **Removing** a color or size **deactivates** (`active = false`) the affected variants.
  It never deletes them. A variant may have stock movement history in the append-only
  ledger (ADR-0004); deleting it would corrupt that history.
- **Reactivating**: if a color or size is re-added, the use case reuses the existing
  (previously deactivated) variant record rather than inserting a duplicate, relying on
  the `UNIQUE (product_id, color_id, size_id)` constraint to enforce this.

SKUs are **auto-generated** at creation time by a deterministic system convention (e.g.,
derived from the product slug, color code, and size code) but remain **editable** by
the admin afterward. The SKU value object must accept both the generated value and an
admin-supplied override while preserving uniqueness.

Variant price and images are editable independently after generation.

## Consequences

- The common workflow (kit in N colors × M sizes) collapses to one operation instead of
  N×M individual creates.
- A dedicated **GenerateVariants** use case emerges in the Application layer, handling
  both initial generation and incremental updates (add/remove color or size).
- The domain invariant that a deactivated variant must never be hard-deleted while it
  has ledger history must be enforced in the use case; the append-only ledger (ADR-0004)
  is the authoritative reason.
- The SKU value object must handle auto-generation on creation and accept an explicit
  override, while enforcing global uniqueness. This is slightly more complex than a
  purely admin-supplied SKU.
- Edge cases in the reactivation path (a previously deactivated variant being revived
  when its color/size returns) must be handled explicitly in GenerateVariants to avoid
  `UNIQUE` constraint violations.
