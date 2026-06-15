<?php
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\HandoverUnit;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.karyawan')] class extends Component {
    use WithPagination, WithFileUploads;

    public string $search     = '';
    public int    $perPage    = 40;
    public ?int   $selectedId = null;
    public bool   $showForm   = false;
    public bool   $isEditing  = false;

    // ── Form fields ─────────────────────────────────────────
    public string $fLotNo          = '';
    public string $fTipeUnit       = '';
    public string $fStrDate        = '';
    public string $fCmgDate        = '';
    public string $fPic            = '';
    public bool   $fPpjb           = false;
    public bool   $fBast           = false;
    public bool   $fHouseRule      = false;
    public string $fIplSfPaydate   = '';
    public string $fIplSfPeriod    = '';
    public int    $fUntilMonth     = 0;
    public int    $fNextMonth      = 0;
    public int    $fKeyCount       = 0;
    public int    $fAccessCardCount= 0;
    public string $fNoAccessCard    = '';
    public string $fNoIntercom      = '';
    public string $fNoTelpon        = '';
    public string $fDayaListrik     = '';
    public string $fStandAwalListrik = '';
    public string $fStandAwalAir    = '';

    // ── Photo uploads ────────────────────────────────────────
    public        $fPasFoto        = null;
    public        $fFotoKtp        = null;
    // Existing paths when editing (for preview without re-upload)
    public string $existingPasFoto = '';
    public string $existingFotoKtp = '';

    // ── KTP document number (auto-generated on photo upload) ─
    public string $fNoKtp = '';

    public function updatedSearch(): void { $this->resetPage(); }

    // Auto-generate no_ktp when KTP photo is first selected
    public function updatedFFotoKtp(): void
    {
        if ($this->fFotoKtp && $this->fNoKtp === '') {
            $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
            $romanMonth = $roman[now()->month - 1];
            $year = now()->year;

            $maxNum = 0;
            HandoverUnit::whereNotNull('no_ktp')->pluck('no_ktp')->each(function ($v) use (&$maxNum) {
                if (preg_match('/KTP\/(\d+)\//', $v, $m)) {
                    $maxNum = max($maxNum, (int) $m[1]);
                }
            });

            $this->fNoKtp = sprintf('KTP/%05d/%s/%d', $maxNum + 1, $romanMonth, $year);
        }
    }

    public function with(): array
    {
        $q = HandoverUnit::orderBy('lot_no');
        if ($this->search) {
            $q->where(function ($sub) {
                $sub->where('lot_no', 'like', "%{$this->search}%")
                    ->orWhere('pic',    'like', "%{$this->search}%");
            });
        }
        return [
            'units'    => $q->paginate($this->perPage),
            'selected' => $this->selectedId ? HandoverUnit::find($this->selectedId) : null,
        ];
    }

    public function selectRow(int $id): void
    {
        $this->selectedId = ($this->selectedId === $id) ? null : $id;
    }

    public function openAdd(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showForm  = true;
    }

    public function openEdit(): void
    {
        if (!$this->selectedId) return;
        $u = HandoverUnit::findOrFail($this->selectedId);
        $this->fLotNo           = $u->lot_no;
        $this->fTipeUnit        = $u->tipe_unit                         ?? '';
        $this->fStrDate         = $u->str_date?->format('Y-m-d')        ?? '';
        $this->fCmgDate         = $u->cmg_date?->format('Y-m-d')        ?? '';
        $this->fPic             = $u->pic                               ?? '';
        $this->fPpjb            = (bool) $u->ppjb;
        $this->fBast            = (bool) $u->bast;
        $this->fHouseRule       = (bool) $u->house_rule;
        $this->fIplSfPaydate    = $u->ipl_sf_paydate?->format('Y-m-d')  ?? '';
        $this->fIplSfPeriod     = $u->ipl_sf_period                     ?? '';
        $this->fUntilMonth      = (int) $u->until_month;
        $this->fNextMonth       = (int) $u->next_month;
        $this->fKeyCount        = (int) $u->key_count;
        $this->fAccessCardCount = (int) $u->access_card_count;
        $this->fNoAccessCard     = $u->no_access_card     ?? '';
        $this->fNoIntercom       = $u->no_intercom        ?? '';
        $this->fNoTelpon         = $u->no_telpon          ?? '';
        $this->fDayaListrik      = $u->daya_listrik       ?? '';
        $this->fStandAwalListrik = $u->stand_awal_listrik !== null ? (string) $u->stand_awal_listrik : '';
        $this->fStandAwalAir     = $u->stand_awal_air     !== null ? (string) $u->stand_awal_air     : '';
        $this->existingPasFoto   = $u->pas_foto           ?? '';
        $this->existingFotoKtp  = $u->foto_ktp                         ?? '';
        $this->fNoKtp           = $u->no_ktp                           ?? '';
        $this->fPasFoto         = null;
        $this->fFotoKtp         = null;
        $this->isEditing        = true;
        $this->showForm         = true;
    }

    public function save(): void
    {
        $uniqueRule = $this->isEditing
            ? 'unique:handover_units,lot_no,' . $this->selectedId
            : 'unique:handover_units,lot_no';

        $this->validate([
            'fLotNo'          => "required|string|max:30|{$uniqueRule}",
            'fStrDate'        => 'nullable|date',
            'fCmgDate'        => 'nullable|date',
            'fUntilMonth'     => 'integer|min:0',
            'fNextMonth'      => 'integer|min:0',
            'fKeyCount'       => 'integer|min:0',
            'fAccessCardCount'=> 'integer|min:0',
            'fPasFoto'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'fFotoKtp'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'fLotNo.required'    => 'Lot No wajib diisi.',
            'fLotNo.unique'      => 'Lot No sudah terdaftar.',
            'fPasFoto.image'     => 'Pas foto harus berupa gambar.',
            'fPasFoto.mimes'     => 'Format pas foto: JPG, PNG, atau WEBP.',
            'fPasFoto.max'       => 'Ukuran pas foto maks 5 MB.',
            'fFotoKtp.image'     => 'Foto KTP harus berupa gambar.',
            'fFotoKtp.mimes'     => 'Format foto KTP: JPG, PNG, atau WEBP.',
            'fFotoKtp.max'       => 'Ukuran foto KTP maks 5 MB.',
        ]);

        $data = [
            'lot_no'            => strtoupper(trim($this->fLotNo)),
            'tipe_unit'         => $this->fTipeUnit      ?: null,
            'str_date'          => $this->fStrDate      ?: null,
            'cmg_date'          => $this->fCmgDate      ?: null,
            'pic'               => $this->fPic          ?: null,
            'ppjb'              => $this->fPpjb,
            'bast'              => $this->fBast,
            'house_rule'        => $this->fHouseRule,
            'ipl_sf_paydate'    => $this->fIplSfPaydate ?: null,
            'ipl_sf_period'     => $this->fIplSfPeriod  ?: null,
            'until_month'       => $this->fUntilMonth,
            'next_month'        => $this->fNextMonth,
            'key_count'         => $this->fKeyCount,
            'access_card_count' => $this->fAccessCardCount,
            'no_access_card'     => $this->fNoAccessCard      ?: null,
            'no_intercom'        => $this->fNoIntercom        ?: null,
            'no_telpon'          => $this->fNoTelpon          ?: null,
            'daya_listrik'       => $this->fDayaListrik       ?: null,
            'stand_awal_listrik' => $this->fStandAwalListrik !== '' ? (float) $this->fStandAwalListrik : null,
            'stand_awal_air'     => $this->fStandAwalAir     !== '' ? (float) $this->fStandAwalAir     : null,
            'no_ktp'             => $this->fNoKtp             ?: null,
            'input_by'          => auth()->user()->name,
        ];

        // Handle pas_foto upload
        if ($this->fPasFoto) {
            if ($this->existingPasFoto) {
                Storage::disk('public')->delete($this->existingPasFoto);
            }
            $data['pas_foto'] = $this->fPasFoto->store('handover-docs', 'public');
        }

        // Handle foto_ktp upload
        if ($this->fFotoKtp) {
            if ($this->existingFotoKtp) {
                Storage::disk('public')->delete($this->existingFotoKtp);
            }
            $data['foto_ktp'] = $this->fFotoKtp->store('handover-docs', 'public');
        }

        if ($this->isEditing) {
            HandoverUnit::findOrFail($this->selectedId)->update($data);
            session()->flash('flash', "Unit {$data['lot_no']} berhasil diperbarui.");
        } else {
            $unit = HandoverUnit::create($data);
            $this->selectedId = $unit->id;
            session()->flash('flash', "Unit {$data['lot_no']} berhasil ditambahkan.");
        }

        $this->showForm = false;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->fLotNo          = '';
        $this->fTipeUnit       = '';
        $this->fStrDate        = '';
        $this->fCmgDate        = '';
        $this->fPic            = '';
        $this->fPpjb           = false;
        $this->fBast           = false;
        $this->fHouseRule      = false;
        $this->fIplSfPaydate   = '';
        $this->fIplSfPeriod    = '';
        $this->fUntilMonth     = 0;
        $this->fNextMonth      = 0;
        $this->fKeyCount       = 0;
        $this->fAccessCardCount= 0;
        $this->fNoAccessCard     = '';
        $this->fNoIntercom       = '';
        $this->fNoTelpon         = '';
        $this->fDayaListrik      = '';
        $this->fStandAwalListrik = '';
        $this->fStandAwalAir     = '';
        $this->fPasFoto          = null;
        $this->fFotoKtp        = null;
        $this->existingPasFoto = '';
        $this->existingFotoKtp = '';
        $this->fNoKtp          = '';
        $this->resetValidation();
    }
};
?>

<div class="bg-white" style="font-size:12px;">

    @if(session('flash'))
    <div class="px-4 py-1.5 bg-green-50 border-b border-green-200 text-green-800 text-xs">
        {{ session('flash') }}
    </div>
    @endif

    {{-- ── Table header label + action buttons ── --}}
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mx-0 mb-2">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            LIST HANDOVER UNIT
        </div>
    </div>
    <div class="px-4 py-2 border-b border-gray-300 bg-white">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button wire:click="openAdd"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded flex items-center gap-1">
                    + Add
                </button>
                <button wire:click="openEdit"
                        class="px-3 py-1.5 text-xs font-semibold rounded flex items-center gap-1"
                        style="{{ $selectedId ? 'background:#f59e0b; color:#fff;' : 'background:#e5e7eb; color:#9ca3af; cursor:not-allowed;' }}"
                        {{ !$selectedId ? 'disabled' : '' }}>
                    ✏ Edit
                </button>
            </div>
            <input wire:model.live.debounce.300ms="search"
                   type="text" placeholder="Cari lot no / PIC…"
                   class="border border-gray-300 rounded px-2 py-1 text-xs bg-white focus:outline-none focus:border-[#2a8c56] w-52">
        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="overflow-x-auto">
        <table style="width:100%; font-size:11px; border-collapse:collapse; table-layout:fixed;">
            <colgroup>
                <col style="width:28px;">
                <col style="width:6%;">
                <col style="width:6%;">
                <col style="width:7%;">
                <col style="width:7%;">
                <col style="width:7%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:3%;">
                <col style="width:7%;">
                <col style="width:6%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:3%;">
                <col style="width:4%;">
                <col style="width:6%;">
                <col style="width:6%;">
                <col style="width:6%;">
                <col style="width:5%;">
                <col style="width:6%;">
                <col style="width:6%;">
                <col style="width:5%;">
                <col style="width:5%;">
                <col style="width:6%;">
            </colgroup>
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold"></th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center font-semibold">LOT NO</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold" style="font-size:10px;">TIPE</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center font-semibold">STR DATE</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center font-semibold">CMG DATE</th>
                    <th class="border border-blue-200 px-2 py-1.5 text-center font-semibold">PIC</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold">PPJB</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold">BAST</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold">HR</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold" style="font-size:10px;">IPL SF PAYDATE</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold" style="font-size:10px;">IPLSF PERIOD</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold">UNTIL</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold">NEXT</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold">KEY</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold">A.CARD</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold" style="font-size:10px;">NO. A.CARD</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold" style="font-size:10px;">NO.INTERCOM</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold" style="font-size:10px;">NO.TELPON</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold" style="font-size:10px;">DAYA</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold" style="font-size:10px;">ST.LISTRIK</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold" style="font-size:10px;">ST.AIR</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold" style="font-size:10px;">PAS FOTO</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold">KTP</th>
                    <th class="border border-blue-200 px-1 py-1.5 text-center font-semibold" style="font-size:10px;">NO KTP</th>
                </tr>
                <tr class="bg-white">
                    <td class="border border-gray-200 p-0.5"></td>
                    @foreach(range(1, 23) as $_)
                    <td class="border border-gray-200 p-0.5"></td>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($units as $i => $u)
                @php $isSelected = $selectedId === $u->id; @endphp
                <tr wire:click="selectRow({{ $u->id }})"
                    class="cursor-pointer hover:bg-yellow-50 transition-colors"
                    style="{{ $isSelected ? 'background-color: #fff9c4;' : ($i % 2 === 0 ? 'background:#fff;' : 'background:#f9f9f9;') }}">
                    <td class="border border-gray-200 px-1 py-1 text-center text-gray-500" style="overflow:hidden;">
                        {{ ($units->currentPage() - 1) * $units->perPage() + $i + 1 }}
                    </td>
                    <td class="border border-gray-200 px-2 py-1 font-semibold" style="color:#3a9aaa;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $u->lot_no }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:10px;">{{ $u->tipe_unit ?: '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center" style="overflow:hidden;white-space:nowrap;font-size:10px;">{{ $u->str_date?->format('d/m/Y') }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center" style="overflow:hidden;white-space:nowrap;font-size:10px;">{{ $u->cmg_date?->format('d/m/Y') }}</td>
                    <td class="border border-gray-200 px-2 py-1" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $u->pic }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center font-semibold" style="color:{{ $u->ppjb ? '#16a34a' : '#9ca3af' }};">{{ $u->ppjb ? '✓' : '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center font-semibold" style="color:{{ $u->bast ? '#16a34a' : '#9ca3af' }};">{{ $u->bast ? '✓' : '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center font-semibold" style="color:{{ $u->house_rule ? '#16a34a' : '#9ca3af' }};">{{ $u->house_rule ? '✓' : '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center" style="overflow:hidden;white-space:nowrap;font-size:10px;">{{ $u->ipl_sf_paydate?->format('d/m/Y') }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center" style="overflow:hidden;white-space:nowrap;font-size:10px;">{{ $u->ipl_sf_period }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center">{{ $u->until_month ?: '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center">{{ $u->next_month ?: '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center">{{ $u->key_count ?: '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center">{{ $u->access_card_count ?: '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:10px;">{{ $u->no_access_card ?: '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:10px;">{{ $u->no_intercom ?: '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:10px;">{{ $u->no_telpon ?: '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center" style="overflow:hidden;white-space:nowrap;font-size:10px;">{{ $u->daya_listrik ?: '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center" style="overflow:hidden;white-space:nowrap;font-size:10px;">{{ $u->stand_awal_listrik !== null ? number_format($u->stand_awal_listrik, 2) : '–' }}</td>
                    <td class="border border-gray-200 px-1 py-1 text-center" style="overflow:hidden;white-space:nowrap;font-size:10px;">{{ $u->stand_awal_air !== null ? number_format($u->stand_awal_air, 2) : '–' }}</td>
                    <td class="border border-gray-200 px-2 py-1 text-center">
                        @if($u->pas_foto)
                        <a href="{{ asset('storage/' . $u->pas_foto) }}" target="_blank"
                           class="inline-block" title="Lihat Pas Foto">
                            <img src="{{ asset('storage/' . $u->pas_foto) }}"
                                 class="w-8 h-8 object-cover rounded border border-gray-300 hover:opacity-80 transition-opacity">
                        </a>
                        @else
                        <span class="text-gray-300 text-[10px]">—</span>
                        @endif
                    </td>
                    <td class="border border-gray-200 px-2 py-1 text-center">
                        @if($u->foto_ktp)
                        <a href="{{ asset('storage/' . $u->foto_ktp) }}" target="_blank"
                           class="inline-block" title="Lihat KTP">
                            <img src="{{ asset('storage/' . $u->foto_ktp) }}"
                                 class="w-8 h-8 object-cover rounded border border-gray-300 hover:opacity-80 transition-opacity">
                        </a>
                        @else
                        <span class="text-gray-300 text-[10px]">—</span>
                        @endif
                    </td>
                    <td class="border border-gray-200 px-2 py-1 whitespace-nowrap font-mono"
                        style="color: {{ $u->no_ktp ? '#1a6b9a' : '#d1d5db' }};">
                        {{ $u->no_ktp ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="24" class="border border-gray-200 px-3 py-8 text-center text-gray-400">
                        Belum ada data unit. Klik &quot;+ Add&quot; untuk menambah unit baru.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Bottom Bar (pagination only) ── --}}
    <div class="flex items-center px-3 py-1.5 border-t border-gray-300 gap-3"
         style="background-color: #f0f0f0; font-size:11px;">

        {{-- Pagination --}}
        @php
            $cur  = $units->currentPage();
            $last = $units->lastPage();
            $nums = collect();
            for ($p = 1; $p <= $last; $p++) {
                if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
            }
            $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
            $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
            $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
        @endphp
        <div class="flex items-center gap-1 ml-4">
            @if($units->onFirstPage())
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
            @if($units->hasMorePages())
                <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
                <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
            @else
                <span class="{{ $pDis }}">›</span>
                <span class="{{ $pDis }}">›|</span>
            @endif
            <select wire:model.live="perPage" class="border border-gray-400 text-[11px] px-1 py-0.5 bg-white ml-2">
                <option value="20">20</option>
                <option value="40" selected>40</option>
                <option value="100">100</option>
            </select>
        </div>
        <span class="ml-auto text-gray-500">View {{ $units->firstItem() ?? 0 }}–{{ $units->lastItem() ?? 0 }} of {{ $units->total() }}</span>
    </div>

    {{-- ══════════════════════════════════════════
         EDIT / ADD MODAL OVERLAY
    ══════════════════════════════════════════ --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center" style="background: rgba(0,0,0,0.45);">
        <div class="bg-white border border-gray-400 shadow-2xl overflow-y-auto"
             style="width: 560px; max-width: 95vw; max-height: 90vh;">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-4 py-2 sticky top-0 z-10"
                 style="background: linear-gradient(to bottom, #c5dde2, #a8c8d0); border-bottom: 1px solid #88aab0;">
                <span class="font-bold text-gray-700 tracking-wide" style="font-size:12px;">
                    {{ $isEditing ? 'EDIT MODE' : 'ADD MODE' }}
                </span>
                <button wire:click="cancelForm"
                        class="text-gray-600 hover:text-gray-900 text-sm font-bold leading-none border border-gray-400 bg-white hover:bg-gray-100 px-1.5 py-0.5 rounded-sm">
                    ✕
                </button>
            </div>

            {{-- Form Body --}}
            <div class="px-6 py-5 space-y-4" style="font-size:12px;">

                {{-- Lot No --}}
                <div class="flex items-center gap-3">
                    <label class="w-32 font-medium text-gray-700 shrink-0">Lot No</label>
                    <input wire:model="fLotNo" type="text"
                           {{ $isEditing ? 'readonly' : '' }}
                           class="border border-gray-400 rounded px-2 py-1 w-44 text-sm
                                  {{ $isEditing ? 'bg-gray-100 cursor-not-allowed' : 'bg-white focus:outline-none focus:border-[#2a8c56]' }}">
                    @error('fLotNo')<span class="text-red-600 text-[10px] ml-1">{{ $message }}</span>@enderror
                </div>

                {{-- Tipe Unit --}}
                <div class="flex items-center gap-3">
                    <label class="w-32 font-medium text-gray-700 shrink-0">Tipe Unit</label>
                    <select wire:model="fTipeUnit"
                            class="border border-gray-400 rounded px-2 py-1 w-44 text-sm bg-white focus:outline-none focus:border-[#2a8c56]">
                        <option value="">— Pilih Tipe —</option>
                        <option value="Kios">Kios</option>
                        <option value="Studio">Studio</option>
                        <option value="1 Bedroom">1 Bedroom</option>
                        <option value="2 Bedroom">2 Bedroom</option>
                    </select>
                </div>

                {{-- STR Date --}}
                <div class="flex items-center gap-3">
                    <label class="w-32 font-medium text-gray-700 shrink-0">STR Date</label>
                    <input wire:model="fStrDate" type="date"
                           class="border border-gray-400 rounded px-2 py-1 w-44 text-sm bg-white focus:outline-none focus:border-[#2a8c56]">
                </div>

                {{-- CMG Date --}}
                <div class="flex items-center gap-3">
                    <label class="w-32 font-medium text-gray-700 shrink-0">CMG Date</label>
                    <input wire:model="fCmgDate" type="date"
                           class="border border-gray-400 rounded px-2 py-1 w-44 text-sm bg-white focus:outline-none focus:border-[#2a8c56]">
                </div>

                {{-- PIC --}}
                <div class="flex items-center gap-3">
                    <label class="w-32 font-medium text-gray-700 shrink-0">PIC</label>
                    <input wire:model="fPic" type="text"
                           class="border border-gray-400 rounded px-2 py-1 w-44 text-sm bg-white focus:outline-none focus:border-[#2a8c56]">
                </div>

                {{-- Completeness --}}
                <div class="flex items-start gap-3">
                    <label class="w-32 font-medium text-gray-700 shrink-0 pt-0.5">Completeness</label>
                    <div class="flex items-center gap-4 flex-wrap">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input wire:model="fPpjb" type="checkbox" class="w-3.5 h-3.5 accent-[#1a5c2e]">
                            <span>PPJB</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input wire:model="fBast" type="checkbox" class="w-3.5 h-3.5 accent-[#1a5c2e]">
                            <span>BAST</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input wire:model="fHouseRule" type="checkbox" class="w-3.5 h-3.5 accent-[#1a5c2e]">
                            <span>House Rule</span>
                        </label>
                    </div>
                </div>

                {{-- IPL+SF Pay Date --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <label class="w-32 font-medium text-gray-700 shrink-0">IPL+SF Pay Date</label>
                    <input wire:model="fIplSfPaydate" type="date"
                           class="border border-gray-400 rounded px-2 py-1 text-sm bg-white focus:outline-none focus:border-[#2a8c56] w-36">
                    <span class="text-gray-600 shrink-0">for Periode</span>
                    <input wire:model="fIplSfPeriod" type="text" placeholder="e.g. Jan 2026"
                           class="border border-gray-400 rounded px-2 py-1 text-sm bg-white focus:outline-none focus:border-[#2a8c56] w-24">
                    <span class="text-gray-600 shrink-0">until</span>
                    <input wire:model="fUntilMonth" type="number" min="0"
                           class="border border-gray-400 rounded px-2 py-1 text-sm bg-white focus:outline-none focus:border-[#2a8c56] w-14 text-center">
                    <span class="text-gray-600 shrink-0">month &nbsp; Next</span>
                    <input wire:model="fNextMonth" type="number" min="0"
                           class="border border-gray-400 rounded px-2 py-1 text-sm bg-white focus:outline-none focus:border-[#2a8c56] w-14 text-center">
                    <span class="text-gray-600 shrink-0">month</span>
                </div>

                {{-- Amount of --}}
                <div class="flex items-center gap-4">
                    <label class="w-32 font-medium text-gray-700 shrink-0">Amount of</label>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-600">Key</span>
                        <input wire:model="fKeyCount" type="number" min="0"
                               class="border border-gray-400 rounded px-2 py-1 text-sm bg-white focus:outline-none focus:border-[#2a8c56] w-16 text-center">
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-600">Access Card</span>
                        <input wire:model="fAccessCardCount" type="number" min="0"
                               class="border border-gray-400 rounded px-2 py-1 text-sm bg-white focus:outline-none focus:border-[#2a8c56] w-16 text-center">
                    </div>
                </div>

                {{-- Number of --}}
                <div class="flex items-start gap-4">
                    <label class="w-32 font-medium text-gray-700 shrink-0 pt-1">Number of</label>
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-600 whitespace-nowrap">Access Card</span>
                            <input wire:model="fNoAccessCard" type="text"
                                   class="border border-gray-400 rounded px-2 py-1 text-sm bg-white focus:outline-none focus:border-[#2a8c56] w-32">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-600">Intercom</span>
                            <input wire:model="fNoIntercom" type="text"
                                   class="border border-gray-400 rounded px-2 py-1 text-sm bg-white focus:outline-none focus:border-[#2a8c56] w-28">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-600 whitespace-nowrap">No. Telpon</span>
                            <input wire:model="fNoTelpon" type="tel"
                                   class="border border-gray-400 rounded px-2 py-1 text-sm bg-white focus:outline-none focus:border-[#2a8c56] w-28">
                        </div>
                    </div>
                </div>

                {{-- Stand Awal Meter & Daya Listrik --}}
                <div class="flex items-start gap-3">
                    <label class="w-32 font-medium text-gray-700 shrink-0 pt-1">Stand Awal</label>
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-600 whitespace-nowrap">Listrik (kWh)</span>
                            <input wire:model="fStandAwalListrik" type="number" step="0.01" min="0"
                                   class="border border-gray-400 rounded px-2 py-1 text-sm bg-white focus:outline-none focus:border-[#2a8c56] w-28">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-600 whitespace-nowrap">Air (m³)</span>
                            <input wire:model="fStandAwalAir" type="number" step="0.01" min="0"
                                   class="border border-gray-400 rounded px-2 py-1 text-sm bg-white focus:outline-none focus:border-[#2a8c56] w-28">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <label class="w-32 font-medium text-gray-700 shrink-0">Daya Listrik</label>
                    <select wire:model="fDayaListrik"
                            class="border border-gray-400 rounded px-2 py-1 w-44 text-sm bg-white focus:outline-none focus:border-[#2a8c56]">
                        <option value="">— Pilih Daya —</option>
                        <option value="450 VA">450 VA</option>
                        <option value="900 VA">900 VA</option>
                        <option value="1300 VA">1300 VA</option>
                        <option value="2200 VA">2200 VA</option>
                        <option value="3500 VA">3500 VA</option>
                        <option value="4400 VA">4400 VA</option>
                        <option value="5500 VA">5500 VA</option>
                        <option value="6600 VA">6600 VA</option>
                        <option value="10600 VA">10600 VA</option>
                        <option value="13200 VA">13200 VA</option>
                    </select>
                </div>

                {{-- ── Photo Section ────────────────────── --}}
                <div class="border-t border-gray-200 pt-4">
                    <p class="font-semibold text-gray-600 mb-3 tracking-wide" style="font-size:11px;">DOKUMEN FOTO</p>

                    <div class="grid grid-cols-2 gap-4">

                        {{-- Pas Foto --}}
                        <div class="border border-gray-300 rounded p-3 bg-gray-50">
                            <label class="block font-medium text-gray-700 mb-2">
                                Pas Foto
                                <span class="text-gray-400 font-normal text-[10px] block">JPG/PNG/WEBP, maks 5 MB</span>
                            </label>

                            {{-- Existing preview (edit mode) --}}
                            @if($isEditing && $existingPasFoto && !$fPasFoto)
                            <div class="mb-2 relative group w-fit">
                                <img src="{{ asset('storage/' . $existingPasFoto) }}"
                                     class="h-24 w-24 object-cover rounded border border-gray-300">
                                <span class="absolute inset-0 flex items-center justify-center text-[10px] text-white bg-black/40 rounded opacity-0 group-hover:opacity-100 transition-opacity">foto saat ini</span>
                            </div>
                            @endif

                            {{-- New upload preview --}}
                            @if($fPasFoto)
                            <div class="mb-2">
                                <img src="{{ $fPasFoto->temporaryUrl() }}"
                                     class="h-24 w-24 object-cover rounded border border-[#2a8c56]">
                                <p class="text-[10px] text-green-700 mt-0.5">✓ Foto baru dipilih</p>
                            </div>
                            @endif

                            <input wire:model="fPasFoto" type="file"
                                   accept="image/jpeg,image/png,image/webp"
                                   class="w-full text-[11px] text-gray-500
                                          file:mr-2 file:py-0.5 file:px-2 file:border file:border-gray-400
                                          file:bg-white file:text-gray-600 file:text-[11px] file:cursor-pointer
                                          hover:file:bg-gray-100">
                            @error('fPasFoto')<p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Foto KTP --}}
                        <div class="border border-gray-300 rounded p-3 bg-gray-50">
                            <label class="block font-medium text-gray-700 mb-2">
                                Foto NIK / KTP
                                <span class="text-gray-400 font-normal text-[10px] block">JPG/PNG/WEBP, maks 5 MB</span>
                            </label>

                            {{-- Existing preview (edit mode) --}}
                            @if($isEditing && $existingFotoKtp && !$fFotoKtp)
                            <div class="mb-2 relative group w-fit">
                                <img src="{{ asset('storage/' . $existingFotoKtp) }}"
                                     class="h-24 w-24 object-cover rounded border border-gray-300">
                                <span class="absolute inset-0 flex items-center justify-center text-[10px] text-white bg-black/40 rounded opacity-0 group-hover:opacity-100 transition-opacity">foto saat ini</span>
                            </div>
                            @endif

                            {{-- New upload preview --}}
                            @if($fFotoKtp)
                            <div class="mb-2">
                                <img src="{{ $fFotoKtp->temporaryUrl() }}"
                                     class="h-24 w-24 object-cover rounded border border-[#2a8c56]">
                                <p class="text-[10px] text-green-700 mt-0.5">✓ Foto baru dipilih</p>
                            </div>
                            @endif

                            <input wire:model="fFotoKtp" type="file"
                                   accept="image/jpeg,image/png,image/webp"
                                   class="w-full text-[11px] text-gray-500
                                          file:mr-2 file:py-0.5 file:px-2 file:border file:border-gray-400
                                          file:bg-white file:text-gray-600 file:text-[11px] file:cursor-pointer
                                          hover:file:bg-gray-100">
                            @error('fFotoKtp')<p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    {{-- No KTP — auto-generated when KTP photo is selected --}}
                    <div class="mt-3 flex items-center gap-3">
                        <label class="font-medium text-gray-700 shrink-0 w-24">No. KTP Dok.</label>
                        <div class="flex items-center gap-2 flex-1">
                            <input wire:model="fNoKtp" type="text" maxlength="40"
                                   placeholder="Auto-generate saat foto KTP dipilih"
                                   class="border rounded px-2 py-1 text-sm flex-1 font-mono focus:outline-none focus:border-[#2a8c56]
                                          {{ $fNoKtp ? 'border-[#2a8c56] bg-green-50 text-[#1a5c2e]' : 'border-gray-400 bg-white text-gray-700' }}">
                            @if($fNoKtp)
                            <span class="text-green-600 text-[10px] font-semibold whitespace-nowrap">✓ Terisi otomatis</span>
                            @endif
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1" style="padding-left: calc(6rem + 0.75rem);">
                        Nomor dokumen KTP otomatis dibuat saat foto KTP dipilih. Bisa diedit manual.
                    </p>

                </div>

                {{-- Save / Cancel --}}
                <div class="flex items-center justify-center gap-4 pt-2 border-t border-gray-200">
                    <button wire:click="save" wire:loading.attr="disabled"
                            class="px-8 py-1.5 bg-white border border-gray-400 hover:bg-gray-100 text-gray-700 rounded-sm text-sm font-medium transition-colors disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Save</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                    <button wire:click="cancelForm"
                            class="px-6 py-1.5 bg-white border border-gray-400 hover:bg-gray-100 text-gray-700 rounded-sm text-sm font-medium transition-colors">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    </div>
    @endif

</div>
