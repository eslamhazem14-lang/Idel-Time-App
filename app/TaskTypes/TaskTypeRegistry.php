<?php

namespace App\TaskTypes;

use InvalidArgumentException;

class TaskTypeRegistry
{
    /** @var array<string, TaskTypeHandler> */
    private array $types = [];

    public function __construct(array $classes)
    {
        foreach ($classes as $class) {
            $handler = app($class);
            $this->types[$handler->key()] = $handler;
        }
    }

    public function get(string $key): TaskTypeHandler
    {
        return $this->types[$key] ?? throw new InvalidArgumentException("Unknown task type [{$key}].");
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    /** @return array<string, TaskTypeHandler> */
    public function all(): array
    {
        return $this->types;
    }

    public function keys(): array
    {
        return array_keys($this->types);
    }

    public function options(): array
    {
        return array_map(fn (TaskTypeHandler $t) => $t->label(), $this->types);
    }
}
