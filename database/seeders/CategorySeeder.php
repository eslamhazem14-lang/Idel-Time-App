<?php

namespace Database\Seeders;

use App\Models\TaskCategory;
use Illuminate\Database\Seeder;

/**
 * Default task categories. Safe to run in production:
 *   php artisan db:seed --class=CategorySeeder --force
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoContent::categories() as $i => [$name, $slug, $icon, $description]) {
            TaskCategory::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name, 'icon' => $icon, 'description' => $description, 'sort_order' => $i, 'is_active' => true,
            ]);
        }
    }
}
