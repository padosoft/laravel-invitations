<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Padosoft\Invitations\Models\Invitation;

/** Fired when a fresh invitation is created and queued for delivery. */
final class InvitationSent
{
    use Dispatchable;

    public function __construct(public readonly Invitation $invitation) {}
}
