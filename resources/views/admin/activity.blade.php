<x-layouts.app title="Activity log">
    <x-page-header title="Activity log" subtitle="Audit trail of security- and money-relevant actions." />
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input name="action" value="{{ $filters['action'] ?? '' }}" placeholder="Action prefix, e.g. withdrawal." class="field max-w-xs">
        <input name="user" value="{{ $filters['user'] ?? '' }}" placeholder="User ID" class="field max-w-32">
        <button class="btn-secondary">Filter</button>
    </form>
    <div class="card">
        @include('admin._activity-rows', ['logs' => $logs])
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</x-layouts.app>
