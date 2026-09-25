<?php

namespace App\Http\Resources;

use App\Models\TaskSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TaskSubmission */
class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'claim_id' => $this->task_claim_id,
            'task' => new TaskResource($this->whenLoaded('task')),
            'developer' => $this->when($request->user()?->id !== $this->developer_id, fn () => [
                'id' => $this->developer_id, 'name' => $this->developer?->name,
            ]),
            'status' => $this->status->value,
            'answer' => $this->answer,
            'reward' => $this->reward->toDecimal(),
            'time_spent_seconds' => $this->time_spent_seconds,
            'submitted_at' => $this->submitted_at->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'revision_note' => $this->revision_note,
            'auto_approved' => $this->auto_approved,
            'appeal' => $this->whenLoaded('dispute', fn () => $this->dispute ? ['status' => $this->dispute->status->value, 'resolution_note' => $this->dispute->resolution_note] : null),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
