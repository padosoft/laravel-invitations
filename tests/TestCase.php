<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Padosoft\Invitations\InvitationsServiceProvider;
use Padosoft\Invitations\Tests\Fixtures\InviteUser;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            InvitationsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('invitations.user_model', InviteUser::class);
        // Route auth without the web/CSRF stack in tests; actingAs() suffices.
        $app['config']->set('invitations.routes.user_middleware', ['auth']);
        $app['config']->set('invitations.routes.admin_middleware', ['auth']);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Minimal `users` table — the FK target + redeemer/issuer/inviter.
        Schema::create('users', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('guard_name')->nullable();
            $table->timestamps();
        });

        // Package migrations are registered by the service provider's
        // loadMigrationsFrom() and run by RefreshDatabase.
    }

    protected function makeUser(string $email = 'user@example.com'): InviteUser
    {
        return InviteUser::query()->create([
            'name' => 'Test',
            'email' => $email,
            'password' => bcrypt('secret'),
        ]);
    }
}
