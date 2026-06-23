<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Padosoft\Invitations\Http\Controllers\InviteCampaignController;
use Padosoft\Invitations\Http\Controllers\InviteCodeController;
use Padosoft\Invitations\Http\Controllers\InviteInvitationController;
use Padosoft\Invitations\Http\Controllers\InviteMetricsController;
use Padosoft\Invitations\Http\Controllers\InviteReadController;
use Padosoft\Invitations\Http\Controllers\RedemptionController;

/*
|--------------------------------------------------------------------------
| Invitations routes
|--------------------------------------------------------------------------
| Prefix + middleware are config-driven so a host attaches its own auth/RBAC
| (e.g. add a `can:` / role middleware to `invitations.routes.admin_middleware`).
| Publishable: `php artisan vendor:publish --tag=invitations-routes`.
*/

$prefix = (string) config('invitations.routes.prefix', 'api');

// ── User-facing redemption surface ───────────────────────────────────────
Route::middleware((array) config('invitations.routes.user_middleware', ['web', 'auth']))
    ->prefix($prefix.'/invitations')
    ->group(function (): void {
        Route::post('redeem', [RedemptionController::class, 'redeem'])->name('invitations.redeem');
        Route::post('validate', [RedemptionController::class, 'validateCode'])->name('invitations.validate');
        Route::get('pending-count', [RedemptionController::class, 'pendingCount'])->name('invitations.pending-count');
    });

// ── Admin management surface ─────────────────────────────────────────────
Route::middleware((array) config('invitations.routes.admin_middleware', ['web', 'auth']))
    ->prefix($prefix.'/admin/invitations')
    ->group(function (): void {
        Route::get('campaigns', [InviteCampaignController::class, 'index'])->name('invitations.admin.campaigns.index');
        Route::get('tenants', [InviteCampaignController::class, 'tenants'])->name('invitations.admin.tenants');
        Route::post('campaigns', [InviteCampaignController::class, 'store'])->name('invitations.admin.campaigns.store');
        Route::get('campaigns/{id}', [InviteCampaignController::class, 'show'])->whereNumber('id')->name('invitations.admin.campaigns.show');
        Route::patch('campaigns/{id}', [InviteCampaignController::class, 'update'])->whereNumber('id')->name('invitations.admin.campaigns.update');

        Route::get('codes', [InviteCodeController::class, 'index'])->name('invitations.admin.codes.index');
        Route::post('codes', [InviteCodeController::class, 'store'])->name('invitations.admin.codes.store');
        Route::post('codes/{id}/revoke', [InviteCodeController::class, 'revoke'])->whereNumber('id')->name('invitations.admin.codes.revoke');

        Route::get('metrics', [InviteMetricsController::class, 'index'])->name('invitations.admin.metrics');
        Route::post('invitations', [InviteInvitationController::class, 'store'])->name('invitations.admin.invitations.store');

        // Read surfaces for the referral / reward / waitlist / anti-abuse domains.
        Route::get('referrals', [InviteReadController::class, 'referrals'])->name('invitations.admin.referrals');
        Route::get('rewards', [InviteReadController::class, 'rewards'])->name('invitations.admin.rewards');
        Route::get('waitlist', [InviteReadController::class, 'waitlist'])->name('invitations.admin.waitlist');
        Route::get('abuse-signals', [InviteReadController::class, 'abuseSignals'])->name('invitations.admin.abuse-signals');
    });
