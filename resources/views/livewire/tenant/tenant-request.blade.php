<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;
use App\Models\TenantRequest;
use App\Models\WorkOrder;
use App\Models\HandoverUnit;

new #[Layout('layouts.tenant')] class extends Component {
    use WithPagination, WithFileUploads;

    public ?string $panelMode = null; // null | 'input' | 'detail'
    public ?int $detailId = null;
    public ?int $editId   = null;
    public string $photoTab = 'tenant'; // 'tenant' | 'staff'

    // Form fields
    public string $formKategori     = '';
    public string $formSubKategori  = '';
    public string $formDescs        = '';
    public bool   $formBerulang     = false;
    public        $formFoto         = null;

    public string $successMsg = '';

    // Bukti bayar WO
    public int    $buktiWoId       = 0;
    public        $buktiWoFile     = null;
    public string $uploadBuktiMsg  = '';
    public bool   $uploadBuktiOk   = false;

    public function openInput(): void
    {
        $this->editId          = null;
        $this->panelMode       = 'input';
        $this->formKategori    = '';
        $this->formSubKategori = '';
        $this->formDescs       = '';
        $this->formBerulang    = false;
        $this->formFoto        = null;
        $this->successMsg      = '';
    }

    public function openEdit(int $id): void
    {
        $req = TenantRequest::find($id);
        if (! $req || $req->status !== 'Pesan Diterima') return;

        $this->editId          = $id;
        $this->panelMode       = 'input';
        $this->formKategori    = $req->kategori ?? '';
        $this->formSubKategori = $req->sub_kategori ?? '';
        $this->formDescs       = $req->descs ?? '';
        $this->formBerulang    = $req->berulang === 'Ya';
        $this->formFoto        = null;
        $this->successMsg      = '';
    }

    public function cancelRequest(int $id): void
    {
        $req = TenantRequest::find($id);
        if (! $req || $req->status !== 'Pesan Diterima') return;

        $req->update([
            'status'      => 'Tidak Dapat Diaplikasi',
            'desc_status' => 'Dibatalkan oleh Tenant',
            'is_selesai'  => true,
        ]);
        $this->successMsg = "Permintaan {$req->no_request} berhasil dibatalkan.";
        $this->resetPage();
    }

    public function openDetail(int $id): void
    {
        $this->detailId  = $id;
        $this->panelMode = 'detail';
        $this->photoTab  = 'tenant';
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
            'formKategori' => 'required',
            'formDescs'    => 'required|min:10',
            'formFoto'     => 'nullable|image|max:8192',
        ], [
            'formKategori.required' => 'Kategori wajib dipilih.',
            'formDescs.required'    => 'Isi laporan wajib diisi.',
            'formDescs.min'         => 'Isi laporan minimal 10 karakter.',
        ]);

        $user = auth()->user();

        if ($isEdit) {
            // UPDATE
            $req = TenantRequest::findOrFail($this->editId);
            $data = [
                'kategori'     => $this->formKategori,
                'sub_kategori' => $this->formSubKategori ?: null,
                'descs'        => $this->formDescs,
                'berulang'     => $this->formBerulang ? 'Ya' : 'Tidak',
            ];
            if ($this->formFoto) {
                $data['foto'] = $this->formFoto->store('tenant-requests', 'public');
            }
            $req->update($data);
            $this->successMsg = "Permintaan {$req->no_request} berhasil diperbarui.";

        } else {
            // CREATE
            $tenantProfile = $user->tenant;
            $foto = null;
            if ($this->formFoto) {
                $foto = $this->formFoto->store('tenant-requests', 'public');
            }
            $romans  = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
            $roman   = $romans[now()->month - 1];
            $year    = now()->year;
            $last    = TenantRequest::orderByDesc('id')->first();
            $nextNum = 1;
            if ($last && preg_match('/R(\d+)/', $last->no_request, $m)) {
                $nextNum = (int) $m[1] + 1;
            }
            $noRequest = sprintf('R%07d/%s/%d-MAP', $nextNum, $roman, $year);

            $lotNo    = $tenantProfile?->unit_number ?? '';
            $handover = $lotNo ? HandoverUnit::whereRaw('UPPER(lot_no) = ?', [strtoupper($lotNo)])->first() : null;
            $tglStr   = $handover?->str_date?->format('Y-m-d') ?? now()->toDateString();

            TenantRequest::create([
                'no_request'   => $noRequest,
                'tanggal'      => now(),
                'tgl_str'      => $tglStr,
                'lot_no'       => $lotNo,
                'nama'         => $user->name,
                'kepemilikan'  => $tenantProfile?->status ?? 'pemilik',
                'kategori'     => $this->formKategori,
                'sub_kategori' => $this->formSubKategori ?: null,
                'pelaporan_via'=> 'aplikasi',
                'descs'        => $this->formDescs,
                'request_by'   => $user->name,
                'status'       => 'Pesan Diterima',
                'berulang'     => $this->formBerulang ? 'Ya' : 'Tidak',
                'input_by'     => $user->name,
                'foto'         => $foto,
            ]);
            $this->successMsg = "Permintaan {$noRequest} berhasil dikirim.";
        }

        $this->panelMode = null;
        $this->editId    = null;
        $this->resetPage();
    }

    public function uploadBuktiWo(int $woId): void
    {
        $this->buktiWoId = $woId;

        $this->validate([
            'buktiWoFile' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'buktiWoFile.required' => 'Pilih file bukti pembayaran.',
            'buktiWoFile.image'    => 'File harus berupa gambar.',
            'buktiWoFile.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        $wo = WorkOrder::findOrFail($woId);

        if ($wo->bukti_bayar_wo && Storage::disk('public')->exists($wo->bukti_bayar_wo)) {
            Storage::disk('public')->delete($wo->bukti_bayar_wo);
        }

        $path = $this->buktiWoFile->store('bukti-bayar-wo', 'public');
        $wo->update([
            'bukti_bayar_wo'     => $path,
            'tgl_bukti_bayar_wo' => now(),
            'fin_status'         => null,
        ]);

        $this->buktiWoFile    = null;
        $this->uploadBuktiMsg = 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi dari CS/Finance.';
        $this->uploadBuktiOk  = true;
    }

    public function with(): array
    {
        $unit = auth()->user()->tenant?->unit_number;

        $requests = TenantRequest::when($unit, fn($q) => $q->where('lot_no', $unit))
            ->orderByDesc('tanggal')
            ->paginate(10);

        $detail  = $this->detailId ? TenantRequest::find($this->detailId) : null;
        $listWos = $detail
            ? WorkOrder::where('no_complain', $detail->no_request)->get()
            : collect();

        return compact('requests', 'detail', 'listWos');
    }
};
?>

<div>
@if($panelMode !== 'detail')
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
            <span class="text-xs font-semibold text-gray-700">Tenant Request</span>
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
                <h2 class="text-lg font-bold text-gray-800">Permintaan Saya</h2>
                <p class="text-xs text-gray-400 mt-0.5">Daftar permintaan dan keluhan yang Anda ajukan</p>
            </div>
            <button wire:click="openInput"
                    style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; background:#1a5c2e; color:white; font-size:12px; font-weight:600; border-radius:12px; border:none; cursor:pointer; box-shadow:0 1px 4px rgba(0,0,0,0.15); transition:background 0.15s;"
                    onmouseover="this.style.background='#154d26'" onmouseout="this.style.background='#1a5c2e'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Buat Permintaan
            </button>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            @if($requests->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="w-16 h-16 rounded-full bg-amber-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-600 mb-1">Belum ada permintaan</p>
                <p class="text-xs text-gray-400">Klik "Buat Permintaan" untuk mengajukan laporan baru</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">No. Request</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Tanggal</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Kategori</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Deskripsi</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($requests as $req)
                        @php $canEdit = $req->status === 'Pesan Diterima'; @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-[#1a6b9a] font-semibold">{{ $req->no_request }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $req->tanggal?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $req->kategori }}</td>
                            <td class="px-4 py-3 text-gray-600 max-w-[200px]">
                                <span class="line-clamp-2">{{ $req->descs }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $sc = match($req->status) {
                                        'Pesan Diterima'         => 'bg-yellow-100 text-yellow-800',
                                        'Dalam Pengecekan'       => 'bg-blue-100 text-blue-800',
                                        'Dalam Proses'           => 'bg-orange-100 text-orange-800',
                                        'Selesai'                => 'bg-green-100 text-green-800',
                                        'Tidak Dapat Diaplikasi' => 'bg-gray-100 text-gray-600',
                                        default                  => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-semibold {{ $sc }}">
                                    {{ $req->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    <button wire:click="openDetail({{ $req->id }})"
                                            style="font-size:11px; font-weight:600; color:#1a5c2e; background:none; border:none; cursor:pointer; padding:0; text-decoration:underline;">
                                        Lihat
                                    </button>
                                    @if($canEdit)
                                    <span style="color:#d1d5db; font-size:10px;">|</span>
                                    <button wire:click="openEdit({{ $req->id }})"
                                            style="font-size:11px; font-weight:600; color:#d97706; background:none; border:none; cursor:pointer; padding:0; text-decoration:underline;">
                                        Edit
                                    </button>
                                    <span style="color:#d1d5db; font-size:10px;">|</span>
                                    <button wire:click="cancelRequest({{ $req->id }})"
                                            wire:confirm="Batalkan permintaan ini? Tindakan ini tidak dapat diubah."
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

            @if($requests->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span>{{ $requests->firstItem() }}–{{ $requests->lastItem() }} dari {{ $requests->total() }}</span>
                <div class="flex gap-1">
                    @if(!$requests->onFirstPage())
                    <button wire:click="previousPage" class="px-2.5 py-1 rounded-lg border border-gray-200 hover:bg-gray-100">‹</button>
                    @endif
                    <span class="px-3 py-1 rounded-lg bg-gray-100 font-medium">{{ $requests->currentPage() }}</span>
                    @if($requests->hasMorePages())
                    <button wire:click="nextPage" class="px-2.5 py-1 rounded-lg border border-gray-200 hover:bg-gray-100">›</button>
                    @endif
                </div>
            </div>
            @endif
            @endif
        </div>
    </div>
@endif

    {{-- ══════════════ INPUT PANEL (centered modal) ══════════════ --}}
    @if($panelMode === 'input')
    <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:16px; background:rgba(0,0,0,0.5);"
         wire:click.self="closePanel">
        <div style="position:relative; width:100%; max-width:480px; background:white; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.2); display:flex; flex-direction:column; max-height:90vh; overflow:hidden;">

            {{-- Header --}}
            <div style="padding:20px 24px 16px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
                <div>
                    <h3 style="font-size:15px; font-weight:700; color:#111827; margin:0;">{{ $editId ? 'Edit Permintaan' : 'Buat Permintaan' }}</h3>
                    <p style="font-size:11px; color:#9ca3af; margin:2px 0 0;">{{ $editId ? 'Ubah detail laporan yang sudah dikirim' : 'Isi form berikut untuk mengajukan laporan' }}</p>
                </div>
                <button wire:click="closePanel"
                        style="width:32px; height:32px; border-radius:50%; background:#f3f4f6; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#6b7280; font-size:16px; font-weight:700; flex-shrink:0; transition:background 0.15s;"
                        onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">✕</button>
            </div>

            {{-- Scrollable body --}}
            <form wire:submit="save" style="padding:20px 24px; display:flex; flex-direction:column; gap:16px; overflow-y:auto; flex:1;">

                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px;">Kategori Laporan <span style="color:#ef4444;">*</span></label>
                    <select wire:model="formKategori" style="width:100%; font-size:13px; border:1px solid #d1d5db; border-radius:10px; padding:9px 12px; outline:none; background:white; color:#374151;">
                        <option value="">— Pilih Kategori —</option>
                        @foreach(\App\Models\TenantRequest::kategoriOptions() as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('formKategori') <p style="font-size:11px; color:#ef4444; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px;">Sub Kategori <span style="font-weight:400; color:#9ca3af;">(opsional)</span></label>
                    <select wire:model="formSubKategori" style="width:100%; font-size:13px; border:1px solid #d1d5db; border-radius:10px; padding:9px 12px; outline:none; background:white; color:#374151;">
                        <option value="">— Pilih Sub Kategori —</option>
                        @foreach(\App\Models\TenantRequest::subKategoriOptions() as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px;">Isi Laporan / Deskripsi <span style="color:#ef4444;">*</span></label>
                    <textarea wire:model="formDescs" rows="4" placeholder="Jelaskan masalah atau permintaan Anda secara detail..."
                              style="width:100%; font-size:13px; border:1px solid #d1d5db; border-radius:10px; padding:9px 12px; outline:none; resize:none; font-family:inherit; box-sizing:border-box;"></textarea>
                    @error('formDescs') <p style="font-size:11px; color:#ef4444; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="checkbox" wire:model="formBerulang" style="width:15px; height:15px; cursor:pointer; flex-shrink:0; accent-color:#1a5c2e;">
                    <span style="font-size:13px; color:#374151;">Masalah ini berulang / sudah pernah terjadi sebelumnya</span>
                </label>

                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:6px;">Upload Foto <span style="font-weight:400; color:#9ca3af;">(opsional, maks 8 MB)</span></label>
                    <input type="file" wire:model="formFoto" accept="image/*" style="width:100%; font-size:12px; color:#6b7280;">
                    @error('formFoto') <p style="font-size:11px; color:#ef4444; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div style="font-size:11px; color:#9ca3af; background:#f9fafb; border-radius:10px; padding:10px 12px; line-height:1.6;">
                    Data unit, nama, dan metode pelaporan akan otomatis terisi dari profil Anda.
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
                        {{ $editId ? 'Simpan Perubahan' : 'Kirim Permintaan' }}
                    </button>
                </div>

            </form>
        </div>
    </div>
    @endif

    {{-- ══════════════ DETAIL — Full Page View ══════════════ --}}
    @if($panelMode === 'detail' && $detail)
    @php
        $unit = auth()->user()->tenant?->unit_number ?? '';
        $stepOrder = ['Pesan Diterima' => 0, 'Dalam Pengecekan' => 1, 'Dalam Proses' => 2, 'Selesai' => 3];
        $currentStep = $stepOrder[$detail->status] ?? 0;
        $steps = [
            [
                'label' => 'Pesan Diterima',
                'date'  => $detail->tanggal,
                'icon'  => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            ],
            [
                'label' => 'Dalam Pengecekan',
                'date'  => $detail->tgl_verifikasi,
                'icon'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Dalam Proses',
                'date'  => $detail->tgl_dalam_proses,
                'icon'  => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            ],
            [
                'label' => 'Selesai',
                'date'  => $detail->tgl_selesai,
                'icon'  => 'M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5',
            ],
        ];
    @endphp

    {{-- Beige full-page background --}}
    <div class="min-h-screen" style="background-color: #f0ece4; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400' viewBox='0 0 400 400'%3E%3Cellipse cx='300' cy='80' rx='140' ry='200' fill='none' stroke='%23c8b89a' stroke-width='1.5' opacity='0.3' transform='rotate(-20 300 80)'/%3E%3Cellipse cx='350' cy='150' rx='90' ry='160' fill='none' stroke='%23c8b89a' stroke-width='1' opacity='0.2' transform='rotate(-30 350 150)'/%3E%3Cellipse cx='280' cy='120' rx='60' ry='120' fill='none' stroke='%23c8b89a' stroke-width='1' opacity='0.15' transform='rotate(-10 280 120)'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: top right; background-size: 380px;">

        {{-- Back button --}}
        <div class="px-6 pt-5 pb-1">
            <button wire:click="closePanel" class="flex items-center gap-1.5 text-sm font-medium" style="color:#5a4e3a;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </button>
        </div>

        {{-- Heading --}}
        <div class="px-6 pt-4 pb-6">
            <h1 class="font-black uppercase tracking-wider leading-none" style="font-size: 2.4rem; color: #5a4e3a;">CUSTOMER REQUEST</h1>
            <p class="font-bold tracking-widest mt-1" style="color: #5a4e3a; font-size: 1rem;">{{ strtoupper($unit) }}</p>
        </div>

        {{-- Progress Card --}}
        <div class="mx-4 mb-4 bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 py-5">
                <div class="flex items-start justify-between">
                    @foreach($steps as $i => $step)
                    @php $done = $i <= $currentStep; @endphp
                    <div class="flex flex-col items-center flex-1 relative">
                        {{-- Connector line --}}
                        @if($i < count($steps) - 1)
                        <div class="absolute top-7 left-1/2 w-full h-px"
                             style="background-color: {{ $done && $i < $currentStep ? '#1a5c2e' : '#e5e7eb' }};"></div>
                        @endif

                        {{-- Icon circle --}}
                        <div class="relative z-10 w-14 h-14 rounded-full flex items-center justify-center mb-2"
                             style="background-color: {{ $done ? 'white' : 'transparent' }}; border: {{ $done ? '0' : '0' }};">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24"
                                 stroke="{{ $done ? '#1a5c2e' : '#d1d5db' }}" stroke-width="1.8">
                                @if($i === 0)
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                @elseif($i === 1)
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                @elseif($i === 2)
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                                @else
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                @endif
                            </svg>
                        </div>

                        {{-- Label --}}
                        <p class="text-center leading-tight font-medium"
                           style="font-size: 10px; color: {{ $done ? '#1a5c2e' : '#9ca3af' }}; max-width: 70px;">
                            {{ $step['label'] }}
                        </p>

                        {{-- Date --}}
                        @if($done && $step['date'])
                        <p class="text-center font-semibold mt-1" style="font-size: 10px; color: #cc0000;">
                            {{ $step['date'] instanceof \Carbon\Carbon ? $step['date']->translatedFormat('j M Y') : \Carbon\Carbon::parse($step['date'])->translatedFormat('j M Y') }}
                        </p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Request Info --}}
            <div class="border-t border-gray-100 px-5 py-3">
                <p class="font-mono text-sm text-gray-700 font-medium">{{ $detail->no_request }}</p>
                <p class="text-sm text-gray-600 mt-0.5">{{ $detail->sub_kategori ?: ($detail->kategori ?: '—') }}</p>
            </div>

            {{-- Description --}}
            @if($detail->descs)
            <div class="px-5 py-3 border-t border-gray-100">
                <p class="text-sm text-gray-700 leading-relaxed">{{ $detail->descs }}</p>
            </div>
            @endif

            {{-- CS Note --}}
            @if($detail->desc_status)
            <div class="px-5 py-3 border-t border-gray-100 bg-blue-50">
                <p class="text-[11px] font-semibold text-blue-700 mb-0.5">Catatan CS</p>
                <p class="text-xs text-blue-800 leading-relaxed">{{ $detail->desc_status }}</p>
            </div>
            @endif
        </div>

        {{-- Photo Section --}}
        <div class="mx-4 mb-4 bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 flex items-center gap-4 border-b border-gray-100">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Upload Picture :</span>
                <div class="flex gap-1">
                    <button wire:click="$set('photoTab','tenant')"
                            class="px-4 py-1 text-xs font-semibold rounded transition-colors"
                            style="{{ $photoTab === 'tenant' ? 'background-color:#e8c850; color:#3a2e00;' : 'background-color:#f3f4f6; color:#6b7280;' }}">
                        TENANT
                    </button>
                    <button wire:click="$set('photoTab','staff')"
                            class="px-4 py-1 text-xs font-semibold rounded transition-colors"
                            style="{{ $photoTab === 'staff' ? 'background-color:#e8c850; color:#3a2e00;' : 'background-color:#f3f4f6; color:#6b7280;' }}">
                        STAFF
                    </button>
                </div>
            </div>

            <div class="p-4" x-data="{ lightbox: false }">
                @if($photoTab === 'tenant')
                    @if($detail->foto)
                    <img src="{{ asset('storage/' . $detail->foto) }}" alt="Foto Tenant"
                         class="rounded-xl object-contain max-h-48 max-w-xs border border-gray-100 cursor-zoom-in"
                         @click="lightbox = true">
                    {{-- Lightbox --}}
                    <div x-show="lightbox" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
                         @click="lightbox = false">
                        <img src="{{ asset('storage/' . $detail->foto) }}" alt="Foto Tenant"
                             class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain shadow-2xl">
                        <button class="absolute top-4 right-4 text-white text-2xl leading-none" @click="lightbox = false">✕</button>
                    </div>
                    @else
                    <div class="py-8 text-center text-sm text-gray-400">Belum ada foto dari tenant.</div>
                    @endif
                @else
                    @php
                        $staffPhotos = [];
                        foreach ($listWos as $wo) {
                            if ($wo->foto_pengecekan) $staffPhotos[] = ['label' => 'Pengecekan', 'path' => $wo->foto_pengecekan];
                            if ($wo->foto_close)      $staffPhotos[] = ['label' => 'Closing WO', 'path' => $wo->foto_close];
                        }
                    @endphp
                    @if(count($staffPhotos))
                        <div class="flex flex-wrap gap-3">
                            @foreach($staffPhotos as $sp)
                            <div x-data="{ lb: false }">
                                <p class="text-[10px] text-gray-400 mb-1">{{ $sp['label'] }}</p>
                                <img src="{{ asset('storage/' . $sp['path']) }}" alt="{{ $sp['label'] }}"
                                     class="rounded-xl object-contain max-h-48 max-w-xs border border-gray-100 cursor-zoom-in"
                                     @click="lb = true">
                                <div x-show="lb" x-cloak
                                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
                                     @click="lb = false">
                                    <img src="{{ asset('storage/' . $sp['path']) }}" alt="{{ $sp['label'] }}"
                                         class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain shadow-2xl">
                                    <button class="absolute top-4 right-4 text-white text-2xl leading-none" @click="lb = false">✕</button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                    <div class="py-8 text-center text-sm text-gray-400">Belum ada foto dari staff.</div>
                    @endif
                @endif
            </div>
        </div>

        {{-- LIST WO --}}
        @if($listWos->isNotEmpty())
        <div class="mx-4 mb-6 space-y-3">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-1">List WO</p>
            @foreach($listWos as $wo)
            @php
                $woItems   = $wo->item_service ?? [];
                $woTotal   = collect($woItems)->sum(fn($i) => ($i['harga'] ?? 0) * ($i['qty'] ?? 1));
                $hasBilling = count($woItems) > 0 && $woTotal > 0;
                $finSt     = $wo->fin_status;
            @endphp
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                {{-- WO Header --}}
                <div class="px-5 py-3 flex items-center gap-3 {{ $hasBilling ? 'border-b border-gray-100' : '' }}">
                    <div class="flex-1 min-w-0">
                        <p class="font-mono text-sm font-semibold text-[#1a6b9a]">{{ $wo->no_wo }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $wo->status_comp }}</p>
                    </div>
                    @if($hasBilling)
                        @if($finSt === 'Approved')
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-600 text-white">✔ LUNAS</span>
                        @elseif($finSt === 'Rejected')
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-600 text-white">✖ DITOLAK</span>
                        @elseif($wo->bukti_bayar_wo)
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-600 text-white">Sedang Diverifikasi</span>
                        @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500 text-white">Perlu Pembayaran</span>
                        @endif
                    @endif
                    @if($wo->assign_staff)
                    <div class="text-xs text-gray-600 shrink-0">{{ $wo->assign_staff }}</div>
                    @endif
                </div>

                {{-- Tagihan --}}
                @if($hasBilling)
                <div class="px-5 py-4 {{ $finSt === 'Approved' ? 'bg-green-50' : ($finSt === 'Rejected' ? 'bg-red-50' : 'bg-amber-50') }}">
                    <p class="text-[11px] font-bold text-gray-700 mb-2">Tagihan Work Order</p>
                    @if($wo->keterangan_biaya)
                    <p class="text-[11px] text-gray-500 italic mb-2">{{ $wo->keterangan_biaya }}</p>
                    @endif
                    <table class="w-full text-[11px] border-collapse mb-3">
                        <thead>
                            <tr class="bg-white/60">
                                <th class="text-left px-2 py-1 border border-gray-200 font-semibold text-gray-600">Item</th>
                                <th class="text-center px-2 py-1 border border-gray-200 w-10 font-semibold text-gray-600">Qty</th>
                                <th class="text-right px-2 py-1 border border-gray-200 w-24 font-semibold text-gray-600">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($woItems as $item)
                            <tr class="bg-white/40">
                                <td class="px-2 py-1 border border-gray-200">{{ $item['nama'] }}</td>
                                <td class="px-2 py-1 border border-gray-200 text-center">{{ $item['qty'] }}</td>
                                <td class="px-2 py-1 border border-gray-200 text-right font-semibold">
                                    Rp {{ number_format(($item['harga'] ?? 0) * ($item['qty'] ?? 1), 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-bold bg-white/70">
                                <td colspan="2" class="px-2 py-1 border border-gray-200 text-right">Total</td>
                                <td class="px-2 py-1 border border-gray-200 text-right text-blue-700">
                                    Rp {{ number_format($woTotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    @if($finSt === 'Rejected')
                    <div class="bg-red-100 rounded-lg p-2 text-[11px] text-red-700 mb-3">
                        <strong>Ditolak:</strong> {{ $wo->fin_notes ?? 'Silakan hubungi CS.' }}
                    </div>
                    @endif

                    @if($wo->bukti_bayar_wo)
                    <div class="mb-3">
                        <p class="text-[10px] text-gray-500 mb-1">Bukti diupload: {{ $wo->tgl_bukti_bayar_wo?->format('d/m/Y H:i') }}</p>
                        <img src="{{ asset('storage/' . $wo->bukti_bayar_wo) }}"
                             class="max-h-36 rounded border border-gray-200 object-contain">
                    </div>
                    @endif

                    @if($finSt !== 'Approved')
                    {{-- Upload area --}}
                    @if($uploadBuktiMsg && $buktiWoId === 0)
                    <div class="mb-2 text-xs {{ $uploadBuktiOk ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100' }} rounded px-3 py-2">
                        {{ $uploadBuktiMsg }}
                    </div>
                    @endif
                    <div class="border-t border-dashed border-gray-300 pt-3">
                        <p class="text-[11px] font-semibold text-gray-700 mb-2">
                            {{ $wo->bukti_bayar_wo ? 'Ganti Bukti Pembayaran' : 'Upload Bukti Pembayaran' }}
                        </p>
                        <input type="file" wire:model="buktiWoFile" accept="image/*"
                               class="text-[11px] text-gray-600 block mb-2">
                        @if($buktiWoFile)
                        <img src="{{ $buktiWoFile->temporaryUrl() }}"
                             class="max-h-32 rounded border border-gray-200 object-contain mb-2">
                        @endif
                        @error('buktiWoFile') <p class="text-red-500 text-[10px] mb-1">{{ $message }}</p> @enderror
                        <button wire:click="uploadBuktiWo({{ $wo->id }})"
                                wire:loading.attr="disabled"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded disabled:opacity-60">
                            <span wire:loading.remove wire:target="uploadBuktiWo">Upload Bukti Bayar</span>
                            <span wire:loading wire:target="uploadBuktiWo">Mengupload...</span>
                        </button>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

    </div>
    @endif
</div>
