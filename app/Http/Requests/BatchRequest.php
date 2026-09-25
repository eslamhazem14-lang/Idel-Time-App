<?php

namespace App\Http\Requests;

use App\TaskTypes\AbstractTaskType;
use App\TaskTypes\TaskTypeRegistry;
use Illuminate\Validation\Validator;

class BatchRequest extends TaskRequest
{
    protected bool $batch = true;

    public function rules(): array
    {
        return parent::rules() + [
            'items' => ['required', 'string', 'max:2000000'],
        ];
    }

    /**
     * Parse "items": one per line; a line starting with "{" is a JSON object merged into the payload.
     * Every resulting task payload is validated against the task type's rules.
     *
     * @return array<int, string|array>
     */
    public function items(): array
    {
        return array_map(function (string $line) {
            if (str_starts_with($line, '{')) {
                $decoded = json_decode($line, true);

                return is_array($decoded) ? $decoded : $line;
            }

            return $line;
        }, AbstractTaskType::lines((string) $this->input('items')));
    }

    public function after(): array
    {
        return array_merge(parent::after(), [function (Validator $validator) {
            $registry = app(TaskTypeRegistry::class);
            if ($validator->errors()->isNotEmpty() || ! $registry->has((string) $this->input('type'))) {
                return;
            }
            $handler = $registry->get($this->input('type'));
            $items = $this->items();
            $max = (int) config('platform.batch.max_items');
            if (count($items) < 1 || count($items) > $max) {
                $validator->errors()->add('items', "Provide between 1 and {$max} items.");

                return;
            }

            $base = (array) $this->input('payload', []);
            foreach ($items as $i => $item) {
                $payload = is_array($item) ? array_merge($base, $handler->normalizePayload($item)) : array_merge($base, [$handler->batchField() => $item]);
                $check = validator(['payload' => $payload], collect($handler->payloadRules())->mapWithKeys(fn ($r, $k) => ['payload.'.$k => $r])->all());
                if ($check->fails()) {
                    $validator->errors()->add('items', 'Line '.($i + 1).': '.$check->errors()->first());

                    return;
                }
            }
        }]);
    }
}
