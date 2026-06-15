<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantRequest extends Model
{
    protected $fillable = [
        'no_request', 'tanggal', 'tgl_verifikasi', 'tgl_dalam_proses', 'tgl_selesai',
        'lot_no', 'nama', 'kepemilikan', 'sales_agent', 'kategori', 'sub_kategori',
        'pelaporan_via', 'descs', 'request_by', 'status', 'berulang',
        'tgl_str', 'desc_status', 'input_by', 'is_selesai', 'alasan_tidak_aplikasi',
        'foto', 'done_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'          => 'datetime',
            'tgl_verifikasi'   => 'date',
            'tgl_dalam_proses' => 'date',
            'tgl_selesai'      => 'date',
            'tgl_str'          => 'date',
            'is_selesai'       => 'boolean',
        ];
    }

    public static function kategoriOptions(): array
    {
        return [
            'Unit Rumah/Ruko', 'Access Card', 'Lift / Elevator',
            'Air & Listrik', 'Fasilitas Umum', 'Keamanan', 'Lainnya',
        ];
    }

    public static function subKategoriOptions(): array
    {
        return [
            'UNIT COMPLAIN', 'CIVIL', 'ELECTRICAL', 'PLUMBING',
            'MECHANICAL', 'HVAC', 'ACCESS CARD', 'GENERAL', 'PAINTING',
            'LIFT', 'WATER / ELECTRICITY', 'SECURITY', 'LAINNYA',
        ];
    }

    public static function pelaporanViaOptions(): array
    {
        return ['aplikasi', 'Phone', 'WhatsApp', 'Email', 'Letter', 'Visit', 'FO'];
    }

    public static function statusOptions(): array
    {
        return [
            'Pesan Diterima', 'Dalam Pengecekan', 'Dalam Proses',
            'Selesai', 'Tidak Dapat Diaplikasi',
        ];
    }

    public static function berulangOptions(): array
    {
        return ['Tidak', 'Ya'];
    }
}
