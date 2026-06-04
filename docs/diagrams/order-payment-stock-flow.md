# Flow: order → payment → stock

The core asynchronous flow. The key idea (ADR-0005): checkout only **reserves** stock;
the **confirmed-payment webhook** is what commits the sale and decrements stock. The
webhook is idempotent.

```mermaid
sequenceDiagram
    actor Buyer
    participant API as Sunkissed API
    participant Catalog as Catalog (stock ledger)
    participant MP as Mercado Pago
    participant Queue as Queue (Horizon)

    Buyer->>API: POST /v1/orders (checkout)
    API->>Catalog: reserve(variant, qty) [movement: reserva, TTL]
    Catalog-->>API: reserved
    API->>MP: create Checkout Pro preference
    MP-->>API: preference + init_point (redirect URL)
    API-->>Buyer: order created + redirect URL
    Buyer->>MP: pays (Pix or card) on Mercado Pago

    Note over MP,API: Later, out of band
    MP->>API: POST /v1/payments/webhook (payment event)
    API->>API: verify signature + idempotency key
    alt event already processed
        API-->>MP: 200 OK (no-op)
    else first time
        API->>Queue: dispatch ProcessPaymentConfirmation
        API-->>MP: 200 OK
        Queue->>Catalog: commit reservation → movement: saida
        Queue->>API: order state: pago
        Queue->>Buyer: send confirmation (email)
    end

    Note over Catalog: If reservation TTL expires first
    Queue->>Catalog: release → movement: liberacao
```

## Notes

- **Reservation TTL**: a scheduled job releases reservations whose TTL passed without
  a confirmed payment (`STOCK_RESERVATION_TTL_MINUTES`).
- **Idempotency**: the webhook records processed event ids; a duplicate delivery is a
  no-op, so stock is decremented exactly once and the state machine transitions once.
- **State machine**: `aguardando_pagamento → pago → em_preparacao →
  a_caminho | pronto_para_retirada → concluido` (with `cancelado` / `expirado` paths).
  Only valid transitions are allowed.
- **Why a queue**: the webhook returns 200 fast; the actual work (stock commit,
  notifications, audit archive write) runs asynchronously via Horizon.
