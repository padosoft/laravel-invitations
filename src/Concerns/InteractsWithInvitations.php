<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Padosoft\Invitations\Contracts\InvitedAccount;

/**
 * Default {@see InvitedAccount} implementation
 * for a standard Eloquent user model with an `email` column. Override either
 * method if your model differs.
 *
 * @mixin Model
 */
trait InteractsWithInvitations
{
    public function getInviteEmail(): ?string
    {
        $email = $this->getAttribute('email');

        return is_string($email) ? $email : null;
    }

    public function getInviteGuardName(): string
    {
        $guard = $this->getAttribute('guard_name');

        return is_string($guard) && $guard !== ''
            ? $guard
            : (string) config('auth.defaults.guard', 'web');
    }
}
