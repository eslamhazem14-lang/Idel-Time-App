<?php

namespace App\TaskTypes\Types;

use App\Models\Task;
use App\TaskTypes\AbstractTaskType;

class CodeReviewType extends AbstractTaskType
{
    protected array $listFields = ['questions'];

    public const VERDICTS = ['approve' => 'Looks good', 'request_changes' => 'Needs changes', 'comment' => 'Comment only'];

    public function key(): string
    {
        return 'code_review';
    }

    public function label(): string
    {
        return 'Code Review';
    }

    public function description(): string
    {
        return 'The developer reviews a code snippet and answers specific questions.';
    }

    public function payloadRules(): array
    {
        return [
            'language' => ['nullable', 'string', 'max:30'],
            'code' => ['required', 'string', 'max:20000'],
            'questions' => ['nullable', 'array', 'max:10'],
            'questions.*' => ['required', 'string', 'max:500'],
        ];
    }

    public function answerRules(Task $task): array
    {
        $count = count($task->payload['questions'] ?? []);

        return [
            'verdict' => ['required', 'in:'.implode(',', array_keys(self::VERDICTS))],
            'issues' => ['required_if:verdict,request_changes', 'nullable', 'string', 'max:10000'],
            'answers' => [$count ? 'required' : 'nullable', 'array', 'size:'.$count],
            'answers.*' => ['required', 'string', 'max:3000'],
        ];
    }

    public function prepareAnswer(Task $task, array $validated): array
    {
        return [
            'verdict' => $validated['verdict'],
            'issues' => $validated['issues'] ?? null,
            'answers' => array_values($validated['answers'] ?? []),
        ];
    }

    public function fingerprint(array $answer): ?string
    {
        $text = trim(($answer['issues'] ?? '').' '.implode(' ', $answer['answers'] ?? []));

        return $text === '' ? null : $text;
    }

    public function batchField(): string
    {
        return 'code';
    }
}
