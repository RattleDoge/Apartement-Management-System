<?php

use App\Models\Staff;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination;

    public bool   $showPanel  = false;
    public ?int   $editId     = null;
    public ?int   $selectedId = null;
    public string $panelMode  = 'add'; // 'add' | 'edit'

    public string $fNamaStaff  = '';
    public string $fDepartemen = '';
    public string $fStatus     = 'Aktif';
    public string $fNoHpOtp    = '';
    public string $fEmail      = '';
    public string $fRole       = '';
    public string $fFingerId   = '';
    public string $fPt         = 'MAP';
    public string $fProject    = 'Madison Park (MAP)';

    public string $savedMsg    = '';
    public string $search      = '';
    public string $fFilterDept = '';
    public string $fFilterStatus = '';

    private function canManage(): bool
    {
        return auth()->user()->role !== 'tenant';
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFFilterDept(): void { $this->resetPage(); }
    public function updatedFFilterStatus(): void { $this->resetPage(); }

    public function selectRow(int $id): void
    {
        $this->selectedId = ($this->selectedId === $id) ? null : $id;
    }

    public function openAdd(): void
    {
        if (! $this->canManage()) return;
        $this->selectedId = null;
        $this->resetForm();
        $this->panelMode = 'add';
        $this->showPanel = true;
    }

    public function openEdit(int $id): void
    {
        if (! $this->canManage()) return;
        $s = Staff::findOrFail($id);
        $this->editId      = $id;
        $this->fNamaStaff  = $s->nama_staff;
        $this->fDepartemen = $s->departemen ?? '';
        $this->fStatus     = $s->status;
        $this->fNoHpOtp    = $s->no_hp_otp ?? '';
        $this->fEmail      = $s->email ?? '';
        $this->fRole       = $s->role ?? '';
        $this->fFingerId   = $s->finger_id ?? '';
        $this->fPt         = $s->pt ?? 'MAP';
        $this->fProject    = $s->project ?? 'Madison Park (MAP)';
        $this->panelMode   = 'edit';
        $this->showPanel   = true;
    }

    public function save(): void
    {
        if (! $this->canManage()) return;

        $this->validate([
            'fNamaStaff'  => 'required|string|max:255',
            'fDepartemen' => 'nullable|string|max:50',
            'fStatus'     => 'required|in:Aktif,Non-aktif',
            'fNoHpOtp'    => 'nullable|string|max:30',
            'fEmail'      => 'nullable|email|max:255',
            'fRole'       => 'nullable|string|max:50',
            'fFingerId'   => 'nullable|string|max:30',
        ]);

        $data = [
            'nama_staff'  => $this->fNamaStaff,
            'departemen'  => $this->fDepartemen ?: null,
            'status'      => $this->fStatus,
            'no_hp_otp'   => $this->fNoHpOtp ?: null,
            'email'       => $this->fEmail ?: null,
            'role'        => $this->fRole ?: null,
            'finger_id'   => $this->fFingerId ?: null,
            'pt'          => 'MAP',
            'project'     => 'Madison Park (MAP)',
        ];

        if ($this->editId) {
            Staff::findOrFail($this->editId)->update($data);
            $this->savedMsg = 'Data staff berhasil diperbarui.';
        } else {
            Staff::create($data);
            $this->savedMsg = 'Staff berhasil ditambahkan.';
        }

        $this->showPanel = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        if (! $this->canManage()) return;
        Staff::findOrFail($id)->delete();
        $this->savedMsg = 'Staff berhasil dihapus.';
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $staffList = Staff::when($this->search, fn($q) => $q->where('nama_staff', 'like', "%{$this->search}%"))
            ->when($this->fFilterDept, fn($q) => $q->where('departemen', $this->fFilterDept))
            ->when($this->fFilterStatus, fn($q) => $q->where('status', $this->fFilterStatus))
            ->orderBy('nama_staff')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="staff-' . now()->format('Ymd') . '.csv"',
        ];

        return response()->streamDownload(function () use ($staffList) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['NO', 'NAMA STAFF', 'DEPARTMENT', 'STATUS', 'NO. HANDPHONE', 'EMAIL', 'ROLE', 'FINGER ID', 'PT', 'PROYEK']);
            foreach ($staffList as $i => $s) {
                fputcsv($out, [
                    $i + 1,
                    $s->nama_staff,
                    $s->departemen,
                    $s->status,
                    $s->no_hp_otp,
                    $s->email,
                    $s->role,
                    $s->finger_id,
                    $s->pt,
                    $s->project,
                ]);
            }
            fclose($out);
        }, 'staff-' . now()->format('Ymd') . '.csv', $headers);
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editId      = null;
        $this->fNamaStaff  = '';
        $this->fDepartemen = '';
        $this->fStatus     = 'Aktif';
        $this->fNoHpOtp    = '';
        $this->fEmail      = '';
        $this->fRole       = '';
        $this->fFingerId   = '';
        $this->fPt         = 'MAP';
        $this->fProject    = 'MAP';
        $this->savedMsg    = '';
    }

    public function with(): array
    {
        $staffList = Staff::when($this->search, fn($q) => $q->where('nama_staff', 'like', "%{$this->search}%"))
            ->when($this->fFilterDept,   fn($q) => $q->where('departemen', $this->fFilterDept))
            ->when($this->fFilterStatus, fn($q) => $q->where('status', $this->fFilterStatus))
            ->orderBy('nama_staff')
            ->paginate(20);

        $registeredStaffIds = \App\Models\Karyawan::whereNotNull('staff_id')->pluck('staff_id')->toArray();

        $canManage   = $this->canManage();
        $deptOptions = Staff::departemenOptions();
        return compact('staffList', 'canManage', 'deptOptions', 'registeredStaffIds');
    }
}
?>

<div class="p-5">

    {{-- Header --}}
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-3">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            TABLE STAFF
        </div>
    </div>

    {{-- Filters (full width, di atas tabel) --}}
    <div class="flex items-center gap-3 flex-wrap mb-2">
        @if($savedMsg)
        <span class="text-xs text-green-700 bg-green-50 border border-green-200 rounded px-3 py-1.5">{{ $savedMsg }}</span>
        @endif
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="Cari nama..."
               class="text-xs border border-gray-300 rounded px-3 py-1.5 w-44 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
        <select wire:model.live="fFilterDept"
                class="text-xs border border-gray-300 rounded px-3 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]"
                style="min-width:120px; padding-right:1.8rem;">
            <option value="">Semua Dept</option>
            @foreach($deptOptions as $d)
            <option value="{{ $d }}">{{ $d }}</option>
            @endforeach
        </select>
        <select wire:model.live="fFilterStatus"
                class="text-xs border border-gray-300 rounded px-3 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]"
                style="min-width:130px; padding-right:1.8rem;">
            <option value="">Semua Status</option>
            <option value="Aktif">Aktif</option>
            <option value="Non-aktif">Non-aktif</option>
        </select>
    </div>

    {{-- Table (full width) --}}
    <div class="bg-white border border-blue-200 rounded-lg overflow-x-auto shadow-sm">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-8">#</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold min-w-44">NAME</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-24">DEPARTMENT</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">STATUS</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-36">NO. HANDPHONE</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold min-w-40">EMAIL</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-24">ROLE</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-24">FINGER</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-16">PT</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">PROYEK</th>
                </tr>
                {{-- Filter row visual --}}
                <tr class="bg-white">
                    @foreach(range(1, 10) as $col)
                    <td class="border border-gray-200 px-1 py-0.5">
                        <input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50">
                    </td>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($staffList as $i => $s)
                @php
                    $isSel      = $selectedId === $s->id;
                    $isReg      = in_array($s->id, $registeredStaffIds);
                    $incomplete = ! ($s->email && $s->finger_id && $s->no_hp_otp);
                    $rowBg      = $isSel ? 'bg-blue-50' : ($s->status === 'Non-aktif' ? 'bg-gray-50 opacity-60' : '');
                @endphp
                <tr wire:click="selectRow({{ $s->id }})"
                    class="border-b border-gray-100 hover:bg-[#f0f8f0] transition-colors cursor-pointer {{ $rowBg }}"
                    style="{{ $isSel ? 'background:#dbeafe;' : '' }}">
                    <td class="border border-gray-100 px-3 py-1.5 text-center text-gray-500">
                        {{ ($staffList->currentPage() - 1) * $staffList->perPage() + $i + 1 }}
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 font-semibold text-[#1a5c2e]">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span>{{ $s->nama_staff }}</span>
                            @if($isReg)
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                                ✓ Terdaftar
                            </span>
                            @elseif($incomplete && $s->status === 'Aktif')
                            <span title="Email / Finger ID / No. HP belum lengkap — karyawan tidak bisa daftar akun"
                                  class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-700 border border-amber-200 whitespace-nowrap cursor-help">
                                ⚠ Data Kurang
                            </span>
                            @endif
                        </div>
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center font-mono text-gray-600">{{ $s->departemen ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold
                            {{ $s->status === 'Aktif' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500' }}">
                            {{ $s->status }}
                        </span>
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 font-mono">{{ $s->no_hp_otp ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-gray-600">{{ $s->email ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-gray-700">{{ $s->role ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 font-mono text-gray-600">{{ $s->finger_id ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-center font-semibold text-gray-700">{{ $s->pt ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-gray-700">{{ $s->project ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-4 py-10 text-center text-xs text-gray-400">
                        Belum ada data staff.
                        @if($canManage) Klik "+ Input Staff" untuk menambahkan. @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Legend badge --}}
    <div class="mt-1.5 flex items-center gap-4 text-[10px] text-gray-500">
        <span class="flex items-center gap-1">
            <span class="px-1.5 py-0.5 rounded font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">✓ Terdaftar</span>
            Staff sudah punya akun AMS
        </span>
        <span class="flex items-center gap-1">
            <span class="px-1.5 py-0.5 rounded font-bold bg-amber-100 text-amber-700 border border-amber-200">⚠ Data Kurang</span>
            Email / Finger ID / HP belum lengkap
        </span>
    </div>

    {{-- Bottom action bar --}}
    <div class="mt-2 flex items-center justify-between border-t border-gray-200 pt-2">
        <div class="flex items-center gap-1.5">
            @if($canManage)
            <button wire:click="openAdd"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded flex items-center gap-1">
                + Input Staff
            </button>
            <button wire:click="{{ $selectedId ? 'openEdit('.$selectedId.')' : '' }}"
                    @if(!$selectedId) disabled @endif
                    class="px-3 py-1.5 text-xs font-semibold rounded flex items-center gap-1"
                    style="{{ $selectedId ? 'background:#f59e0b; color:#fff;' : 'background:#e5e7eb; color:#9ca3af; cursor:not-allowed;' }}">
                ✎ Edit Data
            </button>
            @endif
            <button wire:click="exportCsv"
                    class="px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white text-xs font-semibold rounded flex items-center gap-1">
                ↓ Export
            </button>
        </div>

        @if($staffList->hasPages())
        @php
            $cur  = $staffList->currentPage();
            $last = $staffList->lastPage();
            $nums = collect();
            for ($p = 1; $p <= $last; $p++) {
                if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
            }
            $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
            $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
            $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
        @endphp
        <div class="flex items-center gap-1">
            @if($staffList->onFirstPage())
                <span class="{{ $pDis }}">|‹</span>
                <span class="{{ $pDis }}">‹</span>
            @else
                <button wire:click="setPage(1)" class="{{ $pBtn }}">|‹</button>
                <button wire:click="previousPage" class="{{ $pBtn }}">‹</button>
            @endif
            @php $pg_prev = null; @endphp
            @foreach($nums as $pg)
                @if($pg_prev !== null && $pg - $pg_prev > 1)
                    <span class="{{ $pDis }}">…</span>
                @endif
                @if($pg == $cur)
                    <span class="{{ $pAct }}">{{ $pg }}</span>
                @else
                    <button wire:click="setPage({{ $pg }})" class="{{ $pBtn }}">{{ $pg }}</button>
                @endif
                @php $pg_prev = $pg; @endphp
            @endforeach
            @if($staffList->hasMorePages())
                <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
                <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
            @else
                <span class="{{ $pDis }}">›</span>
                <span class="{{ $pDis }}">›|</span>
            @endif
            <span class="ml-2 text-xs text-gray-400">· {{ $staffList->total() }} total</span>
        </div>
        @else
        <span class="text-xs text-gray-400">{{ $staffList->total() }} staff</span>
        @endif
    </div>

    {{-- ── Modal Form ── --}}
    @if($showPanel)
    <div class="fixed inset-0 z-50 flex items-center justify-center"
         style="background: rgba(0,0,0,0.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4 max-h-[92vh] overflow-y-auto">

            <div class="flex items-center justify-between px-5 py-3 rounded-t-lg"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide">
                    {{ $panelMode === 'add' ? 'Input Staff' : 'Edit Staff' }}
                </h3>
                <button wire:click="closePanel" class="text-white/70 hover:text-white text-lg leading-none">✕</button>
            </div>

            <form wire:submit="save" class="px-6 py-5 space-y-4">

                {{-- Info: field wajib untuk registrasi akun --}}
                <div class="px-3 py-2 bg-amber-50 border border-amber-200 rounded text-xs text-amber-800 flex gap-2">
                    <span class="shrink-0 mt-0.5">⚠</span>
                    <span>Agar karyawan bisa mendaftar akun, wajib isi: <strong>Nama, Email, Finger ID,</strong> dan <strong>No. Handphone</strong> dengan data yang benar.</span>
                </div>

                <div class="grid grid-cols-2 gap-4">

                    {{-- Nama Staff --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Staff <span class="text-red-500">*</span></label>
                        <input wire:model="fNamaStaff" type="text"
                               class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]"
                               placeholder="Nama lengkap staff">
                        @error('fNamaStaff') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    {{-- Department --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Departemen <span class="text-red-500">*</span></label>
                        <select wire:model="fDepartemen"
                                class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 pr-7 bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <option value="">-- Pilih --</option>
                            @foreach($deptOptions as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                        @error('fDepartemen') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                        <select wire:model="fStatus"
                                class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 pr-7 bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <option value="Aktif">Aktif</option>
                            <option value="Non-aktif">Non-aktif</option>
                        </select>
                    </div>

                    {{-- Role --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Role / Jabatan</label>
                        <select wire:model="fRole"
                                class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 pr-7 bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <option value="">-- Pilih --</option>
                            <option value="AM">AM</option>
                            <option value="Chief">Chief</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>

                    {{-- No. Handphone --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">No. Handphone</label>
                        <input wire:model="fNoHpOtp" type="text"
                               class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]"
                               placeholder="08xxxxxxxxxx">
                    </div>

                    {{-- Finger ID --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Finger ID</label>
                        <input wire:model="fFingerId" type="text"
                               class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    </div>

                    {{-- Email --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                        <input wire:model="fEmail" type="email"
                               class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]"
                               placeholder="email@domain.com">
                        @error('fEmail') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    {{-- Proyek (terkunci) --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Proyek</label>
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded text-sm">
                            <span class="text-gray-400 text-xs">🔒</span>
                            <span class="font-medium text-gray-700 text-sm">Madison Park (MAP)</span>
                            <span class="text-[10px] text-gray-400 ml-auto italic">terkunci otomatis</span>
                        </div>
                    </div>

                </div>

                {{-- Buttons --}}
                <div class="flex justify-center gap-4 pt-3 border-t border-gray-100">
                    <button type="submit"
                            class="px-8 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-semibold">
                        Simpan
                    </button>
                    <button type="button" wire:click="closePanel"
                            class="px-8 py-2 bg-gray-400 hover:bg-gray-500 text-white text-sm rounded font-semibold">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
