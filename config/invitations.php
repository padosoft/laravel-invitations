<?php

declare(strict_types=1);

/**
 * Configuration for padosoft/laravel-invitations.
 *
 * Every value is env-overridable; the defaults are the safe production posture.
 * Keep this in lockstep with .env documentation and the README.
 */
return [
    // ── Host integration seams ───────────────────────────────────────────
    // The host's user/account model. The package types against the
    // Authenticatable contract + the InvitedAccount interface, never a concrete
    // app class — so plain Fortify/Breeze apps and AskMyDocs both work.
    'user_model' => env('INVITATIONS_USER_MODEL', 'App\\Models\\User'),

    // Multi-tenant: data shape is always tenant-aware; a single-tenant app uses
    // the default tenant id below. A multi-tenant host binds its own
    // TenantResolver to scope rows per customer.
    'default_tenant' => env('INVITATIONS_DEFAULT_TENANT', 'default'),

    // ── HTTP routes ──────────────────────────────────────────────────────
    // The package ships a route file; a host attaches its own auth + RBAC by
    // appending middleware (e.g. a `can:` / role gate) to admin_middleware.
    'routes' => [
        'enabled' => (bool) env('INVITATIONS_ROUTES_ENABLED', true),
        'prefix' => env('INVITATIONS_ROUTES_PREFIX', 'api'),
        // Any authenticated account may redeem.
        'user_middleware' => ['web', 'auth'],
        // Admin management — add your RBAC gate here in the host config.
        'admin_middleware' => ['web', 'auth'],
    ],

    // ── Signup gate ──────────────────────────────────────────────────────
    // When true, registration requires a valid invite code; when false, signup
    // proceeds without one. Default false — opt into closed-beta posture.
    'invitation_required' => (bool) env('INVITE_REQUIRED', false),

    // ── Codes ────────────────────────────────────────────────────────────
    'codes' => [
        // Crockford Base32 — deliberately omits I L O U (confusables with
        // 1 / 0). Do NOT add the omitted letters back: the generator refuses
        // an alphabet containing them.
        'alphabet' => '0123456789ABCDEFGHJKMNPQRSTVWXYZ',

        // Default body length for random codes (40 bits at length 8).
        'default_length' => (int) env('INVITE_CODE_LENGTH', 8),

        // Generate-then-check retries before surfacing collision_exhausted.
        'max_attempts' => (int) env('INVITE_CODE_MAX_ATTEMPTS', 5),

        // Reserved vanity codes (system terms) — rejected with vanity_reserved.
        'reserved' => ['ADMIN', 'API', 'ROOT', 'SYSTEM', 'NULL', 'TEST'],
    ],

    // High-entropy link token (Invitation.token) — bytes of CSPRNG entropy.
    'token_bytes' => (int) env('INVITE_TOKEN_BYTES', 32),

    // Invitation default time-to-live in days.
    'invitation_ttl_days' => (int) env('INVITE_INVITATION_TTL_DAYS', 7),

    // Signed-code HMAC key. Falls back to APP_KEY-derived material when unset so
    // dev never emits an unsigned code; production MUST set a dedicated secret.
    'signing_key' => env('INVITE_SIGNING_KEY'),

    // Session key the deferred-redemption flow parks a guest's code under.
    'pending_session_key' => 'invitations.pending_redemption',

    // ── PII handling ─────────────────────────────────────────────────────
    // ip / fingerprint are stored as salted HMACs, never plaintext.
    'pii' => [
        'hash_salt' => env('INVITE_PII_SALT'),
        'retention_days' => (int) env('INVITE_PII_RETENTION_DAYS', 90),
        // Persist network fields at all? Off by default — only when abuse review
        // needs them.
        'store_network_fields' => (bool) env('INVITE_STORE_NETWORK_FIELDS', false),
    ],

    // ── Anti-abuse ───────────────────────────────────────────────────────
    // Advisory gate — fail-open by design: a detector error NEVER blocks; seat
    // safety comes from the atomic claim.
    'anti_abuse' => [
        'enabled' => (bool) env('INVITE_ANTI_ABUSE_ENABLED', true),

        // Scoring → action thresholds (subject rolling totals). Hard-block
        // signals (blacklist, honeypot) short-circuit to block regardless.
        'thresholds' => [
            'flag' => 25,
            'throttle' => 50,
            'block' => 80,
        ],
        'retry_after' => (int) env('INVITE_ABUSE_RETRY_AFTER', 900),

        // Per-subject velocity: max prior redemptions allowed inside the window.
        'velocity' => [
            'account' => ['max' => 5, 'window' => 86400, 'score' => 30],
            'ip' => ['max' => 10, 'window' => 3600, 'score' => 25],
            'fingerprint' => ['max' => 8, 'window' => 3600, 'score' => 30],
        ],

        // Disposable-email domains (domain-only check).
        'disposable_domains' => ['mailinator.com', 'tempmail.com', 'guerrillamail.com', '10minutemail.com'],
        'disposable_score' => 40,

        // Manual blocklist (hard-block, score 100).
        'blocklist' => [
            'ip_hashes' => [],
            'emails' => [],
            'domains' => [],
            'accounts' => [],
        ],

        // False-positive allowlist — skips scoring entirely.
        'allowlist' => [
            'ips' => [],
            'domains' => [],
            'accounts' => [],
        ],
    ],
];
