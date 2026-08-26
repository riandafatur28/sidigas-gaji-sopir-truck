<x-layouts.dashboard
    :title="'Edit Gaji'"
    :pageTitle="'Edit Gaji'"
    >

    <div class="border-b border-gray-200 pb-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit Gaji</h1>
                <p class="text-base text-gray-500 mt-1">Edit biaya per tujuan dan kompensasi gagal produksi — {{ $periode->nama_periode }}</p>
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

    <form id="formEditGaji" action="{{ route('gaji.update', $periode->id) }}" method="POST">
        @csrf
        @method('PUT')

        <input type="hidden" name="periode_id" value="{{ $periode->id }}">

        {{-- INPUT PER TUJUAN --}}
        <div class="w-full border border-gray-200 rounded mb-6 overflow-hidden bg-white">
            <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Input Biaya Per Tujuan</p>
            </div>

            <div class="px-5 py-4">
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wider mb-1">Periode</label>
                    <input type="text" value="{{ $periode->nama_periode }}" disabled
                           class="w-full md:w-1/2 px-4 py-2.5 border border-gray-200 rounded text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
                </div>

                <div class="table-responsive">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tujuan</th>
                                <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">BBM/Rit</th>
                                <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Upah/Rit</th>
                                <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Kompensasi Gagal</th>
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
                                               min="0" step="0.01"
                                               placeholder="0"
                                               value="{{ $detailPerTujuan[$tujuan->kode_tujuan]['bbm_per_rit'] ?? 0 }}"
                                               class="w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 bg-white transition input-bbm">
                                        <p class="text-red-500 text-xs mt-1 hidden error-edit-bbm">Harus angka positif.</p>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="max-w-xs">
                                        <input type="number"
                                               name="detail[{{ $loop->index }}][upah_per_rit]"
                                               min="0" step="0.01"
                                               placeholder="0"
                                               value="{{ $detailPerTujuan[$tujuan->kode_tujuan]['upah_per_rit'] ?? 0 }}"
                                               class="w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 bg-white transition input-upah">
                                        <p class="text-red-500 text-xs mt-1 hidden error-edit-upah">Harus angka positif.</p>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="max-w-xs">
                                        <input type="number"
                                               name="detail[{{ $loop->index }}][kompensasi_gagal]"
                                               min="0" step="0.01"
                                               placeholder="0"
                                               value="{{ $detailPerTujuan[$tujuan->kode_tujuan]['kompensasi_gagal'] ?? 0 }}"
                                               class="w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 bg-white transition">
                                        <p class="text-red-500 text-xs mt-1 hidden error-edit-komp">Harus angka positif.</p>
                                        <input type="hidden" name="detail[{{ $loop->index }}][kode_tujuan]" value="{{ $tujuan->kode_tujuan }}">
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2 max-w-xs">
                                        @php
                                            $tolVal = $detailPerTujuan[$tujuan->kode_tujuan]['tol_per_rit'] ?? 0;
                                        @endphp
                                        <input type="checkbox"
                                               data-tujuan="{{ $tujuan->kode_tujuan }}"
                                               class="tol-checkbox w-4 h-4 rounded border-gray-300 cursor-pointer"
                                               {{ $tolVal > 0 ? 'checked' : '' }}>
                                        <input type="number"
                                               name="detail[{{ $loop->index }}][tol_per_rit]"
                                               data-tujuan="{{ $tujuan->kode_tujuan }}"
                                               data-field="tol_per_rit"
                                               min="0" step="0.01"
                                               placeholder="0"
                                               value="{{ $tolVal }}"
                                               {{ $tolVal > 0 ? '' : 'disabled' }}
                                               class="tol-input w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition {{ $tolVal > 0 ? 'bg-white' : 'bg-gray-100 opacity-50 cursor-not-allowed' }}">
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2 max-w-xs">
                                        @php
                                            $lemburTujuanVal = $detailPerTujuan[$tujuan->kode_tujuan]['lembur_per_rit'] ?? 0;
                                        @endphp
                                        <input type="checkbox"
                                               data-tujuan="{{ $tujuan->kode_tujuan }}"
                                               class="lembur-tujuan-checkbox w-4 h-4 rounded border-gray-300 cursor-pointer"
                                               {{ $lemburTujuanVal > 0 ? 'checked' : '' }}>
                                        <input type="number"
                                               name="detail[{{ $loop->index }}][lembur_per_rit]"
                                               data-tujuan="{{ $tujuan->kode_tujuan }}"
                                               data-field="lembur_per_rit"
                                               min="0" step="1"
                                               placeholder="0"
                                               value="{{ $lemburTujuanVal }}"
                                               {{ $lemburTujuanVal > 0 ? '' : 'disabled' }}
                                               class="lembur-tujuan-input w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition {{ $lemburTujuanVal > 0 ? 'bg-white' : 'bg-gray-100 opacity-50 cursor-not-allowed' }}">
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="table-responsive">
        </div>

        {{-- RINCIAN PER SOPIR --}}
        <div class="w-full border border-gray-200 rounded mb-6 overflow-hidden bg-white">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-sm font-semibold text-gray-600 uppercase tracking-wider px-5 py-3" colspan="9">
                            Rincian Gaji Per Sopir
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
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tol</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Kompensasi</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Lembur</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Grand Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($existingGaji as $gaji)
                        @php
                            $totalRit = $gaji->details->sum('jumlah_rit');
                            $namaSopir = $gaji->sopir ? $gaji->sopir->nama : 'Unknown';
                            $inisial = $gaji->sopir ? substr($gaji->sopir->nama, 0, 1) : '?';
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                        <span class="text-gray-700 font-bold text-xs">{{ $inisial }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $namaSopir }}</p>
                                        <p class="text-xs text-gray-500">{{ $gaji->kode_sopir }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-center font-semibold text-sm text-gray-800">{{ $totalRit }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-800 font-medium text-sm">Rp {{ number_format($gaji->uang_solar, 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-800 font-medium text-sm">Rp {{ number_format($gaji->upah_sopir, 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-800 font-medium text-sm">Rp {{ number_format($gaji->dt, 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-800 font-medium text-sm">Rp {{ number_format($gaji->tol ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-right font-medium text-gray-800 text-sm">
                                Rp {{ number_format($kompensasiGagal[$gaji->kode_sopir] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-gray-800 font-medium text-sm">Rp {{ number_format($gaji->upah_lembur ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-sm text-gray-900">Rp {{ number_format($gaji->total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-400">Tidak ada data gaji untuk periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="8" class="px-5 py-3 text-right text-sm font-semibold text-gray-700">TOTAL KESELURUHAN:</td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-gray-900">Rp {{ number_format($existingGaji->sum('total'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>

        {{-- ACTION BUTTONS --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('gaji.index') }}" class="border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</a>
            <button type="button" onclick="showKonfirmasiEdit()" class="bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition">
                Update Gaji
            </button>
        </div>
    </form>

    {{-- MODAL KONFIRMASI --}}
    <div id="konfirmasiModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
        <div class="bg-white rounded border border-gray-200 w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Update Gaji</h3>
                    <button onclick="closeKonfirmasiEdit()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="konfirmasiDetail" class="text-sm text-gray-600 mb-4 bg-gray-50 p-4 rounded max-h-60 overflow-y-auto"></div>
                <div class="flex gap-3">
                    <button onclick="closeKonfirmasiEdit()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                    <button onclick="submitEdit()" class="flex-1 bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition">Ya, Update</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.penggajianEditAllTujuans = @json($allTujuans ?? []);
        window.penggajianEditPeriodeName = @json($periode->nama_periode);
    </script>
    <script src="{{ asset('js/penggajian-edit.js') }}"></script>
    @endpush
</x-layouts.dashboard>
