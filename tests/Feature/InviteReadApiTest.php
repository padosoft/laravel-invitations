<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Feature;

use Padosoft\Invitations\Models\InviteCampaign;
use Padosoft\Invitations\Models\Reward;
use Padosoft\Invitations\Services\CodeGenerator;
use Padosoft\Invitations\Services\RedemptionService;
use Padosoft\Invitations\Services\ReferralService;
use Padosoft\Invitations\Services\WaitlistService;
use Padosoft\Invitations\Tests\TestCase;

final class InviteReadApiTest extends TestCase
{
    public function test_referrals_and_rewards_read_endpoints_return_data(): void
    {
        $campaign = InviteCampaign::query()->create([
            'tenant_id' => 'default', 'key' => 'ref', 'name' => 'Ref', 'type' => InviteCampaign::TYPE_REFERRAL,
            'status' => InviteCampaign::STATUS_ACTIVE, 'per_user_limit' => 1,
            'reward_policy' => ['referrer' => ['type' => Reward::TYPE_CREDIT, 'amount' => 10]],
            'created_by' => 1,
        ]);
        $referrer = $this->makeUser('r@example.com');
        $referee = $this->makeUser('e@example.com');
        $code = app(CodeGenerator::class)->generateRandom([
            'campaign_id' => $campaign->id, 'issuer_id' => $referrer->getKey(), 'max_uses' => 10,
        ]);
        $referral = app(RedemptionService::class)->redeem($code->code, $referee)->referral;
        app(ReferralService::class)->qualify($referral);

        $this->actingAs($this->makeUser('admin@example.com'))
            ->getJson('/api/admin/invitations/referrals')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($this->makeUser('admin2@example.com'))
            ->getJson('/api/admin/invitations/rewards?party=referrer')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_waitlist_read_endpoint_returns_entries(): void
    {
        app(WaitlistService::class)->join('w1@example.com');
        app(WaitlistService::class)->join('w2@example.com');

        $this->actingAs($this->makeUser())
            ->getJson('/api/admin/invitations/waitlist?status=waiting')
            ->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_abuse_signals_read_endpoint_is_reachable(): void
    {
        $this->actingAs($this->makeUser())
            ->getJson('/api/admin/invitations/abuse-signals')
            ->assertOk()->assertJsonStructure(['data']);
    }
}
