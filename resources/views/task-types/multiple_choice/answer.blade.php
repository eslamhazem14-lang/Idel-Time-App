@php $multiple = $task->payload['multiple'] ?? false; $selected = array_map('intval', (array) ($draft['choices'] ?? [])); @endphp
<fieldset>
    <legend class="label">{{ $multiple ? 'Select all that apply' : 'Select one answer' }}</legend>
    <div class="space-y-2">
        @foreach ($task->payload['options'] ?? [] as $i => $option)
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line bg-bg px-3 py-2.5 text-sm hover:border-line-strong has-[:checked]:border-primary has-[:checked]:bg-primary/10">
                <input type="{{ $multiple ? 'checkbox' : 'radio' }}" name="answer[choices][]" value="{{ $i }}" class="mt-0.5 border-line bg-bg text-primary {{ $multiple ? 'rounded' : '' }}" @checked(in_array($i, $selected, true))>
                <span>{{ $option }}</span>
            </label>
        @endforeach
    </div>
    @error('answer.choices')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
    @error('answer.choices.*')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
</fieldset>
