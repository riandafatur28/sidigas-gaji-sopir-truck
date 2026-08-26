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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-card">
            <p class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Total Sopir</p>
            <p class="text-2xl font-bold mt-1" style="color:var(--text)">{{ $totalSopir }}</p>
        </div>

        <div class="stat-card">
            <p class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Sopir Aktif</p>
            <p class="text-2xl font-bold mt-1" style="color:var(--text)">{{ $sopirAktif }}</p>
        </div>

        <div class="stat-card">
            <p class="text-xs font-semibold uppercase" style="color:var(--text-muted)">Sopir Nonaktif</p>
            <p class="text-2xl font-bold mt-1" style="color:var(--text)">{{ $sopirNonaktif }}</p>
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
                        Tambah
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL DATA SOPIR --}}
    <div class="card mb-6">
        <div class="border-b border-gray-200 px-5 py-3 bg-gray-50">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Daftar Sopir</p>
                    <p class="text-xs text-gray-400 mt-0.5">Menampilkan {{ $sopirs->firstItem() ?? 0 }} - {{ $sopirs->lastItem() ?? 0 }} dari {{ $sopirs->total() }} data</p>
                </div>

                {{-- SEARCH --}}
                <div class="relative w-full sm:w-72">
                    <input type="text" id="liveSearch" value="{{ $search }}"
                        class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white"
                        placeholder="Ketik untuk mencari..." autocomplete="off">

                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-dims)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>

                    <div id="searchLoading" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                        <svg class="w-4 h-4 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-100 rounded transition" title="Hapus pencarian">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

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
                                    {{ $sopir->created_at->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <button onclick="openEditModal({{ $sopir->id }}, '{{ $sopir->kode_sopir }}', '{{ $sopir->nama }}', '{{ $sopir->status }}')" class="text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium">Edit</button>

                                        <button onclick="confirmDelete({{ $sopir->id }}, '{{ $sopir->nama }}')" class="text-xs text-red-600 border border-red-200 px-2.5 py-1.5 rounded hover:bg-red-50 font-medium">Hapus</button>
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

        {{-- PAGINATION --}}
        @if($sopirs->hasPages())
            <div class="border-t border-gray-200 px-5 py-3 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-600">
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
    @endpush

    {{-- FORM HAPUS (HIDDEN) --}}
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

</x-layouts.dashboard>
