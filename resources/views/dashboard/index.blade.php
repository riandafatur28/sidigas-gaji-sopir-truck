<x-layouts.dashboard
    :title="'Dashboard'"
    :pageTitle="'Dashboard'"
    :user="auth()->user()">

    {{-- HEADER --}}
    <div class="border-b border-gray-200 pb-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-base text-gray-500 mt-1">Halo, {{ explode(' ', auth()->user()->name)[0] }}</p>
            </div>
            <div class="text-base text-gray-500">
                {{ now()->translatedFormat('d F Y') }}
            </div>
        </div>
    </div>

    {{-- METRIK CARD MENYAMPING (4 KOLOM) --}}
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="border border-gray-200 rounded px-5 py-4 bg-white">
            <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Armada Aktif</p>
            <p class="text-4xl font-bold text-gray-900 mt-1">{{ $sopirAktif }}</p>
            <p class="text-sm text-gray-400 mt-1">dari {{ $totalSopir }} total</p>
        </div>
        <div class="border border-gray-200 rounded px-5 py-4 bg-white">
            <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Ritase Selesai</p>
            <p class="text-4xl font-bold text-gray-900 mt-1">{{ number_format($totalRitase) }}</p>
            <p class="text-sm text-gray-400 mt-1">tervalidasi</p>
        </div>
        <div class="border border-gray-200 rounded px-5 py-4 bg-white">
            <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Menunggu Verifikasi</p>
            <p class="text-4xl font-bold text-gray-900 mt-1">{{ $ritasePending }}</p>
            <p class="text-sm text-gray-400 mt-1">perlu review</p>
        </div>
        <div class="border border-gray-200 rounded px-5 py-4 bg-white">
            <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Total Gaji</p>
            <p class="text-4xl font-bold text-gray-900 mt-1">
                @if($totalGaji >= 1000000)
                    Rp {{ number_format($totalGaji / 1000000, 1) }} Jt
                @else
                    Rp {{ number_format($totalGaji, 0, ',', '.') }}
                @endif
            </p>
            <p class="text-sm text-gray-400 mt-1">periode berjalan</p>
        </div>
    </div>

    {{-- MENU --}}
    <div class="w-full border border-gray-200 rounded mb-6 overflow-hidden bg-white">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3" colspan="2">Akses Cepat</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-5 py-3" colspan="2">
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('ritase.index') }}" class="text-sm text-gray-700 hover:text-gray-900 px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 font-medium">Input Ritase</a>
                            <a href="#" class="text-sm text-gray-700 hover:text-gray-900 px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 font-medium">Verifikasi</a>
                            <a href="{{ route('gaji.index') }}" class="text-sm text-gray-700 hover:text-gray-900 px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 font-medium">Hitung Gaji</a>
                            <a href="#" class="text-sm text-gray-700 hover:text-gray-900 px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 font-medium">Cetak Slip</a>
                            <a href="{{ route('sopir.index') }}" class="text-sm text-gray-700 hover:text-gray-900 px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 font-medium">Kelola Sopir</a>
                            <a href="{{ route('periode.index') }}" class="text-sm text-gray-700 hover:text-gray-900 px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 font-medium">Kelola Periode</a>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- AKTIVITAS & RINGKASAN --}}
    <div class="grid grid-cols-3 gap-6 mb-6">

        {{-- Aktivitas --}}
        <div class="col-span-2 w-full border border-gray-200 rounded overflow-hidden bg-white">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3">Aktivitas Terbaru</th>
                        <th class="text-right text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3">
                            <a href="{{ route('ritase.index') }}" class="text-gray-600 hover:text-gray-900">Lihat Semua →</a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $recentRitase = \App\Models\Ritase::with(['sopir', 'tujuan'])->latest()->limit(6)->get();
                    @endphp

                    @forelse($recentRitase as $rit)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <span class="text-base font-medium text-gray-800">{{ $rit->sopir->nama ?? 'Unknown' }}</span>
                                <span class="text-base text-gray-400">→</span>
                                <span class="text-base text-gray-600">{{ $rit->tujuan->nama ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <span class="text-base text-gray-400 mr-3">{{ $rit->tanggal->format('d/m/Y') }}</span>
                            <span class="text-sm
                                {{ $rit->status == 'valid' ? 'text-green-700' : '' }}
                                {{ $rit->status == 'pending' ? 'text-yellow-700' : '' }}
                                {{ $rit->status == 'gagal_produksi' ? 'text-red-700' : '' }}">
                                {{ $rit->status == 'valid' ? '✓ Selesai' : ($rit->status == 'pending' ? '○ Pending' : '✗ Gagal') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="px-5 py-6 text-center text-base text-gray-400">Belum ada aktivitas</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Ringkasan --}}
        <div class="w-full border border-gray-200 rounded overflow-hidden bg-white">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3" colspan="2">Ringkasan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-gray-100">
                        <td class="px-5 py-3 text-base text-gray-600">Total Armada</td>
                        <td class="px-5 py-3 text-right text-base font-bold text-gray-900">{{ $totalSopir }}</td>
                    </tr>
                    <tr class="border-b border-gray-100">
                        <td class="px-5 py-3 text-base text-gray-600">Ritase Selesai</td>
                        <td class="px-5 py-3 text-right text-base font-bold text-gray-900">{{ $ritaseValid ?? 0 }}</td>
                    </tr>
                    <tr class="border-b border-gray-100">
                        <td class="px-5 py-3 text-base text-gray-600">Menunggu Verifikasi</td>
                        <td class="px-5 py-3 text-right text-base font-bold text-gray-900">{{ $ritasePending }}</td>
                    </tr>
                    <tr class="border-b border-gray-100">
                        <td class="px-5 py-3 text-base text-gray-600">Gagal Produksi</td>
                        <td class="px-5 py-3 text-right text-base font-bold text-gray-900">{{ $ritaseGagal ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-3 text-base text-gray-600">Total Gaji</td>
                        <td class="px-5 py-3 text-right text-base font-bold text-gray-900">Rp {{ number_format($totalGaji, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- PROGRESS --}}
    <div class="w-full border border-gray-200 rounded overflow-hidden bg-white">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3">Metrik</th>
                    <th class="text-right text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3">Progress</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-100">
                    <td class="px-5 py-3 text-base text-gray-700">Produktivitas</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-3">
                            <span class="text-base font-medium text-gray-800">{{ rand(70, 95) }}%</span>
                            <div class="w-40 bg-gray-200 h-2">
                                <div class="bg-gray-800 h-2" style="width: {{ rand(70, 95) }}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr class="border-b border-gray-100">
                    <td class="px-5 py-3 text-base text-gray-700">Tingkat Validasi</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-3">
                            <span class="text-base font-medium text-gray-800">{{ rand(75, 98) }}%</span>
                            <div class="w-40 bg-gray-200 h-2">
                                <div class="bg-gray-800 h-2" style="width: {{ rand(75, 98) }}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="px-5 py-3 text-base text-gray-700">Ketepatan Waktu</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-3">
                            <span class="text-base font-medium text-gray-800">{{ rand(60, 90) }}%</span>
                            <div class="w-40 bg-gray-200 h-2">
                                <div class="bg-gray-800 h-2" style="width: {{ rand(60, 90) }}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</x-layouts.dashboard>
