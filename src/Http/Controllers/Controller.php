<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Padosoft\Invitations\Contracts\InvitedAccount;

/**
 * Base controller for the package's HTTP surface. Provides the one shared
 * concern: resolving the authenticated account as the invitation-aware model
 * the engine requires.
 */
abstract class Controller extends BaseController
{
    /**
     * The authenticated account, narrowed to the type the engine consumes, or
     * a 403 when the host's user model is not invitation-aware.
     */
    protected function invitedUser(Request $request): Model&InvitedAccount
    {
        $user = $request->user();

        if ($user instanceof Model && $user instanceof InvitedAccount) {
            return $user;
        }

        abort(403, 'The authenticated account is not invitation-aware (implement '.InvitedAccount::class.').');
    }
}
