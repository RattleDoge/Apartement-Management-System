<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['pertanyaan', 'jawaban', 'kategori', 'urutan', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function kategoriOptions(): array
    {
        return ['Umum', 'Pembayaran', 'Fasilitas', 'Work Order', 'Perizinan', 'Lainnya'];
    }
}
