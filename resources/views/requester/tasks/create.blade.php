@php
    $source = $task ?? $template;
    $descriptions = collect($types)->map(fn ($t) => $t->description())->all();
@endphp
<x-layouts.app :title="$task ? 'Edit task' : 'Post a task'">
    <x-page-header :title="$task ? 'Edit task' : 'Post a task'" :subtitle="$task ? 'Changes re-submit the task for approval.' : 'Small, clear, independent tasks get picked up fastest.'" :back="$task ? route('requester.tasks.show', $task) : route('requester.tasks.index')">
        @if (! $task && $templates->isNotEmpty())
            <div x-data="{ open: false }" class="relative">
                <button type="button" class="btn-secondary" @click="open = !open" @click.outside="open = false"><x-icon name="file" /> Start from template</button>
                <div x-show="open" x-cloak class="absolute right-0 z-20 mt-2 w-72 overflow-hidden rounded-lg border border-line-strong bg-surface-2 py-1 shadow-xl">
                    @foreach ($templates as $t)
                        <a href="{{ route('requester.tasks.create', ['template' => $t->id]) }}" class="block px-3 py-2 text-sm hover:bg-surface-3">{{ $t->name }}<span class="block text-xs text-faint">{{ $t->category->name }} · {{ $t->estimated_minutes }} min · {{ $t->suggested_reward->format() }}</span></a>
                    @endforeach
                </div>
            </div>
        @endif
    </x-page-header>

    @if ($template)
        <div class="mb-5 rounded-lg border border-primary/30 bg-primary/10 px-4 py-3 text-sm">Using template <span class="font-medium">{{ $template->name }}</span>. Fill in the task content below.</div>
    @endif

    <form method="POST" action="{{ $task ? route('requester.tasks.update', $task) : route('requester.tasks.store') }}" enctype="multipart/form-data"
          x-data="mixin({ type: '{{ old('type', $source?->type ?? 'text_response') }}', descriptions: @js($descriptions)}, priceCalc('{{ $commission }}', '{{ old('reward', $task?->reward?->toDecimal() ?? $template?->suggested_reward?->toDecimal() ?? '0.50') }}', {{ (int) old('slots', $task?->available_slots ?? 10) }}))"
          class="grid gap-6 lg:grid-cols-[1fr_380px]">
        @csrf
        @if ($task) @method('PUT') @endif
        @if ($template)<input type="hidden" name="template_id" value="{{ $template->id }}">@endif

        <div class="space-y-6">
            @include('requester.tasks._definition', ['source' => $source, 'withPayload' => true])
            <div class="card card-pad space-y-4">
                <h2 class="text-sm font-semibold">Extras</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input name="deadline" type="datetime-local" label="Deadline (optional)" :value="old('deadline', $task?->deadline?->format('Y-m-d\TH:i'))" hint="Unused budget is refunded after the deadline." />
                    @unless ($task)
                        <div>
                            <label class="label" for="attachments">Attachments (optional)</label>
                            <input id="attachments" type="file" name="attachments[]" multiple class="field file:mr-3 file:rounded-md file:border-0 file:bg-surface-3 file:px-3 file:py-1 file:text-xs file:text-ink">
                            <p class="mt-1 text-xs text-faint">{{ strtoupper(implode(', ', config('platform.uploads.extensions'))) }} · max {{ intdiv(config('platform.uploads.max_kb'), 1024) }} MB</p>
                            @error('attachments.*')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                        </div>
                    @endunless
                </div>
            </div>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            @include('requester.tasks._pricing', ['slotsLabel' => 'Number of workers'])
            <button class="btn-primary btn-lg w-full">{{ $task ? 'Save & re-submit' : 'Submit for approval' }}</button>
            <p class="text-center text-xs text-faint">Tasks are reviewed by our team before going live.</p>
        </aside>
    </form>
</x-layouts.app>
