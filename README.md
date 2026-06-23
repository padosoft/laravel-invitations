# Laravel Invitations

> **Enterprise invite-by-code, referral, rewards, waitlist & anti-abuse system for Laravel.**
> Multi-tenant · concurrency-safe · idempotent redemption · GDPR-ready · tri-surface (PHP + HTTP API + MCP).

[![Latest Version on Packagist](https://img.shields.io/packagist/v/padosoft/laravel-invitations.svg?style=flat-square)](https://packagist.org/packages/padosoft/laravel-invitations)
[![License](https://img.shields.io/packagist/l/padosoft/laravel-invitations.svg?style=flat-square)](LICENSE)

> ⚠️ **Under active development toward `v1.0.0`.** This package is being built from a
> production-proven engine. The scaffold is published so the package can be
> registered on Packagist; the full feature set lands incrementally per the
> roadmap. Do not depend on it in production until `v1.0.0` is tagged.

## What it will do

A complete user-acquisition suite, in one coherent multi-tenant domain:

- **Invite codes** — random / vanity / signed (Crockford Base32), expiry, max-uses, per-user limits.
- **Atomic, idempotent, concurrency-safe redemption** — a single conditional `UPDATE` that can never over-redeem; replays are no-ops. (The thing every other package gets wrong.)
- **Email invitations** — send / accept lifecycle, "who accepted vs. not".
- **Referral graph + double-sided / tiered rewards** — with an auditable reward ledger.
- **Waitlist** — double opt-in + refer-to-jump-the-queue virality.
- **Anti-abuse** — weighted, fail-open fraud detection (velocity / disposable-email / honeypot / blacklist), generic `rate_limited` (no probing oracle), HMAC'd PII.
- **Virality analytics** — K-factor, acceptance / conversion rates, time-to-redeem, funnel.
- **Per-invite entitlement grants** — grant a role / project access on redemption (GRANT-never-REVOKE).
- **GDPR** — PII anonymization preserving aggregates + scheduled prune + data-access export.
- **Tri-surface** — PHP services/Artisan + RBAC-gated HTTP API + MCP tools.

Vendor-neutral (plain Fortify/Breeze friendly); `spatie/laravel-permission` and
`laravel/fortify` are optional, supported integrations.

A separate **[`padosoft/laravel-invitations-admin`](https://github.com/padosoft/laravel-invitations-admin)**
package ships a React + Tailwind admin SPA over this package's API.

## Roadmap

See [`docs/ROADMAP.md`](docs/ROADMAP.md).

## License

MIT © [Padosoft](https://www.padosoft.com)
