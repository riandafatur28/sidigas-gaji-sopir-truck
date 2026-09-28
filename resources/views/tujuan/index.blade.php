<x-layouts.dashboard
    :title="'Kelola Tujuan'"
    :pageTitle="'Kelola Tujuan'"
    >

    {{-- HEADER --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold" style="color:var(--text)">Kelola Data Tujuan</h1>
        <p class="text-sm mt-1" style="color:var(--text-muted)">Tambah, edit, dan hapus data tujuan pengiriman armada Anda.</p>
    </div>

{{-- ALERT SUCCESS --}}
@if(session('success'))
    <div class="alert alert-success mb-4">
        {{ session('success') }}
    </div>
@endif

{{-- STATS CARDS --}}
<div class="card mb-6" id="liveStats">
    <div class="grid grid-cols-2 lg:grid-cols-4">
        <div style="padding:15px 20px" class="ledger-cell sm:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TOTAL TUJUAN</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $totalTujuan }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">terdaftar</p>
        </div>
        <div style="padding:15px 20px" class="ledger-cell sm:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TUJUAN AKTIF</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $tujuanAktif }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">punya ritase</p>
        </div>
        <div style="padding:15px 20px" class="ledger-cell lg:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TUJUAN NONAKTIF</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $tujuanNonaktif }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">tanpa ritase</p>
        </div>
        <div style="padding:15px 20px" data-ledger>
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TOTAL RITASE</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($totalRitase ?? 0) }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">semua tujuan</p>
        </div>
    </div>
</div>

{{-- FORM TAMBAH TUJUAN --}}
<div class="card mb-6">
    <div class="card-header">
        <span class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Tambah Tujuan Baru</span>
        <span class="text-xs ml-2" style="color:var(--text-dims);font-weight:400">Kode tujuan akan digenerate otomatis (TUJ-XXX)</span>
    </div>
    <div class="card-body">
        <form id="formTambahTujuan" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <div class="flex-1">
                <label class="form-label">Nama Tujuan</label>
                <input type="text" id="namaTambah" required
                    class="form-input"
                    placeholder="Masukkan nama tujuan...">
                <p class="text-xs font-medium mt-1 hidden" style="color:var(--danger)" id="errorTambah"></p>
            </div>
            <div class="flex items-end">
                <button type="button" onclick="konfirmasiTambah()"
                    class="btn btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Tambah</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- TABEL DATA TUJUAN --}}
<div class="card mb-6" data-live-root>
    <div class="border-b border-gray-200 px-5 py-3 bg-gray-50">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Daftar Tujuan</span>
            </div>

            <div class="flex flex-row gap-2 items-center w-full sm:w-auto">
                <div class="relative flex-1 sm:w-72 sm:flex-none">
                    <input type="text" id="liveSearch" value="{{ $search }}"
                        class="form-input pl-10 pr-10"
                        placeholder="Ketik untuk mencari..." autocomplete="off">

                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-dims)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>

 <div id="searchLoading" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                    <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-200 rounded transition" title="Hapus pencarian">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="relative shrink-0" id="tujuanFilterWrap">
                    <button onclick="toggleTujuanFilter()" class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium bg-white hover:bg-gray-50 transition shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter
                        @if($statusFilter)<span class="w-2 h-2 rounded-full bg-green-500"></span>@endif
                    </button>
                    <div class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4" id="tujuanFilterPanel">
                        <form method="GET" action="{{ route('tujuan.index') }}" class="space-y-3">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</label>
                                <select name="status" id="filterStatus" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                    <option value="">Semua Status</option>
                                    <option value="aktif" {{ $statusFilter == 'aktif' ? 'selected' : '' }}>Aktif</option>
                                    <option value="nonaktif" {{ $statusFilter == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
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
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">No</th>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Kode Tujuan</th>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Nama Tujuan</th>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Status</th>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tanggal Ditambahkan</th>
                    <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @if($tujuans->count() > 0)
                    @foreach($tujuans as $index => $tujuan)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-600 font-medium">{{ $tujuans->firstItem() + $index }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-medium rounded">
                                    {{ $tujuan->kode_tujuan }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-semibold text-gray-900">{{ $tujuan->nama }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($tujuan->status == 'aktif')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                                        <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $tujuan->created_at->locale('id')->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center space-x-2">
                                    <button onclick="openEditModal({{ $tujuan->id }}, '{{ $tujuan->kode_tujuan }}', '{{ $tujuan->nama }}', '{{ $tujuan->status }}')"
                                        class="inline-flex items-center gap-1 text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Edit</button>

                                    <button onclick="confirmDelete({{ $tujuan->id }}, '{{ $tujuan->nama }}')"
                                        class="inline-flex items-center gap-1 text-xs text-red-600 border border-red-200 px-2.5 py-1.5 rounded hover:bg-red-50 font-medium"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <p class="text-gray-500 font-semibold">Belum ada data tujuan</p>
                            <p class="text-gray-400 text-sm mt-1">Tambahkan tujuan pertama Anda menggunakan form di atas.</p>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
        </div>

        <p class="text-xs text-gray-400 px-5 pt-3">Menampilkan {{ $tujuans->firstItem() ?? 0 }} - {{ $tujuans->lastItem() ?? 0 }} dari {{ $tujuans->total() }} data</p>

        {{-- PAGINATION --}}
        <x-shared.pagination :paginator="$tujuans" />
        </div>
    </div>

    {{-- MODAL KONFIRMASI TAMBAH --}}
    <div id="tambahModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded border border-gray-200 w-full max-w-sm mx-4">
            <div class="p-6">
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Konfirmasi Tambah Tujuan</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Anda akan menambahkan tujuan dengan nama:<br>
                        <strong id="namaKonfirmasiTambah" class="text-gray-900 text-base"></strong><br>
                        <span class="text-xs text-gray-500 mt-1 block">Kode tujuan akan digenerate otomatis (TUJ-XXX)</span>
                    </p>

                    <div class="flex gap-3">
                        <button onclick="closeTambahModal()"
                            class="flex-1 px-4 py-2.5 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50 transition inline-flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Batal
                        </button>
                        <button onclick="submitTambah()"
                            class="flex-1 px-4 py-2.5 bg-[#2d6a4f] text-white rounded text-sm font-semibold hover:bg-[#1b4332] transition inline-flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Ya, Tambah
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="editModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded border border-gray-200 w-full max-w-md mx-4">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Data Tujuan</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <form id="editForm" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="form-label">Kode Tujuan</label>
                    <input type="text" id="edit_kode" disabled
                        class="form-input" style="opacity:0.6;cursor:not-allowed">
                    <p class="text-xs text-gray-400 mt-1">Kode tidak dapat diubah</p>
                </div>

                <div>
                    <label class="form-label">Nama Tujuan</label>
                    <input type="text" id="edit_nama" name="nama" required
                        class="form-input">
                </div>

                <div>
                    <label class="form-label">Status</label>
                    <select id="edit_status" name="status" required
                        class="form-input">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()"
                        class="flex-1 px-4 py-2.5 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50 transition inline-flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Batal
                    </button>
                    <button type="button" onclick="konfirmasiEdit()"
                        class="flex-1 px-4 py-2.5 bg-[#2d6a4f] text-white rounded text-sm font-semibold hover:bg-[#1b4332] transition inline-flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL KONFIRMASI EDIT --}}
    <div id="konfirmasiEditModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded border border-gray-200 w-full max-w-sm mx-4">
            <div class="p-6">
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Konfirmasi Perubahan</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Anda yakin ingin memperbarui data tujuan:<br>
                        <strong id="namaKonfirmasiEdit" class="text-gray-900 text-base"></strong>?
                    </p>

                    <div class="flex gap-3">
                        <button onclick="closeKonfirmasiEditModal()"
                            class="flex-1 px-4 py-2.5 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50 transition inline-flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Batal
                        </button>
                        <button onclick="submitEdit()"
                            class="flex-1 px-4 py-2.5 bg-[#2d6a4f] text-white rounded text-sm font-semibold hover:bg-[#1b4332] transition inline-flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Ya, Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FORM HAPUS (hidden) --}}
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    @push('scripts')
    <script>
        window.crudDeleteUrl = '{{ url("/tujuan") }}';
        window.crudStoreUrl = '{{ route("tujuan.store") }}';
        window.crudCsrfToken = '{{ csrf_token() }}';
        window.crudEntityName = 'Tujuan';
    </script>
    <script src="{{ asset('js/crud.js') }}"></script>
    <script src="{{ asset('js/live-search.js') }}"></script>
    <script>
        function toggleTujuanFilter() {
            const panel = document.getElementById('tujuanFilterPanel');
            panel.classList.toggle('hidden');
        }
        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('tujuanFilterWrap');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('tujuanFilterPanel').classList.add('hidden');
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

