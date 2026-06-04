# 0003. Model a kit as a fixed product, not a dynamic bundle

- **Status:** Accepted
- **Date:** 2026-05-28

## Context

About 95% of sales are **kits** — a matching top and bottom sold together. The
remaining ~5% are standalone pieces (jumpsuits, jackets, bands). Kits come in size
(P/M/G) and color variations, and the pieces in a kit always share the same size and
color (no mix-and-match).

Two modeling approaches were considered:

- **Fixed product (bundle as a unit):** "Conjunto Princesa" is a product; its variants
  are size × color, each a sellable SKU with its own stock.
- **Dynamic bundle (composition of pieces):** the top and bottom are independent
  products with their own stock, and the kit is a relation between them.

The dynamic bundle is more elegant and would make selling pieces separately trivial,
but it solves the 5% case at the cost of complicating the 95% case (deriving and
keeping kit stock consistent with piece stock).

## Decision

We will model a **kit as a fixed product** with size × color variants, each variant a
sellable SKU with its own stock in the ledger. The `Product` entity carries a `type`
(`kit` | `single`) from the start. Standalone pieces are modeled as `single`
products. Mix-and-match is not supported.

## Consequences

- The common case (kits) is simple and matches how the owner already thinks about
  inventory ("I have 3 Conjunto Princesa pink M").
- A piece that exists both inside a kit and as a standalone is modeled twice. This is
  a conscious, honest duplication for the MVP.
- If standalone sales of kit pieces become frequent, we can revisit and introduce the
  dynamic-bundle model in a v2. The `type` field leaves room for that evolution.
