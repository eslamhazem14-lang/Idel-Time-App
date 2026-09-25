<x-layouts.app title="Fraud signals">
    <x-page-header title="Fraud signals" subtitle="Heuristic flags for human review. Nothing is banned automatically." />
    @include('admin._tabs', ['route' => 'admin.fraud.index', 'cases' => \App\Enums\FraudFlagStatus::cases(), 'current' => $status])
    <div class="card overflow-x-auto">
        @if ($flags->isEmpty())
            <x-empty icon="shield" title="No signals" text="No accounts need review right now." />
        @else
            <table class="table">
                <thead><tr><th>User</th><th>Signal</th><th>Details</th><th>Score</th><th>Raised</th><th></th></tr></thead>
                <tbody>
                    @foreach ($flags as $f)
                        <tr>
                            <td><a href="{{ route('admin.users.show', $f->user) }}" class="hover:text-primary">{{ $f->user->name }}</a><div class="text-xs text-faint">total {{ $f->user->fraud_score }}</div></td>
                            <td>{{ $f->type->label() }}</td>
                            <td class="max-w-sm truncate font-mono text-[11px] text-faint">{{ json_encode($f->details) }}</td>
                            <td class="mono-num">+{{ $f->score }}</td>
                            <td class="text-muted">{{ $f->created_at->diffForHumans() }}</td>
                            <td class="text-right">
                                @if ($f->status->value === 'open')
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.fraud.update', $f) }}">@csrf<input type="hidden" name="status" value="dismissed"><button class="btn-ghost btn-sm">Dismiss</button></form>
                                        <form method="POST" action="{{ route('admin.fraud.update', $f) }}">@csrf<input type="hidden" name="status" value="confirmed"><button class="btn-danger btn-sm">Confirm</button></form>
                                    </div>
                                @else
                                    <span class="text-xs text-faint">{{ $f->reviewer?->name }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $flags->links() }}</div>
        @endif
    </div>
</x-layouts.app>
