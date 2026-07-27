<x-layouts.dashboard
    :title="'Hasil Parser Ritase'"
    :pageTitle="'Hasil Parser Ritase'"
    >
<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Hasil Parsing Teks Ritase</h1>
            <p class="text-sm text-gray-500 mt-1">NER-based matching (exact → phonetic → substring → similarity)</p>
        </div>
        <a href="{{ route('ritase.parser') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">← Kembali</a>
    </div>

    @if (isset($results['errors']) && count($results['errors']) > 0)
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded">
        <strong>Error:</strong>
        <ul class="mt-2 list-disc list-inside">
            @foreach ($results['errors'] as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Summary --}}
    @php
        $totalDrivers = collect($results['packages'])->pluck('drivers')->flatten()->count();
        $driverMatched = collect($results['driver_matches'])->where('matched', true)->count();
        $routeMatched = collect($results['route_matches'])->where('matched', true)->count();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
            <p style="color:var(--text-muted);font-size:13px">Tanggal</p>
            <p class="text-2xl font-bold text-gray-900">{{ $results['date'] ?? '-' }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
            <p style="color:var(--text-muted);font-size:13px">Total Paket/Rute</p>
            <p class="text-2xl font-bold text-blue-600">{{ count($results['packages'] ?? []) }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
            <p style="color:var(--text-muted);font-size:13px">Total Sopir</p>
            <p class="text-2xl font-bold text-green-600">{{ $totalDrivers }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
            <p style="color:var(--text-muted);font-size:13px">Sopir Ter-match</p>
            <p class="text-2xl font-bold text-purple-600">{{ $driverMatched }}</p>
        </div>
    </div>

    {{-- Driver matches --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Pencocokan Sopir ({{ $driverMatched }}/{{ $totalDrivers }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama Input</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Match DB</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Confidence</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($results['driver_matches'] as $m)
                    <tr class="{{ !$m['matched'] ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-2 text-sm font-medium">{{ $m['input_name'] }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if ($m['matched'])
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ditemukan</span>
                            @else
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Tidak Ditemukan</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm">{{ $m['sopir']['nama'] ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $m['sopir']['kode_sopir'] ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if ($m['matched'])
                            <span class="{{ $m['confidence'] >= 90 ? 'text-green-600' : ($m['confidence'] >= 75 ? 'text-yellow-600' : 'text-orange-600') }}">
                                {{ $m['confidence'] }}%
                            </span>
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Route matches --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Pencocokan Rute ({{ $routeMatched }}/{{ count($results['route_matches']) }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rute Input</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Match DB</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Confidence</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($results['route_matches'] as $m)
                    <tr class="{{ !$m['matched'] ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-2 text-sm font-medium">{{ $m['input_route'] }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if ($m['matched'])
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ditemukan</span>
                            @else
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Tidak Ditemukan</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm">{{ $m['tujuan']['nama'] ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $m['tujuan']['kode_tujuan'] ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if ($m['matched'])
                            <span class="{{ $m['confidence'] >= 80 ? 'text-green-600' : 'text-yellow-600' }}">
                                {{ $m['confidence'] }}%
                            </span>
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Package details --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Detail Paket & Sopir</h2>
        </div>
        <div class="p-4">
            @foreach ($results['packages'] as $index => $pkg)
            <div class="mb-6 pb-6 border-b border-gray-200 last:border-0 last:pb-0">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-md font-semibold text-gray-900">
                        @if (!empty($pkg['is_rit_ke_2']))
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded bg-purple-100 text-purple-800 mr-2">RIT KE 2</span>
                        @elseif (!empty($pkg['is_bongkar']))
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded bg-orange-100 text-orange-800 mr-2">BONGKAR</span>
                        @endif
                        Paket {{ $index + 1 }}: {{ $pkg['route_name'] }}
                    </h3>
                    <span style="color:var(--text-muted);font-size:13px">
                        {{ count($pkg['drivers']) }} sopir
                        @if (!empty($pkg['is_bongkar']) && !empty($pkg['bongkar_source_route']))
                            · lembur from <strong>{{ $pkg['bongkar_source_route'] }}</strong>
                        @endif
                    </span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach ($pkg['drivers'] as $driverName)
                    @php
                        $match = collect($results['driver_matches'])->firstWhere('input_name', $driverName);
                    @endphp
                    <div class="px-3 py-2 text-sm rounded border
                        @if ($match && $match['matched'])
                            bg-green-50 border-green-200 text-green-800
                        @else
                            bg-red-50 border-red-200 text-red-800
                        @endif">
                        {{ $driverName }}
                        @if ($match && $match['matched'])
                        <span class="ml-1 text-xs opacity-75">({{ $match['confidence'] }}%)</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Details log --}}
    @if (isset($results['details']) && count($results['details']) > 0)
    <div class="mt-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Log Aksi</h2>
        </div>
        <div class="p-4">
            <ul class="divide-y divide-gray-200">
                @foreach ($results['details'] as $detail)
                <li class="py-2 text-sm {{
                    $detail['status'] === 'Created' ? 'text-green-700' :
                    ($detail['status'] === 'Updated lembur' ? 'text-blue-700' : 'text-yellow-700')
                }}">
                    <strong>{{ $detail['route'] }}</strong>: {{ $detail['status'] }}
                    @if (isset($detail['reason']))
                    <em>({{ $detail['reason'] }})</em>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Action buttons --}}
    @if (($results['created'] ?? 0) > 0)
    <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
        <p class="text-green-800"><strong>Berhasil!</strong> {{ $results['created'] }} data ritase telah disimpan.</p>
        @if (($results['skipped'] ?? 0) > 0)
        <p class="text-yellow-700 text-sm mt-1">{{ $results['skipped'] }} data dilewati (duplikat/error).</p>
        @endif
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('gaji.index', ['periode' => $periodeId]) }}"
               class="inline-flex items-center px-5 py-2.5 bg-[#1a1a2e] text-white font-semibold rounded-md hover:bg-[#2d2d44] transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Lanjut ke Hitung Gaji
            </a>
            <a href="{{ route('ritase.index') }}" class="text-green-600 hover:underline inline-flex items-center">Lihat Data Ritase</a>
            <span class="text-gray-400">|</span>
            <a href="{{ route('ritase.parser') }}" class="text-green-600 hover:underline inline-flex items-center">Parse Lagi</a>
        </div>
        <p class="text-xs text-gray-400 mt-3">Setelah masuk halaman Gaji, tabel akan otomatis terload dengan data ritase terbaru.</p>
    </div>
    @else
    <div class="mt-6 flex justify-end gap-3">
        <form action="{{ route('ritase.parser.process') }}" method="POST" class="inline">
            @csrf
            <input type="hidden" name="periode_id" value="{{ request()->periode_id ?? old('periode_id') }}">
            <input type="hidden" name="text" value="{{ request()->text ?? old('text') }}">
            <input type="hidden" name="auto_create" value="1">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700">
                Simpan Semua ke Database
            </button>
        </form>
        <a href="{{ route('ritase.parser') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300">
            Edit Ulang
        </a>
    </div>
    @endif
</div>
</x-layouts.dashboard>
