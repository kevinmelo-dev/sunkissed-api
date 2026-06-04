# 0001. API-only backend with a separate SPA

- **Status:** Accepted
- **Date:** 2026-05-28

## Context

The store needs both a customer-facing storefront and an admin panel. The backend
must demonstrate strong, clean backend engineering for a portfolio targeting
backend/Laravel roles, while also running in production for a real store.

Several front-end approaches were considered:

- **Filament** for the admin: fast to build, but opinionated and hard to control at
  the code level; fighting the framework when requirements fall outside its model.
- **Livewire / Inertia**: capable, but they blur the boundary between server and
  client state, which works against a layered, decoupled design and against the goal
  of presenting a clean *backend*.
- **API + separate SPA**: an explicit HTTP contract between a pure backend and an
  independent front end.

The primary goal — make a reviewer conclude "this person understands backend and
Laravel" — is best served by concentrating effort on the backend and keeping its
boundary clean and legible.

## Decision

We will build the backend as an **API-only Laravel application with no views**. The
storefront and admin are a **separate single-page application** (React) that consumes
the API. Authentication uses **Laravel Sanctum** tokens. The two applications live in
separate repositories so the backend reads as a self-contained backend project.

## Consequences

- The backend boundary is explicit: an HTTP contract, documented with OpenAPI. This
  is the clearest possible signal of backend competence.
- The domain stays free of presentation concerns.
- Cost: more total code, plus CORS, token auth, and a front end to maintain. The
  admin takes longer to reach the store owner than a Filament panel would.
- The front end is deliberately sober and consumes work done in the API; the API is
  the engineering centerpiece. The admin may use a ready-made UI kit to avoid turning
  into a design project.
- Risk to watch: over-engineering the API for clients that do not exist. Mitigated by
  ADR scope discipline — REST, `v1`, only the endpoints the store uses.
