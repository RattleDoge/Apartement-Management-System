<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['image_path', 'caption', 'is_active', 'uploaded_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->latest()->first();
    }

    public static function allActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->latest()->get();
    }
}
