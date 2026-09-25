<x-layouts.app title="Notifications">
    <x-page-header title="Notifications">
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn-secondary">Mark all as read</button></form>
        @endif
    </x-page-header>
    <div class="card">
        @forelse ($notifications as $n)
            <a href="{{ route('notifications.open', $n->id) }}" class="flex items-start gap-3 border-b border-line/60 px-5 py-4 last:border-0 hover:bg-surface-2/60 {{ $n->read_at ? '' : 'bg-primary/[0.04]' }}">
                <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-surface-2 text-muted"><x-icon :name="$n->data['icon'] ?? 'bell'" class="size-4" /></span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 text-sm font-medium">{{ $n->data['title'] ?? 'Notification' }} @unless ($n->read_at)<span class="size-1.5 rounded-full bg-primary"></span>@endunless</div>
                    <p class="text-sm text-muted">{{ $n->data['body'] ?? '' }}</p>
                </div>
                <span class="shrink-0 text-xs text-faint">{{ $n->created_at->diffForHumans() }}</span>
            </a>
        @empty
            <x-empty icon="bell" title="No notifications" text="You're all caught up. We'll let you know when something needs your attention." />
        @endforelse
        @if ($notifications->hasPages())<div class="p-4">{{ $notifications->links() }}</div>@endif
    </div>
</x-layouts.app>
