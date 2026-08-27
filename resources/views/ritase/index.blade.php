<x-layouts.dashboard :title="'Kelola Ritase'" :pageTitle="'Kelola Ritase'">

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
                    @if($tanggal || $filterPeriode)<span class="w-2 h-2 rounded-full bg-green-500"></span>@endif
                </button>
                <div class="hidden absolute right-0 mt-2 w-72 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4" id="ritFilterPanel">
                    <form method="GET" action="{{ route('ritase.index') }}" class="space-y-3">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode</label>
                            <select name="periode" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                <option value="">Semua Periode</option>
                                @foreach($periodes as $periode)
                                    <option value="{{ $periode->id }}" {{ $filterPeriode == $periode->id ? 'selected' : '' }}>{{ $periode->nama_periode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</label>
                            <input type="date" name="tanggal" value="{{ $tanggal }}" onchange="onTanggalChange(this)" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
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
    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-error mb-4">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-error mb-4"><ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    {{-- FORM TAMBAH RITASE --}}
    <x-ritase.form-tambah :periodes="$periodes" :sopirs="$sopirs" />

    {{-- STAT CARDS --}}
    <x-ritase.stat-cards :totalRitase="$totalRitase" :ritaseValid="$ritaseValid" :ritasePending="$ritasePending" :ritaseGagal="$ritaseGagal" :sopirTerlibat="$sopirTerlibat" :tanggal="$tanggal" :filterPeriode="$filterPeriode" />

    {{-- TABEL DATA RITASE --}}
    <div class="card mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex gap-0 px-5" role="tablist">
                <button type="button" class="tab-btn" data-tab="1" onclick="switchTab(1)">Kelola Ritase</button>
                <button type="button" class="tab-btn" data-tab="2" onclick="switchTab(2)">Detail Ritase</button>
            </nav>
        </div>

        {{-- TAB 1: DATA RITASE --}}
        <div id="tab-content-1" class="tab-panel">
            <div class="border-b border-gray-200 px-5 py-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Daftar Ritase</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Menampilkan {{ $ritases->firstItem() ?? 0 }} - {{ $ritases->lastItem() ?? 0 }} dari {{ $ritases->total() }} data</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <form method="GET" action="{{ route('ritase.index') }}" class="flex gap-2">
                            @if($tanggal)<input type="hidden" name="tanggal" value="{{ $tanggal }}">@endif
                            <select name="periode" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded text-sm bg-white">
                                <option value="">Semua Periode</option>
                                @foreach($periodes as $periode)<option value="{{ $periode->id }}" {{ $filterPeriode == $periode->id ? 'selected' : '' }}>{{ $periode->nama_periode }}</option>@endforeach
                            </select>
                            <select name="sopir" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded text-sm bg-white">
                                <option value="">Semua Sopir</option>
                                @foreach($sopirs as $sopir)<option value="{{ $sopir->kode_sopir }}" {{ $filterSopir == $sopir->kode_sopir ? 'selected' : '' }}>{{ $sopir->nama }}</option>@endforeach
                            </select>
                            <select name="tujuan" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded text-sm bg-white">
                                <option value="">Semua Tujuan</option>
                                @foreach($tujuans as $tujuan)<option value="{{ $tujuan->kode_tujuan }}" {{ ($filterTujuan ?? '') == $tujuan->kode_tujuan ? 'selected' : '' }}>{{ $tujuan->nama }}</option>@endforeach
                            </select>
                        </form>
                        <div class="relative w-full sm:w-64">
                            <input type="text" id="liveSearch" value="{{ $search }}" class="w-full pl-10 pr-10 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white" placeholder="Cari kode, sopir, tujuan..." autocomplete="off">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-dims)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-200 rounded-full">
                                <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
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
                                        <p class="text-sm font-medium text-gray-900">{{ $ritase->tujuan ? $ritase->tujuan->nama : 'Tujuan tidak ditemukan' }}</p>
                                        <p class="text-xs text-gray-500">{{ $ritase->tujuan ? $ritase->tujuan->kode_tujuan : '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $ritase->tanggal->format('d M Y') }}</td>
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
                                            <button onclick='openEditModal(@json($ritase))' class="text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium">Edit</button>
                                            <form action="{{ route('ritase.destroy', $ritase->id) }}" method="POST" class="inline" id="deleteRitase_{{ $ritase->id }}">
                                                @csrf @method('DELETE')
                                                <button type="button" onclick="confirmDeleteRitase({{ $ritase->id }}, '{{ $ritase->kode_ritase }}')" class="text-xs text-red-600 border border-red-200 px-2.5 py-1.5 rounded hover:bg-red-50 font-medium">Hapus</button>
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

            <x-shared.pagination :paginator="$ritases" />
        </div>

        {{-- TAB 2: DETAIL RITASE --}}
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
                            @foreach($periodes as $periode)<option value="{{ $periode->id }}" {{ $filterPeriode == $periode->id ? 'selected' : '' }}>{{ $periode->nama_periode }}</option>@endforeach
                        </select>
                        <div class="relative w-full sm:w-64">
                            <input type="text" id="detailSearch" class="w-full pl-10 pr-10 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white" placeholder="Cari nama sopir atau tujuan..." autocomplete="off">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-dims)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
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

    <style>
        .tab-btn { padding: 0.75rem 1.25rem; font-size: 0.8125rem; font-weight: 500; color: #8a8698; background: none; border: none; border-bottom: 2px solid transparent; cursor: pointer; transition: all 0.15s ease; }
        .tab-btn:hover { color: #2d6a4f; }
        .tab-btn.active { color: #2d6a4f; border-bottom-color: #2d6a4f; font-weight: 600; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
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
    @endpush

</x-layouts.dashboard>
