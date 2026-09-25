@if ($logs->isEmpty())
    <x-empty icon="activity" title="No activity yet" />
@else
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Subject</th><th>IP</th></tr></thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap text-muted">{{ $log->created_at->diffForHumans() }}</td>
                        <td>@if ($log->user)<a href="{{ route('admin.users.show', $log->user) }}" class="hover:text-primary">{{ $log->user->name }}</a>@else<span class="text-faint">system</span>@endif</td>
                        <td><span class="font-mono text-xs">{{ $log->action }}</span></td>
                        <td class="text-muted">{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '—' }}</td>
                        <td class="font-mono text-xs text-faint">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
