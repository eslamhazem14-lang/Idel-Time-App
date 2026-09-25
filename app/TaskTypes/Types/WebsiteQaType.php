<?php

namespace App\TaskTypes\Types;

use App\Models\Task;
use App\TaskTypes\AbstractTaskType;

class WebsiteQaType extends AbstractTaskType
{
    public const RESULTS = ['pass' => 'Pass — works as described', 'partial' => 'Partial — minor issues', 'fail' => 'Fail — broken'];

    public function key(): string
    {
        return 'website_qa';
    }

    public function label(): string
    {
        return 'Website QA';
    }

    public function description(): string
    {
        return 'The developer opens a URL, follows test instructions and reports results.';
    }

    public function payloadRules(): array
    {
        return [
            'url' => ['required', 'url:http,https', 'max:2000'],
            'test_steps' => ['required', 'string', 'max:5000'],
            'devices' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function answerRules(Task $task): array
    {
        return [
            'result' => ['required', 'in:'.implode(',', array_keys(self::RESULTS))],
            'environment' => ['required', 'string', 'max:255'],
            'findings' => ['required', 'string', 'min:5', 'max:10000'],
        ];
    }

    public function fingerprint(array $answer): ?string
    {
        return $answer['findings'] ?? null;
    }

    public function allowsAttachments(): bool
    {
        return true;
    }

    public function batchField(): string
    {
        return 'url';
    }
}
