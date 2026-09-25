<ul class="space-y-1.5 text-sm">
    @foreach ($task->payload['options'] ?? [] as $i => $option)
        @php $chosen = in_array($i, $answer['choices'] ?? [], true); @endphp
        <li class="flex items-center gap-2 rounded-md px-3 py-2 {{ $chosen ? 'bg-primary/10 text-ink ring-1 ring-primary/30' : 'text-muted' }}">
            <x-icon :name="$chosen ? 'check-circle' : 'square'" class="size-4 {{ $chosen ? 'text-primary' : 'text-faint' }}" /> {{ $option }}
        </li>
    @endforeach
</ul>
