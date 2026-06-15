<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.karyawan')] class extends Component {

    public string $dateFrom   = '';
    public string $dateUntil  = '';
    public ?int   $totalRows  = null;
    public string $errorMsg   = '';
    public string $lastFormat = '';

    public function mount(): void
    {
        $this->dateFrom  = now()->startOfMonth()->format('Y-m-d');
        $this->dateUntil = now()->format('Y-m-d');
    }

    private function checkInput(): bool
    {
        $this->errorMsg = '';
        $this->totalRows = null;

        if (empty($this->dateFrom) || empty($this->dateUntil)) {
            $this->errorMsg = 'Harap isi tanggal From dan Until.';
            return false;
        }
        if ($this->dateFrom > $this->dateUntil) {
            $this->errorMsg = 'Tanggal From tidak boleh lebih besar dari Until.';
            return false;
        }

        $count = \App\Models\WorkOrder::whereDate('tanggal', '>=', $this->dateFrom)
            ->whereDate('tanggal', '<=', $this->dateUntil)
            ->count();

        if ($count === 0) {
            $this->errorMsg = 'Tidak ada data Work Order pada rentang tanggal tersebut.';
            return false;
        }

        $this->totalRows = $count;
        return true;
    }

    public function export(): void
    {
        if (!$this->checkInput()) return;

        $this->lastFormat = 'CSV';
        $url = route('karyawan.cs.work-order-report.download', [
            'from'  => $this->dateFrom,
            'until' => $this->dateUntil,
        ]);

        $this->js("window.open('{$url}', '_blank')");
    }

    public function exportPdf(): void
    {
        if (!$this->checkInput()) return;

        $this->lastFormat = 'PDF';
        $url = route('karyawan.cs.work-order-report.pdf', [
            'from'  => $this->dateFrom,
            'until' => $this->dateUntil,
        ]);

        $this->js("window.open('{$url}', '_blank')");
    }
} ?>

<div class="p-4">

    {{-- Title bar --}}
    <div class="text-white text-sm font-bold px-3 py-1.5 mb-4 rounded-md inline-block" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);">
        Work Order Report
    </div>

    {{-- Filter panel --}}
    <div class="border border-gray-300 bg-white p-4 max-w-lg">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-semibold text-gray-700">WO Date</span>

            <span class="text-sm text-gray-600">From</span>
            <input
                type="date"
                wire:model="dateFrom"
                class="border border-gray-400 text-sm px-2 py-1 focus:outline-none focus:border-blue-500"
            />

            <span class="text-sm text-gray-600">Until</span>
            <input
                type="date"
                wire:model="dateUntil"
                class="border border-gray-400 text-sm px-2 py-1 focus:outline-none focus:border-blue-500"
            />

            <button
                wire:click="export"
                wire:loading.attr="disabled"
                class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-bold px-4 py-1 cursor-pointer"
            >
                <span wire:loading.remove wire:target="export">CSV</span>
                <span wire:loading wire:target="export">...</span>
            </button>
            <button
                wire:click="exportPdf"
                wire:loading.attr="disabled"
                class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm font-bold px-4 py-1 cursor-pointer"
            >
                <span wire:loading.remove wire:target="exportPdf">PDF</span>
                <span wire:loading wire:target="exportPdf">...</span>
            </button>
        </div>

        @if($errorMsg)
            <p class="mt-2 text-red-600 text-xs">{{ $errorMsg }}</p>
        @endif

        @if($totalRows !== null && !$errorMsg)
            <p class="mt-2 text-green-700 text-xs">
                Mengekspor {{ number_format($totalRows) }} baris data...
                File {{ $lastFormat ?: 'CSV' }} akan terunduh otomatis di tab baru.
            </p>
        @endif
    </div>

    {{-- Info --}}
    <div class="mt-4 text-xs text-gray-500 max-w-lg">
        <p>File CSV yang diunduh dapat dibuka dengan Microsoft Excel.</p>
        <p class="mt-1">Rentang: <strong>{{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : '-' }}</strong>
            s/d <strong>{{ $dateUntil ? \Carbon\Carbon::parse($dateUntil)->format('d M Y') : '-' }}</strong></p>
    </div>

</div>
