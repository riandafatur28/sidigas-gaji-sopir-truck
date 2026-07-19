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
            border-bottom: 2px solid #1a1a2e;
        }
        .header h1 {
            font-size: 14pt;
            font-weight: 800;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 1pt;
        }
        .header .periode-title {
            font-size: 11pt;
            font-weight: 700;
            color: #1a1a2e;
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
            background: #1a1a2e;
            color: white;
            font-size: 7pt;
            font-weight: 700;
            padding: 2mm 1.5mm;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            word-break: break-word;
        }
        table thead th:nth-child(2) { text-align: left; }
        table thead th:nth-child(3) { text-align: left; }

        table tbody td {
            padding: 1.5mm 1.5mm;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8pt;
            word-break: break-word;
        }
        table tbody td:nth-child(2) { text-align: left; }
        table tbody td:nth-child(3) { text-align: left; }
        table tbody td:nth-child(4) { text-align: right; }
        table tbody td:nth-child(6) { text-align: right; }
        table tbody tr:nth-child(even) { background: #f9fafb; }

        table tbody tr.subtotal-row {
            background: #f3f4f6;
            font-weight: 700;
        }
        table tbody tr.subtotal-row td {
            border-bottom: 1.5px solid #9ca3af;
            padding: 2mm 1.5mm;
            font-size: 8.5pt;
        }
        table tbody tr.subtotal-row td:nth-child(4) { color: #6b7280; }

        table tbody tr.gagal-row td { color: #991b1b; }

        .grand-total-row td {
            background: #1a1a2e;
            color: white;
            font-size: 9.5pt;
            font-weight: 800;
            padding: 2.5mm 1.5mm;
            border: none;
        }
        .grand-total-row td:last-child { font-size: 11pt; }

        .footer {
            margin-top: 8mm;
            padding-top: 3mm;
            border-top: 1px solid #d1d5db;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 8pt;
            color: #6b7280;
        }
        .footer .signature-block {
            flex: 1;
            text-align: center;
        }
        .footer .signature-block .space { height: 18mm; }
        .footer .signature-block .name { font-weight: 700; color: #374151; }
        .footer .signature-block .title { font-size: 7.5pt; color: #6b7280; margin-bottom: 2mm; }
        .footer .left-info {
            text-align: left;
            font-size: 7.5pt;
            color: #9ca3af;
        }

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
                    <th style="width:29%;">Tujuan</th>
                    <th style="width:20%;">Jenis</th>
                    <th style="width:18%;">@ Harga</th>
                    <th style="width:9%;">Qty</th>
                    <th style="width:19%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $lastTujuan = ''; @endphp
                @forelse($data['detail_rows'] as $row)
                    @php
                        $showHeader = !$row['is_subtotal'] && $row['tujuan'] !== $lastTujuan;
                        if (!$row['is_subtotal']) $lastTujuan = $row['tujuan'];
                    @endphp

                    @if($row['is_subtotal'])
                    <tr class="subtotal-row">
                        <td></td>
                        <td>{{ $row['tujuan'] }}</td>
                        <td style="font-weight:800; letter-spacing:0.5pt;">{{ $row['jenis'] }}</td>
                        <td>-</td>
                        <td>{{ $row['qty'] }} Rit</td>
                        <td style="font-weight:800;">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                    </tr>
                    @else
                    <tr class="{{ $row['jenis'] === 'Gagal' ? 'gagal-row' : '' }}">
                        <td style="{{ $showHeader ? '' : 'color:transparent;' }}">{{ $row['no'] }}</td>
                        <td>{{ $showHeader ? $row['tujuan'] : '' }}</td>
                        <td>{{ $row['jenis'] }}</td>
                        <td>Rp {{ number_format($row['harga'], 0, ',', '.') }}</td>
                        <td>{{ $row['qty'] }} Rit</td>
                        <td>Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="no-data">Tidak ada data untuk periode ini</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="grand-total-row">
                    <td colspan="5" style="text-align:right;">GRAND TOTAL</td>
                    <td>Rp {{ number_format($data['grand_total_all'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <div class="left-info">
                Dicetak: {{ $now->translatedFormat('d F Y') }}
            </div>
            <div class="signature-block">
                <div class="title">Mitra,</div>
                <div class="space"></div>
                <div class="name">Ricki</div>
            </div>
            <div class="signature-block">
                <div class="title">Mengetahui,<br>Penasihat Mitra</div>
                <div class="space"></div>
                <div class="name">Bapak Haryanto</div>
            </div>
        </div>

        <div class="sub-footer">
            &copy; {{ $now->format('Y') }} Sistem Armada &bull; Dokumen digenerate {{ $now->translatedFormat('d F Y H:i') }}
        </div>
    </div>
</body>
</html>