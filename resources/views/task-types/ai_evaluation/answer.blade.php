@php $criteria = $task->handler()->criteria($task); @endphp
<div class="space-y-4">
    @foreach ($criteria as $key => $label)
        <fieldset>
            <legend class="label">{{ $label }} <span class="text-faint">(1 = poor, 5 = excellent)</span></legend>
            <div class="grid grid-cols-5 gap-2">
                @for ($i = 1; $i <= 5; $i++)
                    <label class="cursor-pointer rounded-lg border border-line bg-bg py-2 text-center text-sm hover:border-line-strong has-[:checked]:border-primary has-[:checked]:bg-primary/15 has-[:checked]:text-ink">
                        <input type="radio" name="answer[{{ $key }}]" value="{{ $i }}" class="sr-only" @checked((int) ($draft[$key] ?? 0) === $i)> {{ $i }}
                    </label>
                @endfor
            </div>
            @error('answer.'.$key)<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
        </fieldset>
    @endforeach
    <x-textarea name="answer[comments]" label="Comments (optional)" rows="4" :value="$draft['comments'] ?? ''" hint="Explain low scores — specific errors are the most useful." />
</div>
