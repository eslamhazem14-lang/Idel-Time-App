<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
class TaskResource extends JsonResource
{
    /** Include full task content (instructions + type payload). */
    public bool $detailed = false;

    public function detailed(): static
    {
        $this->detailed = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isOwner = $user && ($user->isAdmin() || $user->id === $this->requester_id);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => $this->handler()->label(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'estimated_minutes' => $this->estimated_minutes,
            'reward' => $this->reward->toDecimal(),
            'reward_formatted' => $this->reward->format(),
            'reward_per_minute' => $this->rewardPerMinute(),
            'currency' => config('platform.currency'),
            'difficulty' => $this->difficulty->value,
            'required_skills' => $this->required_skills ?? [],
            'remaining_slots' => $this->remainingSlots(),
            'deadline' => $this->deadline?->toIso8601String(),
            'status' => $this->status->value,
            'published_at' => $this->published_at?->toIso8601String(),
            $this->mergeWhen($this->detailed, fn () => [
                'instructions' => $this->instructions,
                'answer_format' => $this->answer_format,
                'content' => $this->payload ?? [],
                'accepts_attachments' => $this->handler()->allowsAttachments(),
                'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            ]),
            $this->mergeWhen($isOwner, fn () => [
                'platform_fee' => $this->platform_fee->toDecimal(),
                'total_budget' => $this->total_budget->toDecimal(),
                'escrow_balance' => $this->escrow_balance->toDecimal(),
                'available_slots' => $this->available_slots,
                'reserved_slots' => $this->reserved_slots,
                'completed_slots' => $this->completed_slots,
                'rejection_reason' => $this->rejection_reason,
            ]),
        ];
    }
}
