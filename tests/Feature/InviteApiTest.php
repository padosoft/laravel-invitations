<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Feature;

use Padosoft\Invitations\Models\Redemption;
use Padosoft\Invitations\Services\CodeGenerator;
use Padosoft\Invitations\Tests\TestCase;

final class InviteApiTest extends TestCase
{
    public function test_redeem_endpoint_claims_a_code_for_an_authenticated_account(): void
    {
        $code = app(CodeGenerator::class)->generateRandom();

        $this->actingAs($this->makeUser())
            ->postJson('/api/invitations/redeem', ['code' => $code->code])
            ->assertOk()
            ->assertJson(['ok' => true, 'already' => false]);

        $this->assertSame(1, Redemption::query()->count());
    }

    public function test_redeem_endpoint_requires_authentication(): void
    {
        $code = app(CodeGenerator::class)->generateRandom();

        $this->postJson('/api/invitations/redeem', ['code' => $code->code])
            ->assertUnauthorized();
    }

    public function test_validate_endpoint_reports_a_valid_code(): void
    {
        $code = app(CodeGenerator::class)->generateRandom();

        $this->actingAs($this->makeUser())
            ->postJson('/api/invitations/validate', ['code' => $code->code])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_validate_endpoint_reports_an_unknown_code(): void
    {
        $this->actingAs($this->makeUser())
            ->postJson('/api/invitations/validate', ['code' => 'ZZZZZZZZ'])
            ->assertJson(['valid' => false, 'error' => 'invalid']);
    }

    public function test_admin_can_generate_codes(): void
    {
        $response = $this->actingAs($this->makeUser())
            ->postJson('/api/admin/invitations/codes', ['count' => 3])
            ->assertStatus(201);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_metrics_endpoint_returns_the_funnel_shape(): void
    {
        $this->actingAs($this->makeUser())
            ->getJson('/api/admin/invitations/metrics')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'codes_issued', 'redemptions', 'invites_sent', 'invites_accepted',
                'k_factor', 'acceptance_rate', 'conversion_rate',
            ]]);
    }
}
