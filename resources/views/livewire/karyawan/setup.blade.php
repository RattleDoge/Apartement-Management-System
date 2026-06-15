<?php

use App\Models\AppSetting;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.karyawan')] class extends Component
{
    // Profil
    public string $appNamaApartemen = '';
    public string $appAlamat        = '';
    public string $appTelp          = '';
    public string $appEmail         = '';
    public string $appWa            = '';

    // Tarif
    public string $tarifIplPerM2    = '';
    public string $tarifListrik1300 = '';
    public string $tarifListrik2200 = '';
    public string $tarifListrik3500 = '';
    public string $tarifAirM3       = '';

    // Denda
    public string $dendaPersen      = '';
    public string $dendaBatasHari   = '';

    // WO Eskalasi
    public string $woEskalasiMenit1 = '';
    public string $woEskalasiMenit2 = '';

    public string $savedMsg         = '';
    public string $activeTab        = 'profil';

    public function mount(): void
    {
        $this->appNamaApartemen  = AppSetting::get('app_nama_apartemen', 'Madison Park');
        $this->appAlamat         = AppSetting::get('app_alamat', '');
        $this->appTelp           = AppSetting::get('app_telp', '');
        $this->appEmail          = AppSetting::get('app_email', '');
        $this->appWa             = AppSetting::get('app_wa', '');
        $this->tarifIplPerM2     = AppSetting::get('tarif_ipl_per_m2', '0');
        $this->tarifListrik1300  = AppSetting::get('tarif_listrik_1300', '1444.70');
        $this->tarifListrik2200  = AppSetting::get('tarif_listrik_2200', '1444.70');
        $this->tarifListrik3500  = AppSetting::get('tarif_listrik_3500', '1699.53');
        $this->tarifAirM3        = AppSetting::get('tarif_air_m3', '0');
        $this->dendaPersen       = AppSetting::get('denda_persen', '2');
        $this->dendaBatasHari    = AppSetting::get('denda_batas_hari', '20');
        $this->woEskalasiMenit1  = AppSetting::get('wo_eskalasi_menit_1', '15');
        $this->woEskalasiMenit2  = AppSetting::get('wo_eskalasi_menit_2', '60');
    }

    public function saveProfil(): void
    {
        $this->validate([
            'appNamaApartemen' => 'required|string|max:100',
        ], [
            'appNamaApartemen.required' => 'Nama Apartemen wajib diisi.',
        ]);

        AppSetting::set('app_nama_apartemen', $this->appNamaApartemen);
        AppSetting::set('app_alamat',         $this->appAlamat);
        AppSetting::set('app_telp',           $this->appTelp);
        AppSetting::set('app_email',          $this->appEmail);
        AppSetting::set('app_wa',             $this->appWa);

        $this->savedMsg = 'profil';
    }

    public function saveTarif(): void
    {
        $this->validate([
            'tarifIplPerM2'    => 'required|numeric|min:0',
            'tarifListrik1300' => 'required|numeric|min:0',
            'tarifListrik2200' => 'required|numeric|min:0',
            'tarifListrik3500' => 'required|numeric|min:0',
            'tarifAirM3'       => 'required|numeric|min:0',
        ]);

        AppSetting::set('tarif_ipl_per_m2',    $this->tarifIplPerM2);
        AppSetting::set('tarif_listrik_1300',   $this->tarifListrik1300);
        AppSetting::set('tarif_listrik_2200',   $this->tarifListrik2200);
        AppSetting::set('tarif_listrik_3500',   $this->tarifListrik3500);
        AppSetting::set('tarif_air_m3',         $this->tarifAirM3);

        $this->savedMsg = 'tarif';
    }

    public function saveDenda(): void
    {
        $this->validate([
            'dendaPersen'    => 'required|numeric|min:0|max:100',
            'dendaBatasHari' => 'required|integer|min:1|max:31',
        ]);

        AppSetting::set('denda_persen',     $this->dendaPersen);
        AppSetting::set('denda_batas_hari', $this->dendaBatasHari);

        $this->savedMsg = 'denda';
    }

    public function saveWoSettings(): void
    {
        $this->validate([
            'woEskalasiMenit1' => 'required|integer|min:1',
            'woEskalasiMenit2' => 'required|integer|min:1',
        ]);

        AppSetting::set('wo_eskalasi_menit_1', $this->woEskalasiMenit1);
        AppSetting::set('wo_eskalasi_menit_2', $this->woEskalasiMenit2);

        $this->savedMsg = 'wo';
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->savedMsg  = '';
    }
};
?>

<div class="px-5 py-4 max-w-2xl">

    <div class="border border-blue-200 rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-3 py-2 text-white font-bold text-sm tracking-wide"
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
            SETUP SISTEM
        </div>
    </div>

    {{-- ── Tab Navigation ── --}}
    @php
        $tabs = [
            'profil' => 'Profil Apartemen',
            'tarif'  => 'Tarif',
            'denda'  => 'Denda & Jatuh Tempo',
            'wo'     => 'Work Order',
        ];
    @endphp
    <div class="flex gap-0 mb-5 border-b border-gray-300">
        @foreach($tabs as $key => $label)
        <button wire:click="switchTab('{{ $key }}')"
                class="px-4 py-2 text-xs font-semibold border-b-2 transition-colors
                    {{ $activeTab === $key
                        ? 'border-[#1a5c2e] text-[#1a5c2e] bg-[#f0f9f3]'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    @php
        $inp   = 'border border-gray-400 px-2 py-1 text-[12px] w-full rounded';
        $label = 'text-xs text-gray-600 font-medium mb-1 block';
        $saved = fn(string $section) => $savedMsg === $section
            ? '<div class="px-3 py-2 bg-green-50 border border-green-200 rounded text-xs text-green-700 mb-3">✓ Pengaturan berhasil disimpan.</div>'
            : '';
    @endphp

    {{-- ══════ TAB: PROFIL ══════ --}}
    @if($activeTab === 'profil')
    <div>
        {!! $saved('profil') !!}
        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
            <div>
                <label class="{{ $label }}">Nama Apartemen <span class="text-red-500">*</span></label>
                <input wire:model="appNamaApartemen" type="text" class="{{ $inp }}" />
                @error('appNamaApartemen')<p class="text-[10px] text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $label }}">Alamat</label>
                <textarea wire:model="appAlamat" rows="2" class="{{ $inp }} resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">No. Telepon</label>
                    <input wire:model="appTelp" type="text" class="{{ $inp }}" placeholder="021-xxxx" />
                </div>
                <div>
                    <label class="{{ $label }}">WhatsApp CS</label>
                    <input wire:model="appWa" type="text" class="{{ $inp }}" placeholder="628xxxxxxxx" />
                </div>
            </div>
            <div>
                <label class="{{ $label }}">Email</label>
                <input wire:model="appEmail" type="email" class="{{ $inp }}" placeholder="cs@apartemen.com" />
            </div>
        </div>
        <div class="mt-4">
            <button wire:click="saveProfil"
                    class="px-6 py-2 bg-blue-600 text-white text-xs font-semibold rounded hover:bg-[#154d26] transition-colors">
                Simpan Profil
            </button>
        </div>
    </div>
    @endif

    {{-- ══════ TAB: TARIF ══════ --}}
    @if($activeTab === 'tarif')
    <div>
        {!! $saved('tarif') !!}
        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-5">

            <div>
                <p class="text-xs font-bold text-gray-700 mb-3 border-b pb-1.5">IPL / Service Charge</p>
                <div>
                    <label class="{{ $label }}">Tarif IPL (Rp per m²)</label>
                    <input wire:model="tarifIplPerM2" type="number" step="0.01" min="0" class="{{ $inp }} w-48" />
                    @error('tarifIplPerM2')<p class="text-[10px] text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <p class="text-xs font-bold text-gray-700 mb-3 border-b pb-1.5">Listrik (Rp per kWh berdasarkan Daya)</p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <label class="text-xs text-gray-500 w-28">1300 VA</label>
                        <input wire:model="tarifListrik1300" type="number" step="0.01" min="0" class="border border-gray-400 px-2 py-1 text-[12px] w-32 rounded" />
                        <span class="text-xs text-gray-400">Rp/kWh</span>
                        @error('tarifListrik1300')<p class="text-[10px] text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="text-xs text-gray-500 w-28">2200 VA</label>
                        <input wire:model="tarifListrik2200" type="number" step="0.01" min="0" class="border border-gray-400 px-2 py-1 text-[12px] w-32 rounded" />
                        <span class="text-xs text-gray-400">Rp/kWh</span>
                        @error('tarifListrik2200')<p class="text-[10px] text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="text-xs text-gray-500 w-28">3500–5500 VA</label>
                        <input wire:model="tarifListrik3500" type="number" step="0.01" min="0" class="border border-gray-400 px-2 py-1 text-[12px] w-32 rounded" />
                        <span class="text-xs text-gray-400">Rp/kWh</span>
                        @error('tarifListrik3500')<p class="text-[10px] text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div>
                <p class="text-xs font-bold text-gray-700 mb-3 border-b pb-1.5">Air</p>
                <div class="flex items-center gap-3">
                    <label class="text-xs text-gray-500 w-28">Tarif Air</label>
                    <input wire:model="tarifAirM3" type="number" step="0.01" min="0" class="border border-gray-400 px-2 py-1 text-[12px] w-32 rounded" />
                    <span class="text-xs text-gray-400">Rp/m³</span>
                    @error('tarifAirM3')<p class="text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
        <div class="mt-4">
            <button wire:click="saveTarif"
                    class="px-6 py-2 bg-blue-600 text-white text-xs font-semibold rounded hover:bg-[#154d26] transition-colors">
                Simpan Tarif
            </button>
        </div>
    </div>
    @endif

    {{-- ══════ TAB: DENDA ══════ --}}
    @if($activeTab === 'denda')
    <div>
        {!! $saved('denda') !!}
        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
            <div>
                <label class="{{ $label }}">Persentase Denda per Bulan (%)</label>
                <div class="flex items-center gap-2">
                    <input wire:model="dendaPersen" type="number" step="0.1" min="0" max="100" class="border border-gray-400 px-2 py-1 text-[12px] w-24 rounded" />
                    <span class="text-xs text-gray-400">% dari total tagihan</span>
                </div>
                @error('dendaPersen')<p class="text-[10px] text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $label }}">Batas Tanggal Jatuh Tempo (tgl berapa tiap bulan)</label>
                <div class="flex items-center gap-2">
                    <input wire:model="dendaBatasHari" type="number" min="1" max="31" class="border border-gray-400 px-2 py-1 text-[12px] w-20 rounded" />
                    <span class="text-xs text-gray-400">Lewat tanggal ini dikenakan denda</span>
                </div>
                @error('dendaBatasHari')<p class="text-[10px] text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="text-[11px] text-gray-400 bg-gray-50 rounded p-3">
                Contoh: Batas hari = 20, Denda = 2% → tagihan yang belum lunas setelah tanggal 20 dikenakan denda 2% per bulan.
            </div>
        </div>
        <div class="mt-4">
            <button wire:click="saveDenda"
                    class="px-6 py-2 bg-blue-600 text-white text-xs font-semibold rounded hover:bg-[#154d26] transition-colors">
                Simpan Pengaturan Denda
            </button>
        </div>
    </div>
    @endif

    {{-- ══════ TAB: WORK ORDER ══════ --}}
    @if($activeTab === 'wo')
    <div>
        {!! $saved('wo') !!}
        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
            <div>
                <label class="{{ $label }}">Eskalasi Level 1 — notifikasi ke Supervisor/Chief (menit)</label>
                <div class="flex items-center gap-2">
                    <input wire:model="woEskalasiMenit1" type="number" min="1" class="border border-gray-400 px-2 py-1 text-[12px] w-24 rounded" />
                    <span class="text-xs text-gray-400">menit sejak WO dibuat</span>
                </div>
                @error('woEskalasiMenit1')<p class="text-[10px] text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $label }}">Eskalasi Level 2 — notifikasi ke Manager/GM (menit)</label>
                <div class="flex items-center gap-2">
                    <input wire:model="woEskalasiMenit2" type="number" min="1" class="border border-gray-400 px-2 py-1 text-[12px] w-24 rounded" />
                    <span class="text-xs text-gray-400">menit sejak WO dibuat</span>
                </div>
                @error('woEskalasiMenit2')<p class="text-[10px] text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="text-[11px] text-gray-400 bg-gray-50 rounded p-3">
                WO Scheduler berjalan setiap menit via <code>php artisan schedule:run</code>.
                Jika WO belum ditangani melewati batas waktu, notifikasi eskalasi dikirim ke Supervisor/Manager yang terdaftar.
            </div>
        </div>
        <div class="mt-4">
            <button wire:click="saveWoSettings"
                    class="px-6 py-2 bg-blue-600 text-white text-xs font-semibold rounded hover:bg-[#154d26] transition-colors">
                Simpan Pengaturan WO
            </button>
        </div>
    </div>
    @endif

</div>

