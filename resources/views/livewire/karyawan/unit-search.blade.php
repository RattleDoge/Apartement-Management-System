<?php

use App\Models\Tenant;
use App\Models\HandoverUnit;
use App\Models\Invoice;
use App\Models\TenantRequest;
use App\Models\WorkOrder;
use Livewire\Volt\Component;

new class extends Component
{
    public string $query        = '';
    public array  $suggestions  = [];
    public bool   $showDropdown = false;
    public ?array $unitInfo     = null;

    public function updatedQuery(): void
    {
        $q = trim($this->query);
        if (strlen($q) < 2) {
            $this->suggestions  = [];
            $this->showDropdown = false;
            return;
        }

        $this->suggestions = Tenant::with('user')
            ->where(function ($query) use ($q) {
                $query->where('unit_number', 'like', "%{$q}%")
                      ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%")
                          ->orWhere('first_name', 'like', "%{$q}%")
                          ->orWhere('last_name', 'like', "%{$q}%"));
            })
            ->limit(8)
            ->get()
            ->map(function (Tenant $t) {
                $name = $this->tenantFullName($t);
                return [
                    'unit'   => $t->unit_number,
                    'name'   => $name,
                    'status' => $t->status ?? 'Active',
                ];
            })
            ->toArray();

        $this->showDropdown = ! empty($this->suggestions);
    }

    public function selectUnit(string $unitNumber): void
    {
        $tenant   = Tenant::with('user')
            ->whereRaw('UPPER(unit_number) = ?', [strtoupper($unitNumber)])
            ->first();

        $handover = HandoverUnit::whereRaw('UPPER(lot_no) = ?', [strtoupper($unitNumber)])->first();

        $this->query       = $unitNumber;
        $this->showDropdown = false;
        $this->suggestions  = [];

        if (! $tenant) {
            $this->unitInfo = null;
            return;
        }

        $user = $tenant->user;
        $name = $this->tenantFullName($tenant);

        // Last invoice for this unit
        $lastInv = Invoice::where('debtor_acct', $unitNumber)
            ->orderByDesc('inv_date')
            ->first();

        // Open tenant requests
        $openReqs = TenantRequest::where('lot_no', $unitNumber)
            ->whereNotIn('status', ['Selesai'])
            ->count();

        $this->unitInfo = [
            'unit'             => $tenant->unit_number,
            'status'           => $tenant->status ?? 'Active',
            'name'             => $name,
            'email'            => $user?->email ?? '-',
            'phone'            => $user?->phone ?? '-',
            'dob'              => $tenant->date_of_birth?->format('d M Y') ?? '-',
            'nik'              => $tenant->nik_ktp ?? ($handover?->no_ktp ?? '-'),
            'address'          => $tenant->full_address ?? '-',

            // Handover
            'str_date'         => $handover?->str_date?->format('d M Y') ?? '-',
            'cmg_date'         => $handover?->cmg_date?->format('d M Y') ?? '-',
            'pic'              => $handover?->pic ?? '-',
            'no_intercom'      => $handover?->no_intercom ?? '-',
            'no_telpon'        => $handover?->no_telpon ?? '-',
            'no_access_card'   => $handover?->no_access_card ?? '-',
            'access_card_count'=> $handover?->access_card_count ?? 0,
            'key_count'        => $handover?->key_count ?? 0,
            'ppjb'             => $handover?->ppjb ?? false,
            'bast'             => $handover?->bast ?? false,
            'house_rule'       => $handover?->house_rule ?? false,

            // Quick stats
            'open_requests'    => $openReqs,
            'last_inv_status'  => $lastInv?->status_bayar ?? null,
            'last_inv_amount'  => $lastInv?->amount ?? 0,
        ];
    }

    public function clearSearch(): void
    {
        $this->query        = '';
        $this->suggestions  = [];
        $this->showDropdown = false;
        $this->unitInfo     = null;
    }

    private function tenantFullName(Tenant $tenant): string
    {
        $user = $tenant->user;
        if (! $user) return '-';
        $full = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        return strtoupper($full ?: $user->name ?? '-');
    }
};
?>

<div>
    {{-- ── Search Box ── --}}
    <div class="flex items-center gap-3 mb-3 text-xs">
        <label class="text-gray-600 font-medium shrink-0">Search Unit/Tenant</label>
        <div class="relative" x-data="{ focused: false }">
            <input wire:model.live.debounce.300ms="query"
                   @focus="focused = true"
                   @click.away="focused = false"
                   type="text"
                   placeholder="Contoh: MP/11/AC atau nama..."
                   class="border border-gray-400 px-2 py-1 text-[12px] w-64 rounded-sm focus:outline-none focus:border-[#1a5c2e]" />

            @if($query)
            <button wire:click="clearSearch"
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs">
                ✕
            </button>
            @endif

            {{-- Autocomplete dropdown --}}
            @if($showDropdown && !empty($suggestions))
            <div class="absolute left-0 top-full mt-0.5 w-96 bg-white border border-gray-300 shadow-lg z-50 max-h-48 overflow-y-auto"
                 style="border-top: 2px solid #1a5c2e;">
                @foreach($suggestions as $s)
                <button wire:click="selectUnit('{{ $s['unit'] }}')"
                        class="w-full text-left px-3 py-1.5 text-[12px] hover:bg-[#e8f5e9] border-b border-gray-100 flex items-center justify-between">
                    <span>
                        <span class="font-semibold text-[#1a6b9a]">{{ $s['unit'] }}</span>
                        <span class="text-gray-500 ml-2">{{ $s['name'] }}</span>
                    </span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded {{ $s['status'] === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $s['status'] }}
                    </span>
                </button>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ── Unit Info Card ── --}}
    @if($unitInfo)
    <div class="border border-gray-300 shadow-sm mt-4" style="max-width: 720px;">

        {{-- Header --}}
        <div class="px-4 py-2 flex items-center justify-between"
             style="background: linear-gradient(to bottom, #c5dde2, #b8d4da);">
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-gray-700">Unit : {{ $unitInfo['unit'] }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded font-semibold
                    {{ $unitInfo['status'] === 'Active' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                    {{ $unitInfo['status'] }}
                </span>
            </div>
            <div class="flex items-center gap-3 text-[11px]">
                @if($unitInfo['open_requests'] > 0)
                <span class="bg-yellow-100 text-yellow-800 border border-yellow-200 px-2 py-0.5 rounded font-semibold">
                    {{ $unitInfo['open_requests'] }} request open
                </span>
                @endif
                @if($unitInfo['last_inv_status'])
                <span class="px-2 py-0.5 rounded font-semibold
                    {{ $unitInfo['last_inv_status'] === 'Lunas' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' }}">
                    Invoice: {{ $unitInfo['last_inv_status'] }}
                </span>
                @endif
            </div>
        </div>

        <div class="bg-white p-4 text-[12px]">
            <div class="grid grid-cols-2 gap-x-6">

                {{-- Left: Owner Information --}}
                <div>
                    <p class="font-bold text-gray-700 mb-2 border-b border-gray-200 pb-1">Owner Information</p>
                    <table class="w-full" style="border-collapse: separate; border-spacing: 0 3px;">
                        <colgroup><col style="width: 110px;"><col></colgroup>
                        @foreach([
                            ['Owner',           $unitInfo['name']],
                            ['DOB',             $unitInfo['dob']],
                            ['No. KTP / NIK',   $unitInfo['nik']],
                            ['Email',           $unitInfo['email']],
                            ['Handphone',       $unitInfo['phone']],
                            ['Address',         $unitInfo['address']],
                        ] as [$lbl, $val])
                        <tr>
                            <td class="text-gray-500 align-top pr-2">{{ $lbl }}</td>
                            <td class="text-gray-500 align-top pr-1">:</td>
                            <td class="text-gray-800 align-top font-medium break-words">{{ $val }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                {{-- Right: Handover Information --}}
                <div>
                    <p class="font-bold text-gray-700 mb-2 border-b border-gray-200 pb-1">Handover Information</p>
                    <table class="w-full" style="border-collapse: separate; border-spacing: 0 3px;">
                        <colgroup><col style="width: 130px;"><col></colgroup>
                        @foreach([
                            ['Handover Date',    $unitInfo['str_date']],
                            ['CMG Date',         $unitInfo['cmg_date']],
                            ['Handover Officer', $unitInfo['pic']],
                            ['No. Intercom',     $unitInfo['no_intercom']],
                            ['No. Telpon',       $unitInfo['no_telpon']],
                            ['No. Access Card',  $unitInfo['no_access_card']],
                            ['Jml. Access Card', (string) $unitInfo['access_card_count']],
                            ['Jml. Kunci',       (string) $unitInfo['key_count']],
                        ] as [$lbl, $val])
                        <tr>
                            <td class="text-gray-500 align-top pr-2">{{ $lbl }}</td>
                            <td class="text-gray-500 align-top pr-1">:</td>
                            <td class="text-gray-800 align-top font-medium">{{ $val }}</td>
                        </tr>
                        @endforeach
                    </table>

                    {{-- Document checklist --}}
                    <div class="mt-3 flex gap-3">
                        @foreach([['PPJB', $unitInfo['ppjb']], ['BAST', $unitInfo['bast']], ['House Rule', $unitInfo['house_rule']]] as [$doc, $ok])
                        <span class="flex items-center gap-1 text-[10px] font-semibold
                            {{ $ok ? 'text-green-700' : 'text-red-600' }}">
                            {{ $ok ? '✓' : '✗' }} {{ $doc }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Links --}}
            <div class="mt-4 pt-3 border-t border-gray-100 flex gap-4">
                <a href="{{ route('karyawan.debtor.statement', $unitInfo['unit']) }}"
                   class="text-[#1a5c2e] hover:underline font-medium text-[12px]">
                    View Statement Of Account
                </a>
                <a href="{{ route('karyawan.fa.invoice-list') }}?debtor={{ urlencode($unitInfo['unit']) }}"
                   class="text-[#1a5c2e] hover:underline font-medium text-[12px]">
                    View Invoices Bulan Sekarang
                </a>
                <a href="{{ route('karyawan.cs.tenant-request-belum') }}?lot={{ urlencode($unitInfo['unit']) }}"
                   class="text-[#1a5c2e] hover:underline font-medium text-[12px]">
                    Lihat Tenant Request
                </a>
            </div>
        </div>
    </div>
    @elseif(strlen($query) >= 2 && empty($suggestions))
    <div class="text-[12px] text-gray-400 mt-2">Unit / Tenant tidak ditemukan untuk "<strong>{{ $query }}</strong>"</div>
    @endif
</div>
