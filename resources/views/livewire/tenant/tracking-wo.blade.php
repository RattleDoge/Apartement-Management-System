<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\WorkOrder;
use App\Models\WoFeedback;

new #[Layout('layouts.tenant')] class extends Component {

    public ?int   $selectedId   = null;
    public int    $fRating      = 0;
    public string $fComment     = '';
    public string $feedbackMsg  = '';
    public bool   $showFeedback = false;

    public function selectWo(int $id): void
    {
        $this->selectedId   = $id;
        $this->showFeedback = false;
        $this->feedbackMsg  = '';
        $this->fRating      = 0;
        $this->fComment     = '';
    }

    public function openFeedback(): void
    {
        $this->showFeedback = true;
    }

    public function submitFeedback(): void
    {
        $this->validate([
            'fRating'  => 'required|integer|min:1|max:5',
            'fComment' => 'nullable|string|max:500',
        ]);

        WoFeedback::updateOrCreate(
            ['work_order_id' => $this->selectedId, 'user_id' => auth()->id()],
            ['rating' => $this->fRating, 'comment' => $this->fComment]
        );

        $this->feedbackMsg  = 'Terima kasih atas penilaian Anda!';
        $this->showFeedback = false;
    }

    public function with(): array
    {
        $lotNo = auth()->user()?->tenant?->unit_number;

        $workOrders = WorkOrder::when($lotNo, fn($q) => $q->where('lot_no', $lotNo))
            ->orderByDesc('tanggal')
            ->get();

        $selectedWo = $this->selectedId
            ? $workOrders->firstWhere('id', $this->selectedId)
            : null;

        $existingFeedback = ($selectedWo && $selectedWo->status_comp === 'Work Order Close')
            ? WoFeedback::where('work_order_id', $this->selectedId)
                        ->where('user_id', auth()->id())
                        ->first()
            : null;

        return compact('workOrders', 'selectedWo', 'existingFeedback');
    }
}
?>

<div class="max-w-5xl mx-auto px-4 py-5">
    <h2 class="text-base font-semibold text-gray-800 mb-4">Tracking Work Order</h2>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

        {{-- List WO --}}
        <div class="lg:col-span-2 space-y-2">
            @forelse($workOrders as $wo)
            @php
                $statusColor = match($wo->status_comp) {
                    'Pesan Diterima'   => 'bg-gray-100 text-gray-600',
                    'Dalam Pengecekan' => 'bg-blue-100 text-blue-700',
                    'Dalam Proses'     => 'bg-amber-100 text-amber-700',
                    'Work Order Close' => 'bg-green-100 text-green-700',
                    default            => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <button wire:click="selectWo({{ $wo->id }})"
                    class="w-full text-left bg-white border rounded-xl px-4 py-3 shadow-sm hover:border-[#1a5c2e] transition-colors
                           {{ $selectedId === $wo->id ? 'border-[#1a5c2e] ring-1 ring-[#1a5c2e]' : 'border-gray-200' }}">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-semibold text-gray-800 leading-snug">{{ $wo->descs ?? $wo->jenis_wo }}</p>
                    <span class="shrink-0 text-[10px] px-1.5 py-0.5 rounded font-semibold {{ $statusColor }}">
                        {{ $wo->status_comp }}
                    </span>
                </div>
                <p class="text-[10px] text-gray-400 mt-1 font-mono">{{ $wo->no_wo }}</p>
                <p class="text-[10px] text-gray-400">{{ $wo->tanggal?->format('d M Y') }}</p>
            </button>
            @empty
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-8 text-center text-xs text-gray-400">
                Belum ada Work Order.
            </div>
            @endforelse
        </div>

        {{-- Detail WO --}}
        <div class="lg:col-span-3">
            @if($selectedWo)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                {{-- Timeline header --}}
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                    <p class="text-xs font-semibold text-gray-700">{{ $selectedWo->no_wo }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $selectedWo->jenis_wo }} · {{ $selectedWo->sub_jenis_wo }}</p>
                </div>

                {{-- Timeline steps --}}
                <div class="px-4 py-4">
                    @php
                        $steps = [
                            ['label' => 'Pesan Diterima',   'date' => $selectedWo->tanggal,      'done' => true],
                            ['label' => 'Dalam Pengecekan', 'date' => $selectedWo->balas_at,     'done' => in_array($selectedWo->status_comp, ['Dalam Pengecekan','Dalam Proses','Work Order Close'])],
                            ['label' => 'Dalam Proses',     'date' => $selectedWo->work_started, 'done' => in_array($selectedWo->status_comp, ['Dalam Proses','Work Order Close'])],
                            ['label' => 'Selesai',          'date' => $selectedWo->work_closed,  'done' => $selectedWo->status_comp === 'Work Order Close'],
                        ];
                    @endphp
                    <ol class="relative border-l-2 border-gray-200 ml-3 space-y-5">
                        @foreach($steps as $step)
                        <li class="ml-5">
                            <span class="absolute -left-[9px] flex items-center justify-center w-4 h-4 rounded-full
                                {{ $step['done'] ? 'bg-[#1a5c2e]' : 'bg-gray-200' }}">
                                @if($step['done'])
                                <svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                @endif
                            </span>
                            <p class="text-xs font-semibold {{ $step['done'] ? 'text-gray-800' : 'text-gray-400' }}">
                                {{ $step['label'] }}
                            </p>
                            @if($step['date'])
                            <p class="text-[10px] text-gray-400">
                                {{ $step['date'] instanceof \Carbon\Carbon ? $step['date']->format('d M Y H:i') : \Carbon\Carbon::parse($step['date'])->format('d M Y H:i') }}
                            </p>
                            @endif
                        </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Description --}}
                <div class="px-4 pb-4 space-y-2">
                    @if($selectedWo->descs)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Deskripsi</p>
                        <p class="text-xs text-gray-700 leading-relaxed">{{ $selectedWo->descs }}</p>
                    </div>
                    @endif
                    @if($selectedWo->action_taken)
                    <div class="bg-green-50 rounded-lg p-3">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Tindakan</p>
                        <p class="text-xs text-gray-700 leading-relaxed">{{ $selectedWo->action_taken }}</p>
                    </div>
                    @endif
                    @if($selectedWo->assign_staff)
                    <p class="text-[11px] text-gray-500">Teknisi: <strong>{{ $selectedWo->assign_staff }}</strong></p>
                    @endif
                </div>

                {{-- Feedback section --}}
                @if($selectedWo->status_comp === 'Work Order Close')
                <div class="px-4 pb-4 border-t border-gray-100 pt-3">
                    @if($feedbackMsg)
                    <p class="text-xs text-green-700 bg-green-50 rounded-lg px-3 py-2">{{ $feedbackMsg }}</p>
                    @elseif($existingFeedback)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-[10px] text-gray-400 mb-1">Penilaian Anda</p>
                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                            <span class="text-lg {{ $i <= $existingFeedback->rating ? 'text-yellow-400' : 'text-gray-200' }}">★</span>
                            @endfor
                        </div>
                        @if($existingFeedback->comment)
                        <p class="text-xs text-gray-600 mt-1">{{ $existingFeedback->comment }}</p>
                        @endif
                    </div>
                    @elseif($showFeedback)
                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-700">Beri Penilaian</p>
                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                            <button type="button"
                                    wire:click="$set('fRating', {{ $i }})"
                                    class="text-2xl transition-colors {{ $i <= $fRating ? 'text-yellow-400' : 'text-gray-300 hover:text-yellow-300' }}">
                                ★
                            </button>
                            @endfor
                        </div>
                        @error('fRating') <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p> @enderror
                        <textarea wire:model="fComment" rows="2" placeholder="Komentar (opsional)..."
                                  class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] resize-none"></textarea>
                        <div class="flex gap-2 mt-1">
                            <button wire:click="submitFeedback"
                                    wire:loading.attr="disabled"
                                    style="background:#1a5c2e; color:#fff; border:none; cursor:pointer;"
                                    class="px-4 py-1.5 text-xs rounded-lg font-semibold transition-opacity disabled:opacity-50">
                                <span wire:loading.remove wire:target="submitFeedback">Kirim</span>
                                <span wire:loading wire:target="submitFeedback">Menyimpan...</span>
                            </button>
                            <button wire:click="$set('showFeedback', false)"
                                    class="px-4 py-1.5 bg-gray-100 text-gray-600 text-xs rounded-lg hover:bg-gray-200 transition-colors">
                                Batal
                            </button>
                        </div>
                    </div>
                    @else
                    <button wire:click="openFeedback"
                            class="flex items-center gap-1.5 text-xs text-[#1a5c2e] border border-[#1a5c2e] rounded-lg px-3 py-1.5 hover:bg-[#e8f5e9] transition-colors font-medium">
                        ★ Beri Penilaian WO
                    </button>
                    @endif
                </div>
                @endif
            </div>
            @else
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center text-xs text-gray-400">
                Pilih Work Order untuk melihat detail.
            </div>
            @endif
        </div>
    </div>
</div>
