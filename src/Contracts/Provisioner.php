<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Contracts;

use Illuminate\Database\Eloquent\Model;
use Padosoft\Invitations\Provisioning\SpatiePermissionProvisioner;
use Padosoft\Invitations\Support\TenantGrant;

/**
 * Applies ONE tenant-slice of an invite key's grant to a redeemer account.
 *
 * The package ships {@see SpatiePermissionProvisioner}
 * (role grant) as the default. A host registers additional provisioners (tagged
 * `invitations.provisioners`) to apply host-specific access — e.g. AskMyDocs
 * registers a project-membership provisioner.
 *
 * Two invariants every implementation MUST honour:
 *   - GRANT, never REVOKE — only ever raise access, never downgrade/clobber.
 *   - BEST-EFFORT — a fault must be swallowed + logged, never thrown: the
 *     redemption is already committed when provisioning runs.
 */
interface Provisioner
{
    public function provision(Model $account, TenantGrant $grant): void;
}
