<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Padosoft\Invitations\Contracts\TenantResolver;
use Padosoft\Invitations\Models\Invitation;
use Padosoft\Invitations\Models\InviteCode;
use Padosoft\Invitations\Models\Redemption;
use Padosoft\Invitations\Models\Referral;

/**
 * Funnel + virality metrics (docs/11-analytics.md), computed from the CANONICAL
 * tables (Redemption / Referral / Invitation / InviteCode) — the source of
 * truth — so the numbers reconcile with the raw rows rather than drifting from
 * a separate rollup. The analytics event log is the audit trail; this service
 * is the read model.
 *
 * Definitions:
 *   - k_factor       = qualified referrals / distinct referrers (viral coeff.)
 *   - acceptance     = accepted invitations / sent invitations
 *   - conversion     = redemptions / codes issued
 *   - ttr_p50/p90    = time from code creation to redemption (seconds)
 */
final class MetricsService
{
    public function __construct(private readonly TenantResolver $tenant) {}

    /**
     * @return array<string, int|float|null>
     */
    public function summary(?int $campaignId = null, ?int $sinceDays = null): array
    {
        $tenantId = $this->tenant->current();
        $since = $sinceDays !== null ? Carbon::now()->subDays($sinceDays) : null;

        $codesIssued = InviteCode::query()
            ->forTenant($tenantId)
            ->when($campaignId !== null, fn ($q) => $q->where('campaign_id', $campaignId))
            ->when($since !== null, fn ($q) => $q->where('created_at', '>=', $since))
            ->count();

        $redemptions = Redemption::query()
            ->forTenant($tenantId)
            ->when($campaignId !== null, fn ($q) => $q->whereIn('code_id', $this->campaignCodeIds($tenantId, $campaignId)))
            ->when($since !== null, fn ($q) => $q->where('redeemed_at', '>=', $since))
            ->count();

        $invitesSent = Invitation::query()
            ->forTenant($tenantId)
            ->whereNotNull('sent_at')
            ->when($since !== null, fn ($q) => $q->where('sent_at', '>=', $since))
            ->count();

        $invitesAccepted = Invitation::query()
            ->forTenant($tenantId)
            ->where('status', Invitation::STATUS_ACCEPTED)
            ->when($since !== null, fn ($q) => $q->where('accepted_at', '>=', $since))
            ->count();

        $qualified = Referral::query()
            ->forTenant($tenantId)
            ->whereIn('status', [Referral::STATUS_QUALIFIED, Referral::STATUS_REWARDED])
            ->when($campaignId !== null, fn ($q) => $q->where('campaign_id', $campaignId))
            ->count();

        $distinctReferrers = Referral::query()
            ->forTenant($tenantId)
            ->when($campaignId !== null, fn ($q) => $q->where('campaign_id', $campaignId))
            ->distinct()
            ->count('referrer_id');

        return [
            'codes_issued' => $codesIssued,
            'redemptions' => $redemptions,
            'invites_sent' => $invitesSent,
            'invites_accepted' => $invitesAccepted,
            'referrals_qualified' => $qualified,
            'distinct_referrers' => $distinctReferrers,
            'k_factor' => $distinctReferrers > 0 ? round($qualified / $distinctReferrers, 4) : 0.0,
            'acceptance_rate' => $invitesSent > 0 ? round($invitesAccepted / $invitesSent, 4) : 0.0,
            'conversion_rate' => $codesIssued > 0 ? round($redemptions / $codesIssued, 4) : 0.0,
            'ttr_p50_seconds' => $this->ttrPercentile($tenantId, $campaignId, 0.50, $since),
            'ttr_p90_seconds' => $this->ttrPercentile($tenantId, $campaignId, 0.90, $since),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function campaignCodeIds(string $tenantId, int $campaignId): array
    {
        return InviteCode::query()
            ->forTenant($tenantId)
            ->where('campaign_id', $campaignId)
            ->pluck('id')
            ->all();
    }

    /**
     * Time-to-redeem percentile, in seconds — computed and ordered IN SQL so a
     * single row is fetched at the percentile offset (R3: never loads the whole
     * redemption set into memory). Driver-aware epoch diff.
     */
    private function ttrPercentile(string $tenantId, ?int $campaignId, float $percentile, ?Carbon $since = null): ?int
    {
        $diff = $this->secondsDiffExpression();

        $base = Redemption::query()
            ->forTenant($tenantId)
            // Constrain the JOINED table to the same tenant too — defence-in-depth
            // against cross-tenant leakage if a row is ever corrupted (R30).
            ->join('invite_codes', 'invite_codes.id', '=', 'invite_redemptions.code_id')
            ->where('invite_codes.tenant_id', $tenantId)
            ->when($campaignId !== null, fn ($q) => $q->where('invite_codes.campaign_id', $campaignId))
            // Scope TTR to the same lookback window as the other summary metrics.
            ->when($since !== null, fn ($q) => $q->where('invite_redemptions.redeemed_at', '>=', $since))
            ->whereNotNull('invite_codes.created_at');

        $count = (clone $base)->count();
        if ($count === 0) {
            return null;
        }

        $offset = (int) floor($percentile * ($count - 1));

        $row = (clone $base)
            ->selectRaw("$diff as d")
            ->orderByRaw($diff)
            ->offset($offset)
            ->limit(1)
            ->first();

        if ($row === null) {
            return null;
        }

        return max(0, (int) round((float) $row->getAttribute('d')));
    }

    /**
     * Portable "seconds between invite_codes.created_at and
     * invite_redemptions.redeemed_at" SQL expression.
     */
    private function secondsDiffExpression(): string
    {
        $sqlite = '(julianday(invite_redemptions.redeemed_at) - julianday(invite_codes.created_at)) * 86400';

        return match (DB::connection()->getDriverName()) {
            'pgsql' => 'EXTRACT(EPOCH FROM (invite_redemptions.redeemed_at - invite_codes.created_at))',
            'mysql', 'mariadb' => 'TIMESTAMPDIFF(SECOND, invite_codes.created_at, invite_redemptions.redeemed_at)',
            default => $sqlite,
        };
    }
}
