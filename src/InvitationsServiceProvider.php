<?php

declare(strict_types=1);

namespace Padosoft\Invitations;

use Padosoft\Invitations\Contracts\TenantResolver;
use Padosoft\Invitations\Provisioning\SpatiePermissionProvisioner;
use Padosoft\Invitations\Services\AccountProvisioningService;
use Padosoft\Invitations\Tenancy\DefaultTenantResolver;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for padosoft/laravel-invitations.
 *
 * Wires the config, runs the package migrations, and binds the vendor-neutral
 * seams: a single-tenant {@see TenantResolver} default (a host overrides it)
 * and the {@see SpatiePermissionProvisioner} default under the
 * `invitations.provisioners` tag (a host adds its own — e.g. a project-membership
 * provisioner). See docs/ROADMAP.md.
 */
final class InvitationsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-invitations')
            ->hasConfigFile('invitations');
    }

    public function packageRegistered(): void
    {
        // Default single-tenant resolver; a multi-tenant host binds its own.
        $this->app->bindIf(TenantResolver::class, DefaultTenantResolver::class);

        // Default provisioner (role grant). Hosts add more under the same tag.
        $this->app->tag([SpatiePermissionProvisioner::class], 'invitations.provisioners');

        $this->app->when(AccountProvisioningService::class)
            ->needs('$provisioners')
            ->giveTagged('invitations.provisioners');
    }

    public function packageBooted(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'invitations-migrations');
    }
}
