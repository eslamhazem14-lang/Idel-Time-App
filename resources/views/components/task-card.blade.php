@props(['task', 'compact' => false])
<a href="{{ route('developer.tasks.show', $task) }}"
   class="group card flex flex-col gap-4 p-4 transition hover:border-primary/50 hover:bg-surface-2/60">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-[11px] font-medium uppercase tracking-wider text-muted">
                <x-icon :name="$task->category->icon ?? 'square'" class="size-3.5" />
                <span class="truncate">{{ $task->category->name }}</span>
            </div>
            <h3 class="mt-1.5 line-clamp-2 text-[15px] font-semibold leading-snug text-ink group-hover:text-white">{{ $task->title }}</h3>
        </div>
        <div class="shrink-0 text-right">
            <div class="text-lg font-semibold text-secondary mono-num">{{ $task->reward->format() }}</div>
            <div class="text-[11px] text-faint mono-num">{{ $task->rewardPerMinute() }}</div>
        </div>
    </div>
    @unless ($compact)
        <p class="line-clamp-2 text-sm text-muted">{{ $task->description }}</p>
    @endunless
    <div class="mt-auto flex flex-wrap items-center gap-1.5">
        <x-badge color="violet"><x-icon name="clock" class="size-3" /> {{ $task->estimated_minutes }} min</x-badge>
        <x-badge :color="$task->difficulty->color()">{{ $task->difficulty->label() }}</x-badge>
        @foreach (array_slice($task->required_skills ?? [], 0, 2) as $skill)
            <x-badge>{{ $skill }}</x-badge>
        @endforeach
        <span class="ml-auto inline-flex items-center gap-1 text-xs font-medium text-primary opacity-80 group-hover:opacity-100">
            Start task <x-icon name="arrow-right" class="size-3.5 transition group-hover:translate-x-0.5" />
        </span>
    </div>
</a>
