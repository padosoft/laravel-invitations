<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Padosoft\Invitations\Services\InvitationService;

/**
 * Admin send of targeted invitations (R44 HTTP surface over InvitationService).
 * Send is idempotent per (tenant, recipient, context_ref) inside the service.
 */
final class InviteInvitationController extends Controller
{
    public function __construct(private readonly InvitationService $invitations) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient' => ['required', 'email', 'max:255'],
            'channel' => ['nullable', Rule::in(['email', 'sms', 'in_app', 'link'])],
            'context_ref' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:120'],
            'code_id' => ['nullable', 'integer'],
        ]);

        $invitation = $this->invitations->send($data['recipient'], $this->invitedUser($request), [
            'channel' => $data['channel'] ?? 'email',
            'context_ref' => $data['context_ref'] ?? null,
            'role' => $data['role'] ?? null,
            'code_id' => $data['code_id'] ?? null,
        ]);

        return response()->json([
            'data' => [
                'id' => $invitation->id,
                'recipient' => $invitation->recipient,
                'status' => $invitation->status,
                'channel' => $invitation->channel,
                'expires_at' => optional($invitation->expires_at)->toIso8601String(),
            ],
        ], 201);
    }
}
