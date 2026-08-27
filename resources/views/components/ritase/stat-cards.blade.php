{{-- Stat Cards --}}
@aware(['totalRitase', 'ritaseValid', 'ritasePending', 'ritaseGagal', 'sopirTerlibat', 'tanggal', 'filterPeriode'])
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="stat-card">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Ritase</p>
        <p class="text-2xl font-bold" style="color:var(--text)">{{ number_format($totalRitase) }}</p>
        <p class="text-xs" style="color:var(--text-dims)">
            @if($tanggal) per {{ \Carbon\Carbon::parse($tanggal)->format('d M Y') }}
            @elseif($filterPeriode) {{ $filterPeriode ? \App\Models\Periode::find($filterPeriode)?->nama_periode : '' }}
            @else semua periode @endif
        </p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Valid</p>
        <p class="text-2xl font-bold text-green-600">{{ number_format($ritaseValid) }}</p>
        <p class="text-xs" style="color:var(--text-dims)">
            @if($totalRitase > 0) {{ round(($ritaseValid / $totalRitase) * 100) }}% @else - @endif
        </p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pending</p>
        <p class="text-2xl font-bold text-yellow-600">{{ number_format($ritasePending) }}</p>
        <p class="text-xs" style="color:var(--text-dims)">
            @if($totalRitase > 0) {{ round(($ritasePending / $totalRitase) * 100) }}% @else - @endif
        </p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Gagal Produksi</p>
        <p class="text-2xl font-bold text-red-600">{{ number_format($ritaseGagal) }}</p>
        <p class="text-xs" style="color:var(--text-dims)">
            @if($totalRitase > 0) {{ round(($ritaseGagal / $totalRitase) * 100) }}% @else - @endif
        </p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sopir Aktif</p>
        <p class="text-2xl font-bold" style="color:var(--text)">{{ $sopirTerlibat }}</p>
        <p class="text-xs" style="color:var(--text-dims)">sopir tercatat</p>
    </div>
</div>
