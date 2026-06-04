# Context map

The bounded contexts and how they relate. Arrows indicate dependency direction
(a context that uses another's published concepts points at it). `Shared` is a kernel
used by all.

```mermaid
flowchart TB
    subgraph Shared["Shared (kernel)"]
        VO["Value Objects<br/>Money · Cep · Phone"]
        Audit["AuditLogger"]
        Http["API Response Envelope"]
    end

    Customer["Customer<br/><i>OTP auth, customers, addresses</i>"]
    Catalog["Catalog<br/><i>products, variants, stock ledger</i>"]
    Ordering["Ordering<br/><i>cart, order, state machine, coupons</i>"]
    Payment["Payment<br/><i>Mercado Pago, webhooks, idempotency</i>"]
    Shipping["Shipping<br/><i>methods, freight, free-shipping rule</i>"]

    Ordering --> Catalog
    Ordering --> Customer
    Ordering --> Shipping
    Payment --> Ordering
    Catalog --> Shared
    Ordering --> Shared
    Payment --> Shared
    Shipping --> Shared
    Customer --> Shared
```

## Reading the map

- **Ordering** is the hub: a cart/order references catalog variants, a customer, and a
  chosen shipping method.
- **Payment** reacts to an order and, on confirmation, drives the order's state
  forward and the stock decrement (see the order → payment → stock flow).
- **Catalog** owns the stock ledger; other contexts request reservations/decrements
  through its published application services, never by writing to its tables.
- **Shared** holds only truly cross-cutting concerns. It depends on nothing.
