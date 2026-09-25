<?php

namespace App\Http\Resources;

use App\Models\IdleSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IdleSession */
class IdleSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client' => $this->client,
            'agent' => $this->agent,
            'expected_minutes' => $this->expected_minutes,
            'minutes_left' => $this->minutesLeft(),
            'started_at' => $this->started_at->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'headline' => $this->minutesLeft() > 0 ? "Your AI is working. You have {$this->minutesLeft()} minutes available." : null,
        ];
    }
}
