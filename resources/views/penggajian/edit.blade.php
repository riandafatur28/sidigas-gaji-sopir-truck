<x-layouts.dashboard
    :title="'Edit Gaji'"
    :pageTitle="'Edit Gaji'"
    :user="auth()->user()">

    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Edit Gaji Periode {{ $periode->nama_periode }}</h2>
        <p class="text-gray-600">Edit biaya per tujuan dan kompensasi gagal produksi</p>
    </div>

    @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl">
            <p class="text-red-800 font-semibold">{{ session('error') }}</p>
        </div>
    @endif

    <form id="formGaji" action="{{ route('gaji.update', $periode->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border-t-4 border-yellow-500">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Input Biaya Per Tujuan</h3>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Periode</label>
                <select name="periode_id" class="w-full md:w-1/2 px-4 py-3 bg-gray-100 border-2 border-gray-300 rounded-xl text-gray-600 cursor-not-allowed" disabled>
                    <option value="{{ $periode->id }}">{{ $periode->nama_periode }}</option>
                </select>
                <input type="hidden" name="periode_id" value="{{ $periode->id }}">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tujuan</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">BBM/Rit</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Upah/Rit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($allTujuans as $tujuan)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-800">{{ $tujuan->nama }}</p>
                                <p class="text-xs text-gray-500">{{ $tujuan->kode_tujuan }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold text-sm">Rp</span>
                                    <input type="number"
                                           name="detail[{{ $loop->index }}][bbm_per_rit]"
                                           min="0"
                                           step="0.01"
                                           value="{{ $detailPerTujuan[$tujuan->kode_tujuan]['bbm_per_rit'] ?? 0 }}"
                                           class="w-full pl-10 pr-4 py-2 bg-white border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-gray-900">
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold text-sm">Rp</span>
                                    <input type="number"
                                           name="detail[{{ $loop->index }}][upah_per_rit]"
                                           min="0"
                                           step="0.01"
                                           value="{{ $detailPerTujuan[$tujuan->kode_tujuan]['upah_per_rit'] ?? 0 }}"
                                           class="w-full pl-10 pr-4 py-2 bg-white border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-gray-900">
                                </div>
                            </td>
                            <input type="hidden" name="detail[{{ $loop->index }}][kode_tujuan]" value="{{ $tujuan->kode_tujuan }}">
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabel Per Sopir -->
        <div class="bg-white rounded-2xl shadow-lg border-t-4 border-blue-500 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Rincian Gaji Per Sopir</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Sopir</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Total Rit</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total Solar</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total Upah</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total DT</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Kompensasi</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Grand Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($existingGaji as $gaji)
                            @php
                                $totalRit = $gaji->details->sum('jumlah_rit');
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center">
                                            <span class="text-blue-800 font-bold text-xs">{{ substr($gaji->sopir->nama, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $gaji->sopir->nama }}</p>
                                            <p class="text-xs text-gray-500">{{ $gaji->kode_sopir }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-semibold">{{ $totalRit }}</td>
                                <td class="px-4 py-3 text-right text-blue-600">Rp {{ number_format($gaji->uang_solar, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-green-600">Rp {{ number_format($gaji->upah_sopir, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-purple-600">Rp {{ number_format($gaji->dt, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-orange-600">
                                    <input type="number"
                                           name="kompensasi_gagal[{{ $gaji->kode_sopir }}]"
                                           min="0"
                                           step="0.01"
                                           value="{{ $kompensasiGagal[$gaji->kode_sopir] ?? 0 }}"
                                           class="w-28 px-2 py-1 border-2 border-gray-300 rounded-lg text-right focus:ring-2 focus:ring-yellow-500">
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-yellow-600">Rp {{ number_format($gaji->total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-right font-bold text-gray-800 text-lg">TOTAL KESELURUHAN:</td>
                            <td class="px-4 py-4 text-right font-bold text-yellow-600 text-xl">
                                Rp {{ number_format($existingGaji->sum('total'), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('gaji.index') }}" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-xl">Batal</a>
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg">
                Update Gaji
            </button>
        </div>
    </form>

</x-layouts.dashboard>
