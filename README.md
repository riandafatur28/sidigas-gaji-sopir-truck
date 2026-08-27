# SIDIGAS — Sistem Distribusi Gaji Sopir Dump Truck

Sistem manajemen distribusi gaji sopir dump truck berbasis web. Dibangun dengan Laravel 13, PHP 8.3, dan MySQL.

## Fitur Utama

| Modul | Deskripsi |
|-------|-----------|
| **Dashboard** | Ringkasan metrik real-time: total sopir, ritase, gaji, progress periode |
| **Kelola Sopir** | CRUD sopir dengan kode unik otomatis (SPR-NNN) dan sinkronisasi status aktif/nonaktif |
| **Kelola Tujuan** | CRUD tujuan/rute dengan kode unik (TUJ-NNN) dan koordinat lokasi |
| **Kelola Periode** | CRUD periode gaji dengan validasi overlap tanggal dan auto-sync status |
| **Parser Ritase** | Input teks WhatsApp → parsing otomatis via regex + fuzzy matching (Jaro-Winkler) |
| **Ritase CRUD** | Tabel ritase dengan filter periode, edit manual, pivot sopir x tanggal |
| **Penggajian** | Kalkulasi gaji otomatis: solar, upah, DT, tol, kompensasi gagal, lembur |
| **Slip Gaji** | Preview & download PDF slip gaji per sopir (4 slip per halaman F4) |
| **Validasi Bukti** | Form publik untuk submit bukti kerja + admin review (setujui/tolak) |
| **Autentikasi** | Login email/password + Google OAuth + lupa password via OTP email |

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.3, Laravel 13 |
| Frontend | Blade, Tailwind CSS, Vite |
| Database | MySQL 8.x (SQLite untuk testing) |
| Auth | Laravel Socialite (Google OAuth) |
| PDF | barryvdh/laravel-dompdf |
| Excel | phpoffice/phpspreadsheet |
| Testing | PHPUnit 12.x (159 tests) |
| Docker | Multi-stage build, compose.yaml |
| Deploy | Render.com (free tier) |

## Instalasi

### Prasyarat

- PHP >= 8.3
- Composer
- Node.js >= 18
- MySQL / SQLite

### Setup Lokal

```bash
# Clone repo
git clone https://github.com/riandafatur28/backup-llm-gajisopir.git
cd distribusi-gaji-baru

# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database (SQLite default)
touch database/database.sqlite
php artisan migrate

# Build assets
npm run build

# Jalankan server
php artisan serve
```

Aplikasi berjalan di `http://localhost:8000`.

### Docker

```bash
# Build & run
docker compose up -d

# Aplikasi berjalan di http://localhost:8080
```

Docker setup termasuk MySQL, aplikasi, dan ngrok untuk HTTPS tunneling (perlu `NGROK_AUTHTOKEN` di `.env`).

### Composer Scripts

```bash
composer setup     # Install + migrate + build (sekali jalan)
composer dev       # Jalankan server + queue + logs + vite (parallel)
composer test      # Clear config + jalankan test
```

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_KEY` | Laravel encryption key | — |
| `APP_URL` | Base URL aplikasi | `http://localhost` |
| `DB_CONNECTION` | Database driver | `sqlite` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | `3306` |
| `DB_DATABASE` | Database name | `laravel` |
| `DB_USERNAME` | Database user | `root` |
| `DB_PASSWORD` | Database password | — |
| `GOOGLE_CLIENT_ID` | Google OAuth client ID | — |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret | — |
| `GOOGLE_REDIRECT_URL` | Google OAuth callback URL | `http://localhost/auth/google/callback` |
| `MAIL_MAILER` | Email driver | `log` |

## Arsitektur

```
┌─────────────────────────────────────────────────────────┐
│                    SIDIGAS — Laravel 13                   │
├─────────────────────────────────────────────────────────┤
│  Frontend : Blade + Tailwind CSS + Vite                  │
│  Backend  : PHP 8.3, Laravel 13.x                        │
│  Database : MySQL (production) / SQLite (testing)         │
│  Auth     : Email/Password + Google OAuth (Socialite)     │
│  PDF      : barryvdh/laravel-dompdf                       │
│  Excel    : phpoffice/phpspreadsheet                      │
├─────────────────────────────────────────────────────────┤
│  9 Models → 10 Controllers → 13 Services → 29 Views      │
└─────────────────────────────────────────────────────────┘
```

### Struktur Project

```
app/
├── Http/
│   ├── Controllers/          # 10 controllers (semua ≤100 baris)
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── SopirController.php
│   │   ├── TujuanController.php
│   │   ├── PeriodeController.php
│   │   ├── RitaseController.php
│   │   ├── PenggajianController.php
│   │   ├── ValidasiBuktiController.php
│   │   ├── ProfileController.php
│   │   └── Controller.php
│   ├── Middleware/
│   │   └── SecurityHeaders.php    # CSP, HSTS, rate limiting
│   └── Requests/                  # Form request validation
├── Models/                        # 9 Eloquent models
│   ├── User.php, Otp.php
│   ├── Sopir.php, Tujuan.php, Periode.php
│   ├── Ritase.php
│   ├── Penggajian.php, PenggajianDetail.php
│   └── ValidasiBukti.php
├── Services/                      # 13 service classes
│   ├── AuthService.php
│   ├── DashboardService.php
│   ├── PenggajianService.php
│   ├── PenggajianDataService.php
│   ├── RitaseService.php
│   ├── RitaseDetailService.php
│   ├── RitaseParserService.php   # Facade → TextParser, FuzzyMatcher, Creator
│   ├── RitaseTextParser.php
│   ├── RitaseFuzzyMatcher.php
│   ├── RitaseCreator.php
│   ├── SlipBuilderService.php
│   ├── LaporanBuilderService.php
│   └── ValidasiBuktiService.php
├── Mail/
│   └── OtpMail.php
├── Traits/
│   └── HasUniqueKode.php
└── View/Components/
    └── layouts/Auth.php

resources/views/
├── auth/                  # Login, forgot/reset password, verify OTP
├── dashboard/             # Dashboard metrics
├── sopir/                 # CRUD sopir
├── tujuan/                # CRUD tujuan
├── periode/               # CRUD periode
├── ritase/                # Parser, table, detail pivot, PDF
├── penggajian/            # Index, edit, slip, laporan, riwayat
├── validasi-bukti/        # Form publik, kelola admin, detail
├── profil/                # Edit profil
├── components/            # Shared blade components
│   ├── pagination.blade.php
│   └── form-tambah.blade.php
└── layouts/               # Layout master

tests/
├── Feature/               # 12 test files, 159 tests
│   ├── AuthControllerTest.php        (21 tests)
│   ├── RitaseCrudTest.php            (8 tests)
│   ├── PeriodeControllerTest.php     (8 tests)
│   ├── SopirControllerTest.php       (7 tests)
│   ├── TujuanControllerTest.php      (7 tests)
│   ├── BladeViewTest.php             (32 tests)
│   └── ...
└── Unit/
```

### Service Layer

Kode dibagi ke service terpisah agar mudah di-maintain:

| Service | Responsibility | LOC |
|---------|---------------|-----|
| `RitaseParserService` | Facade delegasi ke 3 parser services | ~65 |
| `RitaseTextParser` | Regex parsing teks WhatsApp | ~120 |
| `RitaseFuzzyMatcher` | Jaro-Winkler + Metaphone matching | ~100 |
| `RitaseCreator` | Insert ritase ke database | ~80 |
| `RitaseService` | CRUD ritase + pivot detail | ~340 |
| `RitaseDetailService` | Detail logic ritase | ~145 |
| `PenggajianService` | Generate penggajian | ~200 |
| `PenggajianDataService` | Query & data penggajian | ~340 |
| `SlipBuilderService` | Build data slip gaji | ~308 |
| `LaporanBuilderService` | Build data laporan | ~200 |
| `DashboardService` | Dashboard metrics (cached 5 min) | ~170 |
| `AuthService` | Login, OTP, password reset | ~57 |
| `ValidasiBuktiService` | Submit & review validasi bukti | ~250 |

## API Routes

### Public (tanpa auth)

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/validasi-bukti` | Form submit bukti kerja |
| POST | `/validasi-bukti` | Submit bukti (throttle: 5/3min) |

### Guest (belum login)

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/login` | Halaman login |
| POST | `/login` | Proses login (throttle: 5/3min) |
| GET | `/auth/google` | Redirect Google OAuth |
| GET | `/auth/google/callback` | Google OAuth callback |
| GET | `/forgot-password` | Form lupa password |
| POST | `/forgot-password` | Kirim OTP (throttle: 3/5min) |
| GET | `/verify-otp` | Form verifikasi OTP |
| POST | `/verify-otp` | Verifikasi OTP |
| GET | `/reset-password` | Form reset password |
| POST | `/reset-password` | Update password |

### Authenticated

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/dashboard` | Dashboard metrics |
| POST | `/logout` | Logout |
| GET/POST | `/profil` | Lihat/edit profil |
| **Sopir** | | |
| GET | `/sopir` | List sopir |
| POST | `/sopir` | Tambah sopir |
| PUT | `/sopir/{id}` | Edit sopir |
| DELETE | `/sopir/{id}` | Hapus sopir |
| **Tujuan** | | |
| GET | `/tujuan` | List tujuan |
| POST | `/tujuan` | Tambah tujuan |
| PUT | `/tujuan/{id}` | Edit tujuan |
| DELETE | `/tujuan/{id}` | Hapus tujuan |
| **Ritase** | | |
| GET | `/ritase` | Parser form |
| POST | `/ritase` | Proses parser teks |
| GET | `/ritase/table` | Tabel ritase |
| POST | `/ritase/table` | Tambah ritase manual |
| PUT | `/ritase/table/{id}` | Edit ritase |
| DELETE | `/ritase/table/{id}` | Hapus ritase |
| POST | `/ritase/cek-aturan` | Cek aturan DT (AJAX) |
| GET | `/ritase/detail-data` | Pivot sopir x tanggal (AJAX) |
| GET | `/ritase/detail-pdf` | Download detail PDF |
| **Periode** | | |
| GET | `/periode` | List periode |
| POST | `/periode` | Tambah periode |
| PUT | `/periode/{id}` | Edit periode |
| DELETE | `/periode/{id}` | Hapus periode |
| **Penggajian** | | |
| GET | `/gaji` | Proses penggajian |
| POST | `/gaji` | Hitung & simpan gaji |
| GET | `/gaji/{id}/edit` | Edit penggajian |
| PUT | `/gaji/{id}` | Update penggajian |
| DELETE | `/gaji/{id}` | Hapus penggajian |
| GET | `/gaji/riwayat` | Riwayat penggajian |
| GET | `/gaji/slip/{periode}/{sopir}` | Slip gaji AJAX |
| GET | `/gaji/slip-pdf/{periode}` | Download slip PDF |
| GET | `/gaji/slip-view/{periode}` | Lihat slip HTML |
| GET | `/gaji/laporan-pdf/{periode}` | Download laporan PDF |
| **Validasi Bukti (Admin)** | | |
| GET | `/validasi-bukti/kelola` | Kelola validasi |
| GET | `/validasi-bukti/{id}` | Detail validasi |
| POST | `/validasi-bukti/{id}/setujui` | Setujui bukti |
| POST | `/validasi-bukti/{id}/tolak` | Tolak bukti |
| POST | `/validasi-bukti/{id}/ritase` | Tambah ritase dari bukti |
| DELETE | `/validasi-bukti/{id}` | Hapus validasi |
| POST | `/settings/toggle-validasi` | Toggle aturan validasi |

## Aturan Bisnis

### Distribusi DT (Dalam Tangki)

| Kabupaten | Aturan |
|-----------|--------|
| Nganjuk, Kediri, Kota Kediri, Jombang | Maks **1 DT/hari/sopir**. Rit ke-2 = DT Rp 0 |
| Blitar, Kota Blitar, Ngawi, Kota Ngawi, Lainnya | Tiap rit dapat **DT penuh** (Rp 330.000) |
| Gagal produksi | DT = Rp 0 |

Konfigurasi DT: `config/dt.php`

### Parser Ritase

Input teks WhatsApp (copy-paste) → otomatis di-parse:

1. **Tanggal** diekstrak dari format `DD MM YY` atau `YYYY-MM-DD`
2. **Sopir** diekstrak dari baris berawalan angka + titik (`1. Budi`)
3. **Rute/tujuan** diekstrak dari baris selanjutnya
4. **Fuzzy matching** untuk mencocokkan nama sopir & tujuan ke database
5. **Auto-create** sopir/tujuan baru jika tidak ditemukan (threshold: 85%)

### Sync Status Otomatis

- **Periode**: Status `aktif` jika hari ini dalam rentang `tanggal_mulai` — `tanggal_selesai`
- **Sopir**: `aktif` jika punya ritase di periode aktif
- **Tujuan**: `aktif` jika punya ritase di periode aktif

## Testing

```bash
# Jalankan semua test
php artisan test

# Jalankan test suite tertentu
php artisan test --testsuite=Feature
php artisan test tests/Feature/AuthControllerTest.php

# Dengan coverage (perlu xdebug/pcov)
php artisan test --coverage
```

### Test Coverage

| Test File | Tests | Deskripsi |
|-----------|-------|-----------|
| `AuthControllerTest` | 21 | Login, OTP, password reset, edge cases |
| `BladeViewTest` | 32 | Semua blade view render tanpa error |
| `RitaseCrudTest` | 8 | CRUD ritase, validasi, parser |
| `PeriodeControllerTest` | 8 | CRUD, overlap, delete guard |
| `SopirControllerTest` | 7 | CRUD, delete guard dengan ritase |
| `TujuanControllerTest` | 7 | CRUD, delete guard dengan ritase |
| `DashboardControllerTest` | 1 | Dashboard render |
| `ProfileControllerTest` | 8 | Update profil, validasi |
| `ValidasiSearchDeleteGajiSearchTest` | 10 | Validasi, search, delete |
| **Total** | **159** | **158 passing, 1 skipped** |

Testing menggunakan **SQLite in-memory** (default di `phpunit.xml`).

## Deployment

### Render.com

Konfigurasi sudah tersedia di `render.yaml`:

1. Push repo ke GitHub
2. Buat **Web Service** di Render → pilih repo
3. Render akan otomatis detect `render.yaml`
4. Set environment variables: `APP_KEY`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`
5. Deploy

### Docker (Manual)

```bash
# Build
docker compose build

# Run
docker compose up -d

# Logs
docker compose logs -f app
```

Dockerfile menggunakan multi-stage build:
1. **Build stage**: Install PHP, Composer, Node.js → build assets
2. **Run stage**: PHP 8.3-cli minimal → migrate + serve

## License

MIT License — lihat [LICENSE](LICENSE) untuk detail.
