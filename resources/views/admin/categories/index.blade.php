<x-layouts.app title="Categories">
    <x-page-header title="Categories" />
    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>Name</th><th>Icon</th><th>Order</th><th>Active</th><th>Tasks</th><th></th></tr></thead>
                <tbody>
                    @forelse ($categories as $c)
                        <tr x-data="{ edit: false }">
                            <td colspan="6" class="!p-0">
                                <div x-show="!edit" class="grid grid-cols-[1fr_auto] items-center gap-3 px-4 py-3 sm:grid-cols-[2fr_1fr_1fr_1fr_1fr_auto]">
                                    <div><div class="font-medium">{{ $c->name }}</div><div class="text-xs text-faint">{{ $c->description }}</div></div>
                                    <div class="hidden sm:block"><x-icon :name="$c->icon" class="text-muted" /></div>
                                    <div class="hidden sm:block mono-num">{{ $c->sort_order }}</div>
                                    <div class="hidden sm:block">@if ($c->is_active)<x-badge color="green">Active</x-badge>@else<x-badge>Inactive</x-badge>@endif</div>
                                    <div class="hidden sm:block mono-num">{{ $c->tasks_count }}</div>
                                    <div class="flex gap-1">
                                        <button class="btn-ghost btn-sm" @click="edit = true">Edit</button>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" onsubmit="return confirm('Delete (or deactivate) this category?')">@csrf @method('DELETE')<button class="btn-ghost btn-sm text-danger">Delete</button></form>
                                    </div>
                                </div>
                                <form x-show="edit" x-cloak method="POST" action="{{ route('admin.categories.update', $c) }}" class="grid gap-2 bg-surface-2/50 px-4 py-3 sm:grid-cols-[2fr_2fr_1fr_1fr_auto]">
                                    @csrf @method('PUT')
                                    <input name="name" value="{{ $c->name }}" class="field" required>
                                    <input name="description" value="{{ $c->description }}" class="field" placeholder="Description">
                                    <input name="icon" value="{{ $c->icon }}" class="field" placeholder="icon">
                                    <input name="sort_order" type="number" value="{{ $c->sort_order }}" class="field">
                                    <div class="flex items-center gap-2">
                                        <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($c->is_active) class="rounded border-line bg-bg text-primary"> Active</label>
                                        <button class="btn-primary btn-sm">Save</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="tag" title="No categories" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="card card-pad space-y-3 self-start">
            @csrf
            <h2 class="text-sm font-semibold">New category</h2>
            <x-input name="name" label="Name" required />
            <x-input name="description" label="Description" />
            <x-input name="icon" label="Icon" placeholder="code, sparkles, globe, bug…" />
            <x-input name="sort_order" type="number" label="Sort order" value="0" />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded border-line bg-bg text-primary"> Active</label>
            <button class="btn-primary w-full">Create category</button>
        </form>
    </div>
</x-layouts.app>
