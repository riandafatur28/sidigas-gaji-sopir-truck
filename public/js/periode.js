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
        var form = document.getElementById('deleteForm');

        // Fallback bila shared modal tidak tersedia
        if (typeof showConfirmModal !== 'function') {
            if (!confirm('Hapus periode ' + nama + '?')) return;
            form.action = window.crudDeleteUrl + '/' + id;
            form.submit();
            return;
        }

        // Punya ritase -> tidak boleh dihapus
        if (jumlahRitase > 0) {
            showConfirmModal({
                title: 'Tidak Dapat Dihapus',
                message: 'Periode ' + nama + ' memiliki ' + jumlahRitase + ' ritase dan tidak dapat dihapus.',
                type: 'warning',
                confirmText: 'Mengerti'
            });
            return;
        }

        showConfirmModal({
            title: 'Hapus Periode?',
            message: 'Anda yakin ingin menghapus periode ' + nama + '? Tindakan ini tidak dapat dibatalkan.',
            type: 'danger',
            confirmText: 'Ya, Hapus',
            onConfirm: function() {
                form.action = window.crudDeleteUrl + '/' + id;
                form.submit();
            }
        });
    };

    window.closeDeleteModal = function() {
        var modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.submitDelete = function() {
        document.getElementById('deleteForm').submit();
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (typeof closeTambahModal === 'function') closeTambahModal();
            if (typeof closeEditModal === 'function') closeEditModal();
            if (typeof closeKonfirmasiEditModal === 'function') closeKonfirmasiEditModal();
            if (typeof closeDeleteModal === 'function') closeDeleteModal();
        }
    });
})();
