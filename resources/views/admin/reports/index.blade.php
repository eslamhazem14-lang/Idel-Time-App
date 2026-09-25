<x-layouts.app title="Reports">
    <x-page-header title="Reports" subtitle="Problems reported by developers." />
    @include('admin._tabs', ['route' => 'admin.reports.index', 'cases' => \App\Enums\ReportStatus::cases(), 'current' => $status])
    <div class="card">
        @forelse ($reports as $r)
            <div class="flex flex-col gap-3 border-b border-line/60 px-5 py-4 last:border-0 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 text-sm">
                    <div class="font-medium">{{ $r->reason->label() }}
                        @if ($r->task) · <a href="{{ route('admin.tasks.show', $r->task) }}" class="text-primary hover:underline">{{ $r->task->title }}</a>@endif
                    </div>
                    @if ($r->description)<p class="mt-1 text-muted">{{ $r->description }}</p>@endif
                    <div class="mt-1 text-xs text-faint">by {{ $r->reporter->name }}{{ $r->reportedUser ? ' · about '.$r->reportedUser->name : '' }} · {{ $r->created_at->diffForHumans() }}</div>
                    @if ($r->resolution_note)<div class="mt-1 text-xs text-ink-2">Note: {{ $r->resolution_note }}</div>@endif
                </div>
                <form method="POST" action="{{ route('admin.reports.update', $r) }}" class="flex shrink-0 gap-2">
                    @csrf
                    <input name="note" class="field py-1.5 text-xs" placeholder="Note (optional)">
                    <select name="status" class="field py-1.5 text-xs">@foreach (\App\Enums\ReportStatus::cases() as $s)<option value="{{ $s->value }}" @selected($r->status === $s)>{{ $s->label() }}</option>@endforeach</select>
                    <button class="btn-secondary btn-sm">Save</button>
                </form>
            </div>
        @empty
            <x-empty icon="flag" title="No reports" text="Nothing reported in this state." />
        @endforelse
        @if ($reports->hasPages())<div class="p-4">{{ $reports->links() }}</div>@endif
    </div>
</x-layouts.app>
