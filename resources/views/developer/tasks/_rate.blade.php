<div class="card card-pad" x-data="{ rating: {{ (int) ($myReview->rating ?? 0) }} }">
    <h2 class="text-sm font-semibold">{{ $myReview ? 'Your rating' : 'Rate this task' }}</h2>
    <p class="mt-1 text-xs text-muted">Help other developers and requesters improve tasks.</p>
    <form method="POST" action="{{ route('developer.tasks.review', $task) }}" class="mt-3 space-y-3">
        @csrf
        <div class="flex gap-1" role="radiogroup" aria-label="Rating">
            @for ($i = 1; $i <= 5; $i++)
                <label class="cursor-pointer">
                    <input type="radio" name="rating" value="{{ $i }}" class="sr-only" x-model.number="rating" @checked(($myReview->rating ?? 0) === $i)>
                    <svg viewBox="0 0 24 24" class="size-6" :class="rating >= {{ $i }} ? 'text-amber' : 'text-line-strong'" fill="currentColor" aria-hidden="true"><path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>
                    <span class="sr-only">{{ $i }} stars</span>
                </label>
            @endfor
        </div>
        <select name="time_accurate" class="field" aria-label="Was the time estimate accurate?">
            <option value="">Was the time estimate accurate?</option>
            <option value="1" @selected(($myReview?->time_accurate) === true)>Yes, about right</option>
            <option value="0" @selected(($myReview?->time_accurate) === false)>No, it took longer</option>
        </select>
        <textarea name="comment" rows="2" class="field" placeholder="Optional comment">{{ $myReview->comment ?? '' }}</textarea>
        <button class="btn-secondary w-full" :disabled="!rating">Save rating</button>
    </form>
</div>
