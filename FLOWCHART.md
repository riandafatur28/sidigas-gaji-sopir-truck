# Flowchart Sistem — SIDIGAS (Sistem Distribusi Gaji Sopir)

## Simbol — Activity Diagram

| Simbol | Nama | Keterangan |
|--------|------|------------|
| ![start] | **Terminal** (Start/Stop) | Permulaan atau akhir suatu proses |
| ![process] | **Proses** (Action) | Activity/tindakan yang dilakukan |
| ![decision] | **Decision** (Percabangan) | Kondisi yang menghasilkan beberapa kemungkinan (Ya/Tidak) |
| ![swimlane] | **Swimlane** (Actor) | Pemisah aktor/user yang melakukan aktivitas |
| ![flow] | **Flow** (Arus) | Aliran proses dari satu aktivitas ke aktivitas lain |
| ![loop] | **Loop** (Perulangan) | Proses berulang selama kondisi terpenuhi |

> **Catatan:** Simbol di atas mengacu pada standar **Activity Diagram UML** yang digunakan dalam seluruh diagram sistem ini.
>
> Diagram ditulis dalam format **PlantUML** — buka file `.puml` di `docs/` atau render dengan [PlantUML Online Server](https://www.plantuml.com/plantuml/uml/).

---

## Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────┐
│                    SIDIGAS — Laravel 13                      │
├─────────────────────────────────────────────────────────────┤
│  Frontend: Blade + Tailwind CSS + Vite                      │
│  Backend:  PHP 8.3, Laravel 13.x                            │
│  DB:       MySQL (via migrations)                           │
│  Auth:     Email/Password + Google OAuth (Socialite)        │
│  PDF:      barryvdh/laravel-dompdf                          │
│  Excel:    phpoffice/phpspreadsheet                         │
│  Queue:    Laravel Queue (sync driver)                      │
├─────────────────────────────────────────────────────────────┤
│  9 Models → 10 Controllers → 13 Services → 29 Views          │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Entity Relationship Diagram

```plantuml
@startuml
skinparam backgroundColor #ffffff
skinparam defaultFontSize 10
skinparam defaultFontName Arial
left to right direction

entity users {
  *id : bigint unsigned PK
  *name : varchar(255)
  *email : varchar(255)
  phone : varchar(255) NULL
  *password : varchar(255)
  created_at : timestamp NULL
  updated_at : timestamp NULL
}

entity otps {
  *id : bigint unsigned PK
  *email : varchar(255)
  *otp : varchar(6)
  *expires_at : timestamp
  created_at : timestamp NULL
  updated_at : timestamp NULL
}

entity sopirs {
  *id : bigint unsigned PK
  *kode_sopir : varchar(255) UNIQUE
  *nama : varchar(255)
  *status : enum(aktif,nonaktif) default=aktif
  created_at : timestamp NULL
  updated_at : timestamp NULL
}

entity tujuans {
  *id : bigint unsigned PK
  *kode_tujuan : varchar(255) UNIQUE
  *nama : varchar(255)
  latitude : decimal(10,7) NULL
  longitude : decimal(10,7) NULL
  *status : enum(aktif,nonaktif) default=aktif
  created_at : timestamp NULL
  updated_at : timestamp NULL
}

entity periodes {
  *id : bigint unsigned PK
  *kode_periode : varchar(255) UNIQUE
  *nama_periode : varchar(255)
  *tanggal_mulai : date
  *tanggal_selesai : date
  *status : enum(aktif,selesai) default=aktif
  created_at : timestamp NULL
  updated_at : timestamp NULL
}

entity ritases {
  *id : bigint unsigned PK
  *kode_ritase : varchar(255) UNIQUE
  *periode_id : bigint unsigned FK
  *kode_sopir : varchar(255) FK
  *kode_tujuan : varchar(255) FK
  *tanggal : date
  *waktu : enum(pagi,malam)
  *kabupaten : enum(Nganjuk,Kediri,Kota Kediri,Jombang,Blitar,Kota Blitar,Ngawi,Kota Ngawi,Lainnya)
  *status : enum(valid,pending,gagal_produksi) default=pending
  upah_sopir : decimal(15,2) default=0.00
  dt : decimal(15,2) default=0.00
  nominal_kompensasi : decimal(15,2) default=0.00
  is_lembur : tinyint(1) default=0
  upah_lembur : decimal(15,2) default=0.00
  catatan : text NULL
  created_at : timestamp NULL
  updated_at : timestamp NULL
}

entity gajis {
  *id : bigint unsigned PK
  *kode_sopir : varchar(20) FK
  *periode_id : bigint unsigned FK
  total_solar : decimal(15,2) default=0.00
  total_upah : decimal(15,2) default=0.00
  total_sewa_dt : decimal(15,2) default=0.00
  grand_total : decimal(15,2) default=0.00
  created_at : timestamp NULL
  updated_at : timestamp NULL
  ~~~ UNUSED : tabel legacy sebelum penggajian + detail
}

entity penggajian {
  *id : bigint unsigned PK
  *kode_sopir : varchar(255) FK
  *periode_id : bigint unsigned FK
  *tanggal : date
  uang_solar : decimal(15,2) default=0.00
  upah_sopir : decimal(15,2) default=0.00
  upah_lembur : decimal(15,2) default=0.00
  dt : decimal(15,2) default=0.00
  tol : decimal(15,2) default=0.00
  kompensasi_gagal : decimal(15,2) default=0.00
  total : decimal(15,2) default=0.00
  created_at : timestamp NULL
  updated_at : timestamp NULL
}

entity penggajian_details {
  *id : bigint unsigned PK
  *penggajian_id : bigint unsigned FK
  *kode_tujuan : varchar(255) FK
  jumlah_rit : int default=0
  solar_per_rit : decimal(15,2) default=0.00
  upah_per_rit : decimal(15,2) default=0.00
  total_solar : decimal(15,2) default=0.00
  total_upah : decimal(15,2) default=0.00
  sewa_dt : decimal(15,2) default=0.00
  tol_per_rit : decimal(15,2) default=0.00
  total_tol : decimal(15,2) default=0.00
  subtotal : decimal(15,2) default=0.00
  created_at : timestamp NULL
  updated_at : timestamp NULL
}

entity validasi_bukti {
  *id : bigint unsigned PK
  kode_sopir : varchar(20) FK NULL
  *nama_sopir : varchar(100)
  sopir_baru : tinyint(1) default=0
  kode_tujuan : varchar(20) FK NULL
  *nama_tujuan : varchar(100)
  tujuan_baru : tinyint(1) default=0
  *foto : varchar(255)
  latitude : decimal(10,7) NULL
  longitude : decimal(10,7) NULL
  lokasi : varchar(255) NULL
  *waktu_foto : datetime
  *tanggal : date
  periode_id : bigint unsigned FK NULL
  catatan : text NULL
  *status : enum(pending,disetujui,ditolak) default=pending
  catatan_mitra : text NULL
  created_at : timestamp NULL
  updated_at : timestamp NULL
}

' === RELATIONSHIPS ===
periodes ||..|{ ritases : "periode_id"
sopirs ||..|{ ritases : "kode_sopir"
tujuans ||..|{ ritases : "kode_tujuan"

sopirs ||..|{ gajis : "kode_sopir"
periodes ||..|{ gajis : "periode_id"

sopirs ||..|{ penggajian : "kode_sopir"
periodes ||..|{ penggajian : "periode_id"

penggajian ||..|{ penggajian_details : "penggajian_id"
tujuans ||..|{ penggajian_details : "kode_tujuan"

sopirs ||..|{ validasi_bukti : "kode_sopir"
tujuans ||..|{ validasi_bukti : "kode_tujuan"
periodes ||..|{ validasi_bukti : "periode_id"

note as N
  **Legenda:**
  * = NOT NULL
  PK = Primary Key
  FK = Foreign Key
  UNIQUE = Unique Index
end note
@enduml
```

---

## 1. Login (Email & Password)

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Buka halaman /login;
:Input email & password;
:Klik tombol "Login";

|Sistem|
:Validasi email & password;
if (Data cocok?) then (ya)
  :Buat session login;
  :Redirect ke /dashboard;
  |Admin|
  :Lihat halaman dashboard;
  stop
else (tidak)
  :Redirect kembali ke /login\ndengan error;
  |Admin|
  :Lihat pesan error\n"Email/password salah";
  stop
endif

@enduml
```

---

## 2. Login Google

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Klik tombol "Login dengan Google";

|Sistem|
:Redirect ke Google OAuth;

|Google|
:User login & pilih akun Google;

|Sistem|
:Validasi email;
if (Email terdaftar?) then (ya)
  :Buat session login;
  :Redirect ke /dashboard;
  |Admin|
  :Lihat halaman dashboard;
  stop
else (tidak)
  :Tampilkan error\n"Email tidak terdaftar";
  stop
endif

@enduml
```

---

## 3. Lupa Password — OTP

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Klik "Lupa Password";
:Input email;
:Klik "Kirim OTP";

|Sistem|
:Generate OTP 6 digit;
:Simpan OTP ke tabel otps;
:Kirim email berisi OTP;
:Redirect ke form input OTP;

|Admin|
:Buka email;
:Catat kode OTP;
:Input OTP di form;
:Klik "Verifikasi";

|Sistem|
:Cari OTP berdasarkan email;
if (OTP cocok & belum expired?) then (ya)
  :Redirect ke form\npassword baru;
  |Admin|
  :Input password baru\n& konfirmasi;
  :Klik "Reset Password";
  |Sistem|
  :Hash password baru;
  :Update tabel users;
  :Hapus OTP;
  :Redirect ke /login\ndengan sukses;
  |Admin|
  :Login dengan\npassword baru;
  stop
else (tidak)
  :Tampilkan error\n"OTP salah/kadaluarsa";
  stop
endif

@enduml
```

---

## 4. Dashboard

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Buka /dashboard;

|Sistem|
:Periode::syncActiveStatus();
:Sopir::syncActiveStatus();
:Tujuan::syncActiveStatus();
:Query metrik dashboard:
- total sopir (aktif/nonaktif)
- total ritase (valid/pending/gagal)
- total gaji periode aktif
- validasi bukti (pending/disetujui/ditolak);
:Query psikologi:
- progress periode (hari)
- sisa hari periode
- top 5 sopir
- aktivitas hari ini;
:Render data ke card & chart;
|Admin|
:Lihat ringkasan dashboard;
stop

@enduml
```

---

## 5. Kelola Sopir (CRUD)

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Buka /sopir;

|Sistem|
:Query semua sopir\n(paginated 10/halaman);
:Tampilkan tabel sopir;

repeat
  |Admin|
  if (Pilih aksi?) then (Tambah)
    :Klik "Tambah";
    :Isi form data sopir;
    :Submit;
    |Sistem|
    if (Validasi lolos?) then (ya)
      :Generate kode SPR-NNN;
      :Simpan ke sopirs;
      :Sopir::syncActiveStatus();
      :Redirect back;
    else (tidak)
      :Tampilkan error;
    endif

  elseif (Edit) then
    :Klik "Edit" pada baris;
    |Sistem|
    :Load data sopir;
    :Tampilkan modal form;
    |Admin|
    :Ubah data;
    :Submit;
    |Sistem|
    if (Validasi lolos?) then (ya)
      :Update sopirs;
      :Redirect back;
    else (tidak)
      :Tampilkan error;
    endif

  elseif (Hapus) then
    :Klik "Hapus";
    if (Konfirmasi?) then (ya)
      |Sistem|
      if (Punya ritase?) then (ya)
        :Tampilkan error\n"Tidak bisa hapus";
      else (tidak)
        :Hapus sopir;
      endif
      :Redirect back;
    endif

  else (Navigasi)
    :Klik nomor halaman;
    |Sistem|
    :Query halaman berikutnya;
  endif

repeat while (Masih di halaman sopir?) is (ya)
->tidak;
stop

@enduml
```

---

## 6. Kelola Tujuan (CRUD)

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Buka /tujuan;

|Sistem|
:Query semua tujuan\n(paginated);
:Tampilkan tabel tujuan;

repeat
  |Admin|
  if (Pilih aksi?) then (Tambah)
    :Klik "Tambah";
    :Isi form data tujuan;
    :Submit;
    |Sistem|
    if (Validasi lolos?) then (ya)
      :Generate kode TUJ-NNN;
      :Simpan ke tujuans;
      :Tujuan::syncActiveStatus();
      :Redirect back;
    else (tidak)
      :Tampilkan error;
    endif

  elseif (Edit) then
    :Klik "Edit";
    |Sistem|
    :Load data tujuan;
    :Tampilkan modal;
    |Admin|
    :Ubah data;
    :Submit;
    |Sistem|
    if (Validasi lolos?) then (ya)
      :Update tujuans;
      :Redirect back;
    else (tidak)
      :Tampilkan error;
    endif

  elseif (Hapus) then
    :Klik "Hapus";
    if (Konfirmasi?) then (ya)
      |Sistem|
      if (Punya ritase?) then (ya)
        :Tampilkan error\n"Tidak bisa hapus";
      else (tidak)
        :Hapus tujuan;
      endif
      :Redirect back;
    endif

  else (Navigasi)
    :Klik nomor halaman;
    |Sistem|
    :Query halaman berikutnya;
  endif

repeat while (Masih di halaman tujuan?) is (ya)
->tidak;
stop

@enduml
```

---

## 7. Kelola Periode (CRUD)

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Buka /periode;

|Sistem|
:Query semua periode\n(order by tgl_mulai DESC);
:Tampilkan tabel periode;

repeat
  |Admin|
  if (Pilih aksi?) then (Tambah)
    :Input nama_periode,\ntgl_mulai, tgl_selesai;
    :Submit;
    |Sistem|
    if (Validasi:\ntgl_selesai >= tgl_mulai\n& tidak overlap?) then (ya)
      :Generate kode PER-NNN;
      :Simpan ke periodes;
      :Periode::syncActiveStatus();
      :Redirect back;
    else (tidak)
      :Tampilkan error;
    endif

  elseif (Edit) then
    :Klik "Edit";
    |Sistem|
    :Load data periode;
    :Tampilkan modal;
    |Admin|
    :Ubah data;
    :Submit;
    |Sistem|
    if (Validasi lolos?) then (ya)
      :Update periodes;
      :Redirect back;
    else (tidak)
      :Tampilkan error;
    endif

  elseif (Hapus) then
    :Klik "Hapus";
    if (Konfirmasi?) then (ya)
      |Sistem|
      if (Ada ritase?) then (ya)
        :Tampilkan error\n"Memiliki data ritase";
      else (tidak)
        :Hapus periode;
      endif
      :Redirect back;
    endif

  else (Navigasi)
    :Klik nomor halaman;
    |Sistem|
    :Query halaman berikutnya;
  endif

repeat while (Masih di halaman periode?) is (ya)
->tidak;
stop

@enduml
```

### Auto-sync Status

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

start
:Cek today() vs\ntanggal_mulai - tanggal_selesai;
if (Today dalam rentang?) then (ya)
  :Set status=aktif\n(nonaktifkan periode lain);
  stop
else (tidak)
  :Set status=selesai;
  stop
endif

@enduml
```

---

## 8. Input Ritase — Parser Teks

### Flowchart Utama

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Buka /ritase;
|Sistem|
:Filter periode aktif;
:Query ritase periode aktif;
:Tampilkan tabel;

|Admin|
if (Pilih aksi?) then (Parse Teks)
  :Buka /ritase/parse;
  :Pilih periode;
  :Paste teks ritase;
  :Klik "Parse";
  |Sistem|
  :Parse teks\n(RitaseParserService);
  if (Berhasil?) then (ya)
    :Tampilkan preview parser;
    |Admin|
    if (Setuju & auto_create?) then (ya)
      :Klik "Simpan";
      |Sistem|
      :RitaseParserService@createRitases();
      :Sopir::syncActiveStatus();
      :Tujuan::syncActiveStatus();
      :Redirect ke /ritase;
    else (tidak)
      :Lihat preview\ndan sesuaikan;
    endif
  else (tidak)
    :Tampilkan error parse;
  endif

elseif (Tambah Manual) then
  :Klik "Tambah";
  :Isi form modal;
  :Submit;
  |Sistem|
  :Simpan ke ritases;
  :Redirect back;

elseif (Edit) then
  :Klik "Edit";
  :Ubah data via modal;
  :Submit;
  |Sistem|
  :Hitung ulang DT;
  :Update ritases;
  :Redirect back;

elseif (Hapus) then
  :Klik "Hapus";
  :Konfirmasi;
  |Sistem|
  :Hapus ritase;
  :Redirect back;

elseif (Detail) then
  :Klik "Detail";
  |Sistem|
  :Generate pivot\nsopir x tanggal;
  if (View = HTML?) then (ya)
    :Tampilkan detail-html\ndi iframe;
  else (tidak)
    :Generate PDF via DomPDF;
    :Download file;
  endif
endif
stop

@enduml
```

### 8.1. Mekanisme Parsing (REGEX)

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Sistem|
start
:Terima teks input;
:Split teks per baris\n(trim & filter kosong);

:Scan semua baris:\nRegex /'DD MM YY'/ atau /'YYYY-MM-DD'/;
if (Tanggal ditemukan?) then (tidak)
  :Return date=null;
  stop
endif

:Deteksi header sebelum tanggal:\n- Waktu (pagi/malam)\n- Header rute;

:Iterasi baris sisanya;
repeat
  if (Baris diawali angka + titik?) then (ya - sopir)
    :Regex preg_match\n'/^\d+\.(.*)$/';
    :Ambil group 1 = nama;
    :cleanDriverName():\n  hapus (mbah/pak/bu/ira)\n  hapus simbol (√✔✓🙏🙌);
    :Deduplikasi nama\nper package;

  else (tidak - rute)
    :Deteksi keyword jenis kerja:\n  patching, paket, overlay,\n  cmm, kormuling, rekon;
    if (Keyword di tengah?\n=sopir sebelum keyword) then (ya)
      :Split: sopir = teks sebelum\nRoute = teks sejak keyword;
    else (tidak)
      :Route = seluruh teks;
    endif
    :Deteksi:\n  - Rit ke 2 (duplikat)\n  - Bongkar (muatan)\n  - Gagal produksi;
  endif

repeat while (Baris habis?) is (tidak)
-> (ya);

:Merge package dgn route sama\n(gabung driver per route);

:Post-processing:\n- Link bongkar ke non-bongkar\n- Gabung gagal + route berikutnya;

:Return {date, packages};
stop

@enduml
```

### 8.2. Pencocokan Driver (Fuzzy Matching)

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

start
:Input nama sopir\ndan tabel sopirs;

if (Exact match\ncase insensitive?) then (ya)
  :Score: 100%;
else (tidak)
  if (Metaphone match?\nToni ↔ Tony) then (ya)
    :Score: 95%;
  else (tidak)
    if (Substring match?\n'Eko' in 'Eko Wilangan') then (ya)
      :Score: 90%;
    else (tidak)
      :Hitung Jaro-Winkler\n+ first-letter check;
      if (Skor ≥ 85%?) then (ya)
        :Score: similarity%;
      else (tidak)
        :Tidak cocok;
        :Auto-create sopir baru\nSPR-NNN;
        stop
      endif
    endif
  endif
endif

:Sopir ditemukan;
stop

@enduml
```

### 8.3. Pencocokan Rute (String Similarity)

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

start
:Input nama rute\n(patching Nganjuk);

:stripRoutePrefixes():\nHapus: paket, patching, overlay,\ncmm, kormuling, rekon,\nbondan, gabungan, rombongan;

if (Exact match\nsetelah strip?) then (ya)
  :Score: 100%;
else (tidak)
  if (Substring?\n'Nganjuk' in tujuan) then (ya)
    :Score: 95%;
  else (tidak)
    if (Word-set match\nbidirectional?) then (ya)
      :Score: 90%;
    else (tidak)
      :Hitung Jaro-Winkler\n+ first-letter;
      if (Skor ≥ threshold?) then (ya)
        :Score: similarity%;
      else (tidak)
        :Tidak cocok;
        :Auto-create tujuan baru\nTUJ-NNN;
        stop
      endif
    endif
  endif
endif

:Tujuan ditemukan;
stop

@enduml
```

### 8.4. Alur Simpan Hasil Parse

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

start
:Hasil parse: {date, packages};
:matchDrivers():\n  cocokin nama → tabel sopir\n  Exact → Metaphone → Substring → Jaro-Winkler;
:matchRoutes():\n  cocokin rute → tabel tujuan\n  Exact → Substring → Word-set → Jaro-Winkler;
:Preview:\n  driver_match[] + route_match[];

if (auto_create?) then (ya)
  :createRitases();
  foreach (Package in packages)
    :Cari driver match;
    if (Driver unmatched?) then (ya)
      :Auto-create sopir baru;
    endif
    :Cari route match;
    if (Route unmatched?) then (ya)
      :Auto-create tujuan baru;
    endif
    if (Status gagal_produksi?) then (ya)
      :Update ritase valid sebelumnya\njadi gagal_produksi, dt=0;
    else (tidak)
      if (is_rit_ke_2?) then (ya)
        :Create duplikat ritase\n(hari & sopir sama);
      else (tidak)
        if (is_bongkar?) then (ya)
          :Tandai bongkar,\nasosiasi ke non-bongkar;
        else (tidak)
          :Simpan ritase baru\n(status: pending, dt: dihitung);
        endif
      endif
    endif
  endforeach
  :Sync status sopir & tujuan;
  :Return {created, skipped, errors};
endif
stop

@enduml
```

---

## 9. Detail Ritase (Pivot Sopir × Tanggal)

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Klik "Detail";

|Sistem|
:Query semua ritase di periode;
:Group by sopir + tanggal + waktu;
:Build pivot table:
- Baris: sopir (kode + nama)
- Kolom: P1, P2... M1, M2... per tanggal
- Sel: jumlah rit + status;

:Render pivot HTML;

:Hitung per sopir:
- Total rit
- Total upah
- Total DT
- Eligible count;

if (Download PDF?) then (ya)
  :Generate PDF via DomPDF;
  :Download file;
endif
stop

@enduml
```

**Aturan DT:**
- Kabupaten `Nganjuk`, `Kediri`, `Kota Kediri`, `Jombang` → maks 1 DT/hari/sopir (rit ke-2 = 0)
- Kabupaten `Blitar`, `Kota Blitar`, `Ngawi`, `Kota Ngawi`, `Lainnya` → tiap rit dapat DT penuh
- Rit `gagal_produksi` → DT = 0

---

## 10. Validasi Bukti — Public Submit

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Sopir (Publik)|
start
:Buka /validasi-bukti\n(tanpa login);
:Input nama sopir;
:Input nama tujuan;
:Ambil foto\n(otomatis dapat\nlokasi & waktu);
:Klik "Kirim";

|Sistem|
:Validasi: foto required\nmax 2MB, jpg/png;
if (Lengkap?) then (tidak)
  :Tampilkan error;
  stop
endif

if (Aturan validasi\ndiaktifkan?) then (tidak)
  :Set status = disetujui;
else (ya)
  :Set status = pending;
endif

:Cocokkan nama sopir\nke DB (fuzzy match);
if (Sopir cocok?) then (tidak)
  :Tandai sopir_baru = true;
endif

:Cocokkan nama tujuan\nke DB (fuzzy match);
if (Tujuan cocok?) then (tidak)
  :Tandai tujuan_baru = true;
endif

:Simpan ke validasi_bukti;
:Tampilkan\n"Bukti berhasil dikirim";
stop

@enduml
```

---

## 11. Validasi Bukti — Admin Review

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Buka /validasi-bukti/kelola;

|Sistem|
:Query semua bukti\n(filter status);
:Tampilkan tabel\ndaftar bukti;

|Admin|
if (Pilih aksi?) then (Setujui)
  :Klik "Setujui";
  |Sistem|
  if (sopir_baru?) then (ya)
    :Auto-create sopir\n(fuzzy match dulu);
  endif
  if (tujuan_baru?) then (ya)
    :Auto-create tujuan\n(fuzzy match dulu);
  endif
  :Buat ritase:\nstatus=valid, hitungDt(),
  upah_sopir=0 (dari gaji);
  :Update status = disetujui;
  :Redirect back;

elseif (Tolak) then
  :Input catatan penolakan;
  :Klik "Tolak";
  |Sistem|
  :Update status = ditolak;
  :Redirect back;

elseif (Tambah Ritase) then
  :Klik "Tambah ke Ritase";
  |Sistem|
  :Auto-create sopir/tujuan\njika sopir_baru/tujuan_baru;
  :Buat ritase baru\ndari data bukti;
  :Redirect ke /ritase;

elseif (Toggle Aturan) then
  :Klik toggle aturan;
  |Sistem|
  :Update status aturan\ndi session/cache;
endif
stop

@enduml
```

### Fungsi hitungDt()

```php
// Cek apakah sudah ada rit non-gagal dengan
// sopir + tanggal + kabupaten + waktu SAMA
$ritLain = Ritase::where(...)->first();

if ($ritLain && in_array($kabupaten,
    ['Nganjuk','Kediri','Kota Kediri','Jombang']))
{
    return 0;  // Rit ke-2 → tidak dapat DT
}
return 330000; // Dapat DT
```

---

## 12. Penggajian — Proses & Kalkulasi

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Buka /gaji;

|Sistem|
:Load periode aktif;
:Query sopir + ritase\nperiode aktif;
:Tampilkan tabel\ninput biaya;

|Admin|
:Input biaya tiap tujuan:\n  uang_solar\n  upah_sopir\n  tol_per_rit;
:Klik "Hitung";

|Sistem|
:5 batch queries:
  1. Count rit per sopir+tujuan
  2. Sum DT per sopir
  3. Sum kompensasi gagal
  4. Sum upah lembur
  5. Update upah_sopir per tujuan;

:Iterasi per sopir;

repeat
  :Iterasi per tujuan;
  repeat
    if (Ada rit di sopir+tujuan?) then (ya)
      :Total Solar   = BBM/rit × jumlah;
      :Total Upah    = Upah/rit × jumlah;
      :Total Tol     = Tol/rit × jumlah;
    endif
  repeat while (Semua tujuan?) is (tidak)
  ->(ya);

  :DT = Σ dt dari ritases;
  :Kompensasi = Σ gagal;
  :Lembur = Σ upah_lembur;

  :Grand Total = Solar + Upah + DT\n+ Tol + Kompensasi + Lembur;

  :Create Penggajian + Detail;
repeat while (Semua sopir?) is (tidak)
->(ya);

:Tampilkan hasil\n(redirect ke halaman edit);
stop

@enduml
```

### Struktur Gaji Akhir

```
Grand Total = Total Solar + Total Upah + Total DT + Total Tol
            + Kompensasi Gagal + Upah Lembur
```

| Komponen | Sumber |
|----------|--------|
| Total Solar | Σ (BBM/rit × jumlah rit per tujuan) |
| Total Upah | Σ (upah/rit × jumlah rit per tujuan) |
| Total DT | Σ dt dari tabel ritases |
| Total Tol | Σ (tol/rit × jumlah rit per tujuan) |
| Kompensasi Gagal | Σ nominal_kompensasi dari rit gagal_produksi |
| Upah Lembur | Σ upah_lembur dari rit is_lembur=true |

---

## 13. Edit Gaji

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Buka /gaji/{id}/edit;

|Sistem|
:Load Penggajian + Details;
:Load Ritase per sopir per hari;
:Tampilkan form edit;

|Admin|
:Ubah detail per tujuan\n(BBM, upah, tol);
:Ubah DT, kompensasi, lembur;
:Klik "Update";

|Sistem|
if (Ada tujuan dihapus?) then (ya)
  :Hapus detail lama;
endif
:Update/insert detail baru;
:Rekap ulang:
  Total solar, upah, tol,
  dt, kompensasi, lembur,
  grand total;
:Simpan penggajian;
:Redirect ke index;
stop

@enduml
```

---

## 14. Slip Gaji & PDF

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Klik "Lihat Slip";
|Sistem|
:Fetch data slip via AJAX:
- Periode data
- Sopir data
- Penggajian + Details
- Ritase per hari;
:Sisipkan HTML ke modal\n(strip global CSS);
:Tampilkan slip;

|Admin|
if (Download?) then (ya)
  |Sistem|
  :Generate PDF via DomPDF\n(4 slip per F4);
  :Download slip.pdf;
endif
stop

@enduml
```

Slip per sopir: `GET /gaji/slip-pdf/{periode_id}` — download semua slip per sopir dalam 1 PDF.
Laporan rekap: `GET /gaji/laporan-pdf/{periode_id}` — rekap per sopir + per tujuan, PDF.

---

## 15. Riwayat & Laporan

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

|Admin|
start
:Buka /gaji/riwayat;

|Sistem|
:Query periode (pagination);
:Tampilkan daftar periode;

|Admin|
:Klik periode → lihat rekap;

|Sistem|
:Rekap:
- Total sopir
- Hari kerja
- Total per tujuan
- Total per sopir
- Grand total;

|Admin|
if (Download PDF?) then (ya)
  |Sistem|
  :Generate PDF laporan\n(semua sopir + tujuan);
  :Download laporan.pdf;
endif
stop

@enduml
```

---

## 16. Navigasi & Struktur File

### Navigasi Utama

```plantuml
@startuml
!theme plain
skinparam backgroundColor #ffffff
skinparam defaultFontSize 11
skinparam defaultFontName Arial

[[Guest]]
start
:Buka /validasi-bukti\n(public form);
stop

[[Guest]]
start
:Buka /login;
if (Login email) then (ya)
  :AuthController@login;
else (tidak)
  :Login Google\n(hanya email terdaftar);
endif
if (Berhasil?) then (ya)
  :Redirect ke /dashboard;
endif
stop

[[Admin]]
start
:Buka /dashboard;
if (Pilih menu?) then (Sopir)
  :/sopir;
elseif (Tujuan) then
  :/tujuan;
elseif (Periode) then
  :/periode;
elseif (Ritase) then
  :/ritase (parser)
  :/ritase/table (tabel)
  :/ritase/detail-data (pivot);
elseif (Validasi) then
  :/validasi-bukti/kelola;
elseif (Gaji) then
  :/gaji (penggajian)
  :/gaji/riwayat (laporan)
  :/gaji/slip/{periode}/{sopir} (slip);
elseif (Profil) then
  :/profil;
elseif (Logout) then
  :AuthController@logout;
endif
stop

@enduml
```

### Struktur File

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php          # Login/logout/Google OAuth/reset password
│   │   ├── DashboardController.php     # Dashboard metrics
│   │   ├── SopirController.php         # CRUD sopir
│   │   ├── TujuanController.php        # CRUD tujuan
│   │   ├── PeriodeController.php       # CRUD periode
│   │   ├── RitaseController.php        # CRUD ritase + parser + detail pivot
│   │   ├── PenggajianController.php    # Penggajian + edit + slip + laporan
│   │   ├── ValidasiBuktiController.php # Validasi bukti public + admin
│   │   ├── ProfileController.php       # Edit profil user
│   │   └── Controller.php              # Base controller
│   ├── Middleware/
│   │   ├── CacheControl.php
│   │   ├── RoleMiddleware.php
│   │   └── SecurityHeaders.php
│   ├── Requests/                       # Form request validation
├── Models/
│   ├── Sopir.php                       # Sopir model
│   ├── Tujuan.php                      # Tujuan model
│   ├── Periode.php                     # Periode model
│   ├── Ritase.php                      # Ritase model
│   ├── Penggajian.php                  # Penggajian model
│   ├── PenggajianDetail.php            # Detail per tujuan
│   ├── ValidasiBukti.php               # Validasi bukti model
│   ├── User.php                        # User auth model
│   └── Otp.php                         # OTP model
├── Services/                           # 13 service classes
│   ├── AuthService.php                 # Login, OTP, password reset
│   ├── DashboardService.php            # Dashboard metrics (cached 5 min)
│   ├── PenggajianService.php           # Generate penggajian
│   ├── PenggajianDataService.php       # Query & data penggajian
│   ├── RitaseService.php               # CRUD ritase + pivot detail
│   ├── RitaseDetailService.php         # Detail logic ritase
│   ├── RitaseParserService.php         # Facade delegasi ke 3 parser services
│   ├── RitaseTextParser.php            # Regex parsing teks WhatsApp
│   ├── RitaseFuzzyMatcher.php          # Jaro-Winkler + Metaphone matching
│   ├── RitaseCreator.php              # Insert ritase ke database
│   ├── SlipBuilderService.php          # Build data slip gaji
│   ├── LaporanBuilderService.php       # Build data laporan
│   └── ValidasiBuktiService.php        # Submit & review validasi bukti
├── Mail/
│   └── OtpMail.php                     # Email OTP
├── Traits/
│   └── HasUniqueKode.php              # Trait untuk kode unik otomatis
└── View/Components/
    └── layouts/Auth.php
resources/views/
├── auth/                               # Login, forgot/reset password, verify OTP
├── dashboard/                          # Dashboard
├── sopir/                              # CRUD sopir
├── tujuan/                             # CRUD tujuan
├── periode/                            # CRUD periode
├── ritase/                             # parser, parser-result, index, detail-html, detail-pdf
├── penggajian/                         # index, edit, slip, slip-pdf, laporan, laporan-pdf, riwayat, pdf
├── validasi-bukti/                     # form public, kelola (admin), detail
├── profil/                             # Edit profil
└── layouts/ + components/layouts/      # Layouts
routes/
└── web.php                             # Semua route
docs/
├── activity-diagram-*.puml            # Activity diagrams (PlantUML)
└── erd.puml                            # Entity relationship diagram
```

---

## 17. Iterasi Pengembangan

### Iterasi 1 — CRUD Dasar
- Sopir, Tujuan, Periode (CRUD manual via modal)
- Ritase (input manual via form)
- Penggajian (hitung manual)
- Login email/password + Google OAuth

### Iterasi 2 — Parser Teks & Fuzzy Matching
- **RitaseParserService**: input teks WhatsApp → parse otomatis
- Regex untuk ekstraksi sopir (`^\d+\.`), **bukan NER**/AI
- Fuzzy matching: Metaphone + Jaro-Winkler untuk cocokin driver & route
- Auto-create sopir/tujuan baru jika tidak cocok
- Deteksi `gagal produksi`, `bongkar`, `Rit ke 2`

### Iterasi 3 — Validasi Bukti & Geolokasi
- Public form submit: foto + geolokasi + nama sopir/tujuan
- Admin review: setujui/tolak + auto-create sopir/tujuan
- Aturan validasi (toggle on/off di session)
- Hitung DT otomatis berdasarkan aturan kabupaten

### Iterasi 4 — Detail Ritase (Pivot) & PDF
- Tabel pivot sopir × tanggal (P1/P2... M1/M2...)
- PDF detail ritase per periode
- Perbaikan aturan DT: kabupaten `Lainnya` dapat DT tiap rit
- Index optimization (15 indexes ke tabel)

### Iterasi 5 — Tol, Lembur, & Revisi Kabupaten
- Tambah kolom `tol` di penggajian + `tol_per_rit` di detail
- Tambah `is_lembur` + `upah_lembur` di ritase
- Tambah `upah_lembur` di penggajian
- Update enum kabupaten: +Blitar, Kota Blitar, Ngawi, Kota Ngawi

### Iterasi Final — Stabilisasi
- **Fix plimping**: dari keyword jenis kerja → nama daerah (dihapus dari routeKeywords & stripPrefixes)
- Tambah `kormuling`, `rekon` sebagai jenis pekerjaan
- Konsistensi strip prefix di semua titik (parser + controller)
- Auto-sync status: sopir, tujuan, periode (by tanggal)
- Dashboard dengan filter waktu & psikologi (progress %)
