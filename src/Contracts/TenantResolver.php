<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Contracts;

use Padosoft\Invitations\Tenancy\DefaultTenantResolver;

/**
 * Resolves the active tenant id for every invitation query / write.
 *
 * The package is multi-tenant by data shape (every table carries `tenant_id`)
 * but vendor-neutral by contract: a plain app gets the single-tenant
 * {@see DefaultTenantResolver}; a multi-tenant
 * host (e.g. AskMyDocs) binds its own resolver in a service provider.
 */
interface TenantResolver
{
    /**
     * The current tenant id. Never null — single-tenant deployments return the
     * configured default (e.g. 'default').
     */
    public function current(): string;
}
