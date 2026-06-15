<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.karyawan')] class extends Component {

    use WithFileUploads;

    public bool   $showPanel    = false;
    public ?int   $editId       = null;
    public string $fJudul       = '';
    public string $fDeskripsi   = '';
    public string $fKategori    = '';
    public bool   $fIsActive    = true;
    public        $fFile        = null;
    public string $savedMsg     = '';
    public string $filterKat    = '';

    private function canManage(): bool
    {
        return auth()->user()->role !== 'tenant';
    }

    public function openAdd(): void
    {
        $this->resetForm(); $this->showPanel = true;
    }

    public function openEdit(int $id): void
    {
        $d = Document::findOrFail($id);
        $this->editId      = $id;
        $this->fJudul      = $d->judul;
        $this->fDeskripsi  = $d->deskripsi ?? '';
        $this->fKategori   = $d->kategori ?? '';
        $this->fIsActive   = $d->is_active;
        $this->showPanel   = true;
    }

    public function save(): void
    {
        if (! $this->canManage()) return;

        $rules = [
            'fJudul'    => 'required|string|max:200',
            'fKategori' => 'nullable|string|max:50',
            'fDeskripsi'=> 'nullable|string|max:500',
        ];
        if (! $this->editId) {
            $rules['fFile'] = 'required|file|max:10240';
        } else {
            $rules['fFile'] = 'nullable|file|max:10240';
        }

        $this->validate($rules);

        $data = [
            'judul'       => $this->fJudul,
            'deskripsi'   => $this->fDeskripsi ?: null,
            'kategori'    => $this->fKategori ?: null,
            'is_active'   => $this->fIsActive,
            'uploaded_by' => auth()->user()->name,
        ];

        if ($this->fFile) {
            $data['file_path'] = $this->fFile->store('documents', 'public');
        }

        if ($this->editId) {
            Document::findOrFail($this->editId)->update($data);
            $this->savedMsg = 'Dokumen diperbarui.';
        } else {
            Document::create($data);
            $this->savedMsg = 'Dokumen ditambahkan.';
        }

        $this->showPanel = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        if (! $this->canManage()) return;
        $d = Document::findOrFail($id);
        $d->update(['is_active' => !$d->is_active]);
    }

    public function delete(int $id): void
    {
        if (! $this->canManage()) return;
        $d = Document::findOrFail($id);
        if ($d->file_path) {
            Storage::disk('public')->delete($d->file_path);
        }
        $d->delete();
        $this->savedMsg = 'Dokumen dihapus.';
    }

    public function closePanel(): void { $this->showPanel = false; $this->resetForm(); }

    private function resetForm(): void
    {
        $this->editId = null; $this->fJudul = ''; $this->fDeskripsi = '';
        $this->fKategori = ''; $this->fIsActive = true; $this->fFile = null;
    }

    public function with(): array
    {
        $canManage       = $this->canManage();
        $kategoriOptions = Document::kategoriOptions();
        $documents       = Document::when($this->filterKat, fn($q) => $q->where('kategori', $this->filterKat))
            ->orderByDesc('created_at')->get();

        return compact('canManage', 'kategoriOptions', 'documents');
    }
}
?>

<div class="p-5">
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>KELOLA DOKUMEN</span>
            @if($savedMsg)
            <span class="text-xs font-normal bg-white/20 px-2 py-0.5 rounded">{{ $savedMsg }}</span>
            @endif
        </div>
    </div>

    {{-- Filter --}}
    <div class="flex flex-wrap gap-2 mb-3">
        <button wire:click="$set('filterKat', '')"
                class="px-3 py-1 text-xs rounded-full border font-medium transition-colors
                       {{ $filterKat === '' ? 'bg-blue-600 text-white border-[#1a5c2e]' : 'bg-white text-gray-600 border-gray-200' }}">
            Semua
        </button>
        @foreach($kategoriOptions as $k)
        <button wire:click="$set('filterKat', '{{ $k }}')"
                class="px-3 py-1 text-xs rounded-full border font-medium transition-colors
                       {{ $filterKat === $k ? 'bg-blue-600 text-white border-[#1a5c2e]' : 'bg-white text-gray-600 border-gray-200' }}">
            {{ $k }}
        </button>
        @endforeach
    </div>

    <div class="bg-white border border-blue-200 rounded-lg overflow-x-auto shadow-sm">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-8">#</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">JUDUL</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-28">KATEGORI</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-28">UPLOAD OLEH</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">STATUS</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-16">FILE</th>
                    @if($canManage)<th class="border border-blue-200 px-3 py-2 text-center font-semibold w-28">AKSI</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $i => $d)
                <tr class="border-b border-gray-100 hover:bg-[#f0f8f0] {{ !$d->is_active ? 'opacity-50' : '' }}">
                    <td class="border border-gray-100 px-3 py-2 text-center text-gray-500">{{ $i+1 }}</td>
                    <td class="border border-gray-100 px-3 py-2">
                        <p class="font-medium text-gray-800">{{ $d->judul }}</p>
                        @if($d->deskripsi)
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ \Str::limit($d->deskripsi, 60) }}</p>
                        @endif
                    </td>
                    <td class="border border-gray-100 px-3 py-2 text-gray-600">{{ $d->kategori ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-2 text-gray-500">{{ $d->uploaded_by ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-2 text-center">
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold
                            {{ $d->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $d->is_active ? 'Aktif' : 'Draft' }}
                        </span>
                    </td>
                    <td class="border border-gray-100 px-3 py-2 text-center">
                        @if($d->file_path)
                        <a href="{{ Storage::url($d->file_path) }}" target="_blank"
                           class="text-[10px] text-blue-600 hover:underline">Unduh</a>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    @if($canManage)
                    <td class="border border-gray-100 px-3 py-2 text-center">
                        <div class="flex justify-center gap-1">
                            <button wire:click="openEdit({{ $d->id }})" class="px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-500 hover:bg-amber-600 text-white">Edit</button>
                            <button wire:click="toggleActive({{ $d->id }})" class="px-2 py-0.5 text-[10px] font-semibold rounded bg-blue-500 hover:bg-blue-600 text-white">
                                {{ $d->is_active ? 'Draft' : 'Aktif' }}
                            </button>
                            <button wire:click="delete({{ $d->id }})" wire:confirm="Hapus dokumen ini?" class="px-2 py-0.5 text-[10px] font-semibold rounded bg-red-600 hover:bg-red-700 text-white">Hapus</button>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $canManage ? 7 : 6 }}" class="px-4 py-10 text-center text-gray-400">Belum ada dokumen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($canManage)
    <div class="mt-3 flex items-center gap-2">
        <button wire:click="openAdd" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded flex items-center gap-1">
            + Upload Dokumen
        </button>
    </div>
    @endif

    @if($showPanel)
    <div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-3 border-b"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <h3 class="text-sm font-bold text-white uppercase">{{ $editId ? 'Edit Dokumen' : 'Upload Dokumen' }}</h3>
                <button wire:click="closePanel" class="text-white/70 hover:text-white text-lg leading-none">✕</button>
            </div>
            <form wire:submit="save" class="px-5 py-4 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Judul Dokumen</label>
                    <input wire:model="fJudul" type="text" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                    @error('fJudul') <p class="text-red-500 text-[10px]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                    <textarea wire:model="fDeskripsi" rows="2" class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kategori</label>
                        <select wire:model="fKategori" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 bg-white focus:outline-none">
                            <option value="">— Pilih —</option>
                            @foreach($kategoriOptions as $k)<option value="{{ $k }}">{{ $k }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">File (PDF/DOC/Excel)</label>
                        <input wire:model="fFile" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx"
                               class="w-full text-xs border border-gray-300 rounded px-2 py-1.5 focus:outline-none">
                        @error('fFile') <p class="text-red-500 text-[10px]">{{ $message }}</p> @enderror
                        @if($editId)
                        <p class="text-[10px] text-gray-400 mt-0.5">Kosongkan jika tidak ganti file.</p>
                        @endif
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs">
                    <input wire:model="fIsActive" type="checkbox" class="accent-[#1a5c2e]"> Tampilkan ke Tenant (Aktif)
                </label>
                <div class="flex justify-center gap-3 pt-2 border-t border-gray-100">
                    <button type="submit" class="px-8 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded font-semibold">💾 Simpan</button>
                    <button type="button" wire:click="closePanel" class="px-8 py-2 bg-gray-400 hover:bg-gray-500 text-white text-sm rounded font-semibold">✕ Batal</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

