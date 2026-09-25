<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Difficulty;
use App\Http\Controllers\Controller;
use App\Models\TaskCategory;
use App\Models\TaskTemplate;
use App\TaskTypes\TaskTypeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.templates.index', ['templates' => TaskTemplate::query()->with('category')->orderBy('name')->paginate(25)]);
    }

    public function create(TaskTypeRegistry $types): View
    {
        return view('admin.templates.form', $this->formData($types) + ['template' => new TaskTemplate(['is_active' => true, 'estimated_minutes' => 5, 'suggested_reward' => '0.50'])]);
    }

    public function store(Request $request, TaskTypeRegistry $types): RedirectResponse
    {
        TaskTemplate::query()->create($this->validated($request, $types));

        return redirect()->route('admin.templates.index')->with('success', 'Template created.');
    }

    public function edit(TaskTemplate $template, TaskTypeRegistry $types): View
    {
        return view('admin.templates.form', $this->formData($types) + ['template' => $template]);
    }

    public function update(Request $request, TaskTemplate $template, TaskTypeRegistry $types): RedirectResponse
    {
        $template->update($this->validated($request, $types));

        return redirect()->route('admin.templates.index')->with('success', 'Template updated.');
    }

    public function destroy(TaskTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('success', 'Template deleted.');
    }

    private function formData(TaskTypeRegistry $types): array
    {
        return ['categories' => TaskCategory::query()->active()->get(), 'types' => $types->options(), 'difficulties' => Difficulty::cases()];
    }

    private function validated(Request $request, TaskTypeRegistry $types): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category_id' => ['required', 'exists:task_categories,id'],
            'type' => ['required', Rule::in($types->keys())],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'instructions' => ['required', 'string', 'max:10000'],
            'estimated_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'suggested_reward' => ['required', 'decimal:0,2', 'min:0.01', 'max:1000'],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'required_skills' => ['nullable', 'string', 'max:255'],
            'answer_format' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['required_skills'] = array_values(array_filter(array_map('trim', explode(',', (string) ($data['required_skills'] ?? '')))));
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
