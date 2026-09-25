<x-layouts.app :title="$template->exists ? 'Edit template' : 'New template'">
    <x-page-header :title="$template->exists ? 'Edit template' : 'New template'" :back="route('admin.templates.index')" />
    <form method="POST" action="{{ $template->exists ? route('admin.templates.update', $template) : route('admin.templates.store') }}" class="card card-pad max-w-3xl space-y-4">
        @csrf
        @if ($template->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input name="name" label="Template name" :value="$template->name" required />
            <x-select name="type" label="Task type" :options="$types" :value="$template->type" required />
            <x-select name="category_id" label="Category" :options="$categories->pluck('name', 'id')->all()" :value="$template->category_id" placeholder="Choose…" required />
            <x-select name="difficulty" label="Difficulty" :options="collect($difficulties)->mapWithKeys(fn ($d) => [$d->value => $d->label()])->all()" :value="$template->difficulty?->value" />
        </div>
        <x-input name="title" label="Default task title" :value="$template->title" required />
        <x-textarea name="description" label="Description" rows="2" :value="$template->description" required />
        <x-textarea name="instructions" label="Instructions" rows="6" :value="$template->instructions" required />
        <x-input name="answer_format" label="Expected answer format" :value="$template->answer_format" />
        <div class="grid gap-4 sm:grid-cols-3">
            <x-input name="estimated_minutes" type="number" label="Estimated minutes" :value="$template->estimated_minutes" required />
            <x-input name="suggested_reward" label="Suggested reward (USD)" :value="$template->suggested_reward?->toDecimal()" required />
            <x-input name="required_skills" label="Skills (comma separated)" :value="implode(', ', $template->required_skills ?? [])" />
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($template->is_active) class="rounded border-line bg-bg text-primary"> Active (visible to requesters)</label>
        <div class="flex justify-end"><button class="btn-primary">Save template</button></div>
    </form>
</x-layouts.app>
