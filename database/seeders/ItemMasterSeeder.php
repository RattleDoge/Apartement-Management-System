<?php

namespace Database\Seeders;

use App\Models\ItemMaster;
use Illuminate\Database\Seeder;

class ItemMasterSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['kode' => 'W01', 'nama' => 'Nud Keramik',                                          'satuan' => 'M2',        'harga' => 40000,  'kategori' => 'CIVIL'],
            ['kode' => 'W02', 'nama' => 'Man Power Civil',                                       'satuan' => 'jam/orang', 'harga' => 30000,  'kategori' => 'CIVIL'],
            ['kode' => 'W03', 'nama' => 'Cat Interior Unitdinding/Plafond (hanya harga material)','satuan' => 'kg',        'harga' => 45000,  'kategori' => 'PAINTING'],
            ['kode' => 'W04', 'nama' => 'Cat Skirting/Minyak (hanya harga Material)',            'satuan' => 'kg',        'harga' => 45000,  'kategori' => 'PAINTING'],
            ['kode' => 'W05', 'nama' => 'Access Card Baru',                                      'satuan' => 'pcs',       'harga' => 100000, 'kategori' => 'ACCESS CARD'],
            ['kode' => 'W10', 'nama' => 'Pipa PVC 3/4 inch',                                    'satuan' => 'batang',    'harga' => 25000,  'kategori' => 'PLUMBING'],
            ['kode' => 'W11', 'nama' => 'Lampu TL 18W',                                         'satuan' => 'pcs',       'harga' => 35000,  'kategori' => 'ELECTRICAL'],
            ['kode' => 'W12', 'nama' => 'Freon R32',                                            'satuan' => 'kg',        'harga' => 150000, 'kategori' => 'MECHANICAL'],
            ['kode' => 'W13', 'nama' => 'Engsel Pintu',                                         'satuan' => 'pcs',       'harga' => 75000,  'kategori' => 'CIVIL'],
            ['kode' => 'W20', 'nama' => 'Cat Tembok Dulux 5Kg',                                 'satuan' => 'kaleng',    'harga' => 185000, 'kategori' => 'PAINTING'],
            ['kode' => 'W30', 'nama' => 'Seal Karet Wastafel',                                  'satuan' => 'pcs',       'harga' => 15000,  'kategori' => 'PLUMBING'],
            ['kode' => 'W31', 'nama' => 'Kran Air',                                             'satuan' => 'pcs',       'harga' => 85000,  'kategori' => 'PLUMBING'],
            ['kode' => 'W35', 'nama' => 'Man Power Plumbing',                                   'satuan' => 'jam/orang', 'harga' => 30000,  'kategori' => 'PLUMBING'],
            ['kode' => 'W36', 'nama' => 'Man Power Electrical',                                 'satuan' => 'jam/orang', 'harga' => 30000,  'kategori' => 'ELECTRICAL'],
            ['kode' => 'W37', 'nama' => 'Man Power Painting',                                   'satuan' => 'jam/orang', 'harga' => 25000,  'kategori' => 'PAINTING'],
            ['kode' => 'W40', 'nama' => 'Stop Kontak',                                          'satuan' => 'pcs',       'harga' => 45000,  'kategori' => 'ELECTRICAL'],
            ['kode' => 'W41', 'nama' => 'MCB 10A',                                              'satuan' => 'pcs',       'harga' => 65000,  'kategori' => 'ELECTRICAL'],
            ['kode' => 'W50', 'nama' => 'Semen 50kg',                                           'satuan' => 'sak',       'harga' => 65000,  'kategori' => 'CIVIL'],
            ['kode' => 'W51', 'nama' => 'Pasir Halus',                                          'satuan' => 'kg',        'harga' => 5000,   'kategori' => 'CIVIL'],
        ];

        foreach ($items as $item) {
            ItemMaster::create($item);
        }
    }
}
