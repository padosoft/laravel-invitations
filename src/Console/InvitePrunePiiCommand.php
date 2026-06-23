<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Console;

use Illuminate\Console\Command;
use Padosoft\Invitations\Contracts\TenantResolver;
use Padosoft\Invitations\Services\ErasureService;

/**
 * Scheduled GDPR retention sweep for the invite subsystem (Phase 6).
 *
 * Anonymizes Redemption network fields, AbuseSignal PII subjects, and resolved
 * Invitation recipients older than INVITE_PII_RETENTION_DAYS — IN PLACE, so
 * the immutable row facts the aggregates depend on (current_uses, funnel
 * counts) survive. `0` disables the rotation (per the repo scheduler
 * convention).
 */
final class InvitePrunePiiCommand extends Command
{
    protected $signature = 'invite:prune-pii
                            {--days= : Override INVITE_PII_RETENTION_DAYS}
                            {--tenant= : tenant_id to sweep (default: current tenant)}
                            {--dry-run : Count rows without anonymizing}';

    protected $description = 'Anonymize invite-system PII (Redemption/AbuseSignal/Invitation) older than the retention window.';

    public function handle(TenantResolver $tenant, ErasureService $erasure): int
    {
        $daysOption = $this->option('days');
        $days = is_numeric($daysOption)
            ? (int) $daysOption
            : (int) config('invitations.pii.retention_days', 90);

        if ($days <= 0) {
            $this->warn('Invite PII retention is 0 or negative — skipping sweep.');

            return self::SUCCESS;
        }

        $tenantOption = $this->option('tenant');
        $tenantId = is_string($tenantOption) && $tenantOption !== ''
            ? $tenantOption
            : $tenant->current();

        $dryRun = (bool) $this->option('dry-run');
        $summary = $erasure->sweepRetention($days, $dryRun, $tenantId);

        $verb = $dryRun ? 'Would anonymize' : 'Anonymized';
        $this->info(sprintf(
            '%s: %d redemptions, %d abuse signals, %d invitations (retention %d days, tenant %s).',
            $verb,
            $summary['redemptions'],
            $summary['abuse_signals'],
            $summary['invitations'],
            $days,
            $tenantId,
        ));

        return self::SUCCESS;
    }
}
