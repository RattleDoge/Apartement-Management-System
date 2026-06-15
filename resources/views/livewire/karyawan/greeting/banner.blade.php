<?php

use App\Models\Banner;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.karyawan')] class extends Component
{
    use WithFileUploads, WithPagination;

    public $image       = null;
    public string $caption = '';
    public string $flash   = '';

    private function canManage(): bool
    {
        return auth()->user()->role !== 'tenant';
    }

    public function save(): void
    {
        if (! $this->canManage()) {
            $this->flash = 'error:Anda tidak memiliki akses untuk upload banner.';
            return;
        }

        $this->validate([
            'image'   => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'caption' => 'nullable|string|max:255',
        ], [
            'image.required' => 'File gambar wajib dipilih.',
            'image.image'    => 'File harus berupa gambar.',
            'image.mimes'    => 'Format gambar harus JPG, PNG, atau WEBP.',
            'image.max'      => 'Ukuran gambar maksimal 5 MB.',
        ]);

        $path = $this->image->store('banners', 'public');

        Banner::create([
            'image_path'  => $path,
            'caption'     => trim($this->caption) ?: null,
            'is_active'   => true,
            'uploaded_by' => auth()->user()?->name,
        ]);

        $this->image   = null;
        $this->caption = '';
        $this->flash   = 'ok:Banner berhasil diupload dan diaktifkan.';
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        if (! $this->canManage()) return;
        $banner = Banner::findOrFail($id);
        $banner->update(['is_active' => ! $banner->is_active]);
    }

    public function delete(int $id): void
    {
        if (! $this->canManage()) return;
        $banner = Banner::findOrFail($id);
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($banner->image_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($banner->image_path);
        }
        $banner->delete();
        $this->flash = 'ok:Banner berhasil dihapus.';
    }

    public function with(): array
    {
        return [
            'banners'    => Banner::latest()->paginate(10),
            'canManage'  => $this->canManage(),
        ];
    }
};
?>

<div class="px-4 py-4">
    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            PENGUMUMAN / BANNER TENANT
        </div>
    </div>

    {{-- Flash --}}
    @if($flash)
    @php [$type, $msg] = explode(':', $flash, 2); @endphp
    <div class="mb-3 px-4 py-2 rounded text-sm border
                {{ $type === 'ok' ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-700' }}">
        {{ $msg }}
    </div>
    @endif

    @if($canManage)
    {{-- Upload Form --}}
    <div class="border border-gray-300 shadow-sm mb-5" style="max-width:640px;">
        <div class="px-3 py-1.5 font-bold text-sm text-white"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            Upload Banner Baru
        </div>
        <div class="bg-white px-5 py-4">
            <form wire:submit="save">
                <div class="mb-3">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        File Gambar <span class="text-red-500">*</span>
                        <span class="font-normal text-gray-400">(JPG, PNG, WEBP — maks 5 MB)</span>
                    </label>
                    <input wire:model="image" type="file" accept="image/jpeg,image/png,image/webp"
                           class="w-full text-xs text-gray-600
                                  file:mr-3 file:py-1 file:px-3 file:border file:border-gray-400
                                  file:bg-gray-100 file:text-gray-700 file:text-xs file:cursor-pointer
                                  hover:file:bg-gray-200">
                    @error('image') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror

                    {{-- Preview --}}
                    @if($image)
                    <div class="mt-2">
                        <img src="{{ $image->temporaryUrl() }}" alt="Preview"
                             class="max-h-40 rounded border border-gray-300 object-contain">
                    </div>
                    @endif
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Keterangan / Caption <span class="text-gray-400 font-normal">(opsional)</span>
                    </label>
                    <input wire:model="caption" type="text" maxlength="255"
                           placeholder="Contoh: Pemberitahuan Pemeliharaan Lift 27 Mei 2026"
                           class="w-full border border-gray-400 px-2 py-1 text-sm focus:outline-none focus:border-[#5b9aaa]">
                </div>
                <div class="flex gap-3">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="px-6 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">⬆ Upload &amp; Aktifkan</span>
                        <span wire:loading wire:target="save">Uploading...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @else
    <div class="mb-4 px-4 py-2.5 bg-yellow-50 border border-yellow-300 text-yellow-800 text-xs rounded">
        Hanya Apartement Manager dan Customer Service yang dapat mengelola banner.
    </div>
    @endif

    {{-- Banner List --}}
    <div class="border border-gray-300 overflow-x-auto" style="max-width:900px; font-size:12px;">
        <table class="border-collapse w-full">
            <thead>
                <tr style="background: linear-gradient(to bottom, #dbeafe, #eff6ff); color:#1e40af;">
                    <th class="border border-blue-200 px-2 py-1 text-center w-8">#</th>
                    <th class="border border-blue-200 px-2 py-1 text-left w-48">Preview</th>
                    <th class="border border-blue-200 px-2 py-1 text-left">Keterangan</th>
                    <th class="border border-blue-200 px-2 py-1 text-center w-24">Status</th>
                    <th class="border border-blue-200 px-2 py-1 text-left w-28">Uploaded By</th>
                    <th class="border border-blue-200 px-2 py-1 text-left w-28">Tanggal</th>
                    @if($canManage)
                    <th class="border border-blue-200 px-2 py-1 text-center w-28">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($banners as $i => $b)
                <tr class="{{ $b->is_active ? 'bg-green-50' : 'bg-white' }}">
                    <td class="border border-gray-300 px-2 py-1 text-center text-gray-500">
                        {{ ($banners->currentPage() - 1) * $banners->perPage() + $i + 1 }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1">
                        <img src="{{ asset('storage/' . $b->image_path) }}" alt="banner"
                             class="h-16 w-40 object-cover rounded border border-gray-200">
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-gray-700">
                        {{ $b->caption ?? '—' }}
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-center">
                        @if($b->is_active)
                            <span class="inline-block px-2 py-0.5 bg-green-600 text-white text-[10px] font-bold rounded">AKTIF</span>
                        @else
                            <span class="inline-block px-2 py-0.5 bg-gray-300 text-gray-600 text-[10px] rounded">nonaktif</span>
                        @endif
                    </td>
                    <td class="border border-gray-300 px-2 py-1 text-gray-600">{{ $b->uploaded_by ?? '—' }}</td>
                    <td class="border border-gray-300 px-2 py-1 text-gray-500 whitespace-nowrap">
                        {{ $b->created_at->format('d/m/Y H:i') }}
                    </td>
                    @if($canManage)
                    <td class="border border-gray-300 px-2 py-1 text-center">
                        <div class="flex justify-center gap-1">
                            <button wire:click="toggleActive({{ $b->id }})"
                                    style="padding:2px 8px;font-size:10px;font-weight:600;border-radius:4px;border:none;cursor:pointer;{{ $b->is_active ? 'background:#f59e0b;color:#fff;' : 'background:#3b82f6;color:#fff;' }}">
                                {{ $b->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                            <button wire:click="delete({{ $b->id }})"
                                    wire:confirm="Hapus banner ini?"
                                    class="px-2 py-0.5 text-[10px] font-semibold rounded bg-red-600 hover:bg-red-700 text-white">
                                Hapus
                            </button>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canManage ? 7 : 6 }}" class="border border-gray-300 px-4 py-6 text-center text-gray-400">
                        Belum ada banner yang diupload.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($banners->hasPages())
    @php
        $cur  = $banners->currentPage();
        $last = $banners->lastPage();
        $nums = collect();
        for ($p = 1; $p <= $last; $p++) {
            if ($p === 1 || $p === $last || abs($p - $cur) <= 2) { $nums->push($p); }
        }
        $pBtn = 'min-w-[28px] px-2 py-0.5 border border-gray-400 rounded bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 text-[11px] text-center leading-5';
        $pDis = 'min-w-[28px] px-2 py-0.5 border border-gray-200 rounded bg-gray-50 text-gray-300 cursor-not-allowed text-[11px] text-center leading-5';
        $pAct = 'min-w-[28px] px-2 py-0.5 border border-blue-600 rounded bg-blue-600 text-white font-bold text-[11px] text-center leading-5';
    @endphp
    <div class="flex items-center gap-1 mt-2">
        @if($banners->onFirstPage())
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
        @if($banners->hasMorePages())
            <button wire:click="nextPage" class="{{ $pBtn }}">›</button>
            <button wire:click="setPage({{ $last }})" class="{{ $pBtn }}">›|</button>
        @else
            <span class="{{ $pDis }}">›</span>
            <span class="{{ $pDis }}">›|</span>
        @endif
    </div>
    @endif
</div>
