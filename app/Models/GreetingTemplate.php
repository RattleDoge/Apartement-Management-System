<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GreetingTemplate extends Model
{
    protected $fillable = [
        'nama_template', 'jenis', 'isi',
        'cover_img', 'content_img',
        'status', 'modified_by', 'kirim_count',
    ];

    public static function jenisOptions(): array
    {
        return [
            'News & Event',
            'News Announcement',
            'Informasi Penting',
            'Promo',
        ];
    }
}
