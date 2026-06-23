<?php

declare(strict_types=1);

namespace Padosoft\Invitations;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for padosoft/laravel-invitations.
 *
 * Scaffold stage (Phase 0a): wires the config file only. The engine (models,
 * migrations, services, routes, commands, MCP tools) is ported from the seed
 * in Phase 1 and registered here behind the package's interfaces
 * (TenantResolver / Provisioner / InvitedAccount) — see docs/ROADMAP.md.
 */
final class InvitationsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-invitations')
            ->hasConfigFile('invitations');
    }
}
