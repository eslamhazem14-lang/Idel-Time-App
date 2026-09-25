<?php

namespace App\TaskTypes\Types;

use App\Models\Task;
use App\TaskTypes\AbstractTaskType;

class AiEvaluationType extends AbstractTaskType
{
    public const CRITERIA = [
        'correctness' => 'Correctness',
        'relevance' => 'Relevance',
        'quality' => 'Quality',
        'safety' => 'Safety',
    ];

    public function key(): string
    {
        return 'ai_evaluation';
    }

    public function label(): string
    {
        return 'AI Evaluation';
    }

    public function description(): string
    {
        return 'The developer rates an AI response to a prompt on correctness, relevance, quality and safety.';
    }

    public function normalizePayload(array $input): array
    {
        $input['safety_applicable'] = filter_var($input['safety_applicable'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $input;
    }

    public function payloadRules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:10000'],
            'response' => ['required', 'string', 'max:20000'],
            'safety_applicable' => ['boolean'],
        ];
    }

    public function criteria(Task $task): array
    {
        $criteria = self::CRITERIA;
        if (! ($task->payload['safety_applicable'] ?? false)) {
            unset($criteria['safety']);
        }

        return $criteria;
    }

    public function answerRules(Task $task): array
    {
        $rules = [];
        foreach (array_keys($this->criteria($task)) as $criterion) {
            $rules[$criterion] = ['required', 'integer', 'between:1,5'];
        }
        $rules['comments'] = ['nullable', 'string', 'max:5000'];

        return $rules;
    }

    public function prepareAnswer(Task $task, array $validated): array
    {
        $answer = [];
        foreach (array_keys($this->criteria($task)) as $criterion) {
            $answer[$criterion] = (int) $validated[$criterion];
        }
        $answer['comments'] = $validated['comments'] ?? null;

        return $answer;
    }

    public function fingerprint(array $answer): ?string
    {
        $comments = trim((string) ($answer['comments'] ?? ''));

        return mb_strlen($comments) >= 40 ? $comments : null;
    }

    public function batchField(): string
    {
        return 'prompt';
    }
}
