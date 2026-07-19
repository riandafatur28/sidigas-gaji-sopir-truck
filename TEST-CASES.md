# Test Case — Blackbox Equivalent Partitioning

**Aplikasi:** SIDIGAS (Sistem Distribusi Gaji Sopir)
**Metode:** Blackbox — Equivalent Partitioning (EP)
**Teknik:** Partisi nilai input ke dalam kelas-kelas ekuivalen (valid/invalid), lalu ambil 1 wakil dari tiap kelas.

---

## 1. Login (Email & Password)

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| L-01 | Email & password benar | email: `senjanugraha320@gmail.com`, password: `password123` | Valid (email terdaftar, password cocok) | Redirect ke dashboard |
| L-02 | Email benar, password salah | email: `senjanugraha320@gmail.com`, password: `salah123` | Valid email, invalid password | Kembali ke login, error "Email atau password salah." |
| L-03 | Email tidak terdaftar | email: `tidakada@test.com`, password: `password123` | Invalid email (tidak exist) | Kembali ke login, error "Email atau password salah." |
| L-04 | Email format salah | email: `bukanemail`, password: `password123` | Invalid format email | Validasi Laravel reject, error |
| L-05 | Email kosong | email: `""`, password: `password123` | Empty required field | Validasi reject "Email harus diisi" |
| L-06 | Password kosong | email: `senjanugraha320@gmail.com`, password: `""` | Empty required field | Validasi reject "Password harus diisi" |
| L-07 | Keduanya kosong | email: `""`, password: `""` | Empty required fields | Validasi reject keduanya |
| L-08 | Remember Me dicentang | kredensial benar, checkbox on | Valid + cookie | Login sukses, session persistent cookie |

---

## 2. Lupa Password — OTP

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| FP-01 | Email terdaftar | `senjanugraha320@gmail.com` | Valid (exists) | OTP terkirim, redirect ke verify-otp |
| FP-02 | Email tidak terdaftar | `tidakada@test.com` | Invalid (not exists) | Error "Email tidak ditemukan" |
| FP-03 | Email format salah | `bukanemail` | Invalid format | Validasi reject |
| FP-04 | Email kosong | `""` | Empty | Validasi reject |

---

## 3. Verifikasi OTP

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| OT-01 | OTP benar (6 digit) | otp: `123456` (sesuai DB) | Valid | Redirect ke reset password |
| OT-02 | OTP salah | otp: `000000` | Invalid (salah) | Error "Kode OTP salah atau sudah kadaluarsa." |
| OT-03 | OTP expired (>15 menit) | otp expired di DB | Invalid (expired) | Error "Kode OTP salah atau sudah kadaluarsa." |
| OT-04 | OTP kurang dari 6 digit | otp: `123` | Invalid format | Validasi reject |
| OT-05 | OTP non-numeric | otp: `abcdef` | Invalid format | Validasi reject (digits:6) |
| OT-06 | OTP kosong | otp: `""` | Empty | Validasi reject |

---

## 4. Reset Password

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| RP-01 | Password ≥ 6, konfirmasi cocok | password: `rahasia123`, password_confirmation: `rahasia123` | Valid | Password terupdate, redirect login dgn success |
| RP-02 | Password < 6 karakter | password: `abc12`, password_confirmation: `abc12` | Invalid (min:6) | Validasi reject |
| RP-03 | Konfirmasi tidak cocok | password: `rahasia123`, password_confirmation: `lain123` | Invalid (confirmed) | Validasi reject |
| RP-04 | Password kosong | password: `""`, password_confirmation: `""` | Empty | Validasi reject |
| RP-05 | Email tidak terdaftar | email: `fake@test.com` + password valid | Invalid (not exists) | Validasi reject |

---

## 5. Login Google

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| GL-01 | Email Google sudah terdaftar | Google akun = `senjanugraha320@gmail.com` | Valid (exists) | Login langsung, redirect dashboard |
| GL-02 | Email Google baru | Google akun baru (belum ada) | Valid (new user) | User baru dibuat (name, email, random pass), login, redirect dashboard |
| GL-03 | Gagal dapet data dari Google | - | Error callback | Redirect login dgn error "Gagal login dengan Google" |
| GL-04 | Google auth ditolak user | User klik "Batal" di consent Google | User cancelled | Redirect ke login (tanpa error, kena Exception handle) |

---

## 6. Logout

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| LO-01 | Logout dari session aktif | - | Valid | Session di-invalidate, redirect ke login |
| LO-02 | Akses dashboard setelah logout | - | Invalid (no auth) | Redirect ke login (middleware auth) |

---

## 7. Dashboard

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| DB-01 | Load dashboard tanpa filter | `periode` = default | Semua waktu | 4 kartu metrik tampil, recent activity, ringkasan |
| DB-02 | Filter "Periode Ini" | `periode` = `periode_ini` | Periode aktif terakhir | Data terfilter |
| DB-03 | Filter "Periode Lalu" | `periode` = `periode_lalu` | Periode sebelumnya | Data terfilter |
| DB-04 | Filter "Bulan Ini" | `periode` = `bulan_ini` | Current month | Data terfilter |
| DB-05 | Filter "Semua Waktu" | `periode` = `semua` | All data | Semua data tampil |
| DB-06 | Klik dropdown Validasi Pending | Klik salah satu opsi | Pending/Disetujui/Ditolak | Count berubah via JS |

---

## 8. Profil — Lihat & Edit

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| PR-01 | Lihat profil | - | - | Info user tampil (name, email, avatar, join date) |
| PR-02 | Update nama valid | name: `Senja Baru` | Valid (min:3, max:100) | Nama terupdate, success message |
| PR-03 | Update nama kosong | name: `""` | Invalid (required) | Validasi reject |
| PR-04 | Update nama 1 karakter | name: `A` | Invalid (min:3) | Validasi reject |
| PR-05 | Update nama >100 karakter | name: (101 chars) | Invalid (max:100) | Validasi reject |
| PR-06 | Update email valid (beda) | email: `baru@test.com` | Valid (unique) | Email terupdate |
| PR-07 | Update email sama (milik sendiri) | email: `senjanugraha320@gmail.com` | Valid (unique except self) | Email tetap, success |
| PR-08 | Update email duplicate | email: (email user lain) | Invalid (already taken) | Validasi unique reject |
| PR-09 | Ganti password — semua benar | password_lama: `password123`, password_baru: `newpass1`, password_baru_confirmation: `newpass1` | Valid | Password terupdate |
| PR-10 | Ganti password — lama salah | password_lama: `salah`, password_baru: `newpass1` | Invalid (current_password) | Error "Password lama tidak sesuai" |
| PR-11 | Ganti password — baru < 6 | password_lama: `password123`, password_baru: `abc12` | Invalid (min:6) | Validasi reject |
| PR-12 | Ganti password — konfirmasi beda | password_lama: `password123`, password_baru: `newpass1`, password_baru_confirmation: `lain` | Invalid (confirmed) | Validasi reject |
| PR-13 | Ganti password — baru dikosongkan | password_lama: `password123`, password_baru: `""` | Tidak ganti password | Nama/email terupdate, password tidak berubah |

---

## 9. Sopir (Driver) Management

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| SP-01 | Lihat daftar sopir | - | - | Tabel 10 data, stats cards, search input |
| SP-02 | Cari sopir (exists) | search: `senja` | Cocok | Tampil sopir dgn nama/kode mengandung "senja" |
| SP-03 | Cari sopir (not exists) | search: `zzzzzz` | Tidak cocok | Tabel kosong, "Tidak ada data" |
| SP-04 | Tambah sopir — nama valid | nama: `Budi Santoso` | Valid (≥3, ≤255) | Kode auto-generated (SPR-XXX), success |
| SP-05 | Tambah sopir — nama 2 karakter | nama: `Ab` | Invalid (min:3) | Validasi reject |
| SP-06 | Tambah sopir — nama kosong | nama: `""` | Invalid (required) | Validasi reject |
| SP-07 | Tambah sopir — nama dgn simbol | nama: `Budi@!#` | Invalid (regex) | Validasi reject (huruf/angka/spasi/dash saja) |
| SP-08 | Edit sopir — nama valid | nama: `Budi Update`, status: `aktif` | Valid | Terupdate |
| SP-09 | Edit sopir — status nonaktif | nama: `Budi`, status: `nonaktif` | Valid | Status berubah |
| SP-10 | Edit sopir — status invalid | status: `invalid_status` | Invalid enum | Validasi reject |
| SP-11 | Hapus sopir | - | Valid | Sopir terhapus |
| SP-12 | Hapus sopir (setelah dihapus) | ID sudah terhapus | Invalid (not found) | Error 404 |

---

## 10. Tujuan (Destination) Management

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| TU-01 | Lihat daftar tujuan | - | - | Tabel 10 data, stats cards |
| TU-02 | Tambah tujuan — nama valid | nama: `Kediri` | Valid (≥3, ≤255) | Kode auto-generated (TUJ-XXX) |
| TU-03 | Tambah tujuan — nama terlalu pendek | nama: `Ke` | Invalid (min:3) | Validasi reject |
| TU-04 | Tambah tujuan — nama kosong | nama: `""` | Invalid (required) | Validasi reject |
| TU-05 | Edit tujuan | nama: `Kediri Baru`, status: `aktif` | Valid | Terupdate |
| TU-06 | Edit status nonaktif | status: `nonaktif` | Valid | Status berubah |
| TU-07 | Hapus tujuan | - | Valid | Tujuan terhapus |

---

## 11. Periode Management

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| PE-01 | Lihat periode | - | - | Tabel, stats |
| PE-02 | Tambah periode — valid | nama: `Periode Juli 2026`, mulai: `2026-07-01`, selesai: `2026-07-31` | Valid (no overlap) | Kode PER-XXX, status aktif |
| PE-03 | Tambah periode — nama < 3 | nama: `Pe` | Invalid | Validasi reject |
| PE-04 | Tambah periode — tgl selesai < mulai | mulai: `2026-07-31`, selesai: `2026-07-01` | Invalid (after_or_equal) | Validasi reject |
| PE-05 | Tambah periode — overlap | tanggal overlap dgn periode exist | Invalid (overlap) | Error "Periode sudah ada" |
| PE-06 | Edit periode — status selesai | status: `selesai` | Valid | Status berubah |
| PE-07 | Hapus periode (tanpa ritase) | ID tanpa relasi | Valid | Terhapus |
| PE-08 | Hapus periode (dgn ritase) | ID punya ritase | Invalid (protected) | Error, tidak bisa hapus |

---

## 12. Ritase (Trip) Management

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| RT-01 | Lihat ritase | - | - | Tabel 15 data, filter periode & sopir, stats |
| RT-02 | Filter ritase by periode | periode: pilih salah satu | Filter | Hanya ritase periode tersebut |
| RT-03 | Filter by sopir | sopir: pilih sopir | Filter | Hanya ritase sopir tersebut |
| RT-04 | Tambah ritase — valid | `periode_id`: valid, `kode_sopir`: valid, `kode_tujuan`: valid, `tanggal`: `2026-07-15`, `waktu`: `pagi`, `kabupaten`: `Nganjuk`, `status`: `valid` | Valid | DT auto-calc, kode RIT-XXX, success |
| RT-05 | Tambah ritase — status gagal_produksi | status: `gagal_produksi` + nominal_kompensasi: `50000` | Valid (gagal) | DT = 0, kompensasi terisi |
| RT-06 | Tambah ritase — sopir+tanggal+kabupaten+waktu sama (duplikat) | Input duplikat | Kondisi DT=0 | DT = 0 (hanya hitung 1x) |
| RT-07 | Tambah ritase — kabupaten invalid | kabupaten: `Jakarta` | Invalid enum | Validasi reject |
| RT-08 | Tambah ritase — waktu invalid | waktu: `siang` | Invalid enum | Validasi reject |
| RT-09 | Tambah ritase — status invalid | status: `invalid_status` | Invalid enum | Validasi reject |
| RT-10 | Tambah ritase — aturan validasi aktif & belum ada validasi | aturan aktif, belum submit validasi | Invalid (aturan) | Error harus validasi dulu |
| RT-11 | Edit ritase — ganti status | status: `gagal_produksi` + kompensasi | Valid | DT recalculate |
| RT-12 | Hapus ritase | - | Valid | Terhapus |
| RT-13 | Cek aturan DT (AJAX) — status gagal | `status`: `gagal_produksi` | Gagal | sewa_dt = 0 |
| RT-14 | Cek aturan DT — pertama kali (sopir+tgl+wilayah+waktu unik) | - | Unik | sewa_dt = 330000 |
| RT-15 | Cek aturan DT — duplikat (sama sopir+tgl+wilayah+waktu) | - | Duplikat | sewa_dt = 0 |
| RT-16 | Tambah ritase — catatan > 500 karakter | catatan: 501 chars | Invalid (max:500) | Validasi reject |

---

## 13. Validasi Bukti — Submit

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| VB-01 | Submit validasi — lengkap | `nama_sopir`: `Budi`, `sopir_baru`: false, `kode_sopir`: valid, `nama_tujuan`: `Kediri`, `tujuan_baru`: false, `kode_tujuan`: valid, `foto`: base64 valid, `latitude`: -7.123, `longitude`: 112.456, `lokasi`: "Jalan Raya", `waktu_foto`: `2026-07-15 08:00`, `tanggal`: `2026-07-15` | Valid | Foto tersimpan di storage/bukti/, status pending, success |
| VB-02 | Submit — sopir baru | `sopir_baru`: true, `nama_sopir`: `Sopir Baru`, `kode_sopir`: `""` | Valid (sopir baru) | Tersimpan dgn sopir_baru = true |
| VB-03 | Submit — tujuan baru | `tujuan_baru`: true, `nama_tujuan`: `Tujuan Baru`, `kode_tujuan`: `""` | Valid (tujuan baru) | Tersimpan dgn tujuan_baru = true |
| VB-04 | Submit — foto invalid base64 | foto: `bukan-base64` | Invalid (decoding fail) | Error "Gagal menyimpan foto" |
| VB-05 | Submit — foto kosong | foto: `""` | Invalid (required) | Validasi reject |
| VB-06 | Submit — rate limit terlampaui | Post 6x dalam 3 menit | Rate limit | Error 429 Too Many Requests |
| VB-07 | Akses form validasi bukti | - | - | Form tampil dgn kamera, GPS, sopir/tujuan selector |
| VB-08 | GPS on page load | - | GPS support + HTTPS | Cari koordinat otomatis |
| VB-09 | GPS gagal (HTTP/tanpa izin) | - | GPS tidak support | Fallback ke EXIF lalu IP |

---

## 14. Validasi Bukti — Admin (Kelola, Detail, Approve/Reject)

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| VK-01 | Lihat daftar validasi | - | - | Tabel 20 data, tab filter |
| VK-02 | Filter status "pending" | status: `pending` | Filter | Hanya yg pending |
| VK-03 | Filter status "disetujui" | status: `disetujui` | Filter | Hanya yg disetujui |
| VK-04 | Filter status "ditolak" | status: `ditolak` | Filter | Hanya yg ditolak |
| VK-05 | Lihat detail validasi | - | - | Foto, lokasi, sopir, tujuan, status |
| VK-06 | Setujui — sopir baru | `catatan_mitra`: `""` (nullable) | Valid (sopir_baru=true) | Sopir baru terbuat, status = disetujui |
| VK-07 | Setujui — tujuan baru | `catatan_mitra`: `""` | Valid (tujuan_baru=true) | Tujuan baru terbuat, status = disetujui |
| VK-08 | Setujui — sopir & tujuan sudah ada | - | Valid (sudah ada) | Status = disetujui, tanpa buat baru |
| VK-09 | Tolak — dgn catatan | `catatan_mitra`: `"Foto tidak jelas"` | Valid (required) | Status = ditolak |
| VK-10 | Tolak — catatan kosong | `catatan_mitra`: `""` | Invalid (required) | Validasi reject |
| VK-11 | Tolak — catatan > 255 | catatan: 256 chars | Invalid (max:255) | Validasi reject |
| VK-12 | Tambah ritase dari validasi — valid | `kode_sopir`, `kode_tujuan`, `tanggal`, `waktu`: `pagi`, `kabupaten`: `bangkalan` | Valid | Status validasi jadi disetujui, ritase terbuat, DT auto |
| VK-13 | Tambah ritase — kabupaten invalid | kabupaten: `invalid` | Invalid enum | Validasi reject |
| VK-14 | Toggle aturan validasi ON | - | Mati → Nyala | Cache `aturan_validasi_enabled` = true |
| VK-15 | Toggle aturan validasi OFF | - | Nyala → Mati | Cache `aturan_validasi_enabled` = false |

---

## 15. Penggajian (Payroll)

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| GJ-01 | Lihat halaman gaji | query: `periode` | - | Tabel summary per sopir |
| GJ-02 | Load data ritase (AJAX) | query: `periode` | Periode valid | JSON: per-sopir data dgn rincian per tujuan |
| GJ-03 | Hitung & simpan gaji — valid | `periode_id`: valid, `detail[]`: semua rates terisi | Valid | Penggajian + PenggajianDetail terbuat, upah_sopir di ritase terupdate, success |
| GJ-04 | Hitung gaji — aturan validasi ON & ada ritase tanpa validasi | aturan aktif, ada ritase tanpa approval | Aturan aktif | Error, harus validasi dulu |
| GJ-05 | Edit gaji | muat data existing, update rates | Valid | Data gaji di-recalculate |
| GJ-06 | Hapus gaji | periode_id | Valid | Semua penggajian + detail terhapus |
| GJ-07 | Lihat slip gaji | periode_id + kode_sopir | Valid | Tabel per-hari: rit, solar, upah, DT |

---

## 16. Laporan & Riwayat

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| LR-01 | Lihat laporan gaji | query: `periode` | Periode valid | Table grouped by tujuan dgn subtotal |
| LR-02 | Lihat laporan — tanpa pilih periode | tanpa query | - | "Pilih periode untuk melihat laporan" |
| LR-03 | Riwayat gaji | - | - | Tabel history semua periode |
| LR-04 | Download slip PDF | periode_id | Valid | File PDF terdownload (bulk all sopir) |
| LR-05 | Download laporan PDF | periode_id | Valid | File PDF laporan terdownload |

---

## 17. PDF Output

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| PDF-01 | Slip PDF — semua sopir | periode_id dgn data | Data lengkap | PDF tiap sopir, tiap halaman, tidak terpotong |
| PDF-02 | Slip PDF — periode tanpa data | periode_id tanpa data | Data kosong | PDF tetap ter-generate (kosong atau pesan) |
| PDF-03 | Laporan PDF — semua kolom terisi | periode_id dgn data | Data lengkap | PDF: header + tabel + footer, tidak overflow |
| PDF-04 | Laporan PDF — kolom tujuan panjang | tujuan nama panjang | Long text | Teks di-wrap, tidak overflow ke kanan |

---

## 18. Security & Middleware

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| SC-01 | Akses halaman auth tanpa login | GET `/dashboard` | Unauthenticated | Redirect ke login |
| SC-02 | Akses halaman auth setelah logout | Back button | Session invalid | Redirect ke login (Cache-Control) |
| SC-03 | Rate limit login > 10 request | 11x POST `/login` dlm 3 menit | Rate exceeded | Error 429 Too Many Requests |
| SC-04 | Rate limit validasi > 5 request | 6x POST `/validasi-bukti` dlm 3 menit | Rate exceeded | Error 429 |
| SC-05 | Security headers | - | - | Response headers: X-Frame-Options: DENY, Cache-Control: no-cache, dll |
| SC-06 | CSRF token | Submit form tanpa token | Invalid CSRF | Error 419 Page Expired |

---

## 19. States & Edge Cases

| ID | Skenario | Input | Partisi | Hasil Diharapkan |
|----|----------|-------|---------|------------------|
| EC-01 | Load dashboard — data kosong | DB tidak ada data | Empty | Kartu metrik 0, tabel kosong, progress bar 0% |
| EC-02 | Pagination — data > 1 halaman | 25 data sopir | Multi-page | Tombol next/prev, page 2 |
| EC-03 | Pagination — data kosong | 0 data | Empty | Tidak ada pagination, "Tidak ada data" |
| EC-04 | Search — karakter spesial | search: `<>script` | XSS attempt | Output escaped, tidak execute script |
| EC-05 | Submit form via POST langsung | Curl tanpa referer | Direct POST | Diproses normal (tidak ada CSRF check di client) |
| EC-06 | Akses route yg tidak ada | GET `/halaman-tidak-ada` | 404 | Error 404 Not Found |

---

## Ringkasan

**Total test cases:** ~110
**Cakupan modul:**
- Login & Auth: 8
- Lupa Password (OTP): 4
- Verifikasi OTP: 6
- Reset Password: 5
- Login Google: 4
- Logout: 2
- Dashboard: 6
- Profil: 13
- Sopir: 12
- Tujuan: 7
- Periode: 8
- Ritase: 16
- Validasi Bukti (submit): 9
- Validasi Bukti (admin): 15
- Penggajian: 7
- Laporan & Riwayat: 5
- PDF Output: 4
- Security: 6
- Edge Cases: 6
