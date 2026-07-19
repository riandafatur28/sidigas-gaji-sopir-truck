<x-layouts.dashboard
    :title="'Kelola Tujuan'"
    :pageTitle="'Kelola Tujuan'"
    :user="auth()->user()">

    {{-- HEADER --}}
    <div class="border-b border-gray-200 pb-4 mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Kelola Data Tujuan</h1>
        <p class="text-base text-gray-500 mt-1">Tambah, edit, dan hapus data tujuan pengiriman armada Anda.</p>
    </div>

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
        <div class="border border-green-200 bg-green-50 text-green-700 px-4 py-3 rounded mb-4 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- STATS CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Total Tujuan</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalTujuan }}</p>
        </div>

        <div class="bg-white border border-gray-200 rounded p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Tujuan Aktif</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $tujuanAktif }}</p>
        </div>

        <div class="bg-white border border-gray-200 rounded p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Tujuan Nonaktif</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $tujuanNonaktif }}</p>
        </div>
    </div>

    {{-- FORM TAMBAH TUJUAN --}}
    <div class="bg-white border border-gray-200 rounded mb-6">
        <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
            <span class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Tambah Tujuan Baru</span>
            <span class="font-normal text-gray-400 text-xs ml-2">Kode tujuan akan digenerate otomatis (TUJ-XXX)</span>
        </div>
        <div class="px-5 py-4">
            <form id="formTambahTujuan" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Nama Tujuan</label>
                    <input type="text" id="namaTambah" required
                        class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition"
                        placeholder="Masukkan nama tujuan...">
                    <p class="text-red-500 text-xs font-medium mt-1 hidden" id="errorTambah"></p>
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="konfirmasiTambah()"
                        class="px-5 py-2.5 bg-[#1a1a2e] text-white rounded text-sm font-semibold hover:bg-[#2d2d44] transition flex items-center space-x-2">
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
    <div class="bg-white border border-gray-200 rounded overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50 border-b border-gray-200 px-5 py-3">
            <div>
                <span class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Daftar Tujuan</span>
                <span class="text-xs text-gray-400 ml-2">Menampilkan {{ $tujuans->firstItem() ?? 0 }} - {{ $tujuans->lastItem() ?? 0 }} dari {{ $tujuans->total() }} data</span>
            </div>

            <div class="relative w-full sm:w-72">
                <input type="text" id="liveSearch" value="{{ $search }}"
                    class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition"
                    placeholder="Ketik untuk mencari..." autocomplete="off">

                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>

                <div id="searchLoading" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                    <svg class="w-4 h-4 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-200 rounded transition" title="Hapus pencarian">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
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
                                {{ $tujuan->created_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center space-x-2">
                                    <button onclick="openEditModal({{ $tujuan->id }}, '{{ $tujuan->kode_tujuan }}', '{{ $tujuan->nama }}', '{{ $tujuan->status }}')"
                                        class="p-1.5 text-gray-500 border border-gray-200 rounded hover:text-gray-700 hover:bg-gray-50 transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>

                                    <button onclick="confirmDelete({{ $tujuan->id }}, '{{ $tujuan->nama }}')"
                                        class="p-1.5 text-red-500 border border-gray-200 rounded hover:text-red-700 hover:bg-red-50 transition" title="Hapus">
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

        {{-- PAGINATION --}}
        @if($tujuans->hasPages())
            <div class="px-5 py-3 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-600">
                        Halaman {{ $tujuans->currentPage() }} dari {{ $tujuans->lastPage() }}
                    </p>

                    <div class="flex items-center space-x-2">
                        @if($tujuans->onFirstPage())
                            <span class="px-3 py-1.5 text-sm text-gray-400 bg-white border border-gray-200 rounded cursor-not-allowed">
                                Sebelumnya
                            </span>
                        @else
                            <a href="{{ $tujuans->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 font-medium">
                                Sebelumnya
                            </a>
                        @endif

                        @foreach($tujuans->getUrlRange(1, $tujuans->lastPage()) as $page => $url)
                            @if($page == $tujuans->currentPage())
                                <span class="px-3 py-1.5 text-sm font-bold text-white bg-[#1a1a2e] border border-[#1a1a2e] rounded">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 font-medium">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach

                        @if($tujuans->hasMorePages())
                            <a href="{{ $tujuans->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 font-medium">
                                Selanjutnya
                            </a>
                        @else
                            <span class="px-3 py-1.5 text-sm text-gray-400 bg-white border border-gray-200 rounded cursor-not-allowed">
                                Selanjutnya
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
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
                            class="flex-1 px-4 py-2.5 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button onclick="submitTambah()"
                            class="flex-1 px-4 py-2.5 bg-[#1a1a2e] text-white rounded text-sm font-semibold hover:bg-[#2d2d44] transition">
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
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Kode Tujuan</label>
                    <input type="text" id="edit_kode" disabled
                        class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded text-sm text-gray-600 font-semibold cursor-not-allowed">
                    <p class="text-xs text-gray-400 mt-1">Kode tidak dapat diubah</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Nama Tujuan</label>
                    <input type="text" id="edit_nama" name="nama" required
                        class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Status</label>
                    <select id="edit_status" name="status" required
                        class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()"
                        class="flex-1 px-4 py-2.5 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <button type="button" onclick="konfirmasiEdit()"
                        class="flex-1 px-4 py-2.5 bg-[#1a1a2e] text-white rounded text-sm font-semibold hover:bg-[#2d2d44] transition">
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
                            class="flex-1 px-4 py-2.5 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button onclick="submitEdit()"
                            class="flex-1 px-4 py-2.5 bg-[#1a1a2e] text-white rounded text-sm font-semibold hover:bg-[#2d2d44] transition">
                            Ya, Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL KONFIRMASI HAPUS --}}
    <div id="deleteModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded border border-gray-200 w-full max-w-sm mx-4">
            <div class="p-6">
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Hapus Data Tujuan?</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Anda yakin ingin menghapus <strong id="delete_nama" class="text-gray-900"></strong>?<br>
                        <span class="text-xs text-red-500 mt-1 block">Tindakan ini tidak dapat dibatalkan.</span>
                    </p>

                    <div class="flex gap-3">
                        <button onclick="closeDeleteModal()"
                            class="flex-1 px-4 py-2.5 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button onclick="submitDelete()"
                            class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded text-sm font-semibold hover:bg-red-700 transition">
                            Ya, Hapus
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

    <script>
        // ===== VALIDASI INPUT: Hanya huruf, angka, spasi, strip =====
        function validasiNama(input) {
            return /^[a-zA-Z0-9\s\-\.]+$/.test(input);
        }

        // ===== LIVE SEARCH =====
        (function() {
            const searchInput = document.getElementById('liveSearch');
            const searchLoading = document.getElementById('searchLoading');
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
                searchLoading.classList.remove('hidden');
                clearSearch.classList.add('hidden');

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

            const debouncedSearch = debounce(performSearch, 500);

            searchInput.addEventListener('input', debouncedSearch);

            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                performSearch();
                searchInput.focus();
            });

            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(debounceTimer);
                    performSearch();
                }
            });

            if (searchInput.value) {
                clearSearch.classList.remove('hidden');
            }
        })();

        // ===== TAMBAH TUJUAN =====
        function konfirmasiTambah() {
            const nama = document.getElementById('namaTambah').value.trim();
            if (!nama) {
                document.getElementById('errorTambah').textContent = 'Nama tujuan wajib diisi.';
                document.getElementById('errorTambah').classList.remove('hidden');
                return;
            }
            if (nama.length < 3) {
                document.getElementById('errorTambah').textContent = 'Nama minimal 3 karakter.';
                document.getElementById('errorTambah').classList.remove('hidden');
                return;
            }
            if (!validasiNama(nama)) {
                document.getElementById('errorTambah').textContent = 'Nama hanya boleh huruf, angka, spasi, dan strip.';
                document.getElementById('errorTambah').classList.remove('hidden');
                return;
            }
            document.getElementById('errorTambah').classList.add('hidden');
            document.getElementById('namaKonfirmasiTambah').textContent = nama;

            const modal = document.getElementById('tambahModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeTambahModal() {
            const modal = document.getElementById('tambahModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function submitTambah() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("tujuan.store") }}';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';

            const nama = document.createElement('input');
            nama.type = 'hidden';
            nama.name = 'nama';
            nama.value = document.getElementById('namaTambah').value;

            form.appendChild(csrf);
            form.appendChild(nama);
            document.body.appendChild(form);
            form.submit();
        }

        // ===== EDIT TUJUAN =====
        function openEditModal(id, kode, nama, status) {
            document.getElementById('editForm').action = `/tujuan/${id}`;
            document.getElementById('edit_kode').value = kode;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_status').value = status;

            const modal = document.getElementById('editModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function konfirmasiEdit() {
            const nama = document.getElementById('edit_nama').value.trim();
            if (!nama || nama.length < 3) return;
            if (!validasiNama(nama)) return;

            document.getElementById('namaKonfirmasiEdit').textContent = nama;
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

        function submitEdit() {
            document.getElementById('editForm').submit();
        }

        // ===== HAPUS TUJUAN =====
        function confirmDelete(id, nama) {
            document.getElementById('deleteForm').action = `/tujuan/${id}`;
            document.getElementById('delete_nama').textContent = nama;

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

        // Tutup modal dengan Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTambahModal();
                closeEditModal();
                closeKonfirmasiEditModal();
                closeDeleteModal();
            }
        });

        // Clear error saat mengetik
        document.getElementById('namaTambah').addEventListener('input', function() {
            document.getElementById('errorTambah').classList.add('hidden');
        });
    </script>

</x-layouts.dashboard>
