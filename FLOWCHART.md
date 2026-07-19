# Flowchart Sistem — SIDIGAS

**Simbol:**

| Simbol Mermaid | Nama | Keterangan |
|----------------|------|------------|
| `(["..."])` | Terminal | Permulaan/akhir suatu proses |
| `[/"..."/]` | Input/Output | Proses input/output data |
| `["..."]` | Proses | Pelaksanaan pemrosesan komputer |
| `[["..."]]` | Predefined Process | Penyimpanan penyediaan/pemberian harga awal |
| `{"..."}` | Decision | Kondisi yang menghasilkan beberapa kemungkinan |
| `-->` | Arus Pemrosesan | Arah aliran proses |
| `-.->` | Off-Page Connector | Keluar/masuk proses di halaman lain |

---

## 1. Login (Email & Password)

```mermaid
flowchart TD
    START(["Mulai"])
    START --> INPUT[/"Tampilkan form login / Email & Password"/]
    INPUT --> ISI["User mengisi email & password"]
    ISI --> C_VALID{Email & Password / terisi,<br/>email @gmail.com?}
    C_VALID -- Tidak --> E_VALID[/"Tampilkan error validasi"/]
    E_VALID --> ISI
    C_VALID -- Ya --> PROSES["AuthController@login<br/>Auth::attempt(credentials)"]
    PROSES --> C_COCOK{Email & Password / cocok?}
    C_COCOK -- Tidak --> E_SALAH[/"Tampilkan error / 'Email atau password salah'"/]
    E_SALAH --> ISI
    C_COCOK -- Ya --> REGEN["Regenerasi session"]
    REGEN --> REDIR["Redirect ke Dashboard"]
    REDIR --> STOP(["Selesai"])
```

---

## 2. Login Google

```mermaid
flowchart TD
    START(["Mulai"])
    START --> KLIK[/"User klik 'Masuk dengan Google'"/]
    KLIK --> GOOGLE["Socialite::driver('google')<br/>redirect()"]
    GOOGLE --> AUTH["Redirect ke halaman<br/>konsen Google OAuth"]
    AUTH --> C_SETUJU{User setuju / memberi akses?}
    C_SETUJU -- Tidak --> BATAL[/"Redirect ke login<br/>(Exception handle)"/]
    BATAL --> STOP(["Selesai"])
    C_SETUJU -- Ya --> CALLBACK["Google callback ke<br/>loginGoogleCallback()"]
    CALLBACK --> C_EXIST{Email Google / terdaftar di users?}
    C_EXIST -- Tidak --> E_REG[/"Tampilkan error / 'Email tidak terdaftar di sistem'"/]
    E_REG --> STOP(["Selesai"])
    C_EXIST -- Ya --> LOGIN["Auth::login(user)"]
    LOGIN --> REGEN["Regenerasi session"]
    REGEN --> DASH[/"Redirect ke Dashboard"/]
    DASH --> STOP(["Selesai"])
```

---

## 3. Lupa Password — Request OTP

```mermaid
flowchart TD
    START(["Mulai"])
    START --> FORM[/"Tampilkan form / masukkan email"/]
    FORM --> ISI["User mengisi email"]
    ISI --> C_VALID{Email valid, @gmail.com, / terdaftar?}
    C_VALID -- Tidak --> E_VALID[/"Tampilkan error"/]
    E_VALID --> ISI
    C_VALID -- Ya --> HAPUS_OTP["Hapus OTP lama / untuk email ini"]
    HAPUS_OTP --> GEN_OTP["Generate 6 digit OTP random"]
    GEN_OTP --> SIMPAN["Simpan ke tabel otps / (email, otp, expires_at=+15min)"]
    SIMPAN --> KIRIM["Kirim email OTP / via OtpMail"]
    KIRIM --> REDIR[/"Redirect ke form / Verifikasi OTP"/]
    REDIR --> STOP(["Selesai"])
```

---

## 4. Verifikasi OTP

```mermaid
flowchart TD
    START(["Mulai"])
    START --> FORM[/"Tampilkan form / input 6 digit OTP"/]
    FORM --> ISI["User memasukkan / kode OTP"]
    ISI --> C_VALID{OTP 6 digit / numeric?}
    C_VALID -- Tidak --> E_FORM[/"Validasi reject"/]
    E_FORM --> ISI
    C_VALID -- Ya --> CEK_OTP["Cari OTP di DB / (email, otp, expires_at >= now)"]
    CEK_OTP --> C_DITEMUKAN{OTP ditemukan / & belum expired?}
    C_DITEMUKAN -- Tidak --> E_OTP[/"Error 'OTP salah atau / sudah kedaluwarsa'"/]
    E_OTP --> ISI
    C_DITEMUKAN -- Ya --> HAPUS["Hapus OTP dari DB"]
    HAPUS --> REDIR[/"Redirect ke form / Reset Password"/]
    REDIR --> STOP(["Selesai"])
```

---

## 5. Reset Password

```mermaid
flowchart TD
    START(["Mulai"])
    START --> FORM[/"Tampilkan form / Password Baru + Konfirmasi"/]
    FORM --> ISI["User mengisi password / dan konfirmasi"]
    ISI --> C_VALID{Password >= 6 / & konfirmasi cocok?}
    C_VALID -- Tidak --> E_VALID[/"Validasi reject"/]
    E_VALID --> ISI
    C_VALID -- Ya --> UPDATE["User::whereEmail<br/>->update password<br/>Hash::make(password)"]
    UPDATE --> REDIR[/"Redirect ke Login / dgn success message"/]
    REDIR --> STOP(["Selesai"])
```

---

## 6. Dashboard

```mermaid
flowchart TD
    START(["Mulai"])
    START --> LOAD["DashboardController@index"]
    LOAD --> FILTER["Baca parameter ?periode="]
    FILTER --> QUERY["Query data:<br/>- count sopir (aktif/total)<br/>- count ritase (total/valid/pending/gagal)<br/>- count validasi (pending/disetujui/ditolak)<br/>- sum total gaji<br/>- recent 6 ritase"]
    QUERY --> DATA["Siapkan data ke view"]
    DATA --> RENDER[/"Render dashboard/index.blade.php"/]
    RENDER --> C_FILTER{User pilih / filter periode?}
    C_FILTER -- Ya --> FILTER
    C_FILTER -- Tidak --> C_DROPDOWN{User klik / dropdown Validasi?}
    C_DROPDOWN -- Ya --> JS["JavaScript toggle / count validasi"]
    JS --> RENDER
    C_DROPDOWN -- Tidak --> STOP(["Selesai"])
```

---

## 7. Profil — Lihat & Edit

```mermaid
flowchart TD
    START(["Mulai"])
    START --> LIHAT[/"Tampilkan halaman Profil / (nama, email, avatar, join date)"/]
    LIHAT --> FORM[/"Form edit: nama, email,<br/>password_lama, password_baru,<br/>password_baru_confirmation"/]
    FORM --> ISI["User edit data & submit"]
    ISI --> C_VALID{Nama >=3, <=100<br/>Email unique (kecuali milik sendiri)<br/>Fields valid?}
    C_VALID -- Tidak --> E_FORM[/"Tampilkan error validasi"/]
    E_FORM --> FORM
    C_VALID -- Ya --> C_GANTI_PASS{password_baru / diisi?}
    C_GANTI_PASS -- Tidak --> UPDATE_INFO["Update name & email"]
    C_GANTI_PASS -- Ya --> C_LAMA_BENAR{password_lama / cocok dgn current?}
    C_LAMA_BENAR -- Tidak --> E_PASS[/"Error 'Password lama tidak sesuai'"/]
    E_PASS --> FORM
    C_LAMA_BENAR -- Ya --> C_BARU_VALID{password_baru >=6 / & confirmed?}
    C_BARU_VALID -- Tidak --> E_FORM
    C_BARU_VALID -- Ya --> UPDATE_ALL["Update name, email,<br/>& password (Hash::make)"]
    UPDATE_INFO --> SUCCESS[/"Success message"/]
    UPDATE_ALL --> SUCCESS
    SUCCESS --> LIHAT
```

---

## 8. Sopir — CRUD

```mermaid
flowchart TD
    START(["Mulai"])
    START --> INDEX[/"Tampilkan daftar sopir / (10/page, search, stats)"/]

    INDEX --> C_ACTION{Pilih aksi?}

    C_ACTION -- Cari --> SEARCH["Input search keyword"]
    SEARCH --> QUERY["Query sopir where<br/>nama/kode_sopir like %keyword%"]
    QUERY --> INDEX

    C_ACTION -- Tambah --> ADD_FORM[/"Modal form input nama"/]
    ADD_FORM --> ADD_ISI["User input nama"]
    ADD_ISI --> C_ADD_VALID{Nama >=3, <=255?}
    C_ADD_VALID -- Tidak --> E_ADD[/"Validasi reject"/]
    E_ADD --> ADD_FORM
    C_ADD_VALID -- Ya --> STORE["Simpan: generate kode SPR-XXX,<br/>status='aktif'"]
    STORE --> ADD_CLOSE[/"Tutup modal, success"/]
    ADD_CLOSE --> INDEX

    C_ACTION -- Edit --> EDIT_FORM[/"Modal form edit nama & status"/]
    EDIT_FORM --> EDIT_ISI["User ubah nama/status"]
    EDIT_ISI --> C_EDIT_VALID{Nama valid,<br/>status in [aktif/nonaktif]?}
    C_EDIT_VALID -- Tidak --> E_EDIT[/"Validasi reject"/]
    E_EDIT --> EDIT_FORM
    C_EDIT_VALID -- Ya --> UPDATE["Update sopir"]
    UPDATE --> EDIT_CLOSE[/"Tutup modal, success"/]
    EDIT_CLOSE --> INDEX

    C_ACTION -- Hapus --> C_PUNYA_RITASE{Sopir punya / data ritase?}
    C_PUNYA_RITASE -- Ya --> E_HAPUS[/"Error 'Tidak bisa hapus, / ada data ritase'"/]
    E_HAPUS --> INDEX
    C_PUNYA_RITASE -- Tidak --> KONFIRM[/"Modal konfirmasi hapus"/]
    KONFIRM --> C_YAKIN{Yakin hapus?}
    C_YAKIN -- Tidak --> INDEX
    C_YAKIN -- Ya --> DESTROY["Delete sopir"]
    DESTROY --> HAPUS_CLOSE[/"Tutup modal, success"/]
    HAPUS_CLOSE --> INDEX
```

---

## 9. Tujuan — CRUD

*(Pola sama seperti Sopir, ganti Sopir → Tujuan, kode TUJ-XXX)*

```mermaid
flowchart TD
    START(["Mulai"])
    START --> INDEX[/"Tampilkan daftar tujuan / (10/page, search, stats)"/]

    INDEX --> C_ACTION{Pilih aksi?}

    C_ACTION -- Cari --> SEARCH["Input search keyword"]
    SEARCH --> QUERY["Query tujuan where<br/>nama/kode_tujuan like %keyword%"]
    QUERY --> INDEX

    C_ACTION -- Tambah --> ADD_FORM[/"Modal form input nama"/]
    ADD_FORM --> ADD_ISI["User input nama"]
    ADD_ISI --> C_ADD_VALID{Nama >=3, <=255?}
    C_ADD_VALID -- Tidak --> E_ADD[/"Validasi reject"/]
    E_ADD --> ADD_FORM
    C_ADD_VALID -- Ya --> STORE["Simpan: generate kode TUJ-XXX,<br/>status='aktif'"]
    STORE --> ADD_CLOSE[/"Tutup modal, success"/]
    ADD_CLOSE --> INDEX

    C_ACTION -- Edit --> EDIT_FORM[/"Modal form edit nama & status"/]
    EDIT_FORM --> EDIT_ISI["User ubah nama/status"]
    EDIT_ISI --> C_EDIT_VALID{Nama valid,<br/>status in [aktif/nonaktif]?}
    C_EDIT_VALID -- Tidak --> E_EDIT[/"Validasi reject"/]
    E_EDIT --> EDIT_FORM
    C_EDIT_VALID -- Ya --> UPDATE["Update tujuan"]
    UPDATE --> EDIT_CLOSE[/"Tutup modal, success"/]
    EDIT_CLOSE --> INDEX

    C_ACTION -- Hapus --> C_PUNYA_RITASE{Tujuan punya / data ritase?}
    C_PUNYA_RITASE -- Ya --> E_HAPUS[/"Error 'Tidak bisa hapus, / ada data ritase'"/]
    E_HAPUS --> INDEX
    C_PUNYA_RITASE -- Tidak --> KONFIRM[/"Modal konfirmasi hapus"/]
    KONFIRM --> C_YAKIN{Yakin hapus?}
    C_YAKIN -- Tidak --> INDEX
    C_YAKIN -- Ya --> DESTROY["Delete tujuan"]
    DESTROY --> HAPUS_CLOSE[/"Tutup modal, success"/]
    HAPUS_CLOSE --> INDEX
```

---

## 10. Periode — CRUD

```mermaid
flowchart TD
    START(["Mulai"])
    START --> INDEX[/"Tampilkan daftar periode / (10/page, search)"/]

    INDEX --> C_ACTION{Pilih aksi?}

    C_ACTION -- Tambah --> ADD_FORM[/"Modal form: nama,<br/>tgl_mulai, tgl_selesai"/]
    ADD_FORM --> ADD_ISI["User isi form"]
    ADD_ISI --> C_VALID{Nama >=3,<br/>selesai >= mulai,<br/>no overlap?}
    C_VALID -- Tidak --> E_ADD[/"Validasi reject / error overlap"/]
    E_ADD --> ADD_FORM
    C_VALID -- Ya --> STORE["Simpan: kode PER-XXX,<br/>status='aktif'"]
    STORE --> ADD_CLOSE[/"Tutup modal, success"/]
    ADD_CLOSE --> INDEX

    C_ACTION -- Edit --> EDIT_FORM[/"Modal form edit"/]
    EDIT_FORM --> EDIT_ISI["User ubah data"]
    EDIT_ISI --> C_EDIT_VALID{Valid?}
    C_EDIT_VALID -- Tidak --> E_EDIT[/"Validasi reject"/]
    E_EDIT --> EDIT_FORM
    C_EDIT_VALID -- Ya --> UPDATE["Update periode"]
    UPDATE --> EDIT_CLOSE[/"Tutup modal, success"/]
    EDIT_CLOSE --> INDEX

    C_ACTION -- Hapus --> C_PUNYA_RITASE{Periode punya / data ritase?}
    C_PUNYA_RITASE -- Ya --> E_HAPUS[/"Error 'tidak bisa hapus, / ada data ritase'"/]
    E_HAPUS --> INDEX
    C_PUNYA_RITASE -- Tidak --> KONFIRM[/"Modal konfirmasi hapus"/]
    KONFIRM --> DESTROY["Delete periode"]
    DESTROY --> HAPUS_CLOSE[/"Tutup modal, success"/]
    HAPUS_CLOSE --> INDEX
```

---

## 11. Ritase — CRUD + DT Logic

```mermaid
flowchart TD
    START(["Mulai"])
    START --> INDEX[/"Tampilkan daftar ritase / (15/page, filter periode & sopir, stats)"/]

    INDEX --> C_ACTION{Pilih aksi?}

    C_ACTION -- Filter --> FILTER["Pilih periode / sopir"]
    FILTER --> QUERY["Query dengan where<br/>periode_id / kode_sopir"]
    QUERY --> INDEX

    C_ACTION -- Tambah --> FORM[/"Modal form tambah ritase"/]
    FORM --> ISI["User isi: periode, sopir, tujuan,<br/>tanggal, waktu (pagi/malam),<br/>kabupaten (Nganjuk/Kediri/Kota Kediri/Jombang/Lainnya),<br/>status (valid/pending/gagal_produksi),<br/>kompensasi (opsional), catatan (max:500)"]
    ISI --> C_VALID{Semua field / valid?}
    C_VALID -- Tidak --> E_FORM[/"Validasi reject"/]
    E_FORM --> FORM
    C_VALID -- Ya --> C_ATURAN{Aturan validasi / aktif?}
    C_ATURAN -- Ya --> C_VALIDASI{Ada validasi_bukti / disetujui utk sopir+<br/>tanggal+tujuan?}
    C_VALIDASI -- Tidak --> E_ATURAN[/"Error 'Harus validasi dulu'"/]
    E_ATURAN --> FORM
    C_VALIDASI -- Ya --> HITUNG_DT
    C_ATURAN -- Tidak --> HITUNG_DT

    HITUNG_DT --> DT["[[hitungDT()]]"]
    DT --> SIMPAN["Simpan ritase / (kode RIT-XXX)"]
    SIMPAN --> CLOSE[/"Tutup modal, success"/]
    CLOSE --> INDEX

    C_ACTION -- Edit --> EDIT_FORM[/"Modal edit ritase"/]
    EDIT_FORM --> EDIT_ISI["User ubah data"]
    EDIT_ISI --> C_EDIT_VALID{Valid?}
    C_EDIT_VALID -- Tidak --> E_EDIT[/"Validasi reject"/]
    E_EDIT --> EDIT_FORM
    C_EDIT_VALID -- Ya --> DT_EDIT["[[hitungDT(excludeId)]]"]
    DT_EDIT --> UPDATE["Update ritase"]
    UPDATE --> EDIT_CLOSE[/"Tutup modal, success"/]
    EDIT_CLOSE --> INDEX

    C_ACTION -- Hapus --> KONFIRM[/"Modal konfirmasi hapus"/]
    KONFIRM --> DESTROY["Delete ritase"]
    DESTROY --> INDEX
```

### Subproses hitungDT()

```mermaid
flowchart TD
    START(["hitungDT()"])
    START --> INPUT[/"Input: status, kabupaten,<br/>sopir, tanggal, waktu,<br/>excludeId (optional)"/]
    INPUT --> C_GAGAL{Status = / gagal_produksi?}
    C_GAGAL -- Ya --> DT0["return DT = 0"]
    DT0 --> KEMBALI(["Kembali"])
    C_GAGAL -- Tidak --> C_DUPLIKAT{Ada ritase lain<br/>sama sopir + tgl +<br/>kabupaten + waktu<br/>(exclude excludeId)?}
    C_DUPLIKAT -- Ya --> DT0
    C_DUPLIKAT -- Tidak --> DT330["return DT = 330000"]
    DT330 --> KEMBALI(["Kembali"])
```

---

## 12. Validasi Bukti — Submit (User/Sopir)

```mermaid
flowchart TD
    START(["Mulai"])
    START --> FORM[/"Tampilkan form validasi / (kamera, GPS, selector)"/]

    FORM --> GPS["cariGPS()<br/>navigator.geolocation.getCurrentPosition<br/>(HTTPS + izin required)"]
    GPS --> C_GPS{GPS berhasil / dalam 15 detik?}
    C_GPS -- Ya --> SIMPAN_GPS["latitude, longitude = GPS"]
    C_GPS -- Tidak --> COBA_LAGI{Retry / 5 detik?}
    COBA_LAGI -- Ya --> GPS
    COBA_LAGI -- Tidak --> EXIF["cariEXIF()<br/>exifr.parse(file)<br/>(baca GPS dari foto)"]
    EXIF --> C_EXIF{EXIF ada / lat/long?}
    C_EXIF -- Ya --> SIMPAN_GPS
    C_EXIF -- Tidak --> IP["cariIP()<br/>ip-api.com → ipapi.co<br/>(fallback lokasi dari IP)"]

    SIMPAN_GPS --> ISI["Pilih sopir (dari database)<br/>Pilih tujuan (dari database)<br/>Input tanggal, waktu_foto, catatan"]
    ISI --> FOTO["User ambil foto via kamera<br/>(input type=file accept=image/* capture=environment)"]
    FOTO --> WATERMARK["Canvas: gambar foto +<br/>watermark 5 baris di bawah:<br/>Sopir, Tujuan, Koordinat,<br/>Alamat, Tanggal & Waktu"]
    WATERMARK --> PREVIEW[/"Tampilkan modal verifikasi / (preview foto, sopir, tujuan,<br/>koordinat, lokasi, waktu)"/]
    PREVIEW --> C_YAKIN{User yakin / submit?}
    C_YAKIN -- Tidak --> FORM
    C_YAKIN -- Ya --> SUBMIT["POST /validasi-bukti<br/>(rate limit: 5x/3min)"]

    SUBMIT --> C_RATE{Melebihi / rate limit?}
    C_RATE -- Ya --> E_429[/"Error 429 Too Many Requests"/]
    E_429 --> STOP(["Selesai"])
    C_RATE -- Tidak --> DECODE["Decode base64 foto<br/>(strip prefix data:image/...)"]
    DECODE --> C_DECODE{Base64 valid?}
    C_DECODE -- Tidak --> E_FOTO[/"Error 'Gagal menyimpan foto'"/]
    E_FOTO --> FORM
    C_DECODE -- Ya --> STORE_FOTO["Simpan foto ke / storage/bukti/"]
    STORE_FOTO --> DETEC_PERIODE["Deteksi periode aktif / (tanggal di dalam range)"] 
    DETEC_PERIODE --> SAVE["Simpan ke tabel<br/>validasi_bukti<br/>status='pending'"]
    SAVE --> SUCCESS[/"Success, menunggu / verifikasi admin"/]
    SUCCESS --> STOP(["Selesai"])
```

---

## 13. Validasi Bukti — Admin (Kelola, Approve, Reject, Tambah Ritase)

```mermaid
flowchart TD
    START(["Mulai"])
    START --> KELOLA[/"Tampilkan daftar validasi / (20/page, tab filter status)"/]

    KELOLA --> C_TAB{User klik tab / status?}
    C_TAB -- Ya --> FILTER["Query where status =<br/>pending/disetujui/ditolak/semua"]
    FILTER --> KELOLA
    C_TAB -- Tidak --> C_TOGGLE{User toggle / aturan validasi?}
    C_TOGGLE -- Ya --> TOGGLE["Cache::toggle<br/>aturan_validasi_enabled"]
    TOGGLE --> KELOLA
    C_TOGGLE -- Tidak --> C_ITEM{User klik / salah satu item?}
    C_ITEM -- Tidak --> KELOLA
    C_ITEM -- Ya --> DETAIL[/"Tampilkan detail validasi<br/>(foto, lokasi, sopir, tujuan,<br/>status, catatan)"/]

    DETAIL --> C_ACTION{Aksi apa?}

    C_ACTION -- Setujui --> C_SOPIR_BARU{sopir_baru / = true?}
    C_SOPIR_BARU -- Ya --> CREATE_SOPIR["Buat Sopir baru<br/>(nama_sopir → SPR-XXX)"]
    C_SOPIR_BARU -- Tidak --> C_TUJUAN_BARU
    CREATE_SOPIR --> C_TUJUAN_BARU{tujuan_baru / = true?}
    C_TUJUAN_BARU -- Ya --> CREATE_TUJUAN["Buat Tujuan baru<br/>(nama_tujuan → TUJ-XXX)"]
    C_TUJUAN_BARU -- Tidak --> APPROVE["Update status = disetujui<br/>catatan_mitra = (nullable)"]
    CREATE_TUJUAN --> APPROVE
    APPROVE --> KELOLA

    C_ACTION -- Tolak --> C_CATATAN{Catatan mitra / terisi & <=255?}
    C_CATATAN -- Tidak --> E_CAT[/"Validasi reject"/]
    E_CAT --> DETAIL
    C_CATATAN -- Ya --> REJECT["Update status = ditolak<br/>catatan_mitra = ..."]
    REJECT --> DETAIL

    C_ACTION -- Tambah Ritase --> FORM_RIT[/"Form tambah ritase:<br/>sopir, tujuan, tanggal,<br/>waktu, kabupaten<br/>(Nganjuk/Kediri/Kota Kediri/Jombang/Lainnya)"/]
    FORM_RIT --> ISI_RIT["User isi & submit"]
    ISI_RIT --> C_RIT_VALID{Semua field / valid?}
    C_RIT_VALID -- Tidak --> E_RIT[/"Validasi reject"/]
    E_RIT --> FORM_RIT
    C_RIT_VALID -- Ya --> DT["[[hitungDt(kodeSopir, tanggal, kabupaten, waktu)]]"]
    DT --> SAVE_RIT["Buat ritase status=valid<br/>Update validasi status=disetujui"]
    SAVE_RIT --> KELOLA
```

---

## 14. Penggajian — Hitung & Simpan

```mermaid
flowchart TD
    START(["Mulai"])
    START --> INDEX[/"Tampilkan halaman gaji / per periode"/]

    INDEX --> PILIH["Pilih periode"]
    PILIH --> AJAX["AJAX GET /api/get-ritase-data<br/>(per-sopir data rit per tujuan,<br/>rates default dari periode sebelumnya,<br/>sopir dengan ritase belum digaji)"]
    AJAX --> FORM[/"Tampilkan form input rates<br/>(bbm_per_rit, upah_per_rit,<br/>kompensasi_gagal per tujuan)"/]

    FORM --> C_HITUNG{User klik / 'Hitung Gaji'?}
    C_HITUNG -- Tidak --> FORM
    C_HITUNG -- Ya --> C_ATURAN{Aturan validasi / aktif?}
    C_ATURAN -- Ya --> C_VALIDASI{Semua ritase non-gagal punya / validasi_bukti disetujui?}
    C_VALIDASI -- Tidak --> E_ATURAN[/"Error 'Harus validasi dulu'"/]
    E_ATURAN --> FORM
    C_VALIDASI -- Ya --> PROSES
    C_ATURAN -- Tidak --> PROSES

    PROSES["[[Hitung & Simpan Gaji]]"]
    PROSES --> HAPUS_LAMA["Hapus data penggajian<br/>lama periode ini"]
    HAPUS_LAMA --> LOOP_SOPIR["Loop per sopir:<br/>hitung rit per tujuan"]
    LOOP_SOPIR --> LOOP_TUJUAN["Loop per tujuan:<br/>- jumlah rit (non-gagal)<br/>- total_solar = bbm_per_rit x jml_rit<br/>- total_upah = upah_per_rit x jml_rit<br/>- total DT (akumulasi dari ritase)<br/>- kompensasi_gagal (dari detail[])"]
    LOOP_TUJUAN --> SIMPAN_DETAIL["Simpan PenggajianDetail"]
    SIMPAN_DETAIL --> C_SELESAI{Semua sopir / selesai?}
    C_SELESAI -- Tidak --> LOOP_SOPIR
    C_SELESAI -- Ya --> UPDATE_RITASE["Update upah_sopir<br/>di tabel ritase"]
    UPDATE_RITASE --> SUCCESS[/"Success message"/]
    SUCCESS --> INDEX
```

---

## 15. Laporan & Riwayat

```mermaid
flowchart TD
    START(["Mulai"])
    START --> C_MENU{Pilih menu?}

    C_MENU -- Laporan --> FORM_LAP["Pilih periode"]
    FORM_LAP --> C_PILIH{Periode / dipilih?}
    C_PILIH -- Tidak --> INFO[/"Pilih periode untuk melihat laporan"/]
    INFO --> FORM_LAP
    C_PILIH -- Ya --> LAPORAN[/"Tampilkan laporan grouped by tujuan<br/>(solar, upah, DT, gagal, subtotal)"/]
    LAPORAN --> C_DOWNLOAD{Download PDF?}
    C_DOWNLOAD -- Ya --> PDF_LAPORAN["DomPDF: folio landscape<br/>load laporan-pdf.blade.php"]
    PDF_LAPORAN --> STREAM_LAP[/"Stream PDF laporan"/]
    C_DOWNLOAD -- Tidak --> FORM_LAP

    C_MENU -- Riwayat --> RIWAYAT[/"Tampilkan tabel history / semua periode"/]
    RIWAYAT --> C_MENU

    C_MENU -- Slip --> FORM_SLIP["Pilih periode + sopir"]
    FORM_SLIP --> SLIP[/"Tampilkan slip per-hari:<br/>rit, tujuan, solar, upah, DT, jumlah"/]
    SLIP --> C_DOWNLOAD_SLIP{Download PDF / slip?}
    C_DOWNLOAD_SLIP -- Ya --> PDF_SLIP["DomPDF: load slip-pdf.blade.php"]
    PDF_SLIP --> STREAM_SLIP[/"Stream PDF slip"/]
    C_DOWNLOAD_SLIP -- Tidak --> FORM_SLIP
```

---

## 16. Logout

```mermaid
flowchart TD
    START(["Mulai"])
    START --> KLIK[/"User klik Logout / (POST /logout)"/]
    KLIK --> LOGOUT["Auth::logout()"]
    LOGOUT --> INVALIDATE["Session::invalidate()"]
    INVALIDATE --> REGEN["Session::regenerateToken()"]
    REGEN --> REDIR[/"Redirect ke halaman Login"/]
    REDIR --> STOP(["Selesai"])
```

---

## 17. Navigasi Utama Guest → Login → Semua Fitur

```mermaid
flowchart TD
    GUEST([Guest])
    GUEST --> VALIDASI_PUBLIK["Validasi Bukti (Public Form)<br/>GET /validasi-bukti"]
    VALIDASI_PUBLIK --> GUEST

    GUEST --> FORM_LOGIN[/"Halaman Login"/]
    FORM_LOGIN --> LOGIN["Login (Email dgn @gmail.com)"]
    FORM_LOGIN --> GOOGLE["Login Google (hanya email terdaftar)"]
    LOGIN --> DASH
    GOOGLE --> DASH

    DASH["Dashboard"] --> SOPIR["Sopir"]
    DASH --> TUJUAN["Tujuan"]
    DASH --> PERIODE["Periode"]
    DASH --> RITASE["Ritase"]
    DASH --> VALIDASI_ADMIN["Validasi Bukti (Admin)"]
    DASH --> GAJI["Penggajian"]
    DASH --> LAPORAN["Laporan & Riwayat"]
    DASH --> PROFIL["Profil"]

    SOPIR --> DASH
    TUJUAN --> DASH
    PERIODE --> DASH
    RITASE --> DASH
    VALIDASI_ADMIN --> DASH
    GAJI --> DASH
    LAPORAN --> DASH
    PROFIL --> DASH

    DASH --> LOGOUT["Logout"]
    LOGOUT --> GUEST
```
