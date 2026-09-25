<?php

namespace App\TaskTypes\Types;

use App\Models\Task;
use App\TaskTypes\AbstractTaskType;

class BugReproductionType extends AbstractTaskType
{
    public const OUTCOMES = ['yes' => 'Reproduced', 'partially' => 'Partially reproduced', 'no' => 'Could not reproduce'];

    public function key(): string
    {
        return 'bug_reproduction';
    }

    public function label(): string
    {
        return 'Bug Reproduction';
    }

    public function description(): string
    {
        return 'The developer follows reproduction steps and reports whether the bug occurs.';
    }

    public function payloadRules(): array
    {
        return [
            'environment' => ['nullable', 'string', 'max:500'],
            'steps' => ['required', 'string', 'max:10000'],
            'expected' => ['required', 'string', 'max:2000'],
            'actual' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function answerRules(Task $task): array
    {
        return [
            'reproduced' => ['required', 'in:'.implode(',', array_keys(self::OUTCOMES))],
            'environment' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'min:5', 'max:10000'],
        ];
    }

    public function fingerprint(array $answer): ?string
    {
        return $answer['notes'] ?? null;
    }

    public function allowsAttachments(): bool
    {
        return true;
    }

    public function batchField(): string
    {
        return 'steps';
    }
}
