@extends('layouts.app')

@section('title', 'Notifications - '.config('app.name', 'mailist DX'))

@php
    $typeMeta = [
        \App\Notifications\UserFollowed::class => ['icon' => 'user-plus', 'tint' => 'bg-sky-500/15 text-sky-300'],
        \App\Notifications\ProfileCommentCreated::class => ['icon' => 'message-square', 'tint' => 'bg-violet-500/15 text-violet-300'],
        \App\Notifications\ChartCommentCreated::class => ['icon' => 'message-square', 'tint' => 'bg-blue-500/15 text-blue-300'],
        \App\Notifications\ChartDifficultyCommentCreated::class => ['icon' => 'message-square', 'tint' => 'bg-indigo-500/15 text-indigo-300'],
        \App\Notifications\ImprovementSuggested::class => ['icon' => 'lightbulb', 'tint' => 'bg-amber-500/15 text-amber-300'],
        \App\Notifications\ImprovementResolved::class => ['icon' => 'circle-check', 'tint' => 'bg-emerald-500/15 text-emerald-300'],
        \App\Notifications\ChartStatusChanged::class => ['icon' => 'badge-check', 'tint' => 'bg-emerald-500/15 text-emerald-300'],
    ];
    $fallbackMeta = ['icon' => 'bell', 'tint' => 'bg-background-accent text-foreground-accent'];
@endphp

@section('content')
    <x-page-header title="Notifications" subtitle="Recent activity relevant to you." />

    <div class="space-y-6">
        @if ($notifications->isEmpty())
            <x-placeholder
                title="No notifications yet"
                description="Follows, comments, suggestions, and chart status changes will appear here."
                icon="bell"
                height="h-64" />
        @else
            @if ($unreadCount > 0)
                <div class="flex justify-end">
                    <form method="POST" action="{{ route('notifications.readAll') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-background-accent px-3 py-1.5 text-xs font-semibold text-foreground transition hover:bg-accent">
                            <x-lucide-check class="h-3.5 w-3.5" /> Mark all as read
                        </button>
                    </form>
                </div>
            @endif

            <div class="divide-y divide-border overflow-hidden rounded-2xl border border-border bg-background-accent/40">
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $meta = $typeMeta[$notification->type] ?? $fallbackMeta;
                        $isUnread = $notification->read_at === null;
                    @endphp

                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                        @csrf
                        @method('PATCH')

                        <button type="submit"
                            class="flex w-full items-start gap-4 p-4 text-left transition hover:bg-accent/40 {{ $isUnread ? 'bg-background-accent/70' : '' }}">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $meta['tint'] }}">
                                <x-dynamic-component :component="'lucide-'.$meta['icon']" class="h-4.5 w-4.5" />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-foreground">{{ $data['title'] ?? 'Notification' }}</span>

                                    @if ($isUnread)
                                        <span class="h-2 w-2 shrink-0 rounded-full bg-sky-400" title="Unread"></span>
                                    @endif
                                </span>

                                <span class="mt-0.5 block text-sm text-foreground-accent line-clamp-2">
                                    {{ $data['body'] ?? '' }}
                                </span>

                                <x-local-time :value="$notification->created_at" class="mt-1 block text-xs text-foreground-accent/70" />
                            </span>

                            <x-lucide-chevron-right class="h-4 w-4 shrink-0 text-foreground-accent" />
                        </button>
                    </form>
                @endforeach
            </div>

            <div>
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
