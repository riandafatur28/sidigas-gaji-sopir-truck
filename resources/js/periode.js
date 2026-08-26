/**
 * Periode Page - JS extracted from blade
 * Expects globals: window.crudDeleteUrl, window.crudStoreUrl, window.crudCsrfToken
 */
(function() {
    'use strict';

    function validasiNama(input) {
        return /^[a-zA-Z0-9\s\-\.]+$/.test(input);
    }

    function formatTanggal(dateStr) {
        var bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var date = new Date(dateStr);
        return date.getDate() + ' ' + bulan[date.getMonth()] + ' ' + date.getFullYear();
    }

    window.konfirmasiTambahPeriode = function() {
        var nama = document.getElementById('nama_periode').value.trim();
        var tglMulai = document.getElementById('tanggal_mulai').value;
        var tglSelesai = document.getElementById('tanggal_selesai').value;

        if (!nama || !tglMulai || !tglSelesai) return;

        if (!validasiNama(nama)) {
            document.getElementById('error_nama').textContent = 'Nama hanya boleh huruf, angka, spasi, dan strip.';
            document.getElementById('error_nama').classList.remove('hidden');
            return;
        }
        document.getElementById('error_nama').classList.add('hidden');

        if (new Date(tglSelesai) < new Date(tglMulai)) {
            alert('Tanggal selesai harus sama atau setelah tanggal mulai!');
            return;
        }

        document.getElementById('konfirmasiDetail').innerHTML =
            '<div class="space-y-2">' +
            '<div class="flex justify-between"><span class="text-gray-500">Nama:</span><span class="font-semibold">' + nama + '</span></div>' +
            '<div class="flex justify-between"><span class="text-gray-500">Mulai:</span><span class="font-semibold">' + formatTanggal(tglMulai) + '</span></div>' +
            '<div class="flex justify-between"><span class="text-gray-500">Selesai:</span><span class="font-semibold">' + formatTanggal(tglSelesai) + '</span></div>' +
            '</div>';

        var modal = document.getElementById('tambahModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closeTambahModal = function() {
        var modal = document.getElementById('tambahModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.submitTambahPeriode = function() {
        var form = document.getElementById('formTambahPeriode');
        form.method = 'POST';
        form.action = window.crudStoreUrl;
        form.submit();
    };

    window.openEditModal = function(periode) {
        document.getElementById('editForm').action = window.crudDeleteUrl + '/' + periode.id;
        document.getElementById('edit_kode').value = periode.kode_periode;
        document.getElementById('edit_nama').value = periode.nama_periode;
        document.getElementById('edit_tanggal_mulai').value = periode.tanggal_mulai.split(' ')[0];
        document.getElementById('edit_tanggal_selesai').value = periode.tanggal_selesai.split(' ')[0];
        document.getElementById('edit_status').value = periode.status;

        var modal = document.getElementById('editModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closeEditModal = function() {
        var modal = document.getElementById('editModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.konfirmasiEditPeriode = function() {
        var nama = document.getElementById('edit_nama').value.trim();
        if (!validasiNama(nama)) return;
        closeEditModal();
        var modal = document.getElementById('konfirmasiEditModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closeKonfirmasiEditModal = function() {
        var modal = document.getElementById('konfirmasiEditModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.submitEditPeriode = function() {
        document.getElementById('editForm').submit();
    };

    window.confirmDelete = function(id, nama, jumlahRitase) {
        document.getElementById('deleteForm').action = window.crudDeleteUrl + '/' + id;
        var pesan = document.getElementById('delete_pesan');
        if (jumlahRitase > 0) {
            pesan.innerHTML = 'Periode <strong class="text-gray-900">' + nama + '</strong> memiliki <strong class="text-red-600">' + jumlahRitase + ' ritase</strong> dan <strong class="text-red-600">tidak dapat dihapus</strong>!';
            document.querySelector('#deleteModal button.bg-red-600').disabled = true;
            document.querySelector('#deleteModal button.bg-red-600').classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            pesan.innerHTML = 'Anda yakin ingin menghapus periode <strong class="text-gray-900">' + nama + '</strong>?<br><span class="text-xs text-red-500 mt-1 block">Tindakan ini tidak dapat dibatalkan.</span>';
            document.querySelector('#deleteModal button.bg-red-600').disabled = false;
            document.querySelector('#deleteModal button.bg-red-600').classList.remove('opacity-50', 'cursor-not-allowed');
        }
        var modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closeDeleteModal = function() {
        var modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.submitDelete = function() {
        document.getElementById('deleteForm').submit();
    };

    // Live search
    (function() {
        var searchInput = document.getElementById('liveSearch');
        if (!searchInput) return;
        var clearSearch = document.getElementById('clearSearch');
        var debounceTimer;
        function debounce(func, wait) {
            return function() {
                var args = arguments;
                var ctx = this;
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() { func.apply(ctx, args); }, wait);
            };
        }
        function performSearch() {
            var query = searchInput.value.trim();
            var url = new URL(window.location.href);
            if (query) { url.searchParams.set('search', query); clearSearch.classList.remove('hidden'); }
            else { url.searchParams.delete('search'); clearSearch.classList.add('hidden'); }
            window.location.href = url.toString();
        }
        searchInput.addEventListener('input', debounce(performSearch, 500));
        clearSearch.addEventListener('click', function() { searchInput.value = ''; performSearch(); searchInput.focus(); });
        if (searchInput.value) clearSearch.classList.remove('hidden');
    })();

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (typeof closeTambahModal === 'function') closeTambahModal();
            if (typeof closeEditModal === 'function') closeEditModal();
            if (typeof closeKonfirmasiEditModal === 'function') closeKonfirmasiEditModal();
            if (typeof closeDeleteModal === 'function') closeDeleteModal();
        }
    });
})();
