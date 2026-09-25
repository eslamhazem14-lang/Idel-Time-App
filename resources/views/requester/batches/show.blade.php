<x-layouts.app :title="$batch->title">
    <x-page-header :title="$batch->title" :subtitle="$batch->category->name.' · '.$batch->total_tasks.' tasks · '.$batch->slots_per_task.' worker(s) each'" :back="route('requester.tasks.index')">
        <x-status :value="$batch->status" />
    </x-page-header>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Tasks" :value="$batch->total_tasks" />
        <x-stat label="Reward per item" :value="$batch->reward->format()" />
        <x-stat label="Fee per item" :value="$batch->platform_fee->format()" />
        <x-stat label="Total budget" :value="$batch->total_budget->format()" />
    </div>
    <div class="card mt-6 overflow-x-auto">
        <table class="table">
            <thead><tr><th>Task</th><th>Status</th><th>Progress</th><th>To review</th></tr></thead>
            <tbody>
                @foreach ($tasks as $task)
                    <tr>
                        <td><a href="{{ route('requester.tasks.show', $task) }}" class="hover:text-primary">{{ $task->title }}</a></td>
                        <td><x-status :value="$task->status" /></td>
                        <td class="mono-num">{{ $task->completed_slots }} / {{ $task->available_slots }}</td>
                        <td>{{ $task->pending_count ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $tasks->links() }}</div>
    </div>
</x-layouts.app>
