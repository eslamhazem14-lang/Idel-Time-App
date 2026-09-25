<x-layouts.app title="Task templates">
    <x-page-header title="Task templates" subtitle="Starting points requesters can pick when posting a task.">
        <a href="{{ route('admin.templates.create') }}" class="btn-primary"><x-icon name="plus" /> New template</a>
    </x-page-header>
    <div class="card overflow-x-auto">
        @if ($templates->isEmpty())
            <x-empty icon="file" title="No templates" text="Templates make it faster for requesters to post consistent tasks.">
                <a href="{{ route('admin.templates.create') }}" class="btn-primary">Create template</a>
            </x-empty>
        @else
            <table class="table">
                <thead><tr><th>Name</th><th>Type</th><th>Category</th><th>Estimate</th><th>Suggested reward</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($templates as $t)
                        <tr>
                            <td class="font-medium">{{ $t->name }}</td>
                            <td>{{ app(\App\TaskTypes\TaskTypeRegistry::class)->get($t->type)->label() }}</td>
                            <td>{{ $t->category->name }}</td>
                            <td>{{ $t->estimated_minutes }} min</td>
                            <td class="mono-num">{{ $t->suggested_reward->format() }}</td>
                            <td>@if ($t->is_active)<x-badge color="green">Active</x-badge>@else<x-badge>Inactive</x-badge>@endif</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.templates.edit', $t) }}" class="btn-ghost btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('admin.templates.destroy', $t) }}" onsubmit="return confirm('Delete template?')">@csrf @method('DELETE')<button class="btn-ghost btn-sm text-danger">Delete</button></form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $templates->links() }}</div>
        @endif
    </div>
</x-layouts.app>
