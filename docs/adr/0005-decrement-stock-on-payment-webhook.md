# 0005. Decrement stock on the payment webhook, not at checkout

- **Status:** Accepted
- **Date:** 2026-05-28

## Context

Payment is asynchronous. With Pix especially, a buyer initiates payment and the
provider (Mercado Pago) confirms it later, out of band, via a webhook. If we
decremented stock at checkout, an abandoned or failed payment would wrongly consume
inventory; if we waited until checkout with no reservation, two buyers could oversell
the same last unit.

## Decision

At checkout we will create a **reservation** movement (ADR-0004) against the variant
with a TTL. The **confirmed-payment webhook** is the trigger that converts the
reservation into a definitive `saida`. If the reservation TTL expires before
confirmation, a `liberacao` movement releases the stock.

Webhook processing is **idempotent**: the same Mercado Pago event delivered more than
once produces exactly one stock movement and one order state transition (see
ADR-0007 and the Payment context).

## Consequences

- No overselling (reservation protects the unit) and no phantom consumption (only
  confirmed payment commits the sale).
- The source of truth for "sold" is the payment confirmation, which matches reality.
- Requires a reservation-expiry mechanism (scheduled job) and idempotency keys on
  webhook handling.
- Slightly more moving parts than a naive "decrement on order create", but it is the
  correct model for asynchronous payments and demonstrates real webhook discipline.
