---
title: MCP tools
description: Reference for the three bundled MCP tools — schemas, output shapes, annotations, and registration.
---

# MCP tools reference

Three tools under `Padosoft\Invitations\Mcp\Tools`, each a thin adapter over a core service. For the
design rationale and a worked agent flow, see [The MCP surface](/guides/mcp).

## Registration

```php
public array $tools = [
    \Padosoft\Invitations\Mcp\Tools\InviteValidateCodeTool::class,
    \Padosoft\Invitations\Mcp\Tools\InviteGenerateCodesTool::class,
    \Padosoft\Invitations\Mcp\Tools\InviteMetricsTool::class,
];
```

`laravel/mcp` is an optional dependency; the consumer registers the tools on its own server.

## `InviteValidateCodeTool`

`#[IsReadOnly]` · `#[IsIdempotent]` — validate without redeeming.

```text
input:  { code: string (required) }   # any casing / separators — normalized
output: { valid: true, code_kind, max_uses, current_uses }
      | { valid: false, error: "invalid"|"expired"|"exhausted"|"revoked"|"ineligible" }
```

Delegates to `CodeValidator::validate()`. The `error` is the canonical lowercase `RedemptionError`
value.

## `InviteGenerateCodesTool`

Write surface — mint a batch.

```text
input:  { count: int 1..1000 (required),
          campaign_key?: string,   # tenant-scoped; "campaign_not_found" if missing
          max_uses?: int = 1,
          length?: int }           # body length; 8 ≈ 40 bits
output: { codes: [string, ...] } | { error: "campaign_not_found", campaign_key }
        | { error: "count must be between 1 and 1000" }
```

Delegates to `CampaignService` / `CodeGenerator`; codes are normalized Crockford with a `UNIQUE(code)`
collision guard.

## `InviteMetricsTool`

`#[IsReadOnly]` · `#[IsIdempotent]` — read virality metrics.

```text
input:  { campaign_id?: int, since_days?: int }
output: { codes_issued, redemptions, invites_sent, invites_accepted,
          referrals_qualified, distinct_referrers,
          k_factor, acceptance_rate, conversion_rate,
          ttr_p50_seconds, ttr_p90_seconds }
```

Delegates to `MetricsService::summary()` — see [Virality analytics](/concepts/analytics).

## Tenant scope

Every tool resolves the tenant from the MCP‑resolved `TenantResolver` (R30), so a tool call is scoped
to the caller's tenant exactly like an HTTP call.

::: callout tip
The read tools carry `IsReadOnly` + `IsIdempotent`, so MCP clients may cache and retry them. The
generate tool is a write — a partial batch is still valid because each code is persisted
independently.
:::
