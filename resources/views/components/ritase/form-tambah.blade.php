@props(['periodes', 'sopirs'])

{{-- FORM TAMBAH RITASE --}}
<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-xs font-semibold uppercase" style="color:var(--text-muted)">
            Tambah Ritase Baru
            <span class="text-xs ml-2" style="color:var(--text-dims);font-weight:400">Sistem akan otomatis mengecek aturan sewa DT</span>
        </h3>
    </div>
    <div class="card-body">
        <form id="formTambahRitase" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Periode <span class="text-red-500">*</span></label>
                    <select id="periode_id" name="periode_id" required class="form-input">
                        <option value="">-- Pilih Periode --</option>
                        @foreach($periodes as $periode)<option value="{{ $periode->id }}">{{ $periode->nama_periode }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Sopir <span class="text-red-500">*</span></label>
                    <select id="kode_sopir" name="kode_sopir" required class="form-input">
                        <option value="">-- Pilih Sopir --</option>
                        @foreach($sopirs as $sopir)<option value="{{ $sopir->kode_sopir }}">{{ $sopir->nama }} ({{ $sopir->kode_sopir }})</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Tujuan <span class="text-red-500">*</span></label>
                    <select id="kode_tujuan" name="kode_tujuan" required class="form-input">
                        <option value="">-- Pilih Tujuan --</option>
                        @foreach(\App\Models\Tujuan::orderBy('id', 'asc')->get() as $tujuan)<option value="{{ $tujuan->kode_tujuan }}">{{ $tujuan->nama }} ({{ $tujuan->kode_tujuan }})</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" id="tanggal" name="tanggal" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Waktu <span class="text-red-500">*</span></label>
                    <select id="waktu" name="waktu" required class="form-input">
                        <option value="">-- Pilih Waktu --</option>
                        <option value="pagi">Pagi</option>
                        <option value="malam">Malam</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Kabupaten <span class="text-red-500">*</span></label>
                    <select id="kabupaten" name="kabupaten" required class="form-input">
                        <option value="">-- Pilih Kabupaten --</option>
                        <option value="Nganjuk">Nganjuk</option>
                        <option value="Kediri">Kediri</option>
                        <option value="Kota Kediri">Kota Kediri</option>
                        <option value="Jombang">Jombang</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" required onchange="toggleKompensasiField()" class="form-input">
                        <option value="pending">Pending</option>
                        <option value="valid">Valid</option>
                        <option value="gagal_produksi">Gagal Produksi</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">DT (Sewa Dump Truck)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                        <input type="number" id="dt" name="dt" min="0" readonly class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm bg-gray-100 text-gray-600 cursor-not-allowed" value="0">
                    </div>
                    <p class="text-xs text-gray-800 mt-1">*DT akan dihitung otomatis berdasarkan aturan</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div id="kompensasi_container" class="hidden">
                    <label class="form-label">Nominal Kompensasi</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                        <input type="number" id="nominal_kompensasi" name="nominal_kompensasi" min="0" class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white" placeholder="0">
                    </div>
                    <p class="text-red-500 text-xs mt-1 hidden" id="error_kompensasi">Nominal harus angka positif.</p>
                </div>
                <div>
                    <label class="form-label">Catatan</label>
                    <input type="text" id="catatan" name="catatan" class="form-input" placeholder="Catatan tambahan (opsional)">
                    <p class="text-red-500 text-xs mt-1 hidden" id="error_catatan">Catatan hanya boleh huruf, angka, spasi, dan strip.</p>
                </div>
                <div>
                    <label class="flex items-center gap-2 mt-5">
                        <input type="checkbox" id="is_lembur" name="is_lembur" value="1" onchange="toggleLemburField()" class="w-4 h-4">
                        <span class="text-sm font-medium text-gray-700">Lembur</span>
                    </label>
                    <div id="upah_lembur_container" class="hidden mt-2">
                        <label class="form-label">Upah Lembur</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                            <input type="number" id="upah_lembur" name="upah_lembur" min="0" class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white" placeholder="0" value="0">
                        </div>
                    </div>
                </div>
            </div>
            <div id="previewAturan" class="hidden border border-gray-200 rounded p-4 bg-gray-50">
                <h4 class="font-semibold text-gray-800 text-sm mb-1">Preview Aturan Sewa DT</h4>
                <p class="text-sm text-gray-600" id="previewKeterangan">-</p>
                <div class="mt-2 flex flex-wrap items-center gap-4">
                    <span class="text-sm text-gray-600">Rit ke-<span id="previewRitKe" class="font-semibold text-gray-900">-</span></span>
                    <span class="text-sm text-gray-600">Sewa DT: <span id="previewSewaDT" class="font-semibold text-gray-900">-</span></span>
                    <span id="previewKompensasiContainer" class="hidden text-sm text-gray-800">Kompensasi: Rp <span id="previewKompensasi">0</span></span>
                </div>
            </div>
            <div class="flex justify-end pt-2 gap-2">
                <a href="{{ route('ritase.parser') }}" class="btn btn-secondary">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Tambah Ritase Otomatis
                </a>
                <button type="submit" class="btn btn-primary">Tambah Ritase</button>
            </div>
        </form>
    </div>
</div>
