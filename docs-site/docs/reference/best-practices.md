---
title: Best practices
description: Operating padosoft/laravel-invitations safely in production — tenancy, concurrency, secrets, privacy, and observability.
---

# Best practices

A checklist distilled from the package's invariants. Each item links to the page that explains *why*.

## Correctness under load

- **Never replace the atomic claim with a read‑then‑write.** The conditional `UPDATE` in
  `RedemptionService::claimSeat()` is the only sanctioned path that bumps `current_uses`. Ship a
  **two‑process concurrency test** for any change to it.
  → [Atomic idempotent redemption](/concepts/atomic-redemption)
- **Treat idempotency as a database invariant**, not code discipline — rely on
  `UNIQUE(code_id, redeemer_id)` and `UNIQUE(idempotency_key)`, and *handle* the violation (catch →
  release → idempotent success) rather than trying to avoid the race.

## Tenancy

- **Scope every query** through `forTenant()` / the `BelongsToTenant` trait. An unscoped
  `where('code', …)` mixes tenants and is a bug.
- **Bind a real `TenantResolver`** in multi‑tenant hosts; single‑tenant apps need none.
  → [Multi‑tenancy & host seams](/concepts/multi-tenancy)

## Codes

- **Increase the length, don't loop forever** when you see `collision_exhausted` — the keyspace is too
  small for your issue volume.
- **Don't add `I L O U`** or duplicates to the alphabet — the generator refuses them because
  normalization would shrink the keyspace.
  → [Invite codes](/guides/invite-codes)

## Secrets

- **Set `INVITE_SIGNING_KEY` and `INVITE_PII_SALT`** to dedicated, rotatable secrets in production.
  The `APP_KEY` fallback is a dev convenience; rotating `APP_KEY` would otherwise orphan signed codes
  and PII hashes.
  → [Configuration reference](/operations/configuration)

## Privacy

- **Leave `store_network_fields` off** unless abuse review genuinely needs ip / fingerprint.
- **Schedule `invite:prune-pii`** so PII is anonymized past the retention window — in place, so
  aggregates survive.
  → [GDPR & data privacy](/guides/gdpr)

## Authorization

- **Append your RBAC gate to `routes.admin_middleware`** before exposing the admin API — the default is
  authenticate‑only, not authorize.
  → [The HTTP API](/operations/http-api)

## Anti‑abuse

- **Tune thresholds and velocity rules**; don't disable the gate to "fix" false positives — use the
  `allowlist` instead.
- **Never surface the tripped `signal_type`** to callers, and never make the gate throw on the
  redemption path (it must fail‑open).
  → [Anti‑abuse scoring](/concepts/anti-abuse)

## Provisioning

- **Keep provisioners additive and idempotent** (`firstOrCreate`, additive roles). The contract is
  GRANT‑never‑REVOKE, best‑effort.
  → [Per‑invite entitlement grants](/guides/grants)

## Observability

- **Listen to the domain events** (`CodeRedeemed`, `InvitationSent`, `InvitationAccepted`) for perks,
  mail, and projections — `CodeRedeemed` fires once per genuine redemption.
  → [Domain events](/guides/events)
- **Read metrics from the API / MCP / `MetricsService`** — they reconcile against the canonical rows.
  → [Virality analytics](/concepts/analytics)

::: callout tip
All of these invariants are encoded in the package's `CLAUDE.md`, so an AI pair‑programmer inherits
them automatically. The package's quality gate is `composer check` (Pint + PHPStan level max +
PHPUnit, on a PHP 8.3/8.4/8.5 × Laravel 12/13 matrix).
:::
