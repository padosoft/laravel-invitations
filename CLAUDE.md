# CLAUDE.md — padosoft/laravel-invitations

Working rules for AI agents on this package. Mirrored for other tools in `AGENTS.md`.

## What this is
An enterprise, **vendor-neutral**, multi-tenant invite-by-code + referral + rewards + waitlist + anti-abuse + analytics suite for Laravel. Headless core (PHP + HTTP API + MCP); the React admin SPA lives in `padosoft/laravel-invitations-admin`. Built by extracting the production-proven engine from AskMyDocs PR #355 — see `docs/ROADMAP.md`.

## Stack & gates
- PHP `^8.3`; Laravel `^12|^13`; `spatie/laravel-package-tools`.
- `declare(strict_types=1)` everywhere; constructor promotion; explicit return types.
- **All gates must stay green:** `composer check` = Pint + PHPStan **level max** (fix at source, no baselines) + PHPUnit (Testbench). CI matrix PHP 8.3/8.4/8.5 × Laravel 12/13.

## Non-negotiable invariants (the reason this package beats the field)
1. **Atomic, idempotent, concurrency-safe redemption** — the single conditional `UPDATE … WHERE current_uses < max_uses` (+ state flip in the same statement) backed by `UNIQUE(code_id, redeemer_id)` is the ONLY path that bumps `current_uses`. Never replace it with a read-then-write. Ship a 2-process concurrency test for any change here.
2. **Multi-tenant scoping** — every table carries `tenant_id`; composite uniques start with `tenant_id`; every query is tenant-scoped via the `TenantResolver`. Two tenants may share a human code.
3. **Fail-open, generic anti-abuse** — a detector fault degrades to `none`, never a block; the caller only ever learns `rate_limited` (no probing oracle). PII is HMAC'd, never plaintext.
4. **GRANT-never-REVOKE provisioning** — an invite can only raise access (additive role / `firstOrCreate` membership), never downgrade. Provisioning is best-effort: it must never fail an already-committed redemption.
5. **Vendor-neutral seams** — type against `TenantResolver` / `Provisioner` / `InvitedAccount` interfaces, the configurable `invitations.user_model`, and the `Authenticatable` contract. `spatie/laravel-permission` + `laravel/fortify` are OPTIONAL integrations, not hard deps.
6. **Tri-surface** — every capability is reachable via PHP (service/Artisan), HTTP API (RBAC-gated), and an MCP tool. All three delegate to ONE core service.
7. **GDPR** — PII anonymization preserves aggregates (`current_uses`, funnel counts untouched); scheduled prune; data-access export.

## Releases
Single feature branch → PR to `main`; Copilot/critic review loop until 0 must-fix + CI green; tag `vX.Y.Z`; Packagist auto-updates. WOW community README + doc-site are the final deliverables before `v1.0.0`.
