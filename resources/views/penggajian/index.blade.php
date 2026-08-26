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

    {{-- MODAL --}}
    <x-penggajian.modal-konfirmasi />

    @push('scripts')
    <script>
        window.penggajianAllTujuans = @json($allTujuans ?? []);
        window.penggajianHasErrors = @json($errors->any() ? true : false);
    </script>
    <script src="{{ asset('js/penggajian.js') }}"></script>
    @endpush
</x-layouts.dashboard>
