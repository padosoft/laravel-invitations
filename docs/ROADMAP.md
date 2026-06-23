# Roadmap — `padosoft/laravel-invitations` (enterprise) + `-admin` + AskMyDocs integration

Master plan (approved 2026-06-23). Mirrors `~/.claude/plans/jiggly-waddling-narwhal.md`.

## Why
PR #355 on AskMyDocs (`lopadova/AskMyDocs`, branch `feature/v8.18-invite-system-clear`, by Marco Bramato — `+10,333/−21`, 114 files) shipped an enterprise-grade invite/referral subsystem hardcoded into the app. It is high quality (lock-free atomic idempotent redemption, fail-open generic fraud gate, HMAC'd PII, GDPR-preserving erasure, ~71 tests) and ~80% domain-agnostic. The community has no package with multi-tenant scoping + concurrency-safe redemption + fraud + K-factor analytics + per-invite grants + MCP/API. **Decision:** park PR #355 unmerged, extract its engine into this standalone package, close the remaining enterprise gaps, ship a separate React `-admin` package, then have AskMyDocs adopt the package at v1.0.0.

## Locked decisions
- Standalone vendor-neutral package; deps on `laravel/fortify` + `spatie/laravel-permission` are acceptable.
- Full acquisition suite: invite codes + email invitations + referral graph + double-sided/tiered rewards (ledger) + waitlist (queue-jump) + anti-abuse + funnel/K-factor analytics + per-invite entitlement grants.
- Names: `padosoft/laravel-invitations` + `padosoft/laravel-invitations-admin`.
- Headless core owns the read API; separate React+Tailwind+Vite `-admin` package (in this roadmap); AskMyDocs builds **native** screens over the core API.
- Rebel bridge = separate plan (`REBEL-BRIDGE-PLAN.md`), run later in a new session.

## Isolation
Pure Laravel packages live in the Laravel projects root **`C:\xampp\htdocs`** (NOT `Ai/`): `C:\xampp\htdocs\laravel-invitations` + `C:\xampp\htdocs\laravel-invitations-admin` (both already cloned from the existing GitHub repos). The live `Ai/AskMyDocs` folder is busy with another session — AskMyDocs adoption (Phase 4) happens in a **separate clone** (e.g. `C:\xampp\htdocs\AskMyDocs-invite-integration`), never the live folder. Read-only `git show`/`gh` against AskMyDocs is fine.

## Phase 0a — IMMEDIATE scaffold + push (unblocks Packagist registration)  ← do first
Both repos **already exist on GitHub** (`padosoft/laravel-invitations`, `padosoft/laravel-invitations-admin`, public, MIT) and are cloned into `htdocs`. Do NOT `gh repo create`. Scaffold a **minimal but valid composer package** in each and **push to `main` right away** so Lorenzo can register both on **Packagist in parallel** — so that by Phase 3/4 `composer require` resolves.
- Core: `composer.json` (`padosoft/laravel-invitations`, `php ^8.3`, `illuminate/* ^12|^13`, `spatie/laravel-package-tools`, autoload `Padosoft\\Invitations\\` → `src/`), `InvitationsServiceProvider` (package-tools `configurePackage`), `config/invitations.php` stub, auto-discovery `extra.laravel.providers`, README stub.
- Admin: `composer.json` (`padosoft/laravel-invitations-admin`, requires `padosoft/laravel-invitations`), `InvitationsAdminServiceProvider` stub, README stub.
- Push `main`. **Hand-off point: tell Lorenzo "scaffold pushed → register both on Packagist now."**

## Phase 0b — Full skeleton
Finish the skeleton via `spatie/laravel-package-tools`: publish tags, migrations dir, routes file. CI matrix PHP 8.3/8.4/8.5 × Laravel 12/13; Pint + PHPStan max + PHPUnit; `composer check`. Pin `config.platform.php=8.3.0`. Ship `.claude/` vibe-coding pack + `AGENTS.md`. Opt-in `tests/Live/` for any external call.

## Phase 1 — Port the engine (seed from PR #355)
Lift `app/Services/Invite/*`, `app/Models/*`, migrations, `app/Mail/*`, `app/Console/*`; rename `App\…\Invite` → `Padosoft\Invitations\…`. Abstract 3 seams:
- **`TenantResolver`** interface (default single-tenant `'default'`; host binds its own). Keep `tenant_id` + `forTenant()` scoping.
- **`Provisioner`** interface replacing direct Spatie/`ProjectMembership` writes. Default `SpatiePermissionProvisioner` (role grant); project-membership grants are a host-supplied provisioner. GRANT-never-REVOKE + best-effort preserved.
- **Account contract** — configurable `invitations.user_model`, typed via `Authenticatable` + an `InvitedAccount` interface (`email`, `guard_name`).
Migrations publishable; keep SQLite-vs-pgsql CHECK split. Port all ~71 tests to Testbench.

## Phase 2 — Close the gaps (be #1)
- Reward **ledger state machine** `pending→held→confirmed→paid→reversed` + hold-then-confirm + reversal (auditable).
- **Tiered/milestone** rewards + per-referrer cap.
- **Waitlist queue-jump** virality + "invite N from top" + double opt-in + consent log (taldres pattern).
- **Signed-code hardening** — finish HMAC path + key rotation + offline verify.
- **Fraud depth** — device-fingerprint + shared-payment hooks (pluggable) + manual-review queue.
- Fix `MetricsService::ttrPercentile` (loads all rows + PHP sort → R3) → SQL percentile / bounded sample.
- **Events** on every lifecycle transition (community's #1 gap).
- **Immutable audit trail** table (survives hard delete).
- GDPR: keep anonymize-preserving-aggregates + `invite:prune-pii`; add typed data-access export DTOs.

## Phase 3 — Surfaces + `-admin` + docs
- **Tri-surface (R44):** PHP services/facade + Artisan; publishable RBAC-gated HTTP API (+ R32 matrix test in-package); optional **MCP tool** classes (consumers register them). Metrics/events/who-accepted read API lives in core (headless).
- **`padosoft/laravel-invitations-admin`** — requires core; React+Tailwind+Vite SPA from the Claude Design template (`ADMIN-DESIGN-BRIEF.md`); prebuilt assets published; gated Blade mount, default-OFF flag (R43 both states → clean 404 when off). Mirrors `laravel-ai-finops-admin` / `flow-admin`.
- Tag **v1.0.0** on both + Packagist release (full features before AskMyDocs integration).

### Phase 3z — Package docs FINAL (WOW README + doc-site)  ← final task of package dev
- **WOW community README** on BOTH repos — the full 14-section treatment matching other `padosoft/*` packages (`feedback_open_source_readme_quality`): badges, theory, **comparison table vs doorman / mateusjunges-laravel-invite-codes / pdazcom-referrals / taldres-waitlist**, quick-start, full feature tour, architecture (Mermaid) diagram, examples, the `🚀 AI vibe-coding pack included` section (`feedback_package_readme_must_highlight_vibe_coding_pack`), Live-testsuite how-to.
- **High-level doc-site** for the package(s) (deep standalone pages — motivation → theory → design+Mermaid → data model → ADR rationale → worked example → gotchas), same depth bar as the AskMyDocs `/docs-site/` Mintlify pages (`mintlify-doc-authoring` / R45). Decide host (package `docs/` site or a Mintlify group) at this step.

## Phase 4 — AskMyDocs adoption (separate clone; R37/R39/R46)
Branch `feature/v8.2x-invitations` (R37). `composer require padosoft/laravel-invitations`; bind `TenantContext`→`TenantResolver`; add a `ProjectMembership` provisioner; register MCP tools on `KnowledgeBaseServer` (+ registration-count test). **Delete** in-app `app/Services/Invite/*` (now in the package). R43 both-states for the `/api/auth/register` invite gate + conscious default. Native React screens (port `InviteView.tsx`/forms) over the core API + Playwright real-data E2E (R12/R13) + R32 matrix rows. **Close PR #355 unmerged** crediting the work. R40 pre-flight + R36 cloud loop + R46 deferred-E2E; merge on green + 0 must-fix; rc tag per R39.

### Phase 4z — AskMyDocs docs FINAL (README + doc-site)  ← final task after integration
- **README.md done right** (the user's original point 1, which PR #355 botched): add the invite/referral suite to `### Key Features`; **flip/add the roadmap row** `⏳ planned → ✅ shipped YYYY-MM-DD` (`feedback_readme_roadmap_status_flip_on_ga`); state the exact **version** it ships in; add an above-the-fold killer-feature section (`feedback_readme_refresh_per_wave`).
- **`/docs-site/` deep Mintlify page** for the acquisition suite (R45 / `mintlify-doc-authoring`), registered in `docs.json`.
- CLAUDE.md + `.github/copilot-instructions.md` parity pass (R9): tenant-aware table list, MCP roster count, any new env knobs.

## Verification
`composer check` green on the matrix; ported + new PHPUnit green incl. 2-process concurrency test (R21) + R43 both-states per flag; PHPStan max clean. `-admin`: Vitest + Playwright; OFF-path clean 404. AskMyDocs: full suites + real-data E2E green; register proven both states; MCP count bumped; CI green + 0 Copilot must-fix before merge. Manual: generate→redeem (API/MCP/UI)→assert idempotent replay, exhaustion, additive grant, referral attribution, ledger transitions, metric reconciliation, PII hashing, prune anonymization with aggregates intact.

## Notes
- Obtain the portable **`Spec4LLM/InviteSystem`** handoff from Bramato (referenced in code as `docs/04-data-model.md`, `07-redemption-flow.md`, `10-anti-abuse.md`, `11-analytics.md`, `15-security-privacy.md`; not committed) — accelerates docs/README.
- Provisioning is the one genuinely host-specific seam — `ProjectMembership` stays host-supplied; only Spatie role-grant ships by default.
- Effort: moderate, not large (engine exists). Multi-day to v1.0.0, then a clean adoption PR — less total work than merge-then-rip.
