<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Padosoft\Invitations\Models\Invitation;

/** Fired when an invitation is accepted (status → accepted). */
final class InvitationAccepted
{
    use Dispatchable;

    public function __construct(public readonly Invitation $invitation) {}
}
