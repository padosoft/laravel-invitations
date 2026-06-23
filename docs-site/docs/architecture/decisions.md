---
title: Decision records (ADR)
description: The architectural decisions that shaped padosoft/laravel-invitations, in Problem → Decision → Consequences form.
---

# Decision records (ADR)

This page collects the load‑bearing decisions behind the package. Each follows the
*Problem → Decision → Consequences* shape. Decisions specific to a subsystem are also linked from that
subsystem's page.

## Redemption

::: collapsible "ADR-001 · Lock-free conditional UPDATE for the seat claim"
**Problem.** Prevent over‑redemption under concurrency without serializing every redemption behind a
lock.

**Decision.** A single conditional `UPDATE … WHERE current_uses < max_uses` that also flips `state` in
the same statement is the **only** path that bumps `current_uses`.

**Consequences.** No held locks, no deadlocks, portable across pgsql / MySQL / SQLite; safety is a
property of the statement. A rare same‑account double‑insert is compensated by `releaseSeat()`. See
[Atomic idempotent redemption](/concepts/atomic-redemption).
:::

::: collapsible "ADR-002 · UNIQUE(code_id, redeemer_id) is the idempotency anchor"
**Problem.** Make a replayed redemption a no‑op across workers and restarts.

**Decision.** A unique index on `(code_id, redeemer_id)` in the append‑only `invite_redemptions`
table.

**Consequences.** Idempotency is a database invariant, not code discipline (host rule R21). The loser
of a race catches the violation, releases its over‑counted seat, and returns the winner's claim.
:::

## Tenancy & seams

::: collapsible "ADR-003 · Tenant scope at the application layer"
**Problem.** Isolate customers that may share a `project_key` / code string.

**Decision.** `tenant_id` on every table; composite uniques lead with it; cross‑tenant isolation is
the mandatory `forTenant()` scope, not a tenant‑keyed FK.

**Consequences.** Free single‑tenant operation via a `default` tenant; every query must be scoped (an
unscoped read is a bug). See [Multi‑tenancy & host seams](/concepts/multi-tenancy).
:::

::: collapsible "ADR-004 · Vendor-neutral seams (TenantResolver / Provisioner / InvitedAccount)"
**Problem.** Ship a reusable engine without coupling to a specific User model, tenancy package, or
permission system.

**Decision.** Three interfaces with safe defaults; the host binds its own implementations.
`spatie/laravel-permission`, `laravel/fortify`, `laravel/mcp` are optional integrations.

**Consequences.** Plain Fortify/Breeze apps and a complex multi‑tenant host both work with the same
core. Project/team membership — the one genuinely host‑specific concern — is a host‑supplied
provisioner.
:::

::: collapsible "ADR-005 · GRANT-never-REVOKE provisioning, best-effort"
**Problem.** An invite that grants access must not be a vector to *remove* access, and a provisioning
fault must not undo a committed redemption.

**Decision.** Provisioning is additive only (grant role, `firstOrCreate` membership) and best‑effort —
faults are swallowed and logged.

**Consequences.** Redemption commits independently of provisioning; access only ever rises. A failed
grant is observable in logs but never rolls back a claimed seat.
:::

## Anti‑abuse & privacy

::: collapsible "ADR-006 · Fail-open, generic anti-abuse"
**Problem.** A fraud gate must not become a denial‑of‑service for real users, nor a probing oracle for
attackers.

**Decision.** Fail‑open (a fault → `none`, never a block) and generic (the caller only learns
`rate_limited`).

**Consequences.** Seat safety is the atomic claim's job; the gate is advisory. Signals are logged for
review but the tripped rule is never echoed. See [Anti‑abuse scoring](/concepts/anti-abuse).
:::

::: collapsible "ADR-007 · Anonymize-in-place, preserve aggregates"
**Problem.** GDPR erasure must remove PII without corrupting `current_uses`, funnel counts, or
K‑factor.

**Decision.** Never delete rows; overwrite PII **columns** in place. PII (ip / fingerprint / email) is
stored as a salted HMAC, never plaintext.

**Consequences.** Retention sweeps and right‑to‑be‑forgotten requests leave every aggregate intact.
See [GDPR & data privacy](/guides/gdpr).
:::

## Codes

::: collapsible "ADR-008 · Crockford Base32 + normalization as identity"
**Problem.** Human‑typed codes suffer from confusable glyphs (`I`/`1`, `O`/`0`) and casing/separator
noise.

**Decision.** A Crockford Base32 alphabet that omits `I L O U`; every code is persisted in its
`CodeNormalizer` canonical form so the generator and redeemer agree on identity. The generator refuses
an alphabet containing the confusables or duplicates.

**Consequences.** A user can type `q7-k9-2mnp` and redeem `Q7K92MNP`. Signed codes survive
normalization unchanged. See [Invite codes](/guides/invite-codes).
:::

::: collapsible "ADR-009 · Tri-surface over one core"
**Problem.** Different consumers want PHP, HTTP, or MCP access — without three diverging
implementations.

**Decision.** One set of services; thin controllers and thin MCP tools adapt input → core → output
(host rule R44).

**Consequences.** A capability change lands once and is reflected on all surfaces; the surfaces cannot
drift. See [Architecture overview](/architecture/overview).
:::

::: callout tip
These ADRs are mirrored in the package's `CLAUDE.md` invariants so an AI pair‑programmer inherits them
automatically — see the *AI vibe‑coding pack* note on the [home page](/).
:::
