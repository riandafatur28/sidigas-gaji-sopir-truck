<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Bukti</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/js/exifr.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-white rounded shadow border border-gray-200">
        <div class="px-6 py-5 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-900">Validasi Bukti</h1>
            <p class="text-sm text-gray-500 mt-0.5">Kirim bukti pekerjaan untuk diverifikasi mitra</p>
        </div>

        @if(session('success'))
            <div class="mx-6 mt-4 border border-green-200 bg-green-50 text-green-700 px-4 py-3 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mx-6 mt-4 border border-red-200 bg-red-50 text-red-700 px-4 py-3 rounded text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

            <form id="formBukti" method="POST" action="/validasi-bukti" class="p-6 space-y-5">
            @csrf

            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <input type="hidden" name="lokasi" id="lokasi">
            <input type="hidden" name="waktu_foto" id="waktu_foto">
            <input type="hidden" name="tanggal" id="tanggal">
            <input type="hidden" name="foto" id="foto">
            <input type="hidden" name="sopir_baru" id="sopir_baru" value="0">
            <input type="hidden" name="tujuan_baru" id="tujuan_baru" value="0">
            <input type="hidden" name="kode_sopir" id="kode_sopir">
            <input type="hidden" name="kode_tujuan" id="kode_tujuan">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Sopir</label>
                <select id="sopir_select" class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm bg-white focus:outline-none focus:border-blue-500">
                    <option value="">-- Pilih Sopir --</option>
                    @foreach($sopirs as $s)
                        <option value="{{ $s->kode_sopir }}" data-nama="{{ $s->nama }}">{{ $s->kode_sopir }} - {{ $s->nama }}</option>
                    @endforeach
                    <option value="__baru__">+ Sopir Baru (tidak ada di daftar)</option>
                </select>
                <input type="text" id="sopir_baru_input" placeholder="Nama sopir baru..."
                    class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm mt-2 hidden focus:outline-none focus:border-blue-500">
                <input type="text" id="sopir_nama_display" readonly
                    class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm mt-2 hidden bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tujuan</label>
                <select id="tujuan_select" class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm bg-white focus:outline-none focus:border-blue-500">
                    <option value="">-- Pilih Tujuan --</option>
                    @foreach($tujuans as $t)
                        <option value="{{ $t->kode_tujuan }}" data-nama="{{ $t->nama }}">{{ $t->kode_tujuan }} - {{ $t->nama }}</option>
                    @endforeach
                    <option value="__baru__">+ Tujuan Baru (tidak ada di daftar)</option>
                </select>
                <input type="text" id="tujuan_baru_input" placeholder="Nama tujuan baru..."
                    class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm mt-2 hidden focus:outline-none focus:border-blue-500">
                <input type="text" id="tujuan_nama_display" readonly
                    class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm mt-2 hidden bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan</label>
                <textarea name="catatan" rows="2" placeholder="Catatan tambahan (opsional)"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Foto Bukti</label>
                <div id="camera_container" class="border-2 border-dashed border-gray-300 rounded p-4 text-center">
                    <video id="video" autoplay playsinline class="w-full rounded hidden"></video>
                    <canvas id="canvas" class="w-full rounded hidden"></canvas>
                    <div id="camera_placeholder" class="py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <p class="text-sm text-gray-400">Kamera akan dimulai saat tombol di bawah diklik</p>
                    </div>
                    <img id="foto_preview" class="w-full rounded hidden">
                </div>
                <div class="flex gap-2 mt-3">
                    <button type="button" id="btnAmbilFoto"
                        class="flex-1 bg-blue-600 text-white rounded text-sm font-semibold px-4 py-2.5 hover:bg-blue-700 transition">
                        Ambil Foto
                    </button>
                    <button type="button" id="btnUlang" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition hidden">
                        Ulang
                    </button>
                </div>
                <p id="status_lokasi" class="text-xs text-gray-400 mt-2">Mendapatkan lokasi...</p>
            </div>

            <button type="button" id="btnSubmit"
                class="w-full bg-gray-300 text-gray-500 rounded text-sm font-semibold px-5 py-3 transition cursor-not-allowed" disabled>
                Kirim Bukti
            </button>
    </form>
</div>

<!-- Modal Verifikasi -->
<div id="modalVerifikasi" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-lg max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="p-5 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Verifikasi Data</h2>
            <p class="text-sm text-gray-500">Pastikan data berikut sudah benar</p>
        </div>
        <div class="p-5 space-y-4">
            <img id="modalFoto" class="w-full rounded border border-gray-200">
            <div class="text-sm space-y-1.5 bg-gray-50 rounded p-3">
                <p><span class="font-medium text-gray-600">Sopir:</span> <span id="modalSopir" class="text-gray-900"></span></p>
                <p><span class="font-medium text-gray-600">Tujuan:</span> <span id="modalTujuan" class="text-gray-900"></span></p>
                <p><span class="font-medium text-gray-600">Koordinat:</span> <span id="modalKoordinat" class="text-gray-900"></span></p>
                <p><span class="font-medium text-gray-600">Lokasi:</span> <span id="modalLokasi" class="text-gray-900 text-xs"></span></p>
                <p><span class="font-medium text-gray-600">Waktu:</span> <span id="modalWaktu" class="text-gray-900"></span></p>
            </div>
        </div>
        <div class="p-5 border-t border-gray-200 flex gap-3">
            <button type="button" id="btnBatalModal"
                class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">
                Batal
            </button>
            <button type="button" id="btnKirimModal"
                class="flex-1 bg-gray-900 text-white rounded text-sm font-semibold px-4 py-2.5 hover:bg-gray-800 transition">
                Ya, Kirim
            </button>
        </div>
    </div>
</div>

<script>
        const sopirSelect = document.getElementById('sopir_select');
        const sopirBaruInput = document.getElementById('sopir_baru_input');
        const sopirNamaDisplay = document.getElementById('sopir_nama_display');
        const sopirBaruHidden = document.getElementById('sopir_baru');
        const kodeSopirHidden = document.getElementById('kode_sopir');
        const namaSopirHidden = document.createElement('input');
        namaSopirHidden.type = 'hidden';
        namaSopirHidden.name = 'nama_sopir';
        document.getElementById('formBukti').appendChild(namaSopirHidden);

        const tujuanSelect = document.getElementById('tujuan_select');
        const tujuanBaruInput = document.getElementById('tujuan_baru_input');
        const tujuanNamaDisplay = document.getElementById('tujuan_nama_display');
        const tujuanBaruHidden = document.getElementById('tujuan_baru');
        const kodeTujuanHidden = document.getElementById('kode_tujuan');
        const namaTujuanHidden = document.createElement('input');
        namaTujuanHidden.type = 'hidden';
        namaTujuanHidden.name = 'nama_tujuan';
        document.getElementById('formBukti').appendChild(namaTujuanHidden);

        sopirSelect.addEventListener('change', function() {
            const val = this.value;
            if (val === '__baru__') {
                sopirBaruInput.classList.remove('hidden');
                sopirNamaDisplay.classList.add('hidden');
                sopirBaruHidden.value = '1';
                kodeSopirHidden.value = '';
                namaSopirHidden.value = '';
            } else if (val) {
                sopirBaruInput.classList.add('hidden');
                sopirNamaDisplay.classList.remove('hidden');
                sopirBaruHidden.value = '0';
                const opt = this.options[this.selectedIndex];
                kodeSopirHidden.value = opt.value;
                namaSopirHidden.value = opt.dataset.nama;
                sopirNamaDisplay.value = opt.dataset.nama;
            } else {
                sopirBaruInput.classList.add('hidden');
                sopirNamaDisplay.classList.add('hidden');
                sopirBaruHidden.value = '0';
                kodeSopirHidden.value = '';
                namaSopirHidden.value = '';
            }
        });

        sopirBaruInput.addEventListener('input', function() {
            namaSopirHidden.value = this.value;
        });

        tujuanSelect.addEventListener('change', function() {
            const val = this.value;
            if (val === '__baru__') {
                tujuanBaruInput.classList.remove('hidden');
                tujuanNamaDisplay.classList.add('hidden');
                tujuanBaruHidden.value = '1';
                kodeTujuanHidden.value = '';
                namaTujuanHidden.value = '';
            } else if (val) {
                tujuanBaruInput.classList.add('hidden');
                tujuanNamaDisplay.classList.remove('hidden');
                tujuanBaruHidden.value = '0';
                const opt = this.options[this.selectedIndex];
                kodeTujuanHidden.value = opt.value;
                namaTujuanHidden.value = opt.dataset.nama;
                tujuanNamaDisplay.value = opt.dataset.nama;
            } else {
                tujuanBaruInput.classList.add('hidden');
                tujuanNamaDisplay.classList.add('hidden');
                tujuanBaruHidden.value = '0';
                kodeTujuanHidden.value = '';
                namaTujuanHidden.value = '';
            }
        });

        tujuanBaruInput.addEventListener('input', function() {
            namaTujuanHidden.value = this.value;
        });

        const canvas = document.getElementById('canvas');
        const cameraPlaceholder = document.getElementById('camera_placeholder');
        const btnAmbilFoto = document.getElementById('btnAmbilFoto');
        const btnUlang = document.getElementById('btnUlang');
        const fotoInput = document.getElementById('foto');
        const btnSubmit = document.getElementById('btnSubmit');
        const statusLokasi = document.getElementById('status_lokasi');

        // Hidden file input untuk kamera
        const fileCamera = document.createElement('input');
        fileCamera.type = 'file';
        fileCamera.accept = 'image/*';
        fileCamera.capture = 'environment';
        fileCamera.style.display = 'none';
        document.body.appendChild(fileCamera);

        // Hidden file input untuk galeri
        const fileGallery = document.createElement('input');
        fileGallery.type = 'file';
        fileGallery.accept = 'image/*';
        fileGallery.style.display = 'none';
        document.body.appendChild(fileGallery);

        function enableButtons() {
            btnAmbilFoto.disabled = false;
            btnAmbilFoto.className = 'flex-1 bg-blue-600 text-white rounded text-sm font-semibold px-4 py-2.5 hover:bg-blue-700 transition';
            btnAmbilFoto.textContent = 'Ambil Foto';
            var g = document.getElementById('btnGaleri');
            if (g) {
                g.disabled = false;
                g.style.opacity = '1';
                g.style.cursor = 'pointer';
            }
        }

        // === GEOLOCATION ===
        var lokasiDitemukan = false;
        var lokasiTerbaik = null; // {lat, lng, sumber}

        statusLokasi.textContent = 'Mendapatkan lokasi...';
        statusLokasi.className = 'text-xs text-yellow-600 mt-2 font-medium';

        function setLokasi(lat, lng, sumber) {
            if (lokasiTerbaik) {
                var prioritas = { 'gps': 3, 'exif': 2, 'ip': 1 };
                if ((prioritas[sumber] || 0) <= (prioritas[lokasiTerbaik.sumber] || 0)) return;
            }
            lokasiTerbaik = { lat: lat, lng: lng, sumber: sumber };
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            statusLokasi.textContent = sumber.toUpperCase() + ': ' + lat + ', ' + lng;
            statusLokasi.className = 'text-xs text-green-600 mt-2 font-medium';
            fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&accept-language=id')
                .then(function(r) { return r.json(); })
                .then(function(d) { document.getElementById('lokasi').value = d.display_name || ''; })
                .catch(function() {});
            if (!lokasiDitemukan) {
                lokasiDitemukan = true;
                enableButtons();
            }
        }

        function cariGPS(tryAgain) {
            if (!navigator.geolocation) return;
            if (lokasiTerbaik && lokasiTerbaik.sumber === 'gps') return;
            var label = tryAgain ? 'Mencoba ulang GPS...' : 'Mencari GPS (butuh izin lokasi)...';
            statusLokasi.textContent = label;
            statusLokasi.className = 'text-xs text-yellow-600 mt-2 font-medium';

            // Hapus tombol retry kalo ada
            var oldBtn = document.getElementById('btnRetryGps');
            if (oldBtn) oldBtn.remove();

            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    setLokasi(pos.coords.latitude.toFixed(6), pos.coords.longitude.toFixed(6), 'gps');
                },
                function(err) {
                    var msg = '';
                    if (err.code === 1) {
                        msg = 'GPS diblokir. ' + (location.protocol === 'https:' ? '' : 'Akses via HTTPS (ngrok) biar bisa GPS. ');
                    } else if (!tryAgain) {
                        msg = 'GPS lambat, coba lagi 5 detik...';
                        statusLokasi.textContent = msg;
                        statusLokasi.className = 'text-xs text-yellow-600 mt-2 font-medium';
                        setTimeout(function() { cariGPS(true); }, 5000);
                        return;
                    } else {
                        msg = 'GPS gagal. ' + (location.protocol === 'https:' ? 'Coba klik tombol "Coba GPS".' : 'Akses via HTTPS (ngrok) biar GPS work.');
                    }
                    statusLokasi.textContent = msg;
                    statusLokasi.className = 'text-xs text-red-600 mt-2 font-medium';
                    tambahTombolRetry();
                },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        }

        function tambahTombolRetry() {
            if (document.getElementById('btnRetryGps')) return;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.id = 'btnRetryGps';
            btn.textContent = 'Coba GPS';
            btn.className = 'ml-2 text-xs text-blue-600 underline hover:text-blue-800';
            btn.onclick = function() { this.remove(); cariGPS(false); };
            statusLokasi.appendChild(btn);
        }

        // Cari GPS langsung pas halaman dibuka (+ retry otomatis)
        if (location.protocol === 'https:') {
            cariGPS();
        } else {
            statusLokasi.textContent = '⚠ HTTPS diperlukan untuk GPS. Buka via link ngrok. ';
            statusLokasi.className = 'text-xs text-red-600 mt-2 font-medium';
            var link = document.createElement('a');
            link.href = 'https://wispy-vacancy-smokeless.ngrok-free.dev/validasi-bukti';
            link.className = 'text-blue-600 underline text-xs';
            link.textContent = 'Buka via HTTPS';
            statusLokasi.appendChild(link);
        }

        function pakaiIP() {
            fetch('https://ip-api.com/json/?fields=status,lat,lon,city,regionName')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status !== 'success') throw new Error('fail');
                    if (!data.lat || !data.lon) throw new Error('no coords');
                    setLokasi(data.lat, data.lon, 'ip');
                })
                .catch(function() {
                    fetch('https://ipapi.co/json/')
                        .then(function(r) { return r.json(); })
                        .then(function(d2) {
                            if (!d2.latitude || !d2.longitude) throw new Error('no coords');
                            setLokasi(d2.latitude, d2.longitude, 'ip');
                        })
                        .catch(function() {
                            statusLokasi.textContent = 'Lokasi tidak tersedia.';
                            statusLokasi.className = 'text-xs text-red-600 mt-2 font-medium';
                            lokasiDitemukan = true;
                            enableButtons();
                        });
                });
        }

        // Mulai dengan IP (langsung dapat, walau kurang akurat)
        pakaiIP();

        // === FOTO + EXIF GPS ===
        async function handleFile(file) {
            if (!file) return;

            // WAIT: EXIF GPS dulu (biar watermark pake koordinat foto)
            try {
                if (typeof exifr !== 'undefined') {
                    var gps = await exifr.parse(file, ['latitude', 'longitude']);
                    if (gps && gps.latitude && gps.longitude) {
                        setLokasi(gps.latitude, gps.longitude, 'exif');
                    }
                }
            } catch(e) {}

            var reader = new FileReader();
            reader.onload = function(e) {
                var img = new Image();
                img.onload = function() {
                    canvas.width = img.width;
                    canvas.height = img.height;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    addWatermark(ctx, canvas.width, canvas.height);

                    canvas.classList.remove('hidden');
                    cameraPlaceholder.classList.add('hidden');
                    fotoInput.value = canvas.toDataURL('image/jpeg', 0.85);

                    var now = new Date();
                    document.getElementById('waktu_foto').value = now.toISOString();
                    document.getElementById('tanggal').value = now.toISOString().slice(0, 10);

                    btnAmbilFoto.classList.add('hidden');
                    var g = document.getElementById('btnGaleri');
                    if (g) g.classList.add('hidden');
                    btnUlang.classList.remove('hidden');
                    btnSubmit.disabled = false;
                    btnSubmit.className = 'w-full bg-gray-900 text-white rounded text-sm font-semibold px-5 py-3 hover:bg-gray-800 transition';
                    btnSubmit.textContent = 'Kirim Bukti';
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        fileCamera.addEventListener('change', function() { handleFile(this.files[0]); this.value = ''; });
        fileGallery.addEventListener('change', function() { handleFile(this.files[0]); this.value = ''; });

        btnAmbilFoto.addEventListener('click', function() {
            cariGPS(true);
            fileCamera.click();
        });

        // Kalo user klik body, coba GPS lagi (gesture = permission chance)
        document.addEventListener('click', function() {
            if (!lokasiTerbaik || lokasiTerbaik.sumber !== 'gps') {
                cariGPS(true);
            }
        }, { once: true });

        var btnGaleri = document.createElement('button');
        btnGaleri.type = 'button';
        btnGaleri.id = 'btnGaleri';
        btnGaleri.className = 'flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 transition';
        btnGaleri.textContent = 'Pilih dari Galeri';
        btnGaleri.disabled = true;
        btnGaleri.style.opacity = '0.5';
        btnGaleri.style.cursor = 'not-allowed';
        btnGaleri.addEventListener('click', function() { fileGallery.click(); });
        btnAmbilFoto.parentNode.insertBefore(btnGaleri, btnUlang);

        function addWatermark(ctx, w, h) {
            var lat = document.getElementById('latitude').value || '-';
            var lng = document.getElementById('longitude').value || '-';
            var lokasi = document.getElementById('lokasi').value || '-';
            var namaSopir = document.getElementsByName('nama_sopir')[0]?.value || '-';
            var namaTujuan = document.getElementsByName('nama_tujuan')[0]?.value || '-';
            var now = new Date();
            var dateStr = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            var timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            ctx.fillStyle = 'rgba(0,0,0,0.6)';
            var barH = Math.max(90, Math.round(h * 0.1));
            ctx.fillRect(0, h - barH, w, barH);

            ctx.fillStyle = '#ffffff';
            var fontSize = Math.max(12, Math.round(w * 0.025));
            ctx.font = 'bold ' + fontSize + 'px sans-serif';
            ctx.textBaseline = 'top';
            var pad = 10;
            var lineH = fontSize + 4;
            ctx.fillText('Sopir: ' + namaSopir, pad, h - barH + pad);
            ctx.fillText('Tujuan: ' + namaTujuan, pad, h - barH + pad + lineH);
            ctx.fillText('Koordinat: ' + lat + ', ' + lng, pad, h - barH + pad + 2 * lineH);
            ctx.fillText(lokasi, pad, h - barH + pad + 3 * lineH);
            ctx.fillText(dateStr + ' ' + timeStr, pad, h - barH + pad + 4 * lineH);
        }

        btnUlang.addEventListener('click', function() {
            fotoInput.value = '';
            canvas.classList.add('hidden');
            cameraPlaceholder.classList.remove('hidden');
            document.getElementById('foto_preview').classList.add('hidden');
            btnAmbilFoto.classList.remove('hidden');
            var g = document.getElementById('btnGaleri');
            if (g) g.classList.remove('hidden');
            btnUlang.classList.add('hidden');
            btnSubmit.disabled = true;
            btnSubmit.className = 'w-full bg-gray-300 text-gray-500 rounded text-sm font-semibold px-5 py-3 transition cursor-not-allowed';
            btnSubmit.textContent = 'Kirim Bukti';
        });

        // Modal verifikasi
        var modal = document.getElementById('modalVerifikasi');
        var btnBatalModal = document.getElementById('btnBatalModal');
        var btnKirimModal = document.getElementById('btnKirimModal');

        btnSubmit.addEventListener('click', function() {
            if (btnSubmit.disabled) return;
            // Validasi
            var nama = namaSopirHidden.value || sopirBaruInput.value;
            if (!nama) { alert('Silakan pilih atau masukkan nama sopir!'); return; }
            var tujuan = namaTujuanHidden.value || tujuanBaruInput.value;
            if (!tujuan) { alert('Silakan pilih atau masukkan tujuan!'); return; }
            if (!fotoInput.value) { alert('Silakan ambil foto terlebih dahulu!'); return; }

            // Isi modal
            document.getElementById('modalFoto').src = canvas.toDataURL('image/jpeg');
            document.getElementById('modalSopir').textContent = nama;
            document.getElementById('modalTujuan').textContent = tujuan;
            document.getElementById('modalKoordinat').textContent =
                (document.getElementById('latitude').value || '-') + ', ' +
                (document.getElementById('longitude').value || '-');
            document.getElementById('modalLokasi').textContent =
                document.getElementById('lokasi').value || '-';
            document.getElementById('modalWaktu').textContent =
                new Date().toLocaleDateString('id-ID', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) +
                ' ' + new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });

            modal.classList.remove('hidden');
        });

        btnBatalModal.addEventListener('click', function() {
            modal.classList.add('hidden');
        });

        btnKirimModal.addEventListener('click', function() {
            modal.classList.add('hidden');
            document.getElementById('formBukti').submit();
        });

        modal.addEventListener('click', function(e) {
            if (e.target === modal) modal.classList.add('hidden');
        });

        // Hapus submit handler lama (pindah ke tombol modal)
        // Validasi form tetap jalan via HTML5 constraint validation
    </script>
</body>
</html>
