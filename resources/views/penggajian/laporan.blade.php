<x-layouts.dashboard
    :title="'Laporan Gaji'"
    :pageTitle="'Laporan Gaji'"
    >

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
            <select onchange="window.location.href='{{ route('gaji.laporan') }}?periode='+this.value" class="w-full md:w-1/2 px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white">
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
        <div class="card mb-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
                <div style="padding:15px 20px" class="ledger-cell lg:border-r">
                    <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">HARI KERJA</p>
                    <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $data['hari_kerja'] }} <span style="font-size:13px;font-weight:500;color:var(--text-dims)">hari</span></p>
                </div>
                <div style="padding:15px 20px" class="ledger-cell lg:border-r">
                    <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">SOPIR</p>
                    <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $data['total_sopir'] }} <span style="font-size:13px;font-weight:500;color:var(--text-dims)">orang</span></p>
                </div>
                <div style="padding:15px 20px" class="ledger-cell lg:border-r">
                    <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TOTAL RITASE</p>
                    <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $data['total_ritase'] + $data['total_ritase_gagal'] }} <span style="font-size:13px;font-weight:500;color:var(--text-dims)">rit</span></p>
                </div>
                <div style="padding:15px 20px" class="ledger-cell lg:border-r">
                    <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">RITASE GAGAL</p>
                    <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $data['total_ritase_gagal'] }} <span style="font-size:13px;font-weight:500;color:var(--text-dims)">rit</span></p>
                </div>
                <div style="padding:15px 20px" class="ledger-cell lg:border-r">
                    <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">RIT BERHASIL</p>
                    <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $data['total_ritase'] }} <span style="font-size:13px;font-weight:500;color:var(--text-dims)">rit</span></p>
                </div>
                <div style="padding:15px 20px" data-ledger>
                    <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">GRAND TOTAL</p>
                    <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">Rp {{ number_format($data['grand_total_all'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="table-responsive border border-gray-200 rounded bg-white">
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
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 py-2 whitespace-nowrap">Hari / Tanggal</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tujuan</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Jenis</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">@ Harga</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Qty</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php
                        $groupCounts = [];
                        $hariCounts = [];
                        foreach($data['detail_rows'] as $r){
                            $k = ($r['hari']??'').'|'.($r['tujuan']??'');
                            $groupCounts[$k] = ($groupCounts[$k] ?? 0) + 1;
                            $hk = $r['tanggal'] ?? $r['hari'] ?? '';
                            $hariCounts[$hk] = ($hariCounts[$hk] ?? 0) + 1;
                        }
                        $seen = [];
                        $seenHari = [];
                        $hariNo = [];
                        $hariCounter = 0;
                    @endphp
                    @forelse($data['detail_rows'] as $row)
                        @php
                            $curKey = ($row['hari'] ?? '') . '|' . ($row['tujuan'] ?? '');
                            $hariKey = $row['tanggal'] ?? $row['hari'] ?? '';
                            $isFirstTujuan = !isset($seen[$curKey]);
                            $isFirstHari = !isset($seenHari[$hariKey]);
                            $rowspanTujuan = $groupCounts[$curKey] ?? 1;
                            $rowspanHari = $hariCounts[$hariKey] ?? 1;
                            if($isFirstTujuan) $seen[$curKey]=true;
                            if($isFirstHari) $seenHari[$hariKey]=true;
                            if($isFirstHari){ $hariCounter++; $hariNo[$hariKey] = $hariCounter; }
                            $hariTanggal = $row['tgl_label'] ?? (($row['hari'] ?? '') . ' ' . ($row['tanggal'] ?? ''));
                        @endphp
                        @if($row['is_subtotal'])
                        <tr class="bg-gray-50 font-semibold">
                            @if($isFirstHari)
                                <td class="px-3 py-2.5 text-center text-xs text-gray-400" rowspan="{{ $rowspanHari }}">{{ $hariNo[$hariKey] ?? '' }}</td>
                            @endif
                            @if($isFirstHari)
                                <td class="px-3 py-2.5 text-xs text-gray-700 whitespace-nowrap text-left" rowspan="{{ $rowspanHari }}">{{ $hariTanggal }}</td>
                            @endif
                            @if($isFirstTujuan)
                                <td class="px-4 py-2.5 text-sm text-gray-700 text-left" rowspan="{{ $rowspanTujuan }}">{{ $row['tujuan'] }}</td>
                            @endif
                            <td class="px-4 py-2.5 text-sm font-bold text-gray-800 uppercase tracking-wider text-left">{{ $row['jenis'] }}</td>
                            <td class="px-4 py-2.5 text-right text-sm text-gray-600">-</td>
                            <td class="px-4 py-2.5 text-center text-sm font-bold text-gray-800">{{ $row['qty'] }} Rit</td>
                            <td class="px-4 py-2.5 text-right text-sm font-bold text-gray-900">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                        </tr>
                        @else
                        <tr class="hover:bg-gray-50 {{ $row['jenis'] === 'Gagal' ? 'text-red-600' : '' }}">
                            @if($isFirstHari)
                                <td class="px-3 py-2.5 text-center text-sm text-gray-400" rowspan="{{ $rowspanHari }}">{{ $hariNo[$hariKey] ?? '' }}</td>
                            @endif
                            @if($isFirstHari)
                                <td class="px-3 py-2.5 text-xs text-gray-700 whitespace-nowrap text-left" rowspan="{{ $rowspanHari }}">{{ $hariTanggal }}</td>
                            @endif
                            @if($isFirstTujuan)
                                <td class="px-4 py-2.5 text-sm text-gray-800 text-left" rowspan="{{ $rowspanTujuan }}">{{ $row['tujuan'] }}</td>
                            @endif
                            <td class="px-4 py-2.5 text-sm text-gray-600 text-left">{{ $row['jenis'] }}</td>
                            <td class="px-4 py-2.5 text-right text-sm text-gray-800 font-medium">Rp {{ number_format($row['harga'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-center text-sm text-gray-700">{{ $row['qty'] }} Rit</td>
                            <td class="px-4 py-2.5 text-right text-sm font-medium text-gray-800">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada data untuk periode ini</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-white border-t border-gray-200">
                        <td colspan="6" class="px-4 py-2.5 text-right text-sm font-medium text-gray-700 uppercase tracking-wider">Pot. Operasional (20rb × {{ $data['unique_kabupaten'] }} trip)</td>
                        <td class="px-4 py-2.5 text-right text-sm font-medium text-gray-700">Rp {{ number_format($data['unique_kabupaten'] * 20000, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="bg-gray-100 border-t-2 border-gray-300">
                        <td colspan="6" class="px-4 py-3 text-right text-sm font-bold text-gray-900 text-base uppercase tracking-wider">Grand Total (dengan pot. operasional)</td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-900 text-base">Rp {{ number_format($data['grand_total_all'] + ($data['unique_kabupaten'] * 20000), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-4 flex justify-end">
            <a href="{{ route('gaji.laporan-pdf', $periode->id) }}"
               class="bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download PDF
            </a>
        </div>
    </div>
    @else
    <div class="table-responsive border border-gray-200 rounded bg-white">
        <div class="px-5 py-12 text-center">
            <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-500 text-base font-medium">Pilih periode untuk melihat laporan penggajian</p>
        </div>
    </div>
    @endif
</x-layouts.dashboard>