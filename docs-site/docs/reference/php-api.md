---
title: PHP API
description: The core service classes and their primary methods — CodeGenerator, RedemptionService, ReferralService, RewardEngine, WaitlistService, MetricsService, ErasureService, InvitationService.
---

# PHP API

The PHP surface is the canonical one — the HTTP API and MCP tools delegate to these services. Resolve
each from the container (`app(Service::class)`); all are tenant‑scoped via the injected
`TenantResolver`.

## CodeGenerator

```php
generateRandom(array $attrs = [], ?int $length = null): InviteCode
generateBatch(int $count, array $attrs = [], ?int $length = null): array
mintVanity(string $requested, array $attrs = []): InviteCode
mintSigned(array $payload, array $attrs = []): InviteCode   // payload: { campaign, capacity, exp }
verifySigned(string $code): array                            // { ok, payload? , reason? }
```

`$attrs`: `campaign_id`, `issuer_id`, `max_uses`, `expires_at`, `metadata`, `grant`, `tenant_id`.
See [Invite codes](/guides/invite-codes).

## RedemptionService

```php
redeem(string $rawCode, Model&InvitedAccount $redeemer, array $context = []): RedemptionResult
```

`$context`: `ip`, `user_agent`, `fingerprint`, `invitation_id`, `honeypot`. Returns a
`RedemptionResult { ok, already, redemption, referral, error }`. The cornerstone — see
[Atomic idempotent redemption](/concepts/atomic-redemption) and the
[pipeline](/architecture/pipeline).

## ReferralService

```php
attribute(Redemption $redemption, InviteCode $code): ?Referral   // first-wins, self-ref rejected
qualify(Referral $referral): array                                // { referral, rewards[] }
```

See [Referrals & rewards](/guides/referrals-rewards).

## RewardEngine

```php
grantForReferral(Referral $referral, string $party): ?Reward   // party: 'referrer' | 'referee'
reverse(Reward $reward): Reward                                 // granted → reversed, idempotent
```

Idempotent on the deterministic `idempotency_key`; honours the per‑referrer cap.

## WaitlistService

```php
join(string $email): WaitlistEntry                          // idempotent per (tenant, email)
recordReferral(string $email, int $by = 1): ?WaitlistEntry  // bumps priority
inviteFromTop(int $count): array                            // mint codes for the top entries
```

See [Waitlist & queue‑jump](/guides/waitlist).

## InvitationService

```php
send(string $recipient, Model&InvitedAccount $inviter, array $options = []): Invitation
accept(string $token, (Model&InvitedAccount)|null $accepter = null): ?Invitation
bounce(Invitation $invitation): Invitation
pendingCountFor(string $recipient): int
expireDue(): int
```

See [Email invitations](/guides/email-invitations).

## MetricsService

```php
summary(?int $campaignId = null, ?int $sinceDays = null): array
// { codes_issued, redemptions, invites_sent, invites_accepted, referrals_qualified,
//   distinct_referrers, k_factor, acceptance_rate, conversion_rate,
//   ttr_p50_seconds, ttr_p90_seconds }
```

See [Virality analytics](/concepts/analytics).

## ErasureService

```php
sweepRetention(int $days, bool $dryRun = false, ?string $tenantId = null): array
exportAccount(int $accountId, ?string $email = null): array   // right-of-access
eraseAccount(int $accountId, ?string $email = null): array    // right-to-be-forgotten
```

See [GDPR & data privacy](/guides/gdpr).

## FraudDetector

```php
assess(AssessmentContext $ctx): AbuseDecision   // fail-open; AbuseDecision::blocked()
```

See [Anti‑abuse scoring](/concepts/anti-abuse).

::: callout tip
Every service injects `TenantResolver`, so a container‑resolved instance is automatically scoped to the
active tenant. In a multi‑tenant host, set the tenant before resolving (or bind a request‑aware
resolver).
:::
