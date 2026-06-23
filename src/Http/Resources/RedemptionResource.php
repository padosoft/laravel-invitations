<?php

declare(strict_types=1);

namespace Padosoft\Invitations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Padosoft\Invitations\Models\Redemption;

/**
 * @mixin Redemption
 *
 * Deliberately omits the PII columns (ip / user_agent / fingerprint) — they
 * are $hidden on the model and never belong in an API response.
 */
class RedemptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'code_id' => $this->resource->code_id,
            'redeemer_id' => $this->resource->redeemer_id,
            'invitation_id' => $this->resource->invitation_id,
            'redeemed_at' => optional($this->resource->redeemed_at)->toIso8601String(),
        ];
    }
}
