<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Padosoft\Invitations\Contracts\TenantResolver;
use Padosoft\Invitations\Models\AbuseSignal;
use Padosoft\Invitations\Models\Referral;
use Padosoft\Invitations\Models\Reward;
use Padosoft\Invitations\Models\WaitlistEntry;

/**
 * Admin read surface for the referral / reward / waitlist / anti-abuse domains
 * (R44 — HTTP layer over the canonical tables). Tenant-scoped (R30), bounded
 * (limit 500 — large exports belong to a future cursor/stream endpoint, R3).
 */
final class InviteReadController extends Controller
{
    private const LIMIT = 500;

    public function __construct(private readonly TenantResolver $tenant) {}

    public function referrals(Request $request): JsonResponse
    {
        $v = $request->validate([
            'campaign_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:pending,qualified,rewarded,reversed'],
        ]);

        $rows = Referral::query()
            ->forTenant($this->tenant->current())
            ->when(isset($v['campaign_id']), fn ($q) => $q->where('campaign_id', $v['campaign_id']))
            ->when(isset($v['status']), fn ($q) => $q->where('status', $v['status']))
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function rewards(Request $request): JsonResponse
    {
        $v = $request->validate([
            'state' => ['nullable', 'in:pending,granted,reversed,expired'],
            'party' => ['nullable', 'in:referrer,referee'],
        ]);

        $rows = Reward::query()
            ->forTenant($this->tenant->current())
            ->when(isset($v['state']), fn ($q) => $q->where('state', $v['state']))
            ->when(isset($v['party']), fn ($q) => $q->where('party', $v['party']))
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function waitlist(Request $request): JsonResponse
    {
        $v = $request->validate([
            'status' => ['nullable', 'in:waiting,invited,converted,removed'],
        ]);

        $rows = WaitlistEntry::query()
            ->forTenant($this->tenant->current())
            ->when(isset($v['status']), fn ($q) => $q->where('status', $v['status']))
            ->orderByDesc('priority')
            ->orderBy('position')
            ->limit(self::LIMIT)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function abuseSignals(Request $request): JsonResponse
    {
        $v = $request->validate([
            'severity' => ['nullable', 'in:warn,block'],
            'action' => ['nullable', 'in:none,flag,throttle,block'],
        ]);

        $rows = AbuseSignal::query()
            ->forTenant($this->tenant->current())
            ->when(isset($v['severity']), fn ($q) => $q->where('severity', $v['severity']))
            ->when(isset($v['action']), fn ($q) => $q->where('action_taken', $v['action']))
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get();

        return response()->json(['data' => $rows]);
    }
}
