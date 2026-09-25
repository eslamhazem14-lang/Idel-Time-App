<?php

namespace App\Http\Resources;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin Attachment */
class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            // Short-lived signed link; only issued to users already authorized to see the parent resource.
            'download_url' => URL::temporarySignedRoute('attachments.signed', now()->addMinutes(15), ['attachment' => $this->id]),
        ];
    }
}
