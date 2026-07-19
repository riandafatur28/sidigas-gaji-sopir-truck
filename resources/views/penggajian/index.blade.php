<x-layouts.dashboard
    :title="'Data Gaji'"
    :pageTitle="'Data Gaji'"
    :user="auth()->user()">

    {{-- HEADER --}}
    <div class="border-b border-gray-200 pb-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Data Gaji</h1>
                <p class="text-base text-gray-500 mt-1">Input BBM, Upah per tujuan, dan Kompensasi Gagal Produksi</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="border border-green-200 bg-green-50 text-green-700 px-4 py-3 rounded mb-4 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="border border-red-200 bg-red-50 text-red-700 px-4 py-3 rounded mb-4 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="border border-red-200 bg-red-50 text-red-700 px-4 py-3 rounded mb-4 text-sm">
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
        <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Input Biaya Per Tujuan</p>
        </div>

        <form id="formGaji" action="{{ route('gaji.store') }}" method="POST">
            @csrf

            <div class="px-5 py-4">
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Pilih Periode <span class="text-red-500">*</span></label>
                    <select name="periode_id" id="pilih_periode" class="w-full md:w-1/2 px-4 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#1a1a2e] focus:ring-1 focus:ring-[#1a1a2e]/20 transition bg-white">
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
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tujuan</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">BBM/Rit</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Upah/Rit</th>
                                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Kompensasi/Rit Gagal</th>
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
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-end gap-3">
                        <a href="{{ route('gaji.index') }}" class="border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</a>
                        <button type="button" onclick="showKonfirmasi()" class="bg-[#1a1a2e] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#2d2d44] transition">
                            Simpan Gaji
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ============================================================ --}}
    {{-- TABEL PER SOPIR --}}
    {{-- ============================================================ --}}
    <div id="tabelGajiContainer" class="hidden">
        <div class="w-full border border-gray-200 rounded mb-6 overflow-hidden bg-white">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3" colspan="7">
                            Rincian Gaji Per Sopir
                            <span class="font-normal text-gray-400 text-xs ml-2" id="periodeLabel">Periode: -</span>
                        </th>
                        <th class="text-right text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3">
                            <a id="downloadSlipBtn" href="#" class="text-xs text-gray-600 border border-gray-200 px-3 py-1.5 rounded hover:bg-gray-50 font-medium hidden">
                                Download Slip PDF
                            </a>
                        </th>
                    </tr>
                </thead>
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Sopir</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total Rit</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total Solar</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total Upah</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total DT</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Kompensasi</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Grand Total</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tabelGajiBody" class="divide-y divide-gray-100">
                    <!-- Data akan diisi oleh JavaScript -->
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="7" class="px-5 py-3 text-right text-sm font-semibold text-gray-700">TOTAL KESELURUHAN:</td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-gray-900" id="grandTotalAll">Rp 0</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- RIWAYAT GAJI --}}
    {{-- ============================================================ --}}
    <div class="w-full border border-gray-200 rounded overflow-hidden bg-white">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3" colspan="7">
                        Riwayat Gaji
                        <span class="font-normal text-gray-400 text-xs ml-2">Total: {{ count($periodes) }} periode</span>
                    </th>
                </tr>
            </thead>
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Periode</th>
                    <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total Ritase</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total Solar</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total Upah</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Total DT</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Grand Total</th>
                    <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($periodes as $periode)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 text-sm font-medium text-gray-800">{{ $periode['nama_periode'] }}</td>
                        <td class="px-4 py-2.5 text-center text-sm text-gray-600">{{ $periode['total_ritase'] }}</td>
                        <td class="px-4 py-2.5 text-right text-sm text-gray-800 font-medium">Rp {{ number_format($periode['total_solar'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm text-gray-800 font-medium">Rp {{ number_format($periode['total_sopir'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm text-gray-800 font-medium">Rp {{ number_format($periode['total_dt'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm font-bold text-gray-800">Rp {{ number_format($periode['grand_total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('gaji.edit', $periode['id']) }}"
                                   class="text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium">
                                    Edit
                                </a>
                                <a href="{{ route('gaji.slip-pdf', $periode['id']) }}"
                                   class="text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium"
                                   title="Download Slip PDF">
                                    Slip PDF
                                </a>
                                <form action="{{ route('gaji.destroy', $periode['id']) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin hapus data gaji periode ini?')"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 border border-red-200 px-2.5 py-1.5 rounded hover:bg-red-50 font-medium">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-400">Belum ada data gaji. Silakan tambahkan data gaji baru.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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
        let formDataGaji = null;
        let periodeId = null;

        // ===== VALIDASI INPUT =====
        function validasiNominal(input) {
            return /^\d+(\.\d+)?$/.test(input) && parseFloat(input) >= 0;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Ambil periode dari URL
            const urlParams = new URLSearchParams(window.location.search);
            const periodeFromUrl = urlParams.get('periode');

            if (periodeFromUrl) {
                document.getElementById('pilih_periode').value = periodeFromUrl;
            }

            document.getElementById('pilih_periode').addEventListener('change', function() {
                periodeId = this.value;
                if (periodeId) {
                    loadGajiData(periodeId);
                    const downloadBtn = document.getElementById('downloadSlipBtn');
                    downloadBtn.href = '{{ url("/gaji/slip-pdf") }}/' + periodeId;
                    downloadBtn.classList.remove('hidden');
                    const url = new URL(window.location.href);
                    url.searchParams.set('periode', periodeId);
                    window.history.pushState({}, '', url);
                } else {
                    document.getElementById('tabelGajiContainer').classList.add('hidden');
                    document.getElementById('downloadSlipBtn').classList.add('hidden');
                    const url = new URL(window.location.href);
                    url.searchParams.delete('periode');
                    window.history.pushState({}, '', url);
                }
            });

            document.querySelectorAll('input[data-field="bbm_per_rit"], input[data-field="upah_per_rit"]').forEach(function(input) {
                input.addEventListener('input', function() {
                    if (gajiData.length > 0) {
                        renderTabelGaji(gajiData);
                    }
                });
            });

            const selectedPeriode = document.getElementById('pilih_periode').value;
            if (selectedPeriode) {
                periodeId = selectedPeriode;
                loadGajiData(selectedPeriode);
                const downloadBtn = document.getElementById('downloadSlipBtn');
                downloadBtn.href = '{{ url("/gaji/slip-pdf") }}/' + selectedPeriode;
                downloadBtn.classList.remove('hidden');
            }
        });

        function loadGajiData(periodeId) {
            const container = document.getElementById('tabelGajiContainer');
            const tbody = document.getElementById('tabelGajiBody');
            const periodeLabel = document.getElementById('periodeLabel');

            container.classList.remove('hidden');
            tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Loading data...</td></tr>`;

            const periodeSelect = document.getElementById('pilih_periode');
            const periodeText = periodeSelect.options[periodeSelect.selectedIndex].text;
            periodeLabel.textContent = 'Periode: ' + periodeText;

            fetch(`/api/get-ritase-data?periode=${periodeId}`)
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
                            if (rate.kompensasi_gagal) {
                                const kompInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="kompensasi_gagal"]`);
                                if (kompInput && parseFloat(kompInput.value) === 0) {
                                    kompInput.value = rate.kompensasi_gagal;
                                    ratesApplied = true;
                                }
                            }
                        });
                    }

                    renderTabelGaji(data.sopir);
                    if (ratesApplied) renderTabelGaji(data.sopir);
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
            document.querySelectorAll('input[data-field="bbm_per_rit"]').forEach(function(inp) {
                bbmByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0;
            });
            document.querySelectorAll('input[data-field="upah_per_rit"]').forEach(function(inp) {
                upahByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0;
            });
            document.querySelectorAll('input[data-field="kompensasi_gagal"]').forEach(function(inp) {
                kompensasiByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0;
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

            data.forEach((sopir, index) => {
                const totalRit = Object.values(sopir.rit_per_tujuan).reduce(function(s, item) { return s + item.total_rit; }, 0);

                const belumDihitung = sopir.belum_dihitung || false;

                let totalSolar, totalUpah;
                if (belumDihitung) {
                    totalSolar = 0;
                    totalUpah = 0;
                    Object.keys(sopir.rit_per_tujuan).forEach(function(kodeTujuan) {
                        const rit = sopir.rit_per_tujuan[kodeTujuan].total_rit;
                        totalSolar += (bbmByTujuan[kodeTujuan] || 0) * rit;
                        totalUpah += (upahByTujuan[kodeTujuan] || 0) * rit;
                    });
                } else {
                    totalSolar = sopir.total_solar || 0;
                    totalUpah = sopir.total_upah || 0;
                }

                const totalDT = sopir.total_dt || 0;
                let totalKompensasi = sopir.total_kompensasi || 0;
                if (belumDihitung) {
                    totalKompensasi = 0;
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
                }
                const previewGrand = totalSolar + totalUpah + totalDT + totalKompensasi;

                grandTotalAll += (belumDihitung ? previewGrand : (sopir.grand_total || 0));

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
                        <span class="text-gray-800 font-medium" id="kompTotal_${sopir.kode_sopir}">Rp ${formatRupiah(totalKompensasi)}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900" id="grandTotal_${sopir.kode_sopir}">Rp ${formatRupiah(belumDihitung ? previewGrand : (sopir.grand_total || 0))}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center gap-1">
                            <button onclick="showDetail(${index})" class="text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium">
                                Detail
                            </button>
                            <a href="/gaji/slip/${periodeId}/${sopir.kode_sopir}" target="_blank"
                               class="text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium">
                                Slip
                            </a>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });

            document.getElementById('grandTotalAll').textContent = 'Rp ' + formatRupiah(grandTotalAll);
        }

        function formatRupiah(angka) {
            return Math.round(angka).toLocaleString('id-ID');
        }

        function showDetail(index) {
            const sopir = gajiData[index];
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/40 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded border border-gray-200 w-full max-w-4xl max-h-[90vh] overflow-y-auto p-4">
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
                    styles.forEach(s => styleHtml += s.outerHTML);
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
                const kompInput = document.querySelector(`input[data-tujuan="${kodeTujuan}"][data-field="kompensasi_gagal"]`);
                const komp = kompInput ? kompInput.value || '0' : '0';
                let line = `${namaTujuan}`;
                line += ` <span class="text-gray-600">BBM: Rp ${formatRupiah(bbm)} | Upah: Rp ${formatRupiah(upah)}`;
                if (parseFloat(komp) > 0) {
                    line += ` | Kompensasi: Rp ${formatRupiah(komp)}`;
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
    </script>
    @endpush
</x-layouts.dashboard>
