@extends('layouts.app')

@section('title', 'Verify your email - '.config('app.name', 'mailist DX'))

@section('content')
    <div class="mx-auto w-full max-w-md">
        <x-page-header title="Check your email" subtitle="Enter the 6-digit code we sent you to finish signing in." />

        <div class="card card-border bg-base-200/40 p-6 sm:p-8">
            <p class="mb-4 text-sm text-foreground-accent">
                We sent a code to <span class="font-medium text-foreground">{{ $email }}</span>.
            </p>

            @if (session('status'))
                <x-alert class="mb-4">{{ session('status') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert variant="error" class="mb-4">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form method="POST" action="{{ route('login.verify.submit') }}" class="space-y-4">
                @csrf

                <x-input
                    name="code"
                    label="Login code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="123456"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    :autofocus="true"
                    :required="true"
                    class="[&_input]:text-center [&_input]:text-2xl [&_input]:font-bold [&_input]:tracking-[0.5em]"
                />

                <x-button type="submit">Verify &amp; sign in</x-button>
            </form>

            <p class="mt-6 text-center text-sm text-foreground-accent">
                Didn't get a code?
                <a href="{{ route('login') }}" class="font-medium text-primary-accent hover:brightness-110">Try again</a>
            </p>
        </div>
    </div>
@endsection
