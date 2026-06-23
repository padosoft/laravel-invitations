<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Feature;

use Padosoft\Invitations\Models\InviteCampaign;
use Padosoft\Invitations\Models\Referral;
use Padosoft\Invitations\Models\Reward;
use Padosoft\Invitations\Services\CodeGenerator;
use Padosoft\Invitations\Services\RedemptionService;
use Padosoft\Invitations\Services\ReferralService;
use Padosoft\Invitations\Services\RewardEngine;
use Padosoft\Invitations\Tests\TestCase;

final class ReferralRewardTest extends TestCase
{
    private function referralCampaign(): InviteCampaign
    {
        return InviteCampaign::query()->create([
            'tenant_id' => 'default',
            'key' => 'launch-referral',
            'name' => 'Launch Referral',
            'type' => InviteCampaign::TYPE_REFERRAL,
            'status' => InviteCampaign::STATUS_ACTIVE,
            'per_user_limit' => 1,
            'reward_policy' => [
                'referrer' => ['type' => Reward::TYPE_CREDIT, 'amount' => 100],
                'referee' => ['type' => Reward::TYPE_CREDIT, 'amount' => 50],
            ],
            'created_by' => 1,
        ]);
    }

    public function test_redeeming_a_referrer_code_attributes_a_referral(): void
    {
        $campaign = $this->referralCampaign();
        $referrer = $this->makeUser('referrer@example.com');
        $referee = $this->makeUser('referee@example.com');

        $code = app(CodeGenerator::class)->generateRandom([
            'campaign_id' => $campaign->id,
            'issuer_id' => $referrer->getKey(),
            'max_uses' => 10,
        ]);

        $result = app(RedemptionService::class)->redeem($code->code, $referee);

        $this->assertTrue($result->ok);
        $this->assertNotNull($result->referral);
        $this->assertSame($referrer->getKey(), $result->referral->referrer_id);
        $this->assertSame($referee->getKey(), $result->referral->referee_id);
    }

    public function test_qualifying_grants_double_sided_rewards_idempotently(): void
    {
        $campaign = $this->referralCampaign();
        $referrer = $this->makeUser('referrer@example.com');
        $referee = $this->makeUser('referee@example.com');
        $code = app(CodeGenerator::class)->generateRandom([
            'campaign_id' => $campaign->id, 'issuer_id' => $referrer->getKey(), 'max_uses' => 10,
        ]);
        $referral = app(RedemptionService::class)->redeem($code->code, $referee)->referral;

        $out = app(ReferralService::class)->qualify($referral);

        $this->assertCount(2, $out['rewards'], 'one reward per party (referrer + referee)');
        $this->assertSame(Referral::STATUS_REWARDED, $out['referral']->status);
        $parties = collect($out['rewards'])->pluck('party')->sort()->values()->all();
        $this->assertSame([Reward::PARTY_REFEREE, Reward::PARTY_REFERRER], $parties);

        // Idempotent: re-qualifying does NOT re-grant.
        app(ReferralService::class)->qualify($referral->refresh());
        $this->assertSame(2, Reward::query()->count());
    }

    public function test_a_granted_reward_can_be_reversed(): void
    {
        $campaign = $this->referralCampaign();
        $referrer = $this->makeUser('referrer@example.com');
        $referee = $this->makeUser('referee@example.com');
        $code = app(CodeGenerator::class)->generateRandom([
            'campaign_id' => $campaign->id, 'issuer_id' => $referrer->getKey(), 'max_uses' => 10,
        ]);
        $referral = app(RedemptionService::class)->redeem($code->code, $referee)->referral;
        $reward = app(ReferralService::class)->qualify($referral)['rewards'][0];

        $reversed = app(RewardEngine::class)->reverse($reward);

        $this->assertSame(Reward::STATE_REVERSED, $reversed->state);
        $this->assertSame(Referral::STATUS_REVERSED, $referral->refresh()->status);
    }
}
