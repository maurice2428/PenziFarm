@php
    $hour = now('Africa/Nairobi')->hour;

    $greeting = match (true) {
        $hour >= 5 && $hour < 12 => 'Good morning',
        $hour >= 12 && $hour < 18 => 'Good afternoon',
        $hour >= 18 && $hour < 21 => 'Good evening',
        default => 'Good night',
    };

    $name = auth()->user()?->name ?? 'User';
@endphp

<div class="hidden items-center whitespace-nowrap text-sm font-semibold text-gray-700 dark:text-gray-200 xl:flex">
    {{ $greeting }}, {{ $name }}
</div>
