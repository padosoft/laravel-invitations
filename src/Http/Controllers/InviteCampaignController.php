<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Padosoft\Invitations\Contracts\TenantResolver;
use Padosoft\Invitations\Http\Requests\InviteCampaignStoreRequest;
use Padosoft\Invitations\Http\Resources\InviteCampaignResource;
use Padosoft\Invitations\Models\InviteCampaign;
use Padosoft\Invitations\Services\CampaignService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Admin campaign management (R44 — HTTP surface over CampaignService).
 * Every query is tenant-scoped (R30); the route group applies host auth + RBAC.
 */
final class InviteCampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaigns,
        private readonly TenantResolver $tenant,
    ) {}

    public function index(): JsonResponse
    {
        $campaigns = InviteCampaign::query()
            ->forTenant($this->tenant->current())
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => InviteCampaignResource::collection($campaigns),
        ]);
    }

    /**
     * Grantable tenants for the campaign form's multi-tenant grant editor.
     * Host-agnostic default: the active tenant + 'default'. A multi-tenant host
     * overrides this method (or binds a richer source) to expose every tenant
     * the admin may grant into.
     */
    public function tenants(): JsonResponse
    {
        $current = $this->tenant->current();

        $tenants = collect([$current, 'default'])
            ->filter(fn (string $id): bool => $id !== '')
            ->unique()
            ->map(fn (string $id): array => ['id' => $id, 'name' => Str::headline($id)])
            ->values()
            ->all();

        return response()->json(['data' => $tenants]);
    }

    public function store(InviteCampaignStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] ??= InviteCampaign::STATUS_DRAFT;
        $data['created_by'] = $this->invitedUser($request)->getKey();

        $campaign = $this->campaigns->createCampaign($data);

        return (new InviteCampaignResource($campaign))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => new InviteCampaignResource($this->findOr404($id))]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $campaign = $this->findOr404($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'in:draft,active,paused,ended'],
            'max_redemptions_total' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['sometimes', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'reward_policy' => ['nullable', 'array'],
            'grant' => ['nullable', 'array'],
            'grant.role' => ['nullable', 'string', 'not_in:super-admin'],
            'grant.projects' => ['nullable', 'array'],
            'grant.projects.*' => ['string', 'max:120'],
            'grant.project_role' => ['nullable', 'in:member,admin,owner'],
            'grant.scope_allowlist' => ['nullable', 'array'],
            'grant.tenants' => ['nullable', 'array'],
            'grant.tenants.*.tenant_id' => ['required', 'string', 'max:50'],
            'grant.tenants.*.role' => ['nullable', 'string', 'not_in:super-admin'],
            'grant.tenants.*.projects' => ['nullable', 'array'],
            'grant.tenants.*.projects.*' => ['string', 'max:120'],
            'grant.tenants.*.project_role' => ['nullable', 'in:member,admin,owner'],
            'grant.tenants.*.scope_allowlist' => ['nullable', 'array'],
        ], [
            'grant.role.not_in' => 'super-admin cannot be granted through an invite code.',
            'grant.tenants.*.role.not_in' => 'super-admin cannot be granted through an invite code.',
        ]);

        return response()->json([
            'data' => new InviteCampaignResource($this->campaigns->updateCampaign($campaign, $data)),
        ]);
    }

    private function findOr404(int $id): InviteCampaign
    {
        $campaign = InviteCampaign::query()
            ->forTenant($this->tenant->current())
            ->find($id);

        if ($campaign === null) {
            throw new NotFoundHttpException('Campaign not found.');
        }

        return $campaign;
    }
}
