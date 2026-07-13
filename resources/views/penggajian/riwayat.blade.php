<x-layouts.dashboard
    :title="'Riwayat Gaji'"
    :pageTitle="'Riwayat Gaji'"
    :user="auth()->user()">

    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Riwayat Gaji 📋</h2>
        <p class="text-gray-600">Daftar semua periode gaji yang telah dihitung</p>
    </div>

    <div class="bg-white rounded-2xl shadow-lg border-t-4 border-blue-500 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Periode</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Total Ritase</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total Solar</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total Upah</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total DT</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Grand Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($periodes as $periode)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold">{{ $periode['nama_periode'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $periode['total_ritase'] }}</td>
                            <td class="px-4 py-3 text-right text-blue-600">Rp {{ number_format($periode['total_solar'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-green-600">Rp {{ number_format($periode['total_sopir'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-purple-600">Rp {{ number_format($periode['total_dt'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-bold text-yellow-600">Rp {{ number_format($periode['grand_total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                Belum ada data gaji.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.dashboard>
