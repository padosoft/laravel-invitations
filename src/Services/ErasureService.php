<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Services;

use Illuminate\Support\Carbon;
use Padosoft\Invitations\Contracts\TenantResolver;
use Padosoft\Invitations\Models\AbuseSignal;
use Padosoft\Invitations\Models\Invitation;
use Padosoft\Invitations\Models\Redemption;
use Padosoft\Invitations\Models\Referral;
use Padosoft\Invitations\Models\Reward;
use Padosoft\Invitations\Support\PiiHasher;

/**
 * GDPR retention + erasure (docs/15-security-privacy.md, Phase 6).
 *
 * The cardinal rule: anonymization NEVER deletes the rows the aggregates need.
 * Redemptions, referrals, rewards and analytics events stay; only their PII
 * COLUMNS are overwritten in place. So after a retention sweep or an erasure
 * request, `current_uses`, funnel counts, and K-factor are unchanged while the
 * subject's ip / fingerprint / recipient are gone.
 *
 * All operations are tenant-scoped (R30) and memory-safe (chunkById, R3).
 */
final class ErasureService
{
    private const ERASED = 'erased';

    public function __construct(
        private readonly TenantResolver $tenant,
        private readonly PiiHasher $hasher,
    ) {}

    /**
     * Anonymize PII older than the retention window, in place. Returns a count
     * summary. `$dryRun` reports what WOULD be anonymized without writing.
     *
     * @return array<string, int>
     */
    public function sweepRetention(int $days, bool $dryRun = false, ?string $tenantId = null): array
    {
        $tenantId ??= $this->tenant->current();
        $cutoff = Carbon::now()->subDays($days);

        $redemptionQuery = Redemption::query()
            ->forTenant($tenantId)
            ->where('redeemed_at', '<', $cutoff)
            ->where(fn ($q) => $q->whereNotNull('ip')->orWhereNotNull('user_agent')->orWhereNotNull('fingerprint'));

        $signalQuery = AbuseSignal::query()
            ->forTenant($tenantId)
            ->where('created_at', '<', $cutoff)
            ->whereIn('subject_type', [AbuseSignal::SUBJECT_IP, AbuseSignal::SUBJECT_EMAIL, AbuseSignal::SUBJECT_FINGERPRINT])
            ->where('subject_value', '!=', self::ERASED);

        $invitationQuery = Invitation::query()
            ->forTenant($tenantId)
            ->whereIn('status', [Invitation::STATUS_ACCEPTED, Invitation::STATUS_EXPIRED, Invitation::STATUS_CANCELLED, Invitation::STATUS_BOUNCED])
            ->where('updated_at', '<', $cutoff)
            ->where('recipient', '!=', self::ERASED);

        if ($dryRun) {
            return [
                'redemptions' => $redemptionQuery->count(),
                'abuse_signals' => $signalQuery->count(),
                'invitations' => $invitationQuery->count(),
            ];
        }

        $summary = ['redemptions' => 0, 'abuse_signals' => 0, 'invitations' => 0];

        $redemptionQuery->chunkById(500, function ($rows) use (&$summary): void {
            foreach ($rows as $row) {
                $row->update(['ip' => null, 'user_agent' => null, 'fingerprint' => null]);
                $summary['redemptions']++;
            }
        });

        $signalQuery->chunkById(500, function ($rows) use (&$summary): void {
            foreach ($rows as $row) {
                $row->update(['subject_value' => self::ERASED]);
                $summary['abuse_signals']++;
            }
        });

        $invitationQuery->chunkById(500, function ($rows) use (&$summary): void {
            foreach ($rows as $row) {
                $row->update(['recipient' => self::ERASED, 'token' => null]);
                $summary['invitations']++;
            }
        });

        return $summary;
    }

    /**
     * Erase one subject's PII across every invite table — a GDPR right-to-be-
     * forgotten request — WITHOUT corrupting aggregates. The redemption rows,
     * current_uses, referrals, rewards and analytics counts are untouched;
     * only ip / fingerprint / recipient / matched subject_value are nulled.
     *
     * @return array<string, int>
     */
    /**
     * GDPR right-of-access: every invite-system record held about an account
     * (its redemptions, the referrals it made + received, its rewards, and —
     * when an email is supplied — the invitations addressed to it). Tenant-
     * scoped; bounded to one subject's own activity.
     *
     * @return array{account_id: int, redemptions: list<array<string, mixed>>, referrals_made: list<array<string, mixed>>, referrals_received: list<array<string, mixed>>, rewards: list<array<string, mixed>>, invitations: list<array<string, mixed>>}
     */
    public function exportAccount(int $accountId, ?string $email = null): array
    {
        $tenantId = $this->tenant->current();
        $normalized = $email !== null ? strtolower(trim($email)) : null;

        return [
            'account_id' => $accountId,
            'redemptions' => Redemption::query()->forTenant($tenantId)
                ->where('redeemer_id', $accountId)->get()->toArray(),
            'referrals_made' => Referral::query()->forTenant($tenantId)
                ->where('referrer_id', $accountId)->get()->toArray(),
            'referrals_received' => Referral::query()->forTenant($tenantId)
                ->where('referee_id', $accountId)->get()->toArray(),
            'rewards' => Reward::query()->forTenant($tenantId)
                ->where('beneficiary_id', $accountId)->get()->toArray(),
            'invitations' => $normalized === null ? [] : Invitation::query()->forTenant($tenantId)
                ->where('recipient', $normalized)->get()->toArray(),
        ];
    }

    public function eraseAccount(int $accountId, ?string $email = null): array
    {
        $tenantId = $this->tenant->current();
        $summary = ['redemptions' => 0, 'invitations' => 0, 'abuse_signals' => 0];

        $summary['redemptions'] = Redemption::query()
            ->forTenant($tenantId)
            ->where('redeemer_id', $accountId)
            ->update(['ip' => null, 'user_agent' => null, 'fingerprint' => null]);

        // Account-subject abuse signals.
        $summary['abuse_signals'] += AbuseSignal::query()
            ->forTenant($tenantId)
            ->where('subject_type', AbuseSignal::SUBJECT_ACCOUNT)
            ->where('subject_value', (string) $accountId)
            ->update(['subject_value' => self::ERASED]);

        if ($email !== null) {
            $normalized = strtolower(trim($email));

            $summary['invitations'] = Invitation::query()
                ->forTenant($tenantId)
                ->where('recipient', $normalized)
                ->update(['recipient' => self::ERASED, 'token' => null]);

            // Email/fingerprint abuse signals are stored as a salted hash of
            // the canonical email — erase the matching subject.
            $emailHash = $this->hasher->hash($this->hasher->canonicalizeEmail($normalized));
            $summary['abuse_signals'] += AbuseSignal::query()
                ->forTenant($tenantId)
                ->where('subject_type', AbuseSignal::SUBJECT_EMAIL)
                ->where('subject_value', $emailHash)
                ->update(['subject_value' => self::ERASED]);
        }

        return $summary;
    }
}
