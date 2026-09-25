<div class="space-y-4">
    @foreach ($task->payload['claims'] ?? [] as $i => $claim)
        <fieldset class="rounded-lg border border-line bg-bg p-3">
            <legend class="sr-only">Claim {{ $i + 1 }}</legend>
            <p class="text-sm text-ink"><span class="font-mono text-xs text-faint">{{ $i + 1 }}.</span> {{ $claim }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach (\App\TaskTypes\Types\DocumentationVerificationType::VERDICTS as $value => $label)
                    <label class="cursor-pointer rounded-md border border-line px-2.5 py-1 text-xs hover:border-line-strong has-[:checked]:border-primary has-[:checked]:bg-primary/15">
                        <input type="radio" name="answer[verdicts][{{ $i }}]" value="{{ $value }}" class="sr-only" @checked(($draft['verdicts'][$i] ?? null) === $value)> {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('answer.verdicts.'.$i)<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
        </fieldset>
    @endforeach
    @error('answer.verdicts')<p class="text-xs text-danger">{{ $message }}</p>@enderror
    <x-textarea name="answer[notes]" label="Notes (optional)" rows="4" :value="$draft['notes'] ?? ''" hint="For inaccurate claims, explain what is actually true." />
</div>
