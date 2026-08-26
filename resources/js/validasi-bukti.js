/**
 * Validasi Bukti Page - Camera, GPS, EXIF, Watermark
 * Expects globals: window.validasiBuktiNgrokUrl
 */
(function() {
    'use strict';

    // === DOM Elements ===
    var sopirSelect = document.getElementById('sopir_select');
    var sopirBaruInput = document.getElementById('sopir_baru_input');
    var sopirNamaDisplay = document.getElementById('sopir_nama_display');
    var sopirBaruHidden = document.getElementById('sopir_baru');
    var kodeSopirHidden = document.getElementById('kode_sopir');
    var namaSopirHidden = document.createElement('input');
    namaSopirHidden.type = 'hidden';
    namaSopirHidden.name = 'nama_sopir';
    document.getElementById('formBukti').appendChild(namaSopirHidden);

    var tujuanSelect = document.getElementById('tujuan_select');
    var tujuanBaruInput = document.getElementById('tujuan_baru_input');
    var tujuanNamaDisplay = document.getElementById('tujuan_nama_display');
    var tujuanBaruHidden = document.getElementById('tujuan_baru');
    var kodeTujuanHidden = document.getElementById('kode_tujuan');
    var namaTujuanHidden = document.createElement('input');
    namaTujuanHidden.type = 'hidden';
    namaTujuanHidden.name = 'nama_tujuan';
    document.getElementById('formBukti').appendChild(namaTujuanHidden);

    sopirSelect.addEventListener('change', function() {
        var val = this.value;
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
            var opt = this.options[this.selectedIndex];
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
        var val = this.value;
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
            var opt = this.options[this.selectedIndex];
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

    var canvas = document.getElementById('canvas');
    var cameraPlaceholder = document.getElementById('camera_placeholder');
    var btnAmbilFoto = document.getElementById('btnAmbilFoto');
    var btnUlang = document.getElementById('btnUlang');
    var fotoInput = document.getElementById('foto');
    var btnSubmit = document.getElementById('btnSubmit');
    var statusLokasi = document.getElementById('status_lokasi');

    var fileCamera = document.createElement('input');
    fileCamera.type = 'file';
    fileCamera.accept = 'image/*';
    fileCamera.capture = 'environment';
    fileCamera.style.display = 'none';
    document.body.appendChild(fileCamera);

    var fileGallery = document.createElement('input');
    fileGallery.type = 'file';
    fileGallery.accept = 'image/*';
    fileGallery.style.display = 'none';
    document.body.appendChild(fileGallery);

    function enableButtons() {
        btnAmbilFoto.disabled = false;
        btnAmbilFoto.className = 'flex-1 bg-green-600 text-white rounded text-sm font-semibold px-4 py-2.5 hover:bg-green-700 transition';
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
    var lokasiTerbaik = null;

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
        btn.className = 'ml-2 text-xs text-green-600 underline hover:text-green-800';
        btn.onclick = function() { this.remove(); cariGPS(false); };
        statusLokasi.appendChild(btn);
    }

    if (location.protocol === 'https:') {
        cariGPS();
    } else {
        statusLokasi.textContent = '⚠ HTTPS diperlukan untuk GPS. Buka via link ngrok. ';
        statusLokasi.className = 'text-xs text-red-600 mt-2 font-medium';
        var link = document.createElement('a');
        link.href = window.validasiBuktiNgrokUrl || '#';
        link.className = 'text-green-600 underline text-xs';
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

    pakaiIP();

    // === FOTO + EXIF GPS ===
    async function handleFile(file) {
        if (!file) return;

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
        var namaSopir = document.getElementsByName('nama_sopir')[0] ? document.getElementsByName('nama_sopir')[0].value : '-';
        var namaTujuan = document.getElementsByName('nama_tujuan')[0] ? document.getElementsByName('nama_tujuan')[0].value : '-';
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
        var nama = namaSopirHidden.value || sopirBaruInput.value;
        if (!nama) { alert('Silakan pilih atau masukkan nama sopir!'); return; }
        var tujuan = namaTujuanHidden.value || tujuanBaruInput.value;
        if (!tujuan) { alert('Silakan pilih atau masukkan tujuan!'); return; }
        if (!fotoInput.value) { alert('Silakan ambil foto terlebih dahulu!'); return; }

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
})();
