<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Services;

use DateTimeInterface;
use Padosoft\Invitations\Models\InviteCode;
use Padosoft\Invitations\Support\RedemptionError;
use Padosoft\Invitations\Support\ValidationResult;

/**
 * Pure, advisory, NON-MUTATING code validation (docs/07-redemption-flow.md).
 *
 * Reads only. The authoritative seat check is the atomic claim in
 * RedemptionService — this validator is the fast-fail front door (unknown /
 * expired / revoked / campaign-closed) and the place a redeem UI can call to
 * show a friendly message before the user commits. `now` is injectable so the
 * expiry branches are deterministic under test.
 */
final class CodeValidator
{
    public function __construct(private readonly CodeNormalizer $normalizer) {}

    public function validate(string $rawCode, string $tenantId, ?DateTimeInterface $now = null): ValidationResult
    {
        $normalized = $this->normalizer->normalize($rawCode);

        if ($normalized === '') {
            return ValidationResult::invalid(RedemptionError::Invalid);
        }

        $code = InviteCode::query()
            ->forTenant($tenantId)
            ->where('code', $normalized)
            ->first();

        if ($code === null) {
            return ValidationResult::invalid(RedemptionError::Invalid);
        }

        $now ??= now();

        if ($code->state === InviteCode::STATE_REVOKED) {
            return ValidationResult::invalid(RedemptionError::Revoked, $code);
        }

        if ($code->state === InviteCode::STATE_EXPIRED || $code->isExpired($now)) {
            return ValidationResult::invalid(RedemptionError::Expired, $code);
        }

        if ($code->state === InviteCode::STATE_EXHAUSTED || ! $code->hasFreeSeat()) {
            return ValidationResult::invalid(RedemptionError::Exhausted, $code);
        }

        // Campaign gate — draft / paused / ended / out-of-window codes are
        // ineligible (the matching campaign error per Phase 2 DoD).
        if ($code->campaign_id !== null) {
            $campaign = $code->campaign;

            if ($campaign !== null && ! $campaign->isOpen($now)) {
                return ValidationResult::invalid(RedemptionError::Ineligible, $code);
            }
        }

        return ValidationResult::valid($code);
    }
}
