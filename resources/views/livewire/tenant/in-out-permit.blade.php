<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\InOutPermit;

new #[Layout('layouts.tenant')] class extends Component {
    use WithPagination, WithFileUploads;

    public ?string $panelMode = null;
    public ?int $detailId = null;

    // Form fields
    public string $formJenis       = 'Keluar';
    public string $formTanggalIjin = '';
    public string $formJam         = '';
    public string $formDescs       = '';
    public string $formRequestBy   = '';
    public        $formFoto        = null;

    public string $successMsg = '';

    public function mount(): void
    {
        $this->formRequestBy = auth()->user()->name;
        $this->formTanggalIjin = now()->addDay()->format('Y-m-d');
        $this->formJam = '08:00';
    }

    public function openInput(): void
    {
        $this->panelMode     = 'input';
        $this->formJenis     = 'Keluar';
        $this->formTanggalIjin = now()->addDay()->format('Y-m-d');
        $this->formJam       = '08:00';
        $this->formDescs     = '';
        $this->formRequestBy = auth()->user()->name;
        $this->formFoto      = null;
        $this->successMsg    = '';
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
    }

    public function save(): void
    {
        $this->validate([
            'formJenis'       => 'required|in:Masuk,Keluar',
            'formTanggalIjin' => 'required|date|after_or_equal:today',
            'formJam'         => 'required',
            'formDescs'       => 'required|min:10',
            'formRequestBy'   => 'required',
            'formFoto'        => 'nullable|image|max:8192',
        ], [
            'formJenis.required'        => 'Jenis wajib dipilih.',
            'formTanggalIjin.required'  => 'Tanggal ijin wajib diisi.',
            'formTanggalIjin.after_or_equal' => 'Tanggal ijin tidak boleh di masa lalu.',
            'formJam.required'          => 'Jam wajib diisi.',
            'formDescs.required'        => 'Keterangan wajib diisi.',
            'formDescs.min'             => 'Keterangan minimal 10 karakter.',
        ]);

        $tenantProfile = auth()->user()->tenant;
        $user = auth()->user();

        $foto = null;
        if ($this->formFoto) {
            $foto = $this->formFoto->store('in-out-permits', 'public');
        }

        // Generate nomor
        $romans = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $roman  = $romans[now()->month - 1];
        $year   = now()->year;
        $last   = InOutPermit::orderByDesc('id')->first();
        $seq    = 1;
        if ($last && preg_match('/KMB(\d+)/', $last->nomor, $m)) {
            $seq = (int) $m[1] + 1;
        }
        $nomor = "KMB{$seq}/{$roman}/{$year}-MAP";

        InOutPermit::create([
            'nomor'        => $nomor,
            'unit'         => $tenantProfile?->unit_number ?? '',
            'tenant_name'  => strtoupper($user->name),
            'tanggal'      => now(),
            'tanggal_ijin' => $this->formTanggalIjin,
            'jam'          => $this->formJam,
            'jenis'        => $this->formJenis,
            'descs'        => $this->formDescs,
            'request_by'   => $this->formRequestBy,
            'request_via'  => 'aplikasi',
            'status'       => 'Pesan Diterima',
            'input_by'     => $user->name,
            'is_active'    => true,
            'foto'         => $foto,
        ]);

        $this->successMsg = "Permohonan {$nomor} berhasil diajukan. Menunggu persetujuan CS.";
        $this->panelMode  = null;
        $this->resetPage();
    }

    public function with(): array
    {
        $unit = auth()->user()->tenant?->unit_number;

        $permits = InOutPermit::when($unit, fn($q) => $q->where('unit', $unit))
            ->orderByDesc('tanggal')
            ->paginate(10);

        $detail = $this->detailId ? InOutPermit::find($this->detailId) : null;

        return compact('permits', 'detail');
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
            <span class="text-xs font-semibold text-gray-700">In Out Permit</span>
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
                <h2 class="text-lg font-bold text-gray-800">Izin Barang Masuk / Keluar</h2>
                <p class="text-xs text-gray-400 mt-0.5">Permohonan izin pemindahan barang yang Anda ajukan</p>
            </div>
            <button wire:click="openInput"
                    style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; background:#1a5c2e; color:white; font-size:12px; font-weight:600; border-radius:12px; border:none; cursor:pointer; box-shadow:0 1px 4px rgba(0,0,0,0.15); transition:background 0.15s;"
                    onmouseover="this.style.background='#154d26'" onmouseout="this.style.background='#1a5c2e'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                + Ajukan Izin
            </button>
        </div>

        {{-- Info steps --}}
        <div class="mb-5 flex items-center gap-2 overflow-x-auto">
            @foreach([
                ['Pesan Diterima', 'bg-yellow-100 text-yellow-700 border-yellow-200'],
                ['→', 'text-gray-300 text-sm font-bold bg-transparent border-transparent'],
                ['Approve CS', 'bg-blue-100 text-blue-700 border-blue-200'],
                ['→', 'text-gray-300 text-sm font-bold bg-transparent border-transparent'],
                ['Approve FA', 'bg-purple-100 text-purple-700 border-purple-200'],
                ['→', 'text-gray-300 text-sm font-bold bg-transparent border-transparent'],
                ['Approve Security', 'bg-green-100 text-green-700 border-green-200'],
            ] as [$label, $cls])
            <span class="shrink-0 text-[10px] font-semibold px-2.5 py-1 rounded-full border {{ $cls }}">{{ $label }}</span>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            @if($permits->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-600 mb-1">Belum ada permohonan</p>
                <p class="text-xs text-gray-400">Klik "Ajukan Izin" untuk membuat permohonan baru</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Nomor</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Tgl Pengajuan</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Jenis</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Tgl Ijin</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Jam</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($permits as $permit)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-[#1a6b9a] font-semibold">{{ $permit->nomor }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $permit->tanggal?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold
                                    {{ $permit->jenis === 'Masuk' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                    {{ $permit->jenis }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $permit->tanggal_ijin?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $permit->jam }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $sc = match($permit->status) {
                                        'Pesan Diterima'              => 'bg-yellow-100 text-yellow-800',
                                        'Approve by Customer Service' => 'bg-blue-100 text-blue-800',
                                        'Approve by FA'               => 'bg-purple-100 text-purple-800',
                                        'Approve by Security'         => 'bg-green-100 text-green-800',
                                        'Tidak Disetujui'             => 'bg-red-100 text-red-700',
                                        default                       => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $sc }}">
                                    {{ $permit->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="openDetail({{ $permit->id }})"
                                        class="text-blue-600 hover:underline text-xs font-medium">
                                    Lihat
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($permits->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span>{{ $permits->firstItem() }}–{{ $permits->lastItem() }} dari {{ $permits->total() }}</span>
                <div class="flex gap-1">
                    @if(!$permits->onFirstPage())
                    <button wire:click="previousPage" class="px-2.5 py-1 rounded-lg border border-gray-200 hover:bg-gray-100">‹</button>
                    @endif
                    <span class="px-3 py-1 rounded-lg bg-gray-100 font-medium">{{ $permits->currentPage() }}</span>
                    @if($permits->hasMorePages())
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
        <div style="position:relative; width:100%; max-width:480px; background:white; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.2); display:flex; flex-direction:column; max-height:90vh; overflow:hidden;">

            {{-- Header --}}
            <div style="padding:20px 24px 16px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
                <div>
                    <h3 style="font-size:15px; font-weight:700; color:#111827; margin:0;">Ajukan Izin</h3>
                    <p style="font-size:11px; color:#9ca3af; margin:2px 0 0;">Permohonan izin masuk / keluar barang</p>
                </div>
                <button wire:click="closePanel"
                        style="width:32px; height:32px; border-radius:50%; background:#f3f4f6; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#6b7280; font-size:16px; font-weight:700; flex-shrink:0; transition:background 0.15s;"
                        onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">✕</button>
            </div>

            <form wire:submit="save" style="padding:20px 24px; display:flex; flex-direction:column; gap:16px; overflow-y:auto; flex:1;">

                {{-- Jenis --}}
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:8px;">Jenis Perpindahan <span style="color:#ef4444;">*</span></label>
                    <div style="display:flex; gap:10px;">
                        @foreach(['Masuk', 'Keluar'] as $j)
                        <button type="button" wire:click="$set('formJenis', '{{ $j }}')"
                                style="flex:1; text-align:center; padding:10px; border-radius:10px; border:2px solid {{ $formJenis === $j ? '#1a5c2e' : '#e5e7eb' }}; background:{{ $formJenis === $j ? '#f0fdf4' : 'white' }}; color:{{ $formJenis === $j ? '#1a5c2e' : '#6b7280' }}; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.15s;">
                            {{ $j === 'Masuk' ? '↓ Masuk' : '↑ Keluar' }}
                        </button>
                        @endforeach
                    </div>
                    @error('formJenis') <p style="font-size:11px; color:#ef4444; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                {{-- Tanggal --}}
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px;">Tanggal Pelaksanaan <span style="color:#ef4444;">*</span></label>
                    <input type="date" wire:model="formTanggalIjin" min="{{ now()->format('Y-m-d') }}"
                           style="width:100%; font-size:13px; border:1px solid #d1d5db; border-radius:10px; padding:9px 12px; outline:none; box-sizing:border-box;">
                    @error('formTanggalIjin') <p style="font-size:11px; color:#ef4444; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                {{-- Jam --}}
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px;">Jam Pelaksanaan <span style="color:#ef4444;">*</span></label>
                    <input type="time" wire:model="formJam"
                           style="width:100%; font-size:13px; border:1px solid #d1d5db; border-radius:10px; padding:9px 12px; outline:none; box-sizing:border-box;">
                    @error('formJam') <p style="font-size:11px; color:#ef4444; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                {{-- Nama Pemohon --}}
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px;">Nama Pemohon <span style="color:#ef4444;">*</span></label>
                    <input type="text" wire:model="formRequestBy"
                           style="width:100%; font-size:13px; border:1px solid #d1d5db; border-radius:10px; padding:9px 12px; outline:none; box-sizing:border-box;">
                    @error('formRequestBy') <p style="font-size:11px; color:#ef4444; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                {{-- Keterangan --}}
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px;">Keterangan Barang <span style="color:#ef4444;">*</span></label>
                    <textarea wire:model="formDescs" rows="3" placeholder="Jelaskan daftar barang dan keperluan pemindahan..."
                              style="width:100%; font-size:13px; border:1px solid #d1d5db; border-radius:10px; padding:9px 12px; outline:none; resize:none; font-family:inherit; box-sizing:border-box;"></textarea>
                    @error('formDescs') <p style="font-size:11px; color:#ef4444; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                {{-- Foto --}}
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px;">Foto Barang <span style="font-weight:400; color:#9ca3af;">(opsional)</span></label>
                    <input type="file" wire:model="formFoto" accept="image/*" style="width:100%; font-size:12px; color:#6b7280;">
                    @error('formFoto') <p style="font-size:11px; color:#ef4444; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div style="font-size:11px; color:#9ca3af; background:#f0fdf4; border:1px solid #dcfce7; border-radius:10px; padding:10px 12px; line-height:1.6;">
                    Permohonan ini akan diproses melalui 3 tahap: Customer Service → Finance → Security.
                    Pastikan pengajuan dilakukan minimal 1 hari sebelum pelaksanaan.
                </div>

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
                        Kirim Permohonan
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
        <div style="position:relative; width:100%; max-width:480px; background:white; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.2); display:flex; flex-direction:column; max-height:90vh; overflow:hidden;">

            <div style="padding:20px 24px 16px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
                <div>
                    <h3 style="font-size:15px; font-weight:700; color:#111827; margin:0;">Detail Izin</h3>
                    <p style="font-size:11px; font-family:monospace; color:#1a6b9a; margin:2px 0 0;">{{ $detail->nomor }}</p>
                </div>
                <button wire:click="closePanel"
                        style="width:32px; height:32px; border-radius:50%; background:#f3f4f6; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#6b7280; font-size:16px; font-weight:700; flex-shrink:0; transition:background 0.15s;"
                        onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">✕</button>
            </div>

            <div style="padding:20px 24px; display:flex; flex-direction:column; gap:14px; overflow-y:auto; flex:1;">
                @php
                    $sc = match($detail->status) {
                        'Pesan Diterima'              => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                        'Approve by Customer Service' => 'bg-blue-100 text-blue-800 border-blue-200',
                        'Approve by FA'               => 'bg-purple-100 text-purple-800 border-purple-200',
                        'Approve by Security'         => 'bg-green-100 text-green-800 border-green-200',
                        'Tidak Disetujui'             => 'bg-red-100 text-red-700 border-red-200',
                        default                       => 'bg-gray-100 text-gray-600 border-gray-200',
                    };
                @endphp
                <div class="inline-block px-3 py-1.5 rounded-full text-xs font-bold border {{ $sc }}">
                    {{ $detail->status }}
                </div>

                {{-- Progress Timeline --}}
                <div class="space-y-2">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Progress Approval</p>
                    @php
                        $stages = [
                            ['label' => 'Customer Service', 'by' => $detail->approved_cs_by, 'at' => $detail->approved_cs_at],
                            ['label' => 'Finance & Accounting', 'by' => $detail->approved_fa_by, 'at' => $detail->approved_fa_at],
                            ['label' => 'Security', 'by' => $detail->approved_sec_by, 'at' => $detail->approved_sec_at],
                        ];
                    @endphp
                    @foreach($stages as $stage)
                    <div class="flex items-center gap-3 py-2 px-3 rounded-xl {{ $stage['by'] ? 'bg-green-50 border border-green-100' : 'bg-gray-50 border border-gray-100' }}">
                        <div class="w-5 h-5 rounded-full flex items-center justify-center shrink-0
                                    {{ $stage['by'] ? 'bg-green-500' : 'bg-gray-300' }}">
                            @if($stage['by'])
                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            @else
                            <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-700">{{ $stage['label'] }}</p>
                            @if($stage['by'])
                            <p class="text-[10px] text-gray-400">{{ $stage['by'] }} · {{ $stage['at']?->format('d/m/Y H:i') }}</p>
                            @else
                            <p class="text-[10px] text-gray-400">Menunggu</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                @foreach([
                    ['Jenis',          $detail->jenis],
                    ['Tanggal Ajuan',  $detail->tanggal?->format('d/m/Y')],
                    ['Tanggal Ijin',   $detail->tanggal_ijin?->format('d/m/Y')],
                    ['Jam',            $detail->jam],
                    ['Pemohon',        $detail->request_by],
                ] as [$label, $val])
                <div class="flex gap-3">
                    <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">{{ $label }}</span>
                    <span class="text-xs font-medium text-gray-800">{{ $val }}</span>
                </div>
                @endforeach

                <div>
                    <span class="text-xs text-gray-400 block mb-1">Keterangan</span>
                    <p class="text-xs text-gray-800 bg-gray-50 rounded-xl p-3 leading-relaxed">{{ $detail->descs }}</p>
                </div>

                @if($detail->foto)
                <div>
                    <span class="text-xs text-gray-400 block mb-1.5">Foto</span>
                    <img src="{{ asset('storage/' . $detail->foto) }}" alt="Foto"
                         class="w-full rounded-xl border border-gray-200 object-cover max-h-56">
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
