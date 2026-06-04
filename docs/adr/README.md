# Architecture Decision Records

This directory records the significant architectural decisions made on this project,
using the [Michael Nygard ADR format](https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions).

## Why

An ADR captures **one decision**, the **context** that forced it, and its
**consequences**. Six months from now — or to a new engineer, or to a reviewer — the
ADRs explain *why* the system is the way it is, not just *how* it works.

## Rules

- One decision per file, numbered: `NNNN-short-title.md`.
- An ADR is **immutable once accepted**. If a decision changes, write a new ADR that
  supersedes the old one and update the old one's status to `Superseded by ADR-NNNN`.
  Never delete history.
- Keep ADRs for decisions with a real trade-off and a considered alternative. Not for
  trivial choices.

## Status values

`Proposed` · `Accepted` · `Superseded by ADR-NNNN` · `Deprecated`

## Index

| # | Title | Status |
|---|-------|--------|
| [0000](0000-template.md) | Template | — |
| [0001](0001-api-only-with-separate-spa.md) | API-only backend with a separate SPA | Accepted |
| [0002](0002-ddd-clean-architecture-by-bounded-context.md) | DDD + Clean Architecture organized by bounded context | Accepted |
| [0003](0003-kit-as-fixed-product.md) | Model a kit as a fixed product, not a dynamic bundle | Accepted |
| [0004](0004-stock-as-append-only-ledger.md) | Stock as an append-only movement ledger | Accepted |
| [0005](0005-decrement-stock-on-payment-webhook.md) | Decrement stock on the payment webhook, not at checkout | Accepted |
| [0006](0006-passwordless-otp-abstracted-channel.md) | Passwordless OTP with an abstracted delivery channel | Accepted |
| [0007](0007-mercado-pago-checkout-pro.md) | Mercado Pago Checkout Pro for the MVP | Accepted |
| [0008](0008-audit-logging-table-plus-archived-file.md) | Audit logging: queryable table plus archived file | Accepted |
