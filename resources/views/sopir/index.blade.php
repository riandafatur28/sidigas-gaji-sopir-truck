<x-layouts.dashboard
    :title="'Kelola Sopir'"
    :pageTitle="'Kelola Sopir'"
    >

    {{-- HEADER --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold" style="color:var(--text)">Kelola Data Sopir</h1>
        <p class="text-sm mt-1" style="color:var(--text-muted)">Tambah, edit, dan hapus data sopir armada Anda.</p>
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
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TOTAL SOPIR</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $totalSopir }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">terdaftar</p>
        </div>
        <div style="padding:15px 20px" class="ledger-cell sm:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">SOPIR AKTIF</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $sopirAktif }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">punya ritase</p>
        </div>
        <div style="padding:15px 20px" class="ledger-cell lg:border-r">
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">SOPIR NONAKTIF</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ $sopirNonaktif }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">tanpa ritase</p>
        </div>
        <div style="padding:15px 20px" data-ledger>
            <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TOTAL RITASE</p>
            <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($totalRitase ?? 0) }}</p>
            <p class="dash-num" style="font-size:12px;color:var(--text-muted)">seluruh sopir</p>
        </div>
    </div>
</div>

{{-- FORM TAMBAH SOPIR --}}
<div class="card mb-6">
    <div class="card-header">
        <span class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Tambah Sopir Baru</span>
        <span class="text-xs ml-2" style="color:var(--text-dims);font-weight:400">Kode sopir akan digenerate otomatis (SPR-XXX)</span>
    </div>
    <div class="card-body">
        <form id="formTambahSopir" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <div class="flex-1">
                <input type="text" id="namaTambah" required
                    class="form-input"
                    placeholder="Masukkan nama sopir...">
                <p class="text-red-500 text-xs mt-1 hidden" id="errorTambah"></p>
            </div>
            <div class="flex items-end">
                <button type="button" onclick="konfirmasiTambah()"
                    class="btn btn-primary">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <span>Tambah</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- TABEL DATA SOPIR --}}
<div class="card mb-6" data-live-root>
    <div class="border-b border-gray-200 px-5 py-3 bg-gray-50">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Daftar Sopir</p>
            </div>

            <div class="flex flex-row gap-2 items-center w-full sm:w-auto">
                <div class="relative flex-1 sm:w-72 sm:flex-none">
                    <input type="text" id="liveSearch" value="{{ $search }}"
                        class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white"
                        placeholder="Ketik untuk mencari..." autocomplete="off">

                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-dims)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>

                    <div id="searchLoading" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-100 rounded transition" title="Hapus pencarian">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="relative shrink-0" id="sopirFilterWrap">
                    <button onclick="toggleSopirFilter()" class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium bg-white hover:bg-gray-50 transition shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter
                        @if($statusFilter)<span class="w-2 h-2 rounded-full bg-green-500"></span>@endif
                    </button>
                    <div class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4" id="sopirFilterPanel">
                        <form method="GET" action="{{ route('sopir.index') }}" class="space-y-3">
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
            @if($sopirs->count() > 0)
                <table class="w-full">
                    <thead style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode Sopir</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Sopir</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Ditambahkan</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($sopirs as $index => $sopir)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $sopirs->firstItem() + $index }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-medium rounded">
                                        {{ $sopir->kode_sopir }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm font-medium text-gray-900">{{ $sopir->nama }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($sopir->status == 'aktif')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-medium">
                                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $sopir->created_at->locale('id')->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <button onclick="openEditModal({{ $sopir->id }}, '{{ $sopir->kode_sopir }}', '{{ $sopir->nama }}', '{{ $sopir->status }}')" class="inline-flex items-center gap-1 text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Edit</button>

                                        <button onclick="confirmDelete({{ $sopir->id }}, '{{ $sopir->nama }}')" class="inline-flex items-center gap-1 text-xs text-red-600 border border-red-200 px-2.5 py-1.5 rounded hover:bg-red-50 font-medium"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p class="text-gray-500 font-medium">Belum ada data sopir</p>
                    <p class="text-gray-400 text-sm mt-1">Tambahkan sopir pertama Anda menggunakan form di atas.</p>
                </div>
            @endif
        </div>

        <p class="text-xs text-gray-400 px-5 pt-3">Menampilkan {{ $sopirs->firstItem() ?? 0 }} - {{ $sopirs->lastItem() ?? 0 }} dari {{ $sopirs->total() }} data</p>

        {{-- PAGINATION --}}
        @if($sopirs->hasPages())
            <div class="border-t border-gray-200 px-5 py-3 bg-gray-50">
                <div class="flex items-center justify-between w-full gap-3">
                    <p class="text-sm text-gray-600 whitespace-nowrap">
                        Halaman {{ $sopirs->currentPage() }} dari {{ $sopirs->lastPage() }}
                    </p>

                    <div class="flex items-center space-x-1.5">
                        @if($sopirs->onFirstPage())
                            <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">
                                Sebelumnya
                            </span>
                        @else
                            <a href="{{ $sopirs->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">
                                Sebelumnya
                            </a>
                        @endif

                        @php
                            $window = 2;
                            $current = $sopirs->currentPage();
                            $last = $sopirs->lastPage();
                            $start = max(1, $current - $window);
                            $end = min($last, $current + $window);
                        @endphp

                        @if($start > 1)
                            <a href="{{ $sopirs->url(1) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">1</a>
                            @if($start > 2)
                                <span class="px-3 py-1.5 text-sm text-gray-400">...</span>
                            @endif
                        @endif

                        @for($page = $start; $page <= $end; $page++)
                            @if($page == $current)
                                <span class="px-3 py-1.5 text-sm font-bold text-white bg-[#2d6a4f] border border-[#2d6a4f] rounded">{{ $page }}</span>
                            @else
                                <a href="{{ $sopirs->url($page) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">{{ $page }}</a>
                            @endif
                        @endfor

                        @if($end < $last)
                            @if($end < $last - 1)
                                <span class="px-3 py-1.5 text-sm text-gray-400">...</span>
                            @endif
                            <a href="{{ $sopirs->url($last) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">{{ $last }}</a>
                        @endif

                        @if($sopirs->hasMorePages())
                            <a href="{{ $sopirs->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">
                                Selanjutnya
                            </a>
                        @else
                            <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">
                                Selanjutnya
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        </div>
    </div>

    {{-- MODALS --}}
    <x-sopir.modal-tambah />
    <x-sopir.modal-edit />
    <x-sopir.modal-konfirmasi-edit />

    @push('scripts')
    <script>
        window.crudDeleteUrl = '{{ url("/sopir") }}';
        window.crudStoreUrl = '{{ route("sopir.store") }}';
        window.crudCsrfToken = '{{ csrf_token() }}';
        window.crudEntityName = 'Sopir';
    </script>
    <script src="{{ asset('js/crud.js') }}"></script>
    <script src="{{ asset('js/live-search.js') }}"></script>
    <script>
        function toggleSopirFilter() {
            const panel = document.getElementById('sopirFilterPanel');
            panel.classList.toggle('hidden');
        }
        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('sopirFilterWrap');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('sopirFilterPanel').classList.add('hidden');
            }
        });
    </script>
@endpush

    {{-- FORM HAPUS (HIDDEN) --}}
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

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


