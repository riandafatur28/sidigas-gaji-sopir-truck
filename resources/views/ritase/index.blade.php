<x-layouts.dashboard :title="'Kelola Ritase'" :pageTitle="'Kelola Ritase'">

    {{-- HEADER --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold" style="color:var(--text)">Kelola Data Ritase</h1>
                <p class="text-sm mt-1" style="color:var(--text-muted)">Input dan kelola ritase dump-truck dengan aturan sewa DT otomatis.</p>
            </div>
        </div>
    </div>

    {{-- ALERTS --}}
    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-error mb-4">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-error mb-4"><ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    {{-- FORM TAMBAH RITASE --}}
    <x-ritase.form-tambah :periodes="$periodes" :sopirs="$sopirs" />

    {{-- STAT CARDS --}}
    <div id="liveStats">
    <x-ritase.stat-cards :totalRitase="$totalRitase" :ritaseValid="$ritaseValid" :ritasePending="$ritasePending" :ritaseGagal="$ritaseGagal" :sopirTerlibat="$sopirTerlibat" :ritaseLembur="$ritaseLembur" :tanggal="$tanggal" :filterPeriode="$filterPeriode" />
    </div>

    {{-- CARD RITASE --}}
    <div class="card mb-6" data-live-root>
        <div class="border-b border-gray-200">
            <nav class="flex gap-0 px-5" role="tablist">
                <button type="button" class="tab-btn active" data-tab="1" onclick="switchTab(1)"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>Kelola Ritase</button>
                <button type="button" class="tab-btn" data-tab="2" onclick="switchTab(2)"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>Detail Ritase</button>
            </nav>
        </div>

        {{-- TAB 1: DATA RITASE --}}
        <div id="tab-content-1" class="tab-panel active">
            <div class="border-b border-gray-200 px-5 py-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Daftar Ritase</h3>
                    </div>
                    <div class="flex flex-row gap-2 items-center w-full sm:w-auto">
                        <div class="relative flex-1 sm:w-64 sm:flex-none">
                            <input type="text" id="liveSearch" value="{{ $search }}" class="w-full pl-10 pr-10 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white" placeholder="Cari kode, sopir, tujuan...">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-dims)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-200 rounded-full">
                                <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div class="relative shrink-0" id="ritFilterWrap">
                            <button onclick="toggleRitFilter()" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium bg-white hover:bg-gray-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                Filter
                                @if($tanggal || $filterPeriode || $filterSopir || $filterTujuan)<span class="w-2 h-2 rounded-full bg-green-500"></span>@endif
                            </button>
                            <div class="hidden absolute right-0 mt-2 w-72 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4 max-h-96 overflow-y-auto" id="ritFilterPanel">
                                <form method="GET" action="{{ route('ritase.index') }}" class="space-y-3">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode</label>
                                        <select name="periode" id="filterPeriode" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                            <option value="semua" {{ $filterPeriode === 'semua' ? 'selected' : '' }}>Semua Periode</option>
                                            @foreach($periodes as $periode)
                                                <option value="{{ $periode->id }}" {{ $filterPeriode == $periode->id ? 'selected' : '' }}>{{ $periode->nama_periode }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sopir</label>
                                        <select name="sopir" id="filterSopir" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                            <option value="">Semua Sopir</option>
                                            @foreach($sopirs as $sopir)
                                                <option value="{{ $sopir->kode_sopir }}" {{ $filterSopir == $sopir->kode_sopir ? 'selected' : '' }}>{{ $sopir->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tujuan</label>
                                        <select name="tujuan" id="filterTujuan" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                            <option value="">Semua Tujuan</option>
                                            @foreach($tujuans as $tujuan)
                                                <option value="{{ $tujuan->kode_tujuan }}" {{ ($filterTujuan ?? '') == $tujuan->kode_tujuan ? 'selected' : '' }}>{{ $tujuan->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</label>
                                        <input type="date" name="tanggal" id="filterTanggal" value="{{ $tanggal }}" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="liveResults">
                <div class="table-responsive">
                @if($ritases->count() > 0)
                    <table class="w-full">
                        <thead style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sopir</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tujuan</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kabupaten</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">DT</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Kompensasi</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Lembur</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($ritases as $ritase)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs font-medium">{{ $ritase->kode_ritase }}</span></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center"><span class="text-gray-700 font-bold text-xs">{{ $ritase->sopir ? substr($ritase->sopir->nama, 0, 1) : '?' }}</span></div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">{{ $ritase->sopir ? $ritase->sopir->nama : 'Sopir tidak ditemukan' }}</p>
                                                <p class="text-xs text-gray-500">{{ $ritase->sopir ? $ritase->sopir->kode_sopir : '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($ritase->status == 'gagal_produksi')
                                            <p class="text-sm font-semibold text-red-600">Gagal Produksi</p>
                                            <p class="text-xs text-red-400">-</p>
                                        @else
                                            <p class="text-sm font-medium text-gray-900">{{ $ritase->tujuan ? $ritase->tujuan->nama : 'Tujuan tidak ditemukan' }}</p>
                                            <p class="text-xs text-gray-500">{{ $ritase->tujuan ? $ritase->tujuan->kode_tujuan : '-' }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $ritase->tanggal->locale('id')->translatedFormat('d M Y') }}</td>
                                    <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-1 rounded-full {{ $ritase->waktu == 'pagi' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' }} text-xs font-semibold">{{ ucfirst($ritase->waktu) }}</span></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $ritase->kabupaten }}</td>
                                    <td class="px-4 py-3">
                                        @if($ritase->status == 'valid')<span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Valid</span>
                                        @elseif($ritase->status == 'pending')<span class="inline-flex items-center px-2 py-1 rounded-full bg-orange-100 text-orange-700 text-xs font-semibold">Pending</span>
                                        @else<span class="inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">Gagal</span>@endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-800">Rp {{ number_format($ritase->dt ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if($ritase->status == 'gagal_produksi' && $ritase->nominal_kompensasi > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs font-semibold">Rp {{ number_format($ritase->nominal_kompensasi, 0, ',', '.') }}</span>
                                        @else<span class="text-xs text-gray-400">-</span>@endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($ritase->is_lembur)<span class="inline-flex items-center px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">Lembur</span>
                                        @else<span class="text-xs text-gray-400">-</span>@endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center space-x-1">
                                            <button onclick='openEditModal(@json($ritase))' class="inline-flex items-center gap-1 text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Edit</button>
                                            <form action="{{ route('ritase.destroy', $ritase->id) }}" method="POST" class="inline" id="deleteRitase_{{ $ritase->id }}">
                                                @csrf @method('DELETE')
                                                <button type="button" onclick="confirmDeleteRitase({{ $ritase->id }}, '{{ $ritase->kode_ritase }}')" class="inline-flex items-center gap-1 text-xs text-red-600 border border-red-200 px-2.5 py-1.5 rounded hover:bg-red-50 font-medium"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-16">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        <p class="text-gray-500 font-semibold">Belum ada data ritase</p>
                        <p class="text-gray-400 text-sm mt-1">Tambahkan ritase pertama Anda menggunakan form di atas.</p>
                    </div>
                @endif
            </div>
            <p class="text-xs text-gray-400 px-5 pt-3">Menampilkan {{ $ritases->firstItem() ?? 0 }} - {{ $ritases->lastItem() ?? 0 }} dari {{ $ritases->total() }} data</p>
            <x-shared.pagination :paginator="$ritases" />
            </div>
        </div>

        {{-- TAB 2: DETAIL RITASE --}}
        <div id="tab-content-2" class="tab-panel hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Detail Ritase per Sopir</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Tujuan ritase setiap sopir berdasarkan tanggal</p>
                    </div>
                    <div class="flex flex-row gap-2 items-center w-full sm:w-auto">
                        <div class="relative flex-1 sm:w-64 sm:flex-none">
                            <input type="text" id="detailSearch" class="w-full pl-10 pr-10 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white" placeholder="Cari nama sopir atau tujuan..." autocomplete="off">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-dims)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <div class="relative shrink-0" id="detailFilterWrap">
                            <button onclick="toggleDetailFilter()" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium bg-white hover:bg-gray-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                Filter
                                @if($filterPeriode)<span class="w-2 h-2 rounded-full bg-green-500"></span>@endif
                            </button>
                            <div class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4" id="detailFilterPanel">
                                <div class="space-y-3">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode</label>
                                        <select id="detailPeriode" onchange="loadDetailData()" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                            <option value="">-- Pilih Periode --</option>
                                            @foreach($periodes as $periode)<option value="{{ $periode->id }}" {{ $filterPeriode == $periode->id ? 'selected' : '' }}>{{ $periode->nama_periode }}</option>@endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto" id="detailContainer">
                <div class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <p class="text-gray-500 font-semibold">Pilih periode untuk menampilkan detail ritase</p>
                    <p class="text-gray-400 text-sm mt-1">Gunakan filter periode di atas untuk melihat data.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER TOGGLE SCRIPT --}}
    <script>
    function toggleRitFilter(){const p=document.getElementById('ritFilterPanel');p.classList.toggle('hidden');}
    document.addEventListener('click',function(e){const w=document.getElementById('ritFilterWrap');if(w&&!w.contains(e.target)){document.getElementById('ritFilterPanel').classList.add('hidden');}});
    function toggleDetailFilter(){document.getElementById('detailFilterPanel').classList.toggle('hidden');}
    document.addEventListener('click',function(e){const w=document.getElementById('detailFilterWrap');if(w&&!w.contains(e.target)){document.getElementById('detailFilterPanel').classList.add('hidden');}});
    function updateDetailDot(){
        var det=document.getElementById('detailPeriode');
        var btn=document.querySelector('#detailFilterWrap button');
        if(!btn) return;
        var dot=btn.querySelector('span.rounded-full');
        var has=det&&det.value!=='';
        if(has&&!dot){var s=document.createElement('span');s.className='w-2 h-2 rounded-full bg-green-500';btn.appendChild(s);}
        if(!has&&dot) dot.remove();
    }
    function isDetailTabActive(){
        if(typeof window.activeTab!=='undefined') return window.activeTab===2;
        var p=document.getElementById('tab-content-2');
        return p&&p.classList.contains('active');
    }
    function syncDetailPeriodeFromMain(){
        var main=document.getElementById('filterPeriode'),det=document.getElementById('detailPeriode');
        if(!main||!det||det.value===main.value) return;
        det.value=main.value;
        updateDetailDot();
        if(isDetailTabActive()&&typeof loadDetailData==='function'){if(typeof detailCurrentPage!=='undefined')detailCurrentPage=1;loadDetailData();}
    }
    function syncMainPeriodeFromDetail(){
        var main=document.getElementById('filterPeriode'),det=document.getElementById('detailPeriode');
        if(!main||!det||main.value===det.value) return;
        main.value=det.value;
        main.dispatchEvent(new Event('change',{bubbles:true}));
    }
    (function(){
        var main=document.getElementById('filterPeriode'),det=document.getElementById('detailPeriode');
        if(main)main.addEventListener('change',syncDetailPeriodeFromMain);
        if(det)det.addEventListener('change',function(){updateDetailDot();syncMainPeriodeFromDetail();});
    })();
    function autoKabupatenTujuan(sel,targetId){
        var target=document.getElementById(targetId);
        if(!sel||!sel.value||!target)return;
        fetch('{{ route("ritase.tebak-kabupaten") }}?kode_tujuan='+encodeURIComponent(sel.value),{headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(function(r){return r.json();})
            .then(function(j){
                if(!j||!j.kabupaten)return;
                for(var i=0;i<target.options.length;i++){if(target.options[i].value===j.kabupaten){target.value=j.kabupaten;break;}}
            })
            .catch(function(){});
    }
    </script>

    <style>
        .tab-btn { display: inline-flex; align-items: center; gap: 6px; padding: 0.75rem 1.25rem; font-size: 0.8125rem; font-weight: 500; color: #8a8698; background: none; border: none; border-bottom: 2px solid transparent; cursor: pointer; transition: all 0.15s ease; }
        .tab-btn svg { width: 15px; height: 15px; flex-shrink: 0; }
        .tab-btn:hover { color: #2d6a4f; }
        .tab-btn.active { color: #2d6a4f; border-bottom-color: #2d6a4f; font-weight: 600; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block !important; }
        .detail-table td, .detail-table th { border: 1px solid #e2e8f0; }
        .detail-table td:first-child { position: sticky; left: 0; background: white; z-index: 2; }
        .detail-table th { position: sticky; top: 0; z-index: 3; }
        .detail-table th:first-child { z-index: 4; }
        .detail-table tbody tr:hover td:first-child { background: #f9fafb; }
    </style>

    {{-- Pass data to JS --}}
    <script>
        window.ritasePeriodData = [
            @foreach($periodes as $periode)
                { id: {{ $periode->id }}, mulai: '{{ $periode->tanggal_mulai->format('Y-m-d') }}', selesai: '{{ $periode->tanggal_selesai->format('Y-m-d') }}' },
            @endforeach
        ];
        window.ritaseStoreUrl = '{{ route("ritase.store") }}';
        window.ritaseUpdateUrl = '{{ route("ritase.update", ["id" => "__ID__"]) }}';
    </script>

    {{-- MODALS --}}
    <x-ritase.modal-edit :periodes="$periodes" :sopirs="$sopirs" />
    <x-ritase.modal-tambah />
    <x-ritase.modal-pdf />

    @push('scripts')
    <script src="{{ asset('js/ritase.js') }}"></script>
    <script src="{{ asset('js/live-search.js') }}"></script>
    <script>
    (function(){
        var stats = document.getElementById('liveStats');
        var searchInput = document.getElementById('liveSearch');
        if (!stats) return;
        var timer = null;
        function collectParams(){
            var params = new URLSearchParams();
            var root = document.querySelector('[data-live-root]');
            if (root) root.querySelectorAll('form[method="GET"] [name]').forEach(function(el){
                if (el.value !== '') params.append(el.name, el.value);
            });
            if (searchInput && !searchInput.name) {
                var q = searchInput.value.trim();
                if (q !== '') params.set('search', q);
            }
            return params;
        }
        function refreshStats(){
            clearTimeout(timer);
            timer = setTimeout(function(){
                var url = new URL(window.location.href);
                url.search = collectParams().toString();
                url.searchParams.delete('page');
                fetch(url.toString(), {headers:{'X-Requested-With':'XMLHttpRequest'}})
                    .then(function(res){ return res.text(); })
                    .then(function(html){
                        var doc = new DOMParser().parseFromString(html, 'text/html');
                        var fresh = doc.getElementById('liveStats');
                        if (fresh) stats.innerHTML = fresh.innerHTML;
                    })
                    .catch(function(){});
            }, 400);
        }
        if (searchInput) searchInput.addEventListener('input', refreshStats);
        var rootEl = document.querySelector('[data-live-root]');
        if (rootEl) rootEl.querySelectorAll('form[method="GET"] select[name], form[method="GET"] input[type="date"][name]').forEach(function(el){
            el.addEventListener('change', refreshStats);
        });
        var clearBtn = document.getElementById('clearSearch');
        if (clearBtn) clearBtn.addEventListener('click', refreshStats);
    })();
    </script>
    @endpush

</x-layouts.dashboard>


