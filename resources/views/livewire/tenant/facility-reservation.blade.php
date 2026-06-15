<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\FacilityReservation;

new #[Layout('layouts.tenant')] class extends Component {
    use WithPagination, WithFileUploads;

    public ?string $panelMode = null;
    public ?int $detailId = null;
    public ?int $editId   = null;

    // Form fields
    public string $formFasilitas       = '';
    public string $formTanggal         = '';
    public string $formJamMulai        = '09:00';
    public string $formJamSelesai      = '11:00';
    public string $formKeperluan       = '';
    public int    $formJumlahTamu      = 1;
    public bool   $isBerbayar          = false;
    public string $biayaDisplay        = 'Rp 0';
    public        $formBuktiBayar      = null;

    public string $successMsg = '';

    public function mount(): void
    {
        $this->formTanggal = now()->addDay()->format('Y-m-d');
    }

    public function updatedFormFasilitas(string $val): void
    {
        $defaults = FacilityReservation::fasilitasBiayaDefault();
        if (isset($defaults[$val])) {
            $this->isBerbayar   = $defaults[$val]['is_berbayar'];
            $biaya = $defaults[$val]['biaya'];
            $this->biayaDisplay = $this->isBerbayar
                ? 'Rp ' . number_format($biaya, 0, ',', '.')
                : 'Gratis';
        } else {
            $this->isBerbayar   = false;
            $this->biayaDisplay = '—';
        }
    }

    public function openInput(): void
    {
        $this->editId          = null;
        $this->panelMode       = 'input';
        $this->formFasilitas   = '';
        $this->formTanggal     = now()->addDay()->format('Y-m-d');
        $this->formJamMulai    = '09:00';
        $this->formJamSelesai  = '11:00';
        $this->formKeperluan   = '';
        $this->formJumlahTamu  = 1;
        $this->isBerbayar      = false;
        $this->biayaDisplay    = '—';
        $this->formBuktiBayar  = null;
        $this->successMsg      = '';
    }

    public function openEdit(int $id): void
    {
        $res = FacilityReservation::find($id);
        if (! $res || $res->status !== 'Pesan Diterima') return;

        $this->editId          = $id;
        $this->panelMode       = 'input';
        $this->formFasilitas   = $res->nama_fasilitas;
        $this->formTanggal     = $res->tanggal_reservasi?->format('Y-m-d') ?? now()->addDay()->format('Y-m-d');
        $this->formJamMulai    = $res->jam_mulai;
        $this->formJamSelesai  = $res->jam_selesai;
        $this->formKeperluan   = $res->keperluan;
        $this->formJumlahTamu  = $res->jumlah_tamu;
        $this->isBerbayar      = $res->is_berbayar;
        $this->biayaDisplay    = $res->is_berbayar ? 'Rp ' . number_format($res->biaya, 0, ',', '.') : 'Gratis';
        $this->formBuktiBayar  = null;
        $this->successMsg      = '';
        $this->updatedFormFasilitas($res->nama_fasilitas);
    }

    public function cancelReservation(int $id): void
    {
        $res = FacilityReservation::find($id);
        if (! $res || $res->status !== 'Pesan Diterima') return;

        $res->update(['status' => 'Ditolak', 'rr_officer' => 'Dibatalkan oleh Tenant']);
        $this->successMsg = "Reservasi {$res->nomor} berhasil dibatalkan.";
        $this->resetPage();
    }

    public function openDetail(int $id): void
    {
        $this->detailId  = $id;
        $this->panelMode = 'detail';
    }

    public function closePanel(): void
    {
        $this->panelMode = null;
        $this->detailId  = null;
        $this->editId    = null;
    }

    public function save(): void
    {
        $isEdit = (bool) $this->editId;

        $this->validate([
            'formFasilitas'   => 'required',
            'formTanggal'     => 'required|date|after_or_equal:today',
            'formJamMulai'    => 'required',
            'formJamSelesai'  => 'required',
            'formKeperluan'   => 'required|min:5',
            'formJumlahTamu'  => 'required|integer|min:1',
            'formBuktiBayar'  => ($this->isBerbayar && !$isEdit) ? 'required|image|max:4096' : 'nullable|image|max:4096',
        ], [
            'formFasilitas.required'  => 'Fasilitas wajib dipilih.',
            'formTanggal.required'    => 'Tanggal reservasi wajib diisi.',
            'formTanggal.after_or_equal' => 'Tanggal tidak boleh di masa lalu.',
            'formJamMulai.required'   => 'Jam mulai wajib diisi.',
            'formJamSelesai.required' => 'Jam selesai wajib diisi.',
            'formKeperluan.required'  => 'Keperluan wajib diisi.',
            'formBuktiBayar.required' => 'Bukti pembayaran wajib diupload untuk fasilitas berbayar.',
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($isEdit) {
            // UPDATE
            $res = FacilityReservation::findOrFail($this->editId);
            $data = [
                'nama_fasilitas'    => $this->formFasilitas,
                'tanggal_reservasi' => $this->formTanggal,
                'jam_mulai'         => $this->formJamMulai,
                'jam_selesai'       => $this->formJamSelesai,
                'keperluan'         => $this->formKeperluan,
                'jumlah_tamu'       => $this->formJumlahTamu,
            ];
            if ($this->formBuktiBayar) {
                $data['bukti_bayar'] = $this->formBuktiBayar->store('facility-bukti', 'public');
            }
            $res->update($data);
            $this->successMsg = "Reservasi {$res->nomor} berhasil diperbarui.";

        } else {
            // CREATE
            $tenantProfile = $user->tenant;
            $defaults = FacilityReservation::fasilitasBiayaDefault();
            $defVal   = $defaults[$this->formFasilitas] ?? ['is_berbayar' => false, 'biaya' => 0];

            $bukti = null;
            if ($this->formBuktiBayar) {
                $bukti = $this->formBuktiBayar->store('facility-bukti', 'public');
            }

            $romans = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
            $roman  = $romans[now()->month - 1];
            $year   = now()->year;
            $last   = FacilityReservation::orderByDesc('id')->first();
            $seq    = 1;
            if ($last && preg_match('/FRS(\d+)/', $last->nomor, $m)) {
                $seq = (int) $m[1] + 1;
            }
            $nomor = "FRS{$seq}/{$roman}/{$year}-MAP";

            FacilityReservation::create([
                'nomor'              => $nomor,
                'unit'               => $tenantProfile?->unit_number ?? '',
                'tenant_name'        => strtoupper($user->name),
                'nama_fasilitas'     => $this->formFasilitas,
                'tanggal_reservasi'  => $this->formTanggal,
                'jam_mulai'          => $this->formJamMulai,
                'jam_selesai'        => $this->formJamSelesai,
                'keperluan'          => $this->formKeperluan,
                'jumlah_tamu'        => $this->formJumlahTamu,
                'is_berbayar'        => $defVal['is_berbayar'],
                'biaya'              => $defVal['biaya'],
                'status_bayar'       => $defVal['is_berbayar'] ? 'Belum Bayar' : 'Bebas Biaya',
                'bukti_bayar'        => $bukti,
                'request_by'         => $user->name,
                'request_via'        => 'aplikasi',
                'status'             => 'Pesan Diterima',
                'input_by'           => $user->name,
            ]);
            $this->successMsg = "Reservasi {$nomor} berhasil diajukan. Menunggu konfirmasi CS.";
        }

        $this->panelMode = null;
        $this->editId    = null;
        $this->resetPage();
    }

    public function with(): array
    {
        $unit = auth()->user()->tenant?->unit_number;

        $reservations = FacilityReservation::when($unit, fn($q) => $q->where('unit', $unit))
            ->orderByDesc('tanggal_reservasi')
            ->paginate(10);

        $detail = $this->detailId ? FacilityReservation::find($this->detailId) : null;

        // Auto-generate QR token ketika reservasi sudah siap
        if ($detail && in_array($detail->status, ['Siap Pelaksanaan', 'Sedang Berlangsung']) && !$detail->qr_token) {
            $detail->generateQrToken();
            $detail->refresh();
        }

        return compact('reservations', 'detail');
    }
}
?>

<div>
    {{-- ── Page Header ── --}}
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center gap-3">
            <a href="{{ route('tenant.dashboard') }}"
               class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-[#1a5c2e] transition-colors font-medium">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Dashboard
            </a>
            <span class="text-gray-300 text-xs">/</span>
            <span class="text-xs font-semibold text-gray-700">Reservasi Fasilitas</span>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-6 py-6">

        @if($successMsg)
        <div class="mb-4 flex items-start gap-3 bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-xl">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ $successMsg }}</span>
        </div>
        @endif

        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Reservasi Fasilitas Umum</h2>
                <p class="text-xs text-gray-400 mt-0.5">Booking fasilitas apartemen yang Anda ajukan</p>
            </div>
            <button wire:click="openInput"
                    style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; background:#1a5c2e; color:white; font-size:12px; font-weight:600; border-radius:12px; border:none; cursor:pointer; box-shadow:0 1px 4px rgba(0,0,0,0.15); transition:background 0.15s;"
                    onmouseover="this.style.background='#154d26'" onmouseout="this.style.background='#0d9488'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Buat Reservasi
            </button>
        </div>

        {{-- Fasilitas chips --}}
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach(FacilityReservation::fasilitasOptions() as $f)
            @php $paid = FacilityReservation::fasilitasBiayaDefault()[$f]['is_berbayar'] ?? false; @endphp
            <span class="text-[10px] font-medium px-2.5 py-1 rounded-full border
                {{ $paid ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-teal-50 text-teal-700 border-teal-200' }}">
                {{ $f }} {{ $paid ? '(Berbayar)' : '(Gratis)' }}
            </span>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            @if($reservations->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="w-16 h-16 rounded-full bg-teal-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-teal-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-600 mb-1">Belum ada reservasi</p>
                <p class="text-xs text-gray-400">Klik "Buat Reservasi" untuk memesan fasilitas</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Nomor</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Fasilitas</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Tanggal</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Jam</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Biaya</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($reservations as $res)
                        @php $canEdit = $res->status === 'Pesan Diterima'; @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-[#1a6b9a] font-semibold">{{ $res->nomor }}</td>
                            <td class="px-4 py-3 text-gray-700 font-medium">{{ $res->nama_fasilitas }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->tanggal_reservasi?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $res->jam_mulai }} – {{ $res->jam_selesai }}</td>
                            <td class="px-4 py-3">
                                @if($res->is_berbayar)
                                <span class="text-orange-600 font-semibold">Rp {{ number_format($res->biaya, 0, ',', '.') }}</span>
                                @else
                                <span style="color:#0d9488; font-weight:500;">Gratis</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $sc = match($res->status) {
                                        'Pesan Diterima'      => 'bg-yellow-100 text-yellow-800',
                                        'Disetujui CS'        => 'bg-blue-100 text-blue-800',
                                        'Siap Pelaksanaan'    => 'bg-indigo-100 text-indigo-800',
                                        'Sedang Berlangsung'  => 'bg-orange-100 text-orange-800',
                                        'Selesai'             => 'bg-green-100 text-green-800',
                                        'Ditolak'             => 'bg-red-100 text-red-700',
                                        default               => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $sc }}">
                                    {{ $res->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    <button wire:click="openDetail({{ $res->id }})"
                                            style="font-size:11px; font-weight:600; color:#1a5c2e; background:none; border:none; cursor:pointer; padding:0; text-decoration:underline;">
                                        Lihat
                                    </button>
                                    @if($canEdit)
                                    <span style="color:#d1d5db; font-size:10px;">|</span>
                                    <button wire:click="openEdit({{ $res->id }})"
                                            style="font-size:11px; font-weight:600; color:#d97706; background:none; border:none; cursor:pointer; padding:0; text-decoration:underline;">
                                        Edit
                                    </button>
                                    <span style="color:#d1d5db; font-size:10px;">|</span>
                                    <button wire:click="cancelReservation({{ $res->id }})"
                                            wire:confirm="Batalkan reservasi ini? Tindakan ini tidak dapat dibatalkan."
                                            style="font-size:11px; font-weight:600; color:#dc2626; background:none; border:none; cursor:pointer; padding:0; text-decoration:underline;">
                                        Batal
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($reservations->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span>{{ $reservations->firstItem() }}–{{ $reservations->lastItem() }} dari {{ $reservations->total() }}</span>
                <div class="flex gap-1">
                    @if(!$reservations->onFirstPage())
                    <button wire:click="previousPage" class="px-2.5 py-1 rounded-lg border border-gray-200 hover:bg-gray-100">‹</button>
                    @endif
                    <span class="px-3 py-1 rounded-lg bg-gray-100 font-medium">{{ $reservations->currentPage() }}</span>
                    @if($reservations->hasMorePages())
                    <button wire:click="nextPage" class="px-2.5 py-1 rounded-lg border border-gray-200 hover:bg-gray-100">›</button>
                    @endif
                </div>
            </div>
            @endif
            @endif
        </div>
    </div>

    {{-- ══════════════ Input Panel (centered modal) ══════════════ --}}
    @if($panelMode === 'input')
    <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:16px; background:rgba(0,0,0,0.5);"
         wire:click.self="closePanel">
        <div style="position:relative; width:100%; max-width:500px; background:white; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.2); display:flex; flex-direction:column; max-height:90vh; overflow:hidden;">

            <div style="padding:20px 24px 16px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
                <div>
                    <h3 style="font-size:15px; font-weight:700; color:#111827; margin:0;">{{ $editId ? 'Edit Reservasi' : 'Buat Reservasi' }}</h3>
                    <p style="font-size:11px; color:#9ca3af; margin:2px 0 0;">{{ $editId ? 'Ubah detail reservasi yang sudah diajukan' : 'Pemesanan fasilitas umum apartemen' }}</p>
                </div>
                <button wire:click="closePanel"
                        style="width:32px; height:32px; border-radius:50%; background:#f3f4f6; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#6b7280; font-size:16px; font-weight:700; flex-shrink:0; transition:background 0.15s;"
                        onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">✕</button>
            </div>

            <form wire:submit="save" style="padding:20px 24px; display:flex; flex-direction:column; gap:16px; overflow-y:auto; flex:1;">

                {{-- Fasilitas --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                        Nama Fasilitas <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="formFasilitas"
                            class="w-full text-sm border border-gray-300 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                        <option value="">— Pilih Fasilitas —</option>
                        @foreach(FacilityReservation::fasilitasOptions() as $f)
                        <option value="{{ $f }}">{{ $f }}</option>
                        @endforeach
                    </select>
                    @error('formFasilitas') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Biaya Info --}}
                @if($formFasilitas)
                <div class="flex items-center gap-3 p-3 rounded-xl {{ $isBerbayar ? 'bg-orange-50 border border-orange-200' : 'bg-teal-50 border border-teal-200' }}">
                    <div class="w-8 h-8 rounded-full {{ $isBerbayar ? 'bg-orange-100' : 'bg-teal-100' }} flex items-center justify-center shrink-0">
                        @if($isBerbayar)
                        <svg class="w-4 h-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
                        </svg>
                        @else
                        <svg class="w-4 h-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold {{ $isBerbayar ? 'text-orange-800' : 'text-teal-800' }}">
                            {{ $isBerbayar ? 'Fasilitas Berbayar' : 'Fasilitas Gratis' }}
                        </p>
                        <p class="text-xs {{ $isBerbayar ? 'text-orange-600' : 'text-teal-600' }}">
                            Biaya: <span class="font-bold">{{ $biayaDisplay }}</span>
                        </p>
                    </div>
                </div>
                @endif

                {{-- Tanggal --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                        Tanggal Reservasi <span class="text-red-500">*</span>
                    </label>
                    <input type="date" wire:model="formTanggal"
                           min="{{ now()->format('Y-m-d') }}"
                           class="w-full text-sm border border-gray-300 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                    @error('formTanggal') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Jam Mulai & Selesai --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Jam Mulai <span class="text-red-500">*</span>
                        </label>
                        <input type="time" wire:model="formJamMulai"
                               class="w-full text-sm border border-gray-300 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                        @error('formJamMulai') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Jam Selesai <span class="text-red-500">*</span>
                        </label>
                        <input type="time" wire:model="formJamSelesai"
                               class="w-full text-sm border border-gray-300 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                        @error('formJamSelesai') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Keperluan --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                        Keperluan / Acara <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="formKeperluan" rows="3"
                              placeholder="Contoh: Arisan keluarga, ulang tahun, rapat RT..."
                              class="w-full text-sm border border-gray-300 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 resize-none"></textarea>
                    @error('formKeperluan') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Jumlah Tamu --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                        Estimasi Jumlah Tamu <span class="text-red-500">*</span>
                    </label>
                    <input type="number" wire:model="formJumlahTamu" min="1"
                           class="w-full text-sm border border-gray-300 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                    @error('formJumlahTamu') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Bukti Bayar (only if berbayar) --}}
                @if($isBerbayar)
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                        Bukti Pembayaran <span class="text-red-500">*</span>
                    </label>
                    <p class="text-[11px] text-orange-600 mb-1.5">
                        Silakan lakukan pembayaran ke rekening pengelola dan upload buktinya.
                    </p>
                    <input type="file" wire:model="formBuktiBayar" accept="image/*"
                           class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-orange-50 file:text-orange-700 file:text-xs file:font-medium hover:file:bg-orange-100 file:cursor-pointer">
                    @error('formBuktiBayar') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                <p class="text-[11px] text-gray-400 bg-gray-50 rounded-xl p-3 leading-relaxed">
                    Reservasi akan dikonfirmasi melalui proses CS → {{ $isBerbayar ? 'Finance → ' : '' }}Housekeeping → Engineering → Security.
                    Pengajuan minimal 1 hari sebelum acara.
                </p>

                {{-- Action buttons --}}
                <div style="display:flex; gap:10px; padding-top:4px;">
                    <button type="button" wire:click="closePanel"
                            style="flex:1; padding:11px; font-size:13px; font-weight:600; border-radius:10px; border:1px solid #e5e7eb; background:white; color:#6b7280; cursor:pointer; transition:background 0.15s;"
                            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                        Batal
                    </button>
                    <button type="submit"
                            style="flex:2; padding:11px; font-size:13px; font-weight:700; border-radius:10px; border:none; background:#1a5c2e; color:white; cursor:pointer; transition:background 0.15s;"
                            onmouseover="this.style.background='#154d26'" onmouseout="this.style.background='#1a5c2e'">
                        {{ $editId ? 'Simpan Perubahan' : 'Ajukan Reservasi' }}
                    </button>
                </div>

            </form>
        </div>
    </div>
    @endif

    {{-- ══════════════ Detail Panel (centered modal) ══════════════ --}}
    @if($panelMode === 'detail' && $detail)
    <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:16px; background:rgba(0,0,0,0.5);"
         wire:click.self="closePanel">
        <div style="position:relative; width:100%; max-width:500px; background:white; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.2); display:flex; flex-direction:column; max-height:90vh; overflow:hidden;">

            <div style="padding:20px 24px 16px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
                <div>
                    <h3 style="font-size:15px; font-weight:700; color:#111827; margin:0;">Detail Reservasi</h3>
                    <p style="font-size:11px; font-family:monospace; color:#1a6b9a; margin:2px 0 0;">{{ $detail->nomor }}</p>
                </div>
                <button wire:click="closePanel"
                        style="width:32px; height:32px; border-radius:50%; background:#f3f4f6; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#6b7280; font-size:16px; font-weight:700; flex-shrink:0; transition:background 0.15s;"
                        onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">✕</button>
            </div>

            <div style="padding:20px 24px; display:flex; flex-direction:column; gap:14px; overflow-y:auto; flex:1;">
                @php
                    $sc = match($detail->status) {
                        'Pesan Diterima'      => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                        'Disetujui CS'        => 'bg-blue-100 text-blue-800 border-blue-200',
                        'Siap Pelaksanaan'    => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                        'Sedang Berlangsung'  => 'bg-orange-100 text-orange-800 border-orange-200',
                        'Selesai'             => 'bg-green-100 text-green-800 border-green-200',
                        'Ditolak'             => 'bg-red-100 text-red-700 border-red-200',
                        default               => 'bg-gray-100 text-gray-600 border-gray-200',
                    };
                @endphp
                <div class="inline-block px-3 py-1.5 rounded-full text-xs font-bold border {{ $sc }}">
                    {{ $detail->status }}
                </div>

                {{-- QR Code — tampil saat reservasi siap atau sedang berlangsung --}}
                @if(in_array($detail->status, ['Siap Pelaksanaan', 'Sedang Berlangsung']) && $detail->qr_token)
                <div class="text-center p-4 rounded-2xl border-2
                    {{ $detail->status === 'Siap Pelaksanaan' ? 'bg-indigo-50 border-indigo-200' : 'bg-orange-50 border-orange-200' }}">
                    <p class="text-xs font-bold mb-1
                        {{ $detail->status === 'Siap Pelaksanaan' ? 'text-indigo-800' : 'text-orange-800' }}">
                        {{ $detail->status === 'Siap Pelaksanaan' ? '✓ Reservasi Disetujui' : '▶ Sedang Berlangsung' }}
                    </p>
                    <p class="text-[10px] mb-3
                        {{ $detail->status === 'Siap Pelaksanaan' ? 'text-indigo-600' : 'text-orange-600' }}">
                        {{ $detail->status === 'Siap Pelaksanaan'
                            ? 'Tunjukkan QR ini ke petugas Security untuk membuka fasilitas'
                            : 'Tunjukkan QR ini ke Security untuk menutup sesi penggunaan' }}
                    </p>
                    <div class="inline-block bg-white p-3 rounded-xl shadow border border-gray-100">
                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(180)->generate(
                            url('/karyawan/qr-scan/' . $detail->qr_token)
                        ) !!}
                    </div>
                    <p class="text-[10px] text-gray-400 mt-2">{{ $detail->nomor }}</p>
                    {{-- Token manual fallback --}}
                    <div class="mt-3 bg-white border border-dashed border-gray-300 rounded-xl px-3 py-2 inline-block">
                        <p class="text-[9px] text-gray-400 mb-1">Jika tidak bisa scan, berikan kode ini ke petugas:</p>
                        <p class="font-mono text-xs font-bold text-gray-700 tracking-widest select-all">{{ $detail->qr_token }}</p>
                    </div>
                </div>
                @endif

                @foreach([
                    ['Fasilitas',    $detail->nama_fasilitas],
                    ['Tanggal',      $detail->tanggal_reservasi?->format('d/m/Y')],
                    ['Waktu',        $detail->jam_mulai . ' – ' . $detail->jam_selesai],
                    ['Keperluan',    $detail->keperluan],
                    ['Jumlah Tamu',  $detail->jumlah_tamu . ' orang'],
                ] as [$label, $val])
                <div class="flex gap-3">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">{{ $label }}</span>
                    <span class="text-xs font-medium text-gray-800">{{ $val }}</span>
                </div>
                @endforeach

                {{-- Biaya & Pembayaran --}}
                <div class="p-3 rounded-xl {{ $detail->is_berbayar ? 'bg-orange-50 border border-orange-100' : 'bg-teal-50 border border-teal-100' }}">
                    <p class="text-xs font-semibold {{ $detail->is_berbayar ? 'text-orange-800' : 'text-teal-800' }} mb-1">
                        {{ $detail->is_berbayar ? 'Fasilitas Berbayar' : 'Fasilitas Gratis' }}
                    </p>
                    @if($detail->is_berbayar)
                    <p class="text-xs text-orange-700">
                        Biaya: <span class="font-bold">Rp {{ number_format($detail->biaya, 0, ',', '.') }}</span>
                        · Status: <span class="font-semibold">{{ $detail->status_bayar ?? '—' }}</span>
                    </p>
                    @endif
                </div>

                {{-- Petugas CS --}}
                @if($detail->rr_officer)
                <div class="flex gap-3">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">Petugas CS</span>
                    <span class="text-xs font-medium text-gray-800">{{ $detail->rr_officer }}</span>
                </div>
                @endif

                @if($detail->bukti_bayar)
                <div>
                    <span class="text-xs text-gray-400 block mb-1.5">Bukti Pembayaran</span>
                    <img src="{{ asset('storage/' . $detail->bukti_bayar) }}" alt="Bukti"
                         class="w-full rounded-xl border border-gray-200 object-cover max-h-56">
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
