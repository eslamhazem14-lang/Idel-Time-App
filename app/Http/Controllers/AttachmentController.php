<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function show(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return $this->download($attachment);
    }

    /** Temporary signed URL (see AttachmentResource) — no session required. */
    public function signed(Attachment $attachment): StreamedResponse
    {
        return $this->download($attachment);
    }

    private function download(Attachment $attachment): StreamedResponse
    {
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        // Always served as a download with a safe content type — never rendered inline.
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }
}
