<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Padosoft\Invitations\Contracts\TenantResolver;
use Padosoft\Invitations\Http\Requests\RedeemRequest;
use Padosoft\Invitations\Http\Resources\RedemptionResource;
use Padosoft\Invitations\Services\CodeValidator;
use Padosoft\Invitations\Services\InvitationService;
use Padosoft\Invitations\Services\RedemptionService;

/**
 * User-facing redemption surface (R44 — HTTP API layer over the same
 * RedemptionService the PHP and MCP surfaces use).
 *
 * - POST /api/invite/redeem    authenticated account claims a code.
 * - POST /api/invite/validate  advisory pre-check (writes nothing).
 *
 * Error → status mapping is driven by RedemptionError::httpStatus() (R14):
 * failures are surfaced with the correct semantic, never 200-with-empty.
 * `already_redeemed` is idempotent SUCCESS (200), not a failure.
 */
final class RedemptionController extends Controller
{
    public function __construct(
        private readonly RedemptionService $redemption,
        private readonly CodeValidator $validator,
        private readonly TenantResolver $tenant,
        private readonly InvitationService $invitations,
    ) {}

    public function redeem(RedeemRequest $request): JsonResponse
    {
        $result = $this->redemption->redeem(
            $request->string('code')->toString(),
            $this->invitedUser($request),
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        if (! $result->ok) {
            return response()->json([
                'ok' => false,
                'error' => $result->error->value,
            ], $result->error->httpStatus());
        }

        return response()->json([
            'ok' => true,
            'already' => $result->already,
            'redemption' => new RedemptionResource($result->redemption),
        ], 200);
    }

    public function validateCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:128'],
        ]);

        $result = $this->validator->validate($validated['code'], $this->tenant->current());

        if (! $result->ok) {
            return response()->json([
                'valid' => false,
                'error' => $result->error->value,
            ], $result->error->httpStatus());
        }

        return response()->json([
            'valid' => true,
            'code_kind' => $result->code->code_kind,
        ], 200);
    }

    /**
     * In-app pending-invitations badge for the authenticated account. Counts
     * pending invitations addressed to the user's own email.
     */
    public function pendingCount(Request $request): JsonResponse
    {
        return response()->json([
            'pending' => $this->invitations->pendingCountFor((string) $this->invitedUser($request)->getInviteEmail()),
        ]);
    }
}
