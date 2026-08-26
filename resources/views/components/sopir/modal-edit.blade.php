{{-- Modal Edit Sopir --}}
<div id="editModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
    <div class="bg-white rounded border border-gray-200 w-full max-w-md mx-4">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Edit Data Sopir</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="editForm" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Kode Sopir</label>
                    <input type="text" id="edit_kode" disabled class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded text-sm text-gray-600 cursor-not-allowed">
                    <p class="text-xs text-gray-400 mt-1">Kode tidak dapat diubah</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nama Sopir</label>
                    <input type="text" id="edit_nama" name="nama" required class="form-input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Status</label>
                    <select id="edit_status" name="status" required class="form-input">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                    <button type="button" onclick="konfirmasiEdit()" class="flex-1 bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
