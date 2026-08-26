{{-- Modal Konfirmasi Edit Sopir --}}
<div id="konfirmasiEditModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
    <div class="bg-white rounded border border-gray-200 w-full max-w-md mx-4">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Perubahan</h3>
                <button onclick="closeKonfirmasiEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <p class="text-sm text-gray-600 mb-6">
                Anda yakin ingin memperbarui data sopir:<br>
                <strong id="namaKonfirmasiEdit" class="text-gray-900 text-base"></strong>?
            </p>
            <div class="flex gap-3">
                <button onclick="closeKonfirmasiEditModal()" class="flex-1 border border-gray-300 rounded text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                <button onclick="submitEdit()" class="flex-1 bg-[#2d6a4f] text-white rounded text-sm font-semibold px-5 py-2.5 hover:bg-[#1b4332] transition">Ya, Simpan</button>
            </div>
        </div>
    </div>
</div>
