<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Contracts;

use Padosoft\Invitations\Concerns\InteractsWithInvitations;

/**
 * Implemented by the host's user/account model so the package can read the two
 * account attributes it needs (email for fraud/abuse correlation, guard for
 * role provisioning) without hard-coupling to a concrete `App\Models\User`.
 *
 * Add it to your model with the {@see InteractsWithInvitations}
 * trait:
 *
 *     class User extends Authenticatable implements InvitedAccount
 *     {
 *         use \Padosoft\Invitations\Concerns\InteractsWithInvitations;
 *     }
 *
 * `getKey()` comes from the Eloquent model itself, so account params are typed
 * `Illuminate\Database\Eloquent\Model&InvitedAccount`.
 */
interface InvitedAccount
{
    /** The account's email, used as an abuse-correlation subject (hashed). */
    public function getInviteEmail(): ?string;

    /** The auth guard the redeemer's roles are granted under. */
    public function getInviteGuardName(): string;
}
