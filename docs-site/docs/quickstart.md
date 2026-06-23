---
title: Quickstart
description: Generate and redeem your first invite code in 60 seconds — PHP, HTTP API, and MCP.
---

# Quickstart

This is the 60‑second tour. For a deep treatment of *why* redemption is correct under load, read
[Atomic idempotent redemption](/concepts/atomic-redemption).

## 1. Install

```bash
composer require padosoft/laravel-invitations
php artisan migrate
```

Make your user model invitation‑aware:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Padosoft\Invitations\Concerns\InteractsWithInvitations;
use Padosoft\Invitations\Contracts\InvitedAccount;

class User extends Authenticatable implements InvitedAccount
{
    use InteractsWithInvitations; // reads `email` + auth guard for the engine
}
```

Full details (publishing config / routes / migrations) are in [Installation](/installation).

## 2. Generate codes (PHP)

```php
use Padosoft\Invitations\Services\CodeGenerator;

$code  = app(CodeGenerator::class)->generateRandom(['max_uses' => 100]);
$batch = app(CodeGenerator::class)->generateBatch(500); // 500 distinct codes
```

Codes are CSPRNG‑drawn Crockford Base32 (no confusable `I L O U`), normalized to a canonical form so
the generator and the redeemer agree on identity. See [Invite codes](/guides/invite-codes).

## 3. Redeem a code — atomic, idempotent, fraud‑gated

```php
use Padosoft\Invitations\Services\RedemptionService;

$result = app(RedemptionService::class)->redeem($rawCode, $user, [
    'ip'          => $request->ip(),
    'fingerprint' => $request->header('X-Device'),
]);

if ($result->ok) {
    // $result->already === true on an idempotent replay (no second grant)
    // $result->redemption, $result->referral
} else {
    // $result->error: invalid | expired | exhausted | revoked | ineligible | rate_limited
}
```

::: callout tip
A replay of the **same code by the same account** is always idempotent success (`already: true`) — it
is never rate‑limited and never grants a second seat. The anti‑abuse gate only runs on a *fresh*
claim.
:::

## 4. Over the REST API

Routes auto‑register; attach your own auth / RBAC via config (see
[Configuration reference](/operations/configuration)).

```http
POST /api/invitations/redeem       { "code": "Q7K92MNP" }
POST /api/invitations/validate     { "code": "Q7K92MNP" }   # advisory, writes nothing
GET  /api/admin/invitations/metrics
POST /api/admin/invitations/codes  { "count": 50, "max_uses": 1 }
```

The complete endpoint catalogue is in [The HTTP API](/operations/http-api).

## 5. Over MCP

Register the bundled tools on your MCP server:

```php
// app/Mcp/Servers/YourServer.php
public array $tools = [
    \Padosoft\Invitations\Mcp\Tools\InviteValidateCodeTool::class,
    \Padosoft\Invitations\Mcp\Tools\InviteGenerateCodesTool::class,
    \Padosoft\Invitations\Mcp\Tools\InviteMetricsTool::class,
];
```

See [The MCP surface](/guides/mcp) and the [MCP tools reference](/reference/mcp-tools).

## Where to go next

::: grids
  ::: grid
    ::: card "Concepts" icon:book-open
    The invariants that make this package correct.

    [Atomic redemption →](/concepts/atomic-redemption)
    :::
  :::
  ::: grid
    ::: card "Architecture" icon:network
    The pipeline, the data model, and the decision records.

    [Overview →](/architecture/overview)
    :::
  :::
  ::: grid
    ::: card "Configuration" icon:settings
    Every env knob and its safe default.

    [Configuration →](/operations/configuration)
    :::
  :::
:::
