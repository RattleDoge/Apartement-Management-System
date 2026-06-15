<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\Tenant;
use App\Notifications\DirectMessageNotification;

new #[Layout('layouts.karyawan')] class extends Component {

    public string $fTarget     = 'all';   // 'all' | 'unit' | 'user'
    public string $fLotNo      = '';
    public string $fUserId     = '';
    public string $fMessage    = '';
    public string $savedMsg    = '';
    public string $savedColor  = 'green';

    public array  $suggestions = [];

    private function canManage(): bool
    {
        return auth()->user()->role !== 'tenant';
    }

    public function updatedFLotNo(): void
    {
        if (strlen($this->fLotNo) < 2) { $this->suggestions = []; return; }
        $this->suggestions = Tenant::whereRaw('UPPER(unit_number) LIKE ?', ['%' . strtoupper($this->fLotNo) . '%'])
            ->with('user')->limit(6)->get()
            ->map(fn($t) => ['lot' => $t->unit_number, 'name' => $t->user?->name ?? '—', 'user_id' => $t->user_id])
            ->toArray();
    }

    public function selectSuggestion(string $lot, int $userId): void
    {
        $this->fLotNo      = $lot;
        $this->fUserId     = $userId;
        $this->suggestions = [];
    }

    public function send(): void
    {
        if (! $this->canManage()) return;

        $this->validate([
            'fMessage' => 'required|string|min:5|max:1000',
        ]);

        $sender  = auth()->user()->name;
        $notif   = new DirectMessageNotification($this->fMessage, $sender);
        $count   = 0;

        if ($this->fTarget === 'all') {
            $users = User::where('role', 'tenant')->get();
            foreach ($users as $u) { $u->notify($notif); $count++; }
        } elseif ($this->fTarget === 'unit' && $this->fUserId) {
            $user = User::find($this->fUserId);
            if ($user) { $user->notify($notif); $count = 1; }
        } elseif ($this->fTarget === 'user' && $this->fUserId) {
            $user = User::find($this->fUserId);
            if ($user) { $user->notify($notif); $count = 1; }
        }

        $this->savedMsg   = "Pesan berhasil dikirim ke {$count} akun tenant.";
        $this->savedColor = 'green';
        $this->fMessage   = '';
        $this->fLotNo     = '';
        $this->fUserId    = '';
    }

    public function with(): array
    {
        $canManage   = $this->canManage();
        $tenantCount = User::where('role', 'tenant')->count();

        // Sent history (last 20)
        $history = \DB::table('notifications')
            ->where('type', 'App\Notifications\DirectMessageNotification')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($n) => array_merge(['id' => $n->id, 'created_at' => $n->created_at], json_decode($n->data, true)));

        return compact('canManage', 'tenantCount', 'history');
    }
}
?>

<div class="p-5">
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            BROADCAST PESAN KE TENANT
        </div>
    </div>

    @if(!$canManage)
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-xs text-amber-700">
        Hanya AM dan CS yang dapat mengirim broadcast pesan.
    </div>
    @else

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Form --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Kirim Pesan</p>
            </div>
            <form wire:submit="send" class="px-4 py-4 space-y-4">

                @if($savedMsg)
                <div class="bg-green-50 border border-green-200 rounded-lg px-3 py-2 text-xs text-green-700">
                    {{ $savedMsg }}
                </div>
                @endif

                {{-- Target --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Kirim ke</label>
                    <div class="flex gap-3">
                        @foreach(['all' => 'Semua Tenant (' . $tenantCount . ')', 'unit' => 'Unit Tertentu'] as $val => $label)
                        <label class="flex items-center gap-1.5 text-xs cursor-pointer">
                            <input wire:model.live="fTarget" type="radio" value="{{ $val }}" class="accent-[#1a5c2e]">
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Unit search (if specific) --}}
                @if($fTarget === 'unit')
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Cari Unit / Tenant</label>
                    <input wire:model.live.debounce.300ms="fLotNo" type="text"
                           placeholder="Ketik nomor unit..."
                           class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    @if(count($suggestions))
                    <div class="absolute z-20 left-0 right-0 bg-white border border-gray-200 rounded shadow-lg mt-1">
                        @foreach($suggestions as $s)
                        <button type="button"
                                wire:click="selectSuggestion('{{ $s['lot'] }}', {{ $s['user_id'] }})"
                                class="w-full text-left px-3 py-2 text-xs hover:bg-[#e8f5e9] flex items-center justify-between">
                            <span class="font-semibold font-mono">{{ $s['lot'] }}</span>
                            <span class="text-gray-500">{{ $s['name'] }}</span>
                        </button>
                        @endforeach
                    </div>
                    @endif
                    @if($fUserId)
                    <p class="text-[10px] text-green-600 mt-0.5">✓ Tenant dipilih</p>
                    @endif
                </div>
                @endif

                {{-- Message --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Pesan</label>
                    <textarea wire:model="fMessage" rows="4"
                              class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] resize-none"
                              placeholder="Tulis pesan untuk tenant..."></textarea>
                    @error('fMessage') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                        class="w-full py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-[#154d26] transition-colors">
                    Kirim Notifikasi
                </button>
            </form>
        </div>

        {{-- History --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Riwayat Pesan Terakhir</p>
            </div>
            @if(empty($history))
            <div class="px-4 py-8 text-center text-xs text-gray-400">Belum ada pesan terkirim.</div>
            @else
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @foreach($history as $h)
                <div class="px-4 py-3">
                    <p class="text-xs text-gray-700 leading-snug">{{ $h['message'] ?? '—' }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">
                        Oleh {{ $h['sent_by'] ?? '—' }} ·
                        {{ isset($h['created_at']) ? \Carbon\Carbon::parse($h['created_at'])->diffForHumans() : '' }}
                    </p>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

