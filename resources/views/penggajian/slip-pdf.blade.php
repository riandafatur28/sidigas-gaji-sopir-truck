<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $periode->nama_periode }}</title>
    <style>
        @page { margin: 5mm 4mm; }
        * {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: white;
            padding: 0;
            width: 100%;
        }

        .page {
            width: 100%;
            padding: 0;
            page-break-after: always;
        }
        .page:last-child { page-break-after: avoid; }

        .slip-block {
            width: 100%;
            margin-bottom: 2mm;
            border: 1px solid #000;
            page-break-inside: avoid;
        }

        .block-header {
            font-size: 10pt;
            font-weight: 700;
            text-align: center;
            padding: 1mm 2mm;
            border-bottom: 1px solid #000;
            background: white;
        }

        .slip-block table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .slip-block th {
            border: 1px solid #000;
            padding: 0.8mm 1mm;
            text-align: center;
            font-weight: 700;
            font-size: 8.5pt;
            background: white;
            vertical-align: middle;
        }

        .slip-block td {
            border: 1px solid #000;
            padding: 0.8mm 1mm;
            text-align: center;
            font-size: 8.5pt;
            background: white;
            vertical-align: middle;
        }

        .slip-block td.label {
            font-weight: 700;
            font-size: 8.5pt;
            text-align: left;
            width: 20mm;
        }

        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }

        .block-footer {
            font-size: 10pt;
            font-weight: 700;
            text-align: right;
            padding: 0.8mm 2mm;
            border-top: 1px solid #000;
        }

        @media screen {
            body { padding: 8px; width: auto; }
            .page { padding: 0; page-break-after: auto; }
            .slip-block { width: 100%; min-width: 0; overflow-x: auto; margin-bottom: 6px; }
            .slip-block table { table-layout: auto !important; }
            .slip-block th { font-size: 10pt; padding: 3px 5px; white-space: nowrap; }
            .slip-block td { font-size: 10pt; padding: 3px 5px; }
            .slip-block td.label { font-size: 10pt; width: auto !important; min-width: 70px; white-space: nowrap; }
            .block-header { font-size: 11pt; padding: 4px 8px; }
            .block-footer { font-size: 11pt; padding: 4px 8px; }
            .slip-block th[style*="mm"] { width: auto !important; min-width: 50px; }
        }
    </style>
</head>
<body>

@php
    $totalDates = count($dateHeaders);
    $sopirW = 8;
    $namaW = 18;
    $dtW = 12;
    $jmlW = 14;
    $availW = 320 - $sopirW - $namaW - $dtW - $jmlW - 4;
    $colW = $totalDates > 0 ? max(7, round($availW / $totalDates)) : 7;
@endphp

@foreach($sopirPerPages as $pageSlips)
    <div class="page">
        @foreach($pageSlips as $slip)
            @php
                $sopirName = $slip['sopir']->nama;
                $ritMap = $slip['ritMap'];
                $ritKe = $slip['ritKe'] ?? 1;
                $totalDT = $slip['totalDTAll'];
                $grandTotal = $slip['grandTotal'];
                $totalTol = $slip['totalTolAll'] ?? 0;
                $rowspan = $totalTol > 0 ? 5 : 4;
            @endphp

            <div class="slip-block">
                <table>
                    <thead>
                        <tr>
                            <th style="width: {{ $sopirW }}mm;">SOPIR</th>
                            <th style="width: {{ $namaW }}mm;">NAMA</th>
                            @foreach($dateHeaders as $dh)
                                <th style="width: {{ $colW }}mm;">{{ $dh['label'] }} {{ $dh['date'] }}</th>
                            @endforeach
                            <th style="width: {{ $dtW }}mm;">DT</th>
                            <th style="width: {{ $jmlW }}mm;">JUMLAH</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="label" style="vertical-align:middle;" rowspan="{{ $rowspan }}">{{ $sopirName }}</td>
                            <td class="label">Solar</td>
                            @foreach($dateHeaders as $dh)
                                @php
                                    $d = $ritMap[$dh['tanggal']][$ritKe] ?? null;
                                    $val = '';
                                    if ($d) {
                                        $val = $d['is_gagal'] ? 'GAGAL' : ($d['solar'] > 0 ? number_format($d['solar'], 0, ',', '.') : '');
                                    }
                                @endphp
                                <td>{{ $val }}</td>
                            @endforeach
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="label">Sopir</td>
                            @foreach($dateHeaders as $dh)
                                @php
                                    $d = $ritMap[$dh['tanggal']][$ritKe] ?? null;
                                    $val = '';
                                    if ($d) {
                                        $val = $upahTampil = $d['upah'] + ($d['is_lembur'] ? $d['upah_lembur'] : 0);
                                        $val = $d['is_gagal'] ? '-' : ($upahTampil > 0 ? number_format($upahTampil, 0, ',', '.') : '');
                                    }
                                @endphp
                                <td>{{ $val }}</td>
                            @endforeach
                            <td></td>
                            <td></td>
                        </tr>
@if($totalTol > 0)
                        <tr>
                            <td class="label">Tol</td>
                            @foreach($dateHeaders as $dh)
                                @php
                                    $d = $ritMap[$dh['tanggal']][$ritKe] ?? null;
                                    $val = '';
                                    if ($d) {
                                        $val = $d['is_gagal'] ? '-' : ($d['tol'] > 0 ? number_format($d['tol'], 0, ',', '.') : '');
                                    }
                                @endphp
                                <td>{{ $val }}</td>
                            @endforeach
                            <td></td>
  ... (1 duplicate lines)
                        </tr>
                        @endif
                        <tr>
                            <td class="label">Jumlah</td>
                            @foreach($dateHeaders as $dh)
                                @php
                                    $d = $ritMap[$dh['tanggal']][$ritKe] ?? null;
                                    $val = '';
                                    if ($d) {
                                        $val = $d['jumlah'] > 0 ? number_format($d['jumlah'], 0, ',', '.') : '';
                                    }
                                @endphp
                                <td class="font-bold">{{ $val }}</td>
                            @endforeach
                            <td class="font-bold">{{ $totalDT > 0 ? number_format($totalDT, 0, ',', '.') : '' }}</td>
                            <td class="font-bold">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Tujuan</td>
                            @foreach($dateHeaders as $dh)
                                @php
                                    $d = $ritMap[$dh['tanggal']][$ritKe] ?? null;
                                    $tujuan = '';
                                    if ($d) {
                                        $tujuan = $d['is_gagal'] ? 'Gagal' : $d['tujuan'] . ($d['is_lembur'] ? ' (Lembur)' : '');
                                    }
                                @endphp
                                <td class="text-left">{{ $tujuan }}</td>
                            @endforeach
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
@endforeach

</body>
</html>
