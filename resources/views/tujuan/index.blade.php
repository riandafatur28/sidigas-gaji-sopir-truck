<x-layouts.dashboard
    :title="'Kelola Tujuan'"
    :pageTitle="'Kelola Tujuan'"
    :user="auth()->user()">

    <!-- Header Section -->
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-pupr-blue mb-2">Kelola Data Tujuan 📍</h2>
        <p class="text-gray-600">Tambah, edit, dan hapus data tujuan pengiriman armada Anda.</p>
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

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-md p-5 border-l-4 border-pupr-blue">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold">Total Tujuan</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalTujuan }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-pupr-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-5 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold">Tujuan Aktif</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $tujuanAktif }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-5 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold">Tujuan Nonaktif</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ $tujuanNonaktif }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Tambah Tujuan -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border-t-4 border-pupr-yellow">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-pupr-blue">Tambah Tujuan Baru</h3>
                <p class="text-sm text-gray-500">Kode tujuan akan digenerate otomatis (TUJ-XXX)</p>
            </div>
            <div class="hidden sm:flex items-center space-x-2 bg-pupr-yellow/20 px-4 py-2 rounded-xl">
                <svg class="w-5 h-5 text-pupr-yellow-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-xs font-semibold text-pupr-yellow-dark">Auto Generate ID</span>
            </div>
        </div>

        <form id="formTambahTujuan" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Tujuan</label>
                <input type="text" id="namaTambah" required
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow focus:bg-white text-gray-900 outline-none transition-all"
                    placeholder="Masukkan nama tujuan...">
                <p class="text-red-500 text-xs font-medium mt-1 hidden" id="errorTambah"></p>
            </div>
            <div class="flex items-end">
                <button type="button" onclick="konfirmasiTambah()"
                    class="w-full sm:w-auto gradient-yellow text-pupr-blue font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl btn-press transition-all flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Tambah</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Data Tujuan -->
    <div class="bg-white rounded-2xl shadow-lg border-t-4 border-pupr-blue overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-pupr-blue">Daftar Tujuan</h3>
                    <p class="text-sm text-gray-500">Menampilkan {{ $tujuans->firstItem() ?? 0 }} - {{ $tujuans->lastItem() ?? 0 }} dari {{ $tujuans->total() }} data</p>
                </div>

                <!-- Live Search -->
                <div class="relative w-full sm:w-72">
                    <input type="text" id="liveSearch" value="{{ $search }}"
                        class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all text-sm"
                        placeholder="Ketik untuk mencari..." autocomplete="off">

                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>

                    <div id="searchLoading" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                        <svg class="w-5 h-5 text-pupr-yellow animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-200 rounded-full transition-colors" title="Hapus pencarian">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            @if($tujuans->count() > 0)
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode Tujuan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Tujuan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Ditambahkan</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($tujuans as $index => $tujuan)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-600 font-medium">{{ $tujuans->firstItem() + $index }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg bg-pupr-yellow/20 text-pupr-yellow-dark font-bold text-sm">
                                        {{ $tujuan->kode_tujuan }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 bg-pupr-blue rounded-full flex items-center justify-center shadow-sm">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900">{{ $tujuan->nama }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($tujuan->status == 'aktif')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $tujuan->created_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick="openEditModal({{ $tujuan->id }}, '{{ $tujuan->kode_tujuan }}', '{{ $tujuan->nama }}', '{{ $tujuan->status }}')"
                                            class="p-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-colors btn-press" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>

                                        <button onclick="confirmDelete({{ $tujuan->id }}, '{{ $tujuan->nama }}')"
                                            class="p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors btn-press" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center py-16">
                    <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <p class="text-gray-500 font-semibold">Belum ada data tujuan</p>
                    <p class="text-gray-400 text-sm mt-1">Tambahkan tujuan pertama Anda menggunakan form di atas.</p>
                </div>
            @endif
        </div>

        <!-- Pagination -->
        @if($tujuans->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-600">
                        Halaman {{ $tujuans->currentPage() }} dari {{ $tujuans->lastPage() }}
                    </p>

                    <div class="flex items-center space-x-2">
                        @if($tujuans->onFirstPage())
                            <span class="px-3 py-2 text-sm text-gray-400 bg-white border border-gray-200 rounded-lg cursor-not-allowed">
                                ← Sebelumnya
                            </span>
                        @else
                            <a href="{{ $tujuans->previousPageUrl() }}" class="px-3 py-2 text-sm text-pupr-blue bg-white border border-pupr-yellow hover:bg-pupr-yellow rounded-lg transition-colors font-semibold">
                                ← Sebelumnya
                            </a>
                        @endif

                        @foreach($tujuans->getUrlRange(1, $tujuans->lastPage()) as $page => $url)
                            @if($page == $tujuans->currentPage())
                                <span class="px-3 py-2 text-sm font-bold text-pupr-blue bg-pupr-yellow border border-pupr-yellow rounded-lg">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-200 hover:bg-pupr-yellow hover:text-pupr-blue hover:border-pupr-yellow rounded-lg transition-colors font-semibold">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach

                        @if($tujuans->hasMorePages())
                            <a href="{{ $tujuans->nextPageUrl() }}" class="px-3 py-2 text-sm text-pupr-blue bg-white border border-pupr-yellow hover:bg-pupr-yellow rounded-lg transition-colors font-semibold">
                                Selanjutnya →
                            </a>
                        @else
                            <span class="px-3 py-2 text-sm text-gray-400 bg-white border border-gray-200 rounded-lg cursor-not-allowed">
                                Selanjutnya →
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Konfirmasi Tambah -->
    <div id="tambahModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 overlay">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-pupr-yellow/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-pupr-yellow-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Konfirmasi Tambah Tujuan</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Anda akan menambahkan tujuan dengan nama:<br>
                    <strong id="namaKonfirmasiTambah" class="text-pupr-blue text-base"></strong><br>
                    <span class="text-xs text-gray-500 mt-1 block">Kode tujuan akan digenerate otomatis (TUJ-XXX)</span>
                </p>

                <div class="flex gap-3">
                    <button onclick="closeTambahModal()"
                        class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors btn-press">
                        Batal
                    </button>
                    <button onclick="submitTambah()"
                        class="flex-1 gradient-yellow text-pupr-blue font-bold py-3 rounded-xl shadow-lg hover:shadow-xl btn-press transition-all">
                        Ya, Tambah
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 overlay">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-pupr-yellow/10 to-pupr-yellow/5 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-pupr-blue">Edit Data Tujuan</h3>
                    <button onclick="closeEditModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <form id="editForm" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Kode Tujuan</label>
                    <input type="text" id="edit_kode" disabled
                        class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-xl text-gray-600 font-bold cursor-not-allowed">
                    <p class="text-xs text-gray-400 mt-1">Kode tidak dapat diubah</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Tujuan</label>
                    <input type="text" id="edit_nama" name="nama" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <select id="edit_status" name="status" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()"
                        class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors btn-press">
                        Batal
                    </button>
                    <button type="button" onclick="konfirmasiEdit()"
                        class="flex-1 gradient-yellow text-pupr-blue font-bold py-3 rounded-xl shadow-lg hover:shadow-xl btn-press transition-all">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Edit -->
    <div id="konfirmasiEditModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 overlay">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Konfirmasi Perubahan</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Anda yakin ingin memperbarui data tujuan:<br>
                    <strong id="namaKonfirmasiEdit" class="text-pupr-blue text-base"></strong>?
                </p>

                <div class="flex gap-3">
                    <button onclick="closeKonfirmasiEditModal()"
                        class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors btn-press">
                        Batal
                    </button>
                    <button onclick="submitEdit()"
                        class="flex-1 gradient-yellow text-pupr-blue font-bold py-3 rounded-xl shadow-lg hover:shadow-xl btn-press transition-all">
                        Ya, Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 overlay">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Data Tujuan?</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Anda yakin ingin menghapus <strong id="delete_nama" class="text-gray-900"></strong>?<br>
                    <span class="text-xs text-red-500 mt-1 block">Tindakan ini tidak dapat dibatalkan.</span>
                </p>

                <div class="flex gap-3">
                    <button onclick="closeDeleteModal()"
                        class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors btn-press">
                        Batal
                    </button>
                    <button onclick="submitDelete()"
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-xl shadow-lg btn-press transition-all">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Hapus (hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <script>
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
