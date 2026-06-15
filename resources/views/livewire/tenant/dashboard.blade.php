<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\TenantRequest;
use App\Models\InOutPermit;
use App\Models\FacilityReservation;
use App\Models\EmergencyContact;
use App\Models\Banner;
use App\Models\Invoice;

new #[Layout('layouts.tenant')] class extends Component {

    public function with(): array
    {
        $tenantProfile = auth()->user()->tenant;
        $unit = $tenantProfile?->unit_number;

        $pendingRequests = $unit
            ? TenantRequest::where('lot_no', $unit)
                ->whereNotIn('status', ['Selesai', 'Tidak Dapat Diaplikasi'])
                ->count()
            : 0;

        $pendingPermits = $unit
            ? InOutPermit::where('unit', $unit)
                ->whereNotIn('status', ['Approve by Security', 'Tidak Disetujui'])
                ->count()
            : 0;

        $pendingReservations = $unit
            ? FacilityReservation::where('unit', $unit)
                ->whereNotIn('status', ['Selesai', 'Ditolak'])
                ->count()
            : 0;

        $unpaidInvoices = $unit
            ? Invoice::where('debtor_acct', $unit)->where('status_bayar', 'Belum Lunas')->count()
            : 0;

        $emergencyContacts = EmergencyContact::orderBy('kategori')->orderBy('nama')->get();
        $activeBanners     = Banner::allActive();

        return compact(
            'tenantProfile', 'pendingRequests', 'pendingPermits',
            'pendingReservations', 'unpaidInvoices', 'emergencyContacts', 'activeBanners'
        );
    }
}
?>

<div x-data="{ emergencyOpen: false,
               lightboxOpen: false, lightboxSlides: [], lightboxCur: 0,
               openLightbox(slides, idx){ this.lightboxSlides=slides; this.lightboxCur=idx; this.lightboxOpen=true; },
               closeLightbox(){ this.lightboxOpen=false; },
               lbPrev(){ this.lightboxCur=(this.lightboxCur-1+this.lightboxSlides.length)%this.lightboxSlides.length; },
               lbNext(){ this.lightboxCur=(this.lightboxCur+1)%this.lightboxSlides.length; } }">

    {{-- ═══════════════════════════════════════════
         HERO BANNER
    ════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden"
         style="background: linear-gradient(135deg, #0f3d1f 0%, #1a5c2e 45%, #237a3e 100%); min-height: 190px;">

        {{-- Decorative pattern --}}
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full" style="background:rgba(255,255,255,0.04);"></div>
            <div class="absolute -bottom-12 -left-12 w-56 h-56 rounded-full" style="background:rgba(255,255,255,0.03);"></div>
            <svg class="absolute bottom-0 right-0 opacity-5" width="300" height="200" viewBox="0 0 300 200">
                <line x1="0" y1="200" x2="300" y2="0" stroke="white" stroke-width="1"/>
                <line x1="50" y1="200" x2="350" y2="0" stroke="white" stroke-width="1"/>
                <line x1="100" y1="200" x2="400" y2="0" stroke="white" stroke-width="1"/>
            </svg>
        </div>

        <div class="relative px-6 py-10" style="color:white;">
            {{-- Two-column: left text, right avatar --}}
            <div style="display:flex; align-items:center; justify-content:space-between; gap:24px;">

                {{-- Left: greeting + name + chips --}}
                <div style="flex:1; min-width:0;">
                    <p style="font-size:11px; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:rgba(167,243,168,0.9); margin-bottom:6px;">
                        Selamat Datang Kembali
                    </p>
                    <h1 style="font-size:28px; font-weight:800; letter-spacing:-0.02em; margin-bottom:14px; line-height:1.1; text-shadow:0 2px 8px rgba(0,0,0,0.2);">
                        {{ ucwords(strtolower(auth()->user()->name)) }}
                    </h1>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                        @if($tenantProfile?->unit_number)
                        <span style="display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; padding:6px 12px; border-radius:8px; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); color:white;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75L12 3l9 6.75V21H3V9.75z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 21V12h6v9"/>
                            </svg>
                            Unit {{ $tenantProfile->unit_number }}
                        </span>
                        @endif
                        @if($tenantProfile?->status)
                        <span style="display:inline-flex; align-items:center; font-size:12px; font-weight:500; padding:6px 12px; border-radius:8px; background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2); color:rgba(220,252,231,1); text-transform:capitalize;">
                            {{ ucfirst($tenantProfile->status) }}
                        </span>
                        @endif
                        <span style="display:inline-flex; align-items:center; font-size:11px; padding:6px 12px; border-radius:8px; background:rgba(0,0,0,0.15); border:1px solid rgba(255,255,255,0.12); color:rgba(187,247,208,0.9);">
                            {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                        </span>
                    </div>
                    @if(!$tenantProfile?->unit_number)
                    <div style="margin-top:16px; display:inline-flex; align-items:center; gap:8px; font-size:12px; padding:8px 16px; border-radius:8px; background:rgba(234,179,8,0.2); border:1px solid rgba(253,224,71,0.35); color:rgba(254,240,138,1);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"/>
                        </svg>
                        Profil belum lengkap. Hubungi CS untuk mengaktifkan akun Anda.
                    </div>
                    @endif
                </div>

                {{-- Right: banner carousel atau avatar --}}
                @php
                    $initials = collect(explode(' ', auth()->user()->name))
                        ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                        ->take(2)->implode('');
                @endphp
                @if($activeBanners->isNotEmpty())
                @php $bannerJsonHero = $activeBanners->map(fn($b) => asset('storage/' . $b->image_path))->values()->toJson(); @endphp
                <div x-data="{ slides: {{ $bannerJsonHero }}, cur: 0, t: null,
                                init(){ if(this.slides.length>1) this.t=setInterval(()=>{ this.cur=(this.cur+1)%this.slides.length; },3500); },
                                destroy(){ clearInterval(this.t); } }"
                     style="flex-shrink:0; width:320px; height:200px; border-radius:12px; overflow:hidden; border:2px solid rgba(255,255,255,0.25); box-shadow:0 4px 20px rgba(0,0,0,0.35); position:relative; background:#000;">
                    <template x-for="(src, i) in slides" :key="i">
                        <img x-show="cur===i" :src="src" alt="banner"
                             @click="openLightbox(slides, i)"
                             x-transition:enter="transition ease-in-out duration-500"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition duration-300"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             style="width:100%;height:100%;object-fit:contain;object-position:center;background:#000;cursor:zoom-in;">
                    </template>
                    {{-- zoom hint --}}
                    <div style="position:absolute;top:6px;right:6px;background:rgba(0,0,0,0.45);border-radius:6px;padding:3px 5px;pointer-events:none;">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0zM11 8v6M8 11h6"/>
                        </svg>
                    </div>
                    @if($activeBanners->count() > 1)
                    <div style="position:absolute;bottom:5px;left:0;right:0;display:flex;justify-content:center;gap:4px;">
                        <template x-for="(s, i) in slides" :key="i">
                            <button @click="cur=i;clearInterval(t)" :style="cur===i ? 'background:rgba(255,255,255,0.9);width:14px;' : 'background:rgba(255,255,255,0.4);width:6px;'"
                                    style="height:6px;border-radius:3px;border:none;cursor:pointer;transition:all 0.3s;padding:0;"></button>
                        </template>
                    </div>
                    @endif
                </div>
                @else
                <div style="display:flex; flex-direction:column; align-items:center; gap:8px; flex-shrink:0;">
                    <div style="width:64px; height:64px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:900; background:rgba(255,255,255,0.15); border:2px solid rgba(255,255,255,0.28); color:white; text-shadow:0 1px 3px rgba(0,0,0,0.2);">
                        {{ $initials }}
                    </div>
                    <span style="font-size:10px; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:rgba(187,247,208,0.65);">Tenant</span>
                </div>
                @endif

            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         MAIN CONTENT
    ════════════════════════════════════════════ --}}
    <div class="px-6 py-8">

        {{-- Section label --}}
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <p style="font-size:11px; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:#9ca3af; white-space:nowrap;">Layanan Tersedia</p>
            <div style="flex:1; height:1px; background:#f3f4f6;"></div>
        </div>

        {{-- 2×2 Card Grid --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">

            {{-- Card 1: Tenant Request --}}
            <a href="{{ route('tenant.request') }}"
               class="group"
               style="background:white; border-radius:16px; border:1px solid #f3f4f6; box-shadow:0 1px 4px rgba(0,0,0,0.06); overflow:hidden; display:flex; flex-direction:column; text-decoration:none; transition:box-shadow 0.2s, transform 0.2s;"
               onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'; this.style.transform='translateY(-2px)'"
               onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,0.06)'; this.style.transform='translateY(0)'">
                <div style="height:4px; background:linear-gradient(to right, #f59e0b, #f97316);"></div>
                <div style="padding:20px; display:flex; flex-direction:column; flex:1;">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px;">
                        <div style="width:44px; height:44px; border-radius:12px; background:#fffbeb; border:1px solid #fde68a; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
                            </svg>
                        </div>
                        @if($pendingRequests > 0)
                        <span style="display:inline-flex; align-items:center; gap:4px; background:#ef4444; color:white; font-size:10px; font-weight:700; padding:4px 10px; border-radius:20px;">
                            <span style="width:6px; height:6px; border-radius:50%; background:rgba(255,255,255,0.8); display:inline-block; animation:pulse 2s infinite;"></span>
                            {{ $pendingRequests }} proses
                        </span>
                        @else
                        <span style="background:#f0fdf4; color:#16a34a; font-size:10px; font-weight:500; padding:4px 10px; border-radius:20px; border:1px solid #bbf7d0;">Tidak ada</span>
                        @endif
                    </div>
                    <p style="font-size:14px; font-weight:700; color:#1f2937; margin-bottom:4px;">Tenant Request</p>
                    <p style="font-size:12px; color:#9ca3af; flex:1; line-height:1.6;">Laporkan keluhan atau permintaan perbaikan pada unit dan fasilitas apartemen Anda.</p>
                    <div style="margin-top:16px; display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:600; color:#f59e0b;">
                        Buka Layanan
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>

            {{-- Card 2: In Out Permit --}}
            <a href="{{ route('tenant.in-out-permit') }}"
               style="background:white; border-radius:16px; border:1px solid #f3f4f6; box-shadow:0 1px 4px rgba(0,0,0,0.06); overflow:hidden; display:flex; flex-direction:column; text-decoration:none; transition:box-shadow 0.2s, transform 0.2s;"
               onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'; this.style.transform='translateY(-2px)'"
               onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,0.06)'; this.style.transform='translateY(0)'">
                <div style="height:4px; background:linear-gradient(to right, #3b82f6, #6366f1);"></div>
                <div style="padding:20px; display:flex; flex-direction:column; flex:1;">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px;">
                        <div style="width:44px; height:44px; border-radius:12px; background:#eff6ff; border:1px solid #bfdbfe; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                            </svg>
                        </div>
                        @if($pendingPermits > 0)
                        <span style="display:inline-flex; align-items:center; gap:4px; background:#ef4444; color:white; font-size:10px; font-weight:700; padding:4px 10px; border-radius:20px;">
                            <span style="width:6px; height:6px; border-radius:50%; background:rgba(255,255,255,0.8); display:inline-block;"></span>
                            {{ $pendingPermits }} proses
                        </span>
                        @else
                        <span style="background:#f0fdf4; color:#16a34a; font-size:10px; font-weight:500; padding:4px 10px; border-radius:20px; border:1px solid #bbf7d0;">Tidak ada</span>
                        @endif
                    </div>
                    <p style="font-size:14px; font-weight:700; color:#1f2937; margin-bottom:4px;">In Out Permit</p>
                    <p style="font-size:12px; color:#9ca3af; flex:1; line-height:1.6;">Ajukan izin untuk pemindahan barang masuk atau keluar dari unit apartemen Anda.</p>
                    <div style="margin-top:16px; display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:600; color:#3b82f6;">
                        Buka Layanan
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>

            {{-- Card 3: Cek Invoice --}}
            <a href="{{ route('tenant.cek-invoice') }}"
               style="background:white; border-radius:16px; border:1px solid #f3f4f6; box-shadow:0 1px 4px rgba(0,0,0,0.06); overflow:hidden; display:flex; flex-direction:column; text-decoration:none; transition:box-shadow 0.2s, transform 0.2s;"
               onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'; this.style.transform='translateY(-2px)'"
               onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,0.06)'; this.style.transform='translateY(0)'">
                <div style="height:4px; background:linear-gradient(to right, #8b5cf6, #a855f7);"></div>
                <div style="padding:20px; display:flex; flex-direction:column; flex:1;">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px;">
                        <div style="width:44px; height:44px; border-radius:12px; background:#f5f3ff; border:1px solid #ddd6fe; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                            </svg>
                        </div>
                        @if($unpaidInvoices > 0)
                        <span style="background:#fef2f2; color:#dc2626; font-size:10px; font-weight:600; padding:4px 10px; border-radius:20px;">{{ $unpaidInvoices }} Belum Lunas</span>
                        @else
                        <span style="background:#f0fdf4; color:#16a34a; font-size:10px; font-weight:500; padding:4px 10px; border-radius:20px;">Lunas</span>
                        @endif
                    </div>
                    <p style="font-size:14px; font-weight:700; color:#1f2937; margin-bottom:4px;">Cek Invoice</p>
                    <p style="font-size:12px; color:#9ca3af; flex:1; line-height:1.6;">Lihat dan cek tagihan IPL, air, listrik, dan pembayaran lainnya terkait hunian Anda.</p>
                    <div style="margin-top:16px; display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:600; color:#8b5cf6;">
                        Lihat Invoice
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>

            {{-- Card 4: Reservasi Fasilitas --}}
            <a href="{{ route('tenant.facility-reservation') }}"
               style="background:white; border-radius:16px; border:1px solid #f3f4f6; box-shadow:0 1px 4px rgba(0,0,0,0.06); overflow:hidden; display:flex; flex-direction:column; text-decoration:none; transition:box-shadow 0.2s, transform 0.2s;"
               onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'; this.style.transform='translateY(-2px)'"
               onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,0.06)'; this.style.transform='translateY(0)'">
                <div style="height:4px; background:linear-gradient(to right, #14b8a6, #10b981);"></div>
                <div style="padding:20px; display:flex; flex-direction:column; flex:1;">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px;">
                        <div style="width:44px; height:44px; border-radius:12px; background:#f0fdfa; border:1px solid #99f6e4; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#14b8a6" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5m-9-6h.008v.008H12V9.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM12 12.75h.008v.008H12v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM12 15.75h.008v.008H12v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                        </div>
                        @if($pendingReservations > 0)
                        <span style="display:inline-flex; align-items:center; gap:4px; background:#ef4444; color:white; font-size:10px; font-weight:700; padding:4px 10px; border-radius:20px;">
                            <span style="width:6px; height:6px; border-radius:50%; background:rgba(255,255,255,0.8); display:inline-block;"></span>
                            {{ $pendingReservations }} proses
                        </span>
                        @else
                        <span style="background:#f0fdf4; color:#16a34a; font-size:10px; font-weight:500; padding:4px 10px; border-radius:20px; border:1px solid #bbf7d0;">Tidak ada</span>
                        @endif
                    </div>
                    <p style="font-size:14px; font-weight:700; color:#1f2937; margin-bottom:4px;">Reservasi Fasilitas</p>
                    <p style="font-size:12px; color:#9ca3af; flex:1; line-height:1.6;">Pesan dan booking fasilitas umum apartemen seperti balai warga, kolam renang, gym, dan lainnya.</p>
                    <div style="margin-top:16px; display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:600; color:#14b8a6;">
                        Buka Layanan
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>

        </div>

        {{-- Bottom info bar --}}
        <div style="margin-top:24px; display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-radius:12px; background:#f9fafb; border:1px solid #f3f4f6;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:28px; height:28px; border-radius:50%; background:#dcfce7; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1a5c2e" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                    </svg>
                </div>
                <p style="font-size:12px; color:#6b7280;">
                    Butuh bantuan? Hubungi CS di lobi atau WhatsApp <strong style="color:#1a5c2e;">CS Madison Park</strong>. Response time ±15 menit.
                </p>
            </div>
            <button @click="emergencyOpen = true"
                    style="flex-shrink:0; margin-left:16px; display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; color:#dc2626; background:none; border:none; cursor:pointer; padding:4px 0; transition:color 0.15s;"
                    onmouseover="this.style.color='#b91c1c'" onmouseout="this.style.color='#dc2626'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                </svg>
                Kontak Darurat
            </button>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════
         EMERGENCY FLOATING BUTTON (bottom-left)
    ════════════════════════════════════════════ --}}
    <div style="position:fixed; bottom:24px; left:24px; z-index:40;" x-cloak>
        <button @click="emergencyOpen = true"
                style="position:relative; display:inline-flex; align-items:center; gap:8px; color:white; font-size:13px; font-weight:700; border-radius:99px; border:none; cursor:pointer; background:linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding:12px 20px; box-shadow:0 4px 20px rgba(220,38,38,0.45); transition:transform 0.15s, box-shadow 0.15s;"
                onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 28px rgba(220,38,38,0.55)'"
                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 20px rgba(220,38,38,0.45)'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
            </svg>
            Darurat
        </button>
    </div>

    {{-- ═══════════════════════════════════════════
         LIGHTBOX MODAL
    ════════════════════════════════════════════ --}}
    <div x-show="lightboxOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="closeLightbox()"
         @keydown.escape.window="closeLightbox()"
         @keydown.arrow-left.window="lbPrev()"
         @keydown.arrow-right.window="lbNext()"
         style="position:fixed;inset:0;z-index:60;background:rgba(0,0,0,0.88);display:flex;align-items:center;justify-content:center;"
         x-cloak>

        {{-- Close button --}}
        <button @click="closeLightbox()"
                style="position:absolute;top:16px;right:16px;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:white;font-size:18px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:2;transition:background 0.15s;"
                onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                onmouseout="this.style.background='rgba(255,255,255,0.15)'">✕</button>

        {{-- Row: prev | image | next --}}
        <div style="display:flex;align-items:center;justify-content:center;gap:16px;padding:56px 24px 40px;max-width:100%;max-height:100%;">

            {{-- Prev button --}}
            <button x-show="lightboxSlides.length > 1"
                    @click="lbPrev()"
                    style="flex-shrink:0;width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:white;font-size:24px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.15s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.28)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.15)'">&#8249;</button>

            {{-- Image --}}
            <img :src="lightboxSlides[lightboxCur]" alt="banner"
                 style="max-width:80vw;max-height:80vh;object-fit:contain;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,0.7);display:block;">

            {{-- Next button --}}
            <button x-show="lightboxSlides.length > 1"
                    @click="lbNext()"
                    style="flex-shrink:0;width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:white;font-size:24px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.15s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.28)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.15)'">&#8250;</button>

        </div>

        {{-- Slide counter --}}
        <div x-show="lightboxSlides.length > 1"
             style="position:absolute;bottom:16px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.5);color:white;font-size:12px;font-weight:600;padding:4px 12px;border-radius:99px;white-space:nowrap;">
            <span x-text="lightboxCur + 1"></span> / <span x-text="lightboxSlides.length"></span>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════
         EMERGENCY CONTACT MODAL
    ════════════════════════════════════════════ --}}
    <div x-show="emergencyOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="position:fixed; inset:0; z-index:50; display:flex; align-items:flex-end; justify-content:center; background:rgba(0,0,0,0.5);"
         @click.self="emergencyOpen = false"
         x-cloak>

        <div x-show="emergencyOpen"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             style="width:100%; max-width:460px; background:white; border-radius:20px 20px 0 0; box-shadow:0 -8px 32px rgba(0,0,0,0.18); overflow:hidden;">

            {{-- Modal header --}}
            <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                    </div>
                    <div>
                        <p style="color:white; font-weight:700; font-size:14px; line-height:1.2;">Kontak Darurat</p>
                        <p style="color:rgba(254,202,202,1); font-size:10px;">Madison Park — Tersedia 24 jam</p>
                    </div>
                </div>
                <button @click="emergencyOpen = false"
                        style="width:28px; height:28px; border-radius:50%; background:rgba(255,255,255,0.2); border:none; color:white; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center;"
                        onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.2)'">✕</button>
            </div>

            {{-- CS Madison Park pinned --}}
            <div style="padding:16px 20px 8px;">
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:#9ca3af; margin-bottom:8px;">Customer Service</p>
                <div style="display:flex; align-items:center; justify-content:space-between; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:12px 14px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:36px; height:36px; border-radius:10px; background:#dcfce7; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1a5c2e" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                            </svg>
                        </div>
                        <div>
                            <p style="font-size:13px; font-weight:700; color:#14532d;">CS Madison Park</p>
                            <p style="font-size:10px; color:#16a34a;">Lobi Apartemen · 08:00–17:00 WIB</p>
                        </div>
                    </div>
                    <a href="https://wa.me/6281234567890" target="_blank"
                       style="display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; color:white; padding:8px 14px; border-radius:8px; background:#1a5c2e; text-decoration:none; transition:background 0.15s;"
                       onmouseover="this.style.background='#14532d'" onmouseout="this.style.background='#1a5c2e'">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WhatsApp
                    </a>
                </div>
            </div>

            {{-- Divider --}}
            <div style="margin:0 20px; height:1px; background:#f3f4f6;"></div>

            {{-- Emergency contacts list --}}
            <div style="padding:8px 20px 20px; max-height:280px; overflow-y:auto;">
                @if($emergencyContacts->count())
                @php $grouped = $emergencyContacts->groupBy('kategori'); @endphp
                @foreach($grouped as $kategori => $contacts)
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:#9ca3af; margin-top:14px; margin-bottom:6px;">{{ $kategori }}</p>
                @foreach($contacts as $c)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f9fafb;">
                    <div style="min-width:0; flex:1;">
                        <p style="font-size:13px; font-weight:600; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $c->nama }}</p>
                        @if($c->alamat)
                        <p style="font-size:10px; color:#9ca3af; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $c->alamat }}</p>
                        @endif
                    </div>
                    <div style="display:flex; align-items:center; gap:6px; flex-shrink:0; margin-left:12px;">
                        @if($c->telp)
                        <a href="tel:{{ $c->telp }}"
                           style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:white; padding:6px 10px; border-radius:8px; background:#dc2626; text-decoration:none; white-space:nowrap;"
                           onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                            </svg>
                            {{ $c->telp }}
                        </a>
                        @endif
                        @if($c->no_wa)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $c->no_wa) }}" target="_blank"
                           style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:white; padding:6px 10px; border-radius:8px; background:#16a34a; text-decoration:none;"
                           onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            WA
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
                @endforeach
                @else
                <div style="padding:32px 0; text-align:center;">
                    <p style="font-size:12px; color:#9ca3af;">Belum ada data kontak darurat.</p>
                    <p style="font-size:10px; color:#d1d5db; margin-top:4px;">Hubungi CS Madison Park untuk informasi lebih lanjut.</p>
                </div>
                @endif
            </div>

        </div>
    </div>

</div>
