<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Padosoft\Invitations\Contracts\TenantResolver;

/**
 * Vendor-neutral tenant trait for the package's models. Auto-fills `tenant_id`
 * from the bound {@see TenantResolver} on create, and exposes `forTenant()`
 * for explicit tenant-scoped reads (R30 discipline, host-agnostic).
 *
 * @mixin Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(static function ($model): void {
            if (empty($model->getAttribute('tenant_id'))) {
                $model->setAttribute('tenant_id', app(TenantResolver::class)->current());
            }
        });
    }

    /**
     * Scope to a tenant — the given id, or the currently-resolved tenant.
     */
    public function scopeForTenant(Builder $query, ?string $tenantId = null): Builder
    {
        return $query->where(
            $this->getTable().'.tenant_id',
            $tenantId ?? app(TenantResolver::class)->current(),
        );
    }
}
