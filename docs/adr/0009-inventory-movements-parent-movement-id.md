# 0009. Explicit parent_movement_id on inventory_movements

- **Status:** Accepted
- **Date:** 2026-06-04

## Context

ADR-0004 establishes that stock is tracked as an append-only ledger of movements. ADR-0005
establishes that checkout creates a `reserva` and the payment webhook converts it into a
`saida`. The question is: how does the system know *which* `saida` consumes *which* `reserva`?

The alternative considered was inference: match a `saida` to a `reserva` by shared
`reference` field and proximity in time. This approach is fragile under concurrent
checkouts — two requests for the same variant could race and produce ambiguous matches,
especially when a payment webhook arrives while a second reservation is in flight for the
same reference. It also makes the ledger harder to audit, because the link between movements
is implicit rather than structural.

## Decision

We will add a self-referencing foreign key `parent_movement_id` on `inventory_movements`.

- A `saida` created by `commitReservation` sets `parent_movement_id` to the id of the
  `reserva` being consumed.
- A `liberacao` created by `releaseReservation` sets `parent_movement_id` to the id of
  the `reserva` being released.
- `expires_at` is only allowed on `reserva` rows.
- `parent_movement_id` is only allowed on `saida` and `liberacao` rows.
- These invariants are enforced in the `InventoryMovement` entity constructor and in
  `StockLedger`.
- A reservation is "active" (counts against available stock) if and only if it has no
  child row referencing it as `parent_movement_id`.

## Consequences

- Stock consumption is deterministic and auditable: each `saida`/`liberacao` points
  unambiguously to its parent `reserva`.
- Concurrent payment webhooks for different orders cannot interfere — each reservation
  has its own id and child.
- `availableFor` filters consumed reservations by checking for children, keeping the
  ledger query straightforward.
- Adds one FK and a disciplined rule that callers (`StockLedger`) must uphold.
- Aligns with ADR-0005 (stock decremented at webhook) and ADR-0004 (append-only ledger).
