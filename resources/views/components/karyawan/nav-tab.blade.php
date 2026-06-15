@props(['route', 'active' => false])

@php
    try {
        $href = route($route);
    } catch (\Exception $e) {
        $href = '#';
    }
@endphp

<a href="{{ $href }}"
   class="px-4 py-2.5 inline-block border-b-2 text-sm transition-colors whitespace-nowrap
          {{ $active
              ? 'border-[#1a5c2e] text-[#1a5c2e] font-semibold'
              : 'border-transparent text-gray-600 hover:text-[#1a5c2e] hover:border-gray-300' }}">
    {{ $slot }}
</a>
