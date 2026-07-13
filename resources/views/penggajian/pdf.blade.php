<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji Sopir</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; }
        .container { display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; gap: 5mm; height: 277mm; }
        .slip { border: 1px solid #000; padding: 3mm; page-break-inside: avoid; }
        .slip-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 2mm; margin-bottom: 2mm; }
        .slip-header h2 { margin: 0; font-size: 12px; font-weight: bold; }
        .slip-info { margin-bottom: 2mm; }
        .slip-info table { width: 100%; font-size: 9px; }
        .slip-info td { padding: 1mm 0; }
        .slip-detail { width: 100%; border-collapse: collapse; margin-top: 2mm; font-size: 9px; }
        .slip-detail th, .slip-detail td { border: 1px solid #000; padding: 1.5mm; text-align: left; }
        .slip-detail th { background-color: #f0f0f0; font-weight: bold; }
        .slip-detail .text-right { text-align: right; }
        .slip-detail .text-center { text-align: center; }
        .slip-total { margin-top: 2mm; border-top: 2px solid #000; padding-top: 2mm; font-weight: bold; }
        .slip-total table { width: 100%; font-size: 10px; }
        .slip-total td { padding: 1mm 0; }
        .grand-total { font-size: 11px; color: #d32f2f; }
    </style>
</head>
<body>
    <div class="container">
        @foreach($gajis as $index => $gaji)
            @if($index < 4)
            <div class="slip">
                <div class="slip-header">
                    <h2>SLIP GAJI SOPIR</h2>
                    <div style="font-size: 8px; margin-top: 1mm;">Periode: {{ $gaji->periode->nama_periode }}</div>
                </div>

                <div class="slip-info">
                    <table>
                        <tr><td width="30%"><strong>Nama Sopir</strong></td><td>: {{ $gaji->sopir->nama }}</td></tr>
                        <tr><td><strong>Kode Sopir</strong></td><td>: {{ $gaji->sopir->kode_sopir }}</td></tr>
                        <tr><td><strong>Tanggal Cetak</strong></td><td>: {{ date('d/m/Y H:i') }}</td></tr>
                    </table>
                </div>

                <table class="slip-detail">
                    <thead>
                        <tr>
                            <th width="25%">Tujuan</th>
                            <th width="15%" class="text-center">Rit</th>
                            <th width="20%" class="text-right">Solar</th>
                            <th width="20%" class="text-right">Upah</th>
                            <th width="20%" class="text-right">Sewa DT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gaji->details as $detail)
                        <tr>
                            <td>{{ $detail->tujuan->nama }}</td>
                            <td class="text-center">{{ $detail->tujuan->ritases->where('kode_sopir', $gaji->kode_sopir)->where('periode_id', $gaji->periode_id)->where('status', 'valid')->count() }}</td>
                            <td class="text-right">Rp {{ number_format($detail->solar, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($detail->upah, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($detail->sewa_dt, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="slip-total">
                    <table>
                        <tr><td width="60%">Total Solar</td><td class="text-right">Rp {{ number_format($gaji->total_solar, 0, ',', '.') }}</td></tr>
                        <tr><td>Total Upah Sopir</td><td class="text-right">Rp {{ number_format($gaji->total_upah, 0, ',', '.') }}</td></tr>
                        <tr><td>Total Sewa Dump Truck</td><td class="text-right">Rp {{ number_format($gaji->total_sewa_dt, 0, ',', '.') }}</td></tr>
                        <tr class="grand-total"><td><strong>GRAND TOTAL</strong></td><td class="text-right"><strong>Rp {{ number_format($gaji->grand_total, 0, ',', '.') }}</strong></td></tr>
                    </table>
                </div>
            </div>
            @endif
        @endforeach
    </div>
</body>
</html>
