<x-layouts.dashboard
    :title="'Kelola Periode'"
    :pageTitle="'Kelola Periode'"
    :user="auth()->user()">

    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-pupr-blue mb-2">Kelola Periode 📅</h2>
        <p class="text-gray-600">Atur periode kerja untuk mengelompokkan ritase sopir.</p>
    </div>

    <!-- Alert -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-xl flex items-start space-x-3">
            <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-green-700 text-sm font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl flex items-start space-x-3">
            <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-red-700 text-sm font-medium">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-md p-5 border-l-4 border-pupr-blue">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold">Total Periode</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalPeriode }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-pupr-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-5 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold">Periode Aktif</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $periodeAktif }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-5 border-l-4 border-gray-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold">Periode Selesai</p>
                    <p class="text-2xl font-bold text-gray-600 mt-1">{{ $periodeSelesai }}</p>
                </div>
                <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Tambah Periode -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border-t-4 border-pupr-yellow">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-pupr-blue">Tambah Periode Baru</h3>
                <p class="text-sm text-gray-500">Kode periode akan digenerate otomatis (PER-XXX)</p>
            </div>
        </div>

        <form id="formTambahPeriode" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Periode <span class="text-red-500">*</span></label>
                    <input type="text" id="nama_periode" name="nama_periode" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all"
                        placeholder="Contoh: Periode 1-7 Juli 2026">
                    <p class="text-red-500 text-xs font-medium mt-1 hidden" id="error_nama"></p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Mulai <span class="text-red-500">*</span></label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all">
                    <p class="text-red-500 text-xs font-medium mt-1 hidden" id="error_tanggal_mulai"></p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Selesai <span class="text-red-500">*</span></label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all">
                    <p class="text-red-500 text-xs font-medium mt-1 hidden" id="error_tanggal_selesai"></p>
                </div>

                <div class="flex items-end">
                    <button type="button" onclick="konfirmasiTambahPeriode()"
                        class="w-full gradient-yellow text-pupr-blue font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl btn-press transition-all flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Tambah</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabel Data Periode -->
    <div class="bg-white rounded-2xl shadow-lg border-t-4 border-pupr-blue overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-pupr-blue">Daftar Periode</h3>
                    <p class="text-sm text-gray-500">Menampilkan {{ $periodes->firstItem() ?? 0 }} - {{ $periodes->lastItem() ?? 0 }} dari {{ $periodes->total() }} data</p>
                </div>

                <!-- Live Search -->
                <div class="relative w-full sm:w-72">
                    <input type="text" id="liveSearch" value="{{ $search }}"
                        class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow focus:border-pupr-yellow text-gray-900 outline-none transition-all text-sm"
                        placeholder="Cari nama atau kode..." autocomplete="off">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-200 rounded-full">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            @if($periodes->count() > 0)
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">No</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Kode Periode</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Nama Periode</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Tanggal Mulai</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Tanggal Selesai</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Jumlah Ritase</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($periodes as $index => $periode)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-600 font-medium">{{ $periodes->firstItem() + $index }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg bg-pupr-yellow/20 text-pupr-yellow-dark font-bold text-sm">
                                        {{ $periode->kode_periode }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $periode->nama_periode }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $periode->tanggal_mulai->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $periode->tanggal_selesai->format('d M Y') }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
                                        {{ $periode->ritase->count() }} ritase
                                    </span>
                                </td>
                                <td class="px-6 py-4">
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
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick='openEditModal(@json($periode))'
                                            class="p-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-colors btn-press" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>

                                       <button onclick="confirmDelete({{ $periode->id }}, '{{ $periode->nama_periode }}
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-gray-500 font-semibold">Belum ada data periode</p>
                    <p class="text-gray-400 text-sm mt-1">Tambahkan periode pertama Anda menggunakan form di atas.</p>
                </div>
            @endif
        </div>

        <!-- Pagination -->
        @if($periodes->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-600">Halaman {{ $periodes->currentPage() }} dari {{ $periodes->lastPage() }}</p>
                    <div class="flex items-center space-x-2">
                        @if($periodes->onFirstPage())
                            <span class="px-3 py-2 text-sm text-gray-400 bg-white border border-gray-200 rounded-lg cursor-not-allowed">← Sebelumnya</span>
                        @else
                            <a href="{{ $periodes->previousPageUrl() }}" class="px-3 py-2 text-sm text-pupr-blue bg-white border border-pupr-yellow hover:bg-pupr-yellow rounded-lg transition-colors font-semibold">← Sebelumnya</a>
                        @endif
                        @foreach($periodes->getUrlRange(1, $periodes->lastPage()) as $page => $url)
                            @if($page == $periodes->currentPage())
                                <span class="px-3 py-2 text-sm font-bold text-pupr-blue bg-pupr-yellow border border-pupr-yellow rounded-lg">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-200 hover:bg-pupr-yellow hover:text-pupr-blue hover:border-pupr-yellow rounded-lg transition-colors font-semibold">{{ $page }}</a>
                            @endif
                        @endforeach
                        @if($periodes->hasMorePages())
                            <a href="{{ $periodes->nextPageUrl() }}" class="px-3 py-2 text-sm text-pupr-blue bg-white border border-pupr-yellow hover:bg-pupr-yellow rounded-lg transition-colors font-semibold">Selanjutnya →</a>
                        @else
                            <span class="px-3 py-2 text-sm text-gray-400 bg-white border border-gray-200 rounded-lg cursor-not-allowed">Selanjutnya →</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Konfirmasi Tambah -->
    <div id="tambahModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 overlay">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-pupr-yellow/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-pupr-yellow-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Konfirmasi Tambah Periode</h3>
                <div id="konfirmasiDetail" class="text-sm text-gray-600 mb-6 text-left bg-gray-50 p-4 rounded-xl"></div>
                <div class="flex gap-3">
                    <button onclick="closeTambahModal()" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors btn-press">Batal</button>
                    <button onclick="submitTambahPeriode()" class="flex-1 gradient-yellow text-pupr-blue font-bold py-3 rounded-xl shadow-lg hover:shadow-xl btn-press transition-all">Ya, Tambah</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 overlay">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-pupr-yellow/10 to-pupr-yellow/5 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-pupr-blue">Edit Data Periode</h3>
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
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Kode Periode</label>
                    <input type="text" id="edit_kode" disabled class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-xl text-gray-600 font-bold cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Periode <span class="text-red-500">*</span></label>
                    <input type="text" id="edit_nama" name="nama_periode" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow text-gray-900 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Mulai <span class="text-red-500">*</span></label>
                    <input type="date" id="edit_tanggal_mulai" name="tanggal_mulai" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow text-gray-900 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Selesai <span class="text-red-500">*</span></label>
                    <input type="date" id="edit_tanggal_selesai" name="tanggal_selesai" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow text-gray-900 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                    <select id="edit_status" name="status" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pupr-yellow text-gray-900 outline-none">
                        <option value="aktif">Aktif</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors btn-press">Batal</button>
                    <button type="button" onclick="konfirmasiEditPeriode()" class="flex-1 gradient-yellow text-pupr-blue font-bold py-3 rounded-xl shadow-lg hover:shadow-xl btn-press transition-all">Simpan</button>
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
                <p class="text-sm text-gray-600 mb-6">Anda yakin ingin memperbarui data periode ini?</p>
                <div class="flex gap-3">
                    <button onclick="closeKonfirmasiEditModal()" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors btn-press">Batal</button>
                    <button onclick="submitEditPeriode()" class="flex-1 gradient-yellow text-pupr-blue font-bold py-3 rounded-xl shadow-lg hover:shadow-xl btn-press transition-all">Ya, Simpan</button>
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
                <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Data Periode?</h3>
                <p id="delete_pesan" class="text-sm text-gray-600 mb-6"></p>
                <div class="flex gap-3">
                    <button onclick="closeDeleteModal()" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors btn-press">Batal</button>
                    <button onclick="submitDelete()" class="flex-1 bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-xl shadow-lg btn-press transition-all">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <script>
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
                document.querySelector('#deleteModal button.bg-red-500').disabled = true;
                document.querySelector('#deleteModal button.bg-red-500').classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                pesan.innerHTML = `Anda yakin ingin menghapus periode <strong class="text-gray-900">${nama}</strong>?<br><span class="text-xs text-red-500 mt-1 block">Tindakan ini tidak dapat dibatalkan.</span>`;
                document.querySelector('#deleteModal button.bg-red-500').disabled = false;
                document.querySelector('#deleteModal button.bg-red-500').classList.remove('opacity-50', 'cursor-not-allowed');
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
