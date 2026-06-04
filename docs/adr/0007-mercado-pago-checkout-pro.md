# 0007. Mercado Pago Checkout Pro for the MVP

- **Status:** Accepted
- **Date:** 2026-05-28

## Context

The store needs to accept Pix and credit card. Mercado Pago is the chosen provider
(easy to integrate and test in Brazil). It offers two integration styles:

- **Checkout Pro (redirect):** we create a payment preference and redirect the buyer
  to Mercado Pago's hosted checkout; card data never touches our server; MP handles
  PCI, anti-fraud, the Pix QR code, and confirmation.
- **Checkout Transparente / Bricks:** the buyer pays on our own pages; the card is
  tokenized in the browser and the token passes through our API; full UX control but
  a larger PCI scope and considerably more work (rendering Pix QR, polling pending
  status, etc.).

In both styles, the engineering that matters for this project — webhook idempotency,
the order state machine, asynchronous stock decrement — lives in processing the
payment notification, which is **identical** either way.

## Decision

We will use **Checkout Pro (redirect)** for the MVP. The gateway is abstracted behind
a **`PaymentGateway`** interface, so Transparente (or another provider) can replace it
later without touching the Payment domain.

## Consequences

- No card data touches the server; PCI scope stays minimal — consistent with the
  project's decision to avoid handling sensitive data.
- Pix is simpler: MP generates the QR and confirms via webhook; we only react to the
  confirmation.
- The valuable backend work (idempotent webhook, state machine, stock) is fully
  exercised at a fraction of the effort.
- Cost: one redirect in the buyer flow and less UX control on the payment screen.
  Acceptable, and arguably increases trust for a small unknown store.
