<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Feature;

use Padosoft\Invitations\Models\WaitlistEntry;
use Padosoft\Invitations\Services\WaitlistService;
use Padosoft\Invitations\Tests\TestCase;

final class WaitlistTest extends TestCase
{
    public function test_join_is_idempotent_and_assigns_increasing_positions(): void
    {
        $service = app(WaitlistService::class);

        $a = $service->join('a@example.com');
        $b = $service->join('b@example.com');
        $aAgain = $service->join('A@example.com'); // same email, different casing

        $this->assertSame(1, $a->position);
        $this->assertSame(2, $b->position);
        $this->assertSame($a->id, $aAgain->id, 're-join must return the same entry');
        $this->assertSame(2, WaitlistEntry::query()->count());
    }

    public function test_referrals_bump_priority_to_jump_the_queue(): void
    {
        $service = app(WaitlistService::class);
        $service->join('first@example.com');   // position 1, priority 0
        $service->join('second@example.com');  // position 2, priority 0

        $service->recordReferral('second@example.com', 3); // priority 3

        // 'second' now outranks 'first' despite joining later.
        $invited = $service->inviteFromTop(1);

        $this->assertCount(1, $invited);
        $this->assertSame('second@example.com', $invited[0]->email);
        $this->assertSame(WaitlistEntry::STATUS_INVITED, $invited[0]->status);
        $this->assertNotNull($invited[0]->granted_code_id);
    }
}
