<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Ritase {{ $periode->nama_periode }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Dark mode overrides for iframe */
        .dark body { background: #000000 !important; }
        .dark th { background: #111111 !important; color: #e5e5e5 !important; border-color: #333333 !important; }
        .dark td { background: #000000 !important; color: #e5e5e5 !important; border-color: #333333 !important; }
        /* Thicker table borders */
        th, td { border-width: 1.5px !important; }
        .dark tr.hover\:bg-gray-50:hover { background: #1a1a1a !important; }
        .dark h1 { color: #e5e5e5 !important; }
        .dark .text-gray-500 { color: #9e9e9e !important; }
        .dark .text-gray-400 { color: #6b6b6b !important; }
        .dark .text-gray-700 { color: #cccccc !important; }
        .dark .text-gray-900 { color: #e5e5e5 !important; }
        .dark .text-green-700, .dark .text-green-800 { color: #e5e5e5 !important; }
        .dark .text-amber-600, .dark .text-green-600 { color: #e5e5e5 !important; }
        /* Red kept for gagal column */
        .dark .text-red-600 { color: #f87171 !important; }
        .dark .border-gray-200 { border-color: #333333 !important; }
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
<body class="bg-white p-4">
    <div class="max-w-full">
        <div class="text-center mb-4">
            <h1 class="text-lg font-bold text-gray-900">Detail Ritase per Sopir</h1>
            <p class="text-sm text-gray-500">{{ $periode->nama_periode }} ({{ \Carbon\Carbon::parse($periode->tanggal_mulai)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($periode->tanggal_selesai)->format('d/m/Y') }})</p>
        </div>

        <div class="table-responsive">
            <table class="w-full text-sm border-collapse" style="min-width:100%">
                <thead>
                    <tr>
                        <th rowspan="2" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider border border-gray-200" style="min-width:120px;color:var(--text-muted,#6b7280)">Nama Sopir</th>
                        @foreach($columns as $col)
                            @if ($loop->first || $col['date'] !== $columns[$loop->index-1]['date'])
                                @php
                                    $subCols = collect($columns)->where('date', $col['date']);
                                    $colspan = $subCols->count();
                                    $dt = \Carbon\Carbon::parse($col['date']);
                                    $dayLabel = $dayNames[$dt->dayOfWeek] ?? '';
                                @endphp
                                <th colspan="{{ $colspan }}" class="px-1 py-2 text-center text-xs font-semibold uppercase tracking-wider border border-gray-200" style="color:var(--text-muted,#6b7280)">{{ $dt->format('d/m') }}<br><span class="text-[10px] font-normal" style="color:var(--text-muted,#9ca3af)">{{ $dayLabel }}</span></th>
                            @endif
                        @endforeach
                        <th rowspan="2" class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider border border-gray-200" style="min-width:50px;color:var(--text-muted,#6b7280)">Total</th>
                        <th rowspan="2" class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider border border-gray-200" style="min-width:65px;color:var(--text-muted,#6b7280)">Total DT</th>
                        <th rowspan="2" class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider border border-gray-200" style="min-width:60px;color:var(--text-muted,#6b7280)">Gagal</th>
                    </tr>
                    <tr>
                        @foreach($columns as $col)
                            @php
                                $warnClass = $col['waktu'] == 'P' ? 'text-amber-600 bg-amber-50/30' : 'text-green-600 bg-green-50/30';
                            @endphp
                            <th class="px-1 py-1 text-center text-[10px] font-semibold border border-gray-200 {{ $warnClass }}">{{ $col['label'] }}</th>
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
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm font-semibold border border-gray-200" style="color:var(--text,#1f2937)">{{ $s['nama'] }}</td>
                            @foreach($columns as $col)
                                @php
                                    $ritKey = $col['date'] . '_' . $col['waktu'];
                                    $val = isset($data[$sk][$ritKey][$col['rit_idx']]) ? $data[$sk][$ritKey][$col['rit_idx']] : '-';
                                    $bgClass = $val !== '-' ? ($col['waktu'] == 'P' ? 'bg-amber-50/30' : 'bg-green-50/30') : '';
                                    if ($val !== '-') $perDayTotals[$col['key']]++;
                                @endphp
                                <td class="px-1 py-2 text-center text-xs font-medium border border-gray-200 {{ $bgClass }}" style="color:var(--text,#374151)">{{ $val }}</td>
                            @endforeach
                            <td class="px-3 py-2 text-center text-sm font-bold border border-gray-200" style="color:var(--text,#111827)">{{ $total }}</td>
                            <td class="px-3 py-2 text-center text-sm font-bold border border-gray-200" style="color:var(--text,#16a34a)">{{ $eligible }}</td>
                            <td class="px-3 py-2 text-center text-sm font-bold text-red-600 border border-gray-200">{{ $gagal }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-bold">
                        <td class="px-3 py-2 text-sm text-left border border-gray-200" style="color:var(--text,#374151)">Grand Total</td>
                        @foreach($columns as $col)
                            <td class="px-1 py-1.5 text-center text-xs font-bold border border-gray-200" style="color:var(--text,#15803d)">{{ $perDayTotals[$col['key']] }}</td>
                        @endforeach
                        <td class="px-3 py-2 text-center text-sm font-bold border border-gray-200" style="color:var(--text,#111827)">{{ $grandTotal }}</td>
                        <td class="px-3 py-2 text-center text-sm font-bold border border-gray-200" style="color:var(--text,#16a34a)">{{ $grandEligible }}</td>
                        <td class="px-3 py-2 text-center text-sm font-bold text-red-600 border border-gray-200">{{ $grandGagal }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
