<x-layouts.base :title="$title" :noindex="true">
    <div class="grid min-h-screen place-items-center px-4 grid-bg">
        <div class="max-w-md text-center">
            <div class="mb-8 flex justify-center"><x-logo /></div>
            <div class="font-mono text-sm text-primary">{{ $code }}</div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $title }}</h1>
            <p class="mt-3 text-muted">{{ $text }}</p>
            <div class="mt-8 flex justify-center gap-3">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="btn-secondary">Go back</a>
                <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="btn-primary">{{ auth()->check() ? 'Dashboard' : 'Home' }}</a>
            </div>
        </div>
    </div>
</x-layouts.base>
