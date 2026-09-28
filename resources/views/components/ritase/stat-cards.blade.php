{{-- Stat Cards --}}
@aware(['totalRitase', 'ritaseValid', 'ritasePending', 'ritaseGagal', 'sopirTerlibat', 'ritaseLembur', 'tanggal', 'filterPeriode'])
<div class="card mb-6">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
        <div style="padding:15px 20px" class="ledger-cell lg:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TOTAL RITASE</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($totalRitase) }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">
                @if($tanggal) per {{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('d M Y') }}
                @elseif($filterPeriode === 'semua') semua periode
                @elseif($filterPeriode) {{ \App\Models\Periode::find($filterPeriode)?->nama_periode }}
                @else semua periode @endif
            </p>
        </div>
        <div style="padding:15px 20px" class="ledger-cell lg:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">VALID</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($ritaseValid) }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">
                @if($totalRitase > 0) {{ round(($ritaseValid / $totalRitase) * 100) }}% @else - @endif
            </p>
        </div>
        <div style="padding:15px 20px" class="ledger-cell lg:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">PENDING</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($ritasePending) }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">
                @if($totalRitase > 0) {{ round(($ritasePending / $totalRitase) * 100) }}% @else - @endif
            </p>
        </div>
        <div style="padding:15px 20px" class="ledger-cell lg:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">GAGAL PRODUKSI</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($ritaseGagal) }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">
                @if($totalRitase > 0) {{ round(($ritaseGagal / $totalRitase) * 100) }}% @else - @endif
            </p>
        </div>
        <div style="padding:15px 20px" class="ledger-cell lg:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">SOPIR AKTIF</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $sopirTerlibat }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">sopir tercatat</p>
        </div>
        <div style="padding:15px 20px" data-ledger>
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">LEMBUR</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($ritaseLembur ?? 0) }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">ritase lembur</p>
        </div>
    </div>
</div>

