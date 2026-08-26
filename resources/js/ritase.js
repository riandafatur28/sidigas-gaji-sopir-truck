// ===== RITASE PAGE JS =====
// Extracted from ritase/index.blade.php for maintainability

document.addEventListener('DOMContentLoaded', function() {
    initFilterToggle();
    initTomSelects();
    initFormSubmit();
    initLiveSearch();
    initDetailSearch();
    initModalOverlayClose();
    initEscapeKeyClose();
    autoCalculateDT();
    switchTab(1);
});

// ===== FILTER TOGGLE =====
function initFilterToggle() {
    document.addEventListener('click', function(e) {
        const w = document.getElementById('ritFilterWrap');
        if (w && !w.contains(e.target)) {
            document.getElementById('ritFilterPanel').classList.add('hidden');
            var c = document.getElementById('ritChevron');
            if (c) c.style.transform = '';
        }
    });
}

function toggleRitFilter() {
    var p = document.getElementById('ritFilterPanel'), c = document.getElementById('ritChevron');
    p.classList.toggle('hidden');
    if (c) c.style.transform = p.classList.contains('hidden') ? '' : 'rotate(180deg)';
}

// Period data for date-based period auto-detection
var periodData = window.ritasePeriodData || [];

function onTanggalChange(input) {
    var dateVal = input.value;
    if (!dateVal) { input.form.submit(); return; }
    var match = periodData.find(function(p) { return dateVal >= p.mulai && dateVal <= p.selesai; });
    if (match) document.getElementById('filterPeriode').value = match.id;
    input.form.submit();
}

// ===== TAB SWITCHING =====
var activeTab = 1;

function switchTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.add('hidden'); p.classList.remove('active'); });
    document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.add('active');
    var panel = document.getElementById('tab-content-' + tab);
    panel.classList.remove('hidden');
    panel.classList.add('active');
    if (tab === 2) loadDetailData();
}

// ===== DETAIL RITASE (TAB 2) =====
var detailCurrentPage = 1;

function loadDetailPage(page) {
    if (page) detailCurrentPage = page;
    loadDetailData();
}

function loadDetailData() {
    var periode = document.getElementById('detailPeriode').value;
    var search = document.getElementById('detailSearch').value;
    var container = document.getElementById('detailContainer');

    if (!periode) {
        container.innerHTML = '<div class="text-center py-16"><svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg><p class="text-gray-500 font-semibold">Pilih periode untuk menampilkan detail ritase</p><p class="text-gray-400 text-sm mt-1">Gunakan filter periode di atas untuk melihat data.</p></div>';
        return;
    }

    container.innerHTML = '<div class="flex items-center justify-center py-16"><svg class="animate-spin h-8 w-8 text-gray-400" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg><p class="text-sm text-gray-500 ml-3">Memuat data...</p></div>';

    fetch('/ritase/detail-data?periode=' + encodeURIComponent(periode) + '&search=' + encodeURIComponent(search) + '&page=' + detailCurrentPage)
        .then(function(r) { return r.json(); })
        .then(function(json) {
            if (!json.sopirs || json.sopirs.length === 0) {
                if (json.pagination && json.pagination.total > 0) {
                    detailCurrentPage = Math.max(1, json.pagination.page - 1);
                    if (detailCurrentPage >= 1) { loadDetailData(); return; }
                }
                container.innerHTML = '<div class="text-center py-16"><svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg><p class="text-gray-500 font-semibold">Tidak ada data untuk periode ini</p><p class="text-gray-400 text-sm mt-1">Belum ada ritase tercatat pada periode yang dipilih.</p></div>';
                return;
            }

            var pag = json.pagination || { page: 1, last_page: 1, total: 0 };
            var numCols = json.columns.length;
            var totalDays = numCols / 2;
            var cellClass = totalDays > 5 ? 'px-1.5 py-2 text-sm leading-tight' : 'px-3 py-2.5 text-sm';
            var sopirWidth = totalDays > 5 ? 'min-width:130px' : 'min-width:160px';
            var colWidth = totalDays > 5 ? 'min-width:56px' : 'min-width:68px';
            var html = '<div class="table-responsive" style="max-height:75vh;overflow-y:auto"><table class="detail-table" style="border-collapse:collapse;min-width:100%"><thead>';
            var dayNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

            html += '<tr>';
            html += '<th class="px-3 py-2.5 text-left text-sm font-semibold text-gray-500 uppercase tracking-wider" rowspan="2" style="' + sopirWidth + ';border:1px solid #e5e7eb;background:#f9fafb">Nama Sopir</th>';

            var currentDate = '';
            json.columns.forEach(function(col) {
                if (col.date !== currentDate) {
                    currentDate = col.date;
                    var parts = col.date.split('-');
                    var dt = new Date(parseInt(parts[0]), parseInt(parts[1])-1, parseInt(parts[2]));
                    var dayLabel = dayNames[dt.getDay()];
                    var dateLabel = parts[2] + '/' + parts[1];
                    html += '<th class="px-1.5 py-2 text-center text-sm font-semibold text-gray-500 uppercase tracking-wider" colspan="2" style="border:1px solid #e5e7eb;background:#f9fafb">' + dateLabel + '<br><span class="text-xs font-normal text-gray-400">' + dayLabel + '</span></th>';
                }
            });
            html += '<th class="px-3 py-2.5 text-center text-sm font-semibold text-gray-500 uppercase tracking-wider" rowspan="2" style="min-width:80px;border:1px solid #e5e7eb;background:#f9fafb">Ritase Berhasil</th>';
            html += '<th class="px-3 py-2.5 text-center text-sm font-semibold text-gray-500 uppercase tracking-wider" rowspan="2" style="min-width:80px;border:1px solid #e5e7eb;background:#f9fafb">Ritase Gagal</th>';
            html += '</tr>';

            html += '<tr>';
            json.columns.forEach(function(col) {
                var cls = col.waktu === 'P' ? 'text-amber-600' : 'text-green-600';
                var label = col.waktu === 'P' ? 'Pagi' : 'Malam';
                html += '<th class="px-1 py-1.5 text-center text-[10px] font-semibold uppercase tracking-wider ' + cls + '" style="border:1px solid #e5e7eb;background:#f9fafb;' + colWidth + '">' + label + '</th>';
            });
            html += '</tr></thead><tbody>';

            var pageBerhasil = 0, pageGagal = 0;
            var perColumnTotals = {};
            json.columns.forEach(function(col) { perColumnTotals[col.key] = 0; });

            json.sopirs.forEach(function(s) {
                var berhasil = (json.counts && json.counts[s.kode_sopir]) ? json.counts[s.kode_sopir].ritase_berhasil : 0;
                var gagal = (json.counts && json.counts[s.kode_sopir]) ? json.counts[s.kode_sopir].ritase_gagal : 0;
                pageBerhasil += berhasil;
                pageGagal += gagal;
                json.columns.forEach(function(col) {
                    if (json.data[s.kode_sopir] && json.data[s.kode_sopir][col.key]) {
                        perColumnTotals[col.key] += json.data[s.kode_sopir][col.key].length;
                    }
                });

                html += '<tr class="hover:bg-gray-50">';
                html += '<td class="px-3 py-2.5 text-sm font-semibold text-gray-900" style="border:1px solid #e5e7eb">' + escapeHtml(s.nama) + '</td>';
                json.columns.forEach(function(col) {
                    var cell = '';
                    if (json.data[s.kode_sopir] && json.data[s.kode_sopir][col.key]) {
                        cell = json.data[s.kode_sopir][col.key].join('<br>');
                    }
                    html += '<td class="text-center align-middle ' + cellClass + ' ' + (col.waktu === 'P' ? 'bg-amber-50/30' : 'bg-green-50/30') + ' text-gray-700" style="border:1px solid #e5e7eb;font-weight:500">';
                    html += cell || '<span class="text-gray-300">-</span>';
                    html += '</td>';
                });
                html += '<td class="text-center align-middle px-3 py-2.5 text-sm font-bold text-green-700" style="border:1px solid #e5e7eb">' + berhasil + '</td>';
                html += '<td class="text-center align-middle px-3 py-2.5 text-sm font-bold text-red-600" style="border:1px solid #e5e7eb">' + gagal + '</td>';
                html += '</tr>';
            });

            html += '<tr class="bg-amber-50">';
            html += '<td class="px-3 py-2.5 text-sm font-bold text-gray-700" style="border:1px solid #e5e7eb">Subtotal Halaman</td>';
            json.columns.forEach(function(col) {
                html += '<td class="text-center align-middle px-1 py-2.5 text-sm font-bold text-gray-900" style="border:1px solid #e5e7eb">' + (perColumnTotals[col.key] || 0) + '</td>';
            });
            html += '<td class="text-center align-middle px-3 py-2.5 text-sm font-bold text-gray-900" style="border:1px solid #e5e7eb">' + pageBerhasil + '</td>';
            html += '<td class="text-center align-middle px-3 py-2.5 text-sm font-bold text-red-600" style="border:1px solid #e5e7eb">' + pageGagal + '</td>';
            html += '</tr></tbody></table></div>';

            // PDF buttons + pagination
            html += '<div class="flex items-center justify-between px-4 py-3 border-t border-gray-100">';
            html += '<div class="flex items-center gap-2">';
            html += '<button onclick="openPdfModal(' + periode + ')" class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 rounded text-sm bg-white hover:bg-gray-50 font-medium" style="color:var(--text);cursor:pointer" type="button">';
            html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z"/></svg>';
            html += 'Lihat PDF</button>';
            html += '<a href="/ritase/detail-pdf?periode=' + periode + '" class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 rounded text-sm bg-white hover:bg-gray-50 font-medium" style="color:var(--text)">';
            html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
            html += 'Download PDF</a></div>';

            if (pag.last_page > 1) {
                html += '<div class="flex items-center gap-3">';
                html += '<span class="text-sm text-gray-600">Halaman ' + pag.page + ' dari ' + pag.last_page + ' (' + pag.total + ' sopir)</span>';
                html += '<div class="flex items-center space-x-1.5">';
                html += pag.page <= 1
                    ? '<span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Sebelumnya</span>'
                    : '<a href="#" onclick="loadDetailPage(' + (pag.page - 1) + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Sebelumnya</a>';

                var w = 2, ss = Math.max(1, pag.page - w), ee = Math.min(pag.last_page, pag.page + w);
                if (ss > 1) {
                    html += '<a href="#" onclick="loadDetailPage(1); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">1</a>';
                    if (ss > 2) html += '<span class="px-3 py-1.5 text-sm text-gray-400">...</span>';
                }
                for (var p = ss; p <= ee; p++) {
                    html += p == pag.page
                        ? '<span class="px-3 py-1.5 text-sm font-bold text-white bg-[#2d6a4f] border border-[#2d6a4f] rounded">' + p + '</span>'
                        : '<a href="#" onclick="loadDetailPage(' + p + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">' + p + '</a>';
                }
                if (ee < pag.last_page) {
                    if (ee < pag.last_page - 1) html += '<span class="px-3 py-1.5 text-sm text-gray-400">...</span>';
                    html += '<a href="#" onclick="loadDetailPage(' + pag.last_page + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">' + pag.last_page + '</a>';
                }
                html += pag.page >= pag.last_page
                    ? '<span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Selanjutnya</span>'
                    : '<a href="#" onclick="loadDetailPage(' + (pag.page + 1) + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Selanjutnya</a>';
                html += '</div></div>';
            }
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(function(err) {
            container.innerHTML = '<div class="text-center py-16"><p class="text-red-500">Gagal memuat data: ' + err.message + '</p></div>';
        });
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

// ===== DELETE CONFIRMATION =====
function confirmDeleteRitase(id, kode) {
    showConfirmModal({
        title: 'Hapus Data Ritase?',
        message: 'Anda yakin ingin menghapus ritase ' + kode + '? Tindakan ini tidak dapat dibatalkan.',
        type: 'danger',
        confirmText: 'Ya, Hapus',
        onConfirm: function() { document.getElementById('deleteRitase_' + id).submit(); }
    });
}

// ===== AUTO CALCULATE DT =====
function autoCalculateDT() {
    var kabupaten = document.getElementById('kabupaten');
    var waktu = document.getElementById('waktu');
    var status = document.getElementById('status');
    var kompensasi = document.getElementById('nominal_kompensasi');
    var dtInput = document.getElementById('dt');
    var kodeSopir = document.getElementById('kode_sopir');
    var tanggal = document.getElementById('tanggal');

    function hitungDT() {
        var kab = kabupaten.value, waktuVal = waktu.value, statusVal = status.value;
        var sopir = kodeSopir.value, tgl = tanggal.value;
        var dt = 0, keterangan = '';

        if (statusVal === 'gagal_produksi') {
            dtInput.value = 0;
            document.getElementById('previewKeterangan').textContent = 'Gagal Produksi - Tidak dapat DT';
            document.getElementById('previewRitKe').textContent = '-';
            document.getElementById('previewSewaDT').textContent = '0';
            document.getElementById('previewAturan').classList.remove('hidden');
            return;
        }

        if (sopir && tgl && kab && waktuVal) {
            fetch('/ritase/cek-aturan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ kode_sopir: sopir, tanggal: tgl, waktu: waktuVal, kabupaten: kab, status: statusVal, nominal_kompensasi: parseFloat(kompensasi.value) || 0 })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                dtInput.value = data.sewa_dt || 0;
                document.getElementById('previewKeterangan').textContent = data.keterangan || '';
                document.getElementById('previewRitKe').textContent = data.rit_keberapa || '-';
                document.getElementById('previewSewaDT').textContent = (data.sewa_dt || 0).toLocaleString('id-ID');
                document.getElementById('previewAturan').classList.remove('hidden');
            })
            .catch(function() { dtInput.value = 0; });
        } else {
            dtInput.value = 0;
            document.getElementById('previewAturan').classList.add('hidden');
        }
    }

    [kabupaten, waktu, status, kompensasi, kodeSopir, tanggal].forEach(function(el) {
        if (el) { el.addEventListener('change', hitungDT); el.addEventListener('input', hitungDT); }
    });
    setTimeout(hitungDT, 100);
}

// ===== TOGGLE FIELDS =====
function toggleKompensasiField() {
    var status = document.getElementById('status').value;
    var c = document.getElementById('kompensasi_container');
    if (status === 'gagal_produksi') { c.classList.remove('hidden'); }
    else { c.classList.add('hidden'); document.getElementById('nominal_kompensasi').value = ''; }
}

function toggleLemburField() {
    var cb = document.getElementById('is_lembur'), c = document.getElementById('upah_lembur_container');
    if (cb.checked) { c.classList.remove('hidden'); }
    else { c.classList.add('hidden'); document.getElementById('upah_lembur').value = '0'; }
}

function toggleEditLemburField() {
    var cb = document.getElementById('edit_is_lembur'), c = document.getElementById('edit_upah_lembur_container');
    if (cb.checked) { c.classList.remove('hidden'); }
    else { c.classList.add('hidden'); document.getElementById('edit_upah_lembur').value = '0'; }
}

function toggleEditKompensasiField() {
    var status = document.getElementById('edit_status').value;
    var c = document.getElementById('edit_kompensasi_container');
    if (status === 'gagal_produksi') { c.classList.remove('hidden'); }
    else { c.classList.add('hidden'); document.getElementById('edit_nominal_kompensasi').value = ''; }
}

// ===== TOM SELECT INIT =====
function initTomSelects() {
    if (document.getElementById('kode_sopir')) {
        window.tomSopir = new TomSelect('#kode_sopir', { create: false, sortField: { field:"text", direction:"asc" }, placeholder: 'Ketik nama atau kode sopir...', allowEmptyOption: true, searchField: ['text'] });
    }
    if (document.getElementById('kode_tujuan')) {
        window.tomTujuan = new TomSelect('#kode_tujuan', { create: false, sortField: { field:"text", direction:"asc" }, placeholder: 'Ketik nama tujuan...', allowEmptyOption: true, searchField: ['text'] });
    }
    toggleKompensasiField();
}

// ===== FORM SUBMIT WITH CONFIRMATION =====
var formDataTambah = null;

function initFormSubmit() {
    var form = document.getElementById('formTambahRitase');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(form);
        var requiredFields = ['periode_id', 'kode_sopir', 'kode_tujuan', 'tanggal', 'waktu', 'kabupaten', 'status'];
        var valid = true, errorMessage = '';

        requiredFields.forEach(function(field) {
            if (!formData.get(field) || formData.get(field) === '') { valid = false; errorMessage += 'Field ' + field.replace('_', ' ') + ' wajib diisi!\n'; }
        });
        if (!valid) { alert(errorMessage); return; }

        var catatan = formData.get('catatan');
        if (catatan && !/^[a-zA-Z0-9\s\-\.]+$/.test(catatan)) {
            document.getElementById('error_catatan').classList.remove('hidden');
            document.getElementById('catatan').classList.add('border-red-500');
            return;
        }
        document.getElementById('error_catatan').classList.add('hidden');
        document.getElementById('catatan').classList.remove('border-red-500');

        var nominal = formData.get('nominal_kompensasi');
        if (nominal && !/^\d+$/.test(nominal)) {
            document.getElementById('error_kompensasi').classList.remove('hidden');
            document.getElementById('nominal_kompensasi').classList.add('border-red-500');
            return;
        }
        document.getElementById('error_kompensasi').classList.add('hidden');
        document.getElementById('nominal_kompensasi').classList.remove('border-red-500');

        formDataTambah = formData;

        var sopir = document.getElementById('kode_sopir');
        var tujuan = document.getElementById('kode_tujuan');
        var periode = document.getElementById('periode_id');
        var status = formData.get('status');
        var nominalValue = parseFloat(formData.get('nominal_kompensasi') || 0);
        var dt = parseFloat(formData.get('dt') || 0);

        var kompensasiHtml = '';
        if (status === 'gagal_produksi') {
            kompensasiHtml = nominalValue > 0
                ? '<div class="flex justify-between"><span class="text-gray-500">Kompensasi:</span><span class="font-semibold text-red-600">Rp ' + nominalValue.toLocaleString('id-ID') + '</span></div>'
                : '<div class="flex justify-between"><span class="text-gray-500">Kompensasi:</span><span class="font-semibold text-gray-600">Belum ditentukan</span></div>';
        }

        document.getElementById('konfirmasiDetail').innerHTML = '<div class="space-y-2">'
            + '<div class="flex justify-between"><span class="text-gray-500">Periode:</span><span class="font-semibold text-gray-900">' + periode.options[periode.selectedIndex].text + '</span></div>'
            + '<div class="flex justify-between"><span class="text-gray-500">Sopir:</span><span class="font-semibold text-gray-900">' + sopir.options[sopir.selectedIndex].text + '</span></div>'
            + '<div class="flex justify-between"><span class="text-gray-500">Tujuan:</span><span class="font-semibold text-gray-900">' + tujuan.options[tujuan.selectedIndex].text + '</span></div>'
            + '<div class="flex justify-between"><span class="text-gray-500">Tanggal:</span><span class="font-semibold text-gray-900">' + formData.get('tanggal') + '</span></div>'
            + '<div class="flex justify-between"><span class="text-gray-500">Waktu:</span><span class="font-semibold text-gray-900 capitalize">' + formData.get('waktu') + '</span></div>'
            + '<div class="flex justify-between"><span class="text-gray-500">Kabupaten:</span><span class="font-semibold text-gray-900">' + formData.get('kabupaten') + '</span></div>'
            + '<div class="flex justify-between"><span class="text-gray-500">Status:</span><span class="font-semibold text-gray-900 capitalize">' + status.replace('_', ' ') + '</span></div>'
            + '<div class="flex justify-between"><span class="text-gray-500">DT (Sewa DT):</span><span class="font-semibold text-gray-800">Rp ' + dt.toLocaleString('id-ID') + '</span></div>'
            + '<div class="flex justify-between"><span class="text-gray-500">Lembur:</span><span class="font-semibold text-gray-900">' + (document.getElementById('is_lembur').checked ? 'Ya' : 'Tidak') + '</span></div>'
            + kompensasiHtml + '</div>';

        var modal = document.getElementById('tambahModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });
}

function submitTambahRitase() {
    if (!formDataTambah) return;
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = window.ritaseStoreUrl;
    var csrf = document.createElement('input');
    csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = document.querySelector('meta[name="csrf-token"]').content;
    form.appendChild(csrf);
    for (var pair of formDataTambah.entries()) {
        var input = document.createElement('input');
        input.type = 'hidden'; input.name = pair[0]; input.value = pair[1];
        form.appendChild(input);
    }
    document.body.appendChild(form);
    closeTambahModal();
    form.submit();
}

// ===== MODALS =====
function openEditModal(ritase) {
    document.getElementById('editForm').action = window.ritaseUpdateUrl.replace('__ID__', ritase.id);
    document.getElementById('edit_kode_ritase').value = ritase.kode_ritase;
    document.getElementById('edit_periode_id').value = ritase.periode_id;
    document.getElementById('edit_tanggal').value = ritase.tanggal;
    document.getElementById('edit_waktu').value = ritase.waktu;
    document.getElementById('edit_kabupaten').value = ritase.kabupaten;
    document.getElementById('edit_status').value = ritase.status;
    document.getElementById('edit_dt').value = ritase.dt || 0;
    document.getElementById('edit_catatan').value = ritase.catatan || '';
    document.getElementById('edit_is_lembur').checked = ritase.is_lembur == 1 || ritase.is_lembur === true;
    document.getElementById('edit_upah_lembur').value = ritase.upah_lembur || 0;
    toggleEditLemburField();

    setTimeout(function() {
        if (window.tomEditSopir) window.tomEditSopir.destroy();
        if (window.tomEditTujuan) window.tomEditTujuan.destroy();
        window.tomEditSopir = new TomSelect('#edit_kode_sopir', { create: false, sortField: { field:"text", direction:"asc" }, placeholder: 'Ketik nama atau kode sopir...', searchField: ['text'] });
        window.tomEditTujuan = new TomSelect('#edit_kode_tujuan', { create: false, sortField: { field:"text", direction:"asc" }, placeholder: 'Ketik nama tujuan...', searchField: ['text'] });
        window.tomEditSopir.setValue(ritase.kode_sopir);
        window.tomEditTujuan.setValue(ritase.kode_tujuan);
    }, 100);

    var kompContainer = document.getElementById('edit_kompensasi_container');
    var nominalInput = document.getElementById('edit_nominal_kompensasi');
    if (ritase.status === 'gagal_produksi') { kompContainer.classList.remove('hidden'); nominalInput.value = ritase.nominal_kompensasi || ''; }
    else { kompContainer.classList.add('hidden'); nominalInput.value = ''; }

    var modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditModal() { var m = document.getElementById('editModal'); m.classList.remove('flex'); m.classList.add('hidden'); }
function closeTambahModal() { var m = document.getElementById('tambahModal'); m.classList.remove('flex'); m.classList.add('hidden'); formDataTambah = null; }

function openPdfModal(periodeId) {
    document.getElementById('pdfIframe').src = '/ritase/detail-pdf?periode=' + periodeId + '&view=1';
    var m = document.getElementById('pdfModal'); m.classList.remove('hidden'); m.classList.add('flex');
}
function closePdfModal() {
    document.getElementById('pdfIframe').src = 'about:blank';
    var m = document.getElementById('pdfModal'); m.classList.remove('flex'); m.classList.add('hidden');
}

function initModalOverlayClose() {
    document.querySelectorAll('.fixed.inset-0').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('flex'); this.classList.add('hidden');
                if (this.id === 'tambahModal') formDataTambah = null;
            }
        });
    });
}

function initEscapeKeyClose() {
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { closeEditModal(); closeTambahModal(); closePdfModal(); }
    });
}

// ===== LIVE SEARCH =====
function initLiveSearch() {
    var searchInput = document.getElementById('liveSearch');
    var clearSearch = document.getElementById('clearSearch');
    if (!searchInput) return;
    var debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            var query = searchInput.value.trim();
            var url = new URL(window.location.href);
            if (query) { url.searchParams.set('search', query); clearSearch.classList.remove('hidden'); }
            else { url.searchParams.delete('search'); clearSearch.classList.add('hidden'); }
            window.location.href = url.toString();
        }, 500);
    });
    clearSearch.addEventListener('click', function() { searchInput.value = ''; searchInput.dispatchEvent(new Event('input')); searchInput.focus(); });
    if (searchInput.value) clearSearch.classList.remove('hidden');
}

function initDetailSearch() {
    var ds = document.getElementById('detailSearch');
    if (!ds) return;
    var dTimer;
    ds.addEventListener('input', function() {
        clearTimeout(dTimer);
        detailCurrentPage = 1;
        dTimer = setTimeout(loadDetailData, 400);
    });
}
