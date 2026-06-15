<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.tenant')] class extends Component {
    use WithPagination, WithFileUploads;

    public string $filterBulan = '';
    public string $filterTahun = '';
    public string $filterStatus = '';

    public ?int  $viewingId    = null;
    public bool  $showPanel    = false;
    public       $uploadBukti  = null;
    public string $uploadMsg   = '';
    public bool  $uploadOk     = false;

    public function mount(): void
    {
        $this->filterTahun = (string) now()->year;
    }

    public function updatedFilterBulan(): void  { $this->resetPage(); }
    public function updatedFilterTahun(): void  { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function with(): array
    {
        $tenant     = auth()->user()->tenant;
        $unitNumber = $tenant?->unit_number ?? '';

        $q = Invoice::where('debtor_acct', $unitNumber)->orderByDesc('inv_date');

        if ($this->filterBulan) {
            $q->where('bulan', $this->filterBulan);
        }
        if ($this->filterTahun) {
            $q->where('tahun', $this->filterTahun);
        }
        if ($this->filterStatus) {
            $q->where('status_bayar', $this->filterStatus);
        }

        $totalTagihan = (clone $q)->sum('amount');
        $totalLunas   = (clone $q)->where('status_bayar', 'Lunas')->sum('amount');
        $belumLunas   = (clone $q)->where('status_bayar', 'Belum Lunas')->sum('amount');

        return [
            'invoices'     => $q->paginate(10),
            'totalTagihan' => $totalTagihan,
            'totalLunas'   => $totalLunas,
            'belumLunas'   => $belumLunas,
            'unitNumber'   => $unitNumber,
            'viewing'      => $this->viewingId ? Invoice::find($this->viewingId) : null,
            'bulanOptions' => Invoice::bulanOptions(),
            'tahunOptions' => range(now()->year, now()->year - 3),
        ];
    }

    public function openDetail(int $id): void
    {
        $this->viewingId = $id;
        $this->showPanel = true;
    }

    public function closePanel(): void
    {
        $this->showPanel    = false;
        $this->viewingId    = null;
        $this->uploadBukti  = null;
        $this->uploadMsg    = '';
        $this->uploadOk     = false;
    }

    public function uploadBuktiPembayaran(): void
    {
        $this->validate([
            'uploadBukti' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'uploadBukti.required' => 'Pilih file bukti pembayaran.',
            'uploadBukti.image'    => 'File harus berupa gambar.',
            'uploadBukti.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        $inv = Invoice::findOrFail($this->viewingId);

        // Delete old bukti if exists
        if ($inv->bukti_bayar && Storage::disk('public')->exists($inv->bukti_bayar)) {
            Storage::disk('public')->delete($inv->bukti_bayar);
        }

        $path = $this->uploadBukti->store('bukti-bayar', 'public');

        $inv->update([
            'bukti_bayar'     => $path,
            'tgl_bukti_bayar' => now(),
        ]);

        $this->uploadBukti = null;
        $this->uploadMsg   = 'Bukti pembayaran berhasil diunggah. Silakan tunggu konfirmasi dari Finance.';
        $this->uploadOk    = true;
    }
};
?>

<div class="min-h-screen bg-gray-50">

    {{-- ── Page Header ── --}}
    <div class="bg-white border-b border-gray-200">
        <div class="px-6 py-4 flex items-center gap-3">
            <a href="{{ route('tenant.dashboard') }}"
               class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-[#1a5c2e] transition-colors font-medium">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Dashboard
            </a>
            <span class="text-gray-300 text-xs">/</span>
            <span class="text-xs font-semibold text-gray-700">Cek Invoice</span>
        </div>
    </div>

    <div class="px-6 py-6">

        {{-- Summary Cards --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <p class="text-xs text-gray-400 mb-1">Total Tagihan</p>
                <p class="text-xl font-bold text-gray-900">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-green-200 p-4 shadow-sm">
                <p class="text-xs text-gray-400 mb-1">Sudah Lunas</p>
                <p class="text-xl font-bold text-green-700">Rp {{ number_format($totalLunas, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-red-200 p-4 shadow-sm">
                <p class="text-xs text-gray-400 mb-1">Belum Lunas</p>
                <p class="text-xl font-bold text-red-600">Rp {{ number_format($belumLunas, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="flex flex-wrap gap-2 mb-4">
            <select wire:model.live="filterBulan"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-[#2a8c56] bg-white">
                <option value="">Semua Bulan</option>
                @foreach($bulanOptions as $num => $nama)
                <option value="{{ $num }}">{{ $nama }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterTahun"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-[#2a8c56] bg-white">
                <option value="">Semua Tahun</option>
                @foreach($tahunOptions as $yr)
                <option value="{{ $yr }}">{{ $yr }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterStatus"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-[#2a8c56] bg-white">
                <option value="">Semua Status</option>
                <option value="Belum Lunas">Belum Lunas</option>
                <option value="Lunas">Lunas</option>
            </select>

            <span class="ml-auto self-center text-xs text-gray-400">Unit: <strong class="text-gray-700">{{ $unitNumber }}</strong></span>
        </div>

        {{-- Invoice list --}}
        <div class="space-y-3">
            @forelse($invoices as $inv)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4">
                    <div class="flex items-start gap-4">
                        {{-- Icon --}}
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0
                            {{ $inv->status_bayar === 'Lunas' ? 'bg-green-100' : 'bg-yellow-100' }}">
                            <svg class="w-5 h-5 {{ $inv->status_bayar === 'Lunas' ? 'text-green-600' : 'text-yellow-600' }}"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-mono text-gray-400">{{ $inv->no_invoice }}</p>
                            <p class="font-semibold text-gray-900 text-sm mt-0.5">
                                {{ $inv->description ?: ($inv->kategori . ' — ' . (Invoice::bulanOptions()[$inv->bulan] ?? $inv->bulan) . ' ' . $inv->tahun) }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Tgl. Invoice: {{ $inv->inv_date?->format('d/m/Y') }}
                                @if($inv->status_bayar === 'Lunas' && $inv->tgl_bayar)
                                · Dibayar: {{ $inv->tgl_bayar->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 shrink-0">
                        <div class="text-right">
                            <p class="text-lg font-bold text-gray-900">
                                Rp {{ number_format($inv->amount, 0, ',', '.') }}
                            </p>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded
                                {{ $inv->status_bayar === 'Lunas' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                                {{ $inv->status_bayar }}
                            </span>
                        </div>
                        <button wire:click="openDetail({{ $inv->id }})"
                                class="text-xs font-medium text-[#1a5c2e] hover:underline whitespace-nowrap">
                            Lihat Detail →
                        </button>
                    </div>
                </div>
                {{-- Category chips --}}
                <div class="border-t border-gray-50 px-5 py-2 flex gap-1.5 bg-gray-50">
                    @if($inv->ipl_amount > 0)
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-medium">IPL</span>
                    @endif
                    @if($inv->listrik_amount > 0)
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 font-medium">Listrik</span>
                    @endif
                    @if($inv->air_amount > 0)
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 font-medium">Air</span>
                    @endif
                    @if($inv->denda > 0)
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-100 text-red-800 font-medium">Denda</span>
                    @endif
                    @if($inv->other_charges > 0)
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-200 text-gray-700 font-medium">Lainnya</span>
                    @endif
                </div>
            </div>
            @empty
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-violet-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-600 mb-1">Belum ada invoice</p>
                <p class="text-xs text-gray-400">Invoice akan muncul di sini setelah diterbitkan oleh Finance.</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-4">{{ $invoices->links() }}</div>

    </div>

    {{-- Detail Slide Panel --}}
    @if($showPanel && $viewing)
    <div class="fixed inset-0 z-40 flex justify-end">
        <div class="absolute inset-0 bg-black/30" wire:click="closePanel"></div>
        <div class="relative z-50 w-full max-w-md bg-white h-full shadow-2xl flex flex-col overflow-hidden">

            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 bg-gray-50 shrink-0">
                <h2 class="font-semibold text-gray-800 text-sm">Detail Invoice</h2>
                <div class="flex items-center gap-2">
                    <a href="{{ route('tenant.invoice-pdf', $viewing->id) }}" target="_blank"
                       class="flex items-center gap-1 px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-semibold rounded transition-colors">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download PDF
                    </a>
                    <button wire:click="closePanel" class="text-gray-400 hover:text-gray-700 text-lg leading-none">&times;</button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-5 text-xs text-gray-700 space-y-4">

                {{-- Header --}}
                <div>
                    <p class="text-gray-400">No. Invoice</p>
                    <p class="font-mono font-bold text-sm text-gray-900 mt-0.5">{{ $viewing->no_invoice }}</p>
                    <p class="text-gray-400 mt-2">Tanggal Invoice</p>
                    <p class="font-medium mt-0.5">{{ $viewing->inv_date?->format('d/m/Y') }}</p>
                    <p class="text-gray-400 mt-2">Periode</p>
                    <p class="font-medium mt-0.5">{{ Invoice::bulanOptions()[$viewing->bulan] ?? $viewing->bulan }} {{ $viewing->tahun }}</p>
                </div>

                {{-- Rincian --}}
                <div class="border-t border-gray-100 pt-3">
                    <p class="font-semibold text-gray-600 mb-3">Rincian Tagihan</p>

                    <div class="space-y-2">
                        @if($viewing->ipl_amount > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-500">IPL / Service Charge</span>
                            <span class="font-medium">Rp {{ number_format($viewing->ipl_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        @if($viewing->listrik_amount > 0)
                        <div class="border border-yellow-100 rounded p-2.5 bg-yellow-50">
                            <p class="font-semibold text-yellow-800 mb-1.5">Listrik</p>
                            @if($viewing->kwh_prev !== null)
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[11px] text-gray-600 mb-1.5">
                                <div>Stand Awal: <span class="font-medium">{{ number_format($viewing->kwh_prev, 3) }} kWh</span></div>
                                <div>Stand Akhir: <span class="font-medium">{{ number_format($viewing->kwh_curr, 3) }} kWh</span></div>
                                <div>Pemakaian: <span class="font-medium">{{ number_format($viewing->kwh_used, 3) }} kWh</span></div>
                                @if($viewing->daya_terpasang)
                                <div>Daya: <span class="font-medium">{{ $viewing->daya_terpasang }}</span></div>
                                @endif
                                <div>Tarif: <span class="font-medium">Rp {{ number_format($viewing->kwh_tariff, 2) }}/kWh</span></div>
                            </div>
                            <div class="flex justify-between font-semibold text-yellow-900">
                                <span>{{ number_format($viewing->kwh_used, 3) }} kWh × Rp {{ number_format($viewing->kwh_tariff, 2) }}</span>
                                <span>Rp {{ number_format($viewing->listrik_amount, 0, ',', '.') }}</span>
                            </div>
                            @else
                            <div class="flex justify-between">
                                <span class="text-gray-500">Listrik</span>
                                <span class="font-medium">Rp {{ number_format($viewing->listrik_amount, 0, ',', '.') }}</span>
                            </div>
                            @endif
                        </div>
                        @endif

                        @if($viewing->air_amount > 0)
                        <div class="border border-blue-100 rounded p-2.5 bg-blue-50">
                            <p class="font-semibold text-blue-800 mb-1.5">Air</p>
                            @if($viewing->meter_prev !== null)
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[11px] text-gray-600 mb-1.5">
                                <div>Stand Awal: <span class="font-medium">{{ number_format($viewing->meter_prev, 3) }} m³</span></div>
                                <div>Stand Akhir: <span class="font-medium">{{ number_format($viewing->meter_curr, 3) }} m³</span></div>
                                <div>Pemakaian: <span class="font-medium">{{ number_format($viewing->meter_m3, 3) }} m³</span></div>
                                <div>Tarif: <span class="font-medium">Rp {{ number_format($viewing->water_tariff, 2) }}/m³</span></div>
                            </div>
                            <div class="flex justify-between font-semibold text-blue-900">
                                <span>{{ number_format($viewing->meter_m3, 3) }} m³ × Rp {{ number_format($viewing->water_tariff, 2) }}</span>
                                <span>Rp {{ number_format($viewing->air_amount, 0, ',', '.') }}</span>
                            </div>
                            @else
                            <div class="flex justify-between">
                                <span class="text-gray-500">Air</span>
                                <span class="font-medium">Rp {{ number_format($viewing->air_amount, 0, ',', '.') }}</span>
                            </div>
                            @endif
                        </div>
                        @endif

                        @if($viewing->denda > 0)
                        <div class="flex justify-between text-red-700">
                            <span>Denda / Penalti</span>
                            <span class="font-medium">Rp {{ number_format($viewing->denda, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        @if($viewing->other_charges > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Biaya Lainnya</span>
                            <span class="font-medium">Rp {{ number_format($viewing->other_charges, 0, ',', '.') }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Total --}}
                    <div class="flex justify-between items-center border-t border-gray-200 mt-3 pt-3 font-bold text-base">
                        <span class="text-gray-800">TOTAL</span>
                        <span class="text-[#1a5c2e]">Rp {{ number_format($viewing->amount, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Status Bayar --}}
                <div class="border-t border-gray-100 pt-3">
                    <p class="font-semibold text-gray-600 mb-2">Status Pembayaran</p>
                    @if($viewing->status_bayar === 'Lunas')
                    <div class="px-3 py-2.5 bg-green-50 border border-green-200 rounded">
                        <p class="text-green-800 font-semibold text-xs">✓ Lunas</p>
                        @if($viewing->tgl_bayar)
                        <p class="text-green-600 text-[11px] mt-0.5">Dibayar: {{ $viewing->tgl_bayar->format('d/m/Y') }}</p>
                        @endif
                        @if($viewing->paid_by)
                        <p class="text-green-600 text-[11px]">Via: {{ $viewing->paid_by }}</p>
                        @endif
                    </div>
                    @else
                    <div class="px-3 py-2.5 bg-red-50 border border-red-200 rounded">
                        <p class="text-red-700 font-semibold text-xs">Belum Lunas</p>
                        @if($viewing->virtual_account)
                        <p class="text-gray-500 text-[11px] mt-1">Virtual Account:</p>
                        <p class="font-mono font-bold text-gray-900 text-sm">{{ $viewing->virtual_account }}</p>
                        @endif
                        @if($viewing->handphone)
                        <p class="text-gray-500 text-[11px] mt-1">Info lebih lanjut hubungi: {{ $viewing->handphone }}</p>
                        @endif
                    </div>

                    {{-- Bukti Bayar Upload --}}
                    <div class="mt-3 max-w-xs">
                        <p class="font-semibold text-gray-600 mb-2 text-xs">Upload Bukti Pembayaran</p>

                        @if($viewing->bukti_bayar)
                        <div class="mb-2">
                            <p class="text-[10px] text-green-700 font-medium mb-1">Bukti sudah diunggah:</p>
                            <img src="{{ asset('storage/' . $viewing->bukti_bayar) }}"
                                 alt="Bukti Bayar"
                                 class="rounded border border-gray-200 max-h-32 object-contain">
                            <p class="text-[10px] text-gray-400 mt-1">
                                {{ $viewing->tgl_bukti_bayar?->format('d/m/Y H:i') }} — Menunggu konfirmasi Finance
                            </p>
                        </div>
                        @endif

                        @if($uploadOk)
                        <div class="px-3 py-2 bg-green-50 border border-green-200 rounded text-xs text-green-700">
                            {{ $uploadMsg }}
                        </div>
                        @else
                        <div class="space-y-2">
                            <input type="file" wire:model="uploadBukti" accept="image/*"
                                   class="text-[11px] w-full file:mr-2 file:py-1 file:px-3 file:rounded file:border-0
                                          file:bg-[#e8f5e9] file:text-[#1a5c2e] file:text-[10px] file:font-semibold">
                            @error('uploadBukti')
                            <p class="text-[10px] text-red-600">{{ $message }}</p>
                            @enderror
                            @if($uploadBukti)
                            <img src="{{ $uploadBukti->temporaryUrl() }}" alt="Preview"
                                 class="rounded border border-gray-200 max-h-24 object-contain">
                            @endif
                            <button wire:click="uploadBuktiPembayaran"
                                    wire:loading.attr="disabled"
                                    style="background:#1a5c2e; color:#fff; border:none; cursor:pointer;"
                                    class="px-5 py-2 text-xs font-semibold rounded disabled:opacity-60 transition-opacity">
                                <span wire:loading.remove wire:target="uploadBuktiPembayaran">Kirim Bukti Pembayaran</span>
                                <span wire:loading wire:target="uploadBuktiPembayaran">Mengunggah...</span>
                            </button>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

            </div>

            <div class="shrink-0 border-t border-gray-200 bg-gray-50 px-5 py-3 flex justify-end">
                <button wire:click="closePanel"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs rounded font-medium">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
