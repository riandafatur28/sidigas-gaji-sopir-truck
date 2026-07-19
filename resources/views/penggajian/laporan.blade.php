<x-layouts.dashboard
    :title="'Laporan Gaji'"
    :pageTitle="'Laporan Gaji'"
    :user="auth()->user()">

    <div class="border-b border-gray-200 pb-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Laporan Penggajian</h1>
                <p class="text-base text-gray-500 mt-1">Rincian penggajian per periode</p>
            </div>
        </div>
    </div>

    <div class="w-full border border-gray-200 rounded mb-6 overflow-hidden bg-white">
        <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Pilih Periode</p>
        </div>
        <div class="px-5 py-4">
            <select onchange="window.location.href='{{ route('gaji.laporan') }}?periode='+this.value" class="w-full md:w-1/2 px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white">
                <option value="">Pilih Periode</option>
                @foreach($periodes as $p)
                    <option value="{{ $p->id }}" {{ $periodeId == $p->id ? 'selected' : '' }}>
                        {{ $p->nama_periode }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @if($data)
    <div id="reportContent">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="border border-gray-200 rounded bg-white px-5 py-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Hari Kerja</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $data['hari_kerja'] }} Hari</p>
            </div>
            <div class="border border-gray-200 rounded bg-white px-5 py-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sopir</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $data['total_sopir'] }} Orang</p>
            </div>
            <div class="border border-gray-200 rounded bg-white px-5 py-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Ritase</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $data['total_ritase'] }} Rit</p>
            </div>
            <div class="border border-gray-200 rounded bg-white px-5 py-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ritase Gagal</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $data['total_ritase_gagal'] }} Rit</p>
            </div>
            <div class="border border-gray-200 rounded bg-white px-5 py-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Grand Total</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($data['grand_total_all'], 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="w-full border border-gray-200 rounded overflow-hidden bg-white">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3" colspan="6">
                            Detail Penggajian
                            <span class="font-normal text-gray-400 text-xs ml-2">Periode: {{ $periode->nama_periode }}</span>
                        </th>
                    </tr>
                </thead>
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 py-2 w-10">No</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tujuan</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Jenis</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">@ Harga</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Qty</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $lastTujuan = ''; @endphp
                    @forelse($data['detail_rows'] as $row)
                        @php
                            $showHeader = !$row['is_subtotal'] && $row['tujuan'] !== $lastTujuan;
                            if (!$row['is_subtotal']) $lastTujuan = $row['tujuan'];
                        @endphp
                    @if($row['is_subtotal'])
                    <tr class="bg-gray-50 font-semibold">
                        <td class="px-3 py-2.5 text-center text-xs text-gray-400">{{ $row['no'] }}</td>
                        <td class="px-4 py-2.5 text-sm text-gray-700">{{ $row['tujuan'] }}</td>
                        <td class="px-4 py-2.5 text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $row['jenis'] }}</td>
                        <td class="px-4 py-2.5 text-right text-sm text-gray-600">-</td>
                        <td class="px-4 py-2.5 text-center text-sm font-bold text-gray-800">{{ $row['qty'] }} Rit</td>
                        <td class="px-4 py-2.5 text-right text-sm font-bold text-gray-900">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                    </tr>
                    @else
                    <tr class="hover:bg-gray-50 {{ $row['jenis'] === 'Gagal' ? 'text-red-600' : '' }}">
                        <td class="px-3 py-2.5 text-center text-sm {{ $showHeader ? 'text-gray-400' : 'text-transparent' }}">{{ $row['no'] }}</td>
                        <td class="px-4 py-2.5 text-sm text-gray-800">{{ $showHeader ? $row['tujuan'] : '' }}</td>
                        <td class="px-4 py-2.5 text-sm text-gray-600">{{ $row['jenis'] }}</td>
                        <td class="px-4 py-2.5 text-right text-sm text-gray-800 font-medium">Rp {{ number_format($row['harga'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-center text-sm text-gray-700">{{ $row['qty'] }} Rit</td>
                        <td class="px-4 py-2.5 text-right text-sm font-medium text-gray-800">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada data untuk periode ini</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100 border-t-2 border-gray-300">
                        <td colspan="5" class="px-4 py-3 text-right text-sm font-bold text-gray-900 text-base uppercase tracking-wider">Grand Total</td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-900 text-base">Rp {{ number_format($data['grand_total_all'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-4 flex justify-end">
            <a href="{{ route('gaji.laporan-pdf', $periode->id) }}"
               class="bg-[#1a1a2e] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#2d2d44] transition inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download PDF
            </a>
        </div>
    </div>
    @else
    <div class="w-full border border-gray-200 rounded overflow-hidden bg-white">
        <div class="px-5 py-12 text-center">
            <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-500 text-base font-medium">Pilih periode untuk melihat laporan penggajian</p>
        </div>
    </div>
    @endif
</x-layouts.dashboard>