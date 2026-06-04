# CLAUDE.md

Guidance for Claude Code working in this repository. Keep this file lean and
specific — it encodes **this project's conventions and guardrails**, not general
Laravel tutorials. When a rule here conflicts with a generic default, this file wins.

---

## What this project is

An **API-only** Laravel 11 e-commerce backend for a Brazilia swimwear store.
No Blade views, no Inertia, no Livewire. The client is a separate React SPA that
consumes this API. The store sells pre-defined **kits** (matching top + bottom, same
size, same color — no mix-and-match) and a few **standalone** pieces.

## Language policy (important)

- **Code, comments, class/method/variable names, commit messages, docs, ADRs:**
  English.
- **User-facing strings** — validation messages, domain exception messages, API
  error copy returned to the client: **Brazilian Portuguese**.
- Never put Portuguese in identifiers; never put English in a message the end user
  reads.

---

## Architecture

Pragmatic **DDD + Clean Architecture**, organized by **bounded context** under
`src/`. Each context has three layers:

```
src/<Context>/
├── Domain/         # Entities, value objects, domain events, domain exceptions,
│                   # repository interfaces. NO framework code. Pure PHP.
├── Application/    # Use cases (one class per use case), DTOs, command/query handlers.
│                   # Orchestrates the domain. Depends on Domain, not on Laravel.
└── Infrastructure/ # Eloquent models, repository implementations, HTTP controllers,
                    # API resources, form requests, external API clients, providers.
                    # This is where Laravel lives.
```

Contexts: `Catalog`, `Ordering`, `Payment`, `Shipping`, `Customer`, `Shared`.

### Dependency rule

- `Domain` depends on nothing (except `Shared/Domain`).
- `Application` depends on `Domain`.
- `Infrastructure` depends on `Application` and `Domain`.
- Dependencies point **inward**. The domain never imports `Illuminate\*`.

---

## Non-negotiable conventions

These are the things that would otherwise be done wrong by default. Follow them
exactly.

1. **Money is a value object, always.** `Shared\Domain\ValueObject\Money`, stored as
   integer **cents**. Never a float. Never a bare integer column without going
   through the VO. No `price` floats anywhere.

2. **Stock is never a column.** Current stock is **derived** from the append-only
   `inventory_movements` ledger (`entrada`, `saida`, `reserva`, `liberacao`,
   `ajuste`). Never store or mutate a `quantity_in_stock` field as source of truth.
   To change stock, append a movement.

3. **Stock is decremented on the payment webhook, not at checkout.** Checkout creates
   a **reservation** movement with a TTL. The confirmed-payment webhook converts the
   reservation into a `saida`. Expiry releases it.

4. **Controllers orchestrate, they do not contain business logic.** A controller
   validates input (via a Form Request), calls a use case, and returns an API
   Resource. No domain rules in controllers.

5. **Use cases receive DTOs, not `Illuminate\Http\Request`.** Map the request to a
   DTO in the controller. The application layer must not know about HTTP.

6. **Domain logic talks to repository *interfaces*, not Eloquent.** Eloquent models
   live in `Infrastructure` and implement repository interfaces defined in `Domain`.
   Use cases depend on the interface, bound in a service provider.

7. **Exceptions:** domain errors extend `Shared\Domain\Exception\DomainException`.
   They are translated to HTTP responses centrally in the exception handler — never
   throw a generic `\Exception` for a business rule, and never build an HTTP response
   inside the domain.

8. **All API responses follow the standard envelope.** Use the shared response
   formatter / API resources. Do not hand-roll `response()->json([...])` shapes per
   endpoint. See `Shared/Infrastructure/Http`.

9. **External integrations sit behind interfaces.** `PaymentGateway`, `OtpChannel`,
   `ShippingCalculator` are interfaces in the domain/application layer; concrete
   Mercado Pago / email / Melhor Envio implementations live in infrastructure and are
   bound in providers. The domain must be swappable.

10. **Audit-worthy actions go through `AuditLogger`** (see `Shared/Infrastructure/
    Audit`). Do not scatter `Log::info` for business events; use the audit logger so
    they land in both the `audit_logs` table and the archived log file.

---

## Out of scope / do NOT do

The biggest risk in this codebase is over-engineering. Resist it.

- **No GraphQL.** REST, versioned `v1`, only the endpoints the store actually uses.
- **No abstraction for clients that don't exist.** One SPA consumes this API. Don't
  build generic multi-tenant / plugin / extensibility layers.
- **No CPF, no NF-e** in the MVP. The store does not issue invoices. Don't add fields
  or flows for them.
- **No username** on customer accounts. Identity is the verified phone/email + a real
  name. Nothing else.
- **No mix-and-match kits.** A kit is a fixed product with size × color variants.
- **Don't add a package** when Laravel's first-party tools or a few lines solve it.
  Justify every new Composer dependency.
- **Don't pre-build WhatsApp OTP.** Implement the `OtpChannel` abstraction and ship
  with email (or SMS). WhatsApp is a later implementation, not MVP scope.
- **Checkout Pro only** for Mercado Pago in the MVP (redirect flow). No card data
  touches this server.

---

## Stack & versions (generate code for these exactly)

- PHP **8.3**, Laravel **11**
- MySQL **8**, Redis (cache + queues)
- Laravel **Horizon**, Laravel **Sanctum**
- **Pest** for tests (not PHPUnit-style by default)
- Laravel **Pint** for code style
- S3-compatible storage (driver `s3`, works for AWS S3 and Cloudflare R2)

---

## Commands

```bash
make test          # ./vendor/bin/pest
make lint          # ./vendor/bin/pint
make shell         # shell into the app container
make migrate       # php artisan migrate
make fresh         # php artisan migrate:fresh --seed
```

When you finish a unit of work, run `make lint` and `make test` and make them pass
before considering it done.

---

## Testing expectations

- Domain logic (value objects, the stock ledger math, the order state machine,
  coupon rules) has unit tests. This is where correctness matters most.
- Use cases have feature/integration tests for the happy path and key failures.
- Webhook idempotency has an explicit test: the same event processed twice produces
  one stock movement and one state transition.
- Prefer testing behavior through use cases over testing Eloquent directly.

---

## When generating code

- Match the pattern of the **existing** sibling code in the same context. If
  `Catalog` already has a use case, mirror its shape in `Ordering`.
- Generate the repetitive parts (migrations, factories, resources, form requests,
  provider bindings, use-case skeletons). Leave domain modeling decisions to the
  human unless explicitly asked.
- Keep classes small and single-purpose. One use case per file.
- Always write the corresponding test when you add a use case or a domain rule.
