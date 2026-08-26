/**
 * Penggajian Edit Page - JS extracted from blade
 * Expects globals: window.penggajianEditAllTujuans, window.penggajianEditPeriodeName
 */
(function() {
    'use strict';

    var allTujuansEdit = window.penggajianEditAllTujuans || [];

    // ===== CHECKBOX TOGGLE (TOL, LEMBUR SOPIR, LEMBUR TUJUAN) =====
    function toggleCheckboxGroup(checkbox, inputSelector) {
        var container = checkbox.closest('.flex.items-center.gap-2');
        var input = container ? container.querySelector(inputSelector) : null;
        if (input) {
            if (checkbox.checked) {
                input.disabled = false;
                input.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                input.classList.add('bg-white');
                input.focus();
            } else {
                input.disabled = true;
                input.value = '0';
                input.classList.remove('bg-white');
                input.classList.add('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
            }
        }
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('tol-checkbox')) {
            toggleCheckboxGroup(e.target, '.tol-input');
        }
        if (e.target.classList.contains('lembur-tujuan-checkbox')) {
            toggleCheckboxGroup(e.target, '.lembur-tujuan-input');
        }
    });

    // ===== VALIDASI INPUT =====
    function validasiNominal(input) {
        return /^\d+(\.\d+)?$/.test(input) && parseFloat(input) >= 0;
    }

    window.showKonfirmasiEdit = function() {
        var form = document.getElementById('formEditGaji');

        var hasInvalid = false;
        var bbmInputs = form.querySelectorAll('.input-bbm');
        var upahInputs = form.querySelectorAll('.input-upah');
        var kompInputs = form.querySelectorAll('input[name$="[kompensasi_gagal]"]');
        var errorBbm = form.querySelectorAll('.error-edit-bbm');
        var errorUpah = form.querySelectorAll('.error-edit-upah');
        var errorKomp = form.querySelectorAll('.error-edit-komp');

        bbmInputs.forEach(function(input, i) {
            if (input.value && !validasiNominal(input.value)) {
                hasInvalid = true;
                input.classList.add('border-red-500');
                if (errorBbm[i]) errorBbm[i].classList.remove('hidden');
            } else {
                input.classList.remove('border-red-500');
                if (errorBbm[i]) errorBbm[i].classList.add('hidden');
            }
        });

        upahInputs.forEach(function(input, i) {
            if (input.value && !validasiNominal(input.value)) {
                hasInvalid = true;
                input.classList.add('border-red-500');
                if (errorUpah[i]) errorUpah[i].classList.remove('hidden');
            } else {
                input.classList.remove('border-red-500');
                if (errorUpah[i]) errorUpah[i].classList.add('hidden');
            }
        });

        kompInputs.forEach(function(input, i) {
            if (input.value && !validasiNominal(input.value)) {
                hasInvalid = true;
                input.classList.add('border-red-500');
                if (errorKomp[i]) errorKomp[i].classList.remove('hidden');
            } else {
                input.classList.remove('border-red-500');
                if (errorKomp[i]) errorKomp[i].classList.add('hidden');
            }
        });

        if (hasInvalid) {
            alert('Nilai harus berupa angka positif!');
            return;
        }

        var detailHtml = '<div class="space-y-2">' +
            '<div class="flex justify-between"><span class="text-gray-500">Periode:</span><span class="font-semibold text-gray-900">' + (window.penggajianEditPeriodeName || '') + '</span></div>' +
            '<div class="border-t pt-2 mt-2">' +
            '<p class="text-xs text-gray-500">Detail Biaya per Tujuan:</p>';

        document.querySelectorAll('.input-bbm').forEach(function(input, i) {
            var kodeTujuan = document.querySelectorAll('input[name$="[kode_tujuan]"]')[i] ? document.querySelectorAll('input[name$="[kode_tujuan]"]')[i].value : '';
            var found = allTujuansEdit.find(function(t) { return t.kode_tujuan === kodeTujuan; });
            var namaTujuan = found ? found.nama : kodeTujuan;
            var bbm = input.value || '0';
            var upahEl = document.querySelectorAll('.input-upah')[i];
            var upah = upahEl ? upahEl.value : '0';
            var kompEl = document.querySelectorAll('input[name$="[kompensasi_gagal]"]')[i];
            var komp = kompEl ? kompEl.value : '0';
            var tolEl = document.querySelectorAll('input[name$="[tol_per_rit]"]')[i];
            var tolVal = tolEl ? tolEl.value : '0';
            var lemburEl = document.querySelectorAll('input[name$="[lembur_per_rit]"]')[i];
            var lemburTujuanVal = lemburEl ? lemburEl.value : '0';
            var bbmNum = parseInt(bbm) || 0;
            var upahNum = parseInt(upah) || 0;
            var kompNum = parseInt(komp) || 0;
            var tolNum = parseInt(tolVal) || 0;
            var lemburTujuanNum = parseInt(lemburTujuanVal) || 0;
            if (bbmNum > 0 || upahNum > 0 || kompNum > 0 || tolNum > 0 || lemburTujuanNum > 0) {
                var line = namaTujuan + ' <span class="text-gray-600">BBM: Rp ' + bbmNum.toLocaleString('id-ID') + ' | Upah: Rp ' + upahNum.toLocaleString('id-ID');
                if (kompNum > 0) line += ' | Kompensasi: Rp ' + kompNum.toLocaleString('id-ID');
                if (tolNum > 0) line += ' | Tol: Rp ' + tolNum.toLocaleString('id-ID');
                if (lemburTujuanNum > 0) line += ' | Lembur: Rp ' + lemburTujuanNum.toLocaleString('id-ID');
                line += '</span>';
                detailHtml += '<div class="flex justify-between text-sm">' + line + '</div>';
            }
        });

        detailHtml += '</div>' +
            '<div class="border-t pt-2 mt-2 text-xs text-gray-500">' +
            'Data gaji akan dihitung ulang berdasarkan perubahan yang dibuat.' +
            '</div></div>';

        document.getElementById('konfirmasiDetail').innerHTML = detailHtml;
        var modal = document.getElementById('konfirmasiModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closeKonfirmasiEdit = function() {
        document.getElementById('konfirmasiModal').classList.remove('flex');
        document.getElementById('konfirmasiModal').classList.add('hidden');
    };

    window.submitEdit = function() {
        closeKonfirmasiEdit();
        document.getElementById('formEditGaji').submit();
    };
})();
