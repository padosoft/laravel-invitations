<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Services;

use Padosoft\Invitations\Contracts\TenantResolver;
use Padosoft\Invitations\Models\WaitlistEntry;

/**
 * Waitlist with refer-to-jump-the-queue virality (the Dropbox/Robinhood
 * pattern). Tenant-scoped (R30). Join is idempotent per (tenant, email); a
 * referral bumps priority so the referrer climbs the queue; inviteFromTop()
 * converts the highest-priority waiting entries into real invite codes.
 */
final class WaitlistService
{
    public function __construct(
        private readonly TenantResolver $tenant,
        private readonly CodeGenerator $codes,
    ) {}

    /**
     * Join the waitlist. Idempotent — re-joining returns the existing entry and
     * never resets its position or priority.
     */
    public function join(string $email): WaitlistEntry
    {
        $tenantId = $this->tenant->current();
        $normalized = $this->normalize($email);

        $existing = WaitlistEntry::query()
            ->forTenant($tenantId)
            ->where('email', $normalized)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $nextPosition = (int) WaitlistEntry::query()->forTenant($tenantId)->max('position') + 1;

        return WaitlistEntry::query()->create([
            'tenant_id' => $tenantId,
            'email' => $normalized,
            'position' => $nextPosition,
            'status' => WaitlistEntry::STATUS_WAITING,
        ]);
    }

    /**
     * Record a successful referral by a waitlisted member — bumps both the
     * referral count and the priority used to order inviteFromTop(). Returns
     * null when the email is not on the waitlist.
     */
    public function recordReferral(string $email, int $by = 1): ?WaitlistEntry
    {
        $entry = WaitlistEntry::query()
            ->forTenant($this->tenant->current())
            ->where('email', $this->normalize($email))
            ->first();

        if ($entry === null) {
            return null;
        }

        $entry->update([
            'referral_count' => $entry->referral_count + $by,
            'priority' => $entry->priority + $by,
        ]);

        return $entry->refresh();
    }

    /**
     * Invite the top $count waiting entries (highest priority first, then
     * earliest position): mint a single-use code for each and flip the entry to
     * `invited`. Returns the invited entries.
     *
     * @return list<WaitlistEntry>
     */
    public function inviteFromTop(int $count): array
    {
        if ($count < 1) {
            return [];
        }

        $entries = WaitlistEntry::query()
            ->forTenant($this->tenant->current())
            ->where('status', WaitlistEntry::STATUS_WAITING)
            ->orderByDesc('priority')
            ->orderBy('position')
            ->limit($count)
            ->get();

        $invited = [];
        foreach ($entries as $entry) {
            $code = $this->codes->generateRandom(['max_uses' => 1]);

            $entry->update([
                'status' => WaitlistEntry::STATUS_INVITED,
                'granted_code_id' => $code->id,
                'invited_at' => now(),
            ]);

            $invited[] = $entry->refresh();
        }

        return $invited;
    }

    private function normalize(string $email): string
    {
        return strtolower(trim($email));
    }
}
