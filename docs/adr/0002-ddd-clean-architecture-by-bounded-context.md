# 0002. DDD + Clean Architecture organized by bounded context

- **Status:** Accepted
- **Date:** 2026-05-28

## Context

The default Laravel layout (`app/Models`, `app/Http/Controllers`) couples business
rules to the framework and scatters a single feature across technical folders. The
domain here has several distinct areas — catalog, ordering, payment, shipping,
customer — with different rules and lifecycles. We want business logic that is
testable in isolation, a clear place for each concept, and a structure that
demonstrates the architectural practices the project is meant to show.

## Decision

We will organize the code by **bounded context** under `src/`, with each context
split into **Domain / Application / Infrastructure** layers (a pragmatic blend of
Domain-Driven Design and Clean Architecture).

- `Domain` holds entities, value objects, domain events, domain exceptions, and
  repository *interfaces* — pure PHP, no `Illuminate\*`.
- `Application` holds use cases and DTOs; it orchestrates the domain.
- `Infrastructure` holds Eloquent models, repository implementations, controllers,
  API resources, form requests, external clients, and service providers.

Dependencies point inward: Infrastructure → Application → Domain. A `composer.json`
PSR-4 autoload maps `src/` and the contexts are wired via per-context service
providers.

## Consequences

- Business rules are framework-independent and unit-testable without booting Laravel.
- Each feature is cohesive: everything about Ordering is under `src/Ordering`.
- New engineers (and reviewers) can read the context map and navigate quickly.
- Cost: more indirection than vanilla Laravel — interfaces, bindings, DTO mapping.
  Justified by the project's goals; kept pragmatic (we do not add layers without a
  reason).
- Requires discipline: the dependency rule must be enforced in review, since PHP
  will not stop a domain class from importing Eloquent.
