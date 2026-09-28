<x-layouts.dashboard
    :title="'Kelola Periode'"
    :pageTitle="'Kelola Periode'"
    >

    {{-- HEADER --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold" style="color:var(--text)">Kelola Periode</h1>
        <p class="text-sm mt-1" style="color:var(--text-muted)">Atur periode kerja untuk mengelompokkan ritase sopir.</p>
    </div>

{{-- ALERT SUCCESS --}}
@if(session('success'))
    <div class="alert alert-success mb-4">
        {{ session('success') }}
    </div>
@endif

{{-- ALERT ERROR --}}
@if(session('error'))
    <div class="alert alert-error mb-4">
        {{ session('error') }}
    </div>
@endif

{{-- STATS CARDS --}}
<div class="card mb-6" id="liveStats">
    <div class="grid grid-cols-2 lg:grid-cols-4">
        <div style="padding:15px 20px" class="ledger-cell sm:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TOTAL PERIODE</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $totalPeriode }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">terdaftar</p>
        </div>
        <div style="padding:15px 20px" class="ledger-cell sm:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">PERIODE AKTIF</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $periodeAktif }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">berjalan</p>
        </div>
        <div style="padding:15px 20px" class="ledger-cell lg:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">PERIODE SELESAI</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $periodeSelesai }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">ditutup</p>
        </div>
        <div style="padding:15px 20px" data-ledger>
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TOTAL RITASE</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($totalRitase ?? 0) }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">semua periode</p>
        </div>
    </div>
</div>

{{-- FORM TAMBAH PERIODE --}}
<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-xs font-semibold uppercase" style="color:var(--text-muted)">
            Tambah Periode Baru
            <span class="text-xs ml-2" style="color:var(--text-dims);font-weight:400">Kode periode akan digenerate otomatis (PER-XXX)</span>
        </h3>
    </div>
    <div class="card-body">
        <form id="formTambahPeriode" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <label class="form-label">Nama Periode <span class="text-red-500">*</span></label>
                    <input type="text" id="nama_periode" name="nama_periode" required
                        class="form-input"
                        placeholder="Contoh: Periode 1-7 Juli 2026">
                    <p class="text-xs font-medium mt-1 hidden" style="color:var(--danger)" id="error_nama"></p>
                </div>

                <div>
                    <label class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" required
                        class="form-input">
                    <p class="text-xs font-medium mt-1 hidden" style="color:var(--danger)" id="error_tanggal_mulai"></p>
                </div>

                <div>
                    <label class="form-label">Tanggal Selesai <span class="text-red-500">*</span></label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" required
                        class="form-input">
                    <p class="text-xs font-medium mt-1 hidden" style="color:var(--danger)" id="error_tanggal_selesai"></p>
                </div>

                <div class="flex items-end">
                    <button type="button" onclick="konfirmasiTambahPeriode()"
                        class="btn btn-primary">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span>Tambah</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- TABEL DATA PERIODE --}}
<div class="card mb-6" data-live-root>
    <div class="border-b border-gray-200 px-5 py-3 bg-gray-50">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Daftar Periode</h3>
            </div>
            <div class="flex flex-row gap-2 items-center w-full sm:w-auto">
                <div class="relative flex-1 sm:w-72 sm:flex-none">
                    <input type="text" id="liveSearch" value="{{ $search }}"
                        class="w-full pl-10 pr-10 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white"
                        placeholder="Cari nama atau kode..." autocomplete="off">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-200 rounded">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="relative shrink-0" id="periodeFilterWrap">
                    <button onclick="togglePeriodeFilter()" class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium bg-white hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter
                        @if($statusFilter)<span class="w-2 h-2 rounded-full bg-green-500"></span>@endif
                    </button>
                    <div class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4" id="periodeFilterPanel">
                        <form method="GET" action="{{ route('periode.index') }}" class="space-y-3">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</label>
                                <select name="status" id="filterStatus" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                    <option value="">Semua Status</option>
                                    <option value="aktif" {{ $statusFilter == 'aktif' ? 'selected' : '' }}>Aktif</option>
                                    <option value="selesai" {{ $statusFilter == 'selesai' ? 'selected' : '' }}>Selesai</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="liveResults">
        <div class="table-responsive">
            <table class="w-full">
                <thead style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode Periode</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Periode</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah Ritase</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @if($periodes->count() > 0)
                        @foreach($periodes as $index => $periode)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 text-sm text-gray-600 font-medium">{{ $periodes->firstItem() + $index }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center px-3 py-1 rounded bg-gray-100 text-gray-700 font-bold text-sm">
                                        {{ $periode->kode_periode }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-sm font-semibold text-gray-900">{{ $periode->nama_periode }}</td>
                                <td class="px-4 py-2.5 text-sm text-gray-600">{{ $periode->tanggal_mulai->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="px-4 py-2.5 text-sm text-gray-600">{{ $periode->tanggal_selesai->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
                                        {{ $periode->ritase->count() }} ritase
                                    </span>
                                </td>
                                <td class="px-4 py-2.5">
                                    @if($periode->status == 'aktif')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
                                            <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                                            Selesai
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick='openEditModal(@json($periode))'
                                            class="inline-flex items-center gap-1 text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Edit</button>

                                        <button onclick="confirmDelete({{ $periode->id }}, '{{ $periode->nama_periode }}')"
                                            class="inline-flex items-center gap-1 text-xs text-red-600 border border-red-200 px-2.5 py-1.5 rounded hover:bg-red-50 font-medium"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8">
                                <div class="text-center py-16">
                                    <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="text-gray-500 font-semibold">Belum ada data periode</p>
                                    <p class="text-gray-400 text-sm mt-1">Tambahkan periode pertama Anda menggunakan form di atas.</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <p class="text-xs text-gray-400 px-5 pt-3">Menampilkan {{ $periodes->firstItem() ?? 0 }} - {{ $periodes->lastItem() ?? 0 }} dari {{ $periodes->total() }} data</p>

        {{-- PAGINATION --}}
        <x-shared.pagination :paginator="$periodes" />
        </div>
    </div>

    {{-- MODAL KONFIRMASI TAMBAH --}}
    <div id="tambahModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded shadow-xl w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Tambah Periode</h3>
                    <button onclick="closeTambahModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="konfirmasiDetail" class="text-sm text-gray-600 mb-4 bg-gray-50 p-4 rounded"></div>
                <div class="flex gap-3">
                    <button onclick="closeTambahModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition inline-flex items-center justify-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Batal</button>
                    <button onclick="submitTambahPeriode()" class="flex-1 bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition inline-flex items-center justify-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Ya, Tambah</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="editModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded shadow-xl w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Data Periode</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="editForm" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="form-label">Kode Periode</label>
                        <input type="text" id="edit_kode" disabled class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded text-sm text-gray-600 font-bold cursor-not-allowed">
                    </div>

                    <div>
                        <label class="form-label">Nama Periode <span class="text-red-500">*</span></label>
                        <input type="text" id="edit_nama" name="nama_periode" required class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" id="edit_tanggal_mulai" name="tanggal_mulai" required class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Tanggal Selesai <span class="text-red-500">*</span></label>
                        <input type="date" id="edit_tanggal_selesai" name="tanggal_selesai" required class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Status <span class="text-red-500">*</span></label>
                        <select id="edit_status" name="status" required class="form-input">
                            <option value="aktif">Aktif</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="closeEditModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition inline-flex items-center justify-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Batal</button>
                        <button type="button" onclick="konfirmasiEditPeriode()" class="flex-1 bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition inline-flex items-center justify-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL KONFIRMASI EDIT --}}
    <div id="konfirmasiEditModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded shadow-xl w-full max-w-sm mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Perubahan</h3>
                    <button onclick="closeKonfirmasiEditModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mb-4">Anda yakin ingin memperbarui data periode ini?</p>
                <div class="flex gap-3">
                    <button onclick="closeKonfirmasiEditModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition inline-flex items-center justify-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Batal</button>
                    <button onclick="submitEditPeriode()" class="flex-1 bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition inline-flex items-center justify-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Ya, Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    @push('scripts')
    <script>
        window.crudDeleteUrl = '{{ url("/periode") }}';
        window.crudStoreUrl = '{{ route("periode.store") }}';
        window.crudCsrfToken = '{{ csrf_token() }}';
        window.crudEntityName = 'Periode';
    </script>
    <script src="{{ asset('js/periode.js') }}"></script>
    <script src="{{ asset('js/live-search.js') }}"></script>
    <script>
        function togglePeriodeFilter() {
            const panel = document.getElementById('periodeFilterPanel');
            panel.classList.toggle('hidden');
        }
        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('periodeFilterWrap');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('periodeFilterPanel').classList.add('hidden');
            }
        });
    </script>
@endpush

@push('scripts')
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
    if (rootEl) rootEl.querySelectorAll('form[method="GET"] select[name]').forEach(function(el){
        el.addEventListener('change', refreshStats);
    });
    var clearBtn = document.getElementById('clearSearch');
    if (clearBtn) clearBtn.addEventListener('click', refreshStats);
})();
</script>
@endpush
</x-layouts.dashboard>


