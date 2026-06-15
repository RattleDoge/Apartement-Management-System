@props(['label', 'value', 'icon', 'color' => 'indigo', 'change' => null, 'changeType' => 'up'])

@php
    $colors = [
        'indigo' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900 dark:text-indigo-300',
        'green'  => 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300',
        'yellow' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-300',
        'red'    => 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300',
    ];
    $colorClass = $colors[$color] ?? $colors['indigo'];
    $icons = [
        'users'   => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        'chart'   => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'cash'    => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        'check'   => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
    $iconPath = $icons[$icon] ?? $icons['chart'];
@endphp

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
    <div class="flex-shrink-0 p-3 rounded-xl {{ $colorClass }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
        </svg>
    </div>
    <div class="flex-1 min-w-0">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $value }}</p>
        @if ($change)
            <p class="text-xs mt-1 {{ $changeType === 'up' ? 'text-green-500' : 'text-red-500' }}">
                {{ $changeType === 'up' ? '↑' : '↓' }} {{ $change }} dari bulan lalu
            </p>
        @endif
    </div>
</div>
