<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - {{ $sopir->nama }}</title>
    <style>
        * { font-family: 'Times New Roman', Times, serif; }
        body { background: white; padding: 20px; margin: 0; }

        .slip-container {
            max-width: 1300px;
            margin: 0 auto;
            background: white;
            border: 2px solid #000;
            padding: 25px 30px;
            overflow-x: auto;
        }

        .slip-title {
            font-size: 22px;
            font-weight: 700;
            color: #000;
            text-align: center;
            padding: 10px 0;
            border-bottom: 3px solid #000;
            margin-bottom: 15px;
            letter-spacing: 2px;
        }

        .slip-subtitle {
            text-align: center;
            font-size: 16px;
            color: #000;
            margin-bottom: 15px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 800px;
        }

        th {
            border: 1px solid #000;
            padding: 8px 10px;
            text-align: center;
            font-weight: 700;
            color: #000;
            font-size: 13px;
            background: white;
        }

        td {
            border: 1px solid #000;
            padding: 8px 10px;
            text-align: center;
            color: #000;
            font-size: 14px;
            background: white;
        }

        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }

        .page-break {
            page-break-after: always;
            border-top: 2px dashed #000;
            margin-top: 20px;
            padding-top: 20px;
        }

        .print-btn {
            background: #000;
            color: white;
            padding: 10px 24px;
            border: none;
            cursor: pointer;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .print-btn:hover { background: #333; }

        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none; }
            .slip-container { border: 2px solid #000; padding: 20px 25px; }
            .page-break { border-top: 2px dashed #000; page-break-after: always; }
            th { background: white !important; }
            td { background: white !important; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn no-print">🖨️ Cetak Slip</button>
    <a href="{{ route('gaji.index') }}" class="print-btn no-print" style="background: #666; text-decoration: none; display: inline-block; margin-left: 10px;">← Kembali</a>

    @php
        // CEK APAKAH ADA DATA
        $hasData = isset($gaji) && $gaji && isset($dataPerHari) && count($dataPerHari) > 0 && $gaji->total > 0;
    @endphp

    @if(!$hasData)
        <div class="slip-container text-center py-10">
            <p style="font-size: 18px; color: #000; font-weight: 600;">Tidak ada data gaji untuk sopir ini</p>
            @if(isset($error))
                <p style="font-size: 14px; color: #cc0000; margin-top: 5px;">{{ $error }}</p>
            @endif
            <p style="font-size: 14px; color: #666; margin-top: 5px;">
                Periode: {{ $periode->nama_periode }} | Sopir: {{ $sopir->nama }}
            </p>
            <div style="margin-top: 15px;">
                <a href="{{ route('gaji.index') }}" class="print-btn" style="background: #1a1a2e; text-decoration: none; display: inline-block;">← Kembali ke Data Gaji</a>
            </div>
        </div>
    @else
        @php
            $totalDT = $gaji->dt ?? 0;
            $grandTotal = $gaji->total ?? 0;

            // URUTAN HARI
            $semuaHari = ['Sabtu', 'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at"];

            // DATA PER HARI
            $dataByHari = [];
            $ritKeByHari = [];
            $urutanHari = [];

            foreach ($dataPerHari as $d) {
                $dataByHari[$d['hari']] = $d;
                $ritKeByHari[$d['hari']] = $d['rit_ke'] > 1 ? $d['rit_ke'] : 1;
                $urutanHari[] = $d['hari'];
            }

            $totalHari = count($urutanHari);
            $itemsPerPage = 7;
            $totalPages = max(1, ceil($totalHari / $itemsPerPage));

            // TUJUAN STRING
            $allTujuan = [];
            foreach ($detailTujuan as $d) {
                $allTujuan[] = $d->tujuan ? $d->tujuan->nama : $d->kode_tujuan;
            }
            $tujuanString = implode(', ', array_slice($allTujuan, 0, 2));
            if (count($allTujuan) > 2) {
                $tujuanString .= ', ...';
            }

            $tanggalMulai = \Carbon\Carbon::parse($periode->tanggal_mulai)->format('d/m/Y');
            $tanggalSelesai = \Carbon\Carbon::parse($periode->tanggal_selesai)->format('d/m/Y');

            // HITUNG TOTAL SOLAR & UPAH PER HARI
            $totalSolarPerHari = [];
            $totalUpahPerHari = [];
            $totalJumlahPerHari = [];
            $tujuanPerHari = [];

            foreach ($urutanHari as $hari) {
                $solar = $dataByHari[$hari]['solar'] ?? 0;
                $upah = $dataByHari[$hari]['upah'] ?? 0;
                $tujuan = $dataByHari[$hari]['tujuan'] ?? '-';

                $totalSolarPerHari[$hari] = $solar;
                $totalUpahPerHari[$hari] = $upah;
                $totalJumlahPerHari[$hari] = $solar + $upah;
                $tujuanPerHari[$hari] = $tujuan;
            }
        @endphp

        @for($halaman = 0; $halaman < $totalPages; $halaman++)
            @php
                $startIdx = $halaman * $itemsPerPage;
                $endIdx = min($startIdx + $itemsPerPage, count($urutanHari));
                $hariHalaman = array_slice($urutanHari, $startIdx, $endIdx - $startIdx);
                $isLastPage = ($halaman == $totalPages - 1);

                $headerNama = $sopir->nama;
                $headerPeriode = $periode->nama_periode;
                $headerTujuan = $tujuanString;
            @endphp

            <div class="slip-container">
                {{-- HEADER --}}
                <div class="slip-title">SLIP GAJI SOPIR</div>
                <div class="slip-subtitle">
                    {{ $headerNama }} | {{ $headerPeriode }} ({{ $tanggalMulai }} - {{ $tanggalSelesai }}) | {{ $headerTujuan }}
                    <span style="margin-left: 20px; font-weight: 400; color: #666;">Halaman {{ $halaman + 1 }} dari {{ $totalPages }}</span>
                </div>

                {{-- TABEL --}}
                <table>
                    <thead>
                        <tr>
                            <th style="width: 12%;">NAMA</th>
                            @foreach($hariHalaman as $hari)
                                @php
                                    $ritKe = $ritKeByHari[$hari] ?? 1;
                                    $label = $hari;
                                    if (isset($dataByHari[$hari]) && $dataByHari[$hari]['rit_ke'] > 1) {
                                        $label = $hari . ' ' . $ritKe . '/4';
                                    }
                                @endphp
                                <th style="width: 10%;">{{ $label }}</th>
                            @endforeach
                            <th style="width: 8%;">DT</th>
                            <th style="width: 10%;">JUMLAH</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- SOLAR --}}
                        <tr>
                            <td class="font-bold text-left" style="font-size: 15px;">Solar</td>
                            @foreach($hariHalaman as $hari)
                                @php
                                    $val = $totalSolarPerHari[$hari] ?? 0;
                                    $display = $val > 0 ? number_format($val, 0, ',', '.') : '';
                                @endphp
                                <td class="text-right" style="font-size: 15px;">{{ $display }}</td>
                            @endforeach
                            <td class="text-right" style="font-size: 15px;"></td>
                            <td class="text-right" style="font-size: 15px;"></td>
                        </tr>

                        {{-- SOPIR --}}
                        <tr>
                            <td class="font-bold text-left" style="font-size: 15px;">Sopir</td>
                            @foreach($hariHalaman as $hari)
                                @php
                                    $val = $totalUpahPerHari[$hari] ?? 0;
                                    $display = $val > 0 ? number_format($val, 0, ',', '.') : '';
                                @endphp
                                <td class="text-right" style="font-size: 15px;">{{ $display }}</td>
                            @endforeach
                            <td class="text-right" style="font-size: 15px;"></td>
                            <td class="text-right" style="font-size: 15px;"></td>
                        </tr>

                        {{-- JUMLAH --}}
                        <tr>
                            <td class="font-bold text-left" style="font-size: 15px;">Jumlah</td>
                            @foreach($hariHalaman as $hari)
                                @php
                                    $val = $totalJumlahPerHari[$hari] ?? 0;
                                    $display = $val > 0 ? number_format($val, 0, ',', '.') : '';
                                @endphp
                                <td class="text-right font-bold" style="font-size: 15px;">{{ $display }}</td>
                            @endforeach
                            <td class="text-right font-bold" style="font-size: 15px;">{{ number_format($totalDT, 0, ',', '.') }}</td>
                            <td class="text-right font-bold" style="font-size: 16px;">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                        </tr>

                        {{-- TUJUAN --}}
                        <tr>
                            <td class="font-bold text-left" style="font-size: 13px;">Tujuan</td>
                            @foreach($hariHalaman as $hari)
                                @php
                                    $tujuan = $tujuanPerHari[$hari] ?? '-';
                                @endphp
                                <td class="text-left" style="font-size: 12px; font-weight: 500;">{{ $tujuan }}</td>
                            @endforeach
                            <td style="font-size: 12px;"></td>
                            <td style="font-size: 12px;"></td>
                        </tr>
                    </tbody>
                </table>

                {{-- FOOTER --}}
                <div style="margin-top: 12px; padding-top: 10px; border-top: 2px solid #000; display: flex; flex-wrap: wrap; justify-content: space-between; font-size: 13px; color: #000;">
                    <div style="display: flex; flex-wrap: wrap; gap: 5px 15px;">
                        @foreach($detailTujuan as $d)
                            <span>• {{ $d->tujuan ? $d->tujuan->nama : $d->kode_tujuan }}</span>
                        @endforeach
                    </div>
                    <div style="font-weight: 700;">
                        <span>DT: Rp {{ number_format($totalDT, 0, ',', '.') }}</span>
                        <span style="margin-left: 20px; font-weight: 800; font-size: 15px;">GRAND TOTAL: Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- FOOTER INSTITUSI --}}
                <div style="margin-top: 8px; padding-top: 5px; border-top: 1px solid #ccc; display: flex; justify-content: space-between; font-size: 11px; color: #666;">
                    <span>Sistem Armada - Kementerian PUPR</span>
                    <span>Slip Gaji {{ $sopir->nama }} - {{ $periode->nama_periode }}</span>
                    <span>Cetak: {{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            @if(!$isLastPage)
                <div class="page-break"></div>
            @endif

        @endfor
    @endif

    <script>
        if (window.location.search.includes('print=1')) {
            window.onload = function() { window.print(); }
        }
    </script>

</body>
</html>
