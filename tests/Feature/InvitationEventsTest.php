<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Padosoft\Invitations\Events\CodeRedeemed;
use Padosoft\Invitations\Services\CodeGenerator;
use Padosoft\Invitations\Services\RedemptionService;
use Padosoft\Invitations\Tests\TestCase;

final class InvitationEventsTest extends TestCase
{
    public function test_code_redeemed_fires_once_on_a_fresh_claim(): void
    {
        Event::fake([CodeRedeemed::class]);

        $code = app(CodeGenerator::class)->generateRandom(['max_uses' => 2]);
        $user = $this->makeUser();

        app(RedemptionService::class)->redeem($code->code, $user);

        Event::assertDispatched(CodeRedeemed::class, 1);
    }

    public function test_code_redeemed_does_not_fire_on_an_idempotent_replay(): void
    {
        $code = app(CodeGenerator::class)->generateRandom(['max_uses' => 2]);
        $user = $this->makeUser();
        $service = app(RedemptionService::class);

        $service->redeem($code->code, $user); // fresh

        Event::fake([CodeRedeemed::class]);
        $service->redeem($code->code, $user); // replay

        Event::assertNotDispatched(CodeRedeemed::class);
    }
}
