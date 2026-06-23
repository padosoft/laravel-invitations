<?php

declare(strict_types=1);

/**
 * Configuration for padosoft/laravel-invitations.
 *
 * Scaffold stage (Phase 0a): minimal stub so the package publishes a config
 * file. The full knob set (codes alphabet/length, token bytes, invitation TTL,
 * signing key, PII retention, anti-abuse thresholds/velocity/blocklists,
 * rewards ledger, waitlist) lands in Phase 1 when the engine is ported from the
 * seed. Every value will be env-overridable; see docs/ROADMAP.md.
 */
return [
    // The host's user/account model. The package types against the
    // Authenticatable contract + an InvitedAccount interface, never a concrete
    // app model — so plain Fortify/Breeze apps and AskMyDocs both work.
    'user_model' => env('INVITATIONS_USER_MODEL', 'App\\Models\\User'),

    // Multi-tenant: when false the package operates single-tenant under the
    // 'default' tenant id; a host binds its own TenantResolver to scope rows.
    'multi_tenant' => (bool) env('INVITATIONS_MULTI_TENANT', false),

    // The signup gate. When true, registration requires a valid invite code.
    'invitation_required' => (bool) env('INVITE_REQUIRED', false),
];
