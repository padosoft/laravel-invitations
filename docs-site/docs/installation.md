---
title: Installation
description: Requirements, install, migrations, publishable assets, and making your user model invitation-aware.
---

# Installation

## Requirements

- PHP `^8.3`
- Laravel `^12.0 | ^13.0`

The package is **vendor‑neutral**. `spatie/laravel-permission`, `laravel/fortify` and `laravel/mcp`
are *optional* first‑class integrations — none of them is a hard dependency.

## Install

```bash
composer require padosoft/laravel-invitations
php artisan migrate
```

The service provider is auto‑discovered. The migrations create the nine invite tables (see
[Data model](/architecture/data-model)).

## Make your user model invitation‑aware

The engine never references a concrete `App\Models\User`. It types against the `Authenticatable`
contract plus an `InvitedAccount` interface that exposes the redeemer's email. Add the trait:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Padosoft\Invitations\Concerns\InteractsWithInvitations;
use Padosoft\Invitations\Contracts\InvitedAccount;

class User extends Authenticatable implements InvitedAccount
{
    use InteractsWithInvitations; // satisfies getInviteEmail() from the model's `email`
}
```

If your user model stores email differently, implement `InvitedAccount` yourself instead of using the
trait. The model class is configurable via `INVITATIONS_USER_MODEL`.

## Publishable assets

::: tabs

== tab "Config"

```bash
php artisan vendor:publish --tag=invitations-config
```

Publishes `config/invitations.php`. Every value is env‑overridable; see
[Configuration reference](/operations/configuration).

== tab "Routes"

```bash
php artisan vendor:publish --tag=invitations-routes
```

Publishes the route file so you can fully control prefix + middleware in your own app. By default the
routes auto‑register under the `api` prefix.

== tab "Migrations"

```bash
php artisan vendor:publish --tag=invitations-migrations
```

Publishes the migrations if you need to customize them before running `php artisan migrate`.

:::

## Multi‑tenant hosts

A single‑tenant app needs **zero** configuration — every row gets the `default` tenant. A
multi‑tenant host binds its own resolver so rows scope per customer:

```php
// In a service provider:
$this->app->bind(
    \Padosoft\Invitations\Contracts\TenantResolver::class,
    MyTenantResolver::class,
);
```

See [Multi‑tenancy & host seams](/concepts/multi-tenancy) for the full seam contract.

## Verify the install

```php
use Padosoft\Invitations\Services\CodeGenerator;

$code = app(CodeGenerator::class)->generateRandom(['max_uses' => 1]);
// $code->code is an 8-char Crockford Base32 string, state = 'active'
```

::: callout tip
Production should set `INVITE_SIGNING_KEY` (signed codes) and `INVITE_PII_SALT` (PII HMAC). In dev
both fall back to `APP_KEY`‑derived material so nothing is ever unsigned or stored unsalted by
accident.
:::
