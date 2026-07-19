<x-layouts.dashboard
    :title="'Riwayat Gaji'"
    :pageTitle="'Riwayat Gaji'"
    :user="auth()->user()">

    <div class="border-b border-gray-200 pb-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Riwayat Gaji</h1>
                <p class="text-base text-gray-500 mt-1">Daftar semua periode gaji yang telah dihitung</p>
            </div>
        </div>
    </div>

    <div class="w-full border border-gray-200 rounded overflow-hidden bg-white">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3" colspan="8">
                        Riwayat Gaji
                        <span class="font-normal text-gray-400 text-xs ml-2">Total: {{ count($periodes) }} periode</span>
                    </th>
                </tr>
            </thead>
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Periode</th>
                    <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Sopir</th>
                    <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Ritase</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Solar</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Upah</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">DT</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Grand Total</th>
                    <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($periodes as $periode)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5">
                            <div class="text-sm font-medium text-gray-800">{{ $periode['nama_periode'] }}</div>
                            <div class="text-xs text-gray-400">
                                {{ \Carbon\Carbon::parse($periode['tanggal_mulai'])->format('d/m/Y') }}
                                -
                                {{ \Carbon\Carbon::parse($periode['tanggal_selesai'])->format('d/m/Y') }}
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-center text-sm font-medium text-gray-700">{{ $periode['jumlah_sopir'] }} org</td>
                        <td class="px-4 py-2.5 text-center text-sm text-gray-600">{{ $periode['total_ritase'] }} rit</td>
                        <td class="px-4 py-2.5 text-right text-sm font-medium text-gray-800">Rp {{ number_format($periode['total_solar'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm font-medium text-gray-800">Rp {{ number_format($periode['total_upah'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm font-medium text-gray-800">Rp {{ number_format($periode['total_dt'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm font-bold text-gray-900">Rp {{ number_format($periode['grand_total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('gaji.index', ['periode' => $periode['id']]) }}"
                                   class="inline-flex items-center px-2.5 py-1.5 bg-blue-50 text-blue-700 rounded text-xs font-medium hover:bg-blue-100 transition">
                                    Detail
                                </a>
                                <a href="{{ route('gaji.slip-pdf', $periode['id']) }}"
                                   class="inline-flex items-center px-2.5 py-1.5 bg-gray-50 text-gray-700 rounded text-xs font-medium hover:bg-gray-100 transition">
                                    Slip
                                </a>
                                <a href="{{ route('gaji.laporan-pdf', $periode['id']) }}"
                                   class="inline-flex items-center px-2.5 py-1.5 bg-green-50 text-green-700 rounded text-xs font-medium hover:bg-green-100 transition">
                                    Laporan
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-400">Belum ada data gaji.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.dashboard>