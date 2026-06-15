<?php

use App\Models\EmergencyContact;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.karyawan')] class extends Component {

    public bool $showPanel = false;
    public ?int $editId    = null;

    public string $fNama      = '';
    public string $fAlamat    = '';
    public string $fKategori  = 'Rumah Sakit';
    public string $fTelp      = '';
    public string $fNoWa      = '';

    public string $savedMsg = '';

    private function canManage(): bool
    {
        return auth()->user()->role !== 'tenant';
    }

    public function openAdd(): void
    {
        if (! $this->canManage()) return;
        $this->resetForm();
        $this->showPanel = true;
    }

    public function openEdit(int $id): void
    {
        if (! $this->canManage()) return;
        $e = EmergencyContact::findOrFail($id);
        $this->editId     = $id;
        $this->fNama      = $e->nama;
        $this->fAlamat    = $e->alamat ?? '';
        $this->fKategori  = $e->kategori;
        $this->fTelp      = $e->telp ?? '';
        $this->fNoWa      = $e->no_wa ?? '';
        $this->showPanel  = true;
    }

    public function save(): void
    {
        if (! $this->canManage()) return;

        $this->validate([
            'fNama'     => 'required|string|max:255',
            'fAlamat'   => 'nullable|string',
            'fKategori' => 'required|string',
            'fTelp'     => 'nullable|string|max:30',
            'fNoWa'     => 'nullable|string|max:30',
        ]);

        $data = [
            'nama'     => $this->fNama,
            'alamat'   => $this->fAlamat ?: null,
            'kategori' => $this->fKategori,
            'telp'     => $this->fTelp ?: null,
            'no_wa'    => $this->fNoWa ?: null,
        ];

        if ($this->editId) {
            EmergencyContact::findOrFail($this->editId)->update($data);
            $this->savedMsg = 'Data berhasil diperbarui.';
        } else {
            EmergencyContact::create($data);
            $this->savedMsg = 'Data berhasil ditambahkan.';
        }

        $this->showPanel = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        if (! $this->canManage()) return;
        EmergencyContact::findOrFail($id)->delete();
        $this->savedMsg = 'Data berhasil dihapus.';
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editId     = null;
        $this->fNama      = '';
        $this->fAlamat    = '';
        $this->fKategori  = 'Rumah Sakit';
        $this->fTelp      = '';
        $this->fNoWa      = '';
        $this->savedMsg   = '';
    }

    public function with(): array
    {
        $contacts   = EmergencyContact::orderBy('kategori')->orderBy('nama')->get();
        $canManage  = $this->canManage();
        $kategoriOptions = EmergencyContact::kategoriOptions();
        return compact('contacts', 'canManage', 'kategoriOptions');
    }
}
?>

<div class="p-5">

    {{-- Header --}}
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide flex items-center justify-between"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            <span>EMERGENCY LIST</span>
            @if($savedMsg)
            <span class="text-xs font-normal bg-white/20 px-2 py-0.5 rounded">{{ $savedMsg }}</span>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-blue-200 rounded-lg overflow-x-auto shadow-sm">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-8">#</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold min-w-40">NAME</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold min-w-56">ALAMAT</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-32">KATEGORI</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-28">TELP</th>
                    <th class="border border-blue-200 px-3 py-2 text-left font-semibold w-28">NO. WA</th>
                    @if($canManage)
                    <th class="border border-blue-200 px-3 py-2 text-center font-semibold w-20">AKSI</th>
                    @endif
                </tr>
                {{-- Filter row --}}
                <tr class="bg-white">
                    <td class="border border-gray-200 px-1 py-0.5"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    <td class="border border-gray-200 px-1 py-0.5"><input type="text" disabled class="w-full text-xs border border-gray-200 rounded px-1 py-0.5 bg-gray-50"></td>
                    @if($canManage)<td class="border border-gray-200"></td>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $i => $c)
                @php
                    $badge = match($c->kategori) {
                        'Rumah Sakit','Klinik'       => 'bg-red-100 text-red-700',
                        'Kantor Polisi'               => 'bg-blue-100 text-blue-700',
                        'Pemadam Kebakaran'           => 'bg-orange-100 text-orange-700',
                        'Ambulans'                    => 'bg-pink-100 text-pink-700',
                        'PLN'                         => 'bg-yellow-100 text-yellow-700',
                        'PDAM'                        => 'bg-cyan-100 text-cyan-700',
                        default                       => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <tr class="border-b border-gray-100 hover:bg-[#f0f8f0] transition-colors">
                    <td class="border border-gray-100 px-3 py-1.5 text-center text-gray-500">{{ $i + 1 }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 font-semibold text-[#1a5c2e]">{{ $c->nama }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 text-gray-600 leading-snug">{{ $c->alamat ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-1.5">
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $badge }}">
                            {{ $c->kategori }}
                        </span>
                    </td>
                    <td class="border border-gray-100 px-3 py-1.5 font-mono">{{ $c->telp ?? '—' }}</td>
                    <td class="border border-gray-100 px-3 py-1.5 font-mono">{{ $c->no_wa ?? '—' }}</td>
                    @if($canManage)
                    <td class="border border-gray-100 px-3 py-1.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button wire:click="openEdit({{ $c->id }})"
                                    style="padding:2px 8px;font-size:10px;font-weight:600;border-radius:4px;border:none;cursor:pointer;background:#f59e0b;color:#fff;">
                                Edit
                            </button>
                            <button wire:click="delete({{ $c->id }})"
                                    wire:confirm="Hapus '{{ $c->nama }}'?"
                                    class="px-2 py-0.5 text-[10px] font-semibold rounded bg-red-600 hover:bg-red-700 text-white">
                                Hapus
                            </button>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canManage ? 7 : 6 }}" class="px-4 py-10 text-center text-xs text-gray-400">
                        Belum ada data kontak darurat.
                        @if($canManage) Klik "+ Input Data" untuk menambahkan. @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Bottom action bar --}}
    <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-3">
        <div class="flex items-center gap-2">
            @if($canManage)
            <button wire:click="openAdd"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded flex items-center gap-1">
                + Input Data
            </button>
            @endif
        </div>
        <span class="text-xs text-gray-400">Total: {{ $contacts->count() }} kontak</span>
    </div>

    {{-- ── Modal Form ── --}}
    @if($showPanel)
    <div class="fixed inset-0 z-50 flex items-center justify-center"
         style="background: rgba(0,0,0,0.45);">
        <div class="bg-white rounded-lg shadow-2xl overflow-y-auto" style="width:100%;max-width:480px;max-height:90vh;margin:0 16px;">

            <div class="flex items-center justify-between px-5 py-3 border-b"
                 style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
                <h3 class="text-sm font-bold text-white uppercase tracking-wide">
                    {{ $editId ? 'Edit Data' : 'Input Data' }}
                </h3>
                <button wire:click="closePanel" class="text-white/70 hover:text-white text-lg leading-none">✕</button>
            </div>

            <form wire:submit="save" class="px-5 py-4 space-y-3">

                {{-- Nama --}}
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1 uppercase tracking-wide">Nama <span class="text-red-400">*</span></label>
                    <input wire:model="fNama" type="text"
                           class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"
                           placeholder="Nama instansi / kontak">
                    @error('fNama') <p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p> @enderror
                </div>

                {{-- Alamat --}}
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1 uppercase tracking-wide">Alamat</label>
                    <textarea wire:model="fAlamat" rows="2"
                              class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition resize-none"
                              placeholder="Alamat lengkap"></textarea>
                </div>

                {{-- Kategori --}}
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1 uppercase tracking-wide">Kategori <span class="text-red-400">*</span></label>
                    <select wire:model="fKategori"
                            class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white transition">
                        @foreach($kategoriOptions as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Telp + No.WA --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1 uppercase tracking-wide">Telp</label>
                        <input wire:model="fTelp" type="text"
                               class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"
                               placeholder="021-xxxxxxx">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1 uppercase tracking-wide">No. WhatsApp</label>
                        <input wire:model="fNoWa" type="text"
                               class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"
                               placeholder="08xxxxxxxxxx">
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" wire:click="closePanel"
                            class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-md font-semibold transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md font-semibold transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
