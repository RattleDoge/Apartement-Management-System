<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value', 'label', 'group'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function defaults(): array
    {
        return [
            // Profil Apartemen
            ['key' => 'app_nama_apartemen',  'label' => 'Nama Apartemen',      'group' => 'profil', 'value' => 'Madison Park'],
            ['key' => 'app_alamat',          'label' => 'Alamat',               'group' => 'profil', 'value' => ''],
            ['key' => 'app_telp',            'label' => 'No. Telepon',          'group' => 'profil', 'value' => ''],
            ['key' => 'app_email',           'label' => 'Email',                'group' => 'profil', 'value' => ''],
            ['key' => 'app_wa',              'label' => 'WhatsApp CS',          'group' => 'profil', 'value' => ''],

            // Tarif IPL
            ['key' => 'tarif_ipl_per_m2',   'label' => 'Tarif IPL (Rp/m²)',    'group' => 'tarif',  'value' => '0'],

            // Tarif Listrik per VA
            ['key' => 'tarif_listrik_1300',  'label' => 'Tarif Listrik 1300 VA',  'group' => 'tarif', 'value' => '1444.70'],
            ['key' => 'tarif_listrik_2200',  'label' => 'Tarif Listrik 2200 VA',  'group' => 'tarif', 'value' => '1444.70'],
            ['key' => 'tarif_listrik_3500',  'label' => 'Tarif Listrik 3500–5500 VA', 'group' => 'tarif', 'value' => '1699.53'],

            // Tarif Air
            ['key' => 'tarif_air_m3',        'label' => 'Tarif Air (Rp/m³)',    'group' => 'tarif',  'value' => '0'],

            // Denda
            ['key' => 'denda_persen',        'label' => 'Denda (% per bulan)',  'group' => 'denda',  'value' => '2'],
            ['key' => 'denda_batas_hari',    'label' => 'Batas Hari Jatuh Tempo', 'group' => 'denda', 'value' => '20'],

            // Notifikasi Eskalasi WO
            ['key' => 'wo_eskalasi_menit_1', 'label' => 'Eskalasi Level 1 (menit)', 'group' => 'wo', 'value' => '15'],
            ['key' => 'wo_eskalasi_menit_2', 'label' => 'Eskalasi Level 2 (menit)', 'group' => 'wo', 'value' => '60'],
        ];
    }
}
