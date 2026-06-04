# 0006. Passwordless OTP with an abstracted delivery channel

- **Status:** Accepted
- **Date:** 2026-05-28

## Context

The store competes with a near-frictionless flow: "DM on Instagram, pay by Pix". The
site must not lose that advantage by demanding long forms or passwords. We want a
buyer to become able to purchase by verifying a single contact (phone or email) and
providing a real name — no password, no username.

For the verification channel, WhatsApp would be ideal (the audience already lives
there), but the WhatsApp Cloud API requires a dedicated number not registered on any
WhatsApp, business verification, and template approval — too much burocracy for the
MVP. SMS in Brazil costs per message and has uneven delivery. Email is free and has
zero setup friction but is a slightly less familiar channel for codes.

## Decision

We will use **passwordless OTP** authentication. Verifying a contact creates the
account (status "incomplete", holding only the verified contact); the buyer then
provides a real name (deferrable until checkout at the latest). No password, no
username.

The delivery channel is abstracted behind an **`OtpChannel`** interface with
implementations for email, SMS, and WhatsApp. The MVP ships with **email** (zero cost
and zero setup); SMS and WhatsApp are pluggable later by binding a different
implementation, with no change to the OTP domain logic (rate limiting, expiry,
attempts).

## Consequences

- Minimal sign-up friction, aligned with the existing sales flow.
- The channel is a swappable detail; moving to WhatsApp later is an implementation,
  not a rewrite.
- No password or username reduces both friction and the data/security surface.
- Email-first means a buyer must provide an email in the MVP; this is acceptable and
  revisited when WhatsApp OTP lands.
