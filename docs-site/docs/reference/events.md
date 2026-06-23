---
title: Domain events
description: Reference for the three lifecycle events — payloads and fire conditions.
---

# Domain events reference

Three immutable event objects under `Padosoft\Invitations\Events`. For the listening pattern and the
fire‑once contract, see the [Domain events guide](/guides/events).

## `CodeRedeemed`

```php
final class CodeRedeemed
{
    public function __construct(
        public readonly Redemption $redemption,
        public readonly bool $already = false,
    ) {}
}
```

Fired **once**, on a fresh seat claim only — never on an idempotent replay. Use it to grant perks, send
a welcome, or update projections without guarding against replays.

## `InvitationSent`

```php
final class InvitationSent
{
    public function __construct(public readonly Invitation $invitation) {}
}
```

Fired when an email invitation is sent. Listen to queue the actual mail.

## `InvitationAccepted`

```php
final class InvitationAccepted
{
    public function __construct(public readonly Invitation $invitation) {}
}
```

Fired when a pending invitation is accepted (`pending → accepted`).

## At a glance

| Event | Fire condition | Idempotent? |
|---|---|---|
| `CodeRedeemed` | fresh claim commits | yes — never on replay |
| `InvitationSent` | invitation sent | per send |
| `InvitationAccepted` | invitation accepted | once per acceptance |

::: callout tip
Register listeners in your `EventServiceProvider` or via `Event::listen(...)`. The referral and the
provisioned grant are already resolved when `CodeRedeemed` fires, so the listener can read
`$event->redemption` and the attributed referral safely.
:::
