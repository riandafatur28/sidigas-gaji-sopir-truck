<x-layouts.dashboard
    :title="'Dashboard'"
    :pageTitle="'Dashboard'"
    >

    {{-- HEADER -- sapaan personal + endowment + fresh-start effect --}}
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold" style="color:var(--text)">Dashboard</h1>
            <p class="text-sm mt-1" style="color:var(--text-muted)">
                Halo, <strong style="color:var(--text)">{{ explode(' ', auth()->user()->name)[0] }}</strong>.
                @if($hariIniRitase > 0)
                    Hari ini <strong style="color:var(--primary)">{{ $hariIniRitase }} ritase</strong> sudah tercatat.
                @else
                    Belum ada ritase hari ini — mulai catat perjalanan pertama.
                @endif
                @if($sisaHari > 0 && $periodeAktif)
                    Periode tersisa <strong style="color:var(--accent)">{{ $sisaHari }} hari</strong>.
                @endif
            </p>
        </div>
        <div class="relative" id="dashFilterWrap">
            <button onclick="toggleDashFilter()" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
                @if($filter != 'semua' || $tanggal)
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                @endif
                
            </button>
            <div class="hidden absolute right-0 mt-2 w-72 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4" id="dashFilterPanel">
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode</label>
                        <select id="periodeFilter" onchange="window.location.href='{{ route('dashboard') }}?periode='+this.value+(document.getElementById('tanggalFilter').value ? '&tanggal='+document.getElementById('tanggalFilter').value : '')" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                            <option value="semua" {{ $filter == 'semua' ? 'selected' : '' }}>Semua Waktu</option>
                            <option value="periode_ini" {{ $filter == 'periode_ini' ? 'selected' : '' }}>Periode Ini</option>
                            <option value="periode_lalu" {{ $filter == 'periode_lalu' ? 'selected' : '' }}>Periode Lalu</option>
                            <option value="bulan_ini" {{ $filter == 'bulan_ini' ? 'selected' : '' }}>Bulan Ini</option>
                            <option value="3_bulan_lalu" {{ $filter == '3_bulan_lalu' ? 'selected' : '' }}>3 Bulan</option>
                            <option value="6_bulan_lalu" {{ $filter == '6_bulan_lalu' ? 'selected' : '' }}>6 Bulan</option>
                            <option value="1_tahun_lalu" {{ $filter == '1_tahun_lalu' ? 'selected' : '' }}>1 Tahun</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</label>
                        <input type="date" id="tanggalFilter" value="{{ $tanggal }}" onchange="window.location.href='{{ route('dashboard') }}?periode='+document.getElementById('periodeFilter').value+'&tanggal='+this.value" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                    </div>
                    @if($tanggal)
                        <a href="{{ route('dashboard') }}?periode={{ $filter }}" class="block text-center px-3 py-2 border border-gray-200 rounded text-sm text-gray-600 hover:bg-gray-50">Reset</a>
                    @endif
                </div>
            </div>
        </div>
        <script>
        function toggleDashFilter(){document.getElementById('dashFilterPanel').classList.toggle('hidden');}
        document.addEventListener('click',function(e){const w=document.getElementById('dashFilterWrap');if(w&&!w.contains(e.target)){document.getElementById('dashFilterPanel').classList.add('hidden');}});
        </script>
    </div>

    {{-- NEXT ACTION -- satu action utama, kurangi decision fatigue --}}
    @if($validasiPending > 0)
    <div class="card mb-6" style="border-left:4px solid var(--accent);border-radius:14px 14px 14px 14px;box-shadow:0 1px 3px rgba(0,0,0,0.04),0 4px 12px rgba(74,63,107,0.05)">
        <div class="card-body flex items-center justify-between">
            <div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:2px">Sopir mengandalkan Anda</p>
                <p style="font-size:15px;font-weight:600;color:var(--text)">
                    <strong style="color:var(--accent)">{{ $validasiPending }}</strong> validasi menunggu —
                    sopir belum bisa melihat penghasilan mereka sampai Anda review.
                </p>
            </div>
            <a href="#" class="btn btn-primary btn-sm flex-shrink-0">Review Validasi</a>
        </div>
    </div>
    @elseif($sisaHari > 0 && $sisaHari <= 3 && $periodeAktif)
    <div class="card mb-6" style="border-left:4px solid var(--primary);border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,0.04),0 4px 12px rgba(74,63,107,0.05)">
        <div class="card-body flex items-center justify-between">
            <div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:2px">Periode hampir berakhir</p>
                <p style="font-size:15px;font-weight:600;color:var(--text)">
                    Tinggal <strong style="color:var(--primary)">{{ $sisaHari }} hari</strong> lagi.
                    Segera hitung gaji sebelum periode ditutup.
                </p>
            </div>
            <a href="{{ route('gaji.index') }}" class="btn btn-primary btn-sm flex-shrink-0">Hitung Gaji</a>
        </div>
    </div>
    @elseif(!$periodeAktif)
    <div class="card mb-6" style="border-left:4px solid var(--text-dims);border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,0.04),0 4px 12px rgba(74,63,107,0.05)">
        <div class="card-body flex items-center justify-between">
            <div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:2px">Belum ada periode aktif</p>
                <p style="font-size:15px;font-weight:600;color:var(--text)">
                    Buat periode baru untuk mulai mencatat ritase dan menghitung gaji.
                </p>
            </div>
            <a href="{{ route('periode.index') }}" class="btn btn-primary btn-sm flex-shrink-0">Buat Periode</a>
        </div>
    </div>
    @endif

    {{-- PROGRESS PERIODE -- goal gradient effect --}}
    @if($periodeAktif)
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p style="font-size:13px;font-weight:600;color:var(--text)">{{ $periodeAktif->nama_periode }}</p>
                    <p style="font-size:12px;color:var(--text-muted)">
                        {{ $periodeAktif->tanggal_mulai->format('d M') }} &mdash; {{ $periodeAktif->tanggal_selesai->format('d M Y') }}
                        &middot; Sisa {{ $sisaHari }} hari
                    </p>
                </div>
                <span style="font-size:24px;font-weight:700;color:var(--primary)">{{ $progressPeriode }}%</span>
            </div>
            <div style="height:6px;background:#e8e4de;border-radius:3px;overflow:hidden">
                <div style="height:100%;width:{{ $progressPeriode }}%;background:linear-gradient(90deg,var(--primary),var(--accent));border-radius:3px;transition:width 0.5s ease"></div>
            </div>
            <div class="flex justify-between mt-2">
                <span style="font-size:11px;color:var(--text-dims)">Mulai</span>
                <span style="font-size:11px;color:var(--text-dims)">{{ $totalRitase }} ritase tercatat</span>
                <span style="font-size:11px;color:var(--text-dims)">Selesai</span>
            </div>
        </div>
    </div>
    @endif

    {{-- 4 STAT CARDS dengan framing psikologis --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- 4 stat: Armada, Ritase, Validasi, Gaji --}}
        <div class="stat-card">
            <p style="font-size:12px;color:var(--text-muted);font-weight:500">Sopir Siap</p>
            <p style="font-size:28px;font-weight:700;color:var(--text);line-height:1">{{ $sopirAktif }}</p>
            <p style="font-size:12px;color:var(--text-dims)">
                @if($sopirAktif == $totalSopir) Seluruh armada aktif
                @else {{ $totalSopir - $sopirAktif }} sopir nonaktif @endif
            </p>
        </div>
        <div class="stat-card">
            <p style="font-size:12px;color:var(--text-muted);font-weight:500">Ritase Valid</p>
            <p style="font-size:28px;font-weight:700;color:var(--text);line-height:1">{{ number_format($ritaseValid) }}</p>
            <p style="font-size:12px;color:var(--text-dims)">
                @if($totalRitase > 0)
                    {{ round(($ritaseValid / max($totalRitase,1)) * 100) }}% valid &middot; {{ number_format($ritaseGagal) }} gagal
                @else Belum ada ritase @endif
            </p>
        </div>
        <div class="stat-card">
            <p style="font-size:12px;color:var(--text-muted);font-weight:500">Menunggu Validasi</p>
            <p style="font-size:28px;font-weight:700;color:var(--text);line-height:1">{{ $validasiPending }}</p>
            <p style="font-size:12px;color:var(--text-dims)">
                @if($validasiHariIni > 0) {{ $validasiHariIni }} masuk hari ini
                @elseif($validasiPending > 0) Segera review
                @else Semua valid @endif
            </p>
        </div>
        <div class="stat-card">
            <p style="font-size:12px;color:var(--text-muted);font-weight:500">Total Gaji</p>
            <p style="font-size:28px;font-weight:700;color:var(--text);line-height:1">
                @if($totalGaji >= 1000000) Rp {{ number_format($totalGaji / 1000000, 1) }} Jt
                @else Rp {{ number_format($totalGaji, 0, ',', '.') }} @endif
            </p>
            <p style="font-size:12px;color:var(--text-dims)">{{ strtolower($periodLabel) }}</p>
        </div>
    </div>

    {{-- AKTIVITAS + PEMIMPIN (social proof + competition) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Aktivitas terbaru --}}
        <div class="card lg:col-span-2">
            <div class="card-header">
                <span class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Aktivitas Terbaru</span>
                @if($recentRitase->count() > 0)
                <a href="{{ route('ritase.index') }}" style="font-size:12px;color:var(--primary);text-decoration:none">Lihat Semua</a>
                @endif
            </div>
            <div style="padding:0">
                @forelse($recentRitase as $rit)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-bottom:1px solid var(--border);transition:background 0.15s"
                     onmouseover="this.style.background='rgba(232,229,239,0.3)'" onmouseout="this.style.background=''">
                    <div class="flex items-center gap-3">
                        <span style="font-size:14px;font-weight:500;color:var(--text)">{{ $rit->sopir->nama ?? '-' }}</span>
                        <span style="color:var(--text-dims);font-size:13px">ke</span>
                        <span style="font-size:13px;color:var(--text-muted)">{{ $rit->tujuan->nama ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span style="font-size:12px;color:var(--text-dims)">{{ $rit->tanggal->format('d/m') }}</span>
                        <span class="badge
                            {{ $rit->status == 'valid' ? 'badge-success' : '' }}
                            {{ $rit->status == 'pending' ? 'badge-warning' : '' }}
                            {{ $rit->status == 'gagal_produksi' ? 'badge-danger' : '' }}">
                            {{ $rit->status == 'valid' ? 'Selesai' : ($rit->status == 'pending' ? 'Pending' : 'Gagal') }}
                        </span>
                    </div>
                </div>
                @empty
                <div style="padding:32px 20px;text-align:center;color:var(--text-dims);font-size:14px">
                    Belum ada aktivitas tercatat
                </div>
                @endforelse
            </div>
        </div>

        {{-- Top sopir --}}
        <div class="flex flex-col gap-4">
            @if($topSopir->count() > 0)
            <div class="card">
                <div class="card-header">
                    <span class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Sopir Teraktif</span>
                </div>
                <div style="padding:8px 16px">
                    @foreach($topSopir as $i => $ts)
                    <div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:{{ !$loop->last ? '1px solid var(--border)' : 'none' }}">
                        <span style="width:20px;font-size:13px;font-weight:700;color:{{ $i == 0 ? 'var(--accent)' : 'var(--text-dims)' }}">{{ $i + 1 }}</span>
                        <span style="flex:1;font-size:13px;font-weight:500;color:var(--text)">{{ $ts->sopir->nama ?? '-' }}</span>
                        <span style="font-size:12px;font-weight:600;color:{{ $i == 0 ? 'var(--accent)' : 'var(--text-dims)' }}">{{ $ts->total }}x</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- AKSES CEPAT -- mengurangi gesekan (friction) --}}
    <div class="card">
        <div class="card-header">
            <span class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Akses Cepat</span>
        </div>
        <div class="card-body flex flex-wrap gap-2">
            <a href="{{ route('ritase.index') }}" class="btn btn-outline btn-sm">Input Ritase</a>
            <a href="{{ route('gaji.index') }}" class="btn btn-outline btn-sm">Hitung Gaji</a>
            <a href="{{ route('sopir.index') }}" class="btn btn-outline btn-sm">Kelola Sopir</a>
            <a href="{{ route('periode.index') }}" class="btn btn-outline btn-sm">Kelola Periode</a>
        </div>
    </div>

</x-layouts.dashboard>
