/**
 * Generic CRUD Page - JS for sopir/tujuan/etc.
 * Expects globals: crudEntityName, crudDeleteUrl, crudStoreUrl, crudCsrfToken
 * (e.g. window.crudEntityName = 'Sopir', 'Tujuan', etc.)
 */
(function() {
    'use strict';

    function validasiNama(input) {
        return /^[a-zA-Z0-9\s\-\.]+$/.test(input);
    }

    var entity = window.crudEntityName || 'Data';

    window.confirmDelete = function(id, nama) {
        showConfirmModal({
            title: 'Hapus Data ' + entity + '?',
            message: 'Anda yakin ingin menghapus ' + entity.toLowerCase() + ' "' + nama + '"? Tindakan ini tidak dapat dibatalkan.',
            type: 'danger',
            confirmText: 'Ya, Hapus',
            onConfirm: function() {
                document.getElementById('deleteForm').action = window.crudDeleteUrl + '/' + id;
                document.getElementById('deleteForm').submit();
            }
        });
    };

    window.konfirmasiTambah = function() {
        var nama = document.getElementById('namaTambah').value.trim();
        if (!nama) {
            document.getElementById('errorTambah').textContent = 'Nama ' + entity.toLowerCase() + ' wajib diisi.';
            document.getElementById('errorTambah').classList.remove('hidden');
            return;
        }
        if (nama.length < 3) {
            document.getElementById('errorTambah').textContent = 'Nama minimal 3 karakter.';
            document.getElementById('errorTambah').classList.remove('hidden');
            return;
        }
        if (!validasiNama(nama)) {
            document.getElementById('errorTambah').textContent = 'Nama hanya boleh huruf, angka, spasi, dan strip.';
            document.getElementById('errorTambah').classList.remove('hidden');
            return;
        }
        document.getElementById('errorTambah').classList.add('hidden');
        document.getElementById('namaKonfirmasiTambah').textContent = nama;

        var modal = document.getElementById('tambahModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closeTambahModal = function() {
        var modal = document.getElementById('tambahModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.submitTambah = function() {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.crudStoreUrl;

        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = window.crudCsrfToken;

        var nama = document.createElement('input');
        nama.type = 'hidden';
        nama.name = 'nama';
        nama.value = document.getElementById('namaTambah').value;

        form.appendChild(csrf);
        form.appendChild(nama);
        document.body.appendChild(form);
        form.submit();
    };

    window.openEditModal = function(id, kode, nama, status) {
        document.getElementById('editForm').action = window.crudDeleteUrl + '/' + id;
        document.getElementById('edit_kode').value = kode;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_status').value = status;

        var modal = document.getElementById('editModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closeEditModal = function() {
        var modal = document.getElementById('editModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.konfirmasiEdit = function() {
        var nama = document.getElementById('edit_nama').value.trim();
        if (!nama || nama.length < 3) return;
        if (!validasiNama(nama)) return;

        document.getElementById('namaKonfirmasiEdit').textContent = nama;
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

    window.submitEdit = function() {
        document.getElementById('editForm').submit();
    };

    // Clear error on typing
    var namaTambah = document.getElementById('namaTambah');
    if (namaTambah) {
        namaTambah.addEventListener('input', function() {
            document.getElementById('errorTambah').classList.add('hidden');
        });
    }

    // Escape to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (typeof closeTambahModal === 'function') closeTambahModal();
            if (typeof closeEditModal === 'function') closeEditModal();
            if (typeof closeKonfirmasiEditModal === 'function') closeKonfirmasiEditModal();
        }
    });
})();
