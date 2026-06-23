# Claude Design brief — `laravel-invitations` admin panel

**Audience:** Claude Design (or a designer) producing a **React + Tailwind CSS + Vite** admin template.
**Output expected:** a themeable JSX + CSS prototype (component-level, not backend-wired) covering every screen, drawer, modal, and state below — the same kind of high-fidelity prototype delivered for `laravel-ai-act-compliance-admin-web-panel`. This template feeds **two** consumers: (1) the standalone `padosoft/laravel-invitations-admin` SPA package, and (2) AskMyDocs's own React SPA (which will adapt, not copy, the components). Design for reuse and theming.

> This is a **design** brief. Do not implement API calls, routing, or auth. Mock all data with the shapes in §10. Focus on layout, component composition, interaction states, responsiveness, accessibility, and a polished enterprise look.

---

## 1. Product in one paragraph

"**Invitations & Growth**" is the admin console for an enterprise invite-by-code + referral + waitlist + rewards system. An operator uses it to: create **campaigns**, generate/revoke **invite codes**, send & track **email invitations** (who accepted vs. who didn't), inspect the **referral graph**, manage a **reward ledger**, run a **waitlist** with refer-to-jump-the-queue virality, review **anti-abuse** signals, and read a **virality analytics** dashboard (K-factor, funnel, conversion). The product is **multi-tenant**: every screen is implicitly scoped to the current tenant; the top bar shows which tenant is active.

## 2. Look & feel

- **Tone:** modern SaaS console — think Linear / Vercel dashboard / Stripe. Confident, dense-but-breathable, data-first. Not a Bootstrap CRUD scaffold.
- **Theme:** light **and** dark, driven by a `data-theme` attribute and CSS custom properties. Define a token set (`--color-bg`, `--color-surface`, `--color-border`, `--color-text`, `--color-text-muted`, `--color-primary`, `--color-success`, `--color-warning`, `--color-danger`, plus state-badge colors). **No raw hex in components** — only tokens.
- **Type scale & spacing:** Tailwind defaults are fine; establish a clear hierarchy (page title / section / table). Generous table row height, sticky table headers, zebra optional.
- **Density:** tables are the primary surface — make them excellent (sticky header, column alignment, sortable headers, pagination footer, per-page selector, empty/loading skeleton rows).
- **Motion:** subtle — drawer slide-in, toast fade, skeleton shimmer. Nothing flashy.

## 3. Global shell

- **Left nav** (collapsible): the 9 sections in §6, each with an icon + label. Active state. A small "Invitations & Growth" product mark at top.
- **Top bar:** breadcrumb (Section ▸ subview); a **tenant indicator** (read-only label — tenant switching is the host app's job, but show which tenant the data belongs to); a global **date-range picker** (Last 7 / 30 / 90 days / custom) that drives the analytics + any time-filtered table; a theme toggle; a help/`?`.
- **Toast region** (top-right): success / error toasts for every mutation (R: async actions must give visual feedback).
- **Responsive:** ≥1280px = full two-pane; 768–1279px = collapsible nav becomes icon-rail; <768px = nav drawer, tables become horizontally scrollable cards. The grant editor (§6.2) must remain usable on tablet.

## 4. Universal state contract (every async surface)

Each data region renders one of: **idle / loading / ready / error / empty**, exposed as `data-state="…"` + `aria-busy` on the container. Provide a visual for each:
- **loading** — skeleton rows / shimmer KPI cards (never a bare spinner on a full page).
- **empty** — friendly illustration + one-line explanation + primary CTA (e.g. "No campaigns yet — create your first").
- **error** — inline error card with a retry button; the message text is rendered in the DOM (errors must be visible, not swallowed).
- **ready** — the data.

## 5. Selector & a11y contract (bake into the markup)

This template will be driven by Playwright + Vitest, so the markup must carry stable hooks:
- **Test IDs:** hierarchy `feature-resource-{id}-{action}` — e.g. `invite-campaign-row-12-edit`, `invite-code-row-42-revoke`, `invite-code-row-42-revoke-confirm`, `invite-reward-row-7-confirm`, `invite-waitlist-invite-batch`. Trigger buttons follow `feature-action` — `invite-campaign-create`, `invite-codes-generate`, `invite-filter-bar-apply`.
- **A11y:** every `<input>/<select>/<textarea>` has a real `<label htmlFor>` or `aria-label` (placeholder is NOT a label). Icon-only buttons get `aria-label`. `role`/`aria-expanded`/`aria-selected` live on the focusable element (the `<button>`), not a wrapper `<li>`/`<div>`. Visually-hidden-but-real inputs use the CSS visually-hidden pattern, never `display:none`. Tooltips respond to focus + blur, not only hover. Keyboard: full tab order, visible focus ring, Esc closes drawers/modals, focus trap in modals.
- Status badges never rely on color alone — pair color with text/icon.

## 6. Screens

Left nav order = funnel order: **Overview → Campaigns → Codes → Invitations → Referrals → Rewards → Waitlist → Anti-abuse → Settings.**

### 6.1 Overview / Analytics (landing)
- **KPI card row:** K-factor (viral coefficient), Acceptance rate, Conversion rate, Codes issued, Redemptions, Time-to-redeem p50 / p90. Each card: big number, label, small delta vs. previous period, sparkline.
- **Acquisition funnel** — horizontal funnel: Invites sent → Accepted → Activated → Rewarded, with counts + drop-off %.
- **Time-series chart** — redemptions / invites over the selected date range (line or area). Guard the empty array (no `Math.max(...[])` → `-Infinity` broken SVG).
- **Top-referrer leaderboard** — table: referrer, qualified referrals, rewards earned.
- **Filters:** campaign dropdown (derive from real campaigns — never a literal list), date range (from top bar).
- Data: `InviteMetrics` (§10).

### 6.2 Campaigns
- **Table:** Key · Name · Type (badge) · Status (badge) · Redemptions (`used / limit`) · Window (starts→ends) · actions (Edit, View codes).
- **Type** values: `single_use | multi_use | capacity | referral | waitlist_skip` (distinct badge colors). **Status:** `draft | active | paused | ended`.
- **Create / Edit drawer** (right slide-over) — the richest form in the app:
  - Basics: key (slug, immutable on edit), name, description, type, status, schedule (starts_at / ends_at), max redemptions total, per-user limit.
  - **Reward policy** (when type=referral): a small editor for double-sided reward (referrer reward + referee reward) + optional milestone tiers.
  - **Grant editor** (the hard part — design it carefully):
    - **Primary grant** (applies on the redemption tenant): role picker (single select, options from real roles, **`super-admin` excluded**), project multiselect (options from real projects), project-role (`member | admin | owner`), optional scope allowlist (folder globs / tags chips).
    - **Additional tenant grants** (repeatable rows): each row = tenant picker + its own role + projects + project-role + scope allowlist. "Add tenant grant" button; each row removable. This lets one code seed access across several tenants.
    - Make this readable: a "primary" card + a list of "extra tenant" cards, not one giant flat form.
  - Validation inline per field with `data-testid="{field}-error"`.

### 6.3 Codes
- **Table:** Code (monospace, copy button) · Kind badge (`random | vanity | signed`) · State badge (`active | redeemed | exhausted | expired | revoked`) · Uses (`current / max`, with a tiny progress bar) · Expiry · Campaign · actions (Copy, Revoke).
- **Filters:** campaign, state.
- **Generate drawer:** campaign (optional — standalone codes allowed), count (1–N), max uses per code, code length, expiry. On submit show the generated codes with a "copy all / export CSV" action.
- **Revoke** = confirm modal (`invite-code-row-{id}-revoke` → `…-revoke-confirm`). Destructive styling.

### 6.4 Invitations  ← "who accepted vs. who didn't" is the headline
- **Tabs / segmented filter:** All · Sent · Accepted · Pending · Expired (counts per tab).
- **Table:** Recipient (email, **masked** e.g. `j•••@acme.com`) · Channel (email/…) · Status badge · Sent at · Accepted at · actions (Resend, Revoke).
- **Send invitation drawer:** recipient(s), channel, role (optional), context ref. Support a small bulk paste of addresses.
- Make the accepted-vs-not contrast obvious (e.g. a mini stacked bar at the top: X accepted / Y pending / Z expired).

### 6.5 Referrals
- **Referral graph** — who referred whom. A simple, legible node-link or an indented tree/table is fine (don't over-engineer a force-graph; a clean table with referrer → referee + status is acceptable and more usable). Status badge: `pending | qualified | rewarded`.
- **Attribution detail drawer:** when a referral row is opened — show the code used, timestamps, qualifying event, reward link.

### 6.6 Rewards ledger
- **Table:** Reward · Beneficiary (referrer/referee) · Amount/type · **State** badge (`pending → held → confirmed → paid → reversed`) · Created · actions (Confirm, Reverse).
- **State machine** visualized — a small horizontal stepper in the row-detail drawer showing where this reward is.
- **Per-referrer rollup** card/section: total earned, pending, paid.
- Manual **Confirm** / **Reverse** = confirm modals; reversal needs a reason field.

### 6.7 Waitlist
- **Table:** Position (queue rank) · Email (masked) · Status (`pending | confirmed | unsubscribed`) · Joined · Referrals (count — drives queue-jump) · actions.
- **"Invite N from top"** batch action (number input + button) — pulls the top N off the waitlist into invitations.
- Double-opt-in status indicator per row.

### 6.8 Anti-abuse review
- **Signal feed table:** Subject (type + masked value) · Signal type (velocity / disposable_email / honeypot / blacklist / …) · Severity badge (`warn | block`) · Score · Action taken (`none | flag | throttle | block`) · When.
- **Review queue:** flagged subjects with Allow / Block actions.
- **Blocklist / Allowlist editors:** simple chip/list editors for IPs (hashed), emails, domains, accounts.
- Tone: this is a security surface — calm, precise, no alarming red everywhere; reserve danger color for actual blocks.

### 6.9 Settings
- Read-mostly visualization of config knobs: anti-abuse thresholds (flag/throttle/block scores), velocity windows, PII retention days, default code length, invitation TTL. Present as labeled cards/rows; editing can be out-of-scope for v1 (show values + descriptions).

## 7. Components to define (reusable kit)

DataTable (sortable, paginated, sticky header, skeleton, empty), KpiCard (with sparkline + delta), StatBadge (variant per state), FunnelChart, TimeSeriesChart, Leaderboard, SlideOverDrawer, ConfirmModal, Toast, FilterBar (campaign + state + date), GrantEditor (primary + multi-tenant), ChipsInput (scope allowlist / blocklists), MaskedEmail, CopyButton, StateStepper (reward ledger), SegmentedTabs (invitations). Theme tokens file.

## 8. States to show in the prototype (per screen)
For Overview, Campaigns, Codes, Invitations at minimum, render **all** of: loading (skeleton), empty, error, ready, plus the create/edit drawer and the confirm modal. The grant editor must be shown fully expanded with 1 primary + 2 extra-tenant grants.

## 9. Out of scope
Backend, auth, routing, real charts library choice (mock with simple SVG or a lightweight lib — note your choice), i18n. Keep copy in English; the host renders localized strings later.

## 10. Authoritative data shapes (mock these — keep field names exact, R9)

```ts
type CampaignType = 'single_use' | 'multi_use' | 'capacity' | 'referral' | 'waitlist_skip';
type CampaignStatus = 'draft' | 'active' | 'paused' | 'ended';
type CodeState = 'active' | 'redeemed' | 'exhausted' | 'expired' | 'revoked';
type ProjectRole = 'member' | 'admin' | 'owner';

interface TenantGrant { tenant_id: string; role: string | null; projects: string[]; project_role: ProjectRole; scope_allowlist?: Record<string, unknown> | null; }
interface InviteGrant { role: string | null; projects: string[]; project_role: ProjectRole; scope_allowlist?: Record<string, unknown> | null; tenants?: TenantGrant[]; }

interface InviteCampaign { id: number; key: string; name: string; description: string | null; type: CampaignType; status: CampaignStatus; max_redemptions_total: number | null; per_user_limit: number; starts_at: string | null; ends_at: string | null; reward_policy: Record<string, unknown> | null; grant: InviteGrant | null; created_by: number; created_at?: string; updated_at?: string; }

interface InviteCode { id: number; campaign_id: number | null; code: string; code_kind: 'random' | 'vanity' | 'signed'; state: CodeState; max_uses: number; current_uses: number; issuer_id: number | null; expires_at: string | null; grant: InviteGrant | null; created_at?: string; }

interface InviteMetrics { codes_issued: number; redemptions: number; invites_sent: number; invites_accepted: number; referrals_qualified: number; distinct_referrers: number; k_factor: number; acceptance_rate: number; conversion_rate: number; ttr_p50_seconds: number | null; ttr_p90_seconds: number | null; }

// New suite screens (define analogous shapes):
interface Invitation { id: number; recipient: string; channel: string; status: 'pending'|'sent'|'accepted'|'expired'|'revoked'; sent_at: string|null; accepted_at: string|null; role: string|null; }
interface Referral { id: number; referrer_id: number; referee_id: number; campaign_id: number|null; status: 'pending'|'qualified'|'rewarded'; created_at: string; }
interface Reward { id: number; beneficiary_id: number; kind: string; amount: number|string; state: 'pending'|'held'|'confirmed'|'paid'|'reversed'; created_at: string; }
interface WaitlistEntry { id: number; email: string; position: number; status: 'pending'|'confirmed'|'unsubscribed'; referrals_count: number; created_at: string; }
interface AbuseSignal { id: number; subject_type: 'ip'|'email'|'account'|'fingerprint'; subject_value: string; signal_type: string; severity: 'warn'|'block'; score: number; action_taken: 'none'|'flag'|'throttle'|'block'; created_at: string; }
```

## 11. Deliverable checklist
- [ ] Theme tokens (light + dark) + a `data-theme` toggle.
- [ ] Global shell (nav + top bar + toast region) responsive at the 3 breakpoints.
- [ ] All 9 screens, each with loading / empty / error / ready.
- [ ] Campaign create/edit drawer with the full **GrantEditor** (primary + 2 extra-tenant grants shown).
- [ ] Codes generate drawer + revoke confirm modal.
- [ ] Invitations tabs + masked recipients + accepted-vs-not bar + send drawer.
- [ ] Rewards ledger with the state stepper.
- [ ] Reusable component kit (§7) extracted and themeable.
- [ ] Test-id + a11y contract (§5) applied throughout.
- [ ] A short README in the prototype noting chart lib choice + how to theme.

---

*When the roadmap reaches Phase 3, this prototype is dropped into `padosoft/laravel-invitations-admin` (as the package SPA) and adapted into AskMyDocs's existing SPA. Build it once, well.*
