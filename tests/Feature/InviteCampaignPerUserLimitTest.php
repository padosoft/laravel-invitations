<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Feature;

use Padosoft\Invitations\Models\InviteCampaign;
use Padosoft\Invitations\Tests\TestCase;

/**
 * `per_user_limit` is an OPTIONAL, currently-unenforced campaign field. The
 * column is nullable (null = no per-user limit / unset, like
 * `max_redemptions_total`), the store validates it `nullable`, and the update
 * accepts an explicit `null` to clear it — none of these may 500 on a NOT NULL
 * constraint (the regression this suite guards).
 */
final class InviteCampaignPerUserLimitTest extends TestCase
{
    public function test_create_accepts_an_explicit_null_per_user_limit(): void
    {
        $this->actingAs($this->makeUser('admin@example.com'))
            ->postJson('/api/admin/invitations/campaigns', [
                'key' => 'null-limit',
                'name' => 'Null Limit',
                'type' => InviteCampaign::TYPE_SINGLE_USE,
                'per_user_limit' => null,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('invite_campaigns', [
            'key' => 'null-limit',
            'per_user_limit' => null,
        ]);
    }

    public function test_create_defaults_per_user_limit_to_one_when_omitted(): void
    {
        $this->actingAs($this->makeUser('admin@example.com'))
            ->postJson('/api/admin/invitations/campaigns', [
                'key' => 'omitted-limit',
                'name' => 'Omitted Limit',
                'type' => InviteCampaign::TYPE_SINGLE_USE,
            ])
            ->assertSuccessful();

        $campaign = InviteCampaign::query()->where('key', 'omitted-limit')->firstOrFail();
        $this->assertSame(1, $campaign->per_user_limit);
    }

    public function test_update_clears_per_user_limit_to_null(): void
    {
        $campaign = InviteCampaign::query()->create([
            'tenant_id' => 'default',
            'key' => 'clear-limit',
            'name' => 'Clear Limit',
            'type' => InviteCampaign::TYPE_SINGLE_USE,
            'status' => InviteCampaign::STATUS_DRAFT,
            'per_user_limit' => 5,
            'created_by' => 1,
        ]);

        $this->actingAs($this->makeUser('admin@example.com'))
            ->patchJson('/api/admin/invitations/campaigns/'.$campaign->id, [
                'per_user_limit' => null,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('invite_campaigns', [
            'id' => $campaign->id,
            'per_user_limit' => null,
        ]);
    }
}
