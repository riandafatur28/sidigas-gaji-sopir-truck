<x-layouts.dashboard
    :title="'Data Gaji'"
    :pageTitle="'Data Gaji'"
>

    @push('styles')
    <style>
        .skeleton-row td { padding: 12px 16px !important; }
        .skeleton-box {
            display: inline-block; height: 14px; border-radius: 4px;
            background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s infinite ease-in-out;
        }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .skeleton-avatar { width: 32px; height: 32px; border-radius: 9999px; }
        .skeleton-name { width: 100px; }
        .skeleton-code { width: 60px; height: 10px; margin-top: 4px; }
        .skeleton-number { width: 50px; }
        .skeleton-rit { width: 24px; }
        .skeleton-btn { width: 48px; height: 24px; border-radius: 4px; display: inline-block; }
    </style>
    @endpush

    {{-- HEADER --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold" style="color:var(--text)">Data Gaji</h1>
                <p class="text-sm mt-1" style="color:var(--text-muted)">Input BBM, Upah per tujuan, dan Kompensasi Gagal Produksi</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4 p-4 bg-green-100 text-green-700 rounded">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-4 p-4 bg-red-100 text-red-700 rounded">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error mb-4 p-4 bg-red-100 text-red-700 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <p class="text-xs mt-2 text-red-600 font-medium">⚠️ Isian yang sudah Anda masukkan di bawah tetap tersimpan. Cukup perbaiki bagian yang salah dan klik Simpan lagi.</p>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- FORM INPUT PER TUJUAN --}}
    {{-- ============================================================ --}}
    <div id="formInputContainer" class="w-full border border-gray-200 rounded mb-6 overflow-hidden bg-white">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Input Biaya Per Tujuan</p>
        </div>

        <form id="formGaji" action="{{ route('gaji.store') }}" method="POST">
            @csrf
            <div class="p-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Periode <span class="text-red-500">*</span></label>
                    @php
                        $selectedPeriodeValue = old('periode', old('periode_id', $periodeId ?? ''));
                    @endphp
                    <select name="periode" id="pilih_periode" class="form-input form-select w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-[#2d6a4f] focus:border-[#2d6a4f]" onchange="window.location.href='{{ route('gaji.index') }}?periode='+this.value">
                        <option value="">Pilih Periode</option>
                        @foreach($periodesForDropdown ?? [] as $periode)
                            <option value="{{ $periode->id }}" {{ (string) $selectedPeriodeValue === (string) $periode->id ? 'selected' : '' }}>
                                {{ $periode->nama_periode }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">*Pilih periode untuk melihat dan mengedit data gaji</p>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Detail Biaya Per Tujuan</p>

                    <div class="table-responsive border border-gray-200 rounded-lg">
                        <table class="w-full">
                            <thead style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                                <tr>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Tujuan</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">BBM/Rit</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Upah/Rit</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Kompensasi/Rit Gagal</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Tol</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Lembur</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($allTujuans as $tujuan)
                                @php
                                    $i = $loop->index;

                                    // PERBAIKAN UTAMA: Ambil array 'detail' dari old(), fallback ke array kosong
                                    $oldDetails = old('detail', []);

                                    // Akses menggunakan array index, JAUH lebih stabil daripada dot notation
                                    $oldBbm    = isset($oldDetails[$i]['bbm_per_rit']) ? $oldDetails[$i]['bbm_per_rit'] : 0;
                                    $oldUpah   = isset($oldDetails[$i]['upah_per_rit']) ? $oldDetails[$i]['upah_per_rit'] : 0;
                                    $oldKomp   = isset($oldDetails[$i]['kompensasi_gagal']) ? $oldDetails[$i]['kompensasi_gagal'] : 0;
                                    $oldTol    = isset($oldDetails[$i]['tol_per_rit']) ? $oldDetails[$i]['tol_per_rit'] : 0;
                                    $oldLembur = isset($oldDetails[$i]['lembur_per_rit']) ? $oldDetails[$i]['lembur_per_rit'] : 0;

                                    $tolChecked = (float)$oldTol > 0 ? 'checked' : '';
                                    $tolDisabled = (float)$oldTol > 0 ? '' : 'disabled';
                                    $tolClass = (float)$oldTol > 0 ? 'bg-white' : 'bg-gray-100 opacity-50 cursor-not-allowed';

                                    $lemburChecked = (float)$oldLembur > 0 ? 'checked' : '';
                                    $lemburDisabled = (float)$oldLembur > 0 ? '' : 'disabled';
                                    $lemburClass = (float)$oldLembur > 0 ? 'bg-white' : 'bg-gray-100 opacity-50 cursor-not-allowed';
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-sm font-medium text-gray-800">{{ $tujuan->nama }}</p>
                                        <p class="text-xs text-gray-400">{{ $tujuan->kode_tujuan }}</p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="w-32">
                                            <input type="number" name="detail[{{ $i }}][bbm_per_rit]" data-tujuan="{{ $tujuan->kode_tujuan }}" data-field="bbm_per_rit" min="0" step="0.01" value="{{ $oldBbm }}" placeholder="0" class="w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white">
                                            <p class="text-red-500 text-xs mt-1 hidden" id="error_bbm_{{ $i }}">Harus angka positif.</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="w-32">
                                            <input type="number" name="detail[{{ $i }}][upah_per_rit]" data-tujuan="{{ $tujuan->kode_tujuan }}" data-field="upah_per_rit" min="0" step="0.01" value="{{ $oldUpah }}" placeholder="0" class="w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white">
                                            <p class="text-red-500 text-xs mt-1 hidden" id="error_upah_{{ $i }}">Harus angka positif.</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="w-32">
                                            <input type="number" name="detail[{{ $i }}][kompensasi_gagal]" data-tujuan="{{ $tujuan->kode_tujuan }}" data-field="kompensasi_gagal" min="0" step="0.01" value="{{ $oldKomp }}" placeholder="0" class="w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white">
                                            <p class="text-red-500 text-xs mt-1 hidden" id="error_komp_{{ $i }}">Harus angka positif.</p>
                                            <input type="hidden" name="detail[{{ $i }}][kode_tujuan]" value="{{ $tujuan->kode_tujuan }}">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2 w-40">
                                            <input type="checkbox" data-tujuan="{{ $tujuan->kode_tujuan }}" data-field="tol_check" class="tol-checkbox w-4 h-4 rounded border-gray-300 cursor-pointer" {{ $tolChecked }}>
                                            <input type="number" name="detail[{{ $i }}][tol_per_rit]" data-tujuan="{{ $tujuan->kode_tujuan }}" data-field="tol_per_rit" min="0" step="0.01" value="{{ $oldTol }}" placeholder="0" {{ $tolDisabled }} class="tol-input w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition {{ $tolClass }}">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2 w-40">
                                            <input type="checkbox" data-tujuan="{{ $tujuan->kode_tujuan }}" data-field="lembur_tujuan_check" class="lembur-tujuan-checkbox w-4 h-4 rounded border-gray-300 cursor-pointer" {{ $lemburChecked }}>
                                            <input type="number" name="detail[{{ $i }}][lembur_per_rit]" data-tujuan="{{ $tujuan->kode_tujuan }}" data-field="lembur_per_rit" min="0" step="1" value="{{ $oldLembur }}" placeholder="0" {{ $lemburDisabled }} class="lembur-tujuan-input w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition {{ $lemburClass }}">
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-end gap-3">
                        <a href="{{ route('gaji.index') }}" class="border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</a>
                        <button type="button" onclick="showKonfirmasi()" class="bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition">
                            Simpan Gaji
                        </button>
                    </div>
                </div>
            </div>
            <input type="hidden" name="periode_id" id="formPeriodeId" value="{{ $selectedPeriodeValue }}">
        </form>
    </div>

    {{-- ============================================================ --}}
    {{-- SUMMARY CARDS --}}
    {{-- ============================================================ --}}
    <div id="summaryContainer" class="hidden mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Gaji</p>
                <p id="summaryGrandTotal" class="text-lg font-bold text-gray-900 mt-1">Rp 0</p>
            </div>
            <div class="bg-white rounded border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Upah</p>
                <p id="summaryUpah" class="text-lg font-bold text-gray-900 mt-1">Rp 0</p>
            </div>
            <div class="bg-white rounded border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Solar</p>
                <p id="summarySolar" class="text-lg font-bold text-gray-900 mt-1">Rp 0</p>
            </div>
            <div class="bg-white rounded border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total DT</p>
                <p id="summaryDT" class="text-lg font-bold text-gray-900 mt-1">Rp 0</p>
            </div>
            <div class="bg-white rounded border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Kompensasi</p>
                <p id="summaryKompensasi" class="text-lg font-bold text-gray-900 mt-1">Rp 0</p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 mt-3">
            <div class="relative" id="gajiFilterWrap">
                <button onclick="toggleGajiFilter()" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium bg-white hover:bg-gray-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                    <span class="hidden w-2 h-2 rounded-full bg-green-500" id="gajiFilterBadge"></span>
                </button>
                <div class="hidden absolute right-0 mt-2 w-72 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4" id="gajiFilterPanel">
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Filter Tanggal</label>
                            <input type="date" id="filterTanggal" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                        </div>
                        <div class="flex gap-2">
                            <button onclick="applySearch()" class="flex-1 px-4 py-2 bg-[#2d6a4f] text-white rounded text-sm hover:opacity-90 transition">Terapkan</button>
                            <button onclick="clearGajiFilter()" class="px-4 py-2 border border-gray-200 rounded text-sm text-gray-600 hover:bg-gray-50 transition">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
            <p id="summarySopirCount" class="text-xs text-gray-400"></p>
        </div>
        <script>
        function toggleGajiFilter(){const p=document.getElementById('gajiFilterPanel');p.classList.toggle('hidden');}
        document.addEventListener('click',function(e){const w=document.getElementById('gajiFilterWrap');if(w&&!w.contains(e.target)){document.getElementById('gajiFilterPanel').classList.add('hidden');}});
        function clearGajiFilter(){document.getElementById('filterTanggal').value='';applySearch();}
        </script>
    </div>

    {{-- ============================================================ --}}
    {{-- TABEL PER SOPIR --}}
    {{-- ============================================================ --}}
    <div id="tabelGajiContainer" class="hidden">
        <div class="card mb-6 bg-white border border-gray-200 rounded-lg">
            <div class="table-responsive">
                <table class="w-full">
                    <thead>
                        <tr style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                            <th class="text-left text-xs font-semibold uppercase tracking-wider px-5 py-3 whitespace-nowrap" style="color:var(--text-muted)" colspan="8">
                                Rincian Gaji Per Sopir
                                <span class="font-normal text-gray-400 text-xs ml-2" id="periodeLabel">Periode: -</span>
                            </th>
                            <th class="text-right text-xs font-semibold uppercase tracking-wider px-5 py-3 whitespace-nowrap" style="color:var(--text-muted)">
                                <a id="downloadSlipBtn" href="{{ $periodeId ? url('/gaji/slip-pdf/' . $periodeId) : '#' }}" class="text-xs text-gray-600 border border-gray-200 px-3 py-1.5 rounded hover:bg-gray-50 font-medium {{ $periodeId ? '' : 'hidden' }}">
                                    Download Slip PDF
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <thead style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                        <tr>
                            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Sopir</th>
                            <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Total Rit</th>
                            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Total Solar</th>
                            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Total Upah</th>
                            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Total DT</th>
                            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Tol</th>
                            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Kompensasi</th>
                            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Lembur</th>
                            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Grand Total</th>
                            <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-3 whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tabelGajiBody" class="divide-y divide-gray-100">
                        <!-- Data akan diisi oleh JavaScript -->
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200" id="gajiTableFoot">
                        <tr>
                            <td colspan="9" class="px-5 py-3 text-right text-sm font-semibold text-gray-700 whitespace-nowrap">TOTAL KESELURUHAN:</td>
                            <td class="px-5 py-3 text-right text-sm font-bold text-gray-900 whitespace-nowrap" id="grandTotalAll">Rp 0</td>
                        </tr>
                        <tr id="paginationGajiRow" class="border-t border-gray-200 hidden">
                            <td colspan="8" class="px-5 py-3">
                                <div id="paginationGaji" class="flex items-center justify-between"></div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL KONFIRMASI --}}
    {{-- ============================================================ --}}
    <div id="konfirmasiModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded border border-gray-200 w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Simpan Gaji</h3>
                    <button onclick="closeKonfirmasiModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="konfirmasiDetail" class="text-sm text-gray-600 mb-4 bg-gray-50 p-4 rounded max-h-60 overflow-y-auto"></div>
                <div class="flex gap-3">
                    <button onclick="closeKonfirmasiModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                    <button onclick="submitGaji()" class="flex-1 bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition">Ya, Simpan</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const allTujuans = @json($allTujuans ?? []);

        // PERBAIKAN JS: Cek apakah ada error validasi. Jika YA, JavaScript DILARANG mengubah nilai input.
        const hasErrors = @json($errors->any() ? true : false);

        let gajiData = [];
        let gajiDataAll = [];
        let formDataGaji = null;
        let periodeId = null;
        let currentPage = 1;
        const pageSize = 10;

        function validasiNominal(input) {
            return /^\d+(\.\d+)?$/.test(input) && parseFloat(input) >= 0;
        }

        function formatRupiah(angka) {
            return Math.round(angka).toLocaleString('id-ID');
        }

        (function() {
            'use strict';
            const urlParams = new URLSearchParams(window.location.search);
            const periodeFromUrl = urlParams.get('periode');
            const periodeSelect = document.getElementById('pilih_periode');

            if (periodeFromUrl && !hasErrors && periodeSelect) {
                periodeSelect.value = periodeFromUrl;
                periodeId = periodeFromUrl;
                const formPeriodeInput = document.getElementById('formPeriodeId');
                if (formPeriodeInput) formPeriodeInput.value = periodeFromUrl;
            } else if (periodeSelect && periodeSelect.value) {
                periodeId = periodeSelect.value;
            }

            const filterTanggalEl = document.getElementById('filterTanggal');
            if (filterTanggalEl) {
                filterTanggalEl.addEventListener('change', function() {
                    if (periodeId) loadGajiData(periodeId);
                });
            }

            const searchSopirEl = document.getElementById('searchSopirTujuan');
            if (searchSopirEl) {
                searchSopirEl.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') applySearch();
                });
            }

            document.addEventListener('input', function(e) {
                if (e.target.matches('input[data-field="bbm_per_rit"], input[data-field="upah_per_rit"], input[data-field="tol_per_rit"], input[data-field="lembur_per_rit"], input[data-field="kompensasi_gagal"]')) {
                    if (gajiData.length > 0) renderTabelGaji(gajiData);
                }
            });

            document.querySelectorAll('.tol-checkbox').forEach(function(cb) {
                cb.addEventListener('change', function() {
                    const row = this.closest('tr');
                    const input = row.querySelector('.tol-input');
                    if (this.checked) {
                        input.disabled = false;
                        input.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                        input.classList.add('bg-white');
                        input.focus();
                    } else {
                        input.disabled = true;
                        input.value = '0';
                        input.classList.remove('bg-white');
                        input.classList.add('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                        if (gajiData.length > 0) renderTabelGaji(gajiData);
                    }
                });
            });

            document.querySelectorAll('.lembur-tujuan-checkbox').forEach(function(cb) {
                cb.addEventListener('change', function() {
                    const row = this.closest('tr');
                    const input = row.querySelector('.lembur-tujuan-input');
                    if (this.checked) {
                        input.disabled = false;
                        input.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                        input.classList.add('bg-white');
                        input.focus();
                    } else {
                        input.disabled = true;
                        input.value = '0';
                        input.classList.remove('bg-white');
                        input.classList.add('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                        if (gajiData.length > 0) renderTabelGaji(gajiData);
                    }
                });
            });

            if (periodeId) {
                loadGajiData(periodeId);
            }
        })();

        function loadGajiData(periodeId) {
            const container = document.getElementById('tabelGajiContainer');
            const sumContainer = document.getElementById('summaryContainer');
            const tbody = document.getElementById('tabelGajiBody');
            const periodeLabel = document.getElementById('periodeLabel');
            const periodeSelect = document.getElementById('pilih_periode');

            if (!container || !tbody) return;

            container.classList.remove('hidden');
            if (sumContainer) sumContainer.classList.remove('hidden');
            tbody.innerHTML = renderSkeleton(6);

            if (periodeSelect && periodeSelect.options[periodeSelect.selectedIndex]) {
                const periodeText = periodeSelect.options[periodeSelect.selectedIndex].text;
                if (periodeLabel) periodeLabel.textContent = 'Periode: ' + periodeText;
            }

            let url = '/api/get-ritase-data?periode=' + periodeId;
            const filterTanggalEl = document.getElementById('filterTanggal');
            if (filterTanggalEl && filterTanggalEl.value) {
                url += '&tanggal=' + encodeURIComponent(filterTanggalEl.value);
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Gagal memuat data');
                    return response.json();
                })
                .then(data => {
                    if (data.sopir.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="10" class="px-4 py-8 text-center text-gray-500 whitespace-nowrap">Tidak ada data ritase untuk periode ini</td></tr>`;
                        const grandTotalAllEl = document.getElementById('grandTotalAll');
                        if (grandTotalAllEl) grandTotalAllEl.textContent = 'Rp 0';
                        return;
                    }

                    gajiData = data.sopir;
                    gajiDataAll = data.sopir;
                    currentPage = 1;

                    // PERBAIKAN JS: JANGAN timpa nilai form jika ada error validasi (hasErrors === true)
                    if (data.default_rates && !hasErrors) {
                        Object.keys(data.default_rates).forEach(function(kodeTujuan) {
                            const rate = data.default_rates[kodeTujuan];
                            const bbmInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="bbm_per_rit"]`);
                            const upahInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="upah_per_rit"]`);

                            if (bbmInput && parseFloat(bbmInput.value) === 0) bbmInput.value = rate.bbm_per_rit;
                            if (upahInput && parseFloat(upahInput.value) === 0) upahInput.value = rate.upah_per_rit;

                            if (rate.tol_per_rit && parseFloat(rate.tol_per_rit) > 0) {
                                const tolInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="tol_per_rit"]`);
                                const tolCheck = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="tol_check"]`);
                                if (tolInput && tolCheck && !tolCheck.checked) {
                                    tolInput.value = rate.tol_per_rit;
                                    tolCheck.checked = true;
                                    tolInput.disabled = false;
                                    tolInput.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                                    tolInput.classList.add('bg-white');
                                }
                            }
                            if (rate.kompensasi_gagal) {
                                const kompInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="kompensasi_gagal"]`);
                                if (kompInput && parseFloat(kompInput.value) === 0) kompInput.value = rate.kompensasi_gagal;
                            }
                            const lemburTujuanVal = rate.lembur_per_rit || 0;
                            if (parseFloat(lemburTujuanVal) > 0) {
                                const lemburTujuanInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="lembur_per_rit"]`);
                                const lemburTujuanCheck = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="lembur_tujuan_check"]`);
                                if (lemburTujuanInput && lemburTujuanCheck && !lemburTujuanCheck.checked) {
                                    lemburTujuanInput.value = lemburTujuanVal;
                                    lemburTujuanCheck.checked = true;
                                    lemburTujuanInput.disabled = false;
                                    lemburTujuanInput.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                                    lemburTujuanInput.classList.add('bg-white');
                                }
                            }
                        });
                    }

                    renderTabelGaji(data.sopir);
                    updateSummary(data.sopir);
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = `<tr><td colspan="10" class="px-4 py-8 text-center text-red-500 whitespace-nowrap">${error.message || 'Gagal memuat data'}</td></tr>`;
                });
        }

        function renderTabelGaji(data) {
            const tbody = document.getElementById('tabelGajiBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            let grandTotalAll = 0;

            const bbmByTujuan = {}, upahByTujuan = {}, kompensasiByTujuan = {}, tolByTujuan = {}, lemburByTujuan = {};
            document.querySelectorAll('input[data-field="bbm_per_rit"]').forEach(inp => bbmByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0);
            document.querySelectorAll('input[data-field="upah_per_rit"]').forEach(inp => upahByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0);
            document.querySelectorAll('input[data-field="kompensasi_gagal"]').forEach(inp => kompensasiByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0);
            document.querySelectorAll('input[data-field="tol_per_rit"]').forEach(inp => tolByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0);
            document.querySelectorAll('input[data-field="lembur_per_rit"]').forEach(inp => lemburByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0);

            const gagalCountsBySopir = {};
            data.forEach(function(sopir) {
                gagalCountsBySopir[sopir.kode_sopir] = {};
                (sopir.gagal_rits || []).forEach(function(rit) {
                    gagalCountsBySopir[sopir.kode_sopir][rit.kode_tujuan] = (gagalCountsBySopir[sopir.kode_sopir][rit.kode_tujuan] || 0) + 1;
                });
            });

            const totalPages = Math.ceil(data.length / pageSize);
            if (currentPage > totalPages) currentPage = Math.max(1, totalPages);
            const start = (currentPage - 1) * pageSize;
            const pageData = data.slice(start, start + pageSize);

            pageData.forEach((sopir, idx) => {
                const index = start + idx;
                const totalRit = Object.values(sopir.rit_per_tujuan).reduce((s, item) => s + item.total_rit, 0);

                let totalSolar = 0, totalUpah = 0, totalTol = 0, totalLembur = 0;
                Object.keys(sopir.rit_per_tujuan).forEach(function(kodeTujuan) {
                    const rit = sopir.rit_per_tujuan[kodeTujuan].total_rit;
                    totalSolar += (bbmByTujuan[kodeTujuan] || 0) * rit;
                    totalUpah += (upahByTujuan[kodeTujuan] || 0) * rit;
                    totalTol += (tolByTujuan[kodeTujuan] || 0) * rit;
                    totalLembur += (lemburByTujuan[kodeTujuan] || 0) * rit;
                });

                if (totalSolar === 0 && totalUpah === 0 && !sopir.belum_dihitung) {
                    totalSolar = sopir.total_solar || 0;
                    totalUpah = sopir.total_upah || 0;
                    totalTol = sopir.total_tol || 0;
                    if (totalLembur === 0) totalLembur = sopir.upah_lembur || 0;
                }

                const totalDT = sopir.total_dt || 0;
                let totalKompensasi = 0;
                Object.keys(kompensasiByTujuan).forEach(function(kodeTujuan) {
                    const kompPerRit = kompensasiByTujuan[kodeTujuan] || 0;
                    if (kompPerRit > 0) {
                        const sopirGagal = (gagalCountsBySopir[sopir.kode_sopir] || {})[kodeTujuan] || 0;
                        if (sopirGagal > 0) totalKompensasi += kompPerRit * sopirGagal;
                    }
                });
                if (totalKompensasi === 0 && !sopir.belum_dihitung) totalKompensasi = sopir.total_kompensasi || 0;

                const previewGrand = totalSolar + totalUpah + totalDT + totalTol + totalKompensasi + totalLembur;
                grandTotalAll += previewGrand;
                const firstChar = sopir.nama_sopir ? sopir.nama_sopir.charAt(0).toUpperCase() : '?';

                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                row.id = `row_${sopir.kode_sopir}`;
                row.innerHTML = `
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-gray-700 font-bold text-xs">${firstChar}</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">${sopir.nama_sopir}</p>
                                <p class="text-xs text-gray-500">${sopir.kode_sopir}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-semibold whitespace-nowrap">${totalRit}</td>
                    <td class="px-4 py-3 text-right text-gray-800 font-medium whitespace-nowrap">Rp ${formatRupiah(totalSolar)}</td>
                    <td class="px-4 py-3 text-right text-gray-800 font-medium whitespace-nowrap">Rp ${formatRupiah(totalUpah)}</td>
                    <td class="px-4 py-3 text-right text-gray-800 font-medium whitespace-nowrap">Rp ${formatRupiah(totalDT)}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap"><span class="text-gray-800 font-medium" id="tolTotal_${sopir.kode_sopir}">Rp ${formatRupiah(totalTol)}</span></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap"><span class="text-gray-800 font-medium" id="kompTotal_${sopir.kode_sopir}">Rp ${formatRupiah(totalKompensasi)}</span></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap"><span class="text-gray-800 font-medium" id="lemburTotal_${sopir.kode_sopir}">Rp ${formatRupiah(totalLembur)}</span></td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900 whitespace-nowrap" id="grandTotal_${sopir.kode_sopir}">Rp ${formatRupiah(previewGrand)}</td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <button onclick="showDetail(${index})" class="text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium">Detail &amp; Slip</button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            const grandTotalAllEl = document.getElementById('grandTotalAll');
            if (grandTotalAllEl) grandTotalAllEl.textContent = 'Rp ' + formatRupiah(grandTotalAll);

            renderPagination(totalPages);
            updateSummary(gajiData);
        }

        function renderSkeleton(rows) {
            const w = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;
            let h = '';
            for (let r = 0; r < rows; r++) {
                h += `<tr class="skeleton-row">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center space-x-2">
                            <div class="skeleton-box skeleton-avatar flex-shrink-0"></div>
                            <div>
                                <div class="skeleton-box skeleton-name" style="width:${w(70,130)}px"></div>
                                <div class="skeleton-box skeleton-code"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap"><div class="skeleton-box skeleton-rit" style="margin:0 auto"></div></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap"><div class="skeleton-box skeleton-number" style="width:${w(60,90)}px;margin-left:auto"></div></td>
                    <td class="px-4 py-3 text-center whitespace-nowrap"><div class="skeleton-box skeleton-btn" style="margin:0 auto"></div></td>
                </tr>`;
            }
            return h;
        }

        function goToPage(page) {
            currentPage = page;
            renderTabelGaji(gajiData);
        }

        function renderPagination(totalPages) {
            const container = document.getElementById('paginationGaji');
            if (!container) return;
            if (totalPages <= 1) {
                container.innerHTML = '';
                const paginationRow = document.getElementById('paginationGajiRow');
                if (paginationRow) paginationRow.classList.add('hidden');
                return;
            }

            let html = '<div class="flex items-center justify-between w-full gap-3">';
            html += '<p class="text-sm text-gray-600 whitespace-nowrap">Halaman ' + currentPage + ' dari ' + totalPages + '</p>';
            html += '<div class="flex items-center space-x-1.5">';

            if (currentPage <= 1) html += '<span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Sebelumnya</span>';
            else html += '<a href="#" onclick="goToPage(' + (currentPage - 1) + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Sebelumnya</a>';

            const w = 2;
            let ss = Math.max(1, currentPage - w);
            let ee = Math.min(totalPages, currentPage + w);

            if (ss > 1) {
                html += '<a href="#" onclick="goToPage(1); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">1</a>';
                if (ss > 2) html += '<span class="px-3 py-1.5 text-sm text-gray-400">...</span>';
            }

            for (let p = ss; p <= ee; p++) {
                if (p == currentPage) html += '<span class="px-3 py-1.5 text-sm font-bold text-white bg-[#2d6a4f] border border-[#2d6a4f] rounded">' + p + '</span>';
                else html += '<a href="#" onclick="goToPage(' + p + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">' + p + '</a>';
            }

            if (ee < totalPages) {
                if (ee < totalPages - 1) html += '<span class="px-3 py-1.5 text-sm text-gray-400">...</span>';
                html += '<a href="#" onclick="goToPage(' + totalPages + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">' + totalPages + '</a>';
            }

            if (currentPage >= totalPages) html += '<span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Selanjutnya</span>';
            else html += '<a href="#" onclick="goToPage(' + (currentPage + 1) + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Selanjutnya</a>';

            html += '</div></div>';
            container.innerHTML = html;

            const paginationRow = document.getElementById('paginationGajiRow');
            if (paginationRow) paginationRow.classList.remove('hidden');
        }

        function updateSummary(data) {
            var totalGrand = 0, totalUpah = 0, totalSolar = 0, totalDT = 0, totalKomp = 0;
            data.forEach(function(s) {
                const row = document.getElementById('row_' + s.kode_sopir);
                if (row) {
                    const cells = row.querySelectorAll('td');
                    const gt = document.getElementById('grandTotal_' + s.kode_sopir);
                    totalGrand += gt ? (parseFloat(gt.textContent.replace(/[^0-9]/g, '')) || 0) : (s.grand_total || 0);
                    totalSolar += parseFloat(cells[2]?.textContent?.replace(/[^0-9]/g, '')) || 0;
                    totalUpah += parseFloat(cells[3]?.textContent?.replace(/[^0-9]/g, '')) || 0;
                    totalDT += parseFloat(cells[4]?.textContent?.replace(/[^0-9]/g, '')) || 0;
                    totalKomp += parseFloat(cells[6]?.textContent?.replace(/[^0-9]/g, '')) || 0;
                } else {
                    totalGrand += s.grand_total || 0;
                    totalUpah += s.total_upah || 0;
                    totalSolar += s.total_solar || 0;
                    totalDT += s.total_dt || 0;
                    totalKomp += s.total_kompensasi || 0;
                }
            });

            const elGrand = document.getElementById('summaryGrandTotal');
            if (elGrand) elGrand.textContent = 'Rp ' + formatRupiah(totalGrand);
            const elUpah = document.getElementById('summaryUpah');
            if (elUpah) elUpah.textContent = 'Rp ' + formatRupiah(totalUpah);
            const elSolar = document.getElementById('summarySolar');
            if (elSolar) elSolar.textContent = 'Rp ' + formatRupiah(totalSolar);
            const elDT = document.getElementById('summaryDT');
            if (elDT) elDT.textContent = 'Rp ' + formatRupiah(totalDT);
            const elKomp = document.getElementById('summaryKompensasi');
            if (elKomp) elKomp.textContent = 'Rp ' + formatRupiah(totalKomp);
            const elCount = document.getElementById('summarySopirCount');
            if (elCount) elCount.textContent = data.length + ' sopir';
        }

        function applySearch() {
            if (periodeId) loadGajiData(periodeId);
        }

        function clearGajiFilter() {
            const filterEl = document.getElementById('filterTanggal');
            if (filterEl) filterEl.value = '';
            applySearch();
        }

        function toggleGajiFilter() {
            const p = document.getElementById('gajiFilterPanel');
            if (p) p.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            const w = document.getElementById('gajiFilterWrap');
            if (w && !w.contains(e.target)) {
                const p = document.getElementById('gajiFilterPanel');
                if (p) p.classList.add('hidden');
            }
        });

        function showDetail(index) {
            const sopir = gajiData[index];
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="bg-white rounded border border-gray-200 w-full max-w-4xl max-h-[90vh] overflow-y-auto p-4" onclick="event.stopPropagation()">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-semibold text-gray-900">Slip Gaji ${sopir.nama_sopir}</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div id="slipContent" class="text-center text-gray-500 py-8">Loading slip...</div>
                </div>
            `;
            document.body.appendChild(modal);

            fetch('/gaji/slip/' + periodeId + '/' + sopir.kode_sopir)
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const styles = doc.querySelectorAll('style');
                    let styleHtml = '';
                    styles.forEach(s => {
                        let css = s.textContent;
                        css = css.replace(/@page\s*\{[^}]*\}/g, '').replace(/(?:^|\n)\s*\*\s*\{[^}]*\}/g, '').replace(/(?:^|\n)\s*html\s*\{[^}]*\}/g, '').replace(/(?:^|\n)\s*body\s*\{[^}]*\}/g, '');
                        if (css.trim()) styleHtml += '<style>' + css + '</style>';
                    });
                    const containers = doc.querySelectorAll('.slip-container');
                    let slipHtml = '';
                    containers.forEach(c => slipHtml += c.outerHTML);
                    document.getElementById('slipContent').innerHTML = styleHtml + (slipHtml || '<p class="text-gray-500">Tidak ada data slip</p>');
                })
                .catch(() => {
                    document.getElementById('slipContent').innerHTML = '<p class="text-red-500">Gagal memuat slip</p>';
                });
        }

        function showKonfirmasi() {
            const periode = document.getElementById('pilih_periode').value;
            if (!periode) { alert('Silakan pilih Periode terlebih dahulu!'); return; }

            let hasEmpty = false, hasInvalid = false;
            ['bbm_per_rit', 'upah_per_rit'].forEach(field => {
                document.querySelectorAll(`input[data-field="${field}"]`).forEach((input, i) => {
                    const errorEl = document.getElementById(`error_${field.split('_')[0]}_${i}`);
                    if (input.value === '' || parseFloat(input.value) < 0) {
                        hasEmpty = true; input.classList.add('border-red-500');
                        if (errorEl) { errorEl.textContent = 'Wajib diisi.'; errorEl.classList.remove('hidden'); }
                    } else if (!validasiNominal(input.value)) {
                        hasInvalid = true; input.classList.add('border-red-500');
                        if (errorEl) { errorEl.textContent = 'Harus angka positif.'; errorEl.classList.remove('hidden'); }
                    } else {
                        input.classList.remove('border-red-500');
                        if (errorEl) errorEl.classList.add('hidden');
                    }
                });
            });

            if (hasEmpty) { alert('Silakan isi BBM/Rit dan Upah/Rit untuk semua tujuan!'); return; }
            if (hasInvalid) { alert('Nilai harus berupa angka positif!'); return; }

            formDataGaji = new FormData(document.getElementById('formGaji'));
            const periodeSelect = document.getElementById('pilih_periode');
            const periodeText = periodeSelect.options[periodeSelect.selectedIndex].text;

            let detailHtml = `<div class="space-y-2"><div class="flex justify-between"><span class="text-gray-500">Periode:</span><span class="font-semibold text-gray-900">${periodeText}</span></div><div class="border-t pt-2 mt-2"><p class="text-xs text-gray-500">Detail Biaya per Tujuan:</p>`;

            document.querySelectorAll('input[data-field="bbm_per_rit"]').forEach(input => {
                const kode = input.dataset.tujuan;
                const nama = allTujuans.find(t => t.kode_tujuan === kode)?.nama || kode;
                const bbm = input.value || '0';
                const upah = document.querySelector(`input[data-tujuan="${kode}"][data-field="upah_per_rit"]`)?.value || '0';
                detailHtml += `<div class="flex justify-between text-sm py-1"><span class="font-medium">${nama}</span><span class="text-gray-600">BBM: Rp ${formatRupiah(bbm)} | Upah: Rp ${formatRupiah(upah)}</span></div>`;
            });
            detailHtml += `</div><div class="border-t pt-2 mt-2 text-xs text-gray-500">Data akan dihitung ulang berdasarkan ritase yang ada.</div></div>`;

            document.getElementById('konfirmasiDetail').innerHTML = detailHtml;
            const modal = document.getElementById('konfirmasiModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeKonfirmasiModal() {
            const modal = document.getElementById('konfirmasiModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        function submitGaji() {
            if (formDataGaji) {
                closeKonfirmasiModal();
                document.getElementById('formGaji').submit();
            }
        }
    </script>
    @endpush
</x-layouts.dashboard>
