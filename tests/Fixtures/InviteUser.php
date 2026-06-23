<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Padosoft\Invitations\Concerns\InteractsWithInvitations;
use Padosoft\Invitations\Contracts\InvitedAccount;

/**
 * Minimal account model for the package test harness — exactly the shape a
 * host wires up: extend the framework user, implement {@see InvitedAccount},
 * use {@see InteractsWithInvitations}.
 */
final class InviteUser extends Authenticatable implements InvitedAccount
{
    use InteractsWithInvitations;

    protected $table = 'users';

    protected $guarded = [];
}
