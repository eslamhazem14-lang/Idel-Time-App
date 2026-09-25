{{-- Shared task definition fields (single task + batch). Expects: $categories, $types, $difficulties, $source (Task|TaskTemplate|null), $withPayload --}}
@php
    $v = fn ($key, $default = null) => old($key, $source?->{$key} ?? $default);
    $initialType = old('type', $source?->type ?? 'text_response');
    $skills = old('required_skills', $source?->required_skills ?? []);
@endphp
<div class="card card-pad space-y-4">
    <h2 class="text-sm font-semibold">Basics</h2>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-select name="category_id" label="Category" :options="$categories->pluck('name', 'id')->all()" :value="$v('category_id')" placeholder="Choose a category" required />
        <div>
            <label class="label" for="f_type">Task type</label>
            <select id="f_type" name="type" x-model="type" class="field @error('type') field-error @enderror">
                @foreach ($types as $key => $handler)<option value="{{ $key }}">{{ $handler->label() }}</option>@endforeach
            </select>
            <p class="mt-1 text-xs text-faint" x-text="descriptions[type]"></p>
        </div>
    </div>
    <x-input name="title" label="Title" :value="$v('title')" maxlength="120" placeholder="Review an AI answer about SQL joins" required />
    <x-textarea name="description" label="Short description" rows="2" :value="$v('description')" hint="Shown on the task card. What is this and why does it matter?" required />
    <x-textarea name="instructions" label="Detailed instructions" rows="6" :value="$v('instructions')" hint="Step by step. Say exactly what a good answer looks like." required />
    <x-input name="answer_format" label="Expected answer format (optional)" :value="$v('answer_format')" placeholder="e.g. List each issue with a line number" />
    <div class="grid gap-4 sm:grid-cols-3">
        <x-input name="estimated_minutes" type="number" label="Estimated minutes" :value="$v('estimated_minutes', 5)" :min="$limits['min_minutes'] ?? 1" :max="$limits['max_minutes'] ?? 15" required />
        <x-select name="difficulty" label="Difficulty" :options="collect($difficulties)->mapWithKeys(fn ($d) => [$d->value => $d->label()])->all()" :value="old('difficulty', $source?->difficulty?->value ?? 'easy')" />
        <x-input name="required_skills" label="Required skills" :value="is_array($skills) ? implode(', ', $skills) : $skills" placeholder="php, sql" hint="Comma separated" />
    </div>
</div>

@if ($withPayload)
    @php $p = old('payload', $source?->payload ?? []); @endphp
    <div class="card card-pad">
        <h2 class="mb-4 text-sm font-semibold">Task content</h2>
        @foreach ($types as $key => $handler)
            <fieldset x-show="type === '{{ $key }}'" x-bind:disabled="type !== '{{ $key }}'" @if ($initialType !== $key) x-cloak @endif>
                @include($handler->view('form'), ['p' => $p])
            </fieldset>
        @endforeach
    </div>
@endif
