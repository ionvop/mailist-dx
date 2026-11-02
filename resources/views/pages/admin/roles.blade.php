@extends('layouts.app')

@section('title', 'Role administration - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="Manage user roles" subtitle="Assign approver or moderator access to an existing account." />

    <div class="space-y-6">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert variant="error">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-foreground">Current moderators and approvers</h2>

            <div class="overflow-x-auto rounded-lg border border-border">
                <table class="w-full min-w-[36rem] divide-y divide-border text-left text-sm">
                    <thead class="bg-background-accent/40 text-xs uppercase text-foreground-accent">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">Username</th>
                            <th scope="col" class="px-4 py-3 font-medium">Current role</th>
                            <th scope="col" class="px-4 py-3 font-medium">Change role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($staffUsers as $staffUser)
                            <tr>
                                <td class="px-4 py-3 font-medium text-foreground">{{ $staffUser->username }}</td>
                                <td class="px-4 py-3 text-foreground-accent">{{ $staffUser->role->label() }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.roles.store') }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="username" value="{{ $staffUser->username }}">
                                        <select
                                            name="role"
                                            aria-label="Role for {{ $staffUser->username }}"
                                            class="min-w-32 rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-primary-accent focus:ring-2 focus:ring-primary-accent/40"
                                        >
                                            <option value="approver" @selected($staffUser->role === \App\Enums\UserRole::Approver)>Approver</option>
                                            <option value="moderator" @selected($staffUser->role === \App\Enums\UserRole::Moderator)>Moderator</option>
                                            <option value="member">Member</option>
                                        </select>
                                        <x-button type="submit" class="w-auto shrink-0">Save role</x-button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-foreground-accent">
                                    No moderators or approvers yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
            <h2 class="mb-6 text-lg font-semibold text-foreground">Assign a role</h2>
            <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-6">
                @csrf

                <x-input
                    name="username"
                    label="Username"
                    autocomplete="off"
                    required
                />

                <div class="space-y-1.5">
                    <label for="role" class="block text-sm font-medium text-foreground">Role</label>
                    <select
                        name="role"
                        id="role"
                        required
                        class="w-full rounded-lg border bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-primary-accent focus:ring-2 focus:ring-primary-accent/40 {{ $errors->has('role') ? 'border-destructive focus:border-destructive focus:ring-destructive/40' : 'border-border' }}"
                    >
                        <option value="approver" @selected(old('role', 'approver') === 'approver')>Approver</option>
                        <option value="moderator" @selected(old('role') === 'moderator')>Moderator</option>
                    </select>
                    @error('role')
                        <p class="text-xs text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end border-t border-border pt-6">
                    <x-button type="submit" class="w-auto">Set role</x-button>
                </div>
            </form>
        </div>
    </div>
@endsection