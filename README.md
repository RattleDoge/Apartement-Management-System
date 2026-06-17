# AMS — Apartement Management System
### Apartemen Madison Park

Sistem manajemen apartemen berbasis web yang dibangun sebagai Tugas Akhir (Skripsi). AMS mengelola seluruh operasional Apartemen Madison Park mulai dari work order, tenant request, reservasi fasilitas, invoice, hingga laporan bulanan.

---

## Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend | Laravel 13 |
| Frontend | Livewire 3 Volt (single-file components) |
| Styling | Tailwind CSS + Alpine.js |
| Database | SQLite |
| Build Tool | Vite |
| Server (dev) | Laragon (Apache + PHP 8.3) |

---

## Akun & Role

| Role | Akses | Keterangan |
|------|-------|------------|
| `tenant` | Portal Penghuni | Penghuni/penyewa apartemen |
| `karyawan` | Portal Staff | AM, CS, ENG, FA/FIN, HK, SEC, dll. |

**Departemen karyawan** (nilai yang tersimpan di database adalah singkatan):

| Kode | Kepanjangan | Hak Akses Khusus |
|------|-------------|------------------|
| `AM` | Apartment Manager | Akses semua menu |
| `CS` | Customer Service | CS menu, Greeting, Broadcast, FAQ, Dokumen, Fasilitas |
| `ENG` | Engineering | WO Saya, Preventive Maintenance, Laporan Harian, Work Order, In-Out Permit, Reservasi Fasilitas, Item Master |
| `FA` | Finance & Accounting | Invoice, Statement of Account, Approval |
| `HKP` | Housekeeping | — |
| `SEC` | Security | QR buka/tutup fasilitas |

---

## Luaran Sistem (Minimal 8)

### 1. Sistem Manajemen Work Order
Input WO (Internal/External dengan counter terpisah), assign staff, tracking status FIFO, notifikasi eskalasi otomatis, cetak WO, ekspor CSV.

**WO Berbayar** — WO yang memiliki item & service berbayar mengikuti alur persetujuan:
1. Staff ENG/CS tambahkan item (harga > 0) → WO otomatis menjadi berbayar
2. Tenant melihat rincian tagihan di portal dan upload bukti bayar
3. CS memverifikasi bukti bayar (`cs_status = Verified`)
4. Finance mengkonfirmasi uang masuk di rekening → status berubah **LUNAS** (`fin_status = Approved`)
5. Print WO menampilkan stempel **LUNAS** jika sudah disetujui Finance

**Item & Service tersedia:**
- Biaya Material, Biaya Jasa, Biaya Lembur, dll.
- Access Card — Rp 100.000/pcs
- Deposit Access Card Master — Rp 300.000/pcs

**Eskalasi otomatis** (dijalankan via Windows Task Scheduler setiap menit):
| Kondisi | L1 (Supervisor/Chief) | L2 (Manager/GM) |
|---|---|---|
| WO belum ada assign staff | T + 15 menit | T + 30 menit |
| WO sudah assign tapi belum dikerjakan (`work_started` null) | T + 60 menit | T + 120 menit |

### 2. Sistem Tenant Request & Complain
Tenant ajukan komplain via portal, CS kelola dan update status, counter complain real-time di header karyawan.

### 3. Sistem Reservasi Fasilitas
Tenant booking fasilitas (Balai Warga, Games Room, Mini Theater, Rooftop Garden, BBQ Area, Kolam Renang, Gym, Tenis Meja, Playground), alur approval multi-departemen (CS → FIN → HK → ENG → Security), konfirmasi pembayaran fasilitas berbayar, QR Code buka/tutup fasilitas oleh Security.

### 4. Sistem Perizinan In-Out Permit
Tenant ajukan izin keluar-masuk barang/penghuni, alur approval oleh CS/AM, upload foto.

### 5. Sistem Tagihan & Invoice
Upload massal via CSV, Simple Meter Import format ICMS (E/W per lot), kalkulasi tarif listrik & air otomatis, Statement of Account per unit, tracking status bayar, download PDF invoice, approval WO berbayar oleh Finance.

### 6. Sistem Notifikasi Real-time
Notifikasi database untuk tenant (status WO, greeting, broadcast pesan) dan eskalasi WO untuk staff.

### 7. Sistem Serah Terima Unit
Pencatatan serah terima unit baru, checklist kondisi unit (defect, meter air, invoice air pertama), cetak dokumen. Data STR/CMG date & nomor intercom/akses card sinkron otomatis ke portal tenant.

### 8. Sistem Laporan & Dashboard
Laporan bulanan (akses AM/CS/FA) dengan split WO Internal vs External, statistik reservasi fasilitas per kategori, stacked bar chart WO per bulan, ekspor PDF laporan, Approval Center, grafik WO.

---

## Fitur Tambahan

### Portal Tenant
- Beranda — hero section dengan carousel banner aktif (lightbox zoom + prev/next slide, hanya tampil di beranda)
- Profil Unit — info unit & data personal (Tgl. STR, CMG, intercom sinkron dari data Serah Terima Unit)
- Tracking WO — timeline progress + beri rating bintang
- Cek Invoice — lihat, upload bukti bayar & download PDF tagihan
- Riwayat Bayar — histori invoice per unit
- Jadwal Fasilitas — lihat semua booking fasilitas
- Dokumen Penting — unduh dokumen dari manajemen
- FAQ — accordion tanya jawab dengan filter kategori
- Kontak Darurat — floating button emergency (RS, polisi, PLN, dll.)

### Portal CS / AM
- Work Order — manajemen WO (floating modal form, FIFO queue); WO Close otomatis menyembunyikan dari daftar aktif & menutup Tenant Request terkait; CS memverifikasi bukti bayar WO berbayar sebelum diteruskan ke Finance
- Facility Reservation — manajemen reservasi fasilitas
- In-Out Permit — manajemen izin keluar-masuk
- Tenant Request — kelola komplain tenant; auto-close saat WO terkait di-close
- Broadcast Pesan — kirim notifikasi ke semua / unit tertentu
- Kelola FAQ — CRUD pertanyaan & jawaban
- Kelola Dokumen — upload dokumen untuk tenant
- Emergency List — CRUD kontak darurat (nama, alamat, kategori, telepon, WhatsApp)
- Greeting — kirim greeting card ke tenant
- Banner / Pengumuman — upload & kelola banner carousel untuk halaman beranda tenant (toggle aktif/nonaktif)

### Portal Finance
- Invoice — upload massal CSV, Statement of Account; kartu ringkasan hanya menampilkan **Belum Lunas** dan **Sudah Lunas**
- WO Approval — approval akhir Work Order berbayar (setelah CS verifikasi → FA konfirmasi uang masuk → LUNAS)
- Permit Approval — approval In-Out Permit
- Facility Approval — konfirmasi pembayaran reservasi fasilitas

### Portal Engineering
- WO Saya — daftar WO yang ditugaskan ke saya
- Preventive Maintenance — jadwal PM dengan status
- Laporan Harian — catatan kegiatan harian per staff

### Setup (Admin)
- Master Fasilitas — kelola data fasilitas (kapasitas, jam buka, icon, harga sewa — edit harga hanya oleh AM/CS)
- Table Staff — master data staff lapangan untuk assignment WO
- Serah Terima Unit — data unit yang sudah diserahterimakan
- Checklist Unit — checklist kondisi unit saat serah terima
- Debtor List / Statement of Account — daftar piutang & riwayat tagihan per unit
- Kelola Karyawan — data karyawan & departemen

---

## Penomoran Dokumen

| Dokumen | Format | Keterangan |
|---------|--------|------------|
| WO External | `EX00001/VI/2026-MAP` | Counter terpisah dari WO Internal |
| WO Internal | `IN00001/VI/2026-MAP` | Counter terpisah dari WO External |
| Tenant Request | `R0000001/VI/2026-MAP` | Urut dari 1 |
| In-Out Permit | `KMB1/VI/2026-MAP` | Urut dari 1 |
| Facility Reservation | `FRS1/VI/2026-MAP` | Urut dari 1 |

---

## Instalasi (Development)

```bash
cd c:\laragon\www\TA

composer install
npm install
php artisan migrate
npm run dev
```

### Setup Scheduler (Notifikasi Eskalasi WO)

Eskalasi WO membutuhkan Laravel Scheduler berjalan setiap menit. Di Windows/Laragon, daftarkan via **Windows Task Scheduler** (jalankan sekali saja):

```powershell
$action  = New-ScheduledTaskAction -Execute "C:\laragon\www\TA\run-scheduler.bat"
$trigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At (Get-Date)
$settings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Minutes 1) -MultipleInstances IgnoreNew
Register-ScheduledTask -TaskName "Laravel Scheduler - AMS TA" -Action $action -Trigger $trigger -Settings $settings -Force
```

File `run-scheduler.bat` sudah tersedia di root project. Log scheduler tersimpan di `storage/logs/scheduler.log`.

Untuk test eskalasi manual:
```bash
php artisan wo:check-escalation --dry-run   # preview saja
php artisan wo:check-escalation             # kirim notifikasi sungguhan
```

Buat akun karyawan via tinker:

```bash
php artisan tinker
```

```php
// Buat user karyawan AM
$u = \App\Models\User::create([
    'name'               => 'Nama AM',
    'first_name'         => 'Nama',
    'last_name'          => 'AM',
    'email'              => 'am@ams.com',
    'role'               => 'karyawan',
    'password'           => bcrypt('password123'),
    'email_verified_at'  => now(),
]);
\App\Models\Karyawan::create([
    'user_id'      => $u->id,
    'nik_karyawan' => '001',
    'departemen'   => 'AM',
    'jabatan'      => 'Manager',
]);

// Buat akun tenant
$t = \App\Models\User::create([
    'name'              => 'Nama Tenant',
    'first_name'        => 'Nama',
    'last_name'         => 'Tenant',
    'email'             => 'tenant@ams.com',
    'role'              => 'tenant',
    'password'          => bcrypt('password123'),
    'email_verified_at' => now(),
]);
\App\Models\Tenant::create([
    'user_id'     => $t->id,
    'unit_number' => 'MP/01/AA',
    'status'      => 'pemilik',
]);
```

URL dev: **http://localhost/TA/public/login**

---

## Struktur Route

```
/login                              Halaman login
/register/tenant                    Registrasi akun tenant baru
/register/karyawan                  Registrasi akun karyawan baru
/profile                            Profil akun

/notifications                      Daftar notifikasi (semua role)

/tenant/                            Dashboard tenant
/tenant/request                     Tenant request / komplain
/tenant/in-out-permit               Izin keluar-masuk
/tenant/facility-reservation        Reservasi fasilitas
/tenant/cek-invoice                 Cek invoice
/tenant/invoice/{id}/pdf            Download PDF invoice (tenant)
/tenant/profil-unit                 Profil unit
/tenant/tracking-wo                 Tracking work order
/tenant/riwayat-bayar               Riwayat pembayaran
/tenant/jadwal-fasilitas            Jadwal fasilitas
/tenant/dokumen-penting             Dokumen penting
/tenant/faq                         FAQ

/karyawan/fasilitas                 Master data fasilitas (AM/CS)
/karyawan/setup                     Setup sistem (AM/CS)
/karyawan/table-staff               Master staff (AM/CS)
/karyawan/emergency                 Emergency list / kontak darurat (AM/CS)
/karyawan/serah-terima              Serah terima unit (AM/CS)
/karyawan/checklist-unit            Checklist unit (AM/CS)
/karyawan/checklist-unit/{id}/print Print checklist unit
/karyawan/debtor                    Debtor list (AM/CS/FA)
/karyawan/debtor/{acct}/statement   Statement of Account per unit
/karyawan/debtor/{acct}/statement/pdf  Download PDF Statement of Account
/karyawan/laporan-bulanan           Laporan bulanan (AM/CS/FA)
/karyawan/laporan-bulanan/pdf       Download PDF laporan bulanan
/karyawan/approval-center           Approval center (AM/CS)
/karyawan/broadcast-pesan           Broadcast pesan (AM/CS)
/karyawan/kelola-faq                Kelola FAQ (AM/CS)
/karyawan/kelola-dokumen            Kelola dokumen (AM/CS)
/karyawan/wo-saya                   WO saya (ENG)
/karyawan/preventive-maintenance    Jadwal PM (ENG)
/karyawan/laporan-harian            Laporan harian (ENG)

/karyawan/cs/work-order             Work Order (AM/CS/ENG/FA/HKP/SEC)
/karyawan/cs/work-order/{id}/print  Print WO
/karyawan/cs/work-order/{id}/pdf    Download PDF WO
/karyawan/cs/work-order-close       WO Close
/karyawan/cs/work-order-report      Laporan WO
/karyawan/cs/work-order-report/pdf  Download PDF laporan WO
/karyawan/cs/work-order-report/download  Ekspor CSV laporan WO
/karyawan/cs/tenant-request-belum   Request belum selesai
/karyawan/cs/tenant-request-selesai Request selesai
/karyawan/cs/facility-reservation   Reservasi fasilitas (AM/CS/ENG/HKP/SEC)
/karyawan/cs/in-out-permit          In-Out Permit (AM/CS/ENG/FA/SEC)
/karyawan/cs/item-master            Item & Service master (AM/CS/ENG/FA)
/karyawan/cs/grafik                 Grafik WO

/karyawan/greeting/dashboard        Dashboard greeting (AM/CS)
/karyawan/greeting/template         Template greeting (AM/CS)
/karyawan/greeting/banner           Kelola banner / pengumuman (AM/CS)

/karyawan/fa/invoice                Invoice (AM/FA)
/karyawan/fa/invoice-template       Download template CSV invoice
/karyawan/fa/invoice/{id}/pdf       Download PDF invoice (admin)
/karyawan/fa/wo-approval            Approval WO berbayar (AM/FA)
/karyawan/fa/permit-approval        Approval In-Out Permit (AM/FA)
/karyawan/fa/facility-approval      Approval pembayaran fasilitas (AM/FA)

/karyawan/qr-scan/{token}           QR Scan buka/tutup fasilitas (AM/CS/SEC)
/karyawan/qr-scan/{token}/buka      POST — konfirmasi buka fasilitas
/karyawan/qr-scan/{token}/tutup     POST — konfirmasi tutup fasilitas
```

---

## Informasi Proyek

| | |
|--|--|
| **Nama** | Apartement Management System (AMS) |
| **Lokasi** | Apartemen Madison Park |
| **Jenis** | Tugas Akhir / Skripsi |
| **Database** | SQLite — `database/database.sqlite` |
| **URL Dev** | http://localhost/TA/public |
| **PHP** | 8.3 (Laragon) |
| **Laravel** | 13 |
