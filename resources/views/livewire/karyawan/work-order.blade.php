<?php

use App\Models\ItemMaster;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\TenantRequest;
use App\Models\WorkOrder;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

new #[Layout('layouts.karyawan')] class extends Component
{
    use WithPagination, WithFileUploads;

    // ── Filters ──────────────────────────────────────────────
    public string $fExIn         = '';
    public string $fNoComplain   = '';
    public string $fNoWo         = '';
    public string $fJenisWo      = '';
    public string $fSubJenisWo   = '';
    public string $fEstClos      = '';
    public string $fLotNo        = '';
    public string $fName         = '';
    public string $fDescs        = '';
    public string $fStatusComp   = '';
    public string $fDurasiiBln   = '';
    public string $fRequestVia   = '';
    public string $fAssignDep    = '';
    public string $fAssignStaff  = '';

    public int $perPage    = 10;
    public int $selectedId = 0;

    // ── Panel control ────────────────────────────────────────
    // null | 'add' | 'balas' | 'edit' | 'close' | 'item-service'
    public ?string $panelMode = null;

    // ── Add WO form ──────────────────────────────────────────
    public string $formExIn       = 'IN';
    public string $formLotNo      = '';
    public string $formDescs      = '';
    public string $formRequestBy  = '';
    public string $formRequestVia = '';
    public string $formJenisWo    = '';
    public string $formEstClose   = '';
    public array  $tenantInfo     = [];

    // ── Balas Request form ───────────────────────────────────
    public string $balasText  = '';
    public        $balasFoto  = null;

    // ── Edit form ────────────────────────────────────────────
    public string $editNoComplain = '';
    public string $editExIn       = 'IN';
    public string $editWoDate     = '';
    public string $editEstDate    = '';
    public string $editLotNo      = '';
    public string $editDescs      = '';
    public string $editRequestBy  = '';
    public string $editRequestVia = '';
    public string $editJenisWo    = '';
    public string $editSubJenisWo = '';

    // ── Close WO form ────────────────────────────────────────
    public string $closeWorkStartedDate = '';
    public string $closeWorkStartedTime = '';
    public string $closeClosingDate     = '';
    public string $closeClosingTime     = '';
    public string $closeActionBy        = '';
    public string $closeActionTaken     = '';
    public        $closeFoto            = null;

    // ── Item & Service form ───────────────────────────────────
    public int    $isItemId          = 0;
    public int    $isQty             = 1;
    public string $isNote            = '';
    public array  $currentItemService = [];

    // ── Assign Staff form ─────────────────────────────────────
    public string $assignStaff    = '';
    public string $assignTglJanji = '';
    public string $assignNote     = '';

    // ── Complain Modal ────────────────────────────────────────
    public bool  $showComplainModal = false;
    public array $complainData      = [];

    // ── Update Complain Modal ─────────────────────────────────
    public bool   $showUpdateComplain    = false;
    public int    $updateComplainWoId    = 0;
    public string $updateComplainStatus  = '';
    public string $updateComplainKet     = '';
    public array  $updateComplainInfo    = [];

    // ─────────────────────────────────────────────────────────

    public function updated($prop): void
    {
        $isFilter = str_starts_with($prop, 'f') && ! str_starts_with($prop, 'form');
        if ($isFilter || $prop === 'perPage') {
            $this->resetPage();
        }
    }

    public function selectRow(int $id): void
    {
        $this->selectedId = ($this->selectedId === $id) ? 0 : $id;
        $this->panelMode  = null;
        $this->resetValidation();
    }

    public function openComplainModal(string $noComplain): void
    {
        $req = TenantRequest::where('no_request', $noComplain)->first();
        if (! $req) return;

        $this->complainData = [
            'no_request'       => $req->no_request,
            'tanggal'          => $req->tanggal?->format('M d Y g:i:sA'),
            'tgl_verifikasi'   => $req->tgl_verifikasi?->format('M d Y'),
            'tgl_dalam_proses' => $req->tgl_dalam_proses?->format('M d Y'),
            'tgl_selesai'      => $req->tgl_selesai?->format('M d Y'),
            'descs'            => $req->descs,
            'done_by'          => $req->done_by,
            'input_by'         => $req->input_by,
            'status'           => $req->status,
            'foto'             => $req->foto,
            'kategori'         => $req->kategori,
            'sub_kategori'     => $req->sub_kategori,
        ];
        $this->showComplainModal = true;
    }

    public function openUpdateComplain(int $woId): void
    {
        $wo = WorkOrder::find($woId);
        if (! $wo) return;

        $this->updateComplainWoId   = $woId;
        $this->updateComplainStatus = $wo->status_comp ?? 'Pesan Diterima';
        $this->updateComplainKet    = '';
        $this->updateComplainInfo   = [
            'no_complain' => $wo->no_complain ?? '-',
            'lot_no'      => $wo->lot_no ?? '-',
            'descs'       => $wo->descs ?? '',
            'request_by'  => $wo->request_by ?? '-',
            'request_via' => $wo->request_via ?? '-',
        ];
        $this->showUpdateComplain = true;
    }

    public function saveUpdateComplain(): void
    {
        $this->validate(
            ['updateComplainStatus' => 'required'],
            ['updateComplainStatus.required' => 'Status wajib dipilih.']
        );

        $wo = WorkOrder::findOrFail($this->updateComplainWoId);
        $wo->update(['status_comp' => $this->updateComplainStatus]);

        if ($wo->no_complain) {
            $updateData = ['status' => $this->updateComplainStatus];
            if ($this->updateComplainStatus === 'Dalam Pengecekan') {
                $updateData['tgl_verifikasi'] = now()->toDateString();
            } elseif ($this->updateComplainStatus === 'Dalam Proses') {
                $updateData['tgl_dalam_proses'] = now()->toDateString();
            } elseif ($this->updateComplainStatus === 'Selesai') {
                $updateData['tgl_selesai'] = now()->toDateString();
                $updateData['done_by']     = auth()->user()?->name;
                $updateData['is_selesai']  = true;
            }
            if ($this->updateComplainKet) {
                $updateData['desc_status'] = $this->updateComplainKet;
            }

            TenantRequest::where('no_request', $wo->no_complain)
                ->whereNotIn('status', ['Selesai', 'Tidak Dapat Diaplikasi'])
                ->update($updateData);
        }

        $this->showUpdateComplain = false;
        $this->resetValidation();
    }

    // ── Panel openers ─────────────────────────────────────────

    public function openAddForm(): void
    {
        $this->panelMode      = 'add';
        $this->formExIn       = 'IN';
        $this->formEstClose   = now()->addDays(7)->format('Y-m-d');
        $this->formLotNo      = '';
        $this->formDescs      = '';
        $this->formRequestBy  = '';
        $this->formRequestVia = '';
        $this->formJenisWo    = '';
        $this->tenantInfo     = [];
        $this->resetValidation();
    }

    public function openBalas(): void
    {
        if (! $this->selectedId) return;
        $this->panelMode = 'balas';
        $this->balasText = '';
        $this->resetValidation();
    }

    public function openEdit(): void
    {
        if (! $this->selectedId) return;
        $wo = WorkOrder::find($this->selectedId);
        if (! $wo) return;
        $this->panelMode      = 'edit';
        $this->editNoComplain = $wo->no_complain ?? '';
        $this->editExIn       = $wo->ex_in;
        $this->editWoDate     = $wo->tanggal->format('Y-m-d H:i:s');
        $this->editEstDate    = $wo->estimated_close?->format('Y-m-d') ?? '';
        $this->editLotNo      = $wo->lot_no ?? '';
        $this->editDescs      = $wo->descs ?? '';
        $this->editRequestBy  = $wo->request_by ?? '';
        $this->editRequestVia = $wo->request_via ?? '';
        $this->editJenisWo    = $wo->jenis_wo ?? '';
        $this->editSubJenisWo = $wo->sub_jenis_wo ?? '';
        $this->resetValidation();
    }

    public function openClose(): void
    {
        if (! $this->selectedId) return;
        $wo = WorkOrder::find($this->selectedId);
        if (! $wo) return;
        $this->panelMode            = 'close';
        $this->closeWorkStartedDate = $wo->tanggal->format('Y-m-d');
        $this->closeWorkStartedTime = $wo->tanggal->format('H:i:s');
        $this->closeClosingDate     = now()->format('Y-m-d');
        $this->closeClosingTime     = now()->format('H:i:s');
        $this->closeActionBy        = auth()->user()?->name ?? auth()->user()?->email ?? '';
        $this->closeActionTaken     = '';
        $this->resetValidation();
    }

    public function openItemService(): void
    {
        if (! $this->selectedId) return;
        $wo = WorkOrder::find($this->selectedId);
        if (! $wo) return;
        $this->panelMode          = 'item-service';
        $this->currentItemService = $wo->item_service ?? [];
        $this->isItemId           = 0;
        $this->isQty              = 1;
        $this->isNote             = '';
        $this->resetValidation();
    }

    public function openTimeline(int $id): void
    {
        $this->selectedId = $id;
        $this->panelMode  = 'timeline';
        $this->resetValidation();
    }

    public function openAssign(int $id): void
    {
        $wo = WorkOrder::find($id);
        if (! $wo) return;
        $this->selectedId     = $id;
        $this->panelMode      = 'assign';
        $this->assignStaff    = $wo->assign_staff ?? '';
        $this->assignTglJanji = now()->format('Y-m-d');
        $this->assignNote     = '';
        $this->resetValidation();
    }

    public function saveAssign(): void
    {
        $this->validate(
            ['assignStaff' => 'required|string'],
            ['assignStaff.required' => 'Staff wajib dipilih.']
        );

        $wo = WorkOrder::findOrFail($this->selectedId);
        $wo->update([
            'assign_staff' => $this->assignStaff,
            'status_comp'  => 'Dalam Pengecekan',
        ]);

        $this->notifyTenant($wo->fresh(), "Teknisi {$this->assignStaff} akan datang untuk melakukan pengecekan.", 'Dalam Pengecekan');

        $this->closePanel();
    }

    public function closePanel(): void
    {
        $this->panelMode = null;
        $this->resetValidation();
    }

    // ── Auto-update estimated close saat EX/IN berubah ───────

    public function updatedFormExIn(): void
    {
        if ($this->panelMode === 'add') {
            $this->formEstClose = now()->addDays($this->formExIn === 'EX' ? 5 : 7)->format('Y-m-d');
        }
    }

    // ── Lot No lookup (Add form) ──────────────────────────────

    public function updatedFormLotNo(): void
    {
        $lot = strtoupper(trim($this->formLotNo));
        if ($lot === '') { $this->tenantInfo = []; return; }

        $tenant = Tenant::with('user')
            ->whereRaw('UPPER(unit_number) = ?', [$lot])
            ->first();

        if ($tenant) {
            $user     = $tenant->user;
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if (! $fullName) $fullName = $user->name ?? '-';
            $this->tenantInfo = ['found' => true, 'name' => strtoupper($fullName)];
        } else {
            $this->tenantInfo = ['found' => false];
        }
    }

    // ── Savers ────────────────────────────────────────────────

    public function saveWo(): void
    {
        $this->validate([
            'formJenisWo'    => 'required',
            'formRequestBy'  => 'required|string|max:100',
            'formRequestVia' => 'required',
            'formDescs'      => 'required|string',
        ], [
            'formJenisWo.required'    => 'Category wajib dipilih.',
            'formRequestBy.required'  => 'Request By wajib diisi.',
            'formRequestVia.required' => 'Request Via wajib dipilih.',
            'formDescs.required'      => 'Description wajib diisi.',
        ]);

        $prefix = $this->formExIn === 'IN' ? 'IN' : 'EX';
        $noWo   = WorkOrder::generateNoWo($prefix);

        if (! empty($this->tenantInfo['found']) && isset($this->tenantInfo['name'])) {
            $name = $this->tenantInfo['name'];
        } elseif ($this->formLotNo) {
            $name = strtoupper($this->formLotNo);
        } else {
            $name = 'Building Management';
        }

        WorkOrder::create([
            'ex_in'           => $this->formExIn,
            'no_wo'           => $noWo,
            'jenis_wo'        => $this->formJenisWo,
            'tanggal'         => now(),
            'estimated_close' => $this->formEstClose ?: now()->addDays($this->formExIn === 'EX' ? 5 : 7)->format('Y-m-d'),
            'lot_no'          => $this->formLotNo ? strtoupper($this->formLotNo) : null,
            'name'            => $name,
            'descs'           => $this->formDescs,
            'request_by'      => $this->formRequestBy,
            'request_via'     => $this->formRequestVia,
            'assign_dep'      => 'ENG',
            'input_by'        => auth()->user()?->name ?? auth()->user()?->email,
            'durasi_bln'      => 'kurang1bln',
        ]);

        $this->closePanel();
        $this->resetPage();
    }

    public function saveBalas(): void
    {
        $this->validate(
            ['balasText' => 'required|string'],
            ['balasText.required' => 'Balas Request wajib diisi.']
        );

        $wo = WorkOrder::findOrFail($this->selectedId);
        $updateData = [
            'balas_request' => $this->balasText,
            'balas_by'      => auth()->user()?->name ?? auth()->user()?->email,
            'balas_at'      => now(),
            'status_comp'   => 'Dalam Proses',
        ];
        if ($this->balasFoto) {
            $updateData['foto_pengecekan'] = $this->balasFoto->store('wo-pengecekan', 'public');
        }
        $wo->update($updateData);

        if ($wo->no_complain) {
            TenantRequest::where('no_request', $wo->no_complain)
                ->whereNotIn('status', ['Selesai', 'Tidak Dapat Diaplikasi'])
                ->update([
                    'status'          => 'Dalam Pengecekan',
                    'tgl_verifikasi'  => now()->toDateString(),
                ]);
        }

        $this->notifyTenant($wo->fresh(), 'Keluhan Anda sedang dalam proses penanganan. Kami akan segera menghubungi Anda.', 'Dalam Proses');

        $this->closePanel();
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editDescs'      => 'required|string',
            'editRequestBy'  => 'required|string|max:100',
            'editRequestVia' => 'required',
            'editJenisWo'    => 'required',
        ], [
            'editDescs.required'      => 'Description wajib diisi.',
            'editRequestBy.required'  => 'Request By wajib diisi.',
            'editRequestVia.required' => 'Request Via wajib dipilih.',
            'editJenisWo.required'    => 'Jenis WO wajib dipilih.',
        ]);

        WorkOrder::findOrFail($this->selectedId)->update([
            'no_complain'    => $this->editNoComplain ?: null,
            'ex_in'          => $this->editExIn,
            'estimated_close'=> $this->editEstDate ?: null,
            'lot_no'         => $this->editLotNo ?: null,
            'descs'          => $this->editDescs,
            'request_by'     => $this->editRequestBy,
            'request_via'    => $this->editRequestVia,
            'jenis_wo'       => $this->editJenisWo,
            'sub_jenis_wo'   => $this->editSubJenisWo ?: null,
        ]);

        if ($this->editNoComplain) {
            TenantRequest::where('no_request', $this->editNoComplain)
                ->whereNull('tgl_verifikasi')
                ->whereNotIn('status', ['Selesai', 'Tidak Dapat Diaplikasi'])
                ->update([
                    'status'         => 'Dalam Pengecekan',
                    'tgl_verifikasi' => now()->toDateString(),
                ]);
        }

        $this->closePanel();
    }

    public function saveClose(): void
    {
        $this->validate(
            ['closeActionBy' => 'required|string|max:100'],
            ['closeActionBy.required' => 'Action By wajib diisi.']
        );

        $workStarted = $this->closeWorkStartedDate
            ? ($this->closeWorkStartedDate . ' ' . ($this->closeWorkStartedTime ?: '00:00:00'))
            : null;
        $workClosed = $this->closeClosingDate
            ? ($this->closeClosingDate . ' ' . ($this->closeClosingTime ?: '00:00:00'))
            : null;

        $wo = WorkOrder::findOrFail($this->selectedId);
        $closeData = [
            'status_comp'  => 'Work Order Close',
            'work_started' => $workStarted,
            'work_closed'  => $workClosed,
            'action_by'    => $this->closeActionBy,
            'action_taken' => $this->closeActionTaken,
        ];
        if ($this->closeFoto) {
            $closeData['foto_close'] = $this->closeFoto->store('wo-close', 'public');
        }
        $wo->update($closeData);

        if ($wo->no_complain) {
            TenantRequest::where('no_request', $wo->no_complain)
                ->whereNotIn('status', ['Selesai', 'Tidak Dapat Diaplikasi'])
                ->update([
                    'status'     => 'Selesai',
                    'is_selesai' => true,
                    'tgl_selesai' => now()->toDateString(),
                ]);
        }

        $this->notifyTenant($wo->fresh(), 'Work Order Anda telah selesai ditangani. Terima kasih atas kesabaran Anda.', 'Work Order Close');

        $this->closePanel();
    }

    public function addItem(): void
    {
        if (! $this->isItemId || $this->isQty < 1) return;
        $item = ItemMaster::find($this->isItemId);
        if (! $item) return;

        $wo    = WorkOrder::findOrFail($this->selectedId);
        $items = $wo->item_service ?? [];
        $items[] = [
            'nama'   => $item->nama,
            'harga'  => $item->harga,
            'qty'    => $this->isQty,
            'satuan' => $item->satuan,
            'note'   => $this->isNote,
        ];
        $wo->update(['item_service' => $items]);
        $this->currentItemService = $items;
        $this->isItemId = 0;
        $this->isQty    = 1;
        $this->isNote   = '';
    }

    public function removeItem(int $index): void
    {
        $wo    = WorkOrder::findOrFail($this->selectedId);
        $items = $wo->item_service ?? [];
        array_splice($items, $index, 1);
        $wo->update(['item_service' => $items ?: null]);
        $this->currentItemService = $items;
    }

    // ── Helpers ───────────────────────────────────────────────

    private function notifyTenant(WorkOrder $wo, string $msg, string $status): void
    {
        if (! $wo->lot_no) return;

        $tenant = Tenant::with('user')
            ->whereRaw('UPPER(unit_number) = ?', [strtoupper($wo->lot_no)])
            ->first();

        if ($tenant?->user) {
            $tenant->user->notify(new \App\Notifications\WoStatusNotification($wo, $msg, $status));
        }
    }

    // ── Data ──────────────────────────────────────────────────

    public function with(): array
    {
        $query = WorkOrder::query()
            ->where('status_comp', '!=', 'Work Order Close')
            ->when($this->fExIn,        fn($q) => $q->where('ex_in', $this->fExIn))
            ->when($this->fNoComplain,  fn($q) => $q->where('no_complain', 'like', "%{$this->fNoComplain}%"))
            ->when($this->fNoWo,        fn($q) => $q->where('no_wo', 'like', "%{$this->fNoWo}%"))
            ->when($this->fJenisWo,     fn($q) => $q->where('jenis_wo', $this->fJenisWo))
            ->when($this->fSubJenisWo,  fn($q) => $q->where('sub_jenis_wo', 'like', "%{$this->fSubJenisWo}%"))
            ->when($this->fEstClos,     fn($q) => $q->whereDate('estimated_close', $this->fEstClos))
            ->when($this->fLotNo,       fn($q) => $q->where('lot_no', 'like', "%{$this->fLotNo}%"))
            ->when($this->fName,        fn($q) => $q->where('name', 'like', "%{$this->fName}%"))
            ->when($this->fDescs,       fn($q) => $q->where('descs', 'like', "%{$this->fDescs}%"))
            ->when($this->fStatusComp,  fn($q) => $q->where('status_comp', $this->fStatusComp))
            ->when($this->fDurasiiBln,  fn($q) => $q->where('durasi_bln', $this->fDurasiiBln))
            ->when($this->fRequestVia,  fn($q) => $q->where('request_via', $this->fRequestVia))
            ->when($this->fAssignDep,   fn($q) => $q->where('assign_dep', $this->fAssignDep))
            ->when($this->fAssignStaff, fn($q) => $q->where('assign_staff', 'like', "%{$this->fAssignStaff}%"))
            ->orderBy('tanggal', 'desc');

        $workOrders  = $query->paginate($this->perPage);
        $total       = WorkOrder::count();
        $selectedWo  = $this->selectedId > 0 ? WorkOrder::find($this->selectedId) : null;
        $itemMasters = ItemMaster::orderBy('kode')->get();
        $staffList   = Staff::where('status', 'Aktif')->orderBy('nama_staff')->get();

        // FIFO: antrian pengecekan WO aktif, urut dari yang paling lama dibuat
        $fifoQueue = WorkOrder::whereNotIn('status_comp', ['Work Order Close', 'Selesai'])
            ->orderBy('created_at')
            ->pluck('id')
            ->values()
            ->mapWithKeys(fn($id, $pos) => [$id => $pos + 1])
            ->toArray();

        return compact('workOrders', 'total', 'selectedWo', 'itemMasters', 'staffList', 'fifoQueue');
    }
}; ?>

{{-- ─── PANEL HELPER: shared header style ─────────────────────────────── --}}
@php
    $panelHeaderStyle = 'background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%); color: white;';
    $panelHeaderClass = 'flex items-center justify-between px-3 py-1.5';
    $panelBtnClose    = 'w-5 h-5 rounded-full border border-white/60 flex items-center justify-center text-xs font-bold hover:bg-white/20 transition-colors leading-none';
    $fieldLabel       = 'text-right pr-3 text-gray-700 align-middle py-0.5';
    $fieldLabelTop    = 'text-right pr-3 text-gray-700 align-top py-0.5 pt-1.5';
    $inp              = 'border border-gray-400 px-2 py-0.5 text-[12px]';
    $inpRo            = 'border border-gray-300 px-2 py-0.5 text-[12px] bg-gray-50';
@endphp

<div>
    <div class="px-3 py-3">

        {{-- Title + FIFO badge --}}
        <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-2">
            <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center gap-3"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <span>WORK ORDER</span>
                <span class="bg-white/20 text-white text-[10px] font-bold px-2 py-0.5 rounded border border-white/40">FIFO</span>
                <span class="text-[11px] font-normal opacity-80">WO aktif diproses berurutan dari yang paling lama diterima</span>
            </div>
        </div>

        {{-- ── Table ── --}}
        <div class="border border-gray-400 overflow-x-auto" style="font-size: 11px;">
            <table class="border-collapse" style="min-width: 1800px; width: 100%;">
                <thead>
                    <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                        <th class="border border-gray-400 px-1 py-2 text-center w-7 leading-tight">#</th>
                        <th class="border border-gray-400 px-1 py-2 text-center w-14 leading-tight" title="Antrian FIFO — #1 diproses pertama">ANTRIAN</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-12 leading-tight">EX/IN</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-32 leading-tight">NO<br>COMPLAIN</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-44 leading-tight">NO WO</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-32 leading-tight">JENIS WO</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-28 leading-tight">SUB JENIS<br>WO</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-36 leading-tight">DATE ↑</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-24 leading-tight">ESTIMATED<br>CLOSE</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-20 leading-tight">LOT NO</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-36 leading-tight">NAME</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-56 leading-tight">DESCS</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-32 leading-tight">STATUS<br>COMP</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-28 leading-tight">DURASI</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-24 leading-tight">DURASI<br>BULAN</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-32 leading-tight">REQUEST<br>BY</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-24 leading-tight">REQUEST<br>VIA</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-20 leading-tight">ASSIGN<br>DEPT</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-32 leading-tight">ASSIGN<br>STAFF</th>
                        <th class="border border-gray-400 px-2 py-2 text-center w-48 leading-tight">ITEM &amp;<br>SERVICE</th>
                    </tr>
                    <tr style="background-color: #f5f5f5;">
                        <th class="border border-gray-400 px-1 py-0.5"></th>
                        <th class="border border-gray-400 px-1 py-0.5"></th>
                        <th class="border border-gray-400 px-1 py-0.5">
                            <select wire:model.live="fExIn" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                                <option value=""></option><option value="IN">IN</option><option value="EX">EX</option>
                            </select>
                        </th>
                        <th class="border border-gray-400 px-1 py-0.5"><input wire:model.live.debounce.300ms="fNoComplain" type="text" class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                        <th class="border border-gray-400 px-1 py-0.5"><input wire:model.live.debounce.300ms="fNoWo" type="text" class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                        <th class="border border-gray-400 px-1 py-0.5">
                            <select wire:model.live="fJenisWo" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                                <option value=""></option>
                                @foreach (\App\Models\WorkOrder::jenisWoOptions() as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="border border-gray-400 px-1 py-0.5"><select wire:model.live="fSubJenisWo" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white"><option value=""></option></select></th>
                        <th class="border border-gray-400 px-1 py-0.5"></th>
                        <th class="border border-gray-400 px-1 py-0.5"><input wire:model.live.debounce.300ms="fEstClos" type="date" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white" /></th>
                        <th class="border border-gray-400 px-1 py-0.5"><input wire:model.live.debounce.300ms="fLotNo" type="text" class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                        <th class="border border-gray-400 px-1 py-0.5"><input wire:model.live.debounce.300ms="fName" type="text" class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                        <th class="border border-gray-400 px-1 py-0.5"><input wire:model.live.debounce.300ms="fDescs" type="text" class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                        <th class="border border-gray-400 px-1 py-0.5">
                            <select wire:model.live="fStatusComp" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                                <option value=""></option>
                                @foreach (\App\Models\WorkOrder::statusCompOptions() as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="border border-gray-400 px-1 py-0.5"></th>
                        <th class="border border-gray-400 px-1 py-0.5">
                            <select wire:model.live="fDurasiiBln" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                                <option value=""></option><option value="kurang1bln">kurang1bln</option><option value="1-3bln">1-3bln</option><option value="lebih3bln">lebih3bln</option>
                            </select>
                        </th>
                        <th class="border border-gray-400 px-1 py-0.5"><input wire:model.live.debounce.300ms="fName" type="text" class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                        <th class="border border-gray-400 px-1 py-0.5">
                            <select wire:model.live="fRequestVia" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                                <option value=""></option>
                                @foreach (\App\Models\WorkOrder::requestViaOptions() as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="border border-gray-400 px-1 py-0.5">
                            <select wire:model.live="fAssignDep" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                                <option value=""></option>
                                @foreach (\App\Models\WorkOrder::assignDepOptions() as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="border border-gray-400 px-1 py-0.5"><input wire:model.live.debounce.300ms="fAssignStaff" type="text" class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" /></th>
                        <th class="border border-gray-400 px-1 py-0.5"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($workOrders as $i => $wo)
                    @php
                        $rowNo   = ($workOrders->currentPage() - 1) * $workOrders->perPage() + $i + 1;
                        $isIN    = $wo->ex_in === 'IN';
                        $isSel   = $selectedId === $wo->id;
                        $bgStyle = $isSel ? 'background-color:#d0e8ff;' : ($isIN ? 'background-color:#fffacd;' : 'background-color:#ffffff;');
                    @endphp
                    <tr wire:click="selectRow({{ $wo->id }})" style="{{ $bgStyle }}" class="cursor-pointer hover:opacity-80">
                        <td class="border border-gray-300 px-1 py-0.5 text-center text-gray-500 align-top">{{ $rowNo }}</td>
                        {{-- FIFO antrian --}}
                        <td class="border border-gray-300 px-1 py-0.5 text-center align-top">
                            @if(isset($fifoQueue[$wo->id]))
                                @php $q = $fifoQueue[$wo->id]; @endphp
                                <span class="font-bold text-[10px] px-1 py-0.5 rounded
                                    {{ $q === 1 ? 'bg-green-500 text-white' : ($q <= 3 ? 'bg-yellow-400 text-gray-800' : 'bg-gray-200 text-gray-600') }}">
                                    #{{ $q }}
                                </span>
                            @else
                                <span class="text-gray-300 text-[10px]">✓</span>
                            @endif
                        </td>
                        <td class="border border-gray-300 px-1 py-0.5 text-center font-medium align-top text-gray-700">{{ $wo->ex_in }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top">
                            @if($wo->no_complain)
                                <button wire:click.stop="openComplainModal('{{ $wo->no_complain }}')"
                                        class="text-blue-600 hover:underline font-medium text-left leading-tight">
                                    {{ $wo->no_complain }}
                                </button>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top">
                            <a wire:click.stop="openTimeline({{ $wo->id }})" href="#"
                               class="text-[#1a6b9a] hover:underline font-medium">{{ $wo->no_wo }}</a>
                            @if($wo->input_by)<div class="text-gray-400 text-[10px]">input by: {{ $wo->input_by }}</div>@endif
                        </td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700">{{ $wo->jenis_wo }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-500 text-center">{{ $wo->sub_jenis_wo ?? '-' }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700 whitespace-nowrap">{{ $wo->tanggal->format('Y-m-d H:i:s') }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700 whitespace-nowrap">{{ $wo->estimated_close?->format('Y-m-d') }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top font-medium text-gray-700">{{ $wo->lot_no }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700">{{ $wo->name }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700 max-w-xs"><div class="line-clamp-2">{{ $wo->descs }}</div></td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-center">
                            @if($wo->status_comp)
                            <button wire:click.stop="openUpdateComplain({{ $wo->id }})"
                                    class="hover:underline text-left leading-tight"
                                    style="color:#3a9aaa;">
                                {{ $wo->status_comp }}
                            </button>
                            @endif
                        </td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-600 whitespace-nowrap text-center">
                            @php
                                $start   = $wo->tanggal;
                                $end     = $wo->work_closed ?? now();
                                $diffMin = (int) $start->diffInMinutes($end);
                                if ($diffMin < 60) {
                                    $durasiLabel = $diffMin . ' Menit';
                                } elseif ($diffMin < 1440) {
                                    $jam = intdiv($diffMin, 60);
                                    $min = $diffMin % 60;
                                    $durasiLabel = $jam . ' Jam' . ($min > 0 ? ' ' . $min . ' Mnt' : '');
                                } else {
                                    $hari = intdiv($diffMin, 1440);
                                    $jam  = intdiv($diffMin % 1440, 60);
                                    $durasiLabel = $hari . ' Hari' . ($jam > 0 ? ' ' . $jam . ' Jam' : '');
                                }
                            @endphp
                            {{ $durasiLabel }}
                        </td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-600 text-center">{{ $wo->durasi_bln }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700">{{ $wo->request_by }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-600 text-center">{{ $wo->request_via }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top font-medium text-center text-gray-700">{{ $wo->assign_dep }}</td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top text-gray-700">
                            @if ($wo->assign_staff)
                                <div class="text-[11px]">{{ $wo->assign_staff }}</div>
                            @endif
                            <button type="button" wire:click.stop="openAssign({{ $wo->id }})"
                                    class="text-blue-500 hover:underline text-[10px]">Add user</button>
                        </td>
                        <td class="border border-gray-300 px-2 py-0.5 align-top">
                            @if ($wo->item_service)
                                @foreach ($wo->item_service as $item)
                                <div class="text-gray-600 truncate max-w-44">{{ $item['nama'] }} - {{ number_format($item['harga']) }}({{ $item['qty'] }})</div>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="19" class="border border-gray-300 px-4 py-6 text-center text-gray-400">Tidak ada data work order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Action Buttons ── --}}
        @php
            $btnPrimary = 'flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded bg-blue-600 hover:bg-blue-700 text-white transition-colors';
            $btnEdit    = 'flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded bg-amber-500 hover:bg-amber-600 text-white transition-colors';
            $btnDanger  = 'flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded bg-red-600 hover:bg-red-700 text-white transition-colors';
            $btnSlate   = 'flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded bg-slate-600 hover:bg-slate-700 text-white transition-colors';
            $btnOff     = 'flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded bg-gray-200 text-gray-400 cursor-not-allowed';
            $sel        = $selectedId > 0;
        @endphp
        <div class="flex items-center flex-wrap gap-1.5 mt-2">
            <button wire:click="openAddForm" class="{{ $btnPrimary }}">+ Add</button>

            <button wire:click="{{ $sel ? 'openBalas' : '' }}"
                    class="{{ $sel ? $btnPrimary : $btnOff }}" {{ !$sel ? 'disabled' : '' }}>
                Balas Request
            </button>

            {{-- Edit — selalu tampil, warna amber saat aktif --}}
            @if($sel)
            <button wire:click="openEdit"
                    class="flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded text-white"
                    style="background-color:#f59e0b; border:1px solid #b45309;">
                Edit WO
            </button>
            @else
            <button disabled
                    class="flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded cursor-not-allowed"
                    style="background-color:#e5e7eb; color:#9ca3af; border:1px solid #d1d5db;">
                Edit WO
            </button>
            @endif

            <button wire:click="{{ $sel ? 'openClose' : '' }}"
                    class="{{ $sel ? $btnDanger : $btnOff }}" {{ !$sel ? 'disabled' : '' }}>
                Closing WO
            </button>

            <button wire:click="{{ $sel ? 'openItemService' : '' }}"
                    class="{{ $sel ? $btnSlate : $btnOff }}" {{ !$sel ? 'disabled' : '' }}>
                Item &amp; Service
            </button>

            <button class="{{ $sel ? $btnSlate : $btnOff }}" {{ !$sel ? 'disabled' : '' }}>
                + Add Card
            </button>

            @if ($sel)
            <a href="{{ route('karyawan.cs.work-order.print', $selectedId) }}" target="_blank"
               class="{{ $btnSlate }}">Print WO</a>
            @else
            <button class="{{ $btnOff }}" disabled>Print WO</button>
            @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════
             PANEL: ADD WO
        ════════════════════════════════════════════════════════════ --}}
        @if ($panelMode === 'add')
        <div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="max-width:700px;">
            <div class="{{ $panelHeaderClass }} rounded-t-lg" style="{{ $panelHeaderStyle }}">
                <span class="font-bold text-sm tracking-wide">WO INPUT MODE.</span>
                <button wire:click="closePanel" class="{{ $panelBtnClose }}">✕</button>
            </div>
            <div class="bg-white px-5 py-4 text-[12px]">
                <table class="w-full" style="border-collapse:separate; border-spacing:0 5px;">
                    <colgroup><col style="width:185px;"><col></colgroup>
                    <tr>
                        <td class="{{ $fieldLabel }}">WO Type</td>
                        <td>
                            <select wire:model.live="formExIn" class="{{ $inp }}">
                                <option value="IN">INTERNAL</option><option value="EX">EXTERNAL</option>
                            </select>
                            <span class="text-red-600 ml-2 text-[11px]">untuk WO External Input dari menu Tenant Request</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">WO Date</td>
                        <td x-data="{d:'',init(){this.t();setInterval(()=>this.t(),60000)},t(){const n=new Date(),p=v=>String(v).padStart(2,'0');this.d=n.getFullYear()+'-'+p(n.getMonth()+1)+'-'+p(n.getDate())+' '+p(n.getHours())+':'+p(n.getMinutes())}}" x-init="init()">
                            <input :value="d" type="text" readonly class="{{ $inpRo }} w-40" />
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabelTop }}">Lot No</td>
                        <td>
                            <input wire:model.live.debounce.500ms="formLotNo" type="text" placeholder="MP/25/AB" style="text-transform:uppercase;" class="{{ $inp }} w-32" />
                            @if (!empty($tenantInfo))
                                @if ($tenantInfo['found'])
                                <div class="mt-1.5 leading-5">
                                    <div class="font-semibold">** Nama Pemilik : {{ $tenantInfo['name'] }}</div>
                                    <a href="#" class="text-blue-600 hover:underline text-[11px]">LIHAT HISTORY REQUEST</a><br>
                                    <span class="text-gray-500 text-[11px]">** Belum ada data STR</span><br>
                                    <span class="text-gray-500 text-[11px]">** Belum Download Aplikasi</span>
                                </div>
                                @else
                                <div class="mt-1 text-[11px] text-red-500">** Lot No tidak ditemukan</div>
                                @endif
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabelTop }}">Description</td>
                        <td>
                            <textarea wire:model="formDescs" rows="3" class="{{ $inp }} resize-y" style="width:460px;max-width:100%;"></textarea>
                            @error('formDescs')<div class="text-red-500 text-[10px]">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Request By</td>
                        <td>
                            <input wire:model="formRequestBy" type="text" class="{{ $inp }} w-64" />
                            @error('formRequestBy')<div class="text-red-500 text-[10px]">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Request Via</td>
                        <td>
                            <select wire:model="formRequestVia" class="{{ $inp }}">
                                <option value=""></option>
                                <option>Visit</option><option>Phone</option><option>Letter</option><option>WhatsApp</option><option>Email</option>
                            </select>
                            @error('formRequestVia')<div class="text-red-500 text-[10px]">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Category</td>
                        <td>
                            <select wire:model="formJenisWo" class="{{ $inp }} w-64">
                                <option value=""></option>
                                <option value="CIVIL">Civil</option>
                                <option value="ELECTRICAL">Electric</option>
                                <option value="PLUMBING">Plumbing</option>
                                <option value="PERGANTIAN ACCESS CARD">Access Card</option>
                                <option value="MECHANICAL">Lift</option>
                                <option value="WATER / ELECTRICITY">Water / Electricity</option>
                                <option value="GENERAL">New Work</option>
                                <option value="PAINTING">Painting</option>
                                <option value="HVAC">HVAC</option>
                            </select>
                            @error('formJenisWo')<div class="text-red-500 text-[10px]">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">estimated completion date</td>
                        <td><input wire:model="formEstClose" type="date" class="{{ $inp }} w-40" /></td>
                    </tr>
                </table>
                <div class="flex gap-4 mt-5 justify-center">
                    <button wire:click="saveWo" wire:loading.attr="disabled" class="px-6 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveWo">Save</span>
                        <span wire:loading wire:target="saveWo">Saving...</span>
                    </button>
                    <button wire:click="closePanel" class="px-6 py-1.5 bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold rounded">Cancel</button>
                </div>
            </div>
        </div>
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════
             PANEL: BALAS REQUEST
        ════════════════════════════════════════════════════════════ --}}
        @if ($panelMode === 'balas' && $selectedWo)
        <div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="max-width:700px;">
            <div class="{{ $panelHeaderClass }} rounded-t-lg" style="{{ $panelHeaderStyle }}">
                <span class="font-bold text-sm tracking-wide">BALAS REQUEST</span>
                <button wire:click="closePanel" class="{{ $panelBtnClose }}">✕</button>
            </div>
            <div class="bg-white px-5 py-4 text-[12px]">
                {{-- Two reference boxes (as in original system) --}}
                <div class="flex gap-2 mb-3">
                    <input type="text" class="border border-gray-300 px-2 py-0.5 text-[12px]" style="width:160px;" placeholder="Ref. 1" />
                    <input type="text" class="border border-gray-300 px-2 py-0.5 text-[12px]" style="width:160px;" placeholder="Ref. 2" />
                </div>
                <table class="w-full" style="border-collapse:separate; border-spacing:0 4px;">
                    <colgroup><col style="width:120px;"><col></colgroup>
                    <tr><td class="{{ $fieldLabel }}">Nomor Request</td><td><span class="font-bold">{{ $selectedWo->no_complain ?? '-' }}</span></td></tr>
                    <tr><td class="{{ $fieldLabel }}">Tgl Request</td><td>{{ $selectedWo->tanggal->format('Y-m-d H:i:s') }}</td></tr>
                    <tr><td class="{{ $fieldLabel }}">Nomor WO</td><td><span class="font-bold">{{ $selectedWo->no_wo }}</span></td></tr>
                    <tr><td class="{{ $fieldLabel }}">Tgl WO</td><td>{{ $selectedWo->tanggal->format('Y-m-d H:i:s') }}</td></tr>
                    <tr><td class="{{ $fieldLabel }}">UNIT</td><td>{{ $selectedWo->lot_no ?? '-' }}</td></tr>
                    <tr><td class="{{ $fieldLabel }}">Description</td><td>{{ $selectedWo->descs }}</td></tr>
                    <tr><td class="{{ $fieldLabel }}">Request By</td><td>{{ $selectedWo->request_by }}</td></tr>
                    <tr>
                        <td class="{{ $fieldLabelTop }}">Balas Request</td>
                        <td>
                            <textarea wire:model="balasText" rows="5" class="{{ $inp }} resize-y" style="width:460px;max-width:100%;"></textarea>
                            @error('balasText')<div class="text-red-500 text-[10px]">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Upload Gambar</td>
                        <td>
                            <input type="file" wire:model="balasFoto" accept="image/*" class="text-[12px]">
                            @if($balasFoto)
                            <img src="{{ $balasFoto->temporaryUrl() }}" class="mt-1 max-h-24 rounded border border-gray-200 object-contain">
                            @endif
                        </td>
                    </tr>
                </table>
                <div class="flex gap-4 mt-5 justify-center">
                    <button wire:click="saveBalas" wire:loading.attr="disabled" class="px-6 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveBalas">Save</span>
                        <span wire:loading wire:target="saveBalas">Saving...</span>
                    </button>
                    <button wire:click="closePanel" class="px-6 py-1.5 bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold rounded">Cancel</button>
                </div>
            </div>
        </div>
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════
             PANEL: EDIT MODE
        ════════════════════════════════════════════════════════════ --}}
        @if ($panelMode === 'edit' && $selectedWo)
        @php $deptMap = \App\Models\WorkOrder::jenisWoDeptMap(); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="max-width:700px;">
            <div class="{{ $panelHeaderClass }} rounded-t-lg" style="{{ $panelHeaderStyle }}">
                <span class="font-bold text-sm tracking-wide">EDIT MODE</span>
                <button wire:click="closePanel" class="{{ $panelBtnClose }}">✕</button>
            </div>
            <div class="bg-white px-5 py-4 text-[12px]">
                <table class="w-full" style="border-collapse:separate; border-spacing:0 5px;">
                    <colgroup><col style="width:120px;"><col></colgroup>
                    <tr>
                        <td class="{{ $fieldLabel }}">No Complain</td>
                        <td><input wire:model="editNoComplain" type="text" class="{{ $inp }} w-56" /></td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">WO Type</td>
                        <td>
                            <select wire:model.live="editExIn" class="{{ $inp }}">
                                <option value="IN">INTERNAL</option><option value="EX">EXTERNAL</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">WO Date</td>
                        <td><input wire:model="editWoDate" type="text" readonly class="{{ $inpRo }} w-44" /></td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Estimated Date</td>
                        <td><input wire:model="editEstDate" type="date" class="{{ $inp }} w-40" /></td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Lot No</td>
                        <td><input wire:model="editLotNo" type="text" style="text-transform:uppercase;" class="{{ $inp }} w-32" /></td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabelTop }}">Description</td>
                        <td>
                            <textarea wire:model="editDescs" rows="3" class="{{ $inp }} resize-y" style="width:460px;max-width:100%;"></textarea>
                            @error('editDescs')<div class="text-red-500 text-[10px]">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Request By</td>
                        <td>
                            <input wire:model="editRequestBy" type="text" class="{{ $inp }} w-64" />
                            @error('editRequestBy')<div class="text-red-500 text-[10px]">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Request Via</td>
                        <td>
                            <select wire:model="editRequestVia" class="{{ $inp }}">
                                <option value=""></option>
                                <option>Visit</option><option>Phone</option><option>Letter</option><option>WhatsApp</option><option>Email</option>
                            </select>
                            @error('editRequestVia')<div class="text-red-500 text-[10px]">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Jenis WO</td>
                        <td>
                            <select wire:model="editJenisWo" class="{{ $inp }} w-64">
                                <option value=""></option>
                                @foreach (\App\Models\WorkOrder::jenisWoOptions() as $opt)
                                <option value="{{ $opt }}">{{ $opt }} ({{ $deptMap[$opt] ?? 'ENG' }})</option>
                                @endforeach
                            </select>
                            @error('editJenisWo')<div class="text-red-500 text-[10px]">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Sub Jenis WO</td>
                        <td>
                            <select wire:model="editSubJenisWo" class="{{ $inp }} w-48">
                                <option value="">Pilih Sub Jenis WO</option>
                                @foreach (\App\Models\WorkOrder::subJenisWoOptions() as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                </table>
                <div class="flex gap-4 mt-5 justify-center">
                    <button wire:click="saveEdit" wire:loading.attr="disabled" class="px-6 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveEdit">Save</span>
                        <span wire:loading wire:target="saveEdit">Saving...</span>
                    </button>
                    <button wire:click="closePanel" class="px-6 py-1.5 bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold rounded">Cancel</button>
                </div>
            </div>
        </div>
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════
             PANEL: CLOSE WO
        ════════════════════════════════════════════════════════════ --}}
        @if ($panelMode === 'close' && $selectedWo)
        <div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="max-width:700px;">
            <div class="{{ $panelHeaderClass }} rounded-t-lg" style="{{ $panelHeaderStyle }}">
                <span class="font-bold text-sm tracking-wide">CLOSE WO</span>
                <button wire:click="closePanel" class="{{ $panelBtnClose }}">✕</button>
            </div>
            <div class="bg-white px-5 py-4 text-[12px]">
                <table class="w-full" style="border-collapse:separate; border-spacing:0 5px;">
                    <colgroup><col style="width:140px;"><col></colgroup>
                    <tr><td class="{{ $fieldLabel }}">NO WO</td><td><span class="font-bold">{{ $selectedWo->no_wo }}</span></td></tr>
                    <tr><td class="{{ $fieldLabel }}">TGL WO</td><td>{{ $selectedWo->tanggal->format('Y-m-d H:i:s') }}</td></tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Work Started</td>
                        <td class="align-middle py-0.5">
                            <input wire:model="closeWorkStartedDate" type="date" class="{{ $inp }} w-32" />
                            <span class="mx-1 text-gray-500">Time</span>
                            <input wire:model="closeWorkStartedTime" type="time" step="1" class="{{ $inp }} w-28" />
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Closing Date</td>
                        <td class="align-middle py-0.5">
                            <input wire:model="closeClosingDate" type="date" class="{{ $inp }} w-32" />
                            <span class="mx-1 text-gray-500">Time</span>
                            <input wire:model="closeClosingTime" type="time" step="1" class="{{ $inp }} w-28" />
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Action by</td>
                        <td>
                            <input wire:model="closeActionBy" type="text" class="{{ $inp }} w-48" />
                            @error('closeActionBy')<div class="text-red-500 text-[10px]">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabelTop }}">Action Taken</td>
                        <td>
                            <textarea wire:model="closeActionTaken" rows="3" class="{{ $inp }} resize-y" style="width:460px;max-width:100%;"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Upload Gambar</td>
                        <td>
                            <input type="file" wire:model="closeFoto" accept="image/*" class="text-[12px]">
                            @if($closeFoto)
                            <img src="{{ $closeFoto->temporaryUrl() }}" class="mt-1 max-h-24 rounded border border-gray-200 object-contain">
                            @endif
                        </td>
                    </tr>
                </table>
                <div class="flex gap-4 mt-5 justify-center">
                    <button wire:click="saveClose" wire:loading.attr="disabled" class="px-6 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveClose">Save</span>
                        <span wire:loading wire:target="saveClose">Saving...</span>
                    </button>
                    <button wire:click="closePanel" class="px-6 py-1.5 bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold rounded">Cancel</button>
                </div>
            </div>
        </div>
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════
             PANEL: ITEM & SERVICE
        ════════════════════════════════════════════════════════════ --}}
        @if ($panelMode === 'item-service' && $selectedWo)
        <div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="max-width:760px;">
            <div class="{{ $panelHeaderClass }} rounded-t-lg" style="{{ $panelHeaderStyle }}">
                <span class="font-bold text-sm tracking-wide">Add Item &amp; Service</span>
                <button wire:click="closePanel" class="{{ $panelBtnClose }}">✕</button>
            </div>
            <div class="bg-white px-5 py-4 text-[12px]">
                {{-- Add item form --}}
                <div class="flex items-start gap-3 mb-3">
                    <div>
                        <label class="text-gray-600 mr-2">Item &amp; Service</label>
                        <select wire:model="isItemId" class="{{ $inp }}" style="width:340px;">
                            <option value="0">-- Pilih Item --</option>
                            @foreach ($itemMasters as $im)
                            <option value="{{ $im->id }}">{{ $im->dropdown_label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-gray-600 mr-1">Qty</label>
                        <input wire:model="isQty" type="number" min="1" class="{{ $inp }} w-16" />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="text-gray-600 block mb-1">Note</label>
                    <textarea wire:model="isNote" rows="3" class="{{ $inp }} resize-y" style="width:500px;max-width:100%;"></textarea>
                </div>
                <div class="flex gap-3 mb-5">
                    <button wire:click="addItem" wire:loading.attr="disabled"
                            class="px-6 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded disabled:opacity-60">
                        ADD
                    </button>
                    <button wire:click="closePanel" class="px-6 py-1.5 bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold rounded">
                        Cancel
                    </button>
                </div>

                {{-- List --}}
                <div class="font-bold text-[12px] mb-1" style="color:#1e40af;">List Item &amp; Service</div>
                <table class="w-full border-collapse text-[11px]">
                    <thead>
                        <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                            <th class="border border-gray-400 px-2 py-1 text-left">ITEM &amp; SERVICE</th>
                            <th class="border border-gray-400 px-2 py-1 text-center w-12">QTY</th>
                            <th class="border border-gray-400 px-2 py-1 text-center w-20">SATUAN</th>
                            <th class="border border-gray-400 px-2 py-1 text-right w-20">HARGA</th>
                            <th class="border border-gray-400 px-2 py-1 text-left w-32">NOTE</th>
                            <th class="border border-gray-400 px-2 py-1 w-14"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($currentItemService as $idx => $item)
                        <tr class="{{ $idx % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                            <td class="border border-gray-300 px-2 py-1">{{ $item['nama'] ?? '' }}</td>
                            <td class="border border-gray-300 px-2 py-1 text-center">{{ $item['qty'] ?? '' }}</td>
                            <td class="border border-gray-300 px-2 py-1 text-center">{{ $item['satuan'] ?? '' }}</td>
                            <td class="border border-gray-300 px-2 py-1 text-right">{{ number_format($item['harga'] ?? 0) }}</td>
                            <td class="border border-gray-300 px-2 py-1 text-gray-500">{{ $item['note'] ?? '' }}</td>
                            <td class="border border-gray-300 px-2 py-1 text-center">
                                <button wire:click="removeItem({{ $idx }})"
                                        class="px-2 py-0.5 text-[10px] font-semibold rounded bg-red-600 hover:bg-red-700 text-white">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="border border-gray-300 px-4 py-3 text-center text-gray-400">Belum ada item.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════
             PANEL: TIMELINE / HISTORY WO
        ════════════════════════════════════════════════════════════ --}}
        @if ($panelMode === 'timeline' && $selectedWo)
        <div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="max-width:620px;">
            <div class="{{ $panelHeaderClass }} rounded-t-lg" style="{{ $panelHeaderStyle }}">
                <span class="font-bold text-sm tracking-wide">TIMELINE – {{ $selectedWo->no_wo }}</span>
                <button wire:click="closePanel" class="{{ $panelBtnClose }}">✕</button>
            </div>
            <div class="bg-white px-5 py-4 text-[12px]">
                {{-- Timeline events --}}
                <div class="space-y-2 border-b pb-4 mb-4">
                    <div class="text-center font-medium text-gray-700">
                        Request Di terima
                        @if($selectedWo->no_complain) No.{{ $selectedWo->no_complain }} @endif
                        on {{ $selectedWo->tanggal->format('M d Y g:i:s:A') }}
                    </div>

                    @if($selectedWo->balas_at)
                    <div class="text-gray-700 text-center">
                        Pengecekan akan di lakukan A/N {{ $selectedWo->balas_by }}
                        on {{ $selectedWo->balas_at->format('M d Y g:i:s:A') }}
                    </div>
                    @endif

                    @if($selectedWo->work_started)
                    <div class="text-gray-700 text-center">
                        Mulai Pengecekan oleh {{ $selectedWo->action_by ?? $selectedWo->assign_staff }}
                        on {{ $selectedWo->work_started->format('M d Y g:i:s:A') }}
                    </div>
                    @endif

                    @if($selectedWo->work_closed)
                    <div class="text-gray-700 text-center">
                        Selesai Pengecekan oleh {{ $selectedWo->action_by ?? $selectedWo->assign_staff }}
                        on {{ $selectedWo->work_closed->format('M d Y g:i:s:A') }}
                    </div>
                    @endif

                    @if(! $selectedWo->balas_at && ! $selectedWo->work_started)
                    <div class="text-center text-gray-400 italic text-[11px]">Belum ada update pengecekan.</div>
                    @endif
                </div>

                {{-- Balas Request text + foto pengecekan --}}
                @if($selectedWo->balas_request || $selectedWo->foto_pengecekan)
                <div class="mb-4">
                    <div class="font-bold text-[12px] mb-1" style="color:#1e40af;">Balas Request / Pengecekan</div>
                    @if($selectedWo->balas_by)
                    <div class="text-gray-500 text-[11px] mb-1">{{ $selectedWo->balas_by }} on {{ $selectedWo->balas_at?->format('M d Y g:i:sA') }}</div>
                    @endif
                    @if($selectedWo->balas_request)
                    <p class="text-gray-700 whitespace-pre-line leading-relaxed mb-2">{{ $selectedWo->balas_request }}</p>
                    @endif
                    @if($selectedWo->foto_pengecekan)
                    <img src="{{ asset('storage/' . $selectedWo->foto_pengecekan) }}" alt="Foto Pengecekan"
                         class="max-h-40 rounded-lg border border-gray-200 object-contain cursor-zoom-in"
                         onclick="window.open(this.src,'_blank')">
                    @endif
                </div>
                @endif

                {{-- Action Taken + foto close --}}
                @if($selectedWo->action_taken || $selectedWo->foto_close)
                <div class="mb-4 border-t pt-3">
                    <div class="font-bold text-[12px] mb-2" style="color:#1e40af;">Action Taken / Closing</div>
                    @if($selectedWo->action_by)
                    <div class="text-gray-500 text-[11px] mb-1">
                        {{ $selectedWo->action_by }} on {{ $selectedWo->work_closed?->format('M d Y g:i:sA') }}
                    </div>
                    @endif
                    @if($selectedWo->action_taken)
                    <p class="text-gray-700 italic leading-relaxed mb-2">{{ $selectedWo->action_taken }}</p>
                    @endif
                    @if($selectedWo->foto_close)
                    <img src="{{ asset('storage/' . $selectedWo->foto_close) }}" alt="Foto Close"
                         class="max-h-40 rounded-lg border border-gray-200 object-contain cursor-zoom-in"
                         onclick="window.open(this.src,'_blank')">
                    @endif
                </div>
                @endif

                {{-- WO info summary --}}
                <div class="mt-3 border-t pt-3 text-[11px] text-gray-500">
                    <span class="font-semibold">Lot:</span> {{ $selectedWo->lot_no ?? '-' }} &nbsp;
                    <span class="font-semibold">Name:</span> {{ $selectedWo->name }} &nbsp;
                    <span class="font-semibold">Status:</span> {{ $selectedWo->status_comp ?? 'Belum ada' }}
                </div>

                {{-- Aksi cepat dari Timeline --}}
                <div class="mt-4 border-t pt-3 flex flex-wrap gap-2">
                    <button wire:click="openEdit"
                            class="px-4 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded">
                        ✏ Edit WO
                    </button>
                    <button wire:click="$set('panelMode','assign')"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded">
                        👤 Assign Staff
                    </button>
                    <button wire:click="closePanel"
                            class="px-4 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded ml-auto">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════
             PANEL: ASSIGN PENGECEKAN WO
        ════════════════════════════════════════════════════════════ --}}
        @if ($panelMode === 'assign' && $selectedWo)
        <div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,0.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="max-width:620px;">
            <div class="{{ $panelHeaderClass }} rounded-t-lg" style="{{ $panelHeaderStyle }}">
                <span class="font-bold text-sm tracking-wide">ASSIGN PENGECEKAN WO</span>
                <button wire:click="closePanel" class="{{ $panelBtnClose }}">✕</button>
            </div>
            <div class="bg-white px-5 py-4 text-[12px]">
                <table class="w-full" style="border-collapse:separate; border-spacing:0 5px;">
                    <colgroup><col style="width:180px;"><col></colgroup>
                    <tr>
                        <td class="{{ $fieldLabel }}">No wo</td>
                        <td><input type="text" value="{{ $selectedWo->no_wo }}" readonly class="{{ $inpRo }} w-56" /></td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabelTop }}">Description</td>
                        <td>
                            <textarea readonly rows="4" class="{{ $inpRo }} resize-none"
                                style="width:360px;max-width:100%;">{{ $selectedWo->descs }}</textarea>
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Request By</td>
                        <td><input type="text" value="{{ $selectedWo->request_by }}" readonly class="{{ $inpRo }} w-48" /></td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Add User</td>
                        <td>
                            <select wire:model="assignStaff" class="{{ $inp }}" style="width:300px;">
                                <option value="">-- Pilih Staff --</option>
                                @foreach ($staffList->groupBy('departemen') as $dept => $staffGroup)
                                <optgroup label="{{ $dept ?: 'Lainnya' }}">
                                    @foreach ($staffGroup as $s)
                                    <option value="{{ $s->nama_staff }}">{{ $s->nama_staff }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                            @error('assignStaff')<div class="text-red-500 text-[10px]">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabel }}">Tgl Janji Bertemu Tenant</td>
                        <td><input wire:model="assignTglJanji" type="date" class="{{ $inp }} w-40" /></td>
                    </tr>
                    <tr>
                        <td class="{{ $fieldLabelTop }}">Note</td>
                        <td>
                            <textarea wire:model="assignNote" rows="4" class="{{ $inp }} resize-y"
                                style="width:360px;max-width:100%;"></textarea>
                        </td>
                    </tr>
                </table>
                <div class="flex gap-4 mt-5 justify-center">
                    <button wire:click="saveAssign" wire:loading.attr="disabled"
                            class="px-6 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveAssign">Save</span>
                        <span wire:loading wire:target="saveAssign">Saving...</span>
                    </button>
                    <button wire:click="closePanel" class="px-6 py-1.5 bg-gray-400 hover:bg-gray-500 text-white text-xs font-semibold rounded">Cancel</button>
                </div>
            </div>
        </div>
        </div>
        @endif

        {{-- ── Pagination ── --}}
        @php
            $wo_cur  = $workOrders->currentPage();
            $wo_last = $workOrders->lastPage();
            $wo_nums = collect();
            for ($p = 1; $p <= $wo_last; $p++) {
                if ($p === 1 || $p === $wo_last || abs($p - $wo_cur) <= 2) { $wo_nums->push($p); }
            }
            $pBtn  = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-center leading-5';
            $pDis  = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-center leading-5';
            $pAct  = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-center leading-5';
        @endphp
        <div class="flex items-center justify-between mt-2 text-[11px] select-none">
            <div class="flex items-center gap-1">
                {{-- |‹ --}}
                @if($workOrders->onFirstPage())
                    <span class="{{ $pDis }}">|‹</span>
                @else
                    <button wire:click="setPage(1)" class="{{ $pBtn }}">|‹</button>
                @endif

                {{-- ‹ --}}
                @if($workOrders->onFirstPage())
                    <span class="{{ $pDis }}">‹</span>
                @else
                    <button wire:click="previousPage" class="{{ $pBtn }}">‹</button>
                @endif

                {{-- Nomor halaman --}}
                @php $wo_prev = null; @endphp
                @foreach($wo_nums as $pg)
                    @if($wo_prev !== null && $pg - $wo_prev > 1)
                        <span class="{{ $pDis }}">…</span>
                    @endif
                    @if($pg == $wo_cur)
                        <span class="{{ $pAct }}">{{ $pg }}</span>
                    @else
                        <button wire:click="setPage({{ $pg }})" class="{{ $pBtn }}">{{ $pg }}</button>
                    @endif
                    @php $wo_prev = $pg; @endphp
                @endforeach

                {{-- › --}}
                @if($workOrders->hasMorePages())
                    <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
                @else
                    <span class="{{ $pDis }}">›</span>
                @endif

                {{-- ›| --}}
                @if($workOrders->hasMorePages())
                    <button wire:click="setPage({{ $wo_last }})" class="{{ $pBtn }}">›|</button>
                @else
                    <span class="{{ $pDis }}">›|</span>
                @endif

                <select wire:model.live="perPage" class="ml-1 border border-gray-400 text-[11px] px-1 py-0.5 bg-white rounded">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option>
                </select>
            </div>
            <span class="text-[11px] text-gray-500">View {{ $workOrders->firstItem() ?? 0 }}–{{ $workOrders->lastItem() ?? 0 }} of {{ $workOrders->total() }}</span>
        </div>

        {{-- ── Note ── --}}
        <div class="mt-5 border-2 border-red-500 p-3 inline-block text-[11px] text-gray-700">
            <p class="font-semibold mb-1">Note:</p>
            <p>* CS (Pengelola): "Tenant Request" atas unit <strong>SUDAH</strong> serah terima unit (STR)</p>
            <p>* CR (Dev): "Tenant Request" atas unit <strong>BELUM</strong> serah terima unit (STR)</p>
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════════
         MODAL: UPDATE COMPLAIN STATUS
    ════════════════════════════════════════════════════════════ --}}
    @if($showUpdateComplain)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         wire:click="$set('showUpdateComplain', false)">
        <div class="bg-white rounded-lg shadow-2xl w-full mx-4 overflow-hidden"
             style="max-width:520px;"
             wire:click.stop>

            {{-- Header --}}
            <div style="background:#5c8a5c; color:white; padding:10px 16px; font-size:13px; font-weight:bold; display:flex; justify-content:space-between; align-items:center;">
                <span>Update Complain</span>
                <button wire:click="$set('showUpdateComplain', false)" style="color:white; font-size:18px; line-height:1; background:none; border:none; cursor:pointer;">✕</button>
            </div>

            <div style="padding:20px 24px; font-size:12px;">
                <table style="width:100%; border-collapse:collapse;">

                    <tr style="margin-bottom:10px;">
                        <td style="width:110px; color:#555; padding:6px 0; vertical-align:top;">No Complain</td>
                        <td style="padding:6px 0;">
                            <input type="text" value="{{ $updateComplainInfo['no_complain'] ?? '' }}" readonly
                                   style="width:220px; border:1px solid #ccc; padding:4px 8px; border-radius:3px; background:#f9f9f9; font-size:12px;">
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#555; padding:6px 0; vertical-align:top;">Lot No</td>
                        <td style="padding:6px 0;">
                            <input type="text" value="{{ $updateComplainInfo['lot_no'] ?? '' }}" readonly
                                   style="width:160px; border:1px solid #ccc; padding:4px 8px; border-radius:3px; background:#f9f9f9; font-size:12px;">
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#555; padding:6px 0; vertical-align:top;">Description</td>
                        <td style="padding:6px 0;">
                            <textarea readonly rows="3"
                                      style="width:100%; border:1px solid #ccc; padding:4px 8px; border-radius:3px; background:#f9f9f9; font-size:12px; resize:none;">{{ $updateComplainInfo['descs'] ?? '' }}</textarea>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#555; padding:6px 0; vertical-align:top;">Request By</td>
                        <td style="padding:6px 0;">
                            <input type="text" value="{{ $updateComplainInfo['request_by'] ?? '' }}" readonly
                                   style="width:220px; border:1px solid #ccc; padding:4px 8px; border-radius:3px; background:#f9f9f9; font-size:12px;">
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#555; padding:6px 0; vertical-align:top;">Request Via</td>
                        <td style="padding:6px 0;">
                            <input type="text" value="{{ $updateComplainInfo['request_via'] ?? '' }}" readonly
                                   style="width:120px; border:1px solid #ccc; padding:4px 8px; border-radius:3px; background:#f9f9f9; font-size:12px;">
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#555; padding:6px 0; vertical-align:top;">Status</td>
                        <td style="padding:6px 0;">
                            <select wire:model="updateComplainStatus"
                                    style="border:1px solid #ccc; padding:4px 8px; border-radius:3px; font-size:12px; width:180px;">
                                <option value="Pesan Diterima">Pesan Diterima</option>
                                <option value="Dalam Pengecekan">Dalam Pengecekan</option>
                                <option value="Dalam Proses">Dalam Proses</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                            @error('updateComplainStatus') <span style="color:red; font-size:11px;">{{ $message }}</span> @enderror
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#555; padding:6px 0; vertical-align:top;">Keterangan</td>
                        <td style="padding:6px 0;">
                            <textarea wire:model="updateComplainKet" rows="3"
                                      style="width:100%; border:1px solid #ccc; padding:4px 8px; border-radius:3px; font-size:12px; resize:vertical;"></textarea>
                        </td>
                    </tr>

                </table>

                <div style="margin-top:16px; display:flex; gap:8px; justify-content:center; border-top:1px solid #e5e7eb; padding-top:12px;">
                    <button wire:click="saveUpdateComplain"
                            style="background:#6b7280; color:white; border:none; padding:6px 24px; border-radius:4px; font-size:12px; cursor:pointer;">
                        Save
                    </button>
                    <button wire:click="$set('showUpdateComplain', false)"
                            style="background:#e5e7eb; color:#374151; border:none; padding:6px 24px; border-radius:4px; font-size:12px; cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: DETAIL COMPLAIN / TENANT REQUEST
    ════════════════════════════════════════════════════════════ --}}
    @if($showComplainModal && $complainData)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
         wire:click="$set('showComplainModal', false)">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-xs mx-4 overflow-hidden"
             wire:click.stop>

            {{-- Header --}}
            <div class="bg-[#1a3a6e] text-white px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold tracking-wide">TENANT REQUEST</p>
                    <p class="text-[11px] font-mono mt-0.5 opacity-90">{{ $complainData['no_request'] }}</p>
                </div>
                <button wire:click="$set('showComplainModal', false)" class="text-white/70 hover:text-white text-xl leading-none">✕</button>
            </div>

            <div class="px-5 py-4 space-y-3 text-[12px]">

                {{-- Kategori --}}
                @if($complainData['kategori'])
                <div class="flex gap-2">
                    <span class="bg-blue-100 text-blue-700 text-[10px] font-semibold px-2 py-0.5 rounded">{{ $complainData['kategori'] }}</span>
                    @if($complainData['sub_kategori'])
                    <span class="bg-gray-100 text-gray-600 text-[10px] px-2 py-0.5 rounded">{{ $complainData['sub_kategori'] }}</span>
                    @endif
                </div>
                @endif

                {{-- Deskripsi --}}
                @if($complainData['descs'])
                <p class="text-gray-700 leading-relaxed bg-gray-50 rounded-lg px-3 py-2">{{ $complainData['descs'] }}</p>
                @endif

                {{-- Timeline --}}
                @php
                    $cs = $complainData['status'] ?? '';
                    $pengecekanOn = $complainData['tgl_verifikasi'] || in_array($cs, ['Dalam Pengecekan','Dalam Proses','Selesai']);
                    $prosesOn     = $complainData['tgl_dalam_proses'] || in_array($cs, ['Dalam Proses','Selesai']);
                    $selesaiOn    = $complainData['tgl_selesai'] || $cs === 'Selesai';
                @endphp
                <div class="space-y-2">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Timeline</p>

                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                        <div>
                            <p class="font-semibold text-gray-700">Pesan Diterima</p>
                            <p class="text-gray-400 text-[11px]">{{ $complainData['input_by'] ?? '-' }} — {{ $complainData['tanggal'] }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 w-2 h-2 rounded-full {{ $pengecekanOn ? 'bg-blue-500' : 'bg-gray-200' }} shrink-0"></span>
                        <div>
                            <p class="font-semibold {{ $pengecekanOn ? 'text-gray-700' : 'text-gray-300' }}">Dalam Pengecekan</p>
                            @if($complainData['tgl_verifikasi'])
                            <p class="text-gray-400 text-[11px]">{{ $complainData['tgl_verifikasi'] }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 w-2 h-2 rounded-full {{ $prosesOn ? 'bg-amber-500' : 'bg-gray-200' }} shrink-0"></span>
                        <div>
                            <p class="font-semibold {{ $prosesOn ? 'text-gray-700' : 'text-gray-300' }}">Dalam Proses</p>
                            @if($complainData['tgl_dalam_proses'])
                            <p class="text-gray-400 text-[11px]">{{ $complainData['tgl_dalam_proses'] }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 w-2 h-2 rounded-full {{ $selesaiOn ? 'bg-green-700' : 'bg-gray-200' }} shrink-0"></span>
                        <div>
                            <p class="font-semibold {{ $selesaiOn ? 'text-gray-700' : 'text-gray-300' }}">Selesai</p>
                            @if($complainData['tgl_selesai'])
                            <p class="text-gray-400 text-[11px]">{{ $complainData['done_by'] ?? '' }} — {{ $complainData['tgl_selesai'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Foto --}}
                @if($complainData['foto'])
                <div x-data="{ lb: false }">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Foto</p>
                    <img src="{{ asset('storage/' . $complainData['foto']) }}" alt="Foto"
                         class="rounded-lg max-h-40 object-contain border border-gray-200 cursor-zoom-in"
                         @click="lb = true">
                    <div x-show="lb" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80" @click="lb = false">
                        <img src="{{ asset('storage/' . $complainData['foto']) }}" alt="Foto"
                             class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain shadow-2xl">
                        <button class="absolute top-4 right-4 text-white text-2xl" @click="lb = false">✕</button>
                    </div>
                </div>
                @endif

                {{-- Status badge --}}
                <div class="pt-1 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-[10px] font-bold uppercase tracking-wide
                        {{ match($complainData['status'] ?? '') {
                            'Selesai' => 'text-green-600',
                            'Dalam Proses' => 'text-amber-600',
                            'Dalam Pengecekan' => 'text-blue-600',
                            default => 'text-gray-500'
                        } }}">
                        ● {{ $complainData['status'] }}
                    </span>
                    <button wire:click="$set('showComplainModal', false)"
                            class="text-xs text-gray-400 hover:text-gray-600 px-3 py-1 border border-gray-200 rounded">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
