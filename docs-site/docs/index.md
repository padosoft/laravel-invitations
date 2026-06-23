---
title: Laravel Invitations
description: Enterprise invite-by-code, referral, rewards, waitlist & anti-abuse suite for Laravel — multi-tenant, concurrency-safe, idempotent redemption, GDPR-ready, tri-surface (PHP + HTTP API + MCP).
---

![Laravel Invitations banner](https://raw.githubusercontent.com/padosoft/laravel-invitations/main/resources/banner.png)

# Laravel Invitations

**The enterprise invite‑by‑code, referral, rewards, waitlist & anti‑abuse suite for Laravel.**

Multi‑tenant · concurrency‑safe · idempotent redemption · GDPR‑ready · tri‑surface (PHP + HTTP API + MCP)

`padosoft/laravel-invitations` is a headless, vendor‑neutral acquisition engine. It mints invite
codes, attributes referrals, grants double‑sided rewards, runs a viral waitlist, scores abuse, and
reports K‑factor analytics — all behind a single core service set that you reach from **PHP**, a
**REST API**, or **MCP** tools.

::: callout info
This package was extracted from a production‑proven engine (AskMyDocs PR #355) and is in active
development toward `v1.0.0`. The engine is fully tested; the public API may still shift before the
`v1.0.0` tag.
:::

## Why this package exists

Every Laravel invite / referral package on the market stops at *"generate a code, mark it used."*
None of them solve the problems that actually bite in production:

- **They over‑redeem under load.** The popular packages increment a use‑counter with a
  check‑then‑write and **no lock** — two concurrent redemptions both pass the *"1 seat left"* check.
  That is a free‑code / over‑capacity bug.
- **They are single‑tenant.** Codes are globally unique, so two customers can never share an
  intuitive code, and rows leak across tenant boundaries.
- **They store invitee emails forever** with no erasure path — a GDPR liability.
- **They have no events, no fraud controls, no analytics, and no API / MCP surface.**

This package is built the other way around: **correctness, multi‑tenancy, privacy and observability
first**. The cornerstone is a single conditional `UPDATE … WHERE current_uses < max_uses` that flips
state in the same statement, backed by a `UNIQUE(code_id, redeemer_id)` index — so `current_uses` can
**never** exceed `max_uses`, and a replay is a no‑op, never a double‑grant, even under a thundering
herd. Read the full argument in [Atomic idempotent redemption](/concepts/atomic-redemption).

## What you get

::: grids
  ::: grid
    ::: card "Atomic redemption" icon:lock
    Lock‑free, idempotent, concurrency‑safe seat claim. `current_uses` is mathematically capped at `max_uses`; replays return the original claim.

    [Read the theory →](/concepts/atomic-redemption)
    :::
  :::
  ::: grid
    ::: card "Multi‑tenant" icon:building-2
    Every table is tenant‑scoped; two tenants can share the same human code. Single‑tenant apps get a zero‑config `default` tenant.

    [Tenancy & seams →](/concepts/multi-tenancy)
    :::
  :::
  ::: grid
    ::: card "Referrals & rewards" icon:gift
    A referral graph with first‑wins attribution and a double‑sided, idempotent reward ledger (`granted → reversed`).

    [Referrals & rewards →](/guides/referrals-rewards)
    :::
  :::
  ::: grid
    ::: card "Fail‑open anti‑abuse" icon:shield
    Weighted velocity / disposable‑email / honeypot / blacklist scoring that surfaces a generic `rate_limited` and stores HMAC‑hashed PII only.

    [Anti‑abuse scoring →](/concepts/anti-abuse)
    :::
  :::
  ::: grid
    ::: card "Virality analytics" icon:trending-up
    K‑factor, acceptance / conversion rates, and time‑to‑redeem percentiles — reconciled against the canonical rows, not a drifting rollup.

    [K‑factor analytics →](/concepts/analytics)
    :::
  :::
  ::: grid
    ::: card "Tri‑surface" icon:layers
    The same core reachable from PHP services + Artisan, an RBAC‑gated REST API, and MCP tools.

    [The MCP surface →](/guides/mcp)
    :::
  :::
:::

## How it compares

| Capability | **laravel‑invitations** | doorman | mateusjunges/invite‑codes | pdazcom/referrals | taldres/waitlist |
|---|:---:|:---:|:---:|:---:|:---:|
| Invite codes (max‑uses) | ✅ | ✅ | ✅ | — | — |
| **Concurrency‑safe redemption** | ✅ | ❌ | ❌ | — | — |
| **Idempotent replay** | ✅ | ❌ | ❌ | ⚠️ | ⚠️ |
| **Multi‑tenant scoping** | ✅ | ❌ | ❌ | ❌ | ❌ |
| Vanity / signed codes | ✅ | ❌ | ⚠️ | — | ⚠️ |
| Email invitations | ✅ | ✅ | ⚠️ | ❌ | ⚠️ |
| Referral graph + double‑sided rewards | ✅ | ❌ | ❌ | ⚠️ referrer‑only | ❌ |
| **Anti‑abuse / fraud** | ✅ | ❌ | ❌ | ⚠️ self‑ref only | ❌ |
| Per‑invite role / entitlement grant | ✅ | ❌ | ❌ | ❌ | ❌ |
| Virality analytics (K‑factor) | ✅ | ❌ | ❌ | ❌ | ❌ |
| GDPR erasure | ✅ | ❌ | ❌ | ❌ | ✅ |
| Events / hooks | ✅ | ❌ | ✅ | ✅ | ✅ |
| HTTP API + **MCP** surface | ✅ | ❌ | ❌ | ❌ | ❌ |

## Next steps

::: steps

1. **Install**
   `composer require padosoft/laravel-invitations` and run the migrations.
   See [Installation](/installation).

2. **Generate & redeem your first code**
   The 60‑second tour lives in [Quickstart](/quickstart).

3. **Understand the invariants**
   The cornerstones — [atomic redemption](/concepts/atomic-redemption),
   [multi‑tenancy](/concepts/multi-tenancy), [anti‑abuse](/concepts/anti-abuse) — are what make this
   package different.

:::

## AI vibe‑coding pack included

The repo ships a complete AI pair‑programming kit: `CLAUDE.md` (engineering invariants + quality
gates), `AGENTS.md`, and the design / roadmap docs under `docs/`. Point Claude Code, Cursor, or
Copilot at the repo and they inherit the package's rules (atomic redemption, tenant scoping,
fail‑open fraud, GRANT‑never‑REVOKE) automatically.
