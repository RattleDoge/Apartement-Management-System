<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Faq;

new #[Layout('layouts.karyawan')] class extends Component {

    public bool   $showPanel = false;
    public ?int   $editId    = null;
    public string $fPertanyaan = '';
    public string $fJawaban    = '';
    public string $fKategori   = '';
    public int    $fUrutan     = 0;
    public bool   $fIsActive   = true;
    public string $savedMsg    = '';

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
        $f = Faq::findOrFail($id);
        $this->editId       = $id;
        $this->fPertanyaan  = $f->pertanyaan;
        $this->fJawaban     = $f->jawaban;
        $this->fKategori    = $f->kategori ?? '';
        $this->fUrutan      = $f->urutan;
        $this->fIsActive    = $f->is_active;
        $this->showPanel    = true;
    }

    public function save(): void
    {
        $this->validate([
            'fPertanyaan' => 'required|string',
            'fJawaban'    => 'required|string',
            'fKategori'   => 'nullable|string|max:50',
        ]);
        $data = [
            'pertanyaan' => $this->fPertanyaan,
            'jawaban'    => $this->fJawaban,
            'kategori'   => $this->fKategori ?: null,
            'urutan'     => $this->fUrutan,
            'is_active'  => $this->fIsActive,
        ];
        if ($this->editId) {
            Faq::findOrFail($this->editId)->update($data);
            $this->savedMsg = 'FAQ diperbarui.';
        } else {
            Faq::create($data);
            $this->savedMsg = 'FAQ ditambahkan.';
        }
        $this->showPanel = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $f = Faq::findOrFail($id);
        $f->update(['is_active' => !$f->is_active]);
    }

    public function delete(int $id): void
    {
        Faq::findOrFail($id)->delete();
        $this->savedMsg = 'FAQ dihapus.';
    }

    public function closePanel(): void { $this->showPanel = false; $this->resetForm(); }

    private function resetForm(): void
    {
        $this->editId = null; $this->fPertanyaan = ''; $this->fJawaban = '';
        $this->fKategori = ''; $this->fUrutan = 0; $this->fIsActive = true; $this->savedMsg = '';
    }

    public function with(): array
    {
        $faqs            = Faq::orderBy('urutan')->orderBy('kategori')->get();
        $canManage       = $this->canManage();
        $kategoriOptions = Faq::kategoriOptions();
        return compact('faqs', 'canManage', 'kategoriOptions');
    }
}
?>

<div class="p-5">
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>KELOLA FAQ</span>
            @if($savedMsg)
            <span class="text-xs font-normal bg-white/20 px-2 py-0.5 rounded">{{ $savedMsg }}</span>
            @endif
        </div>
    </div>

    <div class="bg-white border border-blue-200 rounded-lg overflow-x-auto shadow-sm">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-8">#</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold">PERTANYAAN</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-28">KATEGORI</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-16">URUTAN</th>
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">STATUS</th>
                    @if($canManage)<th class="border border-blue-200 px-3 py-2 text-center font-semibold w-24">AKSI</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($faqs as $i => $f)
                <tr class="border-b border-gray-100 hover:bg-[#f0f8f0] {{ !$f->is_active ? 'opacity-50' : '' }}">
                    <td class="border border-gray-100 px-3 py-2 text-center text-gray-500">{{ $i+1 }}</td>
                    <td class="border border-gray-100 px-3 py-2 text-gray-800">{{ \Str::limit($f->pertanyaan, 80) }}</td>
                    <td class="border border-gray-100 px-3 py-2 text-gray-600">{{ $f->kategori ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-2 text-center">{{ $f->urutan }}</td>
                    <td class="border border-gray-100 px-3 py-2 text-center">
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold
                            {{ $f->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $f->is_active ? 'Aktif' : 'Draft' }}
                        </span>
                    </td>
                    @if($canManage)
                    <td class="border border-gray-100 px-3 py-2 text-center">
                        <div class="flex justify-center gap-1">
                            <button wire:click="openEdit({{ $f->id }})" class="px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-500 hover:bg-amber-600 text-white">Edit</button>
                            <button wire:click="toggleActive({{ $f->id }})" class="px-2 py-0.5 text-[10px] font-semibold rounded bg-blue-500 hover:bg-blue-600 text-white">
                                {{ $f->is_active ? 'Draft' : 'Aktif' }}
                            </button>
                            <button wire:click="delete({{ $f->id }})" wire:confirm="Hapus FAQ ini?" class="px-2 py-0.5 text-[10px] font-semibold rounded bg-red-600 hover:bg-red-700 text-white">Hapus</button>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $canManage ? 6 : 5 }}" class="px-4 py-10 text-center text-gray-400">Belum ada FAQ.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($canManage)
    <div class="mt-3 flex items-center gap-2">
        <button wire:click="openAdd" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded flex items-center gap-1">
            + Tambah FAQ
        </button>
    </div>
    @endif

    @if($showPanel)
    <div class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.45);">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-3 border-b"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <h3 class="text-sm font-bold text-white uppercase">{{ $editId ? 'Edit FAQ' : 'Tambah FAQ' }}</h3>
                <button wire:click="closePanel" class="text-white/70 hover:text-white text-lg leading-none">✕</button>
            </div>
            <form wire:submit="save" class="px-5 py-4 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Pertanyaan</label>
                    <textarea wire:model="fPertanyaan" rows="2" class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] resize-none"></textarea>
                    @error('fPertanyaan') <p class="text-red-500 text-[10px]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Jawaban</label>
                    <textarea wire:model="fJawaban" rows="4" class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] resize-none"></textarea>
                    @error('fJawaban') <p class="text-red-500 text-[10px]">{{ $message }}</p> @enderror
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
                        <label class="block text-xs font-medium text-gray-600 mb-1">Urutan</label>
                        <input wire:model="fUrutan" type="number" min="0" class="w-full text-sm border border-gray-300 rounded px-3 py-1.5 focus:outline-none">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs">
                    <input wire:model="fIsActive" type="checkbox" class="accent-[#1a5c2e]"> Tampilkan (Aktif)
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
