@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Parser Ritase dari Teks</h1>
        <p class="text-gray-600 mt-1">Paste teks jadwal sopir (format grup WA) untuk otomatis parsing & simpan ke database.</p>
    </div>

    <div class="bg-white shadow rounded-lg">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Format Teks yang Didukung</h2>
            <div class="mt-3 text-sm text-gray-600 space-y-2">
                <pre class="bg-gray-50 p-3 rounded overflow-x-auto text-xs">
<code>22 07 26 rabu
Bondan patching pare kota
Paket cmm blitar kota
1. Riki
2. Kola
3. Firsa
...
Paket watualang ngawi
1. Gun
2. Anjar
...</code></pre>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Baris 1</strong>: Tanggal format "DD MM YY hari" (contoh: <code>22 07 26 rabu</code> = 26 Juli 2022)</li>
                    <li><strong>Baris rute/paket</strong>: Baris yang mengandung nama lokasi (paket, bondan, patching, kota, kabupaten)</li>
                    <li><strong>Baris sopir</strong>: Bernomor urut <code>1. Nama</code>, <code>2. Nama</code>, dst.</li>
                    <li>Nama sopir bisa pakai panggilan: <code>Mbah POR</code>, <code>Eka bence</code>, dll.</li>
                </ul>
            </div>
        </div>

        <form method="POST" action="{{ route('ritase.parser.process') }}" class="p-6 space-y-6">
            @csrf

            @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div>
                <label for="text" class="block text-sm font-medium text-gray-700 mb-2">Teks Jadwal Sopir</label>
                <textarea name="text" id="text" rows="15" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                    placeholder="Paste teks di sini...">{{ old('text') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Maksimal 50.000 karakter</p>
            </div>

            <div>
                <label for="periode_id" class="block text-sm font-medium text-gray-700 mb-2">Periode <span class="text-red-500">*</span></label>
                <select name="periode_id" id="periode_id" required
                    class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Pilih Periode --</option>
                    @foreach ($periodes as $periode)
                    <option value="{{ $periode->id }}" {{ old('periode_id') == $periode->id ? 'selected' : '' }}>
                        {{ $periode->nama }} ({{ $periode->tanggal_mulai }} s/d {{ $periode->tanggal_selesai }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="auto_create" id="auto_create" value="1"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="auto_create" class="ml-2 block text-sm text-gray-700">
                    Simpan otomatis ke database setelah preview (jika dicentang)
                </label>
            </div>

            <div class="flex gap-4">
                <button type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    {{ old('auto_create') ? 'Parse & Simpan' : 'Parse & Preview' }}
                </button>
                <a href="{{ route('ritase.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300">
                    Kembali ke Data Ritase
                </a>
            </div>
        </form>
    </div>

    {{-- Contoh Cepat --}}
    <div class="mt-6 bg-white shadow rounded-lg">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-medium text-gray-900">Contoh Cepat (Klik untuk Isi Otomatis)</h3>
        </div>
        <div class="p-4">
            <button type="button" onclick="fillSample()" 
                class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                Isi Contoh Data Dumptruck
            </button>
        </div>
    </div>
</div>

<script>
const sampleText = `22 07 26 rabu
Bondan patching pare kota
Paket cmm blitar kota
1. Riki
2. Kola
3. Firsa
4. Wahyu
5. Ginem
6. Mbah POR 
7. Didik 
8. Yuri
9. Agung
Paket watualang ngawi
1. Gun
2. Anjar
3. Wilujeng
4. Yanto
5. Soim
6. Kuwat
7. Toni
8. Aripin
9. Avit
10. Radib
11. Topik
12. Narji
13. Eka bence
14. Prapto 
15. Berok
16. Manto
17. Eko Wilangan
18. Torik
19. Adib
20. Wakub`;

function fillSample() {
    document.getElementById('text').value = sampleText;
}
</script>
@endsection