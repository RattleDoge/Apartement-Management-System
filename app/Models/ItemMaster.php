<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemMaster extends Model
{
    protected $fillable = ['kode', 'nama', 'satuan', 'harga', 'kategori'];

    public function getDropdownLabelAttribute(): string
    {
        $prefix = $this->kode ? $this->kode . ' *** ' : '';
        return $prefix . $this->nama . ' price : ' . number_format($this->harga, 0, '.', '');
    }
}
