<x-layouts.dashboard
    :title="'Kelola Ritase'"
    :pageTitle="'Kelola Ritase'"
    :user="auth()->user()">

    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-pupr-blue mb-2">Kelola Data Ritase 🚛</h2>
        <p class="text-gray-600">Input dan kelola ritase dump-truck dengan aturan sewa DT otomatis.</p>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-xl flex items-start space-x-3">
            <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="text-green-800 font-semibold">Berhasil!</p>
                <p class="text-green-700 text-sm">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl flex items-start space-x-3">
            <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <p class="text-red-800 font-semibold">Error!</p>
                <p class="text-red-700 text-sm">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-md p-5 border-l-4 border-pupr-blue">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold">Total Ritase</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalRitase }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-pupr-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-5 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold">Ritase Valid</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $ritaseValid }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-5 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold">Pending</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1">{{ $ritasePending }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-5 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold">Gagal Produksi</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ $ritaseGagal }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Tambah Ritase -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border-t-4 border-pupr-yellow">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-pupr-blue">Tambah Ritase Baru</h3>
                <p class="text-sm text-gray-500">Sistem akan otomatis mengecek aturan sewa DT</p>
            </div>
            <div class="hidden sm:flex items-center space-x-2 bg-pupr-yellow/20 px-4 py-2 rounded-xl">
                <svg class="w-5 h-5 text-pupr-yellow-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-xs font-semibold text-pupr-yellow-dark">Auto Check Rules</span>
            </div>
        </div>

        <form id="formTambahRitase" class="space-y-4">
            @csrf

            <!-- Row 1: Periode, Sopir, Tujuan -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Periode <span class="text-red-500">*</span></label>
                    <select id="periode_id" name="periode_id" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all">
                        <option value="">-- Pilih Periode --</option>
                        @foreach($periodes as $periode)
                            <option value="{{ $periode->id }}">{{ $periode->nama_periode }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sopir <span class="text-red-500">*</span></label>
                    <select id="kode_sopir" name="kode_sopir" required>
                        <option value="">-- Pilih Sopir --</option>
                        @foreach($sopirs as $sopir)
                            <option value="{{ $sopir->kode_sopir }}">{{ $sopir->nama }} ({{ $sopir->kode_sopir }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tujuan <span class="text-red-500">*</span></label>
                    <select id="kode_tujuan" name="kode_tujuan" required>
                        <option value="">-- Pilih Tujuan --</option>
                        @foreach(\App\Models\Tujuan::where('status', 'aktif')->orderBy('id', 'asc')->get() as $tujuan)
                            <option value="{{ $tujuan->kode_tujuan }}">{{ $tujuan->nama }} ({{ $tujuan->kode_tujuan }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Row 2: Tanggal, Waktu, Kabupaten -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" id="tanggal" name="tanggal" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Waktu <span class="text-red-500">*</span></label>
                    <select id="waktu" name="waktu" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all">
                        <option value="">-- Pilih Waktu --</option>
                        <option value="pagi">Pagi</option>
                        <option value="malam">Malam</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Kabupaten <span class="text-red-500">*</span></label>
                    <select id="kabupaten" name="kabupaten" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all">
                        <option value="">-- Pilih Kabupaten --</option>
                        <option value="Nganjuk">Nganjuk</option>
                        <option value="Kediri">Kediri</option>
                        <option value="Kota Kediri">Kota Kediri</option>
                        <option value="Jombang">Jombang</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
            </div>

            <!-- Row 3: Status, DT -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" required onchange="toggleKompensasiField()"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all">
                        <option value="pending">Pending</option>
                        <option value="valid">Valid</option>
                        <option value="gagal_produksi">Gagal Produksi</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">DT (Sewa Dump Truck)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">Rp</span>
                        <input type="number" id="dt" name="dt" min="0" readonly
                            class="w-full pl-12 pr-4 py-3 bg-gray-100 border-2 border-gray-200 rounded-xl text-gray-600 font-semibold cursor-not-allowed"
                            value="0">
                    </div>
                    <p class="text-xs text-blue-600 mt-1 font-semibold">*DT akan dihitung otomatis berdasarkan aturan</p>
                </div>
            </div>

            <!-- Row 4: Kompensasi & Catatan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div id="kompensasi_container" class="hidden">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Nominal Kompensasi
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">Rp</span>
                        <input type="number" id="nominal_kompensasi" name="nominal_kompensasi" min="0"
                            class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all"
                            placeholder="0">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan</label>
                    <input type="text" id="catatan" name="catatan"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all"
                        placeholder="Catatan tambahan (opsional)">
                </div>
            </div>

            <!-- Preview Aturan Sewa DT -->
            <div id="previewAturan" class="hidden bg-gradient-to-r from-pupr-yellow/10 to-pupr-yellow/5 border-2 border-pupr-yellow rounded-xl p-4">
                <div class="flex items-start space-x-3">
                    <svg class="w-6 h-6 text-pupr-yellow-dark flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-bold text-pupr-blue mb-1">Preview Aturan Sewa DT</h4>
                        <p class="text-sm text-gray-700" id="previewKeterangan">-</p>
                        <div class="mt-2 flex flex-wrap items-center gap-4">
                            <span class="text-sm font-semibold text-gray-600">Rit ke-<span id="previewRitKe" class="text-pupr-blue">-</span></span>
                            <span class="text-sm font-semibold text-gray-600">Sewa DT: <span id="previewSewaDT" class="text-pupr-blue">-</span></span>
                            <span id="previewKompensasiContainer" class="hidden text-sm font-semibold text-red-600">Kompensasi: Rp <span id="previewKompensasi">0</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Submit -->
            <div class="flex justify-end pt-4">
                <button type="submit"
                    class="gradient-yellow text-pupr-blue font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl btn-press transition-all flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Tambah Ritase</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Data Ritase -->
    <div class="bg-white rounded-2xl shadow-lg border-t-4 border-pupr-blue overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-pupr-blue">Daftar Ritase</h3>
                    <p class="text-sm text-gray-500">Menampilkan {{ $ritases->firstItem() ?? 0 }} - {{ $ritases->lastItem() ?? 0 }} dari {{ $ritases->total() }} data</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <form method="GET" action="{{ route('ritase.index') }}" class="flex gap-2">
                        <select name="periode" onchange="this.form.submit()" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm">
                            <option value="">Semua Periode</option>
                            @foreach($periodes as $periode)
                                <option value="{{ $periode->id }}" {{ $filterPeriode == $periode->id ? 'selected' : '' }}>{{ $periode->nama_periode }}</option>
                            @endforeach
                        </select>
                        <select name="sopir" onchange="this.form.submit()" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm">
                            <option value="">Semua Sopir</option>
                            @foreach($sopirs as $sopir)
                                <option value="{{ $sopir->kode_sopir }}" {{ $filterSopir == $sopir->kode_sopir ? 'selected' : '' }}>{{ $sopir->nama }}</option>
                            @endforeach
                        </select>
                    </form>

                    <div class="relative w-full sm:w-64">
                        <input type="text" id="liveSearch" value="{{ $search }}"
                            class="w-full pl-10 pr-10 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all text-sm"
                            placeholder="Cari kode, sopir, tujuan..." autocomplete="off">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        <div class="overflow-x-auto">
            @if($ritases->count() > 0)
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Sopir</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tujuan</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kabupaten</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">DT</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Kompensasi</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($ritases as $ritase)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-pupr-yellow/20 text-pupr-yellow-dark font-bold text-xs">
                                        {{ $ritase->kode_ritase }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-8 h-8 gradient-yellow rounded-full flex items-center justify-center">
                                            <span class="text-pupr-blue font-bold text-xs">
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
                                    <span class="inline-flex items-center px-2 py-1 rounded-full {{ $ritase->waktu == 'pagi' ? 'bg-yellow-100 text-yellow-700' : 'bg-indigo-100 text-indigo-700' }} text-xs font-semibold">
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
                                <td class="px-4 py-3 text-right font-semibold text-purple-600">
                                    Rp {{ number_format($ritase->dt ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($ritase->status == 'gagal_produksi' && $ritase->nominal_kompensasi > 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                                            Rp {{ number_format($ritase->nominal_kompensasi, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center space-x-1">
                                        <button onclick='openEditModal(@json($ritase))'
                                            class="p-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-colors btn-press" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>

                                        <form action="{{ route('ritase.destroy', $ritase->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('⚠️ Yakin ingin menghapus ritase {{ $ritase->kode_ritase }}?\n\nData yang dihapus tidak dapat dikembalikan!')"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors btn-press"
                                                title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center py-16">
                    <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <p class="text-gray-500 font-semibold">Belum ada data ritase</p>
                    <p class="text-gray-400 text-sm mt-1">Tambahkan ritase pertama Anda menggunakan form di atas.</p>
                </div>
            @endif
        </div>

        <!-- Pagination -->
        @if($ritases->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-600">Halaman {{ $ritases->currentPage() }} dari {{ $ritases->lastPage() }}</p>
                    <div class="flex items-center space-x-2">
                        @if($ritases->onFirstPage())
                            <span class="px-3 py-2 text-sm text-gray-400 bg-white border border-gray-200 rounded-lg cursor-not-allowed">← Sebelumnya</span>
                        @else
                            <a href="{{ $ritases->previousPageUrl() }}" class="px-3 py-2 text-sm text-pupr-blue bg-white border border-pupr-yellow hover:bg-pupr-yellow rounded-lg transition-colors font-semibold">← Sebelumnya</a>
                        @endif
                        @foreach($ritases->getUrlRange(1, $ritases->lastPage()) as $page => $url)
                            @if($page == $ritases->currentPage())
                                <span class="px-3 py-2 text-sm font-bold text-pupr-blue bg-pupr-yellow border border-pupr-yellow rounded-lg">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-200 hover:bg-pupr-yellow hover:text-pupr-blue hover:border-pupr-yellow rounded-lg transition-colors font-semibold">{{ $page }}</a>
                            @endif
                        @endforeach
                        @if($ritases->hasMorePages())
                            <a href="{{ $ritases->nextPageUrl() }}" class="px-3 py-2 text-sm text-pupr-blue bg-white border border-pupr-yellow hover:bg-pupr-yellow rounded-lg transition-colors font-semibold">Selanjutnya →</a>
                        @else
                            <span class="px-3 py-2 text-sm text-gray-400 bg-white border border-gray-200 rounded-lg cursor-not-allowed">Selanjutnya →</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Edit Ritase -->
    <div id="editModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 overlay">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-pupr-yellow/10 to-pupr-yellow/5 sticky top-0">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-pupr-blue">Edit Data Ritase</h3>
                    <button onclick="closeEditModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <form id="editForm" method="POST" class="p-6 space-y-4" action="">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Kode Ritase</label>
                        <input type="text" id="edit_kode_ritase" disabled class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-xl text-gray-600 font-bold cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Periode <span class="text-red-500">*</span></label>
                        <select id="edit_periode_id" name="periode_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow text-gray-900 outline-none">
                            @foreach($periodes as $periode)
                                <option value="{{ $periode->id }}">{{ $periode->nama_periode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sopir <span class="text-red-500">*</span></label>
                        <select id="edit_kode_sopir" name="kode_sopir" required>
                            @foreach($sopirs as $sopir)
                                <option value="{{ $sopir->kode_sopir }}">{{ $sopir->nama }} ({{ $sopir->kode_sopir }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tujuan <span class="text-red-500">*</span></label>
                        <select id="edit_kode_tujuan" name="kode_tujuan" required>
                            @foreach(\App\Models\Tujuan::where('status', 'aktif')->orderBy('id', 'asc')->get() as $tujuan)
                                <option value="{{ $tujuan->kode_tujuan }}">{{ $tujuan->nama }} ({{ $tujuan->kode_tujuan }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" id="edit_tanggal" name="tanggal" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow text-gray-900 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Waktu <span class="text-red-500">*</span></label>
                        <select id="edit_waktu" name="waktu" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow text-gray-900 outline-none">
                            <option value="pagi">Pagi</option>
                            <option value="malam">Malam</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Kabupaten <span class="text-red-500">*</span></label>
                        <select id="edit_kabupaten" name="kabupaten" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow text-gray-900 outline-none">
                            <option value="Nganjuk">Nganjuk</option>
                            <option value="Kediri">Kediri</option>
                            <option value="Kota Kediri">Kota Kediri</option>
                            <option value="Jombang">Jombang</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                        <select id="edit_status" name="status" required onchange="toggleEditKompensasiField()" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow text-gray-900 outline-none">
                            <option value="pending">Pending</option>
                            <option value="valid">Valid</option>
                            <option value="gagal_produksi">Gagal Produksi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">DT (Sewa Dump Truck)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">Rp</span>
                            <input type="number" id="edit_dt" name="dt" min="0" readonly
                                class="w-full pl-12 pr-4 py-3 bg-gray-100 border-2 border-gray-200 rounded-xl text-gray-600 font-semibold cursor-not-allowed"
                                value="0">
                        </div>
                    </div>
                    <div id="edit_kompensasi_container" class="hidden">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nominal Kompensasi</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">Rp</span>
                            <input type="number" id="edit_nominal_kompensasi" name="nominal_kompensasi" min="0"
                                class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow text-gray-900 outline-none">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan</label>
                        <input type="text" id="edit_catatan" name="catatan" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow text-gray-900 outline-none">
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors btn-press">Batal</button>
                    <button type="submit" class="flex-1 gradient-yellow text-pupr-blue font-bold py-3 rounded-xl shadow-lg hover:shadow-xl btn-press transition-all">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Tambah -->
    <div id="tambahModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 overlay">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all scale-95 opacity-0">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-pupr-yellow/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-pupr-yellow-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Konfirmasi Tambah Ritase</h3>
                <div id="konfirmasiDetail" class="text-sm text-gray-600 mb-4 text-left bg-gray-50 p-4 rounded-xl"></div>
                <div class="flex gap-3">
                    <button onclick="closeTambahModal()" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors btn-press">Batal</button>
                    <button onclick="submitTambahRitase()" class="flex-1 gradient-yellow text-pupr-blue font-bold py-3 rounded-xl shadow-lg hover:shadow-xl btn-press transition-all">Ya, Tambah</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // ===== INITIALIZE TOM SELECT =====
        let tomSopir, tomTujuan, tomEditSopir, tomEditTujuan;
        let formDataTambah = null;

        document.addEventListener('DOMContentLoaded', function() {
            // Form Tambah - Sopir
            if (document.getElementById('kode_sopir')) {
                tomSopir = new TomSelect('#kode_sopir', {
                    create: false,
                    sortField: { field: "text", direction: "asc" },
                    placeholder: 'Ketik nama atau kode sopir...',
                    allowEmptyOption: true,
                    searchField: ['text'],
                });
            }

            // Form Tambah - Tujuan
            if (document.getElementById('kode_tujuan')) {
                tomTujuan = new TomSelect('#kode_tujuan', {
                    create: false,
                    sortField: { field: "text", direction: "asc" },
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

                // Simpan form data untuk submit nanti
                formDataTambah = formData;

                // Tampilkan detail di modal
                const sopir = document.getElementById('kode_sopir');
                const tujuan = document.getElementById('kode_tujuan');
                const periode = document.getElementById('periode_id');
                const status = formData.get('status');
                const nominal = parseFloat(formData.get('nominal_kompensasi') || 0);
                const dt = parseFloat(formData.get('dt') || 0);

                let kompensasiHtml = '';
                if (status === 'gagal_produksi') {
                    if (nominal > 0) {
                        kompensasiHtml = `
                            <div class="flex justify-between">
                                <span class="text-gray-500">Kompensasi:</span>
                                <span class="font-semibold text-red-600">Rp ${nominal.toLocaleString('id-ID')}</span>
                            </div>
                        `;
                    } else {
                        kompensasiHtml = `
                            <div class="flex justify-between">
                                <span class="text-gray-500">Kompensasi:</span>
                                <span class="font-semibold text-orange-600">Belum ditentukan</span>
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
                        <div class="flex justify-between"><span class="text-gray-500">DT (Sewa DT):</span><span class="font-semibold text-purple-600">Rp ${dt.toLocaleString('id-ID')}</span></div>
                        ${kompensasiHtml}
                    </div>
                `;

                // Tampilkan modal dengan animasi
                const modal = document.getElementById('tambahModal');
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('flex');
                    modal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
                    modal.querySelector('.transform').classList.add('scale-100', 'opacity-100');
                }, 10);
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

                // Jika status Gagal Produksi → DT = 0
                if (statusVal === 'gagal_produksi') {
                    dt = 0;
                    keterangan = '❌ Gagal Produksi → Tidak dapat DT';
                    dtInput.value = dt;
                    document.getElementById('previewKeterangan').textContent = keterangan;
                    document.getElementById('previewRitKe').textContent = '-';
                    document.getElementById('previewSewaDT').textContent = '0';
                    document.getElementById('previewAturan').classList.remove('hidden');
                    return;
                }

                // 🔥 KABUPATEN LAINNYA TETAP DAPAT DT (diproses di server)

                // Jika sopir dan tanggal dipilih, cek rit lain
                if (sopir && tgl && kab && waktuVal) {
                    // Cek via AJAX ke server
                    fetch('{{ route("ritase.cek.aturan") }}', {
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
            document.getElementById('editForm').action = '/ritase/' + ritase.id;
            document.getElementById('edit_kode_ritase').value = ritase.kode_ritase;
            document.getElementById('edit_periode_id').value = ritase.periode_id;
            document.getElementById('edit_tanggal').value = ritase.tanggal;
            document.getElementById('edit_waktu').value = ritase.waktu;
            document.getElementById('edit_kabupaten').value = ritase.kabupaten;
            document.getElementById('edit_status').value = ritase.status;
            document.getElementById('edit_dt').value = ritase.dt || 0;
            document.getElementById('edit_catatan').value = ritase.catatan || '';

            // Initialize Tom Select untuk edit
            setTimeout(() => {
                if (tomEditSopir) tomEditSopir.destroy();
                if (tomEditTujuan) tomEditTujuan.destroy();

                tomEditSopir = new TomSelect('#edit_kode_sopir', {
                    create: false,
                    sortField: { field: "text", direction: "asc" },
                    placeholder: 'Ketik nama atau kode sopir...',
                    searchField: ['text'],
                });

                tomEditTujuan = new TomSelect('#edit_kode_tujuan', {
                    create: false,
                    sortField: { field: "text", direction: "asc" },
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
            setTimeout(() => {
                modal.classList.add('flex');
            }, 10);
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
            modal.querySelector('.transform').classList.add('scale-95', 'opacity-0');
            modal.querySelector('.transform').classList.remove('scale-100', 'opacity-100');
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
        document.querySelectorAll('.overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                    this.classList.remove('flex');
                    // Reset animasi tambah modal
                    if (this.id === 'tambahModal') {
                        this.querySelector('.transform').classList.add('scale-95', 'opacity-0');
                        this.querySelector('.transform').classList.remove('scale-100', 'opacity-100');
                        formDataTambah = null;
                    }
                }
            });
        });

        // Tutup dengan Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
                closeTambahModal();
            }
        });
    </script>
    @endpush

</x-layouts.dashboard>
