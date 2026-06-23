<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests;

use Padosoft\Invitations\InvitationsServiceProvider;

final class ScaffoldTest extends TestCase
{
    public function test_the_service_provider_is_registered(): void
    {
        $this->assertTrue(
            $this->app->providerIsLoaded(InvitationsServiceProvider::class),
        );
    }

    public function test_the_config_file_is_published_into_the_container(): void
    {
        // The test harness overrides user_model to the fixture; assert the key
        // resolves and the static defaults are present.
        $this->assertIsString(config('invitations.user_model'));
        $this->assertSame('default', config('invitations.default_tenant'));
        $this->assertFalse(config('invitations.invitation_required'));
    }
}
