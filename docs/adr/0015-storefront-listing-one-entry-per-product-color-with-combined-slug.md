# 0015. Storefront lists one entry per (product, color) with a combined slug

- **Status:** Accepted
- **Date:** 2026-06-07

## Context

The domain model treats a product as having color × size variants (ADR-0003, ADR-0012).
The product detail page already delivers a product with all its colors nested, and images
are scoped to the (product, color) pair (ADR-0014). What was missing was a definition of
how the storefront **listing** and **product URLs** behave.

The business requirement — consistent with the fitness/swimwear market norm — is that each
color appears as its own navigable card on the storefront ("Conjunto Rio Azul",
"Conjunto Rio Rosa" as distinct cards), each with a canonical, shareable URL where the
color is embedded in the product slug (e.g. `conjunto-rio-azul`), not as a path segment
(`conjunto-rio/azul`) or a query parameter (`?cor=azul`). Opening a card shows that color
pre-selected, but the page still offers the full color selector for the product.

Two real alternatives were considered for URL resolution:

1. **Combined slug persisted and unique per (product, color):** a dedicated table stores
   the slug, generated as `{product-slug}-{color-slug}` at association time.
2. **Derived on the fly from `{product-slug}-{color-slug}` at request time:** no extra
   table; the slug is parsed back to (product, color) at read time.

Option 2 was discarded because of **parsing ambiguity** — given a slug like
`conjunto-rio-azul` there is no unambiguous rule for where the product slug ends and the
color slug begins, especially with compound names — and because it is fragile when either
name changes independently.

## Decision

We will introduce the (product, color) pair as a **storefront unit** with its own
persisted, unique slug, materialized in a `product_colors` table
(`product_id`, `color_id`, `slug` unique, timestamps). The slug is generated as
`{product-slug}-{color-name-slug}` when the color is associated with the product.

**Storefront listing** (`GET /catalog/products` and equivalent filtered endpoints) will
return one entry per (product, color), not one entry per product. Each entry carries:
the combined slug (for the link), the product name, the color name and hex value, the
cover image for that color, and the price.

**Visibility rule — a (product, color) pair appears in the storefront only when all of
the following hold:**

- The product is active.
- The color is active.
- The color has at least one image for this product.
- The color has at least one sellable variant for this product — meaning an active variant
  with a price greater than zero that has available stock according to the inventory
  movement ledger (ADR-0004).

Nothing "phantom": everything visible on the storefront is purchasable.

**Out-of-stock handling:** a pair is not hidden when some of its sizes are sold out. The
card disappears only if the color has *no* sellable size at all. Individual sold-out sizes
are surfaced as unavailable inside the card/detail page; the front-end may offer a
"notify me" affordance. This avoids hiding products that are partially available.

**Detail page:** accessed by the combined slug. The backend resolves the slug to
(product, color), then returns the full product structure with all colors nested
(structure from ADR-0014), indicating the pre-selected color. The **domain model does not
change**: a product continues to own color × size variants. This is a presentation layer
projection over the existing model.

The cover-color flag (currently `products.cover_color_id`, ADR-0014) may migrate to the
`product_colors` table as a boolean per pair, or remain on the product — either is
acceptable at implementation time, so long as exactly one cover per product is enforced.

## Consequences

**Positive:**

- The storefront matches the business expectation: one card per color, canonical and
  shareable URL per color, clean for SEO and social sharing.
- Slug uniqueness is enforced at the database level; no parsing ambiguity.
- Eligibility logic is centralised in the listing query (product active + color active +
  has image + has stock), making it easy to audit and test.
- Consistent with ADR-0014 (images scoped to (product, color)) and ADR-0004/ADR-0005
  (availability derived from the ledger).

**Negative / trade-offs:**

- A new `product_colors` table must be created, populated, and kept in sync when products
  or colors change.
- The combined slug must be regenerated when the product name or color name changes. Stale
  slugs held by external links may break; slug redirect handling is explicitly out of MVP
  scope and can be revisited.
- The storefront listing query is more complex: it must join images, variants, and the
  stock ledger to evaluate eligibility per pair.
- "Color as a domain concept" must not creep back: `product_colors` is a **storefront
  projection**, not a new aggregate root. Domain operations (pricing, stock, variant
  management) continue to go through the product + variant model.
