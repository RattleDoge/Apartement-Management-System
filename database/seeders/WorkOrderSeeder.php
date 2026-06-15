<?php

namespace Database\Seeders;

use App\Models\WorkOrder;
use Illuminate\Database\Seeder;

class WorkOrderSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            [
                'ex_in' => 'IN', 'no_complain' => null,
                'no_wo' => 'IN03532/V/2026-MAP', 'jenis_wo' => 'CIVIL', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-22 13:15:00', 'estimated_close' => '2026-06-05',
                'lot_no' => 'BM', 'name' => 'Building Management',
                'descs' => 'PENGUPASAN CAT EX AMBULANCE LANTAI P1',
                'status_comp' => null, 'durasi' => '1 Menit', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'FO', 'request_via' => 'Visit', 'assign_dep' => 'ENG', 'assign_staff' => null,
                'item_service' => [['nama' => 'Nud Keramik', 'harga' => 40000, 'qty' => 1], ['nama' => 'Man Power civil', 'harga' => 30000, 'qty' => 2]],
                'input_by' => 'adit',
            ],
            [
                'ex_in' => 'EX', 'no_complain' => 'R1002890/V/2026-MAP',
                'no_wo' => 'EX02990/V/2026-MAP', 'jenis_wo' => 'CIVIL', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-22 12:01:00', 'estimated_close' => '2026-06-05',
                'lot_no' => 'MP/29/AN', 'name' => 'ANNIE DARMAWAN',
                'descs' => 'PERBAIKAN NAT KAMAR MANDI DAN PERAPIHAN PLAFOND UNIT BAWAH',
                'status_comp' => 'Dalam Pengecekan', 'durasi' => '1 Jam 15 Menit', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'ANNIE DARMAWAN', 'request_via' => 'WhatsApp', 'assign_dep' => 'ENG', 'assign_staff' => 'ICONDRI ADI BRATA',
                'item_service' => [['nama' => 'Cat Interior Unitdinding/Plafond (hanya harga material)', 'harga' => 0, 'qty' => 1], ['nama' => 'Cat Skirting/Minyak (hanya harga Material)', 'harga' => 450000, 'qty' => 1]],
                'input_by' => 'yella',
            ],
            [
                'ex_in' => 'EX', 'no_complain' => 'R1002889/V/2026-MAP',
                'no_wo' => 'EX02989/V/2026-MAP', 'jenis_wo' => 'PERGANTIAN ACCESS CARD', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-22 11:59:00', 'estimated_close' => '2026-06-05',
                'lot_no' => 'MP/06/BA', 'name' => 'MELY YANTI',
                'descs' => 'PEMBLOKIRAN ACCESS CARD KARENA RUSAK DENGAN NO 065-17259 DAN PEMBUATAN SERTA PEMROGRAMAN ACCESS CARD BARU DENGAN NO 038.31398',
                'status_comp' => 'Dalam Pengecekan', 'durasi' => '1 Jam 17 Menit', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'MELY YANTI', 'request_via' => 'Phone', 'assign_dep' => 'ENG', 'assign_staff' => null,
                'item_service' => [['nama' => 'Access Card Baru', 'harga' => 100000, 'qty' => 1]],
                'input_by' => 'yella',
            ],
            [
                'ex_in' => 'IN', 'no_complain' => null,
                'no_wo' => 'IN03531/V/2026-MAP', 'jenis_wo' => 'ELECTRICAL', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-22 11:44:00', 'estimated_close' => '2026-06-05',
                'lot_no' => 'CGF/CR-1', 'name' => 'ELLY GWANDY',
                'descs' => 'MATIKAN LISTRIK OTS 1 BULAN WE Rp39.746+ MFEE Rp0 + DENDA Rp0 = Rp39.746',
                'status_comp' => null, 'durasi' => '1 Jam 32 Menit', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'FINANCE', 'request_via' => 'Letter', 'assign_dep' => 'ENG', 'assign_staff' => null,
                'item_service' => null, 'input_by' => 'yella',
            ],
            [
                'ex_in' => 'IN', 'no_complain' => null,
                'no_wo' => 'IN03530/V/2026-MAP', 'jenis_wo' => 'WATER / ELECTRICITY', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-22 11:30:00', 'estimated_close' => '2026-06-05',
                'lot_no' => 'KGF/C15', 'name' => 'JENNY KUMALA PRASETYO',
                'descs' => 'MATIKAN AIR OTS 1 BULAN WE Rp442.378 + MFEE Rp0 + DENDA Rp0 = Rp442.378 REQ: FINANCE',
                'status_comp' => null, 'durasi' => '1 Jam 46 Menit', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'FINANCE', 'request_via' => 'Letter', 'assign_dep' => 'ENG', 'assign_staff' => null,
                'item_service' => null, 'input_by' => 'yella',
            ],
            [
                'ex_in' => 'EX', 'no_complain' => 'R1002888/V/2026-MAP',
                'no_wo' => 'EX02988/V/2026-MAP', 'jenis_wo' => 'PLUMBING', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-22 10:45:00', 'estimated_close' => '2026-06-05',
                'lot_no' => 'MP/12/C', 'name' => 'BUDI SANTOSO',
                'descs' => 'PERBAIKAN KEBOCORAN PIPA AIR KAMAR MANDI UTAMA',
                'status_comp' => 'Dalam Proses', 'durasi' => '2 Jam 30 Menit', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'BUDI SANTOSO', 'request_via' => 'WhatsApp', 'assign_dep' => 'ENG', 'assign_staff' => 'RUDI HARTONO',
                'item_service' => [['nama' => 'Pipa PVC 3/4 inch', 'harga' => 25000, 'qty' => 3]],
                'input_by' => 'adit',
            ],
            [
                'ex_in' => 'IN', 'no_complain' => null,
                'no_wo' => 'IN03529/V/2026-MAP', 'jenis_wo' => 'ELECTRICAL', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-22 09:00:00', 'estimated_close' => '2026-06-05',
                'lot_no' => 'MP/07/AB', 'name' => 'SITI RAHAYU',
                'descs' => 'PENGGANTIAN LAMPU TL KORIDOR LANTAI 7',
                'status_comp' => 'Pesan Diterima', 'durasi' => '4 Jam 15 Menit', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'SECURITY', 'request_via' => 'Visit', 'assign_dep' => 'ENG', 'assign_staff' => null,
                'item_service' => [['nama' => 'Lampu TL 18W', 'harga' => 35000, 'qty' => 4]],
                'input_by' => 'adit',
            ],
            [
                'ex_in' => 'EX', 'no_complain' => 'R1002885/V/2026-MAP',
                'no_wo' => 'EX02985/V/2026-MAP', 'jenis_wo' => 'MECHANICAL', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-21 14:00:00', 'estimated_close' => '2026-06-04',
                'lot_no' => 'MP/35/B', 'name' => 'DEWI SUSANTI',
                'descs' => 'PERBAIKAN AC SPLIT 1.5 PK UNIT TIDAK DINGIN',
                'status_comp' => 'Dalam Pengecekan', 'durasi' => '23 Jam', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'DEWI SUSANTI', 'request_via' => 'Phone', 'assign_dep' => 'ENG', 'assign_staff' => 'AGUS SETIAWAN',
                'item_service' => [['nama' => 'Freon R32', 'harga' => 150000, 'qty' => 1]],
                'input_by' => 'adit',
            ],
            [
                'ex_in' => 'IN', 'no_complain' => null,
                'no_wo' => 'IN03520/V/2026-MAP', 'jenis_wo' => 'CIVIL', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-21 09:30:00', 'estimated_close' => '2026-06-04',
                'lot_no' => 'GF/LOBBY', 'name' => 'Building Management',
                'descs' => 'PERBAIKAN PINTU LOBBY UTAMA TIDAK BISA MENUTUP SEMPURNA',
                'status_comp' => 'Selesai', 'durasi' => '1 Hari 2 Jam', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'SECURITY', 'request_via' => 'Visit', 'assign_dep' => 'ENG', 'assign_staff' => 'BUDI PRATAMA',
                'item_service' => [['nama' => 'Engsel Pintu', 'harga' => 75000, 'qty' => 2]],
                'input_by' => 'adit',
            ],
            [
                'ex_in' => 'EX', 'no_complain' => 'R1002870/V/2026-MAP',
                'no_wo' => 'EX02970/V/2026-MAP', 'jenis_wo' => 'GENERAL', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-20 11:00:00', 'estimated_close' => '2026-06-03',
                'lot_no' => 'MP/18/AN', 'name' => 'RIZKY PRATAMA',
                'descs' => 'PEMBERSIHAN SALURAN AIR TERSUMBAT DI DAPUR',
                'status_comp' => 'Selesai', 'durasi' => '2 Hari', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'RIZKY PRATAMA', 'request_via' => 'WhatsApp', 'assign_dep' => 'HK', 'assign_staff' => 'SUTRISNO',
                'item_service' => null, 'input_by' => 'yella',
            ],
            [
                'ex_in' => 'IN', 'no_complain' => null,
                'no_wo' => 'IN03528/V/2026-MAP', 'jenis_wo' => 'WATER / ELECTRICITY', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-22 10:15:00', 'estimated_close' => '2026-06-05',
                'lot_no' => 'KGF/A02', 'name' => 'HARTONO WIJAYA',
                'descs' => 'MATIKAN AIR OTS 1 BULAN WE Rp185.000 + DENDA Rp0 REQ: FINANCE',
                'status_comp' => null, 'durasi' => '3 Jam 5 Menit', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'FINANCE', 'request_via' => 'Letter', 'assign_dep' => 'ENG', 'assign_staff' => null,
                'item_service' => null, 'input_by' => 'adit',
            ],
            [
                'ex_in' => 'EX', 'no_complain' => 'R1002887/V/2026-MAP',
                'no_wo' => 'EX02987/V/2026-MAP', 'jenis_wo' => 'PAINTING', 'sub_jenis_wo' => null,
                'tanggal' => '2026-05-22 08:30:00', 'estimated_close' => '2026-06-05',
                'lot_no' => 'MP/45/CD', 'name' => 'LINA MARLINA',
                'descs' => 'PENGECATAN ULANG DINDING RUANG TAMU YANG MENGELUPAS',
                'status_comp' => 'Pesan Diterima', 'durasi' => '5 Jam 45 Menit', 'durasi_bln' => 'kurang1bln',
                'request_by' => 'LINA MARLINA', 'request_via' => 'Email', 'assign_dep' => 'ENG', 'assign_staff' => null,
                'item_service' => [['nama' => 'Cat Tembok Dulux 5Kg', 'harga' => 185000, 'qty' => 2]],
                'input_by' => 'adit',
            ],
        ];

        foreach ($records as $record) {
            WorkOrder::create($record);
        }
    }
}
