<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Feature;

use Padosoft\Invitations\Models\InviteCode;
use Padosoft\Invitations\Models\Redemption;
use Padosoft\Invitations\Services\CodeGenerator;
use Padosoft\Invitations\Services\RedemptionService;
use Padosoft\Invitations\Support\RedemptionError;
use Padosoft\Invitations\Tests\TestCase;

final class RedemptionServiceTest extends TestCase
{
    public function test_redeem_claims_a_seat_and_flips_state(): void
    {
        $code = app(CodeGenerator::class)->generateRandom();
        $user = $this->makeUser();

        $result = app(RedemptionService::class)->redeem($code->code, $user);

        $this->assertTrue($result->ok);
        $this->assertFalse($result->already);
        $this->assertNotNull($result->redemption);

        $code->refresh();
        $this->assertSame(1, $code->current_uses);
        $this->assertSame(InviteCode::STATE_REDEEMED, $code->state);
        $this->assertSame(1, Redemption::query()->count());
    }

    public function test_replaying_the_same_account_is_idempotent(): void
    {
        // Multi-use code so the code is not exhausted after the first claim —
        // the replay must resolve through the idempotency path, not a fresh seat.
        $code = app(CodeGenerator::class)->generateRandom(['max_uses' => 2]);
        $user = $this->makeUser();
        $service = app(RedemptionService::class);

        $first = $service->redeem($code->code, $user);
        $second = $service->redeem($code->code, $user);

        $this->assertTrue($first->ok);
        $this->assertFalse($first->already);
        $this->assertTrue($second->ok);
        $this->assertTrue($second->already, 'replay must be flagged idempotent');

        $code->refresh();
        $this->assertSame(1, $code->current_uses, 'replay must NOT increment current_uses');
        $this->assertSame(1, Redemption::query()->count(), 'replay must NOT create a second redemption');
    }

    public function test_a_single_use_code_is_exhausted_for_a_second_account(): void
    {
        $code = app(CodeGenerator::class)->generateRandom();
        $service = app(RedemptionService::class);

        $this->assertTrue($service->redeem($code->code, $this->makeUser('a@example.com'))->ok);

        $second = $service->redeem($code->code, $this->makeUser('b@example.com'));

        $this->assertFalse($second->ok);
        $this->assertSame(RedemptionError::Exhausted, $second->error);

        $code->refresh();
        $this->assertSame(1, $code->current_uses, 'current_uses can never exceed max_uses');
    }

    public function test_unknown_code_fails_with_invalid(): void
    {
        $result = app(RedemptionService::class)->redeem('ZZZZZZZZ', $this->makeUser());

        $this->assertFalse($result->ok);
        $this->assertSame(RedemptionError::Invalid, $result->error);
    }
}
