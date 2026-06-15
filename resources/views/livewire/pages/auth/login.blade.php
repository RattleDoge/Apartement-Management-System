<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();

        /** @var \App\Models\User|null $authUser */
        $authUser = auth()->user();
        $role = $authUser?->role;
        if ($role === 'tenant') {
            $this->redirect(route('tenant.dashboard'), navigate: false);
        } else {
            $this->redirectIntended(default: route('dashboard'), navigate: false);
        }
    }
}; ?>

<div class="w-full max-w-xs mx-auto select-none">

    {{-- Card --}}
    <div class="rounded border border-gray-300 shadow-2xl overflow-hidden"
         style="background: linear-gradient(to bottom, #edeae3, #f8f6f1);">

        {{-- Logo Header --}}
        <div class="px-10 pt-8 pb-5 text-center"
             style="background: linear-gradient(to bottom, #e4e0d8, #edeae3); border-bottom: 1px solid #d5d0c5;">
            <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:3rem;letter-spacing:0.2em;line-height:1;background:linear-gradient(135deg,#1a5c2e 0%,#2d9e56 60%,#4ade80 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">AMS</div>
            <div style="font-size:10px;color:#6b7280;letter-spacing:0.1em;margin-top:4px;font-weight:600;text-transform:uppercase;">Apartement Management System</div>
            <div style="font-size:11px;color:#9ca3af;margin-top:6px;letter-spacing:0.12em;font-weight:500;text-transform:uppercase;">Madison Park</div>
        </div>

        {{-- Notifikasi Berhasil Daftar (banner tipis di bawah header) --}}
        @if(session('register_success'))
        <div style="display:flex;align-items:center;gap:8px;padding:7px 16px;background:#16a34a;color:#fff;font-size:11px;font-weight:500;letter-spacing:0.02em;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                <path d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            {{ session('register_success') }}
        </div>
        @endif

        {{-- Form Body --}}
        <div class="px-8 py-6">
            <x-auth-session-status class="mb-3 text-xs" :status="session('status')" />

            <form wire:submit="login" class="space-y-3">

                {{-- Error Notification --}}
                @if($errors->has('form.email') || $errors->has('form.password'))
                <div class="px-3 py-2.5 bg-red-50 border border-red-300 rounded flex items-start gap-2">
                    <svg class="w-4 h-4 shrink-0 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <div class="text-xs text-red-700">
                        @foreach($errors->get('form.email') as $msg)
                            <p>{{ $msg }}</p>
                        @endforeach
                        @foreach($errors->get('form.password') as $msg)
                            <p>{{ $msg }}</p>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Username --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-600 mb-1">
                        Email :
                    </label>
                    <input
                        wire:model="form.email"
                        id="email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full border rounded px-3 py-1.5 text-sm bg-white
                               focus:outline-none focus:ring-1
                               {{ $errors->has('form.email') ? 'border-red-400 focus:ring-red-400 bg-red-50' : 'border-gray-300 focus:ring-[#1a5c2e] focus:border-[#1a5c2e]' }}"
                    >
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-600 mb-1">
                        Password :
                    </label>
                    <input
                        wire:model="form.password"
                        id="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full border rounded px-3 py-1.5 text-sm bg-white
                               focus:outline-none focus:ring-1
                               {{ $errors->has('form.email') ? 'border-red-400 focus:ring-red-400 bg-red-50' : 'border-gray-300 focus:ring-[#1a5c2e] focus:border-[#1a5c2e]' }}"
                    >
                </div>

                {{-- Submit --}}
                <div class="pt-2 text-center">
                    <button
                        type="submit"
                        class="px-8 py-1.5 bg-gray-200 hover:bg-gray-300 active:bg-gray-400
                               border border-gray-400 text-gray-700 text-sm rounded transition-colors"
                    >
                        Submit
                    </button>
                </div>
            </form>

            {{-- Register Links --}}
            <div class="mt-5 pt-4 border-t border-gray-300 text-center">
                <p class="text-[11px] text-gray-500 mb-2">Belum punya akun?</p>
                <div class="flex justify-center gap-2">
                    <a
                        href="{{ route('register.tenant') }}"
                        wire:navigate
                        class="text-[11px] px-3 py-1 rounded text-white font-medium transition-colors"
                        style="background-color: #1a5c2e;"
                        onmouseover="this.style.backgroundColor='#154a25'"
                        onmouseout="this.style.backgroundColor='#1a5c2e'"
                    >
                        Daftar Penghuni
                    </a>
                    <a
                        href="{{ route('register.karyawan') }}"
                        wire:navigate
                        class="text-[11px] px-3 py-1 rounded text-white font-medium bg-slate-600 hover:bg-slate-700 transition-colors"
                    >
                        Daftar Karyawan
                    </a>
                </div>
                @if (Route::has('password.request'))
                    <a
                        href="{{ route('password.request') }}"
                        wire:navigate
                        class="block mt-3 text-[11px] text-gray-400 hover:text-gray-600 underline"
                    >
                        Lupa password?
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Label --}}
    <p class="text-right mt-1 text-[11px] text-gray-400">Login Page</p>
</div>
