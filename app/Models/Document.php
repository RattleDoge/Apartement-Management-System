<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'judul', 'deskripsi', 'kategori', 'file_path', 'is_active', 'uploaded_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public static function kategoriOptions(): array
    {
        return ['Umum', 'Peraturan', 'Formulir', 'Panduan', 'Pengumuman'];
    }
}
