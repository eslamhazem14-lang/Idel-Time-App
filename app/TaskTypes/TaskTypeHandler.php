<?php

namespace App\TaskTypes;

use App\Models\Task;

/**
 * Contract for a task type. To add a new type:
 *  1. implement this interface (usually by extending AbstractTaskType),
 *  2. add Blade partials in resources/views/task-types/{key}/ (form, content, answer, review),
 *  3. register the class in config/tasktypes.php.
 */
interface TaskTypeHandler
{
    public function key(): string;

    public function label(): string;

    public function description(): string;

    /** Normalize raw requester input (e.g. "one per line" textareas → arrays) before validation. */
    public function normalizePayload(array $input): array;

    /** Validation rules for the requester-provided payload, keyed relative to "payload.". */
    public function payloadRules(): array;

    /** Normalize raw developer input before validation. */
    public function normalizeAnswer(Task $task, array $input): array;

    /** Validation rules for the developer answer, keyed relative to "answer.". */
    public function answerRules(Task $task): array;

    /** Strip anything not described by the rules and cast values. */
    public function prepareAnswer(Task $task, array $validated): array;

    /** Free text used for duplicate / repeated-answer detection, or null when answers are naturally similar. */
    public function fingerprint(array $answer): ?string;

    /** Field that a single plain-text batch line populates. */
    public function batchField(): string;

    public function allowsAttachments(): bool;

    public function view(string $part): string;
}
