<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Feature;

use Padosoft\Invitations\Models\Redemption;
use Padosoft\Invitations\Services\CodeGenerator;
use Padosoft\Invitations\Services\ErasureService;
use Padosoft\Invitations\Services\RedemptionService;
use Padosoft\Invitations\Tests\TestCase;

final class GdprTest extends TestCase
{
    public function test_export_returns_the_accounts_invite_records(): void
    {
        $code = app(CodeGenerator::class)->generateRandom();
        $user = $this->makeUser();
        app(RedemptionService::class)->redeem($code->code, $user);

        $export = app(ErasureService::class)->exportAccount((int) $user->getKey(), $user->getInviteEmail());

        $this->assertSame((int) $user->getKey(), $export['account_id']);
        $this->assertCount(1, $export['redemptions']);
    }

    public function test_erasure_preserves_aggregates(): void
    {
        $code = app(CodeGenerator::class)->generateRandom(['max_uses' => 5]);
        $user = $this->makeUser();
        app(RedemptionService::class)->redeem($code->code, $user);

        $code->refresh();
        $this->assertSame(1, $code->current_uses);

        app(ErasureService::class)->eraseAccount((int) $user->getKey(), $user->getInviteEmail());

        // The redemption row and the use-counter survive erasure — only PII is
        // anonymized. Aggregates must never be corrupted by a GDPR request.
        $code->refresh();
        $this->assertSame(1, $code->current_uses);
        $this->assertSame(1, Redemption::query()->count());
    }
}
