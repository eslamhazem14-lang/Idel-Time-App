<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Stores uploads on a private disk under random names. Files are only ever
 * served through the authorized AttachmentController.
 */
class AttachmentService
{
    public static function validationRules(): array
    {
        $cfg = config('platform.uploads');

        return [
            'file',
            'max:'.$cfg['max_kb'],
            'extensions:'.implode(',', $cfg['extensions']),
            'mimetypes:'.implode(',', $cfg['mimetypes']),
        ];
    }

    public function store(UploadedFile $file, Model $attachable, ?User $uploader = null): Attachment
    {
        $cfg = config('platform.uploads');
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType(); // sniffed from content, not the client header

        // Defence in depth: validation already ran, but services can be called from other entry points.
        if (! in_array($extension, $cfg['extensions'], true) || ! in_array($mime, $cfg['mimetypes'], true)) {
            throw BusinessRuleException::make('This file type is not allowed.');
        }
        if ($file->getSize() > $cfg['max_kb'] * 1024) {
            throw BusinessRuleException::make('This file is too large.');
        }

        $directory = 'attachments/'.now()->format('Y/m');
        $name = Str::random(40).'.'.$extension;
        $path = $file->storeAs($directory, $name, ['disk' => $cfg['disk']]);

        return $attachable->morphMany(Attachment::class, 'attachable')->create([
            'uploader_id' => $uploader?->id,
            'disk' => $cfg['disk'],
            'path' => $path,
            'original_name' => Str::limit(preg_replace('/[^\w.\- ]+/', '_', $file->getClientOriginalName()), 200, ''),
            'mime_type' => $mime,
            'size' => $file->getSize(),
        ]);
    }
}
