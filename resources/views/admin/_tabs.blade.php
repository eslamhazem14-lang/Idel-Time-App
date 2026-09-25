{{-- Status filter tabs. Expects $route, $cases (enum cases), $current, optional $all (bool), $counts --}}
<div class="mb-4 flex flex-wrap gap-2">
    @if ($all ?? false)
        <a href="{{ route($route, ['status' => '']) }}" class="btn-sm {{ ! $current ? 'btn-primary' : 'btn-secondary' }}">All</a>
    @endif
    @foreach ($cases as $case)
        <a href="{{ route($route, ['status' => $case->value]) }}" class="btn-sm {{ $current === $case->value ? 'btn-primary' : 'btn-secondary' }}">
            {{ $case->label() }}@isset($counts[$case->value]) <span class="text-xs opacity-70 mono-num">{{ $counts[$case->value] }}</span>@endisset
        </a>
    @endforeach
</div>
