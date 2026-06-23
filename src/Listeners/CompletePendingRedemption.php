<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Padosoft\Invitations\Contracts\InvitedAccount;
use Padosoft\Invitations\Services\DeferredRedemptionService;

/**
 * Completes a guest's parked invite redemption on the first auth event
 * (login OR register). Wired in InviteServiceProvider.
 *
 * The read-and-forget happens inside DeferredRedemptionService::complete(),
 * so wiring this to BOTH Login and Registered is safe — the second event
 * finds an empty slot and is a no-op (Phase 2 DoD: "completes on both login
 * and register events; double-firing the event does not double-claim").
 */
final class CompletePendingRedemption
{
    public function __construct(
        private readonly DeferredRedemptionService $deferred,
        private readonly Session $session,
    ) {}

    public function handle(Login|Registered $event): void
    {
        $user = $event->user;

        if (! $user instanceof Model || ! $user instanceof InvitedAccount) {
            return;
        }

        // Failures here must never break the auth flow; the parked code is
        // already pulled (read-and-forget) so there is no retry storm.
        $this->deferred->complete($this->session, $user);
    }
}
