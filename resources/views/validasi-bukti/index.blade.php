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
                <select id="sopir_select" class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm bg-white focus:outline-none focus:border-green-500">
                    <option value="">-- Pilih Sopir --</option>
                    @foreach($sopirs as $s)
                        <option value="{{ $s->kode_sopir }}" data-nama="{{ $s->nama }}">{{ $s->kode_sopir }} - {{ $s->nama }}</option>
                    @endforeach
                    <option value="__baru__">+ Sopir Baru (tidak ada di daftar)</option>
                </select>
                <input type="text" id="sopir_baru_input" placeholder="Nama sopir baru..."
                    class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm mt-2 hidden focus:outline-none focus:border-green-500">
                <input type="text" id="sopir_nama_display" readonly
                    class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm mt-2 hidden bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tujuan</label>
                <select id="tujuan_select" class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm bg-white focus:outline-none focus:border-green-500">
                    <option value="">-- Pilih Tujuan --</option>
                    @foreach($tujuans as $t)
                        <option value="{{ $t->kode_tujuan }}" data-nama="{{ $t->nama }}">{{ $t->kode_tujuan }} - {{ $t->nama }}</option>
                    @endforeach
                    <option value="__baru__">+ Tujuan Baru (tidak ada di daftar)</option>
                </select>
                <input type="text" id="tujuan_baru_input" placeholder="Nama tujuan baru..."
                    class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm mt-2 hidden focus:outline-none focus:border-green-500">
                <input type="text" id="tujuan_nama_display" readonly
                    class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm mt-2 hidden bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan</label>
                <textarea name="catatan" rows="2" placeholder="Catatan tambahan (opsional)"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-green-500"></textarea>
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
                        class="flex-1 bg-green-600 text-white rounded text-sm font-semibold px-4 py-2.5 hover:bg-green-700 transition">
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
    window.validasiBuktiNgrokUrl = '{{ config("app.url", "") }}/validasi-bukti';
</script>
<script src="{{ asset('js/validasi-bukti.js') }}"></script>
</body>
</html>
