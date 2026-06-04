# 0013. Categories: two-level hierarchy, many-to-many with products

- **Status:** Accepted
- **Date:** 2026-06-04

## Context

The storefront needs to group products into browsable categories (e.g., "Winter Sets",
"Floral Collection"). Two requirements drive the design:

1. A product can belong to **more than one category** at the same time (e.g., "Campo
   Set" appearing under both "Winter Sets" and "Floral Collection").
2. Categories may have a **parent grouping** — a top-level section that contains
   related sub-categories.

No ADR or schema currently covers categories.

The main alternative evaluated was an **unlimited-depth tree** using the standard
`parent_id` self-join with no depth cap, possibly managed through a nested-set or
closure-table pattern.

That alternative was rejected for the MVP. An arbitrary-depth tree introduces
complexity that has no payoff at the store's scale: cycle-prevention logic,
recursive queries (CTEs), and more involved admin UI for rearranging nodes. The desired
grouping is shallow — two levels fully cover all known cases.

## Decision

We will model categories as a `categories` table with a nullable **`parent_id`
self-join**, constrained to **exactly two levels**:

- A **root category** has `parent_id = NULL`.
- A **sub-category** has `parent_id` pointing to a root category record.
- A sub-category cannot have children. This two-level depth cap is a **domain
  invariant** enforced in the application layer, not just a convention.

The relationship between products and categories is **many-to-many**, implemented via a
`category_product` pivot table. A product may be associated with any mix of root
categories and sub-categories.

Categories are admin-managed entities (create, update, deactivate), consistent with how
colors and sizes are managed per ADR-0010.

## Consequences

- Storefront grouping is flexible: a product can surface under multiple navigation
  paths without data duplication.
- The two-level cap eliminates cycles and recursive queries entirely. The invariant
  "a sub-category cannot have children" is checked in the use case when a category is
  created or re-parented.
- Two new tables enter the Catalog bounded context: `categories` and
  `category_product`.
- The domain invariant "a product must reference an existing, active category" is
  enforced at the application layer when associating products with categories.
- If the store eventually requires deeper nesting (e.g., three levels), this ADR must
  be superseded. The migration path is a new ADR and a schema change — the two-level
  assumption is explicit and intentional, not accidental.
