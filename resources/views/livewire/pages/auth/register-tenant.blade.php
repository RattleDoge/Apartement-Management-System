<?php

use App\Models\Karyawan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $first_name         = '';
    public string $last_name          = '';
    public string $place_of_birth     = '';
    public string $date_of_birth      = '';
    public string $nik_ktp            = '';
    public string $full_address       = '';
    public string $phone              = '';
    public string $email              = '';
    public string $unit_number        = '';
    public string $unitCheckStatus    = ''; // '' | 'valid' | 'invalid'
    public string $status             = 'penyewa';
    public string $digital_signature  = '';
    public string $password           = '';
    public string $password_confirmation = '';

    // Live-check unit number as user types
    public function updatedUnitNumber(): void
    {
        $val = strtoupper(trim($this->unit_number));
        if (strlen($val) < 3) {
            $this->unitCheckStatus = '';
            return;
        }
        $this->unitCheckStatus = \App\Models\HandoverUnit::where('lot_no', $val)
            ->whereNotNull('str_date')
            ->exists() ? 'valid' : 'invalid';
    }

    public function register(): void
    {
        $this->validate([
            'first_name'        => ['required', 'string', 'max:100'],
            'last_name'         => ['required', 'string', 'max:100'],
            'place_of_birth'    => ['required', 'string', 'max:100'],
            'date_of_birth'     => ['required', 'date'],
            'nik_ktp'           => ['required', 'digits:16'],
            'full_address'      => ['required', 'string'],
            'phone'             => ['required', 'string', 'max:20'],
            'email'             => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'unit_number'       => [
                'required', 'string', 'max:30', 'regex:/^[A-Z0-9\/\-]+$/i',
                function ($attr, $value, $fail) {
                    $found = \App\Models\HandoverUnit::where('lot_no', strtoupper(trim($value)))
                        ->whereNotNull('str_date')
                        ->exists();
                    if (!$found) {
                        $fail('Unit ini belum serah terima. Silakan hubungi Customer Service Madison Park.');
                    }
                },
            ],
            'status'            => ['required', 'in:pemilik,penyewa'],
            'digital_signature' => ['required', 'string'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nik_ktp.digits'             => 'NIK KTP harus 16 digit angka.',
            'unit_number.regex'          => 'Format nomor unit tidak valid (contoh: MP/25/AB atau KGF/F01).',
            'digital_signature.required' => 'Tanda tangan digital wajib diisi.',
        ]);

        $user = User::create([
            'name'       => strtoupper($this->first_name . ' ' . $this->last_name),
            'first_name' => strtoupper($this->first_name),
            'last_name'  => strtoupper($this->last_name),
            'email'      => $this->email,
            'phone'      => $this->phone,
            'role'       => 'tenant',
            'password'   => Hash::make($this->password),
        ]);

        Tenant::create([
            'user_id'           => $user->id,
            'place_of_birth'    => $this->place_of_birth,
            'date_of_birth'     => $this->date_of_birth,
            'nik_ktp'           => $this->nik_ktp,
            'full_address'      => $this->full_address,
            'unit_number'       => strtoupper($this->unit_number),
            'status'            => $this->status,
            'digital_signature' => $this->digital_signature,
        ]);

        event(new Registered($user));
        $user->markEmailAsVerified();

        session()->flash('register_success', 'Akun penghuni Anda berhasil didaftarkan. Silakan login.');

        $this->redirect(route('login'), navigate: false);
    }
}; ?>

<div class="w-full max-w-2xl mx-auto">

    {{-- Card --}}
    <div class="rounded border border-gray-300 shadow-2xl overflow-hidden"
         style="background: linear-gradient(to bottom, #edeae3, #f8f6f1);">

        {{-- Header --}}
        <div class="px-8 py-5 text-center"
             style="background: linear-gradient(to bottom, #e4e0d8, #edeae3); border-bottom: 1px solid #d5d0c5;">
            <h1 class="font-black tracking-widest text-[#1a5c2e]" style="font-size: 2rem; line-height: 1;">AMS</h1>
            <p class="text-[11px] text-gray-500 mt-1 font-semibold tracking-wide uppercase">
                Apartement Madison Park
            </p>
            <p class="text-sm font-semibold text-gray-600 mt-2">Pendaftaran Penghuni / Tenant</p>
        </div>

        {{-- Form --}}
        <div class="px-8 py-6">
            <form wire:submit="register" class="space-y-5">

                {{-- Error Summary --}}
                @if($errors->any())
                <div class="px-4 py-3 bg-red-50 border border-red-300 rounded-lg">
                    <p class="text-sm font-semibold text-red-700 mb-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        Mohon perbaiki kesalahan berikut:
                    </p>
                    <ul class="text-xs text-red-600 list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- ── SECTION: Data Pribadi ── --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-1 border-b border-gray-300">
                        Data Pribadi
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Nama Depan --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Nama Depan <span class="text-red-500">*</span></label>
                            <input wire:model="first_name" type="text" required
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <x-input-error :messages="$errors->get('first_name')" class="mt-1 text-xs" />
                        </div>
                        {{-- Nama Belakang --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Nama Belakang <span class="text-red-500">*</span></label>
                            <input wire:model="last_name" type="text" required
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <x-input-error :messages="$errors->get('last_name')" class="mt-1 text-xs" />
                        </div>
                        {{-- Tempat Lahir --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Tempat Lahir <span class="text-red-500">*</span></label>
                            <input wire:model="place_of_birth" type="text" required
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <x-input-error :messages="$errors->get('place_of_birth')" class="mt-1 text-xs" />
                        </div>
                        {{-- Tanggal Lahir --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Lahir <span class="text-red-500">*</span></label>
                            <input wire:model="date_of_birth" type="date" required
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-1 text-xs" />
                        </div>
                    </div>
                    {{-- NIK KTP --}}
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-600 mb-1">NIK KTP <span class="text-red-500">*</span></label>
                        <input wire:model="nik_ktp" type="text" maxlength="16" required placeholder="16 digit NIK"
                            class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                        <x-input-error :messages="$errors->get('nik_ktp')" class="mt-1 text-xs" />
                    </div>
                </div>

                {{-- ── SECTION: Kontak & Alamat ── --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-1 border-b border-gray-300">
                        Kontak & Alamat
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Nomor Telepon --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Nomor Telepon / WA <span class="text-red-500">*</span></label>
                            <input wire:model="phone" type="tel" required placeholder="08xxxxxxxxxx"
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <x-input-error :messages="$errors->get('phone')" class="mt-1 text-xs" />
                        </div>
                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                            <input wire:model="email" type="email" required
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs" />
                        </div>
                    </div>
                    {{-- Alamat Lengkap --}}
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                        <textarea wire:model="full_address" rows="3" required
                            class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e] resize-none"
                            placeholder="Jl. Nama Jalan No. X, Kelurahan, Kecamatan, Kota"></textarea>
                        <x-input-error :messages="$errors->get('full_address')" class="mt-1 text-xs" />
                    </div>
                </div>

                {{-- ── SECTION: Data Unit ── --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-1 border-b border-gray-300">
                        Data Unit
                    </h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Nomor Unit <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input wire:model.live.debounce.500ms="unit_number" type="text" required
                                placeholder="Hunian: MP/25/AB  |  Kios: KGF/F01"
                                class="w-full border rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 uppercase pr-9
                                    {{ $unitCheckStatus === 'valid'   ? 'border-green-400 focus:ring-green-500' :
                                       ($unitCheckStatus === 'invalid' ? 'border-red-400 focus:ring-red-500'   :
                                       'border-gray-300 focus:ring-[#1a5c2e]') }}"
                                oninput="this.value = this.value.toUpperCase()">
                            {{-- Status icon --}}
                            @if($unitCheckStatus === 'valid')
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-green-500">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </span>
                            @elseif($unitCheckStatus === 'invalid')
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-red-500">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </span>
                            @else
                            <span wire:loading wire:target="updatedUnitNumber"
                                  class="absolute right-2.5 top-1/2 -translate-y-1/2">
                                <svg class="w-3.5 h-3.5 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                            </span>
                            @endif
                        </div>
                        <p class="mt-1 text-[11px] text-gray-400">Format hunian: <span class="font-mono">MP/25/AB</span> &nbsp;|&nbsp; Format kios: <span class="font-mono">KGF/F01</span></p>

                        {{-- Live feedback --}}
                        @if($unitCheckStatus === 'valid')
                        <p class="mt-1.5 text-xs text-green-700 font-medium flex items-center gap-1">
                            <span class="inline-flex items-center justify-center rounded-full bg-green-500 text-white shrink-0"
                                  style="width:14px; height:14px; font-size:9px; line-height:1;">✓</span>
                            Unit terdaftar dan sudah dilakukan serah terima.
                        </p>
                        @elseif($unitCheckStatus === 'invalid')
                        <div class="mt-2 px-4 py-3 bg-red-50 border border-red-300 rounded-lg">
                            <p class="text-sm font-semibold text-red-700 flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                </svg>
                                Unit Belum Serah Terima
                            </p>
                            <p class="text-xs text-red-600 mt-1.5 leading-relaxed">
                                Nomor unit <strong class="font-mono">{{ strtoupper($unit_number) }}</strong>
                                belum terdaftar dalam sistem serah terima atau proses serah terima belum selesai.
                            </p>
                            <div class="mt-2.5 pt-2 border-t border-red-200 space-y-0.5">
                                <p class="text-xs font-semibold text-red-700">Langkah selanjutnya:</p>
                                <p class="text-xs text-red-600">
                                    Silakan kunjungi atau hubungi <strong>Customer Service Madison Park</strong>
                                    untuk melakukan proses serah terima unit terlebih dahulu.
                                </p>
                                <p class="text-xs text-red-500 mt-1">
                                    🏢 Lobby Apartemen Madison Park &nbsp;·&nbsp; 🕐 Senin–Jumat 08:00–17:00 WIB
                                </p>
                            </div>
                        </div>
                        @endif

                        <x-input-error :messages="$errors->get('unit_number')" class="mt-1 text-xs" />
                    </div>

                    {{-- Status: Pemilik / Penyewa --}}
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-600 mb-2">Status Hunian <span class="text-red-500">*</span></label>
                        <div class="flex gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="status" type="radio" value="pemilik"
                                       class="w-4 h-4 accent-[#1a5c2e]">
                                <span class="text-sm text-gray-700">Pemilik</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="status" type="radio" value="penyewa"
                                       class="w-4 h-4 accent-[#1a5c2e]">
                                <span class="text-sm text-gray-700">Penyewa</span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('status')" class="mt-1 text-xs" />
                    </div>
                </div>

                {{-- ── SECTION: Tanda Tangan Digital ── --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-1 border-b border-gray-300">
                        Tanda Tangan Digital
                    </h3>
                    <label class="block text-sm font-medium text-gray-600 mb-2">
                        Tanda Tangan <span class="text-red-500">*</span>
                    </label>
                    <div wire:ignore class="relative border-2 border-dashed border-gray-400 rounded-lg bg-white overflow-hidden"
                         style="height: 150px; touch-action: none;">
                        <canvas id="tenant_sig_canvas"
                                class="absolute inset-0 cursor-crosshair"
                                style="width: 100%; height: 100%;"></canvas>
                        <p id="tenant_sig_hint"
                           class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm pointer-events-none select-none">
                            Tanda tangan di sini
                        </p>
                    </div>
                    <button type="button" onclick="clearTenantSig()"
                            class="mt-1.5 text-xs text-red-500 hover:text-red-700 underline">
                        Hapus Tanda Tangan
                    </button>
                    <input type="hidden" id="tenant_sig_data" wire:model="digital_signature">
                    <x-input-error :messages="$errors->get('digital_signature')" class="mt-1 text-xs" />
                </div>

                {{-- ── SECTION: Keamanan Akun ── --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-1 border-b border-gray-300">
                        Keamanan Akun
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Password <span class="text-red-500">*</span></label>
                            <input wire:model="password" type="password" required autocomplete="new-password"
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <p class="mt-1 text-[11px] text-gray-400">Minimal 8 karakter</p>
                            <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                            <input wire:model="password_confirmation" type="password" required autocomplete="new-password"
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-xs" />
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="pt-2 flex items-center justify-between">
                    <a href="{{ route('login') }}" wire:navigate
                       class="text-sm text-gray-500 hover:text-gray-700 underline">
                        Sudah punya akun?
                    </a>
                    <button type="submit"
                            {{ $unitCheckStatus === 'invalid' ? 'disabled' : '' }}
                            class="px-8 py-2 text-white text-sm font-medium rounded transition-colors
                                   {{ $unitCheckStatus === 'invalid' ? 'opacity-40 cursor-not-allowed' : '' }}"
                            style="background-color: #1a5c2e;"
                            onmouseover="if(!this.disabled) this.style.backgroundColor='#154a25'"
                            onmouseout="this.style.backgroundColor='#1a5c2e'">
                        Daftar Sekarang
                    </button>
                </div>

            </form>
        </div>
    </div>

    <p class="text-right mt-1 text-[11px] text-gray-400">Register Page — Tenant</p>
</div>

<script>
(function () {
    function initTenantPad() {
        const canvas = document.getElementById('tenant_sig_canvas');
        if (!canvas || canvas._ready) return;
        canvas._ready = true;

        const ctx = canvas.getContext('2d');
        const hint = document.getElementById('tenant_sig_hint');
        const hidden = document.getElementById('tenant_sig_data');
        let drawing = false;

        function setup() {
            const w = canvas.offsetWidth;
            const h = canvas.offsetHeight;
            canvas.width = w;
            canvas.height = h;
            ctx.strokeStyle = '#1a1a1a';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
        }
        setup();

        function xy(e) {
            const r = canvas.getBoundingClientRect();
            const s = e.touches ? e.touches[0] : e;
            return [s.clientX - r.left, s.clientY - r.top];
        }

        function start(e) {
            drawing = true;
            hint.style.opacity = '0';
            ctx.beginPath();
            ctx.moveTo(...xy(e));
        }
        function move(e) {
            if (!drawing) return;
            ctx.lineTo(...xy(e));
            ctx.stroke();
        }
        function stop() {
            if (!drawing) return;
            drawing = false;
            hidden.value = canvas.toDataURL('image/png');
            hidden.dispatchEvent(new Event('input'));
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', stop);
        canvas.addEventListener('mouseleave', stop);
        canvas.addEventListener('touchstart', e => { e.preventDefault(); start(e); }, { passive: false });
        canvas.addEventListener('touchmove', e => { e.preventDefault(); move(e); }, { passive: false });
        canvas.addEventListener('touchend', stop);
    }

    window.clearTenantSig = function () {
        const canvas = document.getElementById('tenant_sig_canvas');
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const hidden = document.getElementById('tenant_sig_data');
        hidden.value = '';
        hidden.dispatchEvent(new Event('input'));
        document.getElementById('tenant_sig_hint').style.opacity = '1';
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTenantPad);
    } else {
        initTenantPad();
    }
    document.addEventListener('livewire:navigated', initTenantPad);
})();
</script>
