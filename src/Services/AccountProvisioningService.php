<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Padosoft\Invitations\Contracts\InvitedAccount;
use Padosoft\Invitations\Contracts\Provisioner;
use Padosoft\Invitations\Models\InviteAnalyticsEvent;
use Padosoft\Invitations\Provisioning\SpatiePermissionProvisioner;
use Padosoft\Invitations\Support\InviteGrant;
use Padosoft\Invitations\Support\TenantGrant;

/**
 * Applies an invite key's provisioning grant to the redeemer's account by
 * fanning each tenant-slice out to every registered {@see Provisioner}
 * (R44 core — the same path used by the HTTP redeem endpoint, the deferred
 * Login/Registered listener, and any MCP/CLI redemption).
 *
 * The package ships {@see SpatiePermissionProvisioner}
 * (role grant) under the `invitations.provisioners` tag; a host adds its own —
 * AskMyDocs registers a project-membership provisioner. The two invariants are
 * enforced by the provisioners themselves (GRANT-never-REVOKE) and by this
 * orchestrator (BEST-EFFORT: a fault is logged, never thrown — the redemption
 * is already committed).
 */
final class AccountProvisioningService
{
    /**
     * @param  iterable<Provisioner>  $provisioners
     */
    public function __construct(
        private readonly AnalyticsTracker $analytics,
        private readonly iterable $provisioners = [],
    ) {}

    public function provision(Model&InvitedAccount $user, InviteGrant $grant, string $tenantId): void
    {
        $tenantGrants = $grant->effectiveTenantGrants($tenantId);
        if ($tenantGrants === []) {
            return;
        }

        try {
            // One or MORE tenants: a single code can seed memberships across
            // several tenants ("teams"). Each registered provisioner applies
            // its slice of access additively.
            foreach ($tenantGrants as $tenantGrant) {
                foreach ($this->provisioners as $provisioner) {
                    $provisioner->provision($user, $tenantGrant);
                }
            }

            $this->analytics->record(
                InviteAnalyticsEvent::TYPE_ACCOUNT_PROVISIONED,
                "provisioned:{$tenantId}:{$user->getKey()}",
                [
                    'account_id' => $user->getKey(),
                    'tenant_count' => count($tenantGrants),
                    'project_count' => array_sum(array_map(
                        static fn (TenantGrant $g): int => count($g->projects),
                        $tenantGrants,
                    )),
                ],
            );
        } catch (\Throwable $e) {
            // Never propagate — the redemption is already committed.
            Log::error('invitations.provision.failed', [
                'tenant_id' => $tenantId,
                'account_id' => $user->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
