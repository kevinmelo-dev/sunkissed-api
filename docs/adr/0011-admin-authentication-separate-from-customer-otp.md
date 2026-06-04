# 0011. Admin authentication separate from customer OTP

- **Status:** Accepted
- **Date:** 2026-06-04

## Context

ADR-0006 established passwordless OTP authentication for store **customers**: identity
is a verified phone/email plus a real name — no password. That design fits customers
well (low-friction first visit, no forgotten passwords), but it does not fit the store
administrator.

The admin accesses the back-office panel repeatedly throughout the working day to
manage catalogue, stock, and orders. Requesting a fresh OTP on every login would be
unnecessary friction for a known, recurring user. Two real alternatives were evaluated:

1. **Reuse the customer OTP flow for the admin** — authenticate the admin through the
   same endpoint and guard already built for customers.
2. **Model the admin as a Customer with an elevated role/flag** — add an `is_admin`
   boolean or a `role` column to the `customers` table and branch on it.

Both were rejected. Option 1 imposes OTP friction on a daily-driver workflow. Option 2
contaminates the Customer domain entity with an administrative concept that has no
business meaning in the storefront context, and it collapses two fundamentally
different actors into one table.

## Decision

We will model the admin as a **separate entity** from Customer, with its own `admins`
table and email + password credentials. Authentication will be handled by a dedicated
**Laravel Sanctum guard** (`admin`) that is completely independent of the customer
guard. Admin-only routes (including the existing `POST /v1/catalog/stock-entries` and
all future back-office endpoints) will require the admin guard.

The customer OTP flow defined in ADR-0006 remains untouched and independent.

We will not introduce a roles/permissions system in the MVP. There is one kind of
admin; granular permissions can be added later if the store hires additional staff.

## Consequences

- Two authentication mechanisms coexist in the same API: passwordless OTP for
  customers, email + password for admins. Each is appropriate for its audience.
- The Customer domain entity stays clean — no administrative concepts bleed in.
- The `AuditActor::admin` value in the audit log (ADR-0008) now has a concrete origin:
  the authenticated admin record.
- Adding the admin guard requires a new service provider binding, a new `admins`
  migration, and a separate login endpoint. This is a small, contained cost.
- If the store eventually needs multiple admins with different permissions, this ADR
  must be revisited and a roles/permissions layer added. That is explicitly deferred.
