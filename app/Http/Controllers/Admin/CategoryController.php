<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaskCategory;
use App\Models\TaskTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => TaskCategory::query()->withCount('tasks')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        TaskCategory::query()->create($data + ['slug' => $this->uniqueSlug($data['name'])]);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, TaskCategory $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return back()->with('success', 'Category updated.');
    }

    public function destroy(TaskCategory $category): RedirectResponse
    {
        if ($category->tasks()->exists() || TaskTemplate::query()->where('category_id', $category->id)->exists()) {
            $category->update(['is_active' => false]);

            return back()->with('success', 'Category is in use by tasks or templates, so it was deactivated instead of deleted.');
        }
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    private function validated(Request $request, ?TaskCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('task_categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:40', 'alpha_dash'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['icon'] = $data['icon'] ?? 'square';
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $i = 1;
        while (TaskCategory::query()->where('slug', $slug)->exists()) {
            $slug = Str::slug($name).'-'.++$i;
        }

        return $slug;
    }
}
