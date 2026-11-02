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

        <div class="rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
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