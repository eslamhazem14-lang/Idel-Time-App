@props(['items'])
@if ($items->isNotEmpty())
    <ul class="divide-y divide-line rounded-lg border border-line">
        @foreach ($items as $file)
            <li class="flex items-center gap-3 px-3 py-2 text-sm">
                <x-icon name="paperclip" class="size-4 text-muted" />
                <span class="min-w-0 flex-1 truncate">{{ $file->original_name }}</span>
                <span class="text-xs text-faint">{{ $file->humanSize() }}</span>
                <a href="{{ route('attachments.show', $file) }}" class="btn-ghost btn-sm"><x-icon name="download" class="size-3.5" /> Download</a>
            </li>
        @endforeach
    </ul>
@endif
