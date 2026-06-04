# Sunkissed Swimwear API

A production e-commerce backend for Sunkissed Swimwear, a Brazilian swimwear store,
built as a clean, domain-oriented **Laravel REST API**. The store sells primarily
pre-defined kits
(matching top + bottom) plus a few standalone pieces, and currently runs entirely
over Instagram/WhatsApp with manual stock control. This API replaces the manual
process with a real catalog, stock ledger, checkout, and admin operations — without
adding friction to a sales flow that today is as simple as "DM and pay by Pix".

> **This repository is the backend.** It is an API-only Laravel application with no
> views. The storefront and admin are a separate front-end application (React) that
> consumes this API. The engineering of interest lives here.

---

## Why this project exists

This is a real system going to production for a real store, and at the same time a
portfolio piece. The goal is a backend that is **clean, well-architected, observable,
and documented** — demonstrating: bounded contexts, explicit use cases, domain
events driving asynchronous jobs, value objects, an append-only stock ledger,
idempotent payment webhooks, an order state machine, queue-based processing, and a
documented, versioned REST contract.

A note on language: **code, documentation, and identifiers are in English** (the
project targets international roles). **User-facing API output** — validation
messages, domain exception messages, error copy — is in **Brazilian Portuguese**,
because the storefront and admin are used by Brazilian users.

---

## Architecture at a glance

The codebase is organized into **bounded contexts** under `src/`, each split into
`Domain`, `Application`, and `Infrastructure` layers (a pragmatic blend of
Domain-Driven Design and Clean Architecture). Business rules live in the domain and
never depend on the framework; the framework lives in infrastructure.

| Context | Responsibility |
|----------|----------------|
| **Catalog** | Products (kits & singles), variants (size × color), the stock ledger |
| **Ordering** | Cart, order lifecycle, order state machine, coupons |
| **Payment** | Mercado Pago integration, payment webhooks, idempotency |
| **Shipping** | Shipping methods, freight calculation (Melhor Envio), free-shipping rules |
| **Customer** | Passwordless OTP authentication, customers, addresses |
| **Shared** | Cross-context value objects (Money, Cep, Phone), audit logging, base classes |

See [`docs/diagrams/`](docs/diagrams) for the context map and the
order → payment → stock flow, and [`docs/adr/`](docs/adr) for the decisions behind
the design.

---

## Tech stack

- **PHP 8.3**, **Laravel 11**
- **MySQL 8** (relational model), **Redis** (cache + queues)
- **Laravel Horizon** for queue workers and monitoring
- **Laravel Sanctum** for token authentication (consumed by the SPA)
- **Pest** for testing
- **Docker** (PHP-FPM + Nginx + MySQL + Redis) for a containerized dev environment
- **S3-compatible storage** (AWS S3 or Cloudflare R2) for product images and audit log files
- **OpenAPI** for API documentation

---

## Getting started

Requirements: Docker and Docker Compose. Everything else runs in containers.

```bash
# 1. Copy environment file and adjust as needed
cp .env.example .env

# 2. Bring the stack up, install dependencies, generate key, run migrations
make setup

# 3. The API is now available at http://localhost:8080
```

Common commands (see the full list in the [Makefile](Makefile)):

```bash
make up         # start containers
make down       # stop containers
make test       # run the Pest suite
make lint       # run Laravel Pint (code style)
make shell      # open a shell inside the app container
make migrate    # run database migrations
make fresh      # drop and re-migrate with seeders
```

---

## Documentation

- **[Architecture Decision Records](docs/adr)** — every significant decision, its
  context, and its consequences (Nygard format).
- **[Diagrams](docs/diagrams)** — context map and key flows.
- **API reference** — OpenAPI spec (generated; see the docs route once running).

---

## A note on AI-assisted development

This project is developed with AI assistance (Claude Code) used deliberately:
**scaffolding, repetitive code, and established patterns are generated; domain
modeling, architectural decisions, and business logic are authored and reviewed by
hand.** Conventions and guardrails that steer the generated code live in
[`CLAUDE.md`](CLAUDE.md). Every line in this repository is understood and defensible
— AI is treated as a fast pair, not as the author.

---

## License

Proprietary — all rights reserved. This repository is published for portfolio and
production purposes.
