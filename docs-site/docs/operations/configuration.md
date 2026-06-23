---
title: Configuration reference
description: Every config/invitations.php key and its env override — seams, routes, signup gate, codes, PII, and anti-abuse.
---

# Configuration reference

Every value in `config/invitations.php` is env‑overridable; the defaults are the safe production
posture. Publish it with:

```bash
php artisan vendor:publish --tag=invitations-config
```

## Host integration seams

| Key | Env | Default | Meaning |
|---|---|---|---|
| `user_model` | `INVITATIONS_USER_MODEL` | `App\Models\User` | the host's account model (typed via `Authenticatable` + `InvitedAccount`) |
| `default_tenant` | `INVITATIONS_DEFAULT_TENANT` | `default` | the tenant id a single‑tenant app uses |

A multi‑tenant host binds its own `TenantResolver` — see
[Multi‑tenancy & host seams](/concepts/multi-tenancy).

## HTTP routes

| Key | Env | Default | Meaning |
|---|---|---|---|
| `routes.enabled` | `INVITATIONS_ROUTES_ENABLED` | `true` | register the bundled routes |
| `routes.prefix` | `INVITATIONS_ROUTES_PREFIX` | `api` | URL prefix for all routes |
| `routes.user_middleware` | — | `['web', 'auth']` | guard on the user redemption surface |
| `routes.admin_middleware` | — | `['web', 'auth']` | guard on the admin surface — **add your RBAC gate here** |

::: callout warning
The default `admin_middleware` is only `['web', 'auth']`. Append your own `can:` / role middleware in
the host config before exposing the admin endpoints — the package cannot know your RBAC scheme. See
[The HTTP API](/operations/http-api).
:::

## Signup gate

| Key | Env | Default | Meaning |
|---|---|---|---|
| `invitation_required` | `INVITE_REQUIRED` | `false` | when true, registration requires a valid invite code (closed‑beta posture) |

## Codes

| Key | Env | Default | Meaning |
|---|---|---|---|
| `codes.alphabet` | — | `0123456789ABCDEFGHJKMNPQRSTVWXYZ` | Crockford Base32 (omits `I L O U`) |
| `codes.default_length` | `INVITE_CODE_LENGTH` | `8` | random body length (40 bits) |
| `codes.max_attempts` | `INVITE_CODE_MAX_ATTEMPTS` | `5` | generate‑then‑check retries before `collision_exhausted` |
| `codes.reserved` | — | `['ADMIN','API','ROOT','SYSTEM','NULL','TEST']` | vanity reserved words |
| `token_bytes` | `INVITE_TOKEN_BYTES` | `32` | CSPRNG entropy for the invitation link token |
| `invitation_ttl_days` | `INVITE_INVITATION_TTL_DAYS` | `7` | default invitation TTL |
| `signing_key` | `INVITE_SIGNING_KEY` | `APP_KEY`‑derived | HMAC key for signed codes — **set a dedicated secret in prod** |
| `pending_session_key` | — | `invitations.pending_redemption` | session key for the deferred‑redemption flow |

See [Invite codes](/guides/invite-codes).

## PII handling

| Key | Env | Default | Meaning |
|---|---|---|---|
| `pii.hash_salt` | `INVITE_PII_SALT` | `APP_KEY`‑derived | salt for PII HMACs — **set in prod** |
| `pii.retention_days` | `INVITE_PII_RETENTION_DAYS` | `90` | retention window for the prune sweep |
| `pii.store_network_fields` | `INVITE_STORE_NETWORK_FIELDS` | `false` | persist ip / fingerprint at all (off by default) |

See [GDPR & data privacy](/guides/gdpr).

## Anti‑abuse

| Key | Env | Default | Meaning |
|---|---|---|---|
| `anti_abuse.enabled` | `INVITE_ANTI_ABUSE_ENABLED` | `true` | run the advisory gate |
| `anti_abuse.thresholds.flag` | — | `25` | score → flag |
| `anti_abuse.thresholds.throttle` | — | `50` | score → throttle |
| `anti_abuse.thresholds.block` | — | `80` | score → block |
| `anti_abuse.retry_after` | `INVITE_ABUSE_RETRY_AFTER` | `900` | `Retry-After` seconds on a throttle |
| `anti_abuse.velocity.*` | — | account 5/24h, ip 10/1h, fingerprint 8/1h | per‑subject velocity rules |
| `anti_abuse.disposable_domains` | — | `mailinator.com`, … | disposable‑email domains |
| `anti_abuse.disposable_score` | — | `40` | score for a disposable‑email hit |
| `anti_abuse.blocklist.*` | — | `[]` | hard‑block ip_hashes / emails / domains / accounts (score 100) |
| `anti_abuse.allowlist.*` | — | `[]` | skip scoring for ips / domains / accounts |

See [Anti‑abuse scoring](/concepts/anti-abuse).

## Minimal production `.env`

```dotenv
INVITE_SIGNING_KEY=base64:...        # dedicated, rotatable
INVITE_PII_SALT=base64:...           # dedicated, rotatable
INVITE_PII_RETENTION_DAYS=90
INVITE_STORE_NETWORK_FIELDS=false    # only enable when abuse review needs it
INVITE_REQUIRED=false                # true for a closed beta
```

::: callout tip
`signing_key` and `pii.hash_salt` both fall back to `APP_KEY`‑derived material so dev never emits an
unsigned code or an unsalted hash — but production should set dedicated secrets so rotating `APP_KEY`
does not orphan signed codes or PII hashes.
:::
