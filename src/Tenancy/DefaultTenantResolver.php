<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tenancy;

use Padosoft\Invitations\Contracts\TenantResolver;

/**
 * Single-tenant resolver — returns the configured default tenant id. Bound by
 * default; a multi-tenant host overrides the {@see TenantResolver} binding.
 */
final class DefaultTenantResolver implements TenantResolver
{
    public function current(): string
    {
        return (string) config('invitations.default_tenant', 'default');
    }
}
