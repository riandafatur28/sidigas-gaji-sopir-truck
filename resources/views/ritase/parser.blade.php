<x-layouts.dashboard
    :title="'Parser Ritase'"
    :pageTitle="'Parser Ritase'"
    :user="auth()->user()">
<div class="max-w-4xl mx-auto py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Parser Ritase</h1>
        <p class="text-gray-600 mt-1">Paste teks jadwal sopir (format grup WA) untuk di-parse otomatis.</p>
    </div>

    @if ($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Mode toggle --}}
    <div class="mb-6 bg-white border border-gray-200 rounded-lg p-1 inline-flex" role="group">
        <a href="{{ route('ritase.parser', ['mode' => 'rule']) }}"
            class="px-4 py-2 text-sm font-medium rounded-md {{ $mode === 'rule' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
            ⚙️ Rule-based
        </a>
        <a href="{{ route('ritase.parser', ['mode' => 'llm']) }}"
            class="px-4 py-2 text-sm font-medium rounded-md {{ $mode === 'llm' ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
            🤖 AI / LLM
        </a>
    </div>

    @if ($mode === 'llm')
    <div class="mb-4 p-3 bg-purple-50 border border-purple-200 rounded-lg text-sm text-purple-800">
        <strong>🤖 Mode AI / LLM:</strong> Coba parse pakai AI dulu. Kalau gagal (401/timeout), auto turun ke rule-based.
        <span class="block mt-1 text-purple-600">Confidence score + hallucination detection aktif.</span>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form --}}
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <form method="POST" action="{{ route('ritase.parser.process') }}" class="p-6 space-y-6">
                    @csrf
                    <input type="hidden" name="mode" value="{{ $mode }}">

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
                                {{ $periode->nama_periode }} ({{ $periode->tanggal_mulai }} s/d {{ $periode->tanggal_selesai }})
                            </option>
                            @endforeach
                        </select>
                    </div>



                    <div class="flex gap-4">
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <span class="flex items-center gap-2">
                                @if ($mode === 'llm')
                                <span>🤖</span>
                                @else
                                <span>⚙️</span>
                                @endif
                                Parse & Preview
                            </span>
                        </button>
                        <a href="{{ route('ritase.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300">
                            Kembali ke Data Ritase
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-900 mb-2">Format Teks</h3>
                <div class="text-sm text-gray-600 space-y-2">
                    <pre class="bg-gray-50 p-2 rounded text-xs overflow-x-auto">
<code>22 07 26 rabu
Bondan patching pare kota
Paket cmm blitar kota
1. Riki
2. Kola
3. Firsa
...
Paket watualang ngawi
1. Gun
2. Anjar</code></pre>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li><strong>Baris 1</strong>: Tanggal "DD MM YY hari"</li>
                        <li><strong>Rute</strong>: Baris dgn keyword (paket, bondan, dll)</li>
                        <li><strong>Sopir</strong>: Bernomor "1. Nama"</li>
                    </ul>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="font-medium text-gray-900 mb-2">Mode Parser</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800">Rule</span>
                        <span>Keyword-based, cepat, tanpa API</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 text-xs rounded bg-purple-100 text-purple-800">AI</span>
                        <span>LLM-powered, confidence score</span>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <button type="button" onclick="fillSample()"
                    class="w-full px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                    Isi Contoh Data
                </button>
            </div>
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
</x-layouts.dashboard>
