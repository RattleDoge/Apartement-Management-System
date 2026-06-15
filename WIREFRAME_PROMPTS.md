# Wireframe Prompts — AMS Madison Park
## Siap di-paste ke Uizard / Visily / Galileo AI

---

## CARA PAKAI
1. Buka **Uizard** (uizard.io) atau **Visily** (visily.ai)
2. Pilih "Generate from text" atau "AI Wireframe"
3. Copy salah satu prompt di bawah → Paste → Generate
4. Export ke Figma atau PNG

---

## HALAMAN 1 — LOGIN

```
Web app login page for Apartment Management System (AMS) Madison Park.

Layout: Centered card on a dark blue gradient background.

Elements:
- Top: Logo placeholder circle + text "AMS Madison Park" below it
- Subtitle: "Sistem Manajemen Apartemen"
- Email input field with label "Email"
- Password input field with label "Password" and show/hide toggle icon
- Blue "Masuk" (Login) button, full width
- Small text below button: "Lupa password?"
- Footer text: "Apartemen Madison Park © 2026"

Style: Clean, minimal, corporate blue theme. Card has subtle shadow.
```

---

## HALAMAN 2 — DASHBOARD TENANT (PENGHUNI)

```
Web app dashboard for apartment tenant (resident) portal.

Layout: Top navigation bar + full width content below.

Top Navigation Bar:
- Left: Logo "AMS" + text "Madison Park"
- Center: Navigation links — Dashboard, WO, Gate Pass, Fasilitas, Invoice, FAQ
- Right: Bell notification icon + Avatar circle with user name

Hero Section (below nav):
- Large banner/carousel area (16:5 ratio) with placeholder image
- Dots indicator below banner for multiple slides
- Text overlay: "Selamat Datang, [Nama Penghuni]" + unit number badge

Quick Access Cards (4 cards in a row):
- Card 1: "Tenant Request" icon + number badge (pending count)
- Card 2: "Work Order" icon + status label
- Card 3: "Reservasi Fasilitas" icon + next booking info
- Card 4: "Cek Invoice" icon + outstanding amount

Recent WO Status Section:
- Section title: "Status Work Order Terakhir"
- Small table: No.WO | Deskripsi | Status (colored badge) | Tanggal
- "Lihat Semua" link at bottom right

Emergency Contact floating button:
- Bottom right: Red round button with phone icon, always visible
```

---

## HALAMAN 3 — WORK ORDER (FIFO) — PORTAL KARYAWAN

```
Web app work order management page for staff (Customer Service/AM).

Layout: Top navigation bar + sidebar (left) + main content (right).

Top Bar:
- Logo + "AMS Madison Park"
- Right: Notification bell with badge + staff name + department label (CS/AM)

Sidebar (left, narrow):
- Navigation menu items (vertical list):
  Work Order (active, highlighted blue)
  WO Close
  Tenant Request
  Reservasi Fasilitas
  In-Out Permit
  Laporan Bulanan

Main Content — Work Order List:
- Page title: "WORK ORDER" with blue gradient header bar
- Sub-tabs: "WO Aktif" (active) | "Antrian FIFO"
- Top action bar: Blue "+ Input WO" button (right side) + search box (left)
- Filter row: dropdown filter by Status | Departemen | Jenis WO

Data Table (FIFO queue — ordered by date ascending):
Column headers: No. | No. WO | Tanggal | Jenis | Lot No | Deskripsi | Status | Assign | Aksi

Table rows (5–6 rows visible):
- Row 1 (oldest — top of FIFO): EX00001/V/2026-MAP | 01 Mei | ELE | MP/01/AA | AC tidak dingin | Badge "Dalam Proses" (yellow) | Budi | [Edit] [Close]
- Row 2: EX00002/V/2026-MAP | 03 Mei | CIVIL | MP/02/BB | Pintu rusak | Badge "Pesan Diterima" (gray) | — | [Edit] [Close]
- Row 3: EX00003/V/2026-MAP | 05 Mei | ME | MP/03/CC | Kran bocor | Badge "Dalam Pengecekan" (blue) | Andi | [Edit] [Close]

Footer info: "Menampilkan 3 dari 12 antrian WO aktif" + FIFO label badge

Right Panel (slide-in on "+ Input WO" click):
- Form title: "Input Work Order Baru"
- Fields: Jenis (EX/IN toggle) | Jenis WO (dropdown: CIVIL/ME/ELE/dll) | Lot No | Nama Pelapor | Deskripsi (textarea) | Estimated Close (date picker) | Assign Departemen | Assign Staff
- Buttons: "Simpan" (blue) | "Batal" (gray)
```

---

## HALAMAN 4 — IN-OUT PERMIT / GATE PASS (FIFO) — PORTAL TENANT

```
Web app In-Out Permit (Gate Pass) submission page for apartment tenant.

Layout: Top navigation bar + centered form content.

Top Bar: Same as tenant dashboard.

Page Title: "IZIN KELUAR-MASUK BARANG" with blue header bar.

Tabs:
- "Ajukan Baru" (active)
- "Riwayat Pengajuan"

Form Section — Ajukan Baru:
- Info box (light blue): "Pengajuan akan diproses sesuai urutan masuk (FIFO)"
- Field: Jenis Izin — radio toggle: [Keluar] [Masuk]
- Field: Tanggal Pelaksanaan — date picker
- Field: Keterangan Barang/Orang — textarea
- Field: Upload Foto (optional) — dashed box "Drag foto atau klik upload"
- Submit button: "Ajukan Izin" (blue, full width)

Section Below — Riwayat (summary table):
Table: No. | No. Permit | Tanggal | Jenis | Status | Aksi
Row example: 1 | KMB1/VI/2026-MAP | 01 Jun | Keluar | Badge "Menunggu Approval" (orange) | [Detail]
```

---

## HALAMAN 5 — IN-OUT PERMIT — PORTAL KARYAWAN (CS)

```
Web app Gate Pass management page for Customer Service staff.

Layout: Sidebar + main content. Same nav as Work Order page.

Page Title: "IN-OUT PERMIT" with blue gradient header.

Top action bar:
- Search box (left)
- Filter dropdown: Status | Tanggal

FIFO Queue Table:
Column headers: No. | No. Permit | Tanggal Ajuan | Unit | Nama | Jenis | Keterangan | Status | Aksi

Rows (sorted by Tanggal ASC — oldest first, FIFO):
- Row 1: KMB1/VI/2026-MAP | 30 Mei | MP/01/AA | Siti | Keluar | Sofa, kulkas | Badge "Menunggu" (gray) | [Approve] [Tolak]
- Row 2: KMB2/VI/2026-MAP | 01 Jun | MP/03/BB | Budi | Masuk | Peralatan kantor | Badge "Menunggu" (gray) | [Approve] [Tolak]

Detail Panel (slide-in when row clicked):
- All permit details
- Foto attachment preview
- Timeline: Diajukan → Approved CS → Approved Security
- Action buttons: Approve | Tolak
```

---

## HALAMAN 6 — RESERVASI FASILITAS (ROUND ROBIN) — PORTAL TENANT

```
Web app Facility Reservation booking page for apartment tenant.

Layout: Top navigation bar + two-column layout.

Page Title: "RESERVASI FASILITAS"

Left Column — Pilih Fasilitas:
- Grid of facility cards (2 columns):
  Card: Icon + "Balai Warga" + capacity "50 org" + time "08.00–22.00" + price "Rp 500.000"
  Card: Icon + "Games Room" + capacity "20 org" + time "08.00–22.00" + price "Gratis"
  Card: Icon + "Mini Theater" + capacity "30 org" + time "09.00–21.00" + price "Rp 300.000"
  Card: Icon + "Rooftop Garden" + capacity "40 org" + time "07.00–22.00" + price "Rp 200.000"
  Card: Icon + "Kolam Renang" + capacity "unlimited" + time "06.00–20.00" + price "Gratis"
  Card: Icon + "Gym" + capacity "15 org" + time "05.00–22.00" + price "Gratis"
- Selected card highlighted with blue border

Right Column — Form Booking:
- Section: "Detail Reservasi"
- Field: Fasilitas terpilih (read-only badge)
- Field: Tanggal Reservasi — date picker
- Field: Jam Mulai — time dropdown
- Field: Jam Selesai — time dropdown
- Field: Jumlah Tamu — number input
- Field: Keperluan — textarea
- Field: Upload Bukti Bayar (for paid facilities) — file upload box
- Info box (green): "Officer yang bertugas akan ditentukan otomatis sistem"
- Button: "Buat Reservasi" (blue, full width)

Below — Jadwal Booking (calendar-style):
- Mini calendar showing booked slots
- Legend: Tersedia (green) | Sudah Booked (red) | Saya (blue)
```

---

## HALAMAN 7 — RESERVASI FASILITAS (ROUND ROBIN) — PORTAL KARYAWAN

```
Web app Facility Reservation approval management page for staff.

Layout: Sidebar + main content.

Page Title: "KELOLA RESERVASI FASILITAS" with blue header.

Summary Cards (top, 4 cards):
- "Menunggu Approval CS" — count badge (number)
- "Menunggu Konfirmasi Bayar" — count badge
- "Aktif Hari Ini" — count badge
- "Selesai Bulan Ini" — count badge

Table:
Column headers: No. | No. Reservasi | Tanggal | Fasilitas | Unit | Nama | Officer (RR) | Biaya | Status | Aksi

Rows:
- FRS1/VI/2026-MAP | 10 Jun | Balai Warga | MP/01/AA | Siti | Budi (officer RR) | Rp 500.000 | Badge "Menunggu CS" | [Detail]
- FRS2/VI/2026-MAP | 10 Jun | Games Room | MP/02/BB | Andi | Cici (officer RR) | Gratis | Badge "Menunggu Bayar" | [Detail]
- FRS3/VI/2026-MAP | 11 Jun | Mini Theater | MP/04/CC | Dian | Budi (officer RR) | Rp 300.000 | Badge "Dalam Proses" | [Detail]

Note below table: "Officer ditentukan otomatis Round Robin — Budi → Cici → Budi → Cici → ..."

Detail Slide-in Panel:
- Reservation full details
- Bukti bayar image preview
- Approval timeline: CS ✓ → Finance ○ → HK ○ → ENG ○ → Security Buka ○ → Security Tutup ○
- Action: "Approve" or "Tolak" button
```

---

## HALAMAN 8 — CEK INVOICE — PORTAL TENANT

```
Web app Invoice checking page for apartment tenant.

Layout: Top navigation + centered content.

Page Title: "CEK TAGIHAN & INVOICE"

Filter Row:
- Dropdown: Bulan | Tahun
- Blue "Filter" button

Current Invoice Card (highlighted, large):
- Badge "BELUM LUNAS" (red) top right
- Info: Unit MP/01/AA | Periode: Juni 2026
- Table inside card:
  Item | Volume | Tarif | Jumlah
  IPL (Service Charge) | 1 bln | Rp 500.000 | Rp 500.000
  Listrik | 250 kWh | Rp 1.444/kWh | Rp 361.000
  Air | 10 m³ | Rp 8.000/m³ | Rp 80.000
  ─────────────────────────────────
  TOTAL | | | Rp 941.000
- Upload Bukti Bayar button (dashed box area below total)
- Download PDF Invoice button (outlined blue)

Previous Invoices (below):
- Section title: "Riwayat Tagihan"
- Compact list: Periode | Total | Status Badge (Lunas/Belum) | [Download PDF]
```

---

## HALAMAN 9 — TRACKING WORK ORDER — PORTAL TENANT

```
Web app Work Order tracking page for apartment tenant.

Layout: Top navigation + main content.

Page Title: "TRACKING WORK ORDER SAYA"

WO Card (each complaint, expandable):
- Header: No.WO EX00001/V/2026-MAP | Tanggal: 01 Mei 2026 | Badge "Dalam Proses" (yellow)
- Description: "AC kamar tidak dingin, sudah 3 hari"
- Progress Timeline (horizontal steps):
  [✓] Pesan Diterima → [✓] Dalam Pengecekan → [●] Dalam Proses → [ ] Selesai
- Staff assigned: "Ditangani: Budi (Engineering)"
- Estimated close: "05 Juni 2026"

When status = Selesai, show Rating Section:
- Text: "Beri rating layanan:"
- 5 star rating icons (clickable)
- Textarea: "Komentar (opsional)"
- Submit button: "Kirim Rating"

Second WO Card:
- No.WO EX00002/V/2026-MAP | Selesai | Badge "Work Order Close" (green)
- Show: Rating bintang yang sudah diberikan
```

---

## HALAMAN 10 — LAPORAN BULANAN — PORTAL KARYAWAN (AM)

```
Web app Monthly Report page for Apartment Manager.

Layout: Sidebar + main content area.

Page Title: "LAPORAN BULANAN" with blue gradient header.
Filter: Dropdown Bulan + Tahun + "Tampilkan" button.

Row 1 — Summary Cards (4 cards):
- Total WO Masuk: 24 | Total WO Selesai: 20 | Total Reservasi: 15 | Total Revenue: Rp 8.500.000

Row 2 — Charts (2 side by side):
Left: Stacked Bar Chart — "Work Order per Bulan"
  X-axis: Jan Feb Mar Apr Mei Jun
  Bars: Blue (Internal) stacked with Orange (External)

Right: Pie/Donut Chart — "WO per Jenis"
  Segments: ELE | CIVIL | ME | dll dengan warna berbeda

Row 3 — Tables (2 side by side):
Left Table: "Work Order"
  No.WO | Tanggal | Jenis | Status | Durasi Selesai

Right Table: "Reservasi Fasilitas"
  No. | Fasilitas | Tanggal | Status | Revenue

Bottom: "Export PDF" button + "Export CSV" button
```

---

## HALAMAN 11 — TENANT REQUEST — PORTAL TENANT

```
Web app Tenant Request / Complaint submission page for tenant.

Layout: Top navigation + content.

Page Title: "PENGAJUAN KELUHAN / PERMINTAAN"

Tabs: "Ajukan Baru" | "Belum Selesai" | "Sudah Selesai"

Tab Aktif — Ajukan Baru:
- Form:
  Field: Kategori Keluhan — dropdown (Kerusakan / Kebersihan / Gangguan / Lainnya)
  Field: Subjek — text input
  Field: Deskripsi detail — textarea (large)
  Field: Upload foto/dokumen — drag & drop box
  Button: "Kirim Keluhan" (blue)

Tab — Belum Selesai:
- List of complaint cards:
  Card: No. R0000001/VI/2026-MAP | Submitted 01 Jun | "AC tidak dingin"
        Badge "Dalam Proses" (yellow)
        Info: "WO terkait: EX00001/V/2026-MAP"
        [Lihat Detail] button

Tab — Sudah Selesai:
- Completed complaints list (same card style, badge green "Selesai")
```

---

## HALAMAN 12 — QR SCAN FASILITAS — PORTAL SECURITY

```
Web app QR Code scanner page for Security staff to open/close facility.

Layout: Full screen, centered, mobile-friendly.

Top: Badge label "SECURITY — QR SCAN FASILITAS"

Main Content (centered card):
- Facility icon (large, placeholder)
- Facility name: "BALAI WARGA"
- Reservation info:
  Unit: MP/01/AA
  Nama Pemesan: Siti Rahayu
  Tanggal: 10 Juni 2026 | 09.00–12.00 WIB
  Status reservasi: Badge "Disetujui"

Action Section (conditional):
- If facility not yet opened:
  Large green button "BUKA FASILITAS"
  Subtext: "Tap untuk konfirmasi fasilitas dibuka"

- If facility already opened (in use):
  Large red button "TUTUP FASILITAS"
  Subtext: "Dibuka sejak 09.03 WIB"
  Info: "Officer: Budi (ditentukan Round Robin)"

Confirmation overlay (after button tap):
- Dialog: "Konfirmasi buka/tutup fasilitas?"
- Buttons: [Ya, Konfirmasi] [Batal]
```

---

## HALAMAN 13 — INPUT WORK ORDER (MODAL FORM) — KARYAWAN

```
Web app modal dialog for creating a new Work Order.

Layout: Overlay modal (centered, dark background behind).

Modal Title: "INPUT WORK ORDER BARU" with blue gradient header bar.

Form fields (two-column grid):
Row 1:
- Jenis WO: Toggle button group [EX — Eksternal] [IN — Internal]
- Kategori: Dropdown (CIVIL / ME / ELE / GEN / CUS)

Row 2:
- Lot No / Unit: Text input with search icon
- Nama Pelapor: Text input

Row 3 (full width):
- Deskripsi Masalah: Textarea (3 rows)

Row 4:
- Estimated Close: Date picker
- Priority: Radio (Normal / Urgent)

Row 5:
- Assign Departemen: Dropdown (ENG / HKP / SEC)
- Assign Staff: Dropdown (populated from department)

Row 6:
- Catatan tambahan: Textarea (2 rows)
- Berbayar?: Toggle Yes/No (if Yes, show Estimasi Biaya field)

Footer buttons (right-aligned):
- "Batal" button (gray, outlined)
- "Simpan WO" button (blue, solid)

Note below form: "Nomor WO akan di-generate otomatis saat disimpan"
```

---

## HALAMAN 14 — PROFIL UNIT — PORTAL TENANT

```
Web app Unit Profile page for apartment tenant.

Layout: Top navigation + two-column content.

Page Title: "PROFIL UNIT SAYA"

Left Column — Unit Info Card:
- Large unit number badge: "MP/01/AA"
- Info rows (label: value):
  Status: Pemilik
  Tgl. Serah Terima (STR): 15 Januari 2025
  Tgl. CMG: 20 Januari 2025
  No. Intercom: 1234
  No. Telpon: 021-12345678
  No. Access Card: AC-001

Right Column — Personal Data Card:
- Avatar placeholder circle (large)
- Name: "Siti Rahayu"
- Email: sitirahayu@email.com
- Phone: 0812-3456-7890
- Change Password link

Below (full width) — Aktifitas Terakhir:
- Mini timeline:
  "WO EX00001 — Dalam Proses" — 01 Jun
  "Reservasi Balai Warga — Disetujui" — 28 Mei
  "Invoice Juni 2026 — Belum Lunas" — 01 Jun
```

---

*Copy masing-masing prompt di atas ke Uizard (uizard.io) → "Generate with AI" atau Visily (visily.ai) → "New Project" → "Generate from text"*
