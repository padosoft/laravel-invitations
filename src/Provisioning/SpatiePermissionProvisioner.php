<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Provisioning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Padosoft\Invitations\Contracts\InvitedAccount;
use Padosoft\Invitations\Contracts\Provisioner;
use Padosoft\Invitations\Support\TenantGrant;
use Spatie\Permission\Models\Role;

/**
 * Default provisioner: grants the invite key's role via spatie/laravel-permission.
 *
 * - GRANT-never-REVOKE: `assignRole` is additive + idempotent; an existing role
 *   is never removed.
 * - `super-admin` is never grantable through a code (defence in depth).
 * - No-op when spatie/laravel-permission is absent or the account model does
 *   not expose `assignRole` — so the package degrades cleanly on a plain app.
 *
 * Roles are global (Spatie teams disabled); tenant-scoped access (e.g. project
 * memberships) is the job of a host-registered provisioner, keyed on
 * {@see TenantGrant::$tenantId}.
 */
final class SpatiePermissionProvisioner implements Provisioner
{
    public function provision(Model $account, TenantGrant $grant): void
    {
        $role = $grant->role;

        if ($role === null || $role === 'super-admin') {
            return;
        }

        if (! class_exists(Role::class) || ! method_exists($account, 'assignRole')) {
            return;
        }

        $guard = $account instanceof InvitedAccount
            ? $account->getInviteGuardName()
            : (string) config('auth.defaults.guard', 'web');

        $exists = Role::query()
            ->where('name', $role)
            ->where('guard_name', $guard)
            ->exists();

        if (! $exists) {
            Log::warning('invitations.provision.role_missing', [
                'account_id' => $account->getKey(),
                'role' => $role,
                'guard' => $guard,
            ]);

            return;
        }

        // Additive + idempotent (spatie HasRoles, resolved via Model::__call).
        $account->assignRole($role);
    }
}
