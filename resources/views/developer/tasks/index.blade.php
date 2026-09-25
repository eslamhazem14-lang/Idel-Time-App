<x-layouts.app title="Find tasks">
    <x-page-header title="Find tasks" subtitle="Pick something that fits the time your AI agent needs." />

    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1.4fr_repeat(5,1fr)_auto]" x-data @change="$el.requestSubmit()">
        <div class="relative sm:col-span-2 lg:col-span-1">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 size-4 text-faint" />
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search tasks…" class="field pl-9" aria-label="Search">
        </div>
        <select name="category" class="field" aria-label="Category">
            <option value="">All categories</option>
            @foreach ($categories as $c)<option value="{{ $c->slug }}" @selected(($filters['category'] ?? '') === $c->slug)>{{ $c->name }}</option>@endforeach
        </select>
        <select name="max_minutes" class="field" aria-label="Estimated time">
            <option value="">Any duration</option>
            @foreach (\App\Services\TaskBrowser::TIME_BUCKETS as $v => $l)<option value="{{ $v }}" @selected(($filters['max_minutes'] ?? '') == $v)>{{ $l }}</option>@endforeach
        </select>
        <select name="min_reward" class="field" aria-label="Minimum reward">
            <option value="">Any reward</option>
            @foreach (['0.25', '0.50', '1.00', '2.00'] as $v)<option value="{{ $v }}" @selected(($filters['min_reward'] ?? '') == $v)>${{ $v }}+</option>@endforeach
        </select>
        <select name="difficulty" class="field" aria-label="Difficulty">
            <option value="">Any difficulty</option>
            @foreach (\App\Enums\Difficulty::cases() as $d)<option value="{{ $d->value }}" @selected(($filters['difficulty'] ?? '') === $d->value)>{{ $d->label() }}</option>@endforeach
        </select>
        <select name="skill" class="field" aria-label="Skill">
            <option value="">Any skill</option>
            @foreach ($skills as $s)<option value="{{ $s }}" @selected(($filters['skill'] ?? '') === $s)>{{ $s }}</option>@endforeach
        </select>
        <select name="sort" class="field" aria-label="Sort">
            @foreach (\App\Services\TaskBrowser::SORTS as $v => $l)<option value="{{ $v }}" @selected(($filters['sort'] ?? 'best_rate') === $v)>{{ $l }}</option>@endforeach
        </select>
        <noscript><button class="btn-secondary">Apply</button></noscript>
    </form>

    @if (collect($filters)->except('sort')->filter()->isNotEmpty())
        <div class="-mt-3 mb-4 text-xs text-muted">{{ $tasks->total() }} {{ Str::plural('task', $tasks->total()) }} match · <a href="{{ route('developer.tasks.index') }}" class="text-ink hover:underline">Clear filters</a></div>
    @endif

    @if ($tasks->isEmpty())
        <div class="card">
            <x-empty icon="inbox" title="No tasks available right now." text="Nothing matches these filters. Try widening them, or check back shortly — tasks are posted throughout the day.">
                <a href="{{ route('developer.tasks.index') }}" class="btn-secondary">Reset filters</a>
            </x-empty>
        </div>
    @else
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($tasks as $task)
                <x-task-card :task="$task" />
            @endforeach
        </div>
        <div class="mt-6">{{ $tasks->links() }}</div>
    @endif
</x-layouts.app>
