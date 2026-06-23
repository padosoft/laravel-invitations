---
title: Artisan commands
description: The PHP CLI surface — invite:prune-pii, its options, and how to schedule it.
---

# Artisan commands

The PHP surface includes the Artisan layer. Today the package ships one scheduled maintenance command;
generation and redemption are driven from app code / the API / MCP (see [PHP API](/reference/php-api)).

## `invite:prune-pii`

The GDPR retention sweep — anonymizes `Redemption` network fields, `AbuseSignal` PII subjects, and
resolved `Invitation` recipients older than the retention window, **in place** so aggregates survive.

```text
invite:prune-pii
    {--days=    : Override INVITE_PII_RETENTION_DAYS}
    {--tenant=  : tenant_id to sweep (default: current tenant)}
    {--dry-run  : Count rows without anonymizing}
```

| Option | Default | Effect |
|---|---|---|
| `--days=N` | `pii.retention_days` (90) | retention window; `0` disables the rotation |
| `--tenant=X` | current tenant | scope the sweep to one tenant |
| `--dry-run` | off | report counts without writing |

### Examples

```bash
php artisan invite:prune-pii                  # use configured retention
php artisan invite:prune-pii --days=30        # 30-day window
php artisan invite:prune-pii --dry-run        # "Would anonymize: N redemptions, …"
php artisan invite:prune-pii --tenant=acme    # one tenant only
```

The command prints a summary: `Anonymized: N redemptions, M abuse signals, K invitations (retention D
days, tenant T).`

### Scheduling

```php
// In your scheduler (bootstrap/app.php or a service provider):
$schedule->command('invite:prune-pii')
    ->dailyAt('03:50')
    ->onOneServer()
    ->withoutOverlapping();
```

::: callout tip
`--days=0` is the convention to **disable** the rotation (matching the host platform's prune commands).
The sweep is memory‑safe (`chunkById(500)`) and tenant‑scoped, so it is safe to run against large
tables. See [GDPR & data privacy](/guides/gdpr).
:::
