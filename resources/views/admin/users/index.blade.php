<x-layouts.app title="Users">
    <x-page-header title="Users" />
    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-5">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name or email" class="field sm:col-span-2">
        <select name="role" class="field"><option value="">All roles</option>@foreach (\App\Enums\UserRole::cases() as $r)<option value="{{ $r->value }}" @selected(($filters['role'] ?? '') === $r->value)>{{ $r->label() }}</option>@endforeach</select>
        <select name="status" class="field"><option value="">Any status</option>@foreach (\App\Enums\UserStatus::cases() as $s)<option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>@endforeach</select>
        <div class="flex gap-2">
            <label class="flex items-center gap-2 text-sm text-ink-2"><input type="checkbox" name="flagged" value="1" class="rounded border-line bg-bg text-primary" @checked($filters['flagged'] ?? false)> Flagged</label>
            <button class="btn-secondary ml-auto">Filter</button>
        </div>
    </form>
    <div class="card overflow-x-auto">
        @if ($users->isEmpty())
            <x-empty icon="users" title="No users match" />
        @else
            <table class="table">
                <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Balance</th><th>Fraud score</th><th>Joined</th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td><a href="{{ route('admin.users.show', $u) }}" class="font-medium hover:text-primary">{{ $u->name }}</a><div class="text-xs text-faint">{{ $u->email }}</div></td>
                            <td>{{ $u->role->label() }}</td>
                            <td><x-status :value="$u->status" /> @unless ($u->email_verified_at)<x-badge color="amber">Unverified</x-badge>@endunless</td>
                            <td class="mono-num">{{ $u->wallet?->balance->format() ?? '—' }}</td>
                            <td>@if ($u->fraud_score)<x-badge :color="$u->isFlagged() ? 'red' : 'amber'">{{ $u->fraud_score }}</x-badge>@else<span class="text-faint">0</span>@endif</td>
                            <td class="text-muted">{{ $u->created_at->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $users->links() }}</div>
        @endif
    </div>
</x-layouts.app>
