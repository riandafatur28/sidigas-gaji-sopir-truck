<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Gaji - {{ $periode->nama_periode }}</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; font-family: 'Helvetica', 'Arial', sans-serif; }
        body { background: white; padding: 8mm; font-size: 8.5pt; color: #1f2937; }
        .page { width: auto; }

        .header {
            text-align: center;
            margin-bottom: 4mm;
            padding-bottom: 3mm;
            border-bottom: 2px solid #000;
        }
        .header h1 {
            font-size: 14pt;
            font-weight: 800;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1pt;
        }
        .header .periode-title {
            font-size: 11pt;
            font-weight: 700;
            color: #000;
            margin-top: 2mm;
        }
        .header .date-range {
            font-size: 8.5pt;
            color: #4b5563;
            margin-top: 0.5mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
            table-layout: fixed;
        }
        table thead th {
            background: white;
            color: black;
            font-size: 7pt;
            font-weight: 700;
            padding: 2mm 1.5mm;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            word-break: break-word;
            border: 1.5px solid #000;
        }
        table thead th:nth-child(2) { text-align: left; }
        table thead th:nth-child(3) { text-align: left; }
        table thead th:nth-child(4) { text-align: left; }
        table thead th:nth-child(5) { text-align: right; }
        table thead th:nth-child(6) { text-align: center; }
        table thead th:nth-child(7) { text-align: right; }

        table tbody td {
            padding: 1.5mm 1.5mm;
            border: 1px solid #000;
            text-align: center;
            font-size: 8pt;
            word-break: break-word;
            background: white;
            color: black;
        }
        table tbody td:nth-child(1) { text-align: center; }
        table tbody td:nth-child(2) { text-align: left; }
        table tbody td:nth-child(3) { text-align: left; }
        table tbody td:nth-child(4) { text-align: left; }
        table tbody td:nth-child(5) { text-align: right; }
        table tbody td:nth-child(6) { text-align: center; }
        table tbody td:nth-child(7) { text-align: right; }

        table tbody tr.subtotal-row {
            background: white;
            font-weight: 700;
        }
        table tbody tr.subtotal-row td {
            border: 1.5px solid #000;
            padding: 2mm 1.5mm;
            font-size: 8.5pt;
            background: white;
            color: black;
        }

        table tbody tr.gagal-row td { color: black; background: white; }

        .grand-total-row td {
            background: white;
            color: black;
            font-size: 9.5pt;
            font-weight: 800;
            padding: 2.5mm 1.5mm;
            border: 1.5px solid #000;
        }
        .grand-total-row td:last-child { font-size: 11pt; }

        .sign-table {
            width: 100%;
            margin-top: 8mm;
            padding-top: 3mm;
            border-top: 1px solid #000;
            border-collapse: collapse;
        }
        .sign-table td {
            border: none;
            vertical-align: top;
            font-size: 8pt;
            color: #000;
        }
        .sign-table .sign-left {
            width: 34%;
            text-align: left;
            font-size: 7.5pt;
            vertical-align: bottom;
        }
        .sign-table .sign-block {
            width: 33%;
            text-align: center;
        }
        .sign-table .sign-block .space { height: 18mm; }
        .sign-table .sign-block .name { font-weight: 700; }
        .sign-table .sign-block .title { font-size: 7.5pt; margin-bottom: 2mm; }

        .sub-footer {
            font-size: 7pt;
            color: #9ca3af;
            text-align: center;
            margin-top: 2mm;
        }
        .no-data { text-align: center; padding: 10mm; color: #9ca3af; font-size: 11pt; }
    </style>
</head>
<body>
    @php $now = now(); @endphp
    <div class="page">
        <div class="header">
            <h1>Laporan Penggajian</h1>
            <div class="periode-title">{{ $periode->nama_periode }}</div>
            <div class="date-range">
                {{ \Carbon\Carbon::parse($periode->tanggal_mulai)->translatedFormat('d F Y') }}
                &mdash;
                {{ \Carbon\Carbon::parse($periode->tanggal_selesai)->translatedFormat('d F Y') }}
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:5%;">No</th>
                    <th style="width:17%;">Hari / Tanggal</th>
                    <th style="width:22%;">Tujuan</th>
                    <th style="width:16%;">Jenis</th>
                    <th style="width:14%;">@ Harga</th>
                    <th style="width:8%;">Qty</th>
                    <th style="width:18%;">Total</th>
                </tr>
            </thead>
            <tbody>
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
                    <tr class="subtotal-row">
                        @if($isFirstHari)
                            <td rowspan="{{ $rowspanHari }}">{{ $hariNo[$hariKey] ?? '' }}</td>
                        @endif
                        @if($isFirstHari)
                            <td rowspan="{{ $rowspanHari }}" style="font-size:7pt; text-align:left;"></td>
                        @endif
                        @if($isFirstTujuan)
                            <td rowspan="{{ $rowspanTujuan }}" style="text-align:left;"></td>
                        @endif
                        <td style="font-weight:800; letter-spacing:0.5pt; text-align:left;">{{ $row['jenis'] }}</td>
                        <td style="text-align:right;">-</td>
                        <td style="text-align:center;">{{ $row['qty'] }} Rit</td>
                        <td style="font-weight:800; text-align:right;">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                    </tr>
                    @else
                    <tr class="{{ $row['jenis'] === 'Gagal' ? 'gagal-row' : '' }}">
                        @if($isFirstHari)
                            <td rowspan="{{ $rowspanHari }}">{{ $hariNo[$hariKey] ?? '' }}</td>
                        @endif
                        @if($isFirstHari)
                            <td rowspan="{{ $rowspanHari }}" style="font-size:7pt; text-align:left;">{{ $hariTanggal }}</td>
                        @endif
                        @if($isFirstTujuan)
                            <td rowspan="{{ $rowspanTujuan }}" style="text-align:left;">{{ $row['tujuan'] }}</td>
                        @endif
                        <td style="text-align:left;">{{ $row['jenis'] }}</td>
                        <td style="text-align:right;">Rp {{ number_format($row['harga'], 0, ',', '.') }}</td>
                        <td style="text-align:center;">{{ $row['qty'] }} Rit</td>
                        <td style="text-align:right;">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="no-data">Tidak ada data untuk periode ini</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                @php
                    $potonganOp = ($data['unique_kabupaten'] ?? $data['total_ritase'] ?? 0) * 20000;
                @endphp
                <tr style="background:white;">
                    <td colspan="6" style="text-align:right;padding:2mm 1.5mm;font-size:8.5pt;font-weight:600;border-bottom:1px solid #000;color:black;background:white;">Pot. Operasional (20rb × {{ $data['unique_kabupaten'] ?? $data['total_ritase'] ?? 0 }} trip)</td>
                    <td style="padding:2mm 1.5mm;font-size:8.5pt;font-weight:600;text-align:right;border-bottom:1px solid #000;color:black;background:white;">Rp {{ number_format($potonganOp, 0, ',', '.') }}</td>
                </tr>
                <tr class="grand-total-row">
                    <td colspan="6" style="text-align:right;background:white;color:black;border:1.5px solid #000;">GRAND TOTAL (termasuk pot. operasional)</td>
                    <td style="text-align:right;background:white;color:black;border:1.5px solid #000;">Rp {{ number_format($data['grand_total_all'] + ($data['unique_kabupaten'] ?? $data['total_ritase'] ?? 0) * 20000, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <table class="sign-table">
            <tr>
                <td class="sign-left">
                    Dicetak: {{ $now->translatedFormat('d F Y') }}
                </td>
                <td class="sign-block">
                    <div class="title">Mitra,</div>
                    <div class="space"></div>
                    <div class="name">Ricki</div>
                </td>
                <td class="sign-block">
                    <div class="title">Mengetahui,<br>Penasihat Mitra</div>
                    <div class="space"></div>
                    <div class="name">Bapak Haryanto</div>
                </td>
            </tr>
        </table>

        <div class="sub-footer">
            &copy; {{ $now->format('Y') }} Sistem Armada &bull; Dokumen digenerate {{ $now->translatedFormat('d F Y H:i') }}
        </div>
    </div>
</body>
</html>