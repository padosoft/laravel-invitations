# Plan — `padosoft/laravel-rebel-bridge-invitations` (SEPARATE, later session)

> **Not part of the main roadmap.** Run this as its own plan in a new session **after** `padosoft/laravel-invitations` core has shipped v1.0.0. This doc is the outline to start from.

## Goal
A thin bridge that lets a **Padosoft "rebel" auth deployment** light up the standalone `laravel-invitations` engine using rebel's cross-cutting infrastructure instead of the package's plain defaults — without the core ever depending on rebel.

## What it wires (rebel-core provides these; the bridge adapts the invitations engine onto them)
- **Multi-channel delivery** — route invitation delivery through rebel's **`DeliveryChannelRegistry` / `MessageDeliveryChannel`** (email / telegram / twilio / vonage / discord, multi-tenant, audited) by implementing the core's delivery interface as a rebel-channel adapter. Replaces plain Laravel Mail.
- **Audit trail** — write `invitation.*` / `code.redeemed` / `referral.qualified` / `reward.*` events through rebel's **`AuditLogger` → `rebel_auth_events`**, so invitations show up in the rebel admin **Audit Explorer** alongside auth events.
- **Tenancy** — bind rebel's **`CurrentTenant` / `BelongsToTenant`** into the core's `TenantResolver`.
- **GDPR hashing** — use rebel's **`KeyedHasher` / `HmacKeyedHasher`** (versioned pepper rotation) for invitee-email / IP / fingerprint hashing in place of the core's default HMAC.
- **Bot protection** — optionally gate the public accept/claim/redeem endpoint with rebel's **`BotProtection`** contract.
- **Admin** — ship a Blade admin **section** (`resources/views/sections/`) + matching read endpoints in `laravel-rebel-admin-api` so invitations appear in the rebel panel. NOTE: rebel admin is **Blade + vanilla-JS over `admin-api`**, architecturally different from the React `-admin` package — design/budget that screen on its own.

## Package conventions (from the rebel ecosystem)
- Composer `padosoft/laravel-rebel-bridge-invitations`; namespace `Padosoft\Rebel\BridgeInvitations\`; provider `RebelBridgeInvitationsServiceProvider` (spatie package-tools); config `config/rebel-bridge-invitations.php`; env `REBEL_BRIDGE_INVITATIONS_*`; publish tag `rebel-bridge-invitations-config`.
- Requires `php ^8.3`, `illuminate/* ^12|^13`, `padosoft/laravel-rebel-core`, `padosoft/laravel-invitations`, `spatie/laravel-package-tools`.
- Auto-register into rebel registries at boot (the bridge pattern: adapt third-party/core → rebel contracts + map events into the unified audit trail). Mirrors `bridge-fortify`, `bridge-passkeys`, etc.
- CI: PHP 8.3/8.4/8.5 × Laravel 12/13; Pest 4 + Testbench; PHPStan level max; Pint. Cover tenant scoping + auth-failure + empty states.

## Resolve before building
- **License inconsistency in rebel-core**: `composer.json` says **MIT**, README footer says **Apache-2.0** (`email-otp` has the same conflict). Standardize the canonical license first; match it in the bridge.
- Confirm the core's delivery interface (defined in Phase 2/3 of the main roadmap) is shaped so a rebel-channel adapter slots in cleanly — if not, adjust the core's interface in a core patch first.

## Verification
Bridge installed on a rebel demo app: invites deliver over a rebel channel, events land in `rebel_auth_events` + show in the rebel admin Audit Explorer, invitee PII is hashed via `KeyedHasher`, tenant scoping holds, public redeem endpoint passes bot-protection. CI green on the matrix; PHPStan max clean.
