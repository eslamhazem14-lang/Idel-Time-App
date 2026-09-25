<?php

namespace App\TaskTypes\Types;

use App\Models\Task;
use App\TaskTypes\AbstractTaskType;

class DocumentationVerificationType extends AbstractTaskType
{
    protected array $listFields = ['claims'];

    public const VERDICTS = ['accurate' => 'Accurate', 'inaccurate' => 'Inaccurate', 'unverifiable' => 'Cannot verify'];

    public function key(): string
    {
        return 'documentation_verification';
    }

    public function label(): string
    {
        return 'Documentation Verification';
    }

    public function description(): string
    {
        return 'The developer checks a list of claims against the provided documentation.';
    }

    public function payloadRules(): array
    {
        return [
            'source_url' => ['nullable', 'url:http,https', 'max:2000'],
            'excerpt' => ['nullable', 'required_without:payload.source_url', 'string', 'max:20000'],
            'claims' => ['required', 'array', 'min:1', 'max:20'],
            'claims.*' => ['required', 'string', 'max:1000'],
        ];
    }

    public function answerRules(Task $task): array
    {
        $count = count($task->payload['claims'] ?? []);

        return [
            'verdicts' => ['required', 'array', 'size:'.$count],
            'verdicts.*' => ['required', 'in:'.implode(',', array_keys(self::VERDICTS))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function prepareAnswer(Task $task, array $validated): array
    {
        return ['verdicts' => array_values($validated['verdicts']), 'notes' => $validated['notes'] ?? null];
    }

    public function fingerprint(array $answer): ?string
    {
        $notes = trim((string) ($answer['notes'] ?? ''));

        return mb_strlen($notes) >= 40 ? $notes : null;
    }

    public function batchField(): string
    {
        return 'source_url';
    }
}
