<?php

namespace App\TaskTypes\Types;

use App\Models\Task;
use App\TaskTypes\AbstractTaskType;

class TextResponseType extends AbstractTaskType
{
    public function key(): string
    {
        return 'text_response';
    }

    public function label(): string
    {
        return 'Text Response';
    }

    public function description(): string
    {
        return 'The developer answers a question in free text.';
    }

    public function payloadRules(): array
    {
        return ['question' => ['required', 'string', 'max:5000']];
    }

    public function answerRules(Task $task): array
    {
        return ['text' => ['required', 'string', 'min:2', 'max:10000']];
    }

    public function fingerprint(array $answer): ?string
    {
        return $answer['text'] ?? null;
    }

    public function batchField(): string
    {
        return 'question';
    }
}
