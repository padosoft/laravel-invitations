<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests\Feature;

use Padosoft\Invitations\Services\FraudDetector;
use Padosoft\Invitations\Support\AssessmentContext;
use Padosoft\Invitations\Tests\TestCase;

final class FraudDetectorTest extends TestCase
{
    public function test_a_filled_honeypot_is_blocked(): void
    {
        $decision = app(FraudDetector::class)->assess(new AssessmentContext(
            tenantId: 'default',
            action: 'redeem',
            ip: '203.0.113.7',
            honeypot: true,
        ));

        $this->assertTrue($decision->blocked());
    }

    public function test_a_clean_request_is_not_blocked(): void
    {
        $decision = app(FraudDetector::class)->assess(new AssessmentContext(
            tenantId: 'default',
            action: 'redeem',
            accountId: 1,
            ip: '203.0.113.8',
            email: 'real.person@example.com',
        ));

        $this->assertFalse($decision->blocked());
    }

    public function test_the_gate_is_disabled_by_config(): void
    {
        config()->set('invitations.anti_abuse.enabled', false);

        $decision = app(FraudDetector::class)->assess(new AssessmentContext(
            tenantId: 'default',
            action: 'redeem',
            honeypot: true, // would block if the gate were on
        ));

        $this->assertFalse($decision->blocked());
    }
}
