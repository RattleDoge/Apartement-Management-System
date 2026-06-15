<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FacilityReservation extends Model
{
    protected $fillable = [
        'nomor', 'unit', 'tenant_name', 'nama_fasilitas',
        'tanggal_reservasi', 'jam_mulai', 'jam_selesai',
        'keperluan', 'jumlah_tamu', 'is_berbayar', 'biaya',
        'status_bayar', 'bukti_bayar', 'status',
        'request_by', 'request_via', 'catatan', 'foto',
        'cs_by', 'cs_at',
        'fin_by', 'fin_at',
        'hk_by', 'hk_at',
        'eng_by', 'eng_at',
        'sec_open_by', 'sec_open_at',
        'sec_close_by', 'sec_close_at', 'sec_close_catatan',
        'input_by',
        'rr_index', 'rr_officer',
        'qr_token',
    ];

    public function generateQrToken(): void
    {
        if (! $this->qr_token) {
            $this->update(['qr_token' => strtoupper(Str::random(6))]);
        }
    }

    protected function casts(): array
    {
        return [
            'tanggal_reservasi' => 'date',
            'is_berbayar'       => 'boolean',
            'biaya'             => 'decimal:2',
            'cs_at'             => 'datetime',
            'fin_at'            => 'datetime',
            'hk_at'             => 'datetime',
            'eng_at'            => 'datetime',
            'sec_open_at'       => 'datetime',
            'sec_close_at'      => 'datetime',
        ];
    }

    // All pre-event checks required for this reservation are satisfied
    public function isReadyToStart(): bool
    {
        if ($this->is_berbayar && !$this->fin_by) return false;
        return (bool) ($this->hk_by && $this->eng_by);
    }

    // Status is terminal — no further actions needed
    public function isFinalized(): bool
    {
        return in_array($this->status, ['Selesai', 'Ditolak']);
    }

    public static function fasilitasOptions(): array
    {
        $dbNames = \App\Models\Facility::orderBy('nama_fasilitas')->pluck('nama_fasilitas')->toArray();
        if (count($dbNames) > 0) {
            return $dbNames;
        }
        return [
            'Balai Warga', 'Games Room', 'Mini Theater', 'Rooftop Garden',
            'BBQ Area', 'Kolam Renang', 'Gym / Fitness Center', 'Tenis Meja', 'Playground',
        ];
    }

    // Default payment setting per facility — reads from DB if available
    public static function fasilitasBiayaDefault(): array
    {
        $facilities = \App\Models\Facility::all();
        if ($facilities->isNotEmpty()) {
            return $facilities->keyBy('nama_fasilitas')
                ->map(fn($f) => [
                    'is_berbayar' => (bool) $f->is_berbayar,
                    'biaya'       => (float) $f->biaya,
                ])->toArray();
        }
        return [
            'Balai Warga'          => ['is_berbayar' => true,  'biaya' => 500000],
            'Games Room'           => ['is_berbayar' => true,  'biaya' => 750000],
            'Mini Theater'         => ['is_berbayar' => true,  'biaya' => 300000],
            'Rooftop Garden'       => ['is_berbayar' => true,  'biaya' => 200000],
            'BBQ Area'             => ['is_berbayar' => true,  'biaya' => 150000],
            'Kolam Renang'         => ['is_berbayar' => false, 'biaya' => 0],
            'Gym / Fitness Center' => ['is_berbayar' => false, 'biaya' => 0],
            'Tenis Meja'           => ['is_berbayar' => false, 'biaya' => 0],
            'Playground'           => ['is_berbayar' => false, 'biaya' => 0],
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'Pesan Diterima',
            'Disetujui CS',
            'Siap Pelaksanaan',
            'Sedang Berlangsung',
            'Selesai',
            'Ditolak',
        ];
    }

    public static function requestViaOptions(): array
    {
        return ['aplikasi', 'Phone', 'WhatsApp', 'Email', 'Letter', 'Visit', 'FO'];
    }
}
