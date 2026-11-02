/* submit-guard.js — cegah klik ganda / input dobel pada semua form.
   Cara kerja:
   1. submit pertama  -> tombol di-disable + ditandai, request lanjut.
   2. submit kedua    -> dibatalkan (form sudah bertanda).
   3. form.submit()   -> dibungkus, karena tidak memicu event submit.
   4. tombol Back     -> semua kuncian dilepas (bfcache).
   Escape hatch: tambahkan atribut data-no-guard pada <form> bila perlu. */
(function () {
    'use strict';

    var STORE = 'submitGuardLabel';

    function pickButton(form, submitter) {
        if (submitter && (submitter.type === 'submit' || submitter.tagName === 'BUTTON')) return submitter;
        return form.querySelector('button[type="submit"], input[type="submit"], button:not([type])');
    }

    function lock(form, submitter) {
        if (form.dataset.submitGuard === '1') return false;
        form.dataset.submitGuard = '1';

        var btn = pickButton(form, submitter);
        if (!btn || btn.disabled) return true;

        if (btn.tagName === 'INPUT') {
            btn.dataset[STORE + 'Value'] = btn.value;
        } else {
            btn.dataset[STORE] = btn.innerHTML;
        }
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        btn.style.opacity = '0.6';
        btn.style.cursor = 'wait';
        btn.style.pointerEvents = 'none';
        return true;
    }

    function unlock(form) {
        delete form.dataset.submitGuard;
        var btn = form.querySelector('[aria-busy="true"]');
        if (!btn) return;

        btn.disabled = false;
        btn.removeAttribute('aria-busy');
        btn.style.opacity = '';
        btn.style.cursor = '';
        btn.style.pointerEvents = '';

        if (btn.dataset[STORE + 'Value'] !== undefined) {
            btn.value = btn.dataset[STORE + 'Value'];
            delete btn.dataset[STORE + 'Value'];
        }
        if (btn.dataset[STORE] !== undefined) {
            btn.innerHTML = btn.dataset[STORE];
            delete btn.dataset[STORE];
        }
    }

    function unlockAll() {
        document.querySelectorAll('form[data-submit-guard]').forEach(unlock);
    }

    // 1 & 2 — submit lewat tombol / Enter.
    // Fase bubble: confirm() bawaan form sudah jalan lebih dulu, jadi batal-user
    // (defaultPrevented) tidak ikut mengunci tombol.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.hasAttribute('data-no-guard')) return;

        if (form.dataset.submitGuard === '1') {
            e.preventDefault();
            return;
        }
        if (e.defaultPrevented) return;

        if (!lock(form, e.submitter)) e.preventDefault();
    }, false);

    // 3 — form.submit() tidak memicu event submit, jadi dibungkus.
    var nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        if (!this.hasAttribute('data-no-guard')) {
            if (this.dataset.submitGuard === '1') return;
            lock(this, null);

            // target _blank tidak memuat ulang halaman -> lepas kuncian sendiri
            if (this.target === '_blank') {
                var self = this;
                setTimeout(function () { unlock(self); }, 1500);
            }
        }
        return nativeSubmit.apply(this, arguments);
    };

    // 4 — halaman diambil dari bfcache (tombol Back) -> tombol harus hidup lagi.
    window.addEventListener('pageshow', function (ev) {
        if (ev.persisted) unlockAll();
    });
})();
