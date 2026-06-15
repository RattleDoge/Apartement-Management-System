<?php

use App\Models\GreetingTemplate;
use App\Models\User;
use App\Notifications\GreetingNotification;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.karyawan')] class extends Component
{
    use WithPagination, WithFileUploads;

    // ── Filters ──────────────────────────────────────────────
    public string $fNama   = '';
    public string $fJenis  = '';
    public string $fStatus = '';

    // ── Panel ────────────────────────────────────────────────
    public ?string $panelMode = null;  // null | 'add' | 'edit' | 'view' | 'kirim'
    public int     $selectedId = 0;

    // ── Form fields ──────────────────────────────────────────
    public string  $fNamaTemplate = '';
    public string  $fJenisTemplate = '';
    public string  $fIsi           = '';
    public string  $fStatusForm    = 'Active';
    public         $fCoverImg      = null;
    public         $fContentImg    = null;
    public string  $existingCover  = '';
    public string  $existingContent = '';

    // ── Kirim panel ──────────────────────────────────────────
    public string  $kirimMsg       = '';
    public bool    $kirimOk        = false;

    // ── Access guard ─────────────────────────────────────────
    public function canManage(): bool
    {
        return auth()->user()->role !== 'tenant';
    }

    // ── Filter reset ─────────────────────────────────────────
    public function updatedFNama(): void   { $this->resetPage(); }
    public function updatedFJenis(): void  { $this->resetPage(); }
    public function updatedFStatus(): void { $this->resetPage(); }

    // ── Open panels ──────────────────────────────────────────
    public function openAdd(): void
    {
        $this->panelMode      = 'add';
        $this->selectedId     = 0;
        $this->fNamaTemplate  = '';
        $this->fJenisTemplate = '';
        $this->fIsi           = '';
        $this->fStatusForm    = 'Active';
        $this->fCoverImg      = null;
        $this->fContentImg    = null;
        $this->existingCover  = '';
        $this->existingContent = '';
        $this->resetValidation();
    }

    public function openEdit(int $id): void
    {
        $tpl = GreetingTemplate::findOrFail($id);
        $this->panelMode        = 'edit';
        $this->selectedId       = $id;
        $this->fNamaTemplate    = $tpl->nama_template;
        $this->fJenisTemplate   = $tpl->jenis;
        $this->fIsi             = $tpl->isi ?? '';
        $this->fStatusForm      = $tpl->status;
        $this->fCoverImg        = null;
        $this->fContentImg      = null;
        $this->existingCover    = $tpl->cover_img ?? '';
        $this->existingContent  = $tpl->content_img ?? '';
        $this->resetValidation();
    }

    public function openView(int $id): void
    {
        $this->selectedId = $id;
        $this->panelMode  = 'view';
    }

    public function openKirim(int $id): void
    {
        $this->selectedId = $id;
        $this->panelMode  = 'kirim';
        $this->kirimMsg   = '';
        $this->kirimOk    = false;
    }

    public function selectRow(int $id): void
    {
        $this->selectedId = ($this->selectedId === $id) ? 0 : $id;
    }

    public function closePanel(): void
    {
        $this->panelMode = null;
        $this->resetValidation();
    }

    // ── Save ─────────────────────────────────────────────────
    public function save(): void
    {
        $this->validate([
            'fNamaTemplate'  => 'required|string|max:150',
            'fJenisTemplate' => 'required|string',
            'fCoverImg'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'fContentImg'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'fNamaTemplate.required'  => 'Nama Template wajib diisi.',
            'fJenisTemplate.required' => 'Jenis wajib dipilih.',
        ]);

        $coverPath   = $this->existingCover;
        $contentPath = $this->existingContent;

        if ($this->fCoverImg) {
            if ($coverPath && Storage::disk('public')->exists($coverPath)) {
                Storage::disk('public')->delete($coverPath);
            }
            $coverPath = $this->fCoverImg->store('greeting/cover', 'public');
        }

        if ($this->fContentImg) {
            if ($contentPath && Storage::disk('public')->exists($contentPath)) {
                Storage::disk('public')->delete($contentPath);
            }
            $contentPath = $this->fContentImg->store('greeting/content', 'public');
        }

        $data = [
            'nama_template' => $this->fNamaTemplate,
            'jenis'         => $this->fJenisTemplate,
            'isi'           => $this->fIsi,
            'cover_img'     => $coverPath ?: null,
            'content_img'   => $contentPath ?: null,
            'status'        => $this->fStatusForm,
            'modified_by'   => auth()->user()->name,
        ];

        if ($this->panelMode === 'add') {
            GreetingTemplate::create($data);
        } else {
            GreetingTemplate::findOrFail($this->selectedId)->update($data);
        }

        $this->closePanel();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        if (! $this->canManage()) return;
        $tpl = GreetingTemplate::findOrFail($id);
        if ($tpl->cover_img)   Storage::disk('public')->delete($tpl->cover_img);
        if ($tpl->content_img) Storage::disk('public')->delete($tpl->content_img);
        $tpl->delete();
        if ($this->selectedId === $id) $this->closePanel();
    }

    public function kirimNotif(): void
    {
        if (! $this->canManage()) return;
        $tpl = GreetingTemplate::findOrFail($this->selectedId);

        $tenantUsers = User::where('role', 'tenant')->get();
        foreach ($tenantUsers as $user) {
            $user->notify(new GreetingNotification($tpl));
        }

        $count = $tenantUsers->count();
        $tpl->increment('kirim_count');

        $this->kirimOk  = true;
        $this->kirimMsg = "Notifikasi berhasil dikirim ke {$count} akun penghuni.";
    }

    // ── Data ─────────────────────────────────────────────────
    public function with(): array
    {
        $q = GreetingTemplate::query()
            ->when($this->fNama,   fn($q) => $q->where('nama_template', 'like', "%{$this->fNama}%"))
            ->when($this->fJenis,  fn($q) => $q->where('jenis', $this->fJenis))
            ->when($this->fStatus, fn($q) => $q->where('status', $this->fStatus))
            ->orderByDesc('updated_at');

        $templates  = $q->paginate(10);
        $selected   = $this->selectedId ? GreetingTemplate::find($this->selectedId) : null;
        $tenantCount = User::where('role', 'tenant')->count();

        return compact('templates', 'selected', 'tenantCount');
    }
};
?>

@php
    $hdr = 'background: linear-gradient(to bottom, #dbeafe, #eff6ff);';
    $inp = 'border border-gray-400 px-2 py-1 text-[12px] w-full rounded';
    $lbl = 'text-xs text-gray-600 font-medium mb-1 block';
@endphp

<div class="px-3 py-3">

    {{-- Title --}}
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-3">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            TEMPLATE GREETING
        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="border border-gray-400 overflow-x-auto" style="font-size: 11px;">
        <table class="border-collapse" style="min-width: 900px; width: 100%;">
            <thead>
                <tr style="{{ $hdr }} color:#1e40af;">
                    <th class="border border-gray-400 px-1 py-1 w-7 text-center"></th>
                    <th class="border border-gray-400 px-2 py-1 text-center">NAMA TEMPLATE</th>
                    <th class="border border-gray-400 px-2 py-1 text-center w-32">JENIS</th>
                    <th class="border border-gray-400 px-2 py-1 text-center w-24">IMAGE COVER</th>
                    <th class="border border-gray-400 px-2 py-1 text-center w-24">IMAGE CONTENT</th>
                    <th class="border border-gray-400 px-2 py-1 text-center w-24">STATUS</th>
                    <th class="border border-gray-400 px-2 py-1 text-center w-24">MODIFIED</th>
                    <th class="border border-gray-400 px-2 py-1 text-center w-28">KIRIM NOTIF</th>
                </tr>
                {{-- Filter row --}}
                <tr class="bg-gray-50">
                    <td class="border border-gray-300 px-1 py-0.5"></td>
                    <td class="border border-gray-300 px-1 py-0.5">
                        <input wire:model.live.debounce.300ms="fNama" type="text"
                               class="w-full border border-gray-300 text-[10px] px-1 py-0.5 bg-white" />
                    </td>
                    <td class="border border-gray-300 px-1 py-0.5">
                        <select wire:model.live="fJenis" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                            <option value=""></option>
                            @foreach(\App\Models\GreetingTemplate::jenisOptions() as $j)
                            <option value="{{ $j }}">{{ $j }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="border border-gray-300" colspan="2"></td>
                    <td class="border border-gray-300 px-1 py-0.5">
                        <select wire:model.live="fStatus" class="w-full border border-gray-300 text-[10px] px-0.5 py-0.5 bg-white">
                            <option value=""></option>
                            <option value="Active">Active</option>
                            <option value="Non Active">Non Active</option>
                        </select>
                    </td>
                    <td class="border border-gray-300" colspan="2"></td>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $i => $tpl)
                @php
                    $rowNo = ($templates->currentPage() - 1) * $templates->perPage() + $i + 1;
                    $isSelected = $selectedId === $tpl->id;
                @endphp
                <tr class="cursor-pointer"
                    style="{{ $isSelected ? 'background:#fff9c4;' : ($tpl->status === 'Active' ? 'background:#fff;' : 'background:#f9f9f9;') }}"
                    wire:click="selectRow({{ $tpl->id }})">
                    <td class="border border-gray-300 px-1 py-1 text-center text-gray-500">{{ $rowNo }}</td>
                    <td class="border border-gray-300 px-2 py-1 font-medium text-gray-700">{{ $tpl->nama_template }}</td>
                    <td class="border border-gray-300 px-2 py-1 text-gray-500 text-center">{{ $tpl->jenis }}</td>
                    <td class="border border-gray-300 px-2 py-1 text-center">
                        @if($tpl->cover_img)
                        <img src="{{ Storage::url($tpl->cover_img) }}" alt="cover"
                             class="w-12 h-8 object-cover mx-auto rounded border border-gray-200">
                        @endif
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-center">
                        @if($tpl->content_img)
                        <img src="{{ Storage::url($tpl->content_img) }}" alt="content"
                             class="w-12 h-8 object-cover mx-auto rounded border border-gray-200">
                        @endif
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-center">
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded
                            {{ $tpl->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500' }}">
                            {{ $tpl->status }}
                        </span>
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-center text-gray-500 text-[10px]">
                        {{ $tpl->modified_by }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-center" wire:click.stop>
                        <button wire:click="openKirim({{ $tpl->id }})"
                                class="text-[#1a5c2e] hover:underline text-[11px] font-medium block mx-auto">kirim notif</button>
                        <div class="text-gray-400 text-[10px]">{{ $tpl->kirim_count }}</div>
                        <button wire:click="openView({{ $tpl->id }})"
                                class="text-blue-600 hover:underline text-[10px] mt-0.5 block mx-auto">lihat</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="border border-gray-300 px-4 py-8 text-center text-gray-400">
                    Belum ada template. Klik "+ Input Template" untuk menambahkan.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Action Buttons ── --}}
    <div class="flex items-center gap-2 mt-2">
        <button wire:click="openAdd"
                class="flex items-center gap-1 px-3 py-1 border bg-blue-600 hover:bg-blue-700 text-white text-[11px]">
            <span class="font-bold text-base leading-none">+</span> Input Template
        </button>
        <button wire:click="{{ $selectedId ? 'openEdit('.$selectedId.')' : '' }}"
                class="flex items-center gap-1 px-3 py-1 border text-[11px]"
                style="{{ $selectedId ? 'background:#f59e0b; color:#fff; border-color:#f59e0b;' : 'background:#e5e7eb; color:#9ca3af; border-color:#d1d5db; cursor:not-allowed;' }}"
                {{ !$selectedId ? 'disabled' : '' }}>
            ✏ Edit
        </button>
        <button wire:click="{{ $selectedId ? 'delete('.$selectedId.')' : '' }}"
                @if($selectedId) wire:confirm="Hapus template ini?" @endif
                class="flex items-center gap-1 px-3 py-1 border text-[11px]"
                style="{{ $selectedId ? 'background:#dc2626; color:#fff; border-color:#dc2626;' : 'background:#e5e7eb; color:#9ca3af; border-color:#d1d5db; cursor:not-allowed;' }}"
                {{ !$selectedId ? 'disabled' : '' }}>
            🗑 Hapus
        </button>
    </div>

    {{-- ── Pagination ── --}}
    @php
        $cur  = $templates->currentPage();
        $last = $templates->lastPage();
        $nums = collect();
        for ($p = 1; $p <= $last; $p++) {
            if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
        }
        $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
        $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
        $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
    @endphp
    <div class="flex items-center justify-between mt-2 text-[11px] text-gray-600">
        <div class="flex items-center gap-1">
            @if($templates->onFirstPage())
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
            @if($templates->hasMorePages())
                <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
                <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
            @else
                <span class="{{ $pDis }}">›</span>
                <span class="{{ $pDis }}">›|</span>
            @endif
        </div>
        <span>View {{ $templates->firstItem() ?? 0 }}–{{ $templates->lastItem() ?? 0 }} of {{ $templates->total() }}</span>
    </div>

    {{-- ═══════════════════════════════════════════════
         PANEL: INPUT / EDIT TEMPLATE
    ═══════════════════════════════════════════════ --}}
    @if($panelMode === 'add' || ($panelMode === 'edit' && $selected))
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" wire:click="closePanel"></div>
        <div class="relative z-50 bg-white rounded-sm shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-2.5"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <span class="font-bold text-sm text-white uppercase tracking-wide">
                    {{ $panelMode === 'add' ? 'INPUT TEMPLATE' : 'EDIT TEMPLATE' }}
                </span>
                <button wire:click="closePanel"
                        class="w-5 h-5 rounded-full border border-white/60 flex items-center justify-center text-xs font-bold text-white hover:bg-white/20">✕</button>
            </div>

            {{-- Form --}}
            <div class="p-5 text-[12px]">
                <table class="w-full" style="border-collapse: separate; border-spacing: 0 8px;">
                    <colgroup><col style="width: 130px;"><col></colgroup>

                    <tr>
                        <td class="{{ $lbl }} text-right pr-3">Nama Template <span class="text-red-500">*</span></td>
                        <td>
                            <input wire:model="fNamaTemplate" type="text" class="{{ $inp }}" />
                            @error('fNamaTemplate')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </td>
                    </tr>

                    <tr>
                        <td class="{{ $lbl }} text-right pr-3">Jenis <span class="text-red-500">*</span></td>
                        <td>
                            <select wire:model="fJenisTemplate" class="{{ $inp }}">
                                <option value="">- Pilih Jenis -</option>
                                @foreach(\App\Models\GreetingTemplate::jenisOptions() as $j)
                                <option value="{{ $j }}">{{ $j }}</option>
                                @endforeach
                            </select>
                            @error('fJenisTemplate')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </td>
                    </tr>

                    <tr>
                        <td class="text-right pr-3 text-gray-600 align-top pt-2">ISI</td>
                        <td>
                            {{-- Simple WYSIWYG toolbar + contenteditable --}}
                            <div x-data="{
                                    init() {
                                        this.$refs.editor.innerHTML = @js($fIsi);
                                    },
                                    exec(cmd, val) {
                                        document.execCommand(cmd, false, val ?? null);
                                        this.$refs.editor.focus();
                                        this.sync();
                                    },
                                    sync() {
                                        $wire.set('fIsi', this.$refs.editor.innerHTML);
                                    }
                                }"
                                 class="border border-gray-400 rounded">
                                {{-- Toolbar --}}
                                <div class="flex items-center gap-0.5 px-2 py-1 bg-gray-100 border-b border-gray-300 flex-wrap">
                                    @foreach([
                                        ['B', 'bold', '<b>B</b>'],
                                        ['I', 'italic', '<i>I</i>'],
                                        ['U', 'underline', '<u>U</u>'],
                                    ] as [$k, $cmd, $html])
                                    <button type="button" @click="exec('{{ $cmd }}')"
                                            class="w-6 h-6 border border-gray-300 bg-white hover:bg-gray-200 text-[11px] rounded flex items-center justify-center">
                                        {!! $html !!}
                                    </button>
                                    @endforeach
                                    <div class="w-px h-5 bg-gray-300 mx-1"></div>
                                    @foreach([
                                        ['≡', 'insertUnorderedList'],
                                        ['1.', 'insertOrderedList'],
                                    ] as [$k, $cmd])
                                    <button type="button" @click="exec('{{ $cmd }}')"
                                            class="w-6 h-6 border border-gray-300 bg-white hover:bg-gray-200 text-[11px] rounded flex items-center justify-center">
                                        {{ $k }}
                                    </button>
                                    @endforeach
                                    <div class="w-px h-5 bg-gray-300 mx-1"></div>
                                    <button type="button" @click="exec('justifyLeft')" class="w-6 h-6 border border-gray-300 bg-white hover:bg-gray-200 text-[11px] rounded">⟵</button>
                                    <button type="button" @click="exec('justifyCenter')" class="w-6 h-6 border border-gray-300 bg-white hover:bg-gray-200 text-[11px] rounded">≡</button>
                                    <button type="button" @click="exec('justifyRight')" class="w-6 h-6 border border-gray-300 bg-white hover:bg-gray-200 text-[11px] rounded">⟶</button>
                                    <div class="w-px h-5 bg-gray-300 mx-1"></div>
                                    {{-- Font color --}}
                                    <label class="w-6 h-6 border border-gray-300 bg-white hover:bg-gray-200 rounded flex items-center justify-center cursor-pointer" title="Font Color">
                                        <span class="text-[11px] font-bold" style="color:#cc0000;">A</span>
                                        <input type="color" class="sr-only" @input="exec('foreColor', $event.target.value)">
                                    </label>
                                </div>

                                {{-- Editable area --}}
                                <div x-ref="editor"
                                     contenteditable="true"
                                     @input="sync()"
                                     class="min-h-[180px] p-3 text-[12px] text-gray-800 focus:outline-none leading-relaxed"
                                     style="white-space: pre-wrap;"></div>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="text-right pr-3 text-gray-600 align-top pt-2">Cover Img</td>
                        <td>
                            @if($existingCover)
                            <img src="{{ Storage::url($existingCover) }}" class="h-16 object-contain rounded border border-gray-200 mb-1">
                            @endif
                            @if($fCoverImg)
                            <img src="{{ $fCoverImg->temporaryUrl() }}" class="h-16 object-contain rounded border border-gray-200 mb-1">
                            @endif
                            <input type="file" wire:model="fCoverImg" accept="image/*"
                                   class="text-[11px] w-full file:mr-2 file:py-0.5 file:px-3 file:rounded file:border-0 file:bg-[#e8f5e9] file:text-[#1a5c2e] file:text-[10px] file:font-semibold">
                            <span class="text-[10px] text-gray-400">Recommended: 1280 × 720 px</span>
                            @error('fCoverImg')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </td>
                    </tr>

                    <tr>
                        <td class="text-right pr-3 text-gray-600 align-top pt-2">Content Img</td>
                        <td>
                            @if($existingContent)
                            <img src="{{ Storage::url($existingContent) }}" class="h-16 object-contain rounded border border-gray-200 mb-1">
                            @endif
                            @if($fContentImg)
                            <img src="{{ $fContentImg->temporaryUrl() }}" class="h-16 object-contain rounded border border-gray-200 mb-1">
                            @endif
                            <input type="file" wire:model="fContentImg" accept="image/*"
                                   class="text-[11px] w-full file:mr-2 file:py-0.5 file:px-3 file:rounded file:border-0 file:bg-[#e8f5e9] file:text-[#1a5c2e] file:text-[10px] file:font-semibold">
                            @error('fContentImg')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </td>
                    </tr>

                    @if($panelMode === 'edit')
                    <tr>
                        <td class="text-right pr-3 text-gray-600">Status</td>
                        <td>
                            <select wire:model="fStatusForm" class="border border-gray-400 px-2 py-1 text-[12px] rounded">
                                <option value="Active">Active</option>
                                <option value="Non Active">Non Active</option>
                            </select>
                        </td>
                    </tr>
                    @endif
                </table>

                <div class="flex gap-4 justify-center mt-5">
                    <button wire:click="save" wire:loading.attr="disabled"
                            class="px-10 py-1.5 border border-gray-400 bg-gray-100 text-[12px] hover:bg-gray-200 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Save</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                    <button wire:click="closePanel"
                            class="px-10 py-1.5 border border-gray-400 bg-gray-100 text-[12px] hover:bg-gray-200">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         PANEL: VIEW TEMPLATE
    ═══════════════════════════════════════════════ --}}
    @if($panelMode === 'view' && $selected)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" wire:click="closePanel"></div>
        <div class="relative z-50 bg-white rounded-sm shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-2.5"
                 style="{{ $hdr }} border-bottom: 1px solid #a0c0c8;">
                <span class="font-bold text-sm text-gray-700">{{ $selected->nama_template }}</span>
                <button wire:click="closePanel" class="w-5 h-5 rounded-full border border-gray-400 flex items-center justify-center text-xs font-bold hover:bg-gray-200">✕</button>
            </div>
            <div class="p-5">
                <div class="flex gap-3 mb-4">
                    <span class="text-[10px] px-2 py-0.5 bg-blue-100 text-blue-700 rounded font-semibold">{{ $selected->jenis }}</span>
                    <span class="text-[10px] px-2 py-0.5 rounded font-semibold
                        {{ $selected->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500' }}">
                        {{ $selected->status }}
                    </span>
                    <span class="text-[10px] text-gray-400">Terkirim: {{ $selected->kirim_count }}x</span>
                </div>

                {{-- Cover Image --}}
                @if($selected->cover_img)
                <img src="{{ Storage::url($selected->cover_img) }}"
                     alt="Cover" class="w-full rounded-lg border border-gray-200 mb-4 max-h-48 object-cover">
                @endif

                {{-- Content Image --}}
                @if($selected->content_img)
                <img src="{{ Storage::url($selected->content_img) }}"
                     alt="Content" class="w-full rounded-lg border border-gray-200 mb-4 max-h-48 object-cover">
                @endif

                {{-- ISI --}}
                @if($selected->isi)
                <div class="border border-gray-200 rounded-lg p-4 text-[13px] text-gray-700 leading-relaxed bg-gray-50">
                    {!! $selected->isi !!}
                </div>
                @endif

                <div class="mt-4 flex gap-3 justify-end">
                    <button wire:click="openEdit({{ $selected->id }})"
                            class="px-4 py-1.5 border border-gray-400 bg-gray-100 text-[12px] hover:bg-gray-200">
                        ✏ Edit
                    </button>
                    <button wire:click="openKirim({{ $selected->id }})"
                            class="px-4 py-1.5 border bg-blue-600 text-white hover:bg-blue-700 text-[12px]">
                        📢 Kirim Notifikasi
                    </button>
                    <button wire:click="delete({{ $selected->id }})"
                            wire:confirm="Hapus template '{{ $selected->nama_template }}'?"
                            class="px-4 py-1.5 border border-red-300 bg-red-50 text-red-600 text-[12px] hover:bg-red-100">
                        🗑 Hapus
                    </button>
                    <button wire:click="closePanel"
                            class="px-4 py-1.5 border border-gray-400 bg-gray-100 text-[12px] hover:bg-gray-200">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         PANEL: KIRIM NOTIFIKASI
    ═══════════════════════════════════════════════ --}}
    @if($panelMode === 'kirim' && $selected)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" wire:click="closePanel"></div>
        <div class="relative z-50 bg-white rounded-sm shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-5 py-2.5"
                 style="{{ $hdr }} border-bottom: 1px solid #a0c0c8;">
                <span class="font-bold text-sm text-gray-700 uppercase">Kirim Notifikasi</span>
                <button wire:click="closePanel" class="w-5 h-5 rounded-full border border-gray-400 flex items-center justify-center text-xs font-bold hover:bg-gray-200">✕</button>
            </div>
            <div class="p-5 text-[12px]">
                @if($kirimOk)
                <div class="px-4 py-3 bg-green-50 border border-green-200 rounded text-green-700 text-sm mb-4">
                    ✓ {{ $kirimMsg }}
                </div>
                <button wire:click="closePanel"
                        class="w-full py-2 border border-gray-400 bg-gray-100 text-[12px] hover:bg-gray-200">
                    Tutup
                </button>
                @else
                <div class="mb-4 space-y-2">
                    <div class="flex items-center gap-2">
                        @if($selected->cover_img)
                        <img src="{{ Storage::url($selected->cover_img) }}"
                             class="w-16 h-10 object-cover rounded border border-gray-200">
                        @endif
                        <div>
                            <p class="font-semibold text-gray-800">{{ $selected->nama_template }}</p>
                            <p class="text-[10px] text-gray-400">{{ $selected->jenis }}</p>
                        </div>
                    </div>

                    <div class="px-3 py-2.5 bg-amber-50 border border-amber-200 rounded text-amber-800 text-xs">
                        Notifikasi ini akan dikirim ke <strong>{{ $tenantCount }} akun penghuni</strong>.
                        <br>Sebelumnya sudah terkirim <strong>{{ $selected->kirim_count }}x</strong>.
                    </div>

                    @if($selected->isi)
                    <div class="border border-gray-200 rounded p-3 bg-gray-50 text-gray-700 text-[11px] max-h-24 overflow-y-auto">
                        {!! $selected->isi !!}
                    </div>
                    @endif
                </div>

                <div class="flex gap-3">
                    <button wire:click="kirimNotif"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2 bg-blue-600 text-white text-[12px] font-semibold rounded hover:bg-[#154d26] disabled:opacity-60">
                        <span wire:loading.remove wire:target="kirimNotif">📢 Kirim Sekarang</span>
                        <span wire:loading wire:target="kirimNotif">Mengirim...</span>
                    </button>
                    <button wire:click="closePanel"
                            class="px-4 py-2 border border-gray-400 bg-gray-100 text-[12px] hover:bg-gray-200">
                        Batal
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>


