<x-layouts.dashboard
    :title="'Kelola Periode'"
    :pageTitle="'Kelola Periode'"
    :user="auth()->user()">

    {{-- HEADER --}}
    <div class="border-b border-gray-200 pb-4 mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Kelola Periode</h1>
        <p class="text-base text-gray-500 mt-1">Atur periode kerja untuk mengelompokkan ritase sopir.</p>
    </div>

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
        <div class="rounded border border-green-200 bg-green-50 text-green-700 px-4 py-3 mb-4 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- ALERT ERROR --}}
    @if(session('error'))
        <div class="rounded border border-red-200 bg-red-50 text-red-700 px-4 py-3 mb-4 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- STATS CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Total Periode</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalPeriode }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Periode Aktif</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $periodeAktif }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Periode Selesai</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $periodeSelesai }}</p>
        </div>
    </div>

    {{-- FORM TAMBAH PERIODE --}}
    <div class="bg-white border border-gray-200 rounded mb-6 overflow-hidden">
        <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
            <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">
                Tambah Periode Baru
                <span class="font-normal text-gray-400 text-xs ml-2">Kode periode akan digenerate otomatis (PER-XXX)</span>
            </h3>
        </div>
        <div class="px-5 py-4">
            <form id="formTambahPeriode" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Nama Periode <span class="text-red-500">*</span></label>
                        <input type="text" id="nama_periode" name="nama_periode" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white"
                            placeholder="Contoh: Periode 1-7 Juli 2026">
                        <p class="text-red-500 text-xs font-medium mt-1 hidden" id="error_nama"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" id="tanggal_mulai" name="tanggal_mulai" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white">
                        <p class="text-red-500 text-xs font-medium mt-1 hidden" id="error_tanggal_mulai"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Tanggal Selesai <span class="text-red-500">*</span></label>
                        <input type="date" id="tanggal_selesai" name="tanggal_selesai" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white">
                        <p class="text-red-500 text-xs font-medium mt-1 hidden" id="error_tanggal_selesai"></p>
                    </div>

                    <div class="flex items-end">
                        <button type="button" onclick="konfirmasiTambahPeriode()"
                            class="bg-[#1a1a2e] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#2d2d44] transition">
                            Tambah
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABEL DATA PERIODE --}}
    <div class="bg-white border border-gray-200 rounded overflow-hidden">
        <div class="border-b border-gray-200 px-5 py-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Daftar Periode</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Menampilkan {{ $periodes->firstItem() ?? 0 }} - {{ $periodes->lastItem() ?? 0 }} dari {{ $periodes->total() }} data</p>
                </div>
                <div class="relative w-72">
                    <input type="text" id="liveSearch" value="{{ $search }}"
                        class="w-full pl-10 pr-10 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white"
                        placeholder="Cari nama atau kode..." autocomplete="off">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-200 rounded">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
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
                                <td class="px-4 py-2.5 text-sm text-gray-600">{{ $periode->tanggal_mulai->format('d M Y') }}</td>
                                <td class="px-4 py-2.5 text-sm text-gray-600">{{ $periode->tanggal_selesai->format('d M Y') }}</td>
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
                                            class="p-1.5 text-gray-500 border border-gray-200 rounded hover:text-gray-700 hover:bg-gray-50" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>

                                        <button onclick="confirmDelete({{ $periode->id }}, '{{ $periode->nama_periode }}')"
                                            class="p-1.5 text-red-500 border border-gray-200 rounded hover:text-red-700 hover:bg-red-50" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
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

        {{-- PAGINATION --}}
        @if($periodes->hasPages())
            <div class="border-t border-gray-200 px-5 py-4 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-600">Halaman {{ $periodes->currentPage() }} dari {{ $periodes->lastPage() }}</p>
                    <div class="flex items-center space-x-2">
                        @if($periodes->onFirstPage())
                            <span class="px-3 py-1.5 text-sm text-gray-400 bg-white border border-gray-200 rounded cursor-not-allowed">Sebelumnya</span>
                        @else
                            <a href="{{ $periodes->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-200 rounded hover:bg-gray-50 font-medium">Sebelumnya</a>
                        @endif
                        @foreach($periodes->getUrlRange(1, $periodes->lastPage()) as $page => $url)
                            @if($page == $periodes->currentPage())
                                <span class="px-3 py-1.5 text-sm font-bold bg-[#1a1a2e] text-white border border-[#1a1a2e] rounded">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-200 rounded hover:bg-gray-50 font-medium">{{ $page }}</a>
                            @endif
                        @endforeach
                        @if($periodes->hasMorePages())
                            <a href="{{ $periodes->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-200 rounded hover:bg-gray-50 font-medium">Selanjutnya</a>
                        @else
                            <span class="px-3 py-1.5 text-sm text-gray-400 bg-white border border-gray-200 rounded cursor-not-allowed">Selanjutnya</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
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
                    <button onclick="closeTambahModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                    <button onclick="submitTambahPeriode()" class="flex-1 bg-[#1a1a2e] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#2d2d44] transition">Ya, Tambah</button>
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
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Kode Periode</label>
                        <input type="text" id="edit_kode" disabled class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded text-sm text-gray-600 font-bold cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Nama Periode <span class="text-red-500">*</span></label>
                        <input type="text" id="edit_nama" name="nama_periode" required class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <input type="date" id="edit_tanggal_mulai" name="tanggal_mulai" required class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Tanggal Selesai <span class="text-red-500">*</span></label>
                        <input type="date" id="edit_tanggal_selesai" name="tanggal_selesai" required class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Status <span class="text-red-500">*</span></label>
                        <select id="edit_status" name="status" required class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white">
                            <option value="aktif">Aktif</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="closeEditModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                        <button type="button" onclick="konfirmasiEditPeriode()" class="flex-1 bg-[#1a1a2e] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#2d2d44] transition">Simpan</button>
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
                    <button onclick="closeKonfirmasiEditModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                    <button onclick="submitEditPeriode()" class="flex-1 bg-[#1a1a2e] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#2d2d44] transition">Ya, Simpan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL KONFIRMASI HAPUS --}}
    <div id="deleteModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded shadow-xl w-full max-w-sm mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Hapus Data Periode?</h3>
                    <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p id="delete_pesan" class="text-sm text-gray-600 mb-4"></p>
                <div class="flex gap-3">
                    <button onclick="closeDeleteModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                    <button onclick="submitDelete()" class="flex-1 bg-red-600 text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-red-700 transition">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <script>
        // ===== VALIDASI INPUT =====
        function validasiNama(input) {
            return /^[a-zA-Z0-9\s\-\.]+$/.test(input);
        }

        function validasiNominal(input) {
            return /^\d+$/.test(input) && parseInt(input) >= 0;
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

        // ===== Format tanggal ke Bahasa Indonesia =====
        function formatTanggal(dateStr) {
            const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const date = new Date(dateStr);
            return `${date.getDate()} ${bulan[date.getMonth()]} ${date.getFullYear()}`;
        }

        // ===== TAMBAH PERIODE =====
        function konfirmasiTambahPeriode() {
            const nama = document.getElementById('nama_periode').value.trim();
            const tglMulai = document.getElementById('tanggal_mulai').value;
            const tglSelesai = document.getElementById('tanggal_selesai').value;

            let valid = true;
            ['nama_periode', 'tanggal_mulai', 'tanggal_selesai'].forEach(field => {
                const el = document.getElementById(field);
                const errorEl = document.getElementById('error_' + field.replace('tanggal_', 'tanggal_'));
                if (!el.value.trim()) {
                    valid = false;
                    el.classList.add('border-red-500');
                } else {
                    el.classList.remove('border-red-500');
                }
            });

            if (!nama || !tglMulai || !tglSelesai) return;

            if (!validasiNama(nama)) {
                document.getElementById('error_nama').textContent = 'Nama hanya boleh huruf, angka, spasi, dan strip.';
                document.getElementById('error_nama').classList.remove('hidden');
                return;
            }
            document.getElementById('error_nama').classList.add('hidden');

            if (new Date(tglSelesai) < new Date(tglMulai)) {
                alert('Tanggal selesai harus sama atau setelah tanggal mulai!');
                return;
            }

            document.getElementById('konfirmasiDetail').innerHTML = `
                <div class="space-y-2">
                    <div class="flex justify-between"><span class="text-gray-500">Nama:</span><span class="font-semibold">${nama}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Mulai:</span><span class="font-semibold">${formatTanggal(tglMulai)}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Selesai:</span><span class="font-semibold">${formatTanggal(tglSelesai)}</span></div>
                </div>
            `;

            const modal = document.getElementById('tambahModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeTambahModal() {
            const modal = document.getElementById('tambahModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function submitTambahPeriode() {
            const form = document.getElementById('formTambahPeriode');
            form.method = 'POST';
            form.action = '{{ route("periode.store") }}';
            form.submit();
        }

        // ===== EDIT PERIODE =====
        function openEditModal(periode) {
            document.getElementById('editForm').action = `/periode/${periode.id}`;
            document.getElementById('edit_kode').value = periode.kode_periode;
            document.getElementById('edit_nama').value = periode.nama_periode;
            document.getElementById('edit_tanggal_mulai').value = periode.tanggal_mulai.split(' ')[0];
            document.getElementById('edit_tanggal_selesai').value = periode.tanggal_selesai.split(' ')[0];
            document.getElementById('edit_status').value = periode.status;

            const modal = document.getElementById('editModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function konfirmasiEditPeriode() {
            const nama = document.getElementById('edit_nama').value.trim();
            if (!validasiNama(nama)) return;
            closeEditModal();
            const modal = document.getElementById('konfirmasiEditModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeKonfirmasiEditModal() {
            const modal = document.getElementById('konfirmasiEditModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function submitEditPeriode() {
            document.getElementById('editForm').submit();
        }

        // ===== HAPUS PERIODE =====
        function confirmDelete(id, nama, jumlahRitase) {
            document.getElementById('deleteForm').action = `/periode/${id}`;

            const pesan = document.getElementById('delete_pesan');
            if (jumlahRitase > 0) {
                pesan.innerHTML = `Periode <strong class="text-gray-900">${nama}</strong> memiliki <strong class="text-red-600">${jumlahRitase} ritase</strong> dan <strong class="text-red-600">tidak dapat dihapus</strong>!`;
                document.querySelector('#deleteModal button.bg-red-600').disabled = true;
                document.querySelector('#deleteModal button.bg-red-600').classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                pesan.innerHTML = `Anda yakin ingin menghapus periode <strong class="text-gray-900">${nama}</strong>?<br><span class="text-xs text-red-500 mt-1 block">Tindakan ini tidak dapat dibatalkan.</span>`;
                document.querySelector('#deleteModal button.bg-red-600').disabled = false;
                document.querySelector('#deleteModal button.bg-red-600').classList.remove('opacity-50', 'cursor-not-allowed');
            }

            const modal = document.getElementById('deleteModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function submitDelete() {
            document.getElementById('deleteForm').submit();
        }

        // Tutup modal saat klik di luar
        document.querySelectorAll('.overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                    this.classList.remove('flex');
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTambahModal();
                closeEditModal();
                closeKonfirmasiEditModal();
                closeDeleteModal();
            }
        });
    </script>

</x-layouts.dashboard>
