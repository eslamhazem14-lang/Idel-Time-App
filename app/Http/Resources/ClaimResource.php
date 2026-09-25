<?php

namespace App\Http\Resources;

use App\Models\TaskClaim;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TaskClaim */
class ClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'claimed_at' => $this->claimed_at->toIso8601String(),
            'expires_at' => $this->expires_at->toIso8601String(),
            'submission_deadline' => $this->submissionDeadline()->toIso8601String(),
            'seconds_remaining' => $this->secondsRemaining(),
            'server_time' => now()->toIso8601String(),
            'draft' => $this->when($this->isActive(), $this->draft),
            'task' => (new TaskResource($this->whenLoaded('task')))->detailed(),
        ];
    }
}
