<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $periode->nama_periode }}</title>
    <style>
        @page { margin: 4mm; }
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
            padding: 1mm 2mm;
            page-break-after: always;
        }
        .page:last-child { page-break-after: avoid; }

        .slip-block {
            width: 100%;
            margin-bottom: 1mm;
            border: 1.5px solid #000;
            page-break-inside: avoid;
        }

        .block-header {
            font-size: 11pt;
            font-weight: 700;
            text-align: center;
            padding: 1.5mm 3mm;
            border-bottom: 1.5px solid #000;
            background: white;
        }

        .slip-block table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .slip-block th {
            border: 1px solid #000;
            padding: 1mm 1.5mm;
            text-align: center;
            font-weight: 700;
            font-size: 8pt;
            background: white;
            vertical-align: middle;
        }

        .slip-block td {
            border: 1px solid #000;
            padding: 1mm 1.5mm;
            text-align: center;
            font-size: 9pt;
            background: white;
            vertical-align: middle;
        }

        .slip-block td.label {
            font-weight: 700;
            font-size: 10pt;
            text-align: left;
            width: 22mm;
        }

        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }

        .block-footer {
            font-size: 9pt;
            font-weight: 700;
            text-align: right;
            padding: 1mm 2.5mm;
            border-top: 1.5px solid #000;
        }
    </style>
</head>
<body>

@php
    $totalDates = count($dateHeaders);
    $namaW = 22;
    $dtW = 16;
    $jmlW = 18;
    $availW = 320 - $namaW - $dtW - $jmlW - 4;
    $colW = $totalDates > 0 ? max(9, round($availW / $totalDates)) : 9;
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
            @endphp

            <div class="slip-block">
                <div class="block-header">{{ $sopirName }} | {{ $periode->nama_periode }}</div>
                <table>
                    <thead>
                        <tr>
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
                                        $val = $d['is_gagal'] ? '-' : ($d['upah'] > 0 ? number_format($d['upah'], 0, ',', '.') : '');
                                    }
                                @endphp
                                <td>{{ $val }}</td>
                            @endforeach
                            <td></td>
                            <td></td>
                        </tr>
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
                                        $tujuan = $d['is_gagal'] ? 'Gagal' : $d['tujuan'];
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
