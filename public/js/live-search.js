/* ============================================================
   SIDIGAS live-search — pencarian & filter tanpa reload halaman.
   Cara pakai di blade:
     1. Bungkus SEMUA input filter (text/select/date) + hasil dalam:
          <div data-live-root>
              ... filter (input#liveSearch, select/input[data-live-filter]) ...
              <div id="liveResults">@include('....partials....')</div>
          </div>
     2. TIDAK perlu ubah controller — server me-render halaman penuh
        seperti biasa, JS hanya mencuplik <div id="liveResults"> dari
        HTML respons lalu menukar isinya (tanpa reload/navigasi).
     3. Muat file ini SETELAH script halaman lain. HAPUS handler
        live-search lama di JS halaman (yang pakai window.location.href).
   Perilaku: ketik (debounce 300ms) / ganti select / klik pagination
   → fetch AJAX → ganti isi #liveResults → URL di-sync (replaceState).
   ============================================================ */
(function () {
    'use strict';

    function initLiveRoot(root) {
        var results = root.querySelector('#liveResults');
        var searchInput = root.querySelector('#liveSearch');
        var loading = root.querySelector('#searchLoading');
        var clearBtn = root.querySelector('#clearSearch');
        if (!results) return;

        var debounceTimer = null;
        var aborter = null;

        // Hanya form GET yang ikut (form POST tambah/edit/hapus/toggle
        // di dalam root DIABAIKAN agar tidak bocor ke query string).
        function getFilterEls() {
            return root.querySelectorAll(
                'form[method="GET"] [name], form:not([method]) [name], [data-live-filter][name]'
            );
        }

        function collectParams() {
            var params = new URLSearchParams();
            getFilterEls().forEach(function (el) {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    if (el.checked && el.value !== '') params.append(el.name, el.value);
                    return;
                }
                if (el.value !== '') params.append(el.name, el.value);
            });
            // liveSearch tanpa name tetap ikut sebagai ?search=
            if (searchInput && !searchInput.name) {
                var q = searchInput.value.trim();
                if (q !== '') params.set('search', q);
            }
            return params;
        }

        function setLoading(on) {
            if (loading) loading.classList.toggle('hidden', !on);
        }

        function syncClearBtn() {
            if (!clearBtn || !searchInput) return;
            clearBtn.classList.toggle('hidden', searchInput.value.trim() === '');
        }

        function fetchResults(url) {
            if (aborter) aborter.abort();
            aborter = new AbortController();
            setLoading(true);
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: aborter.signal
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.text();
                })
                .then(function (html) {
                    // Server me-render halaman penuh; cuplik #liveResults saja
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var fresh = doc.getElementById('liveResults');
                    results.innerHTML = fresh ? fresh.innerHTML : html;
                    window.history.replaceState(null, '', url);
                })
                .catch(function (err) {
                    // AbortError = ketikan baru menyalip, abaikan diam-diam
                    if (!err || err.name !== 'AbortError') window.location.href = url;
                })
                .finally(function () { setLoading(false); });
        }

        function buildUrl() {
            var url = new URL(window.location.href);
            url.search = collectParams().toString();
            // reset ke halaman 1 setiap filter berubah (kecuali klik pagination)
            url.searchParams.delete('page');
            return url.toString();
        }

        function onFilterChange(fromPagination, pageUrl) {
            if (fromPagination) fetchResults(pageUrl);
            else fetchResults(buildUrl());
        }

        // 1. Ketik di search (debounce)
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                syncClearBtn();
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () { onFilterChange(false); }, 300);
            });
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(debounceTimer);
                    onFilterChange(false);
                }
            });
            syncClearBtn();
        }

        // 2. Ganti select / date dalam form GET
        root.querySelectorAll('form[method="GET"] select[name], form:not([method]) select[name], form[method="GET"] input[type="date"][name], [data-live-filter]').forEach(function (el) {
            if (el === searchInput) return;
            el.addEventListener('change', function () { onFilterChange(false); });
        });

        // 2b. Input teks bernama dalam form GET (mis. search kelola) ikut live
        root.querySelectorAll('form[method="GET"] input[type="text"][name], form:not([method]) input[type="text"][name]').forEach(function (el) {
            if (el === searchInput) return;
            el.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () { onFilterChange(false); }, 300);
            });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); clearTimeout(debounceTimer); onFilterChange(false); }
            });
        });

        // 2c. Submit form GET (tombol Cari / Enter) → fetch, bukan reload
        root.querySelectorAll('form[method="GET"], form:not([method])').forEach(function (f) {
            f.addEventListener('submit', function (e) {
                e.preventDefault();
                clearTimeout(debounceTimer);
                onFilterChange(false);
            });
        });

        // 3. Tombol clear
        if (clearBtn && searchInput) {
            clearBtn.addEventListener('click', function () {
                searchInput.value = '';
                syncClearBtn();
                onFilterChange(false);
                searchInput.focus();
            });
        }

        // 4. Klik pagination di dalam hasil (event delegation, tetap hidup
        //    walau isi #liveResults diganti). Nomor halaman diambil dari
        //    tautan, filter aktif dipertahankan dari form saat ini.
        results.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]');
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#') return;
            var m = href.match(/[?&]page=(\d+)/);
            if (!m) return;
            e.preventDefault();
            var url = new URL(window.location.href);
            url.search = collectParams().toString();
            url.searchParams.set('page', m[1]);
            fetchResults(url.toString());
        });
    }

    function initAll() {
        document.querySelectorAll('[data-live-root]').forEach(initLiveRoot);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
