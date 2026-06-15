<?php

use App\Models\Karyawan;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $first_name            = '';
    public string $last_name             = '';
    public string $email                 = '';
    public string $finger_id             = '';
    public string $no_hp                 = '';
    public string $jabatan               = '';
    public string $digital_signature     = '';
    public string $password              = '';
    public string $password_confirmation = '';

    public array $jabatanList = [
        'Manager',
        'Chief',
        'Supervisor',
        'Admin',
        'Staff',
    ];

    public function register(): void
    {
        $this->validate([
            'first_name'        => ['required', 'string', 'max:100'],
            'last_name'         => ['required', 'string', 'max:100'],
            'email'             => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'finger_id'         => ['required', 'string', 'max:30'],
            'no_hp'             => ['required', 'string', 'max:20'],
            'jabatan'           => ['required', 'in:Manager,Chief,Supervisor,Admin,Staff'],
            'digital_signature' => ['required', 'string'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'jabatan.in'                     => 'Pilih jabatan yang tersedia.',
            'digital_signature.required'     => 'Tanda tangan digital wajib diisi.',
            'finger_id.required'             => 'Nomor finger / NIK wajib diisi.',
            'no_hp.required'                 => 'Nomor telepon wajib diisi.',
            'no_hp.max'                      => 'Nomor telepon maksimal 20 karakter.',
        ]);

        // ── Verifikasi data staff ────────────────────────────────────────────────
        $namaLengkap = trim($this->first_name . ' ' . $this->last_name);

        $staff = Staff::where('status', 'Aktif')
            ->whereRaw('LOWER(TRIM(email)) = ?',      [strtolower(trim($this->email))])
            ->whereRaw('LOWER(TRIM(nama_staff)) = ?', [strtolower($namaLengkap)])
            ->whereRaw('TRIM(finger_id) = ?',         [trim($this->finger_id)])
            ->whereRaw('TRIM(no_hp_otp) = ?',         [trim($this->no_hp)])
            ->first();

        if (! $staff) {
            $this->addError('verifikasi',
                'Data tidak cocok dengan data karyawan yang terdaftar. ' .
                'Pastikan nama lengkap, email, nomor finger, dan nomor telepon sesuai data yang telah diinput admin. ' .
                'Hubungi admin / CS jika perlu bantuan.'
            );
            return;
        }

        // Cek apakah staff ini sudah punya akun
        if (Karyawan::where('staff_id', $staff->id)->exists()) {
            $this->addError('verifikasi',
                'Karyawan ini sudah memiliki akun yang terdaftar. Silakan login atau hubungi admin.'
            );
            return;
        }

        // ── Buat akun ────────────────────────────────────────────────────────────
        $user = User::create([
            'name'       => $namaLengkap,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'email'      => $this->email,
            'phone'      => $this->no_hp,
            'role'       => 'karyawan',
            'password'   => Hash::make($this->password),
        ]);

        Karyawan::create([
            'user_id'           => $user->id,
            'staff_id'          => $staff->id,
            'nik_karyawan'      => $this->finger_id,
            'departemen'        => $staff->departemen,
            'jabatan'           => $this->jabatan,
            'digital_signature' => $this->digital_signature,
        ]);

        event(new Registered($user));
        $user->markEmailAsVerified();

        session()->flash('register_success', 'Akun karyawan Anda berhasil didaftarkan. Silakan login.');
        $this->redirect(route('login'), navigate: false);
    }
}; ?>

<div class="w-full max-w-xl mx-auto">

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
            <p class="text-sm font-semibold text-gray-600 mt-2">Pendaftaran Karyawan</p>
        </div>

        {{-- Form --}}
        <div class="px-8 py-6">

            {{-- Info verifikasi --}}
            <div class="mb-5 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg flex gap-3">
                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-xs text-blue-700 leading-relaxed">
                    <p class="font-semibold mb-1">Syarat Pendaftaran</p>
                    <p>Data yang Anda masukkan harus <strong>sesuai persis</strong> dengan data yang telah diinput oleh admin. Admin harus mendaftarkan data staff terlebih dahulu sebelum Anda bisa membuat akun.</p>
                    <p class="mt-1">Data yang diverifikasi: <strong>Nama Lengkap, Email, Nomor Finger / NIK,</strong> dan <strong>Nomor Telepon</strong>.</p>
                </div>
            </div>

            <form wire:submit="register" class="space-y-5">

                {{-- Error Verifikasi Staff --}}
                @error('verifikasi')
                <div class="px-4 py-3 bg-red-50 border border-red-400 rounded-lg flex gap-3">
                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-red-700">Verifikasi Gagal</p>
                        <p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>
                    </div>
                </div>
                @enderror

                {{-- Error Summary (validasi form, kecuali 'verifikasi') --}}
                @php
                    $formErrors = collect($errors->toArray())->except('verifikasi')->flatten();
                @endphp
                @if($formErrors->isNotEmpty())
                <div class="px-4 py-3 bg-red-50 border border-red-300 rounded-lg">
                    <p class="text-sm font-semibold text-red-700 mb-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        Mohon perbaiki kesalahan berikut:
                    </p>
                    <ul class="text-xs text-red-600 list-disc list-inside space-y-0.5">
                        @foreach($formErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- ── SECTION: Data Diri ── --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-1 border-b border-gray-300">
                        Data Diri
                        <span class="text-[10px] text-blue-500 font-normal normal-case ml-1">(harus sesuai data staff di sistem)</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Nama Depan <span class="text-red-500">*</span></label>
                            <input wire:model="first_name" type="text" required
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <x-input-error :messages="$errors->get('first_name')" class="mt-1 text-xs" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Nama Belakang <span class="text-red-500">*</span></label>
                            <input wire:model="last_name" type="text" required
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <x-input-error :messages="$errors->get('last_name')" class="mt-1 text-xs" />
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                            <input wire:model="email" type="email" required autocomplete="username"
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Nomor Telepon <span class="text-red-500">*</span></label>
                            <input wire:model="no_hp" type="tel" required placeholder="cth: 08123456789"
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                            <x-input-error :messages="$errors->get('no_hp')" class="mt-1 text-xs" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Nomor Finger / NIK Karyawan <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="finger_id" type="text" required placeholder="Nomor finger absensi atau NIK karyawan"
                            class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                        <x-input-error :messages="$errors->get('finger_id')" class="mt-1 text-xs" />
                    </div>
                </div>

                {{-- ── SECTION: Informasi Pekerjaan ── --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-1 border-b border-gray-300">
                        Informasi Pekerjaan
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Departemen (read-only, auto dari staff) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Departemen</label>
                            <div class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm bg-gray-50 text-gray-500"
                                 style="display:flex; align-items:center; gap:6px;">
                                <svg style="width:14px;height:14px;flex-shrink:0;color:#9ca3af;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Otomatis dari data staff
                            </div>
                        </div>
                        {{-- Jabatan --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Jabatan <span class="text-red-500">*</span></label>
                            <select wire:model="jabatan" required
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1a5c2e]">
                                <option value="">-- Pilih Jabatan --</option>
                                @foreach($jabatanList as $pos)
                                    <option value="{{ $pos }}">{{ $pos }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('jabatan')" class="mt-1 text-xs" />
                        </div>
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
                        <canvas id="karyw_sig_canvas"
                                class="absolute inset-0 cursor-crosshair"
                                style="width: 100%; height: 100%;"></canvas>
                        <p id="karyw_sig_hint"
                           class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm pointer-events-none select-none">
                            Tanda tangan di sini
                        </p>
                    </div>
                    <button type="button" onclick="clearKarywSig()"
                            class="mt-1.5 text-xs text-red-500 hover:text-red-700 underline">
                        Hapus Tanda Tangan
                    </button>
                    <input type="hidden" id="karyw_sig_data" wire:model="digital_signature">
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
                            wire:loading.attr="disabled"
                            class="flex items-center gap-2 px-8 py-2 text-white text-sm font-medium rounded transition-colors disabled:opacity-60"
                            style="background-color: #1a5c2e;"
                            onmouseover="this.style.backgroundColor='#154a25'"
                            onmouseout="this.style.backgroundColor='#1a5c2e'">
                        <span wire:loading.remove>Daftar Sekarang</span>
                        <span wire:loading class="flex items-center gap-1.5">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            Memverifikasi...
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <p class="text-right mt-1 text-[11px] text-gray-400">Register Page — Karyawan</p>
</div>

<script>
(function () {
    function initKarywPad() {
        const canvas = document.getElementById('karyw_sig_canvas');
        if (!canvas || canvas._ready) return;
        canvas._ready = true;

        const ctx = canvas.getContext('2d');
        const hint = document.getElementById('karyw_sig_hint');
        const hidden = document.getElementById('karyw_sig_data');
        let drawing = false;

        function setup() {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
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

        function start(e) { drawing = true; hint.style.opacity = '0'; ctx.beginPath(); ctx.moveTo(...xy(e)); }
        function move(e) { if (!drawing) return; ctx.lineTo(...xy(e)); ctx.stroke(); }
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

    window.clearKarywSig = function () {
        const canvas = document.getElementById('karyw_sig_canvas');
        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        const hidden = document.getElementById('karyw_sig_data');
        hidden.value = '';
        hidden.dispatchEvent(new Event('input'));
        document.getElementById('karyw_sig_hint').style.opacity = '1';
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initKarywPad);
    } else {
        initKarywPad();
    }
    document.addEventListener('livewire:navigated', initKarywPad);
})();
</script>
