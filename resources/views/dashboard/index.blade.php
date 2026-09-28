<x-layouts.dashboard
    :title="'Dashboard'"
    :pageTitle="'Dashboard'"
    >

    @push('styles')
    <style>
        .ledger-row:hover { background: var(--table-hover); }
        .dash-num { font-variant-numeric: tabular-nums; }
        .dash-link { font-size: 13px; font-weight: 600; color: #3c6650; text-decoration: none; white-space: nowrap; }
        .dash-link:hover { text-decoration: underline; }
        .btn-primary { background: #3c6650; }
        .btn-primary:hover { background: #2e5040; }
        .ledger-cell { border-color: var(--border); }
        .chart-box { position: relative; height: 200px; }
        @media (max-width: 640px){
            .chart-box { height: 250px; }
            .card-body { padding: 16px; }
        }
        .nav-tile {
            display: flex; align-items: center; gap: 14px;
            background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px;
            padding: 14px 16px; text-decoration: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(74,63,107,0.05);
            transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        }
        .nav-tile:hover { border-color: #3c6650; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(60,102,80,0.14); }
        .nav-tile-icon {
            width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: transparent; color: #3c6650;
        }
        .dark .nav-tile-icon { color: #ffffff; }
        .nav-tile-icon svg { width: 20px; height: 20px; }
        .nav-tile-text { display: flex; flex-direction: column; min-width: 0; }
        .nav-tile-title { font-size: 13px; font-weight: 650; color: var(--text); }
        .nav-tile-sub { font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
    @endpush

    <div class="flex items-start justify-between mb-6">
        <div>
            <p style="font-size:11px;font-weight:600;letter-spacing:0.12em;color:var(--text-dims)">SIDIGAS &middot; OPERASIONAL</p>
            <h1 class="text-2xl font-bold tracking-tight" style="color:var(--text)">Dashboard</h1>
            <p class="text-sm mt-1 dash-num" style="color:var(--text-muted)">
                {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, j F Y') }}
                @if($periodeAktif)
                    &middot; {{ $periodeAktif->nama_periode }}
                @endif
                @if($hariIniRitase > 0)
                    &middot; {{ $hariIniRitase }} ritase hari ini
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

    @if($validasiPending > 0)
    <div class="card mb-5">
        <div class="card-body flex items-center justify-between gap-4" style="padding-top:13px;padding-bottom:13px">
            <p class="text-sm dash-num" style="color:var(--text)">
                <span style="font-size:11px;font-weight:700;letter-spacing:0.1em;color:var(--danger)">PERLU TINDAKAN</span>
                <span style="margin-left:10px">{{ $validasiPending }} validasi menunggu review</span>
                <span class="hidden sm:inline" style="color:var(--text-dims);font-size:13px"> — ritase terkait belum masuk hitungan gaji.</span>
            </p>
            <a href="{{ route('validasi-bukti.kelola') }}" class="btn btn-primary btn-sm flex-shrink-0"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>Review</a>
        </div>
    </div>
    @elseif($sisaHari > 0 && $sisaHari <= 3 && $periodeAktif)
    <div class="card mb-5">
        <div class="card-body flex items-center justify-between gap-4" style="padding-top:13px;padding-bottom:13px">
            <p class="text-sm dash-num" style="color:var(--text)">
                <span style="font-size:11px;font-weight:700;letter-spacing:0.1em;color:var(--warning)">PERIODE BERAKHIR</span>
                <span style="margin-left:10px">H-{{ $sisaHari }} — hitung gaji sebelum periode ditutup.</span>
            </p>
            <a href="{{ route('gaji.index') }}" class="btn btn-primary btn-sm flex-shrink-0"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Hitung Gaji</a>
        </div>
    </div>
    @elseif(!$periodeAktif)
    <div class="card mb-5">
        <div class="card-body flex items-center justify-between gap-4" style="padding-top:13px;padding-bottom:13px">
            <p class="text-sm" style="color:var(--text)">
                <span style="font-size:11px;font-weight:700;letter-spacing:0.1em;color:var(--text-dims)">BELUM ADA PERIODE</span>
                <span style="margin-left:10px">Buat periode baru untuk mulai mencatat ritase.</span>
            </p>
            <a href="{{ route('periode.index') }}" class="btn btn-primary btn-sm flex-shrink-0"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v6M9 14h6"/></svg>Buat Periode</a>
        </div>
    </div>
    @endif

    @if($periodeAktif)
    <div class="card mb-5">
        <div class="card-body" style="padding-top:13px;padding-bottom:13px">
            <div class="flex items-baseline justify-between mb-2">
                <p class="text-sm font-semibold" style="color:var(--text)">{{ $periodeAktif->nama_periode }}
                    <span class="font-normal dash-num" style="color:var(--text-muted);font-size:12px;margin-left:8px">{{ $periodeAktif->tanggal_mulai->locale('id')->translatedFormat('d M') }} &ndash; {{ $periodeAktif->tanggal_selesai->locale('id')->translatedFormat('d M Y') }}</span>
                </p>
                <p class="text-sm dash-num" style="color:var(--text-muted)">H-{{ $sisaHari }} &middot; {{ $progressPeriode }}%</p>
            </div>
            <div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden">
                <div style="height:100%;width:{{ $progressPeriode }}%;background:#3c6650;border-radius:2px"></div>
            </div>
            <p class="dash-num" style="font-size:12px;color:var(--text-dims);margin-top:6px">{{ $totalRitase }} ritase tercatat periode ini</p>
        </div>
    </div>
    @endif

    <div class="card mb-6">
        <div class="grid grid-cols-2 lg:grid-cols-4">
            <div style="padding:15px 20px" class="ledger-cell lg:border-r" data-ledger>
                <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">SOPIR AKTIF</p>
                <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $sopirAktif }}<span style="font-size:13px;font-weight:500;color:var(--text-dims)"> / {{ $totalSopir }}</span></p>
                <p class="dash-num" style="font-size:12px;color:var(--text-muted)">{{ $totalSopir - $sopirAktif }} nonaktif periode ini</p>
            </div>
            <div style="padding:15px 20px" class="ledger-cell lg:border-r" data-ledger>
                <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">RITASE VALID</p>
                <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($ritaseValid) }}</p>
                <p class="dash-num" style="font-size:12px;color:var(--text-muted)">
                    @if($totalRitase > 0)
                        {{ round(($ritaseValid / max($totalRitase,1)) * 100) }}% dari {{ number_format($totalRitase) }} &middot; {{ number_format($ritaseGagal) }} gagal
                    @else Belum ada ritase @endif
                </p>
            </div>
            <div style="padding:15px 20px" class="ledger-cell lg:border-r" data-ledger>
                <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">ANTREAN VALIDASI</p>
                <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $validasiPending }}</p>
                <p class="dash-num" style="font-size:12px;color:var(--text-muted)">
                    @if($validasiHariIni > 0) {{ $validasiHariIni }} masuk hari ini
                    @elseif($validasiPending > 0) belum ada yang masuk hari ini
                    @else antrean kosong @endif
                </p>
            </div>
            <div style="padding:15px 20px" data-ledger>
                <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TOTAL GAJI</p>
                <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">
                    @if($totalGaji >= 1000000) Rp {{ number_format($totalGaji / 1000000, 1, ',', '.') }} jt
                    @else Rp {{ number_format($totalGaji, 0, ',', '.') }} @endif
                </p>
                <p class="dash-num" style="font-size:12px;color:var(--text-muted)">{{ $periodLabel }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <span class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Ritase harian</span>
                <span class="dash-num" style="font-size:12px;color:var(--text-dims)">{{ $periodLabel }} &middot; {{ number_format($totalRitase - $ritaseGagal) }} berhasil &middot; {{ number_format($ritaseGagal) }} gagal</span>
            </div>
            <div class="card-body">
                <div class="chart-box">
                    <canvas id="chartRitaseTrend"></canvas>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <span class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Status ritase</span>
            </div>
            <div class="card-body">
                <div class="chart-box">
                    <canvas id="chartRitaseStatus"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="card">
            <div class="card-header">
                <span class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Sopir teraktif</span>
                <span style="font-size:12px;color:var(--text-dims)">5 teratas</span>
            </div>
            <div class="card-body">
                <div class="chart-box">
                    <canvas id="chartTopSopir"></canvas>
                </div>
            </div>
        </div>
        <div class="card lg:col-span-2">
            <div class="card-header">
                <span class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Gaji per periode</span>
                <span style="font-size:12px;color:var(--text-dims)">6 periode terakhir</span>
            </div>
            <div class="card-body">
                <div class="chart-box">
                    <canvas id="chartGajiTrend"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header">
            <span class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Ritase terakhir</span>
            @if($recentRitase->count() > 0)
            <a href="{{ route('ritase.index') }}" class="dash-link" style="font-weight:500">Semua ritase</a>
            @endif
        </div>
        <div style="padding:0">
            @forelse($recentRitase as $rit)
            <div class="ledger-row" style="display:flex;align-items:center;justify-content:space-between;padding:11px 24px;border-bottom:1px solid var(--border)">
                    <div class="flex items-center gap-x-3 gap-y-1" style="min-width:0;flex:1;flex-wrap:wrap">
                        <span class="dash-num" style="font-size:12px;color:var(--text-dims);flex-shrink:0">{{ $rit->tanggal->format('d/m') }}</span>
                        <span style="font-size:13px;font-weight:600;color:var(--text)">{{ $rit->sopir->nama ?? '-' }}</span>
                        <span style="color:var(--border);font-size:12px;flex-shrink:0">/</span>
                        <span style="font-size:13px;color:var(--text-muted)">{{ $rit->tujuan->nama ?? '-' }}</span>
                    </div>
                <span class="badge
                    {{ $rit->status == 'valid' ? 'badge-success' : '' }}
                    {{ $rit->status == 'pending' ? 'badge-warning' : '' }}
                    {{ $rit->status == 'gagal_produksi' ? 'badge-danger' : '' }}" style="flex-shrink:0;margin-left:12px">
                    {{ $rit->status == 'valid' ? 'Selesai' : ($rit->status == 'pending' ? 'Pending' : 'Gagal') }}
                </span>
            </div>
            @empty
            <div style="padding:28px 24px;color:var(--text-dims);font-size:13px">
                Belum ada ritase pada rentang ini.
            </div>
            @endforelse
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('ritase.index') }}" class="nav-tile">
            <span class="nav-tile-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </span>
            <span class="nav-tile-text">
                <span class="nav-tile-title">Input ritase</span>
                <span class="nav-tile-sub">Catat perjalanan harian</span>
            </span>
        </a>
        <a href="{{ route('gaji.index') }}" class="nav-tile">
            <span class="nav-tile-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <span class="nav-tile-text">
                <span class="nav-tile-title">Hitung gaji</span>
                <span class="nav-tile-sub">Rekap upah per periode</span>
            </span>
        </a>
        <a href="{{ route('sopir.index') }}" class="nav-tile">
            <span class="nav-tile-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </span>
            <span class="nav-tile-text">
                <span class="nav-tile-title">Kelola sopir</span>
                <span class="nav-tile-sub">{{ $totalSopir }} terdaftar</span>
            </span>
        </a>
        <a href="{{ route('periode.index') }}" class="nav-tile">
            <span class="nav-tile-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
            <span class="nav-tile-text">
                <span class="nav-tile-title">Kelola periode</span>
                <span class="nav-tile-sub">{{ $periodeAktif ? $periodeAktif->nama_periode : 'Belum ada periode' }}</span>
            </span>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    (function(){
        if (typeof Chart === 'undefined') return;
        function isDark(){ return document.documentElement.classList.contains('dark'); }

        function cssVar(name, fallback){
            var v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
            return v || fallback;
        }

        function palette(){
            if (isDark()) {
                return {
                    line: '#ffffff',
                    lineFill: 'rgba(255,255,255,0.07)',
                    fail: '#ff5a5a',
                    bar: 'rgba(255,255,255,0.82)',
                    barHover: '#ffffff',
                    grid: 'rgba(255,255,255,0.09)',
                    donut: ['#ffffff', '#ffcf33', '#ff5a5a']
                };
            }
            return {
                line: '#3c6650',
                    lineFill: 'rgba(60,102,80,0.08)',
                    fail: '#dc2626',
                bar: '#3c6650',
                barHover: '#2e5040',
                grid: 'rgba(120, 113, 100, 0.12)',
                    donut: ['#3c6650', '#eab308', '#dc2626']
            };
        }

        var p = palette();
        var tick = cssVar('--text-dims', '#b0acbc');
        var isMobile = window.matchMedia('(max-width: 640px)').matches;
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var animBase = reduceMotion ? false : { duration: 800, easing: 'easeOutQuart' };
        var animDonut = reduceMotion ? false : { animateRotate: true, animateScale: false, duration: 900, easing: 'easeOutQuart' };

        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
        Chart.defaults.font.size = isMobile ? 12 : 11;
        Chart.defaults.color = tick;

        var grid = { color: p.grid, drawTicks: false };
        var tickOpts = { font: { size: isMobile ? 12 : 10 }, maxTicksLimit: isMobile ? 6 : 8, padding: 6 };
        var barYTickOpts = { font: { size: isMobile ? 12 : 10 }, padding: 4 };
        var gajiXTickOpts = Object.assign({}, tickOpts, { maxRotation: 0, autoSkip: false, callback: function(value){
            var label = this.getLabelForValue(value);
            var words = label.split(' ');
            var lines = [];
            var cur = '';
            words.forEach(function(w){
                if ((cur + ' ' + w).trim().length > 12 && cur !== '') { lines.push(cur); cur = w; }
                else { cur = (cur + ' ' + w).trim(); }
            });
            if (cur !== '') lines.push(cur);
            if (lines.length > 2) {
                lines = lines.slice(0, 2);
                if (lines[1].length > 12) lines[1] = lines[1].slice(0, 12) + '…';
            }
            return lines.length > 1 ? lines : lines[0];
        } });
        var jtCallback = function(v){ return v >= 1000000 ? (v / 1000000) + ' jt' : v; };
        var gajiYMobileTickOpts = Object.assign({}, barYTickOpts, { callback: function(value){
            var label = this.getLabelForValue(value);
            return label.length > 14 ? label.slice(0, 14) + '…' : label;
        } });

        function make(id, config){
            var el = document.getElementById(id);
            if (!el) return null;
            return new Chart(el, config);
        }

        var trendChart = make('chartRitaseTrend', {
            type: 'line',
            data: {
                labels: @json($ritaseTrendLabels ?? []),
                datasets: [{
                    label: 'Berhasil',
                    data: @json($ritaseTrendData ?? []),
                    borderColor: p.line,
                    backgroundColor: p.lineFill,
                    fill: true,
                    tension: 0.25,
                    borderWidth: 1.5,
                    pointRadius: 0,
                    pointHoverRadius: isMobile ? 5 : 3,
                    pointBackgroundColor: p.line
                }, {
                    label: 'Gagal',
                    data: @json($ritaseGagalTrendData ?? []),
                    borderColor: p.fail,
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.25,
                    borderWidth: 1.5,
                    pointRadius: 0,
                    pointHoverRadius: isMobile ? 5 : 3,
                    pointBackgroundColor: p.fail
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, animation: animBase, interaction: { mode: 'index', intersect: false }, plugins: { legend: { display: true, position: 'top', align: 'end', labels: { boxWidth: 14, padding: 12, usePointStyle: true, pointStyle: 'circle' } }, tooltip: { padding: 10, cornerRadius: 8 } }, scales: { y: { beginAtZero: true, grid: grid, border: { display: false }, ticks: Object.assign({ precision: 0 }, tickOpts) }, x: { grid: { display: false }, border: { display: false }, ticks: tickOpts } } }
        });

        var statusChart = make('chartRitaseStatus', {
            type: 'doughnut',
            data: {
                labels: ['Valid', 'Pending', 'Gagal'],
                datasets: [{
                    data: [{{ $ritaseValid ?? 0 }}, {{ $ritasePending ?? 0 }}, {{ $ritaseGagal ?? 0 }}],
                    backgroundColor: p.donut.slice(),
                    borderWidth: 0,
                    hoverOffset: 3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, animation: animDonut, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, padding: 12, usePointStyle: true, pointStyle: 'circle' } }, tooltip: { padding: 10, cornerRadius: 8 } } }
        });

        new MutationObserver(function(){
            var np = palette();
            var legendColor = cssVar('--text-muted', '#8a8698');
            var tickColor = cssVar('--text-dims', '#b0acbc');
            Chart.defaults.color = tickColor;

            if (trendChart) {
                var t = trendChart.data.datasets[0];
                t.borderColor = np.line;
                t.backgroundColor = np.lineFill;
                t.pointBackgroundColor = np.line;
                var g = trendChart.data.datasets[1];
                if (g) {
                    g.borderColor = np.fail;
                    g.pointBackgroundColor = np.fail;
                }
            }
            if (statusChart) {
                statusChart.data.datasets[0].backgroundColor = np.donut.slice();
            }
            if (topChart) {
                topChart.data.datasets[0].backgroundColor = np.bar;
                topChart.data.datasets[0].hoverBackgroundColor = np.barHover;
            }
            if (gajiChart) {
                gajiChart.data.datasets[0].backgroundColor = np.bar;
                gajiChart.data.datasets[0].hoverBackgroundColor = np.barHover;
            }
            [trendChart, statusChart, topChart, gajiChart].forEach(function(ch){
                if (!ch) return;
                var scales = ch.options.scales || {};
                Object.keys(scales).forEach(function(k){
                    if (scales[k].ticks) scales[k].ticks.color = tickColor;
                    if (scales[k].grid) scales[k].grid.color = np.grid;
                });
                var legend = ch.options.plugins && ch.options.plugins.legend;
                if (legend && legend.labels) legend.labels.color = legendColor;
                ch.update();
            });
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        var topChart = make('chartTopSopir', {
            type: 'bar',
            data: {
                labels: @json(($topSopir ?? collect())->map(fn($t) => $t->sopir->nama ?? $t->kode_sopir)->values()),
                datasets: [{
                    label: 'Ritase',
                    data: @json(($topSopir ?? collect())->pluck('total')->values()),
                    backgroundColor: p.bar,
                    hoverBackgroundColor: p.barHover,
                    borderRadius: 3,
                    barThickness: isMobile ? 16 : 12,
                    categoryPercentage: 0.7
                }]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: animBase, plugins: { legend: { display: false }, tooltip: { padding: 10, cornerRadius: 8 } }, scales: { x: { beginAtZero: true, grid: grid, border: { display: false }, ticks: Object.assign({ precision: 0 }, tickOpts) }, y: { grid: { display: false }, border: { display: false }, ticks: barYTickOpts } } }
        });

        var gajiScales = isMobile
            ? { x: { beginAtZero: true, grid: grid, border: { display: false }, ticks: Object.assign({ callback: jtCallback }, tickOpts) }, y: { grid: { display: false }, border: { display: false }, ticks: gajiYMobileTickOpts } }
            : { y: { beginAtZero: true, grid: grid, border: { display: false }, ticks: Object.assign({ callback: jtCallback }, tickOpts) }, x: { grid: { display: false }, border: { display: false }, ticks: gajiXTickOpts } };

        var gajiChart = make('chartGajiTrend', {
            type: 'bar',
            indexAxis: isMobile ? 'y' : 'x',
            data: {
                labels: @json($gajiTrendLabels ?? []),
                datasets: [{
                    label: 'Total gaji',
                    data: @json($gajiTrendData ?? []),
                    backgroundColor: p.bar,
                    hoverBackgroundColor: p.barHover,
                    borderRadius: 3,
                    barThickness: isMobile ? 18 : 40,
                    categoryPercentage: 0.7
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, animation: animBase, plugins: { legend: { display: false }, tooltip: { padding: 10, cornerRadius: 8, callbacks: { label: function(c){ return ' Rp ' + Number(c.raw).toLocaleString('id-ID'); } } } }, scales: gajiScales }
        });
    })();
    </script>

</x-layouts.dashboard>

