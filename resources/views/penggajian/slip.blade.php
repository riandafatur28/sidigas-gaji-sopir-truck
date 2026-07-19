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

        .slip-header {
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            padding: 8px 0;
            border-bottom: 2px solid #000;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 800px;
        }

        th {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: center;
            font-weight: 700;
            font-size: 13px;
            background: white;
        }

        td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: center;
            font-size: 15px;
            background: white;
        }

        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .label-tujuan-nama { font-size: 17px; font-weight: 700; }

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

    <button onclick="window.print()" class="print-btn no-print">Cetak Slip</button>
    <a href="{{ route('gaji.index') }}" class="print-btn no-print" style="background: #666; text-decoration: none; display: inline-block; margin-left: 10px;">← Kembali</a>

    @php
        $hasData = isset($gaji) && $gaji && isset($dataPerHari) && count($dataPerHari) > 0;
    @endphp

    @if(!$hasData)
        <div class="slip-container text-center py-10">
            <p style="font-size: 18px; font-weight: 600;">Tidak ada data gaji untuk sopir ini</p>
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
            $totalKompensasi = $gaji->kompensasi_gagal ?? 0;

            $pages = [];
            foreach ($dataPerHari as $entry) {
                $pages[$entry['rit_ke']][] = $entry;
            }
            ksort($pages);
            $totalPages = max(1, count($pages));

            $tanggalMulai = \Carbon\Carbon::parse($periode->tanggal_mulai)->format('d/m/Y');
            $tanggalSelesai = \Carbon\Carbon::parse($periode->tanggal_selesai)->format('d/m/Y');

            $totalSolarAll = array_sum(array_column($dataPerHari, 'solar'));
            $totalUpahAll = array_sum(array_column($dataPerHari, 'upah'));
            $totalJumlahAll = array_sum(array_column($dataPerHari, 'jumlah'));
            $totalDTAll = array_sum(array_column($dataPerHari, 'dt'));

            $halamanIndex = 0;
        @endphp

        @foreach($pages as $ritKe => $pageData)
            @php
                $isLastPage = ($halamanIndex == $totalPages - 1);

                $pageDT = array_sum(array_column($pageData, 'dt'));
                $pageJumlah = array_sum(array_column($pageData, 'jumlah'));
            @endphp

            <div class="slip-container">
                <div class="slip-header">{{ $sopir->nama }} | {{ $periode->nama_periode }} ({{ $tanggalMulai }} - {{ $tanggalSelesai }}) <span style="font-weight: 400; font-size: 14px; color: #666;">Halaman {{ $halamanIndex + 1 }} dari {{ $totalPages }}</span></div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 12%;">NAMA</th>
                            @foreach($pageData as $d)
                                @php
                                    $tglHeader = \Carbon\Carbon::parse($d['tanggal'])->format('d/m');
                                    $label = $d['hari'] . ' ' . $tglHeader;
                                    if ($d['total_rit_hari'] > 1) {
                                        $label .= ' (' . $d['rit_ke'] . '/' . $d['total_rit_hari'] . ')';
                                    }
                                @endphp
                                <th style="width: 10%;">{{ $label }}</th>
                            @endforeach
                            <th style="width: 8%;">DT</th>
                            <th style="width: 10%;">JUMLAH</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="label-tujuan-nama text-left">Solar</td>
                            @foreach($pageData as $d)
                                @php
                                    $display = $d['is_gagal'] ? 'GAGAL' : ($d['solar'] > 0 ? number_format($d['solar'], 0, ',', '.') : '');
                                @endphp
                                <td class="text-right">{{ $display }}</td>
                            @endforeach
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="label-tujuan-nama text-left">Sopir</td>
                            @foreach($pageData as $d)
                                @php
                                    $display = $d['is_gagal'] ? '-' : ($d['upah'] > 0 ? number_format($d['upah'], 0, ',', '.') : '');
                                @endphp
                                <td class="text-right">{{ $display }}</td>
                            @endforeach
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="label-tujuan-nama text-left">Jumlah</td>
                            @foreach($pageData as $d)
                                @php
                                    $display = $d['jumlah'] > 0 ? number_format($d['jumlah'], 0, ',', '.') : '';
                                @endphp
                                <td class="text-right font-bold">{{ $display }}</td>
                            @endforeach
                            <td class="text-right font-bold">{{ $pageDT > 0 ? number_format($pageDT, 0, ',', '.') : '' }}</td>
                            <td class="text-right font-bold">{{ number_format($pageJumlah + $pageDT, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="label-tujuan-nama text-left">Tujuan</td>
                            @foreach($pageData as $d)
                                @php
                                    $tujuanLabel = $d['is_gagal'] ? 'Gagal Produksi' : $d['tujuan'];
                                @endphp
                                <td class="text-left" style="font-size: 15px;">{{ $tujuanLabel }}</td>
                            @endforeach
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top: 10px; padding-top: 8px; border-top: 2px solid #000; text-align: right; font-weight: 700;">
                    @if($pageDT > 0)
                        <span>DT: Rp {{ number_format($pageDT, 0, ',', '.') }}</span>
                    @endif
                    <span style="margin-left: 20px;">TOTAL: Rp {{ number_format($pageJumlah + $pageDT, 0, ',', '.') }}</span>
                    @if($isLastPage)
                        <span style="margin-left: 20px; font-weight: 900;">GRAND TOTAL: Rp {{ number_format($totalJumlahAll + $totalDTAll, 0, ',', '.') }}</span>
                    @endif
                </div>
            </div>

            @if(!$isLastPage)
                <div class="page-break"></div>
            @endif

            @php $halamanIndex++; @endphp
        @endforeach
    @endif

    <script>
        if (window.location.search.includes('print=1')) {
            window.onload = function() { window.print(); }
        }
    </script>

</body>
</html>
