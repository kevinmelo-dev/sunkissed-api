# 0014. Product images belong to the (product, color) pair, not to the variant

- **Status:** Accepted
- **Date:** 2026-06-05

## Context

Variants are the color × size combination (ADR-0003, ADR-0012). A product photo
reflects the **color**, not the size — "Top Rio black" has the same photos in sizes S,
M, and L. The `image` field (a single string) that currently exists on
`product_variants` — introduced as a placeholder in the `UpdateProductVariant` use case
— models the image at the wrong level: attaching it to the variant (color × size) would
duplicate the same set of photos across every size variant of the same color, requiring
repeated uploads or fragile synchronization.

Additionally, the storefront needs **multiple images per color** (a gallery: front, back,
detail), not a single one. The product listing (following common market patterns, e.g.
fitness stores) presents a color selector that swaps the photo gallery and a separate
size selector — which requires the backend to group variants by color and serve the
images for that color.

**Alternative considered and rejected:** Keep the image on the variant (the current
field). Rejected because it causes duplication across size variants of the same color
and does not reflect that the photo belongs to the color, not the size.

## Decision

We will model images at the **(product, color)** level. A new table (e.g.
`product_color_images`) associates a product + color pair with one or more images,
each carrying a sort order (`sort_order` or `position`).

The `image` field on `product_variants` is **removed** (via a drop migration), and the
`UpdateProductVariant` use case stops handling it.

The product **cover image is derived, not stored**: it is the first image (lowest
`sort_order`) of the active color with the lowest `sort_order` associated with the
product. No extra column is needed.

Images are stored in S3-compatible storage via Laravel's `s3` driver (consistent with
ADR-0008, which already anticipates product images in the same storage bucket), agnostic
between AWS S3 and Cloudflare R2 — the provider choice (R2 in the MVP) is a `.env`
configuration, not code. We persist the **object key** in the bucket; the public URL is
derived at display time and is never stored.

## Consequences

**Better:**

- Images are modeled at the level that reflects reality (color), with no duplication
  across size variants. The admin uploads photos for a color once.
- The storefront can group variants by color and build a color + size selector with the
  correct gallery per color.
- The cover image comes for free from the derivation rule, requiring no extra column or
  write path.
- Storing the object key (not the URL) preserves portability between S3 and R2 and
  allows CDN changes without a data migration, consistent with ADR-0008.

**Costs and trade-offs:**

- A new `product_color_images` table is required.
- The `image` field must be dropped from `product_variants` — a change to existing code
  (`UpdateProductVariant` and its controller/form request).
- Deriving the cover image requires a query (active color with lowest sort order → first
  image); it is not a free column read.
- If photos ever need to vary by size (not the case today), this ADR must be revisited.
