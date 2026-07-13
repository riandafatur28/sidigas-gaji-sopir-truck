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
            <div class="text-base text-gray-500">
                {{ now()->translatedFormat('d F Y') }}
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

    {{-- ============================================================ --}}
    {{-- FORM INPUT PER TUJUAN --}}
    {{-- ============================================================ --}}
    <div class="w-full border border-gray-200 rounded mb-6 overflow-hidden bg-white">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3" colspan="3">
                        Input Biaya Per Tujuan
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="3" class="px-5 py-4">
                        <form id="formGaji" action="{{ route('gaji.store') }}" method="POST">
                            @csrf

                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Pilih Periode <span class="text-red-500">*</span></label>
                                <select name="periode_id" id="pilih_periode" class="w-full md:w-1/2 px-4 py-2.5 border border-gray-300 rounded text-sm focus:outline-none focus:border-gray-600 bg-white">
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
                                                    <div class="relative max-w-xs">
                                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                                                        <input type="number"
                                                               name="detail[{{ $loop->index }}][bbm_per_rit]"
                                                               data-tujuan="{{ $tujuan->kode_tujuan }}"
                                                               data-field="bbm_per_rit"
                                                               min="0"
                                                               step="0.01"
                                                               value="0"
                                                               class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-gray-600 bg-white">
                                                    </div>
                                                </td>
                                                <td class="px-4 py-2.5">
                                                    <div class="relative max-w-xs">
                                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                                                        <input type="number"
                                                               name="detail[{{ $loop->index }}][upah_per_rit]"
                                                               data-tujuan="{{ $tujuan->kode_tujuan }}"
                                                               data-field="upah_per_rit"
                                                               min="0"
                                                               step="0.01"
                                                               value="0"
                                                               class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-gray-600 bg-white">
                                                    </div>
                                                </td>
                                                <input type="hidden" name="detail[{{ $loop->index }}][kode_tujuan]" value="{{ $tujuan->kode_tujuan }}">
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-4 flex justify-end gap-3">
                                    <a href="{{ route('gaji.index') }}" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 font-medium">Batal</a>
                                    <button type="button" onclick="showKonfirmasi()" class="px-6 py-2 bg-[#1a1a2e] text-white rounded text-sm font-medium hover:bg-[#2d2d44] transition-colors">
                                        Simpan Gaji
                                    </button>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ============================================================ --}}
    {{-- TABEL PER SOPIR --}}
    {{-- ============================================================ --}}
    <div id="tabelGajiContainer" class="hidden">
        <div class="w-full border border-gray-200 rounded mb-6 overflow-hidden bg-white">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3" colspan="8">
                            Rincian Gaji Per Sopir
                            <span class="font-normal text-gray-400 text-xs ml-2" id="periodeLabel">Periode: -</span>
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
                        <td class="px-5 py-3 text-right text-sm font-bold text-[#1a1a2e]" id="grandTotalAll">Rp 0</td>
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
                        <td class="px-4 py-2.5 text-right text-sm text-blue-600 font-medium">Rp {{ number_format($periode['total_solar'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm text-green-600 font-medium">Rp {{ number_format($periode['total_sopir'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm text-purple-600 font-medium">Rp {{ number_format($periode['total_dt'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm font-bold text-gray-800">Rp {{ number_format($periode['grand_total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('gaji.edit', $periode['id']) }}"
                                   class="text-xs text-gray-600 hover:text-gray-900 border border-gray-300 px-3 py-1 rounded hover:bg-gray-50 font-medium">
                                    Edit
                                </a>
                                <form action="{{ route('gaji.destroy', $periode['id']) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin hapus data gaji periode ini?')"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800 border border-red-300 px-3 py-1 rounded hover:bg-red-50 font-medium">
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
        <div class="bg-white border border-gray-200 rounded w-full max-w-md mx-4">
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
                    <button onclick="closeKonfirmasiModal()" class="flex-1 px-4 py-2.5 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 font-medium">Batal</button>
                    <button onclick="submitGaji()" class="flex-1 px-4 py-2.5 bg-[#1a1a2e] text-white rounded text-sm font-medium hover:bg-[#2d2d44] transition-colors">Ya, Simpan</button>
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
                    const url = new URL(window.location.href);
                    url.searchParams.set('periode', periodeId);
                    window.history.pushState({}, '', url);
                } else {
                    document.getElementById('tabelGajiContainer').classList.add('hidden');
                    const url = new URL(window.location.href);
                    url.searchParams.delete('periode');
                    window.history.pushState({}, '', url);
                }
            });

            const selectedPeriode = document.getElementById('pilih_periode').value;
            if (selectedPeriode) {
                periodeId = selectedPeriode;
                loadGajiData(selectedPeriode);
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
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-red-500">${data.error}</td></tr>`;
                        return;
                    }

                    if (data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Tidak ada data gaji untuk periode ini</td></tr>`;
                        document.getElementById('grandTotalAll').textContent = 'Rp 0';
                        return;
                    }

                    gajiData = data;
                    renderTabelGaji(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-red-500">Gagal memuat data</td></tr>`;
                });
        }

        function renderTabelGaji(data) {
            const tbody = document.getElementById('tabelGajiBody');
            tbody.innerHTML = '';

            let grandTotalAll = 0;

            data.forEach((sopir, index) => {
                const totalRit = Object.values(sopir.rit_per_tujuan).reduce((sum, item) => sum + item.total_rit, 0);

                // 🔥 Ambil langsung dari data API (sudah tersimpan di database)
                const totalSolar = sopir.total_solar || 0;
                const totalUpah = sopir.total_upah || 0;
                const totalDT = sopir.total_dt || 0;
                const totalKompensasi = sopir.total_kompensasi || 0;
                const grandTotal = sopir.grand_total || 0;

                grandTotalAll += grandTotal;

                const firstChar = sopir.nama_sopir ? sopir.nama_sopir.charAt(0).toUpperCase() : '?';

                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
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
                    <td class="px-4 py-3 text-right text-blue-600 font-medium">Rp ${formatRupiah(totalSolar)}</td>
                    <td class="px-4 py-3 text-right text-green-600 font-medium">Rp ${formatRupiah(totalUpah)}</td>
                    <td class="px-4 py-3 text-right text-purple-600 font-medium">Rp ${formatRupiah(totalDT)}</td>
                    <td class="px-4 py-3 text-right text-orange-600 font-medium">Rp ${formatRupiah(totalKompensasi)}</td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900">Rp ${formatRupiah(grandTotal)}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center gap-1">
                            <button onclick="showDetail(${index})" class="text-xs text-gray-600 hover:text-gray-900 border border-gray-300 px-2 py-1 rounded hover:bg-gray-50 font-medium">
                                Detail
                            </button>
                            <a href="/gaji/slip/${periodeId}/${sopir.kode_sopir}" target="_blank"
                               class="text-xs text-blue-600 hover:text-blue-800 border border-blue-300 px-2 py-1 rounded hover:bg-blue-50 font-medium">
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
            let detailHtml = `<div class="p-4"><h4 class="font-bold text-gray-800 mb-2">Detail ${sopir.nama_sopir}</h4>`;
            detailHtml += `<table class="w-full text-sm"><thead><tr><th class="text-left">Tujuan</th><th class="text-center">Jumlah Rit</th><th class="text-right">BBM</th><th class="text-right">Upah</th></tr></thead><tbody>`;

            const bbmInputs = document.querySelectorAll(`input[data-field="bbm_per_rit"]`);

            let found = false;
            bbmInputs.forEach(input => {
                const kodeTujuan = input.dataset.tujuan;
                const ritCount = sopir.rit_per_tujuan[kodeTujuan] ? sopir.rit_per_tujuan[kodeTujuan].total_rit : 0;
                if (ritCount > 0) {
                    found = true;
                    const namaTujuan = allTujuans.find(t => t.kode_tujuan === kodeTujuan)?.nama || kodeTujuan;
                    const bbm = sopir.rit_per_tujuan[kodeTujuan]?.solar_per_rit || 0;
                    const upah = sopir.rit_per_tujuan[kodeTujuan]?.upah_per_rit || 0;
                    detailHtml += `
                        <tr class="border-b">
                            <td class="py-2">${namaTujuan}</td>
                            <td class="text-center py-2">${ritCount} rit</td>
                            <td class="text-right py-2">Rp ${formatRupiah(bbm * ritCount)}</td>
                            <td class="text-right py-2">Rp ${formatRupiah(upah * ritCount)}</td>
                        </tr>
                    `;
                }
            });

            if (!found) {
                detailHtml += `<tr><td colspan="4" class="py-2 text-center text-gray-500">Tidak ada detail</td></tr>`;
            }

            detailHtml += `</tbody></table></div>`;

            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/40 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white border border-gray-200 rounded w-full max-w-lg max-h-[80vh] overflow-y-auto p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Detail Gaji ${sopir.nama_sopir}</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    ${detailHtml}
                </div>
            `;
            document.body.appendChild(modal);
        }

        function showKonfirmasi() {
            const periode = document.getElementById('pilih_periode').value;
            if (!periode) {
                alert('Silakan pilih Periode terlebih dahulu!');
                return;
            }

            const bbmInputs = document.querySelectorAll('input[data-field="bbm_per_rit"]');
            let hasEmpty = false;
            bbmInputs.forEach(input => {
                if (input.value === '' || parseFloat(input.value) < 0) {
                    hasEmpty = true;
                    input.classList.add('border-red-500');
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            const upahInputs = document.querySelectorAll('input[data-field="upah_per_rit"]');
            upahInputs.forEach(input => {
                if (input.value === '' || parseFloat(input.value) < 0) {
                    hasEmpty = true;
                    input.classList.add('border-red-500');
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (hasEmpty) {
                alert('Silakan isi BBM/Rit dan Upah/Rit untuk semua tujuan!');
                return;
            }

            const form = document.getElementById('formGaji');
            const formData = new FormData(form);
            formDataGaji = formData;

            const periodeText = document.getElementById('pilih_periode').options[document.getElementById('pilih_periode').selectedIndex].text;

            // Hitung total dari data yang ada
            let totalSolarAll = 0;
            let totalUpahAll = 0;
            let totalDTAll = 0;
            let totalKompAll = 0;
            let grandTotalAll = 0;

            if (gajiData.length > 0) {
                gajiData.forEach(s => {
                    totalSolarAll += s.total_solar || 0;
                    totalUpahAll += s.total_upah || 0;
                    totalDTAll += s.total_dt || 0;
                    totalKompAll += s.total_kompensasi || 0;
                    grandTotalAll += s.grand_total || 0;
                });
            }

            let detailHtml = `
                <div class="space-y-2">
                    <div class="flex justify-between"><span class="text-gray-500">Periode:</span><span class="font-semibold text-gray-900">${periodeText}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Total Sopir:</span><span class="font-semibold text-gray-900">${gajiData.length} sopir</span></div>
                    <div class="border-t pt-2 mt-2">
                        <p class="text-xs text-gray-500">Rincian Total:</p>
                        <div class="flex justify-between text-sm"><span>Total Solar</span><span class="font-semibold text-blue-600">Rp ${formatRupiah(totalSolarAll)}</span></div>
                        <div class="flex justify-between text-sm"><span>Total Upah</span><span class="font-semibold text-green-600">Rp ${formatRupiah(totalUpahAll)}</span></div>
                        <div class="flex justify-between text-sm"><span>Total DT</span><span class="font-semibold text-purple-600">Rp ${formatRupiah(totalDTAll)}</span></div>
                        <div class="flex justify-between text-sm"><span>Total Kompensasi</span><span class="font-semibold text-orange-600">Rp ${formatRupiah(totalKompAll)}</span></div>
                        <div class="flex justify-between text-sm font-bold border-t pt-1"><span>GRAND TOTAL</span><span class="font-semibold text-gray-900">Rp ${formatRupiah(grandTotalAll)}</span></div>
                    </div>
                </div>
            `;

            document.getElementById('konfirmasiDetail').innerHTML = detailHtml;
            const modal = document.getElementById('konfirmasiModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('flex');
                modal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
                modal.querySelector('.transform').classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeKonfirmasiModal() {
            const modal = document.getElementById('konfirmasiModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            modal.querySelector('.transform').classList.add('scale-95', 'opacity-0');
            modal.querySelector('.transform').classList.remove('scale-100', 'opacity-100');
        }

        function submitGaji() {
            if (formDataGaji) {
                const form = document.getElementById('formGaji');
                form.querySelectorAll('input[type="hidden"]').forEach(el => el.remove());

                for (let [key, value] of formDataGaji.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }

                closeKonfirmasiModal();
                form.submit();
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
