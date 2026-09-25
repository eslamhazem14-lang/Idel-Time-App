<?php

namespace App\TaskTypes\Types;

use App\Models\Task;
use App\TaskTypes\AbstractTaskType;

class MultipleChoiceType extends AbstractTaskType
{
    protected array $listFields = ['options'];

    public function key(): string
    {
        return 'multiple_choice';
    }

    public function label(): string
    {
        return 'Multiple Choice';
    }

    public function description(): string
    {
        return 'The developer selects one or more answers from a list.';
    }

    public function normalizePayload(array $input): array
    {
        $input = parent::normalizePayload($input);
        $input['multiple'] = filter_var($input['multiple'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $input;
    }

    public function payloadRules(): array
    {
        return [
            'question' => ['required', 'string', 'max:5000'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*' => ['required', 'string', 'max:300'],
            'multiple' => ['boolean'],
        ];
    }

    public function normalizeAnswer(Task $task, array $input): array
    {
        if (isset($input['choices']) && ! is_array($input['choices'])) {
            $input['choices'] = [$input['choices']];
        }

        return $input;
    }

    public function answerRules(Task $task): array
    {
        $max = count($task->payload['options'] ?? []) - 1;

        return [
            'choices' => ['required', 'array', 'min:1', ($task->payload['multiple'] ?? false) ? 'max:10' : 'max:1'],
            'choices.*' => ['integer', 'distinct', 'min:0', 'max:'.max(0, $max)],
        ];
    }

    public function prepareAnswer(Task $task, array $validated): array
    {
        $choices = array_map('intval', $validated['choices']);
        sort($choices);

        return ['choices' => $choices];
    }

    public function batchField(): string
    {
        return 'question';
    }
}
