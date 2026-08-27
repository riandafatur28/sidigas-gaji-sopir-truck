{{-- Modal Edit Ritase --}}
@aware(['periodes', 'sopirs'])
<div id="editModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
    <div class="bg-white rounded border border-gray-200 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Edit Data Ritase</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="editForm" method="POST" class="space-y-4" action="">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Kode Ritase</label>
                        <input type="text" id="edit_kode_ritase" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded text-sm bg-gray-100 text-gray-600 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="form-label">Periode <span class="text-red-500">*</span></label>
                        <select id="edit_periode_id" name="periode_id" required class="form-input">
                            @foreach($periodes as $periode)
                                <option value="{{ $periode->id }}">{{ $periode->nama_periode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Sopir <span class="text-red-500">*</span></label>
                        <select id="edit_kode_sopir" name="kode_sopir" required class="form-input">
                            @foreach($sopirs as $sopir)
                                <option value="{{ $sopir->kode_sopir }}">{{ $sopir->nama }} ({{ $sopir->kode_sopir }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Tujuan <span class="text-red-500">*</span></label>
                        <select id="edit_kode_tujuan" name="kode_tujuan" required class="form-input">
                            @foreach(\App\Models\Tujuan::where('status', 'aktif')->orderBy('id', 'asc')->get() as $tujuan)
                                <option value="{{ $tujuan->kode_tujuan }}">{{ $tujuan->nama }} ({{ $tujuan->kode_tujuan }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" id="edit_tanggal" name="tanggal" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Waktu <span class="text-red-500">*</span></label>
                        <select id="edit_waktu" name="waktu" required class="form-input">
                            <option value="pagi">Pagi</option>
                            <option value="malam">Malam</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Kabupaten <span class="text-red-500">*</span></label>
                        <select id="edit_kabupaten" name="kabupaten" required class="form-input">
                            <option value="Nganjuk">Nganjuk</option>
                            <option value="Kediri">Kediri</option>
                            <option value="Kota Kediri">Kota Kediri</option>
                            <option value="Jombang">Jombang</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Status <span class="text-red-500">*</span></label>
                        <select id="edit_status" name="status" required onchange="toggleEditKompensasiField()" class="form-input">
                            <option value="pending">Pending</option>
                            <option value="valid">Valid</option>
                            <option value="gagal_produksi">Gagal Produksi</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">DT (Sewa Dump Truck)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                            <input type="number" id="edit_dt" name="dt" min="0" readonly class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm bg-gray-100 text-gray-600 cursor-not-allowed" value="0">
                        </div>
                    </div>
                    <div id="edit_kompensasi_container" class="hidden">
                        <label class="form-label">Nominal Kompensasi</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                            <input type="number" id="edit_nominal_kompensasi" name="nominal_kompensasi" min="0" class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Catatan</label>
                        <input type="text" id="edit_catatan" name="catatan" class="form-input">
                    </div>
                    <div>
                        <label class="flex items-center gap-2 mt-5">
                            <input type="checkbox" id="edit_is_lembur" name="is_lembur" value="1" onchange="toggleEditLemburField()" class="w-4 h-4">
                            <span class="text-sm font-medium text-gray-700">Lembur</span>
                        </label>
                        <div id="edit_upah_lembur_container" class="hidden mt-2">
                            <label class="form-label">Upah Lembur</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                                <input type="number" id="edit_upah_lembur" name="upah_lembur" min="0" class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white" placeholder="0" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                    <button type="submit" class="flex-1 bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
