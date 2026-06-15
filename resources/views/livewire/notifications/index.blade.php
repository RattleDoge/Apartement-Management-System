<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new class extends Component {
    use WithPagination;

    public string $filterType = '';

    public function layout(): string
    {
        return auth()->user()?->role === 'tenant' ? 'layouts.tenant' : 'layouts.karyawan';
    }

    public function updatedFilterType(): void { $this->resetPage(); }

    public function markRead(string $id): void
    {
        $notif = auth()->user()->notifications()->find($id);
        if ($notif) $notif->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function with(): array
    {
        $isTenant = auth()->user()?->role === 'tenant';

        $query = auth()->user()->notifications();

        if ($isTenant) {
            // Tenants see WO status + greeting notifications
            $query->whereIn('type', [
                'App\Notifications\WoStatusNotification',
                'App\Notifications\GreetingNotification',
            ]);
        }

        if ($this->filterType) {
            $query->where('type', 'like', "%{$this->filterType}%");
        }

        $notifications = $query->latest()->paginate(20);

        return compact('notifications', 'isTenant');
    }
}
?>

<div class="{{ $isTenant ? 'min-h-screen bg-gray-50' : '' }}">

    {{-- Page Header --}}
    <div class="{{ $isTenant ? 'max-w-5xl mx-auto px-6 py-4' : 'px-6 py-4' }} border-b border-gray-200 bg-white flex items-center justify-between">
        <div>
            <h2 class="text-base font-semibold text-gray-800">
                {{ $isTenant ? 'Semua Notifikasi' : 'Notifikasi Sistem' }}
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ $isTenant ? 'Informasi, status Work Order, dan pengumuman dari pengelola' : 'Riwayat notifikasi eskalasi WO dan sistem' }}
            </p>
        </div>
        @if(auth()->user()->unreadNotifications()->count() > 0)
        <button wire:click="markAllRead"
                class="px-3 py-1.5 text-xs bg-[#1a5c2e] text-white rounded hover:bg-[#154d26] transition-colors">
            Tandai Semua Dibaca
        </button>
        @endif
    </div>

    <div class="{{ $isTenant ? 'max-w-5xl mx-auto px-6 py-5' : 'p-6' }}">

        {{-- Notification List --}}
        <div class="space-y-3">
            @forelse($notifications as $notif)
            @php
                $d         = $notif->data;
                $isUnread  = is_null($notif->read_at);
                $type      = $d['type'] ?? 'escalation';
                $isGreeting = $type === 'greeting';
                $isWoStatus = $type === 'wo_status';
            @endphp

            <div class="bg-white rounded-xl border shadow-sm overflow-hidden
                {{ $isUnread ? ($isGreeting ? 'border-green-200' : 'border-blue-200') : 'border-gray-200' }}">

                {{-- Cover image for greeting --}}
                @if($isGreeting && !empty($d['cover_img']))
                <img src="{{ \Illuminate\Support\Facades\Storage::url($d['cover_img']) }}"
                     alt="" class="w-full max-h-40 object-cover">
                @endif

                <div class="px-4 py-3 flex items-start gap-3">
                    {{-- Icon --}}
                    <div class="shrink-0 mt-0.5">
                        @if($isGreeting)
                        <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center text-sm">📢</div>
                        @elseif($isWoStatus)
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-sm">🔧</div>
                        @else
                        <div class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center text-sm">⚠️</div>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        {{-- Badge + time --}}
                        <div class="flex items-center gap-2 mb-1">
                            @if($isGreeting)
                            <span class="text-[10px] px-1.5 py-0.5 bg-green-100 text-green-700 rounded font-semibold">
                                {{ $d['jenis'] ?? 'Pengumuman' }}
                            </span>
                            @elseif($isWoStatus)
                            <span class="text-[10px] px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded font-semibold">
                                WO {{ $d['status'] ?? '' }}
                            </span>
                            @else
                            <span class="text-[10px] px-1.5 py-0.5 bg-yellow-100 text-yellow-700 rounded font-semibold">
                                Eskalasi L{{ $d['level'] ?? 1 }}
                            </span>
                            @endif
                            @if($isUnread)
                            <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                            @endif
                            <span class="text-[10px] text-gray-400 ml-auto">{{ $notif->created_at->diffForHumans() }}</span>
                        </div>

                        {{-- Message --}}
                        <p class="text-sm {{ $isUnread ? 'font-semibold text-gray-800' : 'text-gray-600' }} leading-snug">
                            {{ $d['message'] ?? '-' }}
                        </p>

                        {{-- Sub-info --}}
                        @if($isWoStatus && !empty($d['no_wo']))
                        <p class="text-[11px] text-gray-400 mt-0.5 font-mono">{{ $d['no_wo'] }}</p>
                        @endif
                        @if(!$isGreeting && !$isWoStatus && !empty($d['descs']))
                        <p class="text-[11px] text-gray-500 mt-0.5 truncate">{{ $d['descs'] }}</p>
                        @endif
                    </div>

                    @if($isUnread)
                    <button wire:click="markRead('{{ $notif->id }}')"
                            class="shrink-0 text-[10px] text-gray-400 hover:text-[#1a5c2e] px-2 py-1 rounded border border-gray-200 hover:border-[#1a5c2e] transition-colors">
                        ✓ Baca
                    </button>
                    @endif
                </div>
            </div>
            @empty
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center">
                <p class="text-sm text-gray-500">Tidak ada notifikasi.</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($notifications->hasPages())
        <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
            <span>{{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} dari {{ $notifications->total() }}</span>
            <div class="flex items-center gap-1">
                <button wire:click="previousPage" @disabled($notifications->onFirstPage())
                        class="px-2 py-1 rounded border border-gray-300 hover:bg-gray-100 disabled:opacity-40">‹</button>
                <span class="px-2">{{ $notifications->currentPage() }} / {{ $notifications->lastPage() }}</span>
                <button wire:click="nextPage" @disabled(!$notifications->hasMorePages())
                        class="px-2 py-1 rounded border border-gray-300 hover:bg-gray-100 disabled:opacity-40">›</button>
            </div>
        </div>
        @endif
    </div>
</div>
