<x-layouts.app title="Disputes">
    <x-page-header title="Disputes" subtitle="Developer appeals against rejected submissions." />
    @include('admin._tabs', ['route' => 'admin.disputes.index', 'cases' => \App\Enums\DisputeStatus::cases(), 'current' => $status])
    <div class="card overflow-x-auto">
        @if ($disputes->isEmpty())
            <x-empty icon="scale" title="No disputes" text="Nothing to resolve here." />
        @else
            <table class="table">
                <thead><tr><th>Developer</th><th>Task</th><th>Opened</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($disputes as $d)
                        <tr>
                            <td>{{ $d->developer->name }}</td>
                            <td class="max-w-xs truncate">{{ $d->submission->task->title }}</td>
                            <td class="text-muted">{{ $d->created_at->diffForHumans() }}</td>
                            <td><x-status :value="$d->status" /></td>
                            <td class="text-right"><a href="{{ route('admin.disputes.show', $d) }}" class="btn-secondary btn-sm">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $disputes->links() }}</div>
        @endif
    </div>
</x-layouts.app>
