<x-layouts.public title="For businesses" description="Post technical microtasks that need human validation: AI response evaluation, code review, website QA, documentation checks and more.">
    <section class="mx-auto max-w-6xl px-4 pt-16 sm:px-6">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">For businesses</p>
        <h1 class="mt-2 max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl">Human technical validation, one microtask at a time.</h1>
        <p class="mt-3 max-w-2xl text-muted">Submit small tasks that need a developer's judgment. Fund a budget, set the reward, and review every result before you pay.</p>
        <div class="mt-6 flex gap-3"><a href="{{ route('register', ['as' => 'requester']) }}" class="btn-primary btn-lg">Post a Task</a><a href="{{ route('pricing') }}" class="btn-secondary btn-lg">See pricing</a></div>
    </section>
    <section class="mx-auto mt-14 grid max-w-6xl gap-4 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-3">
        @foreach ([
            ['sparkles', 'AI response evaluation', 'Rate model outputs for correctness, relevance, quality and safety.'],
            ['code', 'Code review', 'Get targeted answers about a snippet: bugs, edge cases, readability.'],
            ['globe', 'Website QA', 'Have developers walk through flows on real devices and report issues.'],
            ['book', 'Documentation verification', 'Check claims in docs against actual behavior.'],
            ['database', 'Data validation', 'Verify records, labels or extractions against a source.'],
            ['search', 'Technical research', 'Quick lookups that need a technical eye.'],
        ] as [$icon, $title, $text])
            <div class="card card-pad">
                <x-icon :name="$icon" class="size-5 text-primary" />
                <h2 class="mt-3 font-semibold">{{ $title }}</h2>
                <p class="mt-1 text-sm text-muted">{{ $text }}</p>
            </div>
        @endforeach
    </section>
    <section class="mx-auto mt-14 grid max-w-6xl gap-4 px-4 sm:px-6 lg:grid-cols-3">
        @foreach ([
            ['Batches', 'Upload hundreds or thousands of items (one per line, or JSON lines) and create a microtask for each.'],
            ['Escrow', 'Your budget is reserved when you post and only spent on approved work. Unused budget is refunded.'],
            ['Moderated', 'Every task is reviewed by our team before it goes live, and submissions pass automated quality checks.'],
        ] as [$title, $text])
            <div class="rounded-xl border border-line p-5">
                <h3 class="text-sm font-semibold">{{ $title }}</h3>
                <p class="mt-1 text-sm text-muted">{{ $text }}</p>
            </div>
        @endforeach
    </section>
</x-layouts.public>
