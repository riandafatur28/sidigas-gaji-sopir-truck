<x-layouts.dashboard
    :title="'Data Gaji'"
    :pageTitle="'Data Gaji'"
    >

    @push('styles')
<style>
    /* Skeleton animation */
    .skeleton-row td {
        padding: 12px 16px !important;
    }
    .skeleton-box {
        display: inline-block;
        height: 14px;
        border-radius: 4px;
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
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- FORM INPUT PER TUJUAN --}}
    {{-- ============================================================ --}}
    <div id="formInputContainer" class="w-full border border-gray-200 rounded mb-6 overflow-hidden bg-white">
        <div class="card-header">
            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Input Biaya Per Tujuan</p>
        </div>

        <form id="formGaji" action="{{ route('gaji.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label">Pilih Periode <span class="text-red-500">*</span></label>
                    <select name="periode" id="pilih_periode" class="form-input form-select w-full md:w-1/2" onchange="window.location.href='{{ route('gaji.index') }}?periode='+this.value">
                        <option value="">Pilih Periode</option>
                        @foreach($periodesForDropdown ?? [] as $periode)
                            <option value="{{ $periode->id }}" {{ isset($periodeId) && $periodeId == $periode->id ? 'selected' : '' }}>
                                {{ $periode->nama_periode }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">*Pilih periode untuk melihat dan mengedit data gaji</p>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Detail Biaya Per Tujuan</p>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                                <tr>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tujuan</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">BBM/Rit</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Upah/Rit</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Kompensasi/Rit Gagal</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tol</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Lembur</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($allTujuans as $tujuan)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2.5">
                                        <p class="text-sm font-medium text-gray-800">{{ $tujuan->nama }}</p>
                                        <p class="text-xs text-gray-400">{{ $tujuan->kode_tujuan }}</p>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="max-w-xs">
                                            <input type="number"
                                                   name="detail[{{ $loop->index }}][bbm_per_rit]"
                                                   data-tujuan="{{ $tujuan->kode_tujuan }}"
                                                   data-field="bbm_per_rit"
                                                   min="0"
                                                   step="0.01"
                                                   value="0"
                                                   placeholder="0"
                                                   class="w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white">
                                            <p class="text-red-500 text-xs mt-1 hidden" id="error_bbm_{{ $loop->index }}">Harus angka positif.</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="max-w-xs">
                                            <input type="number"
                                                   name="detail[{{ $loop->index }}][upah_per_rit]"
                                                   data-tujuan="{{ $tujuan->kode_tujuan }}"
                                                   data-field="upah_per_rit"
                                                   min="0"
                                                   step="0.01"
                                                   value="0"
                                                   placeholder="0"
                                                   class="w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white">
                                            <p class="text-red-500 text-xs mt-1 hidden" id="error_upah_{{ $loop->index }}">Harus angka positif.</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="max-w-xs">
                                            <input type="number"
                                                   name="detail[{{ $loop->index }}][kompensasi_gagal]"
                                                   data-tujuan="{{ $tujuan->kode_tujuan }}"
                                                   data-field="kompensasi_gagal"
                                                   min="0"
                                                   step="0.01"
                                                   value="0"
                                                   placeholder="0"
                                                   class="w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white">
                                            <p class="text-red-500 text-xs mt-1 hidden" id="error_komp_{{ $loop->index }}">Harus angka positif.</p>
                                            <input type="hidden" name="detail[{{ $loop->index }}][kode_tujuan]" value="{{ $tujuan->kode_tujuan }}">
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2 max-w-xs">
                                            <input type="checkbox"
                                                   data-tujuan="{{ $tujuan->kode_tujuan }}"
                                                   data-field="tol_check"
                                                   class="tol-checkbox w-4 h-4 rounded border-gray-300 cursor-pointer">
                                            <input type="number"
                                                   name="detail[{{ $loop->index }}][tol_per_rit]"
                                                   data-tujuan="{{ $tujuan->kode_tujuan }}"
                                                   data-field="tol_per_rit"
                                                   min="0"
                                                   step="0.01"
                                                   value="0"
                                                   placeholder="0"
                                                   disabled
                                                   class="tol-input w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-gray-100 opacity-50 cursor-not-allowed">
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2 max-w-xs">
                                            <input type="checkbox"
                                                   data-tujuan="{{ $tujuan->kode_tujuan }}"
                                                   data-field="lembur_tujuan_check"
                                                   class="lembur-tujuan-checkbox w-4 h-4 rounded border-gray-300 cursor-pointer">
                                            <input type="number"
                                                   name="detail[{{ $loop->index }}][lembur_per_rit]"
                                                   data-tujuan="{{ $tujuan->kode_tujuan }}"
                                                   data-field="lembur_per_rit"
                                                   min="0" step="1"
                                                   value="0"
                                                   placeholder="0"
                                                   disabled
                                                   class="lembur-tujuan-input w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-gray-100 opacity-50 cursor-not-allowed">
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>



                    <div class="mt-4 flex justify-end gap-3">
                        <a href="{{ route('gaji.index') }}" class="border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</a>
                        <button type="button" onclick="showKonfirmasi()" class="btn btn-primary">
                            Simpan Gaji
                        </button>
                    </div>
                </div>

            </div>

            <input type="hidden" name="periode_id" id="formPeriodeId" value="{{ $periodeId ?? '' }}">
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
        <div class="flex items-center gap-3 mt-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Filter Tanggal</span>
                <input type="date" id="filterTanggal" class="px-3 py-2 border border-gray-200 rounded text-sm bg-white">
                <button onclick="clearFilterTanggal()" class="px-3 py-2 border border-gray-200 rounded text-sm text-gray-600 hover:bg-gray-50 bg-white">Reset</button>
            </div>
            <p id="summarySopirCount" class="text-xs text-gray-400"></p>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- TABEL PER SOPIR --}}
    {{-- ============================================================ --}}
    <div id="tabelGajiContainer" class="hidden">
        <div class="card mb-6">
            <table class="w-full">
                <thead>
                    <tr style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                        <th class="text-left text-xs font-semibold uppercase tracking-wider px-5 py-3" style="color:var(--text-muted)" colspan="8">
                            Rincian Gaji Per Sopir
                            <span class="font-normal text-gray-400 text-xs ml-2" id="periodeLabel">Periode: -</span>
                        </th>
                        <th class="text-right text-xs font-semibold uppercase tracking-wider px-5 py-3" style="color:var(--text-muted)">
                            <a id="downloadSlipBtn" href="{{ $periodeId ? url('/gaji/slip-pdf/' . $periodeId) : '#' }}" class="text-xs text-gray-600 border border-gray-200 px-3 py-1.5 rounded hover:bg-gray-50 font-medium {{ $periodeId ? '' : 'hidden' }}">
                                Download Slip PDF
                            </a>
                        </th>
                    </tr>
                </thead>
                <thead style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Sopir</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total Rit</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total Solar</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total Upah</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total DT</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tol</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Kompensasi</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Lembur</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Grand Total</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tabelGajiBody" class="divide-y divide-gray-100">
                    <!-- Data akan diisi oleh JavaScript -->
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200" id="gajiTableFoot">
                    <tr>
                        <td colspan="9" class="px-5 py-3 text-right text-sm font-semibold text-gray-700">TOTAL KESELURUHAN:</td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-gray-900" id="grandTotalAll">Rp 0</td>
                    </tr>
                    <tr id="paginationGajiRow" class="border-t border-gray-200 hidden">
                        <td colspan="8" class="px-5 py-3">
                            <div id="paginationGaji" class="flex items-center justify-end"></div>
                        </td>
                    </tr>
                </tfoot>
            </table>
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
                    <button onclick="submitGaji()" class="flex-1 bg-[#1a1a2e] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#2d2d44] transition">Ya, Simpan</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const allTujuans = @json($allTujuans ?? []);
        let gajiData = [];
        let gajiDataAll = [];
        let formDataGaji = null;
        let periodeId = null;
        let currentPage = 1;
        const pageSize = 10;

        // ===== VALIDASI INPUT =====
        function validasiNominal(input) {
            return /^\d+(\.\d+)?$/.test(input) && parseFloat(input) >= 0;
        }

        // Turbo-ready: run immediately (script at bottom of body)
        (function() {
            'use strict';
            // Ambil periode dari URL
            const urlParams = new URLSearchParams(window.location.search);
            const periodeFromUrl = urlParams.get('periode');

            if (periodeFromUrl) {
                document.getElementById('pilih_periode').value = periodeFromUrl;
                periodeId = periodeFromUrl;
                var formPeriodeInput = document.getElementById('formPeriodeId');
                if (formPeriodeInput) formPeriodeInput.value = periodeFromUrl;
            }

            document.getElementById('filterTanggal').addEventListener('change', function() {
                if (periodeId) loadGajiData(periodeId);
            });

            document.querySelectorAll('input[data-field="bbm_per_rit"], input[data-field="upah_per_rit"], input[data-field="tol_per_rit"]').forEach(function(input) {
                input.addEventListener('input', function() {
                    if (gajiData.length > 0) {
                        renderTabelGaji(gajiData);
                    }
                });
            });

            // Tol checkbox toggle
            document.querySelectorAll('.tol-checkbox').forEach(function(cb) {
                cb.addEventListener('change', function() {
                    const row = this.closest('tr');
                    const input = row.querySelector('.tol-input');
                    if (this.checked) {
                        input.disabled = false;
                        input.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                        input.focus();
                    } else {
                        input.disabled = true;
                        input.value = '0';
                        input.classList.add('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                        if (gajiData.length > 0) renderTabelGaji(gajiData);
                    }
                });
            });

            // Lembur tujuan checkbox toggle
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

            const selectedPeriode = document.getElementById('pilih_periode').value;
            if (selectedPeriode) {
                periodeId = selectedPeriode;
                loadGajiData(selectedPeriode);
            }

            // Auto-refresh tabel saat input per-tujuan berubah
            document.addEventListener('input', function(e) {
                if (e.target.matches('input[data-field="bbm_per_rit"], input[data-field="upah_per_rit"], input[data-field="tol_per_rit"], input[data-field="lembur_per_rit"], input[data-field="kompensasi_gagal"]')) {
                    if (gajiData.length > 0) renderTabelGaji(gajiData);
                }
            });
        })();

        function loadGajiData(periodeId) {
            const container = document.getElementById('tabelGajiContainer');
            const sumContainer = document.getElementById('summaryContainer');
            const tbody = document.getElementById('tabelGajiBody');
            const periodeLabel = document.getElementById('periodeLabel');

            container.classList.remove('hidden');
            sumContainer.classList.remove('hidden');
            tbody.innerHTML = renderSkeleton(6);

            const periodeSelect = document.getElementById('pilih_periode');
            const periodeText = periodeSelect.options[periodeSelect.selectedIndex].text;
            periodeLabel.textContent = 'Periode: ' + periodeText;

            var tanggal = document.getElementById('filterTanggal').value;
            var url = '/api/get-ritase-data?periode=' + periodeId;
            if (tanggal) url += '&tanggal=' + encodeURIComponent(tanggal);

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.error || 'Server error'); });
                    }
                    return response.json();
                })
                .then(data => {

                    if (data.sopir.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Tidak ada data ritase untuk periode ini</td></tr>`;
                        document.getElementById('grandTotalAll').textContent = 'Rp 0';
                        return;
                    }

                    gajiData = data.sopir;
                    gajiDataAll = data.sopir;
                    currentPage = 1;

                    // Sync period dropdown if date filter changed the period
                    if (data.detected_periode_id && data.detected_periode_id != periodeId) {
                        document.getElementById('pilih_periode').value = data.detected_periode_id;
                        periodeId = data.detected_periode_id;
                        const sel = document.getElementById('pilih_periode');
                        periodeLabel.textContent = 'Periode: ' + sel.options[sel.selectedIndex].text;
                        var formPeriodeInput = document.getElementById('formPeriodeId');
                        if (formPeriodeInput) formPeriodeInput.value = periodeId;
                    }

                    document.getElementById('formInputContainer').classList.remove('hidden');

                    // Pre-fill form inputs dengan default_rates dari periode sama/sebelumnya
                    let ratesApplied = false;
                    if (data.default_rates) {
                        Object.keys(data.default_rates).forEach(function(kodeTujuan) {
                            const rate = data.default_rates[kodeTujuan];
                            const bbmInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="bbm_per_rit"]`);
                            const upahInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="upah_per_rit"]`);
                            if (bbmInput && parseFloat(bbmInput.value) === 0) {
                                bbmInput.value = rate.bbm_per_rit;
                                ratesApplied = true;
                            }
                            if (upahInput && parseFloat(upahInput.value) === 0) {
                                upahInput.value = rate.upah_per_rit;
                                ratesApplied = true;
                            }
                            if (rate.tol_per_rit && parseFloat(rate.tol_per_rit) > 0) {
                                const tolInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="tol_per_rit"]`);
                                const tolCheck = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="tol_check"]`);
                                if (tolInput && tolCheck) {
                                    tolInput.value = rate.tol_per_rit;
                                    tolCheck.checked = true;
                                    tolInput.disabled = false;
                                    tolInput.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                                    ratesApplied = true;
                                }
                            }
                            if (rate.kompensasi_gagal) {
                                const kompInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="kompensasi_gagal"]`);
                                if (kompInput && parseFloat(kompInput.value) === 0) {
                                    kompInput.value = rate.kompensasi_gagal;
                                    ratesApplied = true;
                                }
                            }
                            const lemburTujuanVal = rate.lembur_per_rit || 0;
                            const lemburTujuanInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="lembur_per_rit"]`);
                            const lemburTujuanCheck = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="lembur_tujuan_check"]`);
                            if (parseFloat(lemburTujuanVal) > 0 && lemburTujuanInput && lemburTujuanCheck) {
                                lemburTujuanInput.value = lemburTujuanVal;
                                lemburTujuanCheck.checked = true;
                                lemburTujuanInput.disabled = false;
                                lemburTujuanInput.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                                ratesApplied = true;
                            }
                        });
                    }

                    renderTabelGaji(data.sopir);
                    updateSummary(data.sopir);
                    if (ratesApplied) { renderTabelGaji(data.sopir); updateSummary(data.sopir); }
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-red-500">${error.message || 'Gagal memuat data'}</td></tr>`;
                });
        }

        function renderTabelGaji(data) {
            const tbody = document.getElementById('tabelGajiBody');
            tbody.innerHTML = '';

            let grandTotalAll = 0;

            const bbmByTujuan = {};
            const upahByTujuan = {};
            const kompensasiByTujuan = {};
            const tolByTujuan = {};
            document.querySelectorAll('input[data-field="bbm_per_rit"]').forEach(function(inp) {
                bbmByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0;
            });
            document.querySelectorAll('input[data-field="upah_per_rit"]').forEach(function(inp) {
                upahByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0;
            });
            document.querySelectorAll('input[data-field="kompensasi_gagal"]').forEach(function(inp) {
                kompensasiByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0;
            });
            document.querySelectorAll('input[data-field="tol_per_rit"]').forEach(function(inp) {
                tolByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0;
            });
            const lemburByTujuan = {};
            document.querySelectorAll('input[data-field="lembur_per_rit"]').forEach(function(inp) {
                lemburByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0;
            });

            const totalGagalByTujuan = {};
            const gagalCountsBySopir = {};
            data.forEach(function(sopir) {
                gagalCountsBySopir[sopir.kode_sopir] = {};
                (sopir.gagal_rits || []).forEach(function(rit) {
                    const t = rit.kode_tujuan;
                    totalGagalByTujuan[t] = (totalGagalByTujuan[t] || 0) + 1;
                    gagalCountsBySopir[sopir.kode_sopir][t] = (gagalCountsBySopir[sopir.kode_sopir][t] || 0) + 1;
                });
            });

            const totalPages = Math.ceil(data.length / pageSize);
            if (currentPage > totalPages) currentPage = Math.max(1, totalPages);
            if (currentPage < 1) currentPage = 1;
            const start = (currentPage - 1) * pageSize;
            const end = Math.min(start + pageSize, data.length);
            const pageData = data.slice(start, end);

            pageData.forEach((sopir, idx) => {
                const index = start + idx;
                const totalRit = Object.values(sopir.rit_per_tujuan).reduce(function(s, item) { return s + item.total_rit; }, 0);

                // Always preview from form inputs (live update saat user ketik)
                let totalSolar = 0;
                let totalUpah = 0;
                let totalTol = 0;
                let totalLembur = 0;
                Object.keys(sopir.rit_per_tujuan).forEach(function(kodeTujuan) {
                    const rit = sopir.rit_per_tujuan[kodeTujuan].total_rit;
                    totalSolar += (bbmByTujuan[kodeTujuan] || 0) * rit;
                    totalUpah += (upahByTujuan[kodeTujuan] || 0) * rit;
                    totalTol += (tolByTujuan[kodeTujuan] || 0) * rit;
                    totalLembur += (lemburByTujuan[kodeTujuan] || 0) * rit;
                });
                // Fallback: if form has 0 but saved data has values, use saved
                if (totalSolar === 0 && totalUpah === 0 && !sopir.belum_dihitung) {
                    totalSolar = sopir.total_solar || 0;
                    totalUpah = sopir.total_upah || 0;
                    totalTol = sopir.total_tol || 0;
                    if (totalLembur === 0) totalLembur = sopir.upah_lembur || 0;
                }

                const totalDT = sopir.total_dt || 0;
                // Always recalc kompensasi from form inputs (live preview)
                let totalKompensasi = 0;
                const sopirKode = sopir.kode_sopir;
                Object.keys(kompensasiByTujuan).forEach(function(kodeTujuan) {
                    const kompPerRit = kompensasiByTujuan[kodeTujuan] || 0;
                    if (kompPerRit > 0) {
                        const sopirGagal = (gagalCountsBySopir[sopirKode] || {})[kodeTujuan] || 0;
                        if (sopirGagal > 0) {
                            totalKompensasi += kompPerRit * sopirGagal;
                        }
                    }
                });
                // Fallback: if no form kompensasi, use saved value
                if (totalKompensasi === 0 && !sopir.belum_dihitung) {
                    totalKompensasi = sopir.total_kompensasi || 0;
                }
                const previewGrand = totalSolar + totalUpah + totalDT + totalTol + totalKompensasi + totalLembur;

                grandTotalAll += previewGrand;

                const firstChar = sopir.nama_sopir ? sopir.nama_sopir.charAt(0).toUpperCase() : '?';

                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                row.id = `row_${sopir.kode_sopir}`;
                row.innerHTML = `
                    <td class="px-4 py-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                <span class="text-gray-700 font-bold text-xs">${firstChar}</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">${sopir.nama_sopir}</p>
                                <p class="text-xs text-gray-500">${sopir.kode_sopir}</p>

                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-semibold">${totalRit}</td>
                    <td class="px-4 py-3 text-right text-gray-800 font-medium">Rp ${formatRupiah(totalSolar)}</td>
                    <td class="px-4 py-3 text-right text-gray-800 font-medium">Rp ${formatRupiah(totalUpah)}</td>
                    <td class="px-4 py-3 text-right text-gray-800 font-medium">Rp ${formatRupiah(totalDT)}</td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-gray-800 font-medium" id="tolTotal_${sopir.kode_sopir}">Rp ${formatRupiah(totalTol)}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-gray-800 font-medium" id="kompTotal_${sopir.kode_sopir}">Rp ${formatRupiah(totalKompensasi)}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-gray-800 font-medium" id="lemburTotal_${sopir.kode_sopir}">Rp ${formatRupiah(totalLembur)}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900" id="grandTotal_${sopir.kode_sopir}">Rp ${formatRupiah(previewGrand)}</td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="showDetail(${index})" class="text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium">
                            Detail &amp; Slip
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            document.getElementById('grandTotalAll').textContent = 'Rp ' + formatRupiah(grandTotalAll);
            renderPagination(totalPages);
            updateSummary(gajiData);
        }

        function renderSkeleton(rows) {
            const w = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;
            let h = '';
            for (let r = 0; r < rows; r++) {
                h += `<tr class="skeleton-row">
                    <td class="px-4 py-3">
                        <div class="flex items-center space-x-2">
                            <div class="skeleton-box skeleton-avatar"></div>
                            <div>
                                <div class="skeleton-box skeleton-name" style="width:${w(70,130)}px"></div>
                                <div class="skeleton-box skeleton-code"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center"><div class="skeleton-box skeleton-rit" style="margin:0 auto"></div></td>
                    <td class="px-4 py-3 text-right"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>
                    <td class="px-4 py-3 text-right"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>
                    <td class="px-4 py-3 text-right"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>
                    <td class="px-4 py-3 text-right"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>
                    <td class="px-4 py-3 text-right"><div class="skeleton-box skeleton-number" style="width:${w(60,90)}px;margin-left:auto"></div></td>
                    <td class="px-4 py-3 text-center"><div class="skeleton-box skeleton-btn" style="margin:0 auto"></div></td>
                </tr>`;
            }
            return h;
        }

        function formatRupiah(angka) {
            return Math.round(angka).toLocaleString('id-ID');
        }



        function goToPage(page) {
            currentPage = page;
            renderTabelGaji(gajiData);
        }

        function renderPagination(totalPages) {
            const container = document.getElementById('paginationGaji');
            if (!container) return;
            if (totalPages <= 1) { container.innerHTML = ''; return; }

            let html = '<div class="flex items-center justify-between">';
            html += '<p class="text-sm text-gray-600">Halaman ' + currentPage + ' dari ' + totalPages + '</p>';
            html += '<div class="flex items-center space-x-1.5">';

            if (currentPage <= 1) {
                html += '<span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Sebelumnya</span>';
            } else {
                html += '<a href="#" onclick="goToPage(' + (currentPage - 1) + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Sebelumnya</a>';
            }

            const w = 2;
            let ss = Math.max(1, currentPage - w);
            let ee = Math.min(totalPages, currentPage + w);

            if (ss > 1) {
                html += '<a href="#" onclick="goToPage(1); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">1</a>';
                if (ss > 2) html += '<span class="px-3 py-1.5 text-sm text-gray-400">...</span>';
            }

            for (let p = ss; p <= ee; p++) {
                if (p == currentPage) {
                    html += '<span class="px-3 py-1.5 text-sm font-bold text-white bg-[#1a1a2e] border border-[#1a1a2e] rounded">' + p + '</span>';
                } else {
                    html += '<a href="#" onclick="goToPage(' + p + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">' + p + '</a>';
                }
            }

            if (ee < totalPages) {
                if (ee < totalPages - 1) html += '<span class="px-3 py-1.5 text-sm text-gray-400">...</span>';
                html += '<a href="#" onclick="goToPage(' + totalPages + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">' + totalPages + '</a>';
            }

            if (currentPage >= totalPages) {
                html += '<span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Selanjutnya</span>';
            } else {
                html += '<a href="#" onclick="goToPage(' + (currentPage + 1) + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Selanjutnya</a>';
            }

            html += '</div></div>';
            container.innerHTML = html;
            document.getElementById('paginationGajiRow').classList.toggle('hidden', totalPages <= 1);
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
            document.getElementById('summaryGrandTotal').textContent = 'Rp ' + formatRupiah(totalGrand);
            document.getElementById('summaryUpah').textContent = 'Rp ' + formatRupiah(totalUpah);
            document.getElementById('summarySolar').textContent = 'Rp ' + formatRupiah(totalSolar);
            document.getElementById('summaryDT').textContent = 'Rp ' + formatRupiah(totalDT);
            document.getElementById('summaryKompensasi').textContent = 'Rp ' + formatRupiah(totalKomp);
            document.getElementById('summarySopirCount').textContent = data.length + ' sopir';
        }

        function clearFilterTanggal() {
            document.getElementById('filterTanggal').value = '';
            if (periodeId) loadGajiData(periodeId);
        }



        function showDetail(index) {
            const sopir = gajiData[index];
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/40 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded border border-gray-200 w-full max-w-4xl max-h-[90vh] overflow-y-auto p-4" onclick="event.stopPropagation()">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-semibold text-gray-900">Slip Gaji ${sopir.nama_sopir}</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
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
                        // Strip global selectors that leak to the dashboard
                        css = css.replace(/@page\s*\{[^}]*\}/g, '');
                        css = css.replace(/(?:^|\n)\s*\*\s*\{[^}]*\}/g, '');
                        css = css.replace(/(?:^|\n)\s*html\s*\{[^}]*\}/g, '');
                        css = css.replace(/(?:^|\n)\s*body\s*\{[^}]*\}/g, '');
                        if (css.trim()) {
                            styleHtml += '<style>' + css + '</style>';
                        }
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

        function hitungKompensasiSopir(kodeSopir, value) {
            const sum = parseFloat(value) || 0;
            document.getElementById('kompTotal_' + kodeSopir).textContent = 'Rp ' + formatRupiah(sum);

            const solar = parseInt(document.querySelector('#row_' + kodeSopir + ' td:nth-child(3)').textContent.replace(/[^0-9]/g, '')) || 0;
            const upah = parseInt(document.querySelector('#row_' + kodeSopir + ' td:nth-child(4)').textContent.replace(/[^0-9]/g, '')) || 0;
            const dt = parseInt(document.querySelector('#row_' + kodeSopir + ' td:nth-child(5)').textContent.replace(/[^0-9]/g, '')) || 0;
            document.getElementById('grandTotal_' + kodeSopir).textContent = 'Rp ' + formatRupiah(solar + upah + dt + sum);

            let all = 0;
            document.querySelectorAll('[id^="grandTotal_"]').forEach(function(el) {
                all += parseInt(el.textContent.replace(/[^0-9]/g, '')) || 0;
            });
            document.getElementById('grandTotalAll').textContent = 'Rp ' + formatRupiah(all);
            updateSummary(gajiData);
        }

        function showKonfirmasi() {
            const periode = document.getElementById('pilih_periode').value;
            if (!periode) {
                alert('Silakan pilih Periode terlebih dahulu!');
                return;
            }

            const bbmInputs = document.querySelectorAll('input[data-field="bbm_per_rit"]');
            let hasEmpty = false;
            let hasInvalid = false;
            bbmInputs.forEach((input, i) => {
                const errorEl = document.getElementById('error_bbm_' + i);
                if (input.value === '' || parseFloat(input.value) < 0) {
                    hasEmpty = true;
                    input.classList.add('border-red-500');
                    if (errorEl) { errorEl.textContent = 'Wajib diisi.'; errorEl.classList.remove('hidden'); }
                } else if (!validasiNominal(input.value)) {
                    hasInvalid = true;
                    input.classList.add('border-red-500');
                    if (errorEl) { errorEl.textContent = 'Harus angka positif.'; errorEl.classList.remove('hidden'); }
                } else {
                    input.classList.remove('border-red-500');
                    if (errorEl) errorEl.classList.add('hidden');
                }
            });

            const upahInputs = document.querySelectorAll('input[data-field="upah_per_rit"]');
            upahInputs.forEach((input, i) => {
                const errorEl = document.getElementById('error_upah_' + i);
                if (input.value === '' || parseFloat(input.value) < 0) {
                    hasEmpty = true;
                    input.classList.add('border-red-500');
                    if (errorEl) { errorEl.textContent = 'Wajib diisi.'; errorEl.classList.remove('hidden'); }
                } else if (!validasiNominal(input.value)) {
                    hasInvalid = true;
                    input.classList.add('border-red-500');
                    if (errorEl) { errorEl.textContent = 'Harus angka positif.'; errorEl.classList.remove('hidden'); }
                } else {
                    input.classList.remove('border-red-500');
                    if (errorEl) errorEl.classList.add('hidden');
                }
            });

            const kompInputs = document.querySelectorAll('input[data-field="kompensasi_gagal"]');
            kompInputs.forEach((input, i) => {
                const errorEl = document.getElementById('error_komp_' + i);
                if (input.value && !validasiNominal(input.value)) {
                    hasInvalid = true;
                    input.classList.add('border-red-500');
                    if (errorEl) { errorEl.textContent = 'Harus angka positif.'; errorEl.classList.remove('hidden'); }
                } else {
                    input.classList.remove('border-red-500');
                    if (errorEl) errorEl.classList.add('hidden');
                }
            });

            if (hasEmpty) {
                alert('Silakan isi BBM/Rit dan Upah/Rit untuk semua tujuan!');
                return;
            }
            if (hasInvalid) {
                alert('Nilai harus berupa angka positif!');
                return;
            }

            const form = document.getElementById('formGaji');
            const formData = new FormData(form);
            formDataGaji = formData;

            const periodeSelect = document.getElementById('pilih_periode');
            const periodeText = periodeSelect.options[periodeSelect.selectedIndex].text;

            let detailHtml = `
                <div class="space-y-2">
                    <div class="flex justify-between"><span class="text-gray-500">Periode:</span><span class="font-semibold text-gray-900">${periodeText}</span></div>
                    <div class="border-t pt-2 mt-2">
                        <p class="text-xs text-gray-500">Detail Biaya per Tujuan:</p>
            `;

            const tujuanInputs = document.querySelectorAll('input[data-field="bbm_per_rit"]');
            tujuanInputs.forEach(input => {
                const kodeTujuan = input.dataset.tujuan;
                const namaTujuan = allTujuans.find(t => t.kode_tujuan === kodeTujuan)?.nama || kodeTujuan;
                const bbm = input.value || '0';
                const upahInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="upah_per_rit"]`);
                const upah = upahInput ? upahInput.value || '0' : '0';
                const tolInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="tol_per_rit"]`);
                const tol = tolInput ? tolInput.value || '0' : '0';
                const kompInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="kompensasi_gagal"]`);
                const komp = kompInput ? kompInput.value || '0' : '0';
                const lemburTujuanInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="lembur_per_rit"]`);
                const lemburTujuan = lemburTujuanInput ? lemburTujuanInput.value || '0' : '0';
                let line = `${namaTujuan}`;
                line += ` <span class="text-gray-600">BBM: Rp ${formatRupiah(bbm)} | Upah: Rp ${formatRupiah(upah)}`;
                if (parseFloat(tol) > 0) {
                    line += ` | Tol: Rp ${formatRupiah(tol)}`;
                }
                if (parseFloat(komp) > 0) {
                    line += ` | Kompensasi: Rp ${formatRupiah(komp)}`;
                }
                if (parseFloat(lemburTujuan) > 0) {
                    line += ` | Lembur: Rp ${formatRupiah(lemburTujuan)}`;
                }
                line += '</span>';
                detailHtml += `<div class="flex justify-between text-sm">${line}</div>`;
            });



            detailHtml += `
                    </div>
                    <div class="border-t pt-2 mt-2 text-xs text-gray-500">
                        Data akan dihitung ulang berdasarkan ritase yang ada.
                        ${gajiData.length > 0 ? 'Data lama akan ditimpa.' : ''}
                    </div>
                </div>
            `;

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

        document.querySelectorAll('.overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('flex');
                    this.classList.add('hidden');
                }
            });
        });

        function lihatSlipModal(periodeId) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/40 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded border border-gray-200 w-full max-w-6xl max-h-[95vh] overflow-y-auto p-4" onclick="event.stopPropagation()">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-semibold text-gray-900">Slip Gaji</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="slipViewContent" class="text-center text-gray-500 py-8">Loading slip...</div>
                </div>
            `;
            document.body.appendChild(modal);

            fetch('/gaji/slip-view/' + periodeId)
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const styles = doc.querySelectorAll('style');
                    let styleHtml = '';
                    styles.forEach(s => {
                        let css = s.textContent;
                        css = css.replace(/@page\s*\{[^}]*\}/g, '');
                        css = css.replace(/(?:^|\n)\s*\*\s*\{[^}]*\}/g, '');
                        css = css.replace(/(?:^|\n)\s*html\s*\{[^}]*\}/g, '');
                        css = css.replace(/(?:^|\n)\s*body\s*\{[^}]*\}/g, '');
                        if (css.trim()) {
                            styleHtml += '<style>' + css + '</style>';
                        }
                    });
                    const blocks = doc.querySelectorAll('.slip-block');
                    let slipHtml = '';
                    blocks.forEach(b => slipHtml += b.outerHTML);
                    document.getElementById('slipViewContent').innerHTML = styleHtml + (slipHtml || '<p class="text-gray-500">Tidak ada data slip</p>');
                })
                .catch(() => {
                    document.getElementById('slipViewContent').innerHTML = '<p class="text-red-500">Gagal memuat slip</p>';
                });
        }
    </script>
    @endpush
</x-layouts.dashboard>
