<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Models\TaskClaim;
use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a developer's answer against the rules of the task's type.
 */
class SubmitAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isDeveloper();
    }

    public function task(): Task
    {
        $claim = $this->route('claim');
        if ($claim instanceof TaskClaim) {
            return $claim->task;
        }

        return $this->route('task');
    }

    protected function prepareForValidation(): void
    {
        $answer = $this->input('answer', []);
        if (is_array($answer)) {
            $this->merge(['answer' => $this->task()->handler()->normalizeAnswer($this->task(), $answer)]);
        }
    }

    public function rules(): array
    {
        $task = $this->task();
        $rules = ['answer' => ['required', 'array']];
        foreach ($task->handler()->answerRules($task) as $key => $rule) {
            $rules['answer.'.$key] = $rule;
        }
        if ($task->handler()->allowsAttachments()) {
            $rules['attachments'] = ['nullable', 'array', 'max:'.config('platform.uploads.max_files')];
            $rules['attachments.*'] = AttachmentService::validationRules();
        }

        return $rules;
    }

    public function attributes(): array
    {
        return collect(array_keys($this->rules()))
            ->mapWithKeys(fn ($k) => [$k => str_replace(['answer.', '_', '.*'], ['', ' ', ''], $k)])->all();
    }

    public function answer(): array
    {
        return $this->task()->handler()->prepareAnswer($this->task(), $this->validated('answer'));
    }

    public function uploadedAttachments(): array
    {
        return $this->task()->handler()->allowsAttachments() ? ($this->file('attachments') ?? []) : [];
    }
}
