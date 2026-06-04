# Source layout

Code is organized by **bounded context**, not by technical type. Each context owns
its slice of the domain and is internally layered.

```
src/
└── <Context>/
    ├── Domain/          # Pure PHP. Entities, value objects, domain events,
    │                    # domain exceptions, repository interfaces. No Laravel.
    ├── Application/      # Use cases (one per file), DTOs, command/query handlers.
    │                    # Orchestrates the domain. No HTTP, no Eloquent.
    └── Infrastructure/   # Eloquent models + repository implementations, controllers,
                         # API resources, form requests, external API clients,
                         # service providers. Laravel lives here.
```

## Contexts

- **Catalog** — products (kits & singles), variants (size × color), the stock ledger.
- **Ordering** — cart, order, order state machine, coupons.
- **Payment** — Mercado Pago (Checkout Pro), payment webhooks, idempotency.
- **Shipping** — shipping methods, freight calculation (Melhor Envio), free-shipping rule.
- **Customer** — passwordless OTP auth, customers, addresses.
- **Shared** — cross-context value objects (Money, Cep, Phone), audit logging, the API
  response envelope, base exceptions.

## The dependency rule

Dependencies point inward: `Infrastructure → Application → Domain`. The domain never
imports `Illuminate\*`. Use cases depend on repository *interfaces* (in `Domain`),
whose Eloquent implementations (in `Infrastructure`) are bound in each context's
service provider.

## Namespacing

PSR-4 maps `Src\` → `src/`. Example: `Src\Catalog\Domain\Product`,
`Src\Ordering\Application\PlaceOrder`. Laravel's own bootstrap, config, and
first-party integrations (Sanctum, Horizon) stay under `app/` (`App\`).

See [`../docs/adr/0002-ddd-clean-architecture-by-bounded-context.md`](../docs/adr/0002-ddd-clean-architecture-by-bounded-context.md).
