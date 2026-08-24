<x-layouts.dashboard
    :title="'Kelola Ritase'"
    :pageTitle="'Kelola Ritase'"
    >

    {{-- HEADER --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold" style="color:var(--text)">Kelola Data Ritase</h1>
                <p class="text-sm mt-1" style="color:var(--text-muted)">Input dan kelola ritase dump-truck dengan aturan sewa DT otomatis.</p>
            </div>
            <div class="relative" id="ritFilterWrap">
                <button onclick="toggleRitFilter()" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium bg-white hover:bg-gray-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                    @if($tanggal || $filterPeriode)
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    @endif
                    
                </button>
                <div class="hidden absolute right-0 mt-2 w-72 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4" id="ritFilterPanel">
                    <form method="GET" action="{{ route('ritase.index') }}" id="filterForm" class="space-y-3">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode</label>
                            <select name="periode" id="filterPeriode" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                <option value="">Semua Periode</option>
                                @foreach($periodes as $periode)
                                    <option value="{{ $periode->id }}" {{ $filterPeriode == $periode->id ? 'selected' : '' }}>{{ $periode->nama_periode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</label>
                            <input type="date" name="tanggal" id="filterTanggal" value="{{ $tanggal }}" onchange="onTanggalChange(this)" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                        </div>
                        @if($tanggal || $filterPeriode)
                            <a href="{{ route('ritase.index') }}" class="block text-center px-3 py-2 border border-gray-200 rounded text-sm text-gray-600 hover:bg-gray-50">Reset</a>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ALERTS --}}
    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- FORM TAMBAH RITASE --}}
    {{-- ============================================================ --}}
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="text-xs font-semibold uppercase" style="color:var(--text-muted)">
                Tambah Ritase Baru
                <span class="text-xs ml-2" style="color:var(--text-dims);font-weight:400">Sistem akan otomatis mengecek aturan sewa DT</span>
            </h3>
        </div>
        <div class="card-body">
            <form id="formTambahRitase" class="space-y-4">
                @csrf

                {{-- Row 1: Periode, Sopir, Tujuan --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">Periode <span class="text-red-500">*</span></label>
                        <select id="periode_id" name="periode_id" required
                            class="form-input">
                            <option value="">-- Pilih Periode --</option>
                            @foreach($periodes as $periode)
                                <option value="{{ $periode->id }}">{{ $periode->nama_periode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Sopir <span class="text-red-500">*</span></label>
                        <select id="kode_sopir" name="kode_sopir" required
                            class="form-input">
                            <option value="">-- Pilih Sopir --</option>
                            @foreach($sopirs as $sopir)
                                <option value="{{ $sopir->kode_sopir }}">{{ $sopir->nama }} ({{ $sopir->kode_sopir }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Tujuan <span class="text-red-500">*</span></label>
                        <select id="kode_tujuan" name="kode_tujuan" required
                            class="form-input">
                            <option value="">-- Pilih Tujuan --</option>
                            @foreach(\App\Models\Tujuan::orderBy('id', 'asc')->get() as $tujuan)
                                <option value="{{ $tujuan->kode_tujuan }}">{{ $tujuan->nama }} ({{ $tujuan->kode_tujuan }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Row 2: Tanggal, Waktu, Kabupaten --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" id="tanggal" name="tanggal" required
                            class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Waktu <span class="text-red-500">*</span></label>
                        <select id="waktu" name="waktu" required
                            class="form-input">
                            <option value="">-- Pilih Waktu --</option>
                            <option value="pagi">Pagi</option>
                            <option value="malam">Malam</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Kabupaten <span class="text-red-500">*</span></label>
                        <select id="kabupaten" name="kabupaten" required
                            class="form-input">
                            <option value="">-- Pilih Kabupaten --</option>
                            <option value="Nganjuk">Nganjuk</option>
                            <option value="Kediri">Kediri</option>
                            <option value="Kota Kediri">Kota Kediri</option>
                            <option value="Jombang">Jombang</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>

                {{-- Row 3: Status, DT --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Status <span class="text-red-500">*</span></label>
                        <select id="status" name="status" required onchange="toggleKompensasiField()"
                            class="form-input">
                            <option value="pending">Pending</option>
                            <option value="valid">Valid</option>
                            <option value="gagal_produksi">Gagal Produksi</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">DT (Sewa Dump Truck)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                            <input type="number" id="dt" name="dt" min="0" readonly
                                class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm bg-gray-100 text-gray-600 cursor-not-allowed"
                                value="0">
                        </div>
                        <p class="text-xs text-gray-800 mt-1">*DT akan dihitung otomatis berdasarkan aturan</p>
                    </div>
                </div>

                {{-- Row 4: Kompensasi & Catatan --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div id="kompensasi_container" class="hidden">
                        <label class="form-label">
                            Nominal Kompensasi
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                            <input type="number" id="nominal_kompensasi" name="nominal_kompensasi" min="0"
                                class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white"
                                placeholder="0">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden" id="error_kompensasi">Nominal harus angka positif.</p>
                    </div>
                    <div>
                        <label class="form-label">Catatan</label>
                        <input type="text" id="catatan" name="catatan"
                            class="form-input"
                            placeholder="Catatan tambahan (opsional)">
                        <p class="text-red-500 text-xs mt-1 hidden" id="error_catatan">Catatan hanya boleh huruf, angka, spasi, dan strip.</p>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 mt-5">
                            <input type="checkbox" id="is_lembur" name="is_lembur" value="1" onchange="toggleLemburField()" class="w-4 h-4">
                            <span class="text-sm font-medium text-gray-700">Lembur</span>
                        </label>
                        <div id="upah_lembur_container" class="hidden mt-2">
                            <label class="form-label">Upah Lembur</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                                <input type="number" id="upah_lembur" name="upah_lembur" min="0"
                                    class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white"
                                    placeholder="0" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Preview Aturan Sewa DT --}}
                <div id="previewAturan" class="hidden border border-gray-200 rounded p-4 bg-gray-50">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800 text-sm mb-1">Preview Aturan Sewa DT</h4>
                        <p class="text-sm text-gray-600" id="previewKeterangan">-</p>
                        <div class="mt-2 flex flex-wrap items-center gap-4">
                            <span class="text-sm text-gray-600">Rit ke-<span id="previewRitKe" class="font-semibold text-gray-900">-</span></span>
                            <span class="text-sm text-gray-600">Sewa DT: <span id="previewSewaDT" class="font-semibold text-gray-900">-</span></span>
                            <span id="previewKompensasiContainer" class="hidden text-sm text-gray-800">Kompensasi: Rp <span id="previewKompensasi">0</span></span>
                        </div>
                    </div>
                </div>

                {{-- Tombol Submit --}}
                <div class="flex justify-end pt-2 gap-2">
                    <a href="{{ route('ritase.parser') }}" class="btn btn-secondary">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Tambah Ritase Otomatis
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Tambah Ritase
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- STAT CARDS --}}
    {{-- ============================================================ --}}
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

    @push('scripts')
    <script>
    function toggleRitFilter(){const p=document.getElementById('ritFilterPanel'),c=document.getElementById('ritChevron');p.classList.toggle('hidden');c.style.transform=p.classList.contains('hidden')?'':'rotate(180deg)';}
    document.addEventListener('click',function(e){const w=document.getElementById('ritFilterWrap');if(w&&!w.contains(e.target)){document.getElementById('ritFilterPanel').classList.add('hidden');document.getElementById('ritChevron').style.transform='';}});
    // Period data for date-based period auto-detection
    const periodData = [
        @foreach($periodes as $periode)
            { id: {{ $periode->id }}, mulai: '{{ $periode->tanggal_mulai->format('Y-m-d') }}', selesai: '{{ $periode->tanggal_selesai->format('Y-m-d') }}' },
        @endforeach
    ];

    function onTanggalChange(input) {
        const dateVal = input.value;
        if (!dateVal) {
            input.form.submit();
            return;
        }
        // Find period that contains this date
        const match = periodData.find(p => dateVal >= p.mulai && dateVal <= p.selesai);
        if (match) {
            document.getElementById('filterPeriode').value = match.id;
        }
        input.form.submit();
    }
    </script>
    @endpush

    {{-- ============================================================ --}}
    {{-- TABEL DATA RITASE --}}
    {{-- ============================================================ --}}
    <div class="card mb-6">
        {{-- TAB NAVIGATION --}}
        <div class="border-b border-gray-200">
            <nav class="flex gap-0 px-5" role="tablist">
                <button type="button" class="tab-btn" data-tab="1" onclick="switchTab(1)">
                    Kelola Ritase
                </button>
                <button type="button" class="tab-btn" data-tab="2" onclick="switchTab(2)">
                    Detail Ritase
                </button>
            </nav>
        </div>

        {{-- ===== TAB 1: DATA RITASE ===== --}}
        <div id="tab-content-1" class="tab-panel">
            <div class="border-b border-gray-200 px-5 py-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Daftar Ritase</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Menampilkan {{ $ritases->firstItem() ?? 0 }} - {{ $ritases->lastItem() ?? 0 }} dari {{ $ritases->total() }} data</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <form method="GET" action="{{ route('ritase.index') }}" class="flex gap-2">
                            @if($tanggal)
                                <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                            @endif
                            <select name="periode" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded text-sm bg-white">
                                <option value="">Semua Periode</option>
                                @foreach($periodes as $periode)
                                    <option value="{{ $periode->id }}" {{ $filterPeriode == $periode->id ? 'selected' : '' }}>{{ $periode->nama_periode }}</option>
                                @endforeach
                            </select>
                            <select name="sopir" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded text-sm bg-white">
                                <option value="">Semua Sopir</option>
                                @foreach($sopirs as $sopir)
                                    <option value="{{ $sopir->kode_sopir }}" {{ $filterSopir == $sopir->kode_sopir ? 'selected' : '' }}>{{ $sopir->nama }}</option>
                                @endforeach
                            </select>
                            <select name="tujuan" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded text-sm bg-white">
                                <option value="">Semua Tujuan</option>
                                @foreach($tujuans as $tujuan)
                                    <option value="{{ $tujuan->kode_tujuan }}" {{ ($filterTujuan ?? '') == $tujuan->kode_tujuan ? 'selected' : '' }}>{{ $tujuan->nama }}</option>
                                @endforeach
                            </select>
                        </form>
                        <div class="relative w-full sm:w-64">
                            <input type="text" id="liveSearch" value="{{ $search }}"
                                class="w-full pl-10 pr-10 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white"
                                placeholder="Cari kode, sopir, tujuan..." autocomplete="off">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-dims)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-200 rounded-full">
                                <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

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
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                                            {{ $ritase->kode_ritase }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                                <span class="text-gray-700 font-bold text-xs">
                                                    {{ $ritase->sopir ? substr($ritase->sopir->nama, 0, 1) : '?' }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">
                                                    {{ $ritase->sopir ? $ritase->sopir->nama : 'Sopir tidak ditemukan' }}
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    {{ $ritase->sopir ? $ritase->sopir->kode_sopir : '-' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $ritase->tujuan ? $ritase->tujuan->nama : 'Tujuan tidak ditemukan' }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $ritase->tujuan ? $ritase->tujuan->kode_tujuan : '-' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $ritase->tanggal->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full {{ $ritase->waktu == 'pagi' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' }} text-xs font-semibold">
                                            {{ ucfirst($ritase->waktu) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $ritase->kabupaten }}</td>
                                    <td class="px-4 py-3">
                                        @if($ritase->status == 'valid')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Valid</span>
                                        @elseif($ritase->status == 'pending')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-orange-100 text-orange-700 text-xs font-semibold">Pending</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">Gagal</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                        Rp {{ number_format($ritase->dt ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if($ritase->status == 'gagal_produksi' && $ritase->nominal_kompensasi > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs font-semibold">
                                                Rp {{ number_format($ritase->nominal_kompensasi, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center space-x-1">
                                            @if($ritase->is_lembur)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">Lembur</span>
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center space-x-1">
                                            <button onclick='openEditModal(@json($ritase))'
                                                class="text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium">Edit</button>
                                            <form action="{{ route('ritase.destroy', $ritase->id) }}"
                                                  method="POST"
                                                  class="inline"
                                                  id="deleteRitase_{{ $ritase->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    onclick="confirmDeleteRitase({{ $ritase->id }}, '{{ $ritase->kode_ritase }}')"
                                                    class="text-xs text-red-600 border border-red-200 px-2.5 py-1.5 rounded hover:bg-red-50 font-medium">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-16">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <p class="text-gray-500 font-semibold">Belum ada data ritase</p>
                        <p class="text-gray-400 text-sm mt-1">Tambahkan ritase pertama Anda menggunakan form di atas.</p>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($ritases->hasPages())
                <div class="border-t border-gray-200 px-5 py-3 bg-gray-50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-gray-600">Halaman {{ $ritases->currentPage() }} dari {{ $ritases->lastPage() }}</p>
                        <div class="flex items-center space-x-1.5">
                            @if($ritases->onFirstPage())
                                <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Sebelumnya</span>
                            @else
                                <a href="{{ $ritases->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Sebelumnya</a>
                            @endif
                            @php
                                $window = 2;
                                $current = $ritases->currentPage();
                                $last = $ritases->lastPage();
                                $start = max(1, $current - $window);
                                $end = min($last, $current + $window);
                            @endphp

                            @if($start > 1)
                                <a href="{{ $ritases->url(1) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">1</a>
                                @if($start > 2)
                                    <span class="px-3 py-1.5 text-sm text-gray-400">...</span>
                                @endif
                            @endif

                            @for($page = $start; $page <= $end; $page++)
                                @if($page == $current)
                                    <span class="px-3 py-1.5 text-sm font-bold text-white bg-[#2d6a4f] border border-[#2d6a4f] rounded">{{ $page }}</span>
                                @else
                                    <a href="{{ $ritases->url($page) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">{{ $page }}</a>
                                @endif
                            @endfor

                            @if($end < $last)
                                @if($end < $last - 1)
                                    <span class="px-3 py-1.5 text-sm text-gray-400">...</span>
                                @endif
                                <a href="{{ $ritases->url($last) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">{{ $last }}</a>
                            @endif
                            @if($ritases->hasMorePages())
                                <a href="{{ $ritases->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Selanjutnya</a>
                            @else
                                <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Selanjutnya</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ===== TAB 2: DETAIL RITASE (PIVOT) ===== --}}
        <div id="tab-content-2" class="tab-panel hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Detail Ritase per Sopir</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Tujuan ritase setiap sopir berdasarkan tanggal</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <select id="detailPeriode" onchange="loadDetailData()" class="px-3 py-2 border border-gray-200 rounded text-sm bg-white">
                            <option value="">-- Pilih Periode --</option>
                            @foreach($periodes as $periode)
                                <option value="{{ $periode->id }}" {{ $filterPeriode == $periode->id ? 'selected' : '' }}>{{ $periode->nama_periode }}</option>
                            @endforeach
                        </select>
                        <div class="relative w-full sm:w-64">
                            <input type="text" id="detailSearch"
                                class="w-full pl-10 pr-10 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white"
                                placeholder="Cari nama sopir atau tujuan..." autocomplete="off">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-dims)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto" id="detailContainer">
                <div class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <p class="text-gray-500 font-semibold">Pilih periode untuk menampilkan detail ritase</p>
                    <p class="text-gray-400 text-sm mt-1">Gunakan filter periode di atas untuk melihat data.</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .tab-btn {
            padding: 0.75rem 1.25rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #8a8698;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .tab-btn:hover {
            color: #2d6a4f;
        }
        .tab-btn.active {
            color: #2d6a4f;
            border-bottom-color: #2d6a4f;
            font-weight: 600;
        }
        .tab-panel {
            display: none;
        }
        .tab-panel.active {
            display: block;
        }
        .detail-table td, .detail-table th {
            border: 1px solid #e2e8f0;
        }
        .detail-table td:first-child {
            position: sticky;
            left: 0;
            background: white;
            z-index: 2;
        }
        .detail-table th {
            position: sticky;
            top: 0;
            z-index: 3;
        }
        .detail-table th:first-child {
            z-index: 4;
        }
        .detail-table tbody tr:hover td:first-child {
            background: #f9fafb;
        }
    </style>

    <script>
        // ===== HAPUS RITASE =====
        function confirmDeleteRitase(id, kode) {
            showConfirmModal({
                title: 'Hapus Data Ritase?',
                message: 'Anda yakin ingin menghapus ritase ' + kode + '? Tindakan ini tidak dapat dibatalkan.',
                type: 'danger',
                confirmText: 'Ya, Hapus',
                onConfirm: function() {
                    document.getElementById('deleteRitase_' + id).submit();
                }
            });
        }

        var activeTab = 1;

        function switchTab(tab) {
            activeTab = tab;
            document.querySelectorAll('.tab-btn').forEach(function(b) {
                b.classList.remove('active');
            });
            document.querySelectorAll('.tab-panel').forEach(function(p) {
                p.classList.add('hidden');
                p.classList.remove('active');
            });
            document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.add('active');
            var panel = document.getElementById('tab-content-' + tab);
            panel.classList.remove('hidden');
            panel.classList.add('active');

            if (tab === 2) {
                loadDetailData();
            }
        }

        var detailCurrentPage = 1;
        var detailDebounceTimer = null;

        function loadDetailPage(page) {
            if (page) detailCurrentPage = page;
            loadDetailData();
        }

        function loadDetailData() {
            var periode = document.getElementById('detailPeriode').value;
            var search = document.getElementById('detailSearch').value;
            var container = document.getElementById('detailContainer');

            if (!periode) {
                container.innerHTML = '<div class="text-center py-16"><svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg><p class="text-gray-500 font-semibold">Pilih periode untuk menampilkan detail ritase</p><p class="text-gray-400 text-sm mt-1">Gunakan filter periode di atas untuk melihat data.</p></div>';
                return;
            }

            container.innerHTML = '<div class="flex items-center justify-center py-16"><svg class="animate-spin h-8 w-8 text-gray-400" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg><p class="text-sm text-gray-500 ml-3">Memuat data...</p></div>';

            fetch('/ritase/detail-data?periode=' + encodeURIComponent(periode) + '&search=' + encodeURIComponent(search) + '&page=' + detailCurrentPage)
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    if (!json.sopirs || json.sopirs.length === 0) {
                        if (json.pagination && json.pagination.total > 0) {
                            detailCurrentPage = Math.max(1, json.pagination.page - 1);
                            if (detailCurrentPage >= 1) { loadDetailData(); return; }
                        }
                        container.innerHTML = '<div class="text-center py-16"><svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg><p class="text-gray-500 font-semibold">Tidak ada data untuk periode ini</p><p class="text-gray-400 text-sm mt-1">Belum ada ritase tercatat pada periode yang dipilih.</p></div>';
                        return;
                    }

                    var pag = json.pagination || { page: 1, last_page: 1, total: 0 };
                    var numCols = json.columns.length;
                    var totalDays = numCols / 2;
                    var cellClass = totalDays > 5
                        ? 'px-1.5 py-2 text-sm leading-tight'
                        : 'px-3 py-2.5 text-sm';
                    var sopirWidth = totalDays > 5 ? 'min-width:130px' : 'min-width:160px';
                    var colWidth = totalDays > 5 ? 'min-width:56px' : 'min-width:68px';

                    var html = '<div class="table-responsive" style="max-height:75vh;overflow-y:auto"><table class="detail-table" style="border-collapse:collapse;min-width:100%"><thead>';

                    var dayNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

                    // ROW 1: tanggal + nama hari (colspan=2 per hari)
                    html += '<tr>';
                    html += '<th class="px-3 py-2.5 text-left text-sm font-semibold text-gray-500 uppercase tracking-wider" rowspan="2" style="' + sopirWidth + ';border:1px solid #e5e7eb;background:#f9fafb">Nama Sopir</th>';

                    var currentDate = '';
                    json.columns.forEach(function(col) {
                        if (col.date !== currentDate) {
                            currentDate = col.date;
                            var parts = col.date.split('-');
                            var dt = new Date(parseInt(parts[0]), parseInt(parts[1])-1, parseInt(parts[2]));
                            var dayLabel = dayNames[dt.getDay()];
                            var dateLabel = parts[2] + '/' + parts[1];
                            html += '<th class="px-1.5 py-2 text-center text-sm font-semibold text-gray-500 uppercase tracking-wider" colspan="2" style="border:1px solid #e5e7eb;background:#f9fafb">' + dateLabel + '<br><span class="text-xs font-normal text-gray-400">' + dayLabel + '</span></th>';
                        }
                    });
                    // count columns
                    html += '<th class="px-3 py-2.5 text-center text-sm font-semibold text-gray-500 uppercase tracking-wider" rowspan="2" style="min-width:80px;border:1px solid #e5e7eb;background:#f9fafb">Ritase Berhasil</th>';
                    html += '<th class="px-3 py-2.5 text-center text-sm font-semibold text-gray-500 uppercase tracking-wider" rowspan="2" style="min-width:80px;border:1px solid #e5e7eb;background:#f9fafb">Ritase Gagal</th>';
                    html += '</tr>';

                    // ROW 2: Pagi / Malam
                    html += '<tr>';
                    json.columns.forEach(function(col) {
                        var cls = col.waktu === 'P' ? 'text-amber-600' : 'text-green-600';
                        var label = col.waktu === 'P' ? 'Pagi' : 'Malam';
                        html += '<th class="px-1 py-1.5 text-center text-[10px] font-semibold uppercase tracking-wider ' + cls + '" style="border:1px solid #e5e7eb;background:#f9fafb;' + colWidth + '">' + label + '</th>';
                    });
                    html += '</tr>';

                    html += '</thead><tbody>';

                    var pageBerhasil = 0, pageGagal = 0;
                    var perColumnTotals = {};
                    json.columns.forEach(function(col) { perColumnTotals[col.key] = 0; });

                    json.sopirs.forEach(function(s) {
                        var berhasil = (json.counts && json.counts[s.kode_sopir]) ? json.counts[s.kode_sopir].ritase_berhasil : 0;
                        var gagal = (json.counts && json.counts[s.kode_sopir]) ? json.counts[s.kode_sopir].ritase_gagal : 0;
                        pageBerhasil += berhasil;
                        pageGagal += gagal;
                        json.columns.forEach(function(col) {
                            if (json.data[s.kode_sopir] && json.data[s.kode_sopir][col.key]) {
                                perColumnTotals[col.key] += json.data[s.kode_sopir][col.key].length;
                            }
                        });

                        html += '<tr class="hover:bg-gray-50">';
                        html += '<td class="px-3 py-2.5 text-sm font-semibold text-gray-900" style="border:1px solid #e5e7eb">' + escapeHtml(s.nama) + '</td>';
                        json.columns.forEach(function(col) {
                            var cell = '';
                            if (json.data[s.kode_sopir] && json.data[s.kode_sopir][col.key]) {
                                var items = json.data[s.kode_sopir][col.key];
                                cell = items.join('<br>');
                            }
                            html += '<td class="text-center align-middle ' + cellClass + ' ' + (col.waktu === 'P' ? 'bg-amber-50/30' : 'bg-green-50/30') + ' text-gray-700" style="border:1px solid #e5e7eb;font-weight:500">';
                            html += cell || '<span class="text-gray-300">-</span>';
                            html += '</td>';
                        });
                        // count columns
                        html += '<td class="text-center align-middle px-3 py-2.5 text-sm font-bold text-green-700" style="border:1px solid #e5e7eb">' + berhasil + '</td>';
                        html += '<td class="text-center align-middle px-3 py-2.5 text-sm font-bold text-red-600" style="border:1px solid #e5e7eb">' + gagal + '</td>';
                        html += '</tr>';
                    });

                    // Subtotal row (with per-day totals)
                    html += '<tr class="bg-amber-50">';
                    html += '<td class="px-3 py-2.5 text-sm font-bold text-gray-700" style="border:1px solid #e5e7eb">Subtotal Halaman</td>';
                    json.columns.forEach(function(col) {
                        var val = perColumnTotals[col.key] || 0;
                        html += '<td class="text-center align-middle px-1 py-2.5 text-sm font-bold text-gray-900" style="border:1px solid #e5e7eb">' + val + '</td>';
                    });
                    html += '<td class="text-center align-middle px-3 py-2.5 text-sm font-bold text-gray-900" style="border:1px solid #e5e7eb">' + pageBerhasil + '</td>';
                    html += '<td class="text-center align-middle px-3 py-2.5 text-sm font-bold text-red-600" style="border:1px solid #e5e7eb">' + pageGagal + '</td>';
                    html += '</tr>';

                    html += '</tbody></table></div>';

                    // PDF action buttons + pagination footer
                    html += '<div class="flex items-center justify-between px-4 py-3 border-t border-gray-100">';
                    html += '<div class="flex items-center gap-2">';
                    html += '<button onclick="openPdfModal(' + periode + ')" class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 rounded text-sm bg-white hover:bg-gray-50 font-medium" style="color:var(--text);cursor:pointer" type="button">';
                    html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z"/></svg>';
                    html += 'Lihat PDF';
                    html += '</button>';
                    html += '<a href="/ritase/detail-pdf?periode=' + periode + '" class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 rounded text-sm bg-white hover:bg-gray-50 font-medium" style="color:var(--text)">';
                    html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
                    html += 'Download PDF';
                    html += '</a>';
                    html += '</div>';
                    if (pag.last_page > 1) {
                        html += '<div class="flex items-center gap-3">';
                        html += '<span class="text-sm text-gray-600">Halaman ' + pag.page + ' dari ' + pag.last_page + ' (' + pag.total + ' sopir)</span>';
                        html += '<div class="flex items-center space-x-1.5">';

                        if (pag.page <= 1) {
                            html += '<span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Sebelumnya</span>';
                        } else {
                            html += '<a href="#" onclick="loadDetailPage(' + (pag.page - 1) + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Sebelumnya</a>';
                        }

                        var w = 2;
                        var ss = Math.max(1, pag.page - w);
                        var ee = Math.min(pag.last_page, pag.page + w);

                        if (ss > 1) {
                            html += '<a href="#" onclick="loadDetailPage(1); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">1</a>';
                            if (ss > 2) html += '<span class="px-3 py-1.5 text-sm text-gray-400">...</span>';
                        }

                        for (var p = ss; p <= ee; p++) {
                            if (p == pag.page) {
                                html += '<span class="px-3 py-1.5 text-sm font-bold text-white bg-[#2d6a4f] border border-[#2d6a4f] rounded">' + p + '</span>';
                            } else {
                                html += '<a href="#" onclick="loadDetailPage(' + p + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">' + p + '</a>';
                            }
                        }

                        if (ee < pag.last_page) {
                            if (ee < pag.last_page - 1) html += '<span class="px-3 py-1.5 text-sm text-gray-400">...</span>';
                            html += '<a href="#" onclick="loadDetailPage(' + pag.last_page + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">' + pag.last_page + '</a>';
                        }

                        if (pag.page >= pag.last_page) {
                            html += '<span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Selanjutnya</span>';
                        } else {
                            html += '<a href="#" onclick="loadDetailPage(' + (pag.page + 1) + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Selanjutnya</a>';
                        }

                        html += '</div></div>';
                    }
                    html += '</div>';

                    container.innerHTML = html;
                })
                .catch(function(err) {
                    container.innerHTML = '<div class="text-center py-16"><p class="text-red-500">Gagal memuat data: ' + err.message + '</p></div>';
                });
        }

function escapeHtml(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        // Init: set tab 1 as active by default
        document.addEventListener('DOMContentLoaded', function() {
            switchTab(1);
            // Debounce detail search
            var ds = document.getElementById('detailSearch');
            if (ds) {
                var dTimer;
                ds.addEventListener('input', function() {
                    clearTimeout(dTimer);
                    detailCurrentPage = 1;
                    dTimer = setTimeout(loadDetailData, 400);
                });
            }
        });
    </script>

    {{-- ============================================================ --}}
    {{-- MODAL EDIT RITASE --}}
    {{-- ============================================================ --}}
    <div id="editModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded border border-gray-200 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Data Ritase</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form id="editForm" method="POST" class="space-y-4" action="">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Kode Ritase</label>
                            <input type="text" id="edit_kode_ritase" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm bg-gray-100 text-gray-600 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="form-label">Periode <span class="text-red-500">*</span></label>
                            <select id="edit_periode_id" name="periode_id" required class="form-input">
                                @foreach($periodes as $periode)
                                    <option value="{{ $periode->id }}">{{ $periode->nama_periode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Sopir <span class="text-red-500">*</span></label>
                            <select id="edit_kode_sopir" name="kode_sopir" required class="form-input">
                                @foreach($sopirs as $sopir)
                                    <option value="{{ $sopir->kode_sopir }}">{{ $sopir->nama }} ({{ $sopir->kode_sopir }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Tujuan <span class="text-red-500">*</span></label>
                            <select id="edit_kode_tujuan" name="kode_tujuan" required class="form-input">
                                @foreach(\App\Models\Tujuan::where('status', 'aktif')->orderBy('id', 'asc')->get() as $tujuan)
                                    <option value="{{ $tujuan->kode_tujuan }}">{{ $tujuan->nama }} ({{ $tujuan->kode_tujuan }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
                            <input type="date" id="edit_tanggal" name="tanggal" required class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Waktu <span class="text-red-500">*</span></label>
                            <select id="edit_waktu" name="waktu" required class="form-input">
                                <option value="pagi">Pagi</option>
                                <option value="malam">Malam</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Kabupaten <span class="text-red-500">*</span></label>
                            <select id="edit_kabupaten" name="kabupaten" required class="form-input">
                                <option value="Nganjuk">Nganjuk</option>
                                <option value="Kediri">Kediri</option>
                                <option value="Kota Kediri">Kota Kediri</option>
                                <option value="Jombang">Jombang</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Status <span class="text-red-500">*</span></label>
                            <select id="edit_status" name="status" required onchange="toggleEditKompensasiField()" class="form-input">
                                <option value="pending">Pending</option>
                                <option value="valid">Valid</option>
                                <option value="gagal_produksi">Gagal Produksi</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">DT (Sewa Dump Truck)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                                <input type="number" id="edit_dt" name="dt" min="0" readonly
                                    class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm bg-gray-100 text-gray-600 cursor-not-allowed"
                                    value="0">
                            </div>
                        </div>
                        <div id="edit_kompensasi_container" class="hidden">
                            <label class="form-label">Nominal Kompensasi</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                                <input type="number" id="edit_nominal_kompensasi" name="nominal_kompensasi" min="0"
                                    class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white">
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Catatan</label>
                            <input type="text" id="edit_catatan" name="catatan" class="form-input">
                        </div>
                        <div>
                            <label class="flex items-center gap-2 mt-5">
                                <input type="checkbox" id="edit_is_lembur" name="is_lembur" value="1" onchange="toggleEditLemburField()" class="w-4 h-4">
                                <span class="text-sm font-medium text-gray-700">Lembur</span>
                            </label>
                            <div id="edit_upah_lembur_container" class="hidden mt-2">
                                <label class="form-label">Upah Lembur</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                                    <input type="number" id="edit_upah_lembur" name="upah_lembur" min="0"
                                        class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white"
                                        placeholder="0" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="closeEditModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                        <button type="submit" class="flex-1 bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL KONFIRMASI TAMBAH --}}
    {{-- ============================================================ --}}
    <div id="pdfModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded border border-gray-200 w-full max-w-5xl mx-4" style="height:90vh">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Detail Ritase per Sopir</h3>
                <button onclick="closePdfModal()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <div class="p-2" style="height:calc(100% - 52px)">
                <iframe id="pdfIframe" src="about:blank" style="width:100%;height:100%;border:none"></iframe>
            </div>
        </div>
    </div>

    <div id="tambahModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded shadow-xl w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Tambah Ritase</h3>
                    <button onclick="closeTambahModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="konfirmasiDetail" class="text-sm text-gray-600 mb-4 bg-gray-50 p-4 rounded max-h-60 overflow-y-auto"></div>
                <div class="flex gap-3">
                    <button onclick="closeTambahModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                    <button onclick="submitTambahRitase()" class="flex-1 bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition">Ya, Tambah</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // ===== VALIDASI INPUT =====
        function validasiNama(input) {
            return /^[a-zA-Z0-9\s\-\.]+$/.test(input);
        }

        function validasiCatatan(input) {
            return /^[a-zA-Z0-9\s\-\.]+$/.test(input);
        }

        function validasiNominal(input) {
            return /^\d+$/.test(input) && parseInt(input) >= 0;
        }

        // ===== INITIALIZE TOM SELECT =====
        let tomSopir, tomTujuan, tomEditSopir, tomEditTujuan;
        let formDataTambah = null;

        document.addEventListener('DOMContentLoaded', function() {
            // Form Tambah - Sopir
            if (document.getElementById('kode_sopir')) {
                tomSopir = new TomSelect('#kode_sopir', {
                    create: false,
                    sortField: { field:"text", direction:"asc" },
                    placeholder: 'Ketik nama atau kode sopir...',
                    allowEmptyOption: true,
                    searchField: ['text'],
                });
            }

            // Form Tambah - Tujuan
            if (document.getElementById('kode_tujuan')) {
                tomTujuan = new TomSelect('#kode_tujuan', {
                    create: false,
                    sortField: { field:"text", direction:"asc" },
                    placeholder: 'Ketik nama tujuan...',
                    allowEmptyOption: true,
                    searchField: ['text'],
                });
            }

            // Initialize kompensasi field
            toggleKompensasiField();

            // ===== TAMBAH RITASE DENGAN KONFIRMASI =====
            document.getElementById('formTambahRitase').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = this;
                const formData = new FormData(form);

                // Validasi field wajib
                const requiredFields = ['periode_id', 'kode_sopir', 'kode_tujuan', 'tanggal', 'waktu', 'kabupaten', 'status'];
                let valid = true;
                let errorMessage = '';

                requiredFields.forEach(field => {
                    const value = formData.get(field);
                    if (!value || value === '') {
                        valid = false;
                        errorMessage += 'Field ' + field.replace('_', ' ') + ' wajib diisi!\n';
                    }
                });

                if (!valid) {
                    alert(errorMessage);
                    return;
                }

                // Validasi catatan
                const catatan = formData.get('catatan');
                const errorCatatan = document.getElementById('error_catatan');
                if (catatan && !validasiCatatan(catatan)) {
                    errorCatatan.classList.remove('hidden');
                    document.getElementById('catatan').classList.add('border-red-500');
                    return;
                } else {
                    errorCatatan.classList.add('hidden');
                    document.getElementById('catatan').classList.remove('border-red-500');
                }

                // Validasi nominal kompensasi
                const nominal = formData.get('nominal_kompensasi');
                const errorKompensasi = document.getElementById('error_kompensasi');
                if (nominal && !validasiNominal(nominal)) {
                    errorKompensasi.classList.remove('hidden');
                    document.getElementById('nominal_kompensasi').classList.add('border-red-500');
                    return;
                } else {
                    errorKompensasi.classList.add('hidden');
                    document.getElementById('nominal_kompensasi').classList.remove('border-red-500');
                }

                // Simpan form data untuk submit nanti
                formDataTambah = formData;

                // Tampilkan detail di modal
                const sopir = document.getElementById('kode_sopir');
                const tujuan = document.getElementById('kode_tujuan');
                const periode = document.getElementById('periode_id');
                const status = formData.get('status');
                const nominalValue = parseFloat(formData.get('nominal_kompensasi') || 0);
                const dt = parseFloat(formData.get('dt') || 0);

                let kompensasiHtml = '';
                if (status === 'gagal_produksi') {
                    if (nominalValue > 0) {
                        kompensasiHtml = `
                            <div class="flex justify-between">
                                <span class="text-gray-500">Kompensasi:</span>
                                <span class="font-semibold text-red-600">Rp ${nominalValue.toLocaleString('id-ID')}</span>
                            </div>
                        `;
                    } else {
                        kompensasiHtml = `
                            <div class="flex justify-between">
                                <span class="text-gray-500">Kompensasi:</span>
                                <span class="font-semibold text-gray-600">Belum ditentukan</span>
                            </div>
                        `;
                    }
                }

                document.getElementById('konfirmasiDetail').innerHTML = `
                    <div class="space-y-2">
                        <div class="flex justify-between"><span class="text-gray-500">Periode:</span><span class="font-semibold text-gray-900">${periode.options[periode.selectedIndex].text}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Sopir:</span><span class="font-semibold text-gray-900">${sopir.options[sopir.selectedIndex].text}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Tujuan:</span><span class="font-semibold text-gray-900">${tujuan.options[tujuan.selectedIndex].text}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Tanggal:</span><span class="font-semibold text-gray-900">${formData.get('tanggal')}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Waktu:</span><span class="font-semibold text-gray-900 capitalize">${formData.get('waktu')}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Kabupaten:</span><span class="font-semibold text-gray-900">${formData.get('kabupaten')}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Status:</span><span class="font-semibold text-gray-900 capitalize">${status.replace('_', ' ')}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">DT (Sewa DT):</span><span class="font-semibold text-gray-800">Rp ${dt.toLocaleString('id-ID')}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Lembur:</span><span class="font-semibold text-gray-900">${document.getElementById('is_lembur').checked ? 'Ya' : 'Tidak'}</span></div>
                        ${kompensasiHtml}
                    </div>
                `;

                // Tampilkan modal
                const modal = document.getElementById('tambahModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });

            // ===== AUTO CALCULATE DT =====
            autoCalculateDT();
        });

        // ===== AUTO CALCULATE DT =====
        function autoCalculateDT() {
            const kabupaten = document.getElementById('kabupaten');
            const waktu = document.getElementById('waktu');
            const status = document.getElementById('status');
            const kompensasi = document.getElementById('nominal_kompensasi');
            const dtInput = document.getElementById('dt');
            const kodeSopir = document.getElementById('kode_sopir');
            const tanggal = document.getElementById('tanggal');

            function hitungDT() {
                const kab = kabupaten.value;
                const waktuVal = waktu.value;
                const statusVal = status.value;
                const sopir = kodeSopir.value;
                const tgl = tanggal.value;

                // Default
                let dt = 0;
                let keterangan = '';

                // Jika status Gagal Produksi - DT = 0
                if (statusVal === 'gagal_produksi') {
                    dt = 0;
                    keterangan = 'Gagal Produksi - Tidak dapat DT';
                    dtInput.value = dt;
                    document.getElementById('previewKeterangan').textContent = keterangan;
                    document.getElementById('previewRitKe').textContent = '-';
                    document.getElementById('previewSewaDT').textContent = '0';
                    document.getElementById('previewAturan').classList.remove('hidden');
                    return;
                }

                // Jika sopir dan tanggal dipilih, cek rit lain
                if (sopir && tgl && kab && waktuVal) {
                    // Cek via AJAX ke server
                    fetch('/ritase/cek-aturan', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            kode_sopir: sopir,
                            tanggal: tgl,
                            waktu: waktuVal,
                            kabupaten: kab,
                            status: statusVal,
                            nominal_kompensasi: parseFloat(kompensasi.value) || 0
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        dt = data.sewa_dt || 0;
                        keterangan = data.keterangan || '';

                        dtInput.value = dt;
                        document.getElementById('previewKeterangan').textContent = keterangan;
                        document.getElementById('previewRitKe').textContent = data.rit_keberapa || '-';
                        document.getElementById('previewSewaDT').textContent = dt.toLocaleString('id-ID');

                        // Update preview
                        document.getElementById('previewAturan').classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        dt = 0;
                        dtInput.value = 0;
                    });
                } else {
                    dt = 0;
                    dtInput.value = 0;
                    document.getElementById('previewAturan').classList.add('hidden');
                }
            }

            // Event listeners
            [kabupaten, waktu, status, kompensasi, kodeSopir, tanggal].forEach(el => {
                if (el) {
                    el.addEventListener('change', hitungDT);
                    el.addEventListener('input', hitungDT);
                }
            });

            // Initial hitung
            setTimeout(hitungDT, 100);
        }

        // ===== TOGGLE KOMPENSASI FIELD =====
        function toggleKompensasiField() {
            const status = document.getElementById('status').value;
            const kompContainer = document.getElementById('kompensasi_container');

            if (status === 'gagal_produksi') {
                kompContainer.classList.remove('hidden');
            } else {
                kompContainer.classList.add('hidden');
                document.getElementById('nominal_kompensasi').value = '';
            }
        }

        // ===== TOGGLE LEMBUR FIELD =====
        function toggleLemburField() {
            const cb = document.getElementById('is_lembur');
            const container = document.getElementById('upah_lembur_container');
            if (cb.checked) {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
                document.getElementById('upah_lembur').value = '0';
            }
        }

        function toggleEditLemburField() {
            const cb = document.getElementById('edit_is_lembur');
            const container = document.getElementById('edit_upah_lembur_container');
            if (cb.checked) {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
                document.getElementById('edit_upah_lembur').value = '0';
            }
        }

        // ===== TOGGLE KOMPENSASI FIELD (EDIT) =====
        function toggleEditKompensasiField() {
            const status = document.getElementById('edit_status').value;
            const kompContainer = document.getElementById('edit_kompensasi_container');

            if (status === 'gagal_produksi') {
                kompContainer.classList.remove('hidden');
            } else {
                kompContainer.classList.add('hidden');
                document.getElementById('edit_nominal_kompensasi').value = '';
            }
        }

        // ===== LIVE SEARCH =====
        (function() {
            const searchInput = document.getElementById('liveSearch');
            const clearSearch = document.getElementById('clearSearch');
            let debounceTimer;

            function debounce(func, wait) {
                return function(...args) {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => func.apply(this, args), wait);
                };
            }

            function performSearch() {
                const query = searchInput.value.trim();
                const url = new URL(window.location.href);
                if (query) {
                    url.searchParams.set('search', query);
                    clearSearch.classList.remove('hidden');
                } else {
                    url.searchParams.delete('search');
                    clearSearch.classList.add('hidden');
                }
                window.location.href = url.toString();
            }

            searchInput.addEventListener('input', debounce(performSearch, 500));
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                performSearch();
                searchInput.focus();
            });
            if (searchInput.value) clearSearch.classList.remove('hidden');
        })();

        // ===== EDIT RITASE =====
        function openEditModal(ritase) {
            document.getElementById('editForm').action = '{{ route('ritase.update', ['id' => '__ID__']) }}'.replace('__ID__', ritase.id);
            document.getElementById('edit_kode_ritase').value = ritase.kode_ritase;
            document.getElementById('edit_periode_id').value = ritase.periode_id;
            document.getElementById('edit_tanggal').value = ritase.tanggal;
            document.getElementById('edit_waktu').value = ritase.waktu;
            document.getElementById('edit_kabupaten').value = ritase.kabupaten;
            document.getElementById('edit_status').value = ritase.status;
            document.getElementById('edit_dt').value = ritase.dt || 0;
            document.getElementById('edit_catatan').value = ritase.catatan || '';
            document.getElementById('edit_is_lembur').checked = ritase.is_lembur == 1 || ritase.is_lembur === true;
            document.getElementById('edit_upah_lembur').value = ritase.upah_lembur || 0;
            toggleEditLemburField();

            // Initialize Tom Select untuk edit
            setTimeout(() => {
                if (tomEditSopir) tomEditSopir.destroy();
                if (tomEditTujuan) tomEditTujuan.destroy();

                tomEditSopir = new TomSelect('#edit_kode_sopir', {
                    create: false,
                    sortField: { field:"text", direction:"asc" },
                    placeholder: 'Ketik nama atau kode sopir...',
                    searchField: ['text'],
                });

                tomEditTujuan = new TomSelect('#edit_kode_tujuan', {
                    create: false,
                    sortField: { field:"text", direction:"asc" },
                    placeholder: 'Ketik nama tujuan...',
                    searchField: ['text'],
                });

                tomEditSopir.setValue(ritase.kode_sopir);
                tomEditTujuan.setValue(ritase.kode_tujuan);
            }, 100);

            // Show/hide kompensasi
            const kompContainer = document.getElementById('edit_kompensasi_container');
            const nominalInput = document.getElementById('edit_nominal_kompensasi');

            if (ritase.status === 'gagal_produksi') {
                kompContainer.classList.remove('hidden');
                nominalInput.value = ritase.nominal_kompensasi || '';
            } else {
                kompContainer.classList.add('hidden');
                nominalInput.value = '';
            }

            const modal = document.getElementById('editModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // ===== MODAL KONFIRMASI TAMBAH =====
        function closeTambahModal() {
            const modal = document.getElementById('tambahModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            formDataTambah = null;
        }

        function submitTambahRitase() {
            if (formDataTambah) {
                // Buat form baru untuk submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("ritase.store") }}';

                // Tambahkan CSRF token
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);

                // Tambahkan semua data
                for (let [key, value] of formDataTambah.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                closeTambahModal();
                form.submit();
            }
        }

        // Tutup modal saat klik overlay
        document.querySelectorAll('.fixed.inset-0.bg-black\\/40').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('flex');
                    this.classList.add('hidden');
                    if (this.id === 'tambahModal') {
                        formDataTambah = null;
                    }
                }
            });
        });

        // ===== PDF MODAL =====
        function openPdfModal(periodeId) {
            const modal = document.getElementById('pdfModal');
            const iframe = document.getElementById('pdfIframe');
            iframe.src = '/ritase/detail-pdf?periode=' + periodeId + '&view=1';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closePdfModal() {
            const modal = document.getElementById('pdfModal');
            const iframe = document.getElementById('pdfIframe');
            iframe.src = 'about:blank';
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // Tutup dengan Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
                closeTambahModal();
                closePdfModal();
            }
        });
    </script>
    @endpush

</x-layouts.dashboard>
