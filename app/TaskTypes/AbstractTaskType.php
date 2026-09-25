<?php

namespace App\TaskTypes;

use App\Models\Task;
use Illuminate\Support\Arr;

abstract class AbstractTaskType implements TaskTypeHandler
{
    /** Payload keys that are entered as "one per line" text. */
    protected array $listFields = [];

    public function normalizePayload(array $input): array
    {
        foreach ($this->listFields as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = self::lines($input[$field]);
            }
        }

        return $input;
    }

    public function normalizeAnswer(Task $task, array $input): array
    {
        return $input;
    }

    public function prepareAnswer(Task $task, array $validated): array
    {
        $keys = array_map(fn ($k) => explode('.', $k)[0], array_keys($this->answerRules($task)));

        return Arr::only($validated, array_unique($keys));
    }

    public function fingerprint(array $answer): ?string
    {
        return null;
    }

    public function allowsAttachments(): bool
    {
        return false;
    }

    public function view(string $part): string
    {
        return 'task-types.'.$this->key().'.'.$part;
    }

    public static function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)), fn ($l) => $l !== ''));
    }
}
