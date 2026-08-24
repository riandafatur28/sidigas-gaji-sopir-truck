<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Ritase {{ $periode->nama_periode }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12pt; color: #1f2937; margin: 16px; }
        h2 { text-align: center; font-size: 16pt; margin-bottom: 6px; }
        .sub { text-align: center; font-size: 10pt; color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        th, td { border: 1.5px solid #9ca3af; padding: 4px 5px; text-align: center; vertical-align: middle; }
        th { background: #f3f4f6; font-weight: 600; }
        td.nama { text-align: left; font-weight: 600; min-width: 120px; }
        td.dt { text-align: center; font-weight: 600; }
        tr.grand td { background: #dcfce7; font-weight: 700; }
        .tujuan-cell { font-size: 10pt; }
    </style>
    <style id="dark-theme">
        .dark body { background: #000000 !important; color: #e5e5e5 !important; }
        .dark th { background: #111111 !important; color: #e5e5e5 !important; border-color: #333333 !important; }
        .dark td { background: #000000 !important; color: #e5e5e5 !important; border-color: #333333 !important; }
        .dark tr.grand td { background: #1a1a1a !important; color: #e5e5e5 !important; }
        .dark .sub { color: #9e9e9e !important; }
        .dark td.nama { color: #e5e5e5 !important; }
        .dark td.dt { color: #e5e5e5 !important; }
    </style>
    <script>
        (function() {
            try {
                if (window.parent && window.parent.document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.add('dark');
                }
            } catch(e) {}
        })();
    </script>
</head>
<body>
    <h2>Detail Ritase per Sopir</h2>
    <p class="sub">{{ $periode->nama_periode }} ({{ \Carbon\Carbon::parse($periode->tanggal_mulai)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($periode->tanggal_selesai)->format('d/m/Y') }})</p>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="min-width:120px">Nama Sopir</th>
                @foreach($columns as $col)
                    @if ($loop->first || $col['date'] !== $columns[$loop->index-1]['date'])
                        @php
                            // Count sub-columns for this date
                            $subCols = collect($columns)->where('date', $col['date']);
                            $colspan = $subCols->count();
                            $dt = \Carbon\Carbon::parse($col['date']);
                            $dayLabel = $dayNames[$dt->dayOfWeek] ?? '';
                        @endphp
                        <th colspan="{{ $colspan }}">{{ $dt->format('d/m') }}<br><span style="font-size:9pt;font-weight:400;color:#6b7280">{{ $dayLabel }}</span></th>
                    @endif
                @endforeach
                <th rowspan="2" style="min-width:40px">Total</th>
                <th rowspan="2" style="min-width:50px">DT</th>
                <th rowspan="2" style="min-width:40px">Gagal</th>
            </tr>
            <tr>
                @foreach($columns as $col)
                    <th style="font-size:9pt;color:{{ $col['waktu'] == 'P' ? '#d97706' : '#4f46e5' }}">{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php 
                $grandTotal = 0; $grandGagal = 0; $grandEligible = 0; 
                $perDayTotals = [];
                foreach($columns as $col) {
                    $perDayTotals[$col['key']] = 0;
                }
            @endphp
            @foreach($sopirs as $s)
                @php
                    $sk = $s['kode_sopir'];
                    $total = $counts[$sk]['total'] ?? 0;
                    $gagal = $counts[$sk]['gagal'] ?? 0;
                    $eligible = $counts[$sk]['eligible'] ?? 0;
                    $grandTotal += $total;
                    $grandGagal += $gagal;
                    $grandEligible += $eligible;
                @endphp
                <tr>
                    <td class="nama">{{ $s['nama'] }}</td>
                    @foreach($columns as $col)
                        @php
                            $ritKey = $col['date'] . '_' . $col['waktu'];
                            $idx = $col['rit_idx'];
                            $val = isset($data[$sk][$ritKey][$idx]) ? $data[$sk][$ritKey][$idx] : '-';
                            if ($val !== '-') $perDayTotals[$col['key']]++;
                        @endphp
                        <td class="tujuan-cell">{{ $val }}</td>
                    @endforeach
                    <td class="dt">{{ $total }}</td>
                    <td class="dt">{{ $eligible }}</td>
                    <td class="dt">{{ $gagal }}</td>
                </tr>
            @endforeach
            {{-- grand total (dengan jumlah rit per hari) --}}
            <tr class="grand">
                <td style="text-align:left;padding-left:5px">Grand Total</td>
                @foreach($columns as $col)
                    <td class="dt" style="font-size:10pt">{{ $perDayTotals[$col['key']] }}</td>
                @endforeach
                <td class="dt">{{ $grandTotal }}</td>
                <td class="dt">{{ $grandEligible }}</td>
                <td class="dt">{{ $grandGagal }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
