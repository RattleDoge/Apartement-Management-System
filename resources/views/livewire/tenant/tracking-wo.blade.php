<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\WorkOrder;
use App\Models\WoFeedback;

new #[Layout('layouts.tenant')] class extends Component {
    use WithFileUploads;

    public ?int   $selectedId    = null;
    public int    $fRating       = 0;
    public string $fComment      = '';
    public string $feedbackMsg   = '';
    public bool   $showFeedback  = false;

    public        $buktiWoFile   = null;
    public string $uploadBuktiMsg = '';
    public bool   $uploadBuktiOk  = false;

    public function selectWo(int $id): void
    {
        $this->selectedId    = $id;
        $this->showFeedback  = false;
        $this->feedbackMsg   = '';
        $this->fRating       = 0;
        $this->fComment      = '';
        $this->buktiWoFile   = null;
        $this->uploadBuktiMsg = '';
        $this->uploadBuktiOk  = false;
    }

    public function uploadBuktiWo(): void
    {
        $this->validate([
            'buktiWoFile' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'buktiWoFile.required' => 'Pilih file bukti pembayaran.',
            'buktiWoFile.image'    => 'File harus berupa gambar.',
            'buktiWoFile.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        $wo = WorkOrder::findOrFail($this->selectedId);

        if ($wo->bukti_bayar_wo && Storage::disk('public')->exists($wo->bukti_bayar_wo)) {
            Storage::disk('public')->delete($wo->bukti_bayar_wo);
        }

        $path = $this->buktiWoFile->store('bukti-bayar-wo', 'public');
        $wo->update([
            'bukti_bayar_wo'     => $path,
            'tgl_bukti_bayar_wo' => now(),
            'fin_status'         => null,
            'fin_by'             => null,
            'fin_at'             => null,
            'is_berbayar'        => true,
        ]);

        $this->buktiWoFile    = null;
        $this->uploadBuktiMsg = 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi dari CS/Finance.';
        $this->uploadBuktiOk  = true;
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

                {{-- Pembayaran WO --}}
                @php
                    $woItems  = $selectedWo->item_service ?? [];
                    $woTotal  = collect($woItems)->sum(fn($i) => ($i['harga'] ?? 0) * ($i['qty'] ?? 1));
                    $finSt    = $selectedWo->fin_status;
                @endphp
                @if(count($woItems) > 0 && $woTotal > 0)
                <div class="mx-4 mb-4 rounded-xl border overflow-hidden
                    {{ $finSt === 'Approved' ? 'border-green-300 bg-green-50'
                     : ($finSt === 'Rejected' ? 'border-red-300 bg-red-50'
                     : 'border-amber-300 bg-amber-50') }}">
                    <div class="flex items-center justify-between px-4 py-2.5 border-b
                        {{ $finSt === 'Approved' ? 'border-green-200 bg-green-100'
                         : ($finSt === 'Rejected' ? 'border-red-200 bg-red-100'
                         : 'border-amber-200 bg-amber-100') }}">
                        <span class="text-xs font-bold {{ $finSt === 'Approved' ? 'text-green-800' : ($finSt === 'Rejected' ? 'text-red-800' : 'text-amber-800') }}">
                            Tagihan Work Order
                        </span>
                        @if($finSt === 'Approved')
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-600 text-white">✔ LUNAS</span>
                        @elseif($finSt === 'Rejected')
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-600 text-white">✖ DITOLAK</span>
                        @elseif($selectedWo->bukti_bayar_wo)
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-600 text-white">Bukti Sedang Diverifikasi</span>
                        @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-600 text-white">Menunggu Pembayaran</span>
                        @endif
                    </div>
                    <div class="px-4 py-3 space-y-2">
                        @if($selectedWo->keterangan_biaya)
                        <p class="text-[11px] text-gray-600 italic">{{ $selectedWo->keterangan_biaya }}</p>
                        @endif

                        @if(count($woItems) > 0)
                        <table class="w-full text-[11px] border-collapse">
                            <thead>
                                <tr class="bg-white/60">
                                    <th class="text-left px-2 py-1 border border-gray-200 font-semibold text-gray-600">Item</th>
                                    <th class="text-center px-2 py-1 border border-gray-200 w-12 font-semibold text-gray-600">Qty</th>
                                    <th class="text-right px-2 py-1 border border-gray-200 w-28 font-semibold text-gray-600">Harga</th>
                                    <th class="text-right px-2 py-1 border border-gray-200 w-28 font-semibold text-gray-600">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($woItems as $item)
                                @php $sub = ($item['harga'] ?? 0) * ($item['qty'] ?? 1); @endphp
                                <tr class="bg-white/40">
                                    <td class="px-2 py-1 border border-gray-200">{{ $item['nama'] }}</td>
                                    <td class="px-2 py-1 border border-gray-200 text-center">{{ $item['qty'] }}</td>
                                    <td class="px-2 py-1 border border-gray-200 text-right">Rp {{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-2 py-1 border border-gray-200 text-right font-semibold">Rp {{ number_format($sub, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-bold">
                                    <td colspan="3" class="px-2 py-1 border border-gray-200 text-right">Total</td>
                                    <td class="px-2 py-1 border border-gray-200 text-right text-blue-700">Rp {{ number_format($woTotal, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                        @endif

                        {{-- Bukti bayar yang sudah diupload --}}
                        @if($selectedWo->bukti_bayar_wo)
                        <div class="mt-2">
                            <p class="text-[10px] text-gray-500 mb-1">Bukti pembayaran diupload: {{ $selectedWo->tgl_bukti_bayar_wo?->format('d/m/Y H:i') }}</p>
                            <img src="{{ asset('storage/' . $selectedWo->bukti_bayar_wo) }}"
                                 class="max-h-40 rounded border border-gray-200 object-contain">
                        </div>
                        @endif

                        @if($finSt === 'Rejected')
                        <div class="bg-red-100 rounded p-2 text-[11px] text-red-700">
                            <strong>Ditolak:</strong> {{ $selectedWo->fin_notes ?? 'Silakan hubungi CS.' }}
                        </div>
                        @endif

                        {{-- Upload bukti bayar (belum approved) --}}
                        @if($finSt !== 'Approved')
                        <div class="pt-2 border-t border-dashed border-current border-opacity-30">
                            @if($uploadBuktiMsg)
                            <p class="text-xs {{ $uploadBuktiOk ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50' }} rounded px-3 py-2 mb-2">
                                {{ $uploadBuktiMsg }}
                            </p>
                            @endif
                            <p class="text-[11px] font-semibold text-gray-700 mb-1">
                                {{ $selectedWo->bukti_bayar_wo ? 'Ganti Bukti Pembayaran' : 'Upload Bukti Pembayaran' }}
                            </p>
                            <input type="file" wire:model="buktiWoFile" accept="image/*"
                                   class="text-[11px] text-gray-600 block mb-2">
                            @if($buktiWoFile)
                            <img src="{{ $buktiWoFile->temporaryUrl() }}"
                                 class="max-h-32 rounded border border-gray-200 object-contain mb-2">
                            @endif
                            @error('buktiWoFile') <p class="text-red-500 text-[10px] mb-1">{{ $message }}</p> @enderror
                            <button wire:click="uploadBuktiWo"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded disabled:opacity-60">
                                <span wire:loading.remove wire:target="uploadBuktiWo">Upload</span>
                                <span wire:loading wire:target="uploadBuktiWo">Mengupload...</span>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

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
