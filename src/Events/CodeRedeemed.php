<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Padosoft\Invitations\Models\Redemption;

/**
 * Fired after a code seat is successfully claimed. `$already` is true for an
 * idempotent replay (no new seat consumed). Listen to grant perks, send a
 * welcome, etc. — never to mutate the redemption itself.
 */
final class CodeRedeemed
{
    use Dispatchable;

    public function __construct(
        public readonly Redemption $redemption,
        public readonly bool $already = false,
    ) {}
}
