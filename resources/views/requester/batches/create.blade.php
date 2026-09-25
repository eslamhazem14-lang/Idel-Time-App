@php $descriptions = collect($types)->map(fn ($t) => $t->description())->all(); $source = null; $limits = []; @endphp
<x-layouts.app title="New batch">
    <x-page-header title="Create a task batch" subtitle="One definition, many items. Each line becomes its own microtask." :back="route('requester.tasks.index')" />

    <form method="POST" action="{{ route('requester.batches.store') }}"
          x-data="mixin({ type: '{{ old('type', 'text_response') }}', descriptions: @js($descriptions), items: @js(old('items', '')), get count() { return this.items.split(/\r?\n/).filter(l => l.trim() !== '').length }}, priceCalc('{{ $commission }}', '{{ old('reward', '0.30') }}', {{ (int) old('slots', 1) }}))"
          class="grid gap-6 lg:grid-cols-[1fr_380px]">
        @csrf
        <div class="space-y-6">
            @include('requester.tasks._definition', ['withPayload' => false])
            <div class="card card-pad space-y-3">
                <h2 class="text-sm font-semibold">Items <span class="ml-1 font-normal text-muted" x-text="`(${count} of max {{ $maxItems }})`"></span></h2>
                <p class="text-xs text-muted">One item per line. A plain line fills the type's main field
                    (@foreach ($types as $key => $h)<span x-show="type === '{{ $key }}'" class="font-mono text-ink-2">{{ $h->batchField() }}</span>@endforeach).
                    For multi-field types, use one JSON object per line, e.g. <span class="font-mono text-ink-2">{"prompt": "…", "response": "…"}</span>.</p>
                <textarea name="items" rows="12" x-model="items" class="field font-mono text-[13px] @error('items') field-error @enderror" placeholder="https://example.com/page-1&#10;https://example.com/page-2"></textarea>
                @error('items')<p class="text-xs text-danger">{{ $message }}</p>@enderror
                <div x-show="type === 'multiple_choice'" x-cloak>
                    <x-textarea name="payload[options]" label="Shared answer options (one per line)" rows="4" />
                </div>
                <div x-show="type === 'code_review'" x-cloak>
                    <x-textarea name="payload[questions]" label="Shared review questions (one per line, optional)" rows="3" />
                </div>
                <div x-show="type === 'website_qa'" x-cloak>
                    <x-textarea name="payload[test_steps]" label="Shared test steps" rows="4" />
                </div>
                <div x-show="type === 'documentation_verification'" x-cloak>
                    <x-textarea name="payload[claims]" label="Shared claims to verify (one per line)" rows="4" />
                </div>
                <div x-show="type === 'bug_reproduction'" x-cloak>
                    <x-textarea name="payload[expected]" label="Shared expected result" rows="2" />
                </div>
            </div>
        </div>
        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            @include('requester.tasks._pricing', ['slotsLabel' => 'Workers per item', 'multiplier' => 'Math.max(count, 1)', 'limits' => []])
            <p class="text-xs text-muted">Total = per-item cost × <span x-text="count"></span> items.</p>
            <button class="btn-primary btn-lg w-full">Submit batch for approval</button>
        </aside>
    </form>
</x-layouts.app>
