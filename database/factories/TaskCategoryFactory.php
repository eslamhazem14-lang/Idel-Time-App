<?php

namespace Database\Factories;

use App\Models\TaskCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TaskCategory>
 */
class TaskCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return ['name' => $name, 'slug' => Str::slug($name), 'icon' => 'code', 'is_active' => true, 'sort_order' => 0];
    }
}
