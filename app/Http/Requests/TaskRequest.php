<?php

namespace App\Http\Requests;

use App\Enums\Difficulty;
use App\Services\AttachmentService;
use App\TaskTypes\TaskTypeRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a task definition. Used by web + API, for single tasks and batches.
 * Monetary totals are never read from input — only the per-slot reward, which
 * is bounded by admin settings. The server computes fee and total.
 */
class TaskRequest extends FormRequest
{
    protected bool $batch = false;

    public function authorize(): bool
    {
        return (bool) $this->user()?->isRequester();
    }

    protected function prepareForValidation(): void
    {
        $registry = app(TaskTypeRegistry::class);
        $type = (string) $this->input('type');
        $payload = $this->input('payload', []);

        if ($registry->has($type) && is_array($payload)) {
            $this->merge(['payload' => $registry->get($type)->normalizePayload($payload)]);
        }

        $skills = $this->input('required_skills');
        if (is_string($skills)) {
            $this->merge(['required_skills' => array_values(array_filter(array_map('trim', explode(',', $skills))))]);
        }
        if (is_string($this->input('reward'))) {
            $this->merge(['reward' => str_replace(['$', ',', ' '], '', $this->input('reward'))]);
        }
    }

    public function rules(): array
    {
        $registry = app(TaskTypeRegistry::class);
        $rules = [
            'type' => ['required', Rule::in($registry->keys())],
            'category_id' => ['required', Rule::exists('task_categories', 'id')->where('is_active', true)],
            'template_id' => ['nullable', Rule::exists('task_templates', 'id')],
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'instructions' => ['required', 'string', 'min:10', 'max:10000'],
            'answer_format' => ['nullable', 'string', 'max:1000'],
            'estimated_minutes' => ['required', 'integer', 'min:'.(int) settings('min_task_minutes'), 'max:'.(int) settings('max_task_minutes')],
            'reward' => ['required', 'decimal:0,2', 'min:'.settings('min_reward'), 'max:'.settings('max_reward')],
            'slots' => ['required', 'integer', 'min:1', 'max:'.($this->batch ? 100 : 10000)],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'required_skills' => ['nullable', 'array', 'max:8'],
            'required_skills.*' => ['string', 'max:30'],
            'deadline' => ['nullable', 'date', 'after:now', 'before:+1 year'],
            'attachments' => ['nullable', 'array', 'max:'.config('platform.uploads.max_files')],
            'attachments.*' => AttachmentService::validationRules(),
            'payload' => ['nullable', 'array'],
        ];

        if (! $this->batch && $registry->has((string) $this->input('type'))) {
            foreach ($registry->get($this->input('type'))->payloadRules() as $key => $rule) {
                $rules['payload.'.$key] = $rule;
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'payload.question' => 'question',
            'payload.options' => 'answer options',
            'payload.code' => 'code snippet',
            'payload.questions' => 'review questions',
            'payload.url' => 'URL',
            'payload.test_steps' => 'test steps',
            'payload.prompt' => 'prompt',
            'payload.response' => 'AI response',
            'payload.claims' => 'claims',
            'payload.excerpt' => 'documentation excerpt',
            'payload.steps' => 'reproduction steps',
            'payload.expected' => 'expected result',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (preg_match('/^\d+(\.\d{1,2})?$/', (string) $this->input('reward')) !== 1) {
                $validator->errors()->add('reward', 'Enter the reward as an amount like 0.50.');
            }
        }];
    }
}
