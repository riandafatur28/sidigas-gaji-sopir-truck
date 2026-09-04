/**
 * Penggajian Page - JS extracted from blade
 * Expects global variables:
 *   window.penggajianAllTujuans, window.penggajianHasErrors
 */
(function() {
    'use strict';

    const allTujuans = window.penggajianAllTujuans || [];
    const hasErrors = window.penggajianHasErrors || false;

    let gajiData = [];
    let gajiDataAll = [];
    let formDataGaji = null;
    let periodeId = null;
    let currentPage = 1;
    const pageSize = 10;

    function validasiNominal(input) {
        return /^\d+(\.\d+)?$/.test(input) && parseFloat(input) >= 0;
    }

    function formatRupiah(angka) {
        return Math.round(angka).toLocaleString('id-ID');
    }

    function init() {
        const urlParams = new URLSearchParams(window.location.search);
        const periodeFromUrl = urlParams.get('periode');
        const periodeSelect = document.getElementById('pilih_periode');
        const formPeriodeInput = document.getElementById('formPeriodeId');

        // Determine periodeId from multiple sources (priority: URL > select value > hidden input)
        if (periodeFromUrl && !hasErrors && periodeSelect) {
            periodeSelect.value = periodeFromUrl;
            periodeId = periodeFromUrl;
            if (formPeriodeInput) formPeriodeInput.value = periodeFromUrl;
        } else if (periodeSelect && periodeSelect.value) {
            periodeId = periodeSelect.value;
        } else if (formPeriodeInput && formPeriodeInput.value) {
            // Fallback to hidden input value (server-side rendered default)
            periodeId = formPeriodeInput.value;
            if (periodeSelect) periodeSelect.value = periodeId;
        }

        const filterTanggalEl = document.getElementById('filterTanggal');
        if (filterTanggalEl) {
            filterTanggalEl.addEventListener('change', function() {
                if (periodeId) loadGajiData(periodeId);
            });
        }

        const searchSopirEl = document.getElementById('searchSopirTujuan');
        if (searchSopirEl) {
            searchSopirEl.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') applySearch();
            });
        }

        document.addEventListener('input', function(e) {
            if (e.target.matches('input[data-field="bbm_per_rit"], input[data-field="upah_per_rit"], input[data-field="tol_per_rit"], input[data-field="lembur_per_rit"], input[data-field="kompensasi_gagal"]')) {
                if (gajiData.length > 0) renderTabelGaji(gajiData);
            }
        });

        document.querySelectorAll('.tol-checkbox').forEach(function(cb) {
            cb.addEventListener('change', function() {
                const row = this.closest('tr');
                const input = row.querySelector('.tol-input');
                if (this.checked) {
                    input.disabled = false;
                    input.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                    input.classList.add('bg-white');
                    input.focus();
                } else {
                    input.disabled = true;
                    input.value = '0';
                    input.classList.remove('bg-white');
                    input.classList.add('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                    if (gajiData.length > 0) renderTabelGaji(gajiData);
                }
            });
        });

        document.querySelectorAll('.lembur-tujuan-checkbox').forEach(function(cb) {
            cb.addEventListener('change', function() {
                const row = this.closest('tr');
                const input = row.querySelector('.lembur-tujuan-input');
                if (this.checked) {
                    input.disabled = false;
                    input.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                    input.classList.add('bg-white');
                    input.focus();
                } else {
                    input.disabled = true;
                    input.value = '0';
                    input.classList.remove('bg-white');
                    input.classList.add('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                    if (gajiData.length > 0) renderTabelGaji(gajiData);
                }
            });
        });

        // Auto-load data if periodeId is available
        if (periodeId) {
            loadGajiData(periodeId);
        }
    }

    function loadGajiData(periodeId) {
        const container = document.getElementById('tabelGajiContainer');
        const sumContainer = document.getElementById('summaryContainer');
        const tbody = document.getElementById('tabelGajiBody');
        const periodeLabel = document.getElementById('periodeLabel');
        const periodeSelect = document.getElementById('pilih_periode');

        if (!container || !tbody) return;

        container.classList.remove('hidden');
        if (sumContainer) sumContainer.classList.remove('hidden');
        tbody.innerHTML = renderSkeleton(6);

        if (periodeSelect && periodeSelect.options[periodeSelect.selectedIndex]) {
            const periodeText = periodeSelect.options[periodeSelect.selectedIndex].text;
            if (periodeLabel) periodeLabel.textContent = 'Periode: ' + periodeText;
        }

        let url = '/api/get-ritase-data?periode=' + periodeId;
        const filterTanggalEl = document.getElementById('filterTanggal');
        if (filterTanggalEl && filterTanggalEl.value) {
            url += '&tanggal=' + encodeURIComponent(filterTanggalEl.value);
        }

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(function(response) {
                if (!response.ok) throw new Error('Gagal memuat data');
                return response.json();
            })
            .then(function(data) {
                if (data.error) {
                    throw new Error(data.error);
                }
                if (!data.sopir || !Array.isArray(data.sopir)) {
                    throw new Error('Format data tidak valid dari server');
                }
                if (data.sopir.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="10" class="px-4 py-8 text-center text-gray-500 whitespace-nowrap">Tidak ada data ritase untuk periode ini</td></tr>';
                    var grandTotalAllEl = document.getElementById('grandTotalAll');
                    if (grandTotalAllEl) grandTotalAllEl.textContent = 'Rp 0';
                    return;
                }

                gajiData = data.sopir;
                gajiDataAll = data.sopir;
                currentPage = 1;

                if (data.default_rates && !hasErrors) {
                    Object.keys(data.default_rates).forEach(function(kodeTujuan) {
                        var rate = data.default_rates[kodeTujuan];
                        var bbmInput = document.querySelector('input[data-tujuan="' + kodeTujuan + '"][data-field="bbm_per_rit"]');
                        var upahInput = document.querySelector('input[data-tujuan="' + kodeTujuan + '"][data-field="upah_per_rit"]');

                        if (bbmInput && parseFloat(bbmInput.value) === 0) bbmInput.value = rate.bbm_per_rit;
                        if (upahInput && parseFloat(upahInput.value) === 0) upahInput.value = rate.upah_per_rit;

                        if (rate.tol_per_rit && parseFloat(rate.tol_per_rit) > 0) {
                            var tolInput = document.querySelector('input[data-tujuan="' + kodeTujuan + '"][data-field="tol_per_rit"]');
                            var tolCheck = document.querySelector('input[data-tujuan="' + kodeTujuan + '"][data-field="tol_check"]');
                            if (tolInput && tolCheck && !tolCheck.checked) {
                                tolInput.value = rate.tol_per_rit;
                                tolCheck.checked = true;
                                tolInput.disabled = false;
                                tolInput.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                                tolInput.classList.add('bg-white');
                            }
                        }
                        if (rate.kompensasi_gagal) {
                            var kompInput = document.querySelector('input[data-tujuan="' + kodeTujuan + '"][data-field="kompensasi_gagal"]');
                            if (kompInput && parseFloat(kompInput.value) === 0) kompInput.value = rate.kompensasi_gagal;
                        }
                        var lemburTujuanVal = rate.lembur_per_rit || 0;
                        if (parseFloat(lemburTujuanVal) > 0) {
                            var lemburTujuanInput = document.querySelector('input[data-tujuan="' + kodeTujuan + '"][data-field="lembur_per_rit"]');
                            var lemburTujuanCheck = document.querySelector('input[data-tujuan="' + kodeTujuan + '"][data-field="lembur_tujuan_check"]');
                            if (lemburTujuanInput && lemburTujuanCheck && !lemburTujuanCheck.checked) {
                                lemburTujuanInput.value = lemburTujuanVal;
                                lemburTujuanCheck.checked = true;
                                lemburTujuanInput.disabled = false;
                                lemburTujuanInput.classList.remove('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                                lemburTujuanInput.classList.add('bg-white');
                            }
                        }
                    });
                }

                renderTabelGaji(data.sopir);
                updateSummary(data.sopir);
            })
            .catch(function(error) {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="10" class="px-4 py-8 text-center text-red-500 whitespace-nowrap">' + (error.message || 'Gagal memuat data') + '</td></tr>';
            });
    }

    function renderTabelGaji(data) {
        var tbody = document.getElementById('tabelGajiBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        var grandTotalAll = 0;

        var bbmByTujuan = {}, upahByTujuan = {}, kompensasiByTujuan = {}, tolByTujuan = {}, lemburByTujuan = {};
        document.querySelectorAll('input[data-field="bbm_per_rit"]').forEach(function(inp) { bbmByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0; });
        document.querySelectorAll('input[data-field="upah_per_rit"]').forEach(function(inp) { upahByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0; });
        document.querySelectorAll('input[data-field="kompensasi_gagal"]').forEach(function(inp) { kompensasiByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0; });
        document.querySelectorAll('input[data-field="tol_per_rit"]').forEach(function(inp) { tolByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0; });
        document.querySelectorAll('input[data-field="lembur_per_rit"]').forEach(function(inp) { lemburByTujuan[inp.dataset.tujuan] = parseFloat(inp.value) || 0; });

        var gagalCountsBySopir = {};
        data.forEach(function(sopir) {
            gagalCountsBySopir[sopir.kode_sopir] = {};
            (sopir.gagal_rits || []).forEach(function(rit) {
                gagalCountsBySopir[sopir.kode_sopir][rit.kode_tujuan] = (gagalCountsBySopir[sopir.kode_sopir][rit.kode_tujuan] || 0) + 1;
            });
        });

        var totalPages = Math.ceil(data.length / pageSize);
        if (currentPage > totalPages) currentPage = Math.max(1, totalPages);
        var start = (currentPage - 1) * pageSize;
        var pageData = data.slice(start, start + pageSize);

        pageData.forEach(function(sopir, idx) {
            var index = start + idx;
            var totalRit = Object.values(sopir.rit_per_tujuan).reduce(function(s, item) { return s + item.total_rit; }, 0);

            var totalSolar = 0, totalUpah = 0, totalTol = 0, totalLembur = 0;
            Object.keys(sopir.rit_per_tujuan).forEach(function(kodeTujuan) {
                var rit = sopir.rit_per_tujuan[kodeTujuan].total_rit;
                totalSolar += (bbmByTujuan[kodeTujuan] || 0) * rit;
                totalUpah += (upahByTujuan[kodeTujuan] || 0) * rit;
                totalTol += (tolByTujuan[kodeTujuan] || 0) * rit;
                totalLembur += (lemburByTujuan[kodeTujuan] || 0) * rit;
            });

            if (totalSolar === 0 && totalUpah === 0 && !sopir.belum_dihitung) {
                totalSolar = sopir.total_solar || 0;
                totalUpah = sopir.total_upah || 0;
                totalTol = sopir.total_tol || 0;
                if (totalLembur === 0) totalLembur = sopir.upah_lembur || 0;
            }

            var totalDT = sopir.total_dt || 0;
            var totalKompensasi = 0;
            Object.keys(kompensasiByTujuan).forEach(function(kodeTujuan) {
                var kompPerRit = kompensasiByTujuan[kodeTujuan] || 0;
                if (kompPerRit > 0) {
                    var sopirGagal = (gagalCountsBySopir[sopir.kode_sopir] || {})[kodeTujuan] || 0;
                    if (sopirGagal > 0) totalKompensasi += kompPerRit * sopirGagal;
                }
            });
            if (totalKompensasi === 0 && !sopir.belum_dihitung) totalKompensasi = sopir.total_kompensasi || 0;

            var previewGrand = totalSolar + totalUpah + totalDT + totalTol + totalKompensasi + totalLembur;
            grandTotalAll += previewGrand;
            var firstChar = sopir.nama_sopir ? sopir.nama_sopir.charAt(0).toUpperCase() : '?';

            var row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.id = 'row_' + sopir.kode_sopir;
            row.innerHTML =
                '<td class="px-4 py-3 whitespace-nowrap">' +
                    '<div class="flex items-center space-x-2">' +
                        '<div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0"><span class="text-gray-700 font-bold text-xs">' + firstChar + '</span></div>' +
                        '<div><p class="text-sm font-semibold text-gray-900">' + sopir.nama_sopir + '</p><p class="text-xs text-gray-500">' + sopir.kode_sopir + '</p></div>' +
                    '</div>' +
                '</td>' +
                '<td class="px-4 py-3 text-center font-semibold whitespace-nowrap">' + totalRit + '</td>' +
                '<td class="px-4 py-3 text-right text-gray-800 font-medium whitespace-nowrap">Rp ' + formatRupiah(totalSolar) + '</td>' +
                '<td class="px-4 py-3 text-right text-gray-800 font-medium whitespace-nowrap">Rp ' + formatRupiah(totalUpah) + '</td>' +
                '<td class="px-4 py-3 text-right text-gray-800 font-medium whitespace-nowrap">Rp ' + formatRupiah(totalDT) + '</td>' +
                '<td class="px-4 py-3 text-right whitespace-nowrap"><span class="text-gray-800 font-medium" id="tolTotal_' + sopir.kode_sopir + '">Rp ' + formatRupiah(totalTol) + '</span></td>' +
                '<td class="px-4 py-3 text-right whitespace-nowrap"><span class="text-gray-800 font-medium" id="kompTotal_' + sopir.kode_sopir + '">Rp ' + formatRupiah(totalKompensasi) + '</span></td>' +
                '<td class="px-4 py-3 text-right whitespace-nowrap"><span class="text-gray-800 font-medium" id="lemburTotal_' + sopir.kode_sopir + '">Rp ' + formatRupiah(totalLembur) + '</span></td>' +
                '<td class="px-4 py-3 text-right font-bold text-gray-900 whitespace-nowrap" id="grandTotal_' + sopir.kode_sopir + '">Rp ' + formatRupiah(previewGrand) + '</td>' +
                '<td class="px-4 py-3 text-center whitespace-nowrap"><button onclick="showDetail(' + index + ')" class="text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium">Detail &amp; Slip</button></td>';
            tbody.appendChild(row);
        });

        var grandTotalAllEl = document.getElementById('grandTotalAll');
        if (grandTotalAllEl) grandTotalAllEl.textContent = 'Rp ' + formatRupiah(grandTotalAll);

        renderPagination(totalPages);
        updateSummary(gajiData);
    }

    function renderSkeleton(rows) {
        var w = function(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; };
        var h = '';
        for (var r = 0; r < rows; r++) {
            h += '<tr class="skeleton-row">' +
                '<td class="px-4 py-3 whitespace-nowrap"><div class="flex items-center space-x-2"><div class="skeleton-box skeleton-avatar flex-shrink-0"></div><div><div class="skeleton-box skeleton-name" style="width:' + w(70,130) + 'px"></div><div class="skeleton-box skeleton-code"></div></div></div></td>' +
                '<td class="px-4 py-3 text-center whitespace-nowrap"><div class="skeleton-box skeleton-rit" style="margin:0 auto"></div></td>' +
                '<td class="px-4 py-3 text-right whitespace-nowrap"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>' +
                '<td class="px-4 py-3 text-right whitespace-nowrap"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>' +
                '<td class="px-4 py-3 text-right whitespace-nowrap"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>' +
                '<td class="px-4 py-3 text-right whitespace-nowrap"><div class="skeleton-box skeleton-number" style="margin-left:auto"></div></td>' +
                '<td class="px-4 py-3 text-right whitespace-nowrap"><div class="skeleton-box skeleton-number" style="width:' + w(60,90) + 'px;margin-left:auto"></div></td>' +
                '<td class="px-4 py-3 text-center whitespace-nowrap"><div class="skeleton-box skeleton-btn" style="margin:0 auto"></div></td>' +
                '</tr>';
        }
        return h;
    }

    function goToPage(page) {
        currentPage = page;
        renderTabelGaji(gajiData);
    }

    function renderPagination(totalPages) {
        var container = document.getElementById('paginationGaji');
        if (!container) return;
        if (totalPages <= 1) {
            container.innerHTML = '';
            var paginationRow = document.getElementById('paginationGajiRow');
            if (paginationRow) paginationRow.classList.add('hidden');
            return;
        }

        var html = '<div class="flex items-center justify-between w-full gap-3">';
        html += '<p class="text-sm text-gray-600 whitespace-nowrap">Halaman ' + currentPage + ' dari ' + totalPages + '</p>';
        html += '<div class="flex items-center space-x-1.5">';

        if (currentPage <= 1) html += '<span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Sebelumnya</span>';
        else html += '<a href="#" onclick="goToPage(' + (currentPage - 1) + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Sebelumnya</a>';

        var w = 2;
        var ss = Math.max(1, currentPage - w);
        var ee = Math.min(totalPages, currentPage + w);

        if (ss > 1) {
            html += '<a href="#" onclick="goToPage(1); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">1</a>';
            if (ss > 2) html += '<span class="px-3 py-1.5 text-sm text-gray-400">...</span>';
        }

        for (var p = ss; p <= ee; p++) {
            if (p == currentPage) html += '<span class="px-3 py-1.5 text-sm font-bold text-white bg-[#2d6a4f] border border-[#2d6a4f] rounded">' + p + '</span>';
            else html += '<a href="#" onclick="goToPage(' + p + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">' + p + '</a>';
        }

        if (ee < totalPages) {
            if (ee < totalPages - 1) html += '<span class="px-3 py-1.5 text-sm text-gray-400">...</span>';
            html += '<a href="#" onclick="goToPage(' + totalPages + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">' + totalPages + '</a>';
        }

        if (currentPage >= totalPages) html += '<span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Selanjutnya</span>';
        else html += '<a href="#" onclick="goToPage(' + (currentPage + 1) + '); return false;" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Selanjutnya</a>';

        html += '</div></div>';
        container.innerHTML = html;

        var paginationRow2 = document.getElementById('paginationGajiRow');
        if (paginationRow2) paginationRow2.classList.remove('hidden');
    }

    function updateSummary(data) {
        var totalGrand = 0, totalUpah = 0, totalSolar = 0, totalDT = 0, totalKomp = 0;
        data.forEach(function(s) {
            var row = document.getElementById('row_' + s.kode_sopir);
            if (row) {
                var cells = row.querySelectorAll('td');
                var gt = document.getElementById('grandTotal_' + s.kode_sopir);
                totalGrand += gt ? (parseFloat(gt.textContent.replace(/[^0-9]/g, '')) || 0) : (s.grand_total || 0);
                totalSolar += parseFloat(cells[2] && cells[2].textContent ? cells[2].textContent.replace(/[^0-9]/g, '') : 0) || 0;
                totalUpah += parseFloat(cells[3] && cells[3].textContent ? cells[3].textContent.replace(/[^0-9]/g, '') : 0) || 0;
                totalDT += parseFloat(cells[4] && cells[4].textContent ? cells[4].textContent.replace(/[^0-9]/g, '') : 0) || 0;
                totalKomp += parseFloat(cells[6] && cells[6].textContent ? cells[6].textContent.replace(/[^0-9]/g, '') : 0) || 0;
            } else {
                totalGrand += s.grand_total || 0;
                totalUpah += s.total_upah || 0;
                totalSolar += s.total_solar || 0;
                totalDT += s.total_dt || 0;
                totalKomp += s.total_kompensasi || 0;
            }
        });

        var elGrand = document.getElementById('summaryGrandTotal');
        if (elGrand) elGrand.textContent = 'Rp ' + formatRupiah(totalGrand);
        var elUpah = document.getElementById('summaryUpah');
        if (elUpah) elUpah.textContent = 'Rp ' + formatRupiah(totalUpah);
        var elSolar = document.getElementById('summarySolar');
        if (elSolar) elSolar.textContent = 'Rp ' + formatRupiah(totalSolar);
        var elDT = document.getElementById('summaryDT');
        if (elDT) elDT.textContent = 'Rp ' + formatRupiah(totalDT);
        var elKomp = document.getElementById('summaryKompensasi');
        if (elKomp) elKomp.textContent = 'Rp ' + formatRupiah(totalKomp);
        var elCount = document.getElementById('summarySopirCount');
        if (elCount) elCount.textContent = data.length + ' sopir';
    }

    function applySearch() {
        if (periodeId) loadGajiData(periodeId);
    }

    function clearGajiFilter() {
        var filterEl = document.getElementById('filterTanggal');
        if (filterEl) filterEl.value = '';
        applySearch();
    }

    function toggleGajiFilter() {
        var p = document.getElementById('gajiFilterPanel');
        if (p) p.classList.toggle('hidden');
    }

    function showDetail(index) {
        var sopir = gajiData[index];
        var modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4';
        modal.innerHTML =
            '<div class="bg-white rounded border border-gray-200 w-full max-w-4xl max-h-[90vh] overflow-y-auto p-4" onclick="event.stopPropagation()">' +
                '<div class="flex justify-between items-center mb-3">' +
                    '<h3 class="text-lg font-semibold text-gray-900">Slip Gaji ' + sopir.nama_sopir + '</h3>' +
                    '<button onclick="this.closest(\'.fixed\').remove()" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>' +
                '</div>' +
                '<div id="slipContent" class="text-center text-gray-500 py-8">Loading slip...</div>' +
            '</div>';
        document.body.appendChild(modal);

        fetch('/gaji/slip/' + periodeId + '/' + sopir.kode_sopir, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var styles = doc.querySelectorAll('style');
                var styleHtml = '';
                styles.forEach(function(s) {
                    var css = s.textContent;
                    css = css.replace(/@page\s*\{[^}]*\}/g, '').replace(/(?:^|\n)\s*\*\s*\{[^}]*\}/g, '').replace(/(?:^|\n)\s*html\s*\{[^}]*\}/g, '').replace(/(?:^|\n)\s*body\s*\{[^}]*\}/g, '');
                    if (css.trim()) styleHtml += '<style>' + css + '</style>';
                });
                var containers = doc.querySelectorAll('.slip-container');
                var slipHtml = '';
                containers.forEach(function(c) { slipHtml += c.outerHTML; });
                document.getElementById('slipContent').innerHTML = styleHtml + (slipHtml || '<p class="text-gray-500">Tidak ada data slip</p>');
            })
            .catch(function() {
                document.getElementById('slipContent').innerHTML = '<p class="text-red-500">Gagal memuat slip</p>';
            });
    }

    function showKonfirmasi() {
        var periode = document.getElementById('pilih_periode').value;
        if (!periode) { alert('Silakan pilih Periode terlebih dahulu!'); return; }

        // Build set of tujuan that have ritase > 0 in current data
        var tujuanWithRitase = new Set();
        gajiData.forEach(function(sopir) {
            if (sopir.rit_per_tujuan) {
                Object.keys(sopir.rit_per_tujuan).forEach(function(kt) {
                    if ((sopir.rit_per_tujuan[kt].total_rit || 0) > 0) {
                        tujuanWithRitase.add(kt);
                    }
                });
            }
        });

        var hasEmpty = false, hasInvalid = false;
        ['bbm_per_rit', 'upah_per_rit'].forEach(function(field) {
            document.querySelectorAll('input[data-field="' + field + '"]').forEach(function(input, i) {
                var kodeTujuan = input.dataset.tujuan;
                // Only validate if this tujuan has ritase
                if (!tujuanWithRitase.has(kodeTujuan)) {
                    input.classList.remove('border-red-500');
                    var errorEl = document.getElementById('error_' + field.split('_')[0] + '_' + i);
                    if (errorEl) errorEl.classList.add('hidden');
                    return;
                }

                var errorEl = document.getElementById('error_' + field.split('_')[0] + '_' + i);
                if (input.value === '' || parseFloat(input.value) < 0) {
                    hasEmpty = true; input.classList.add('border-red-500');
                    if (errorEl) { errorEl.textContent = 'Wajib diisi.'; errorEl.classList.remove('hidden'); }
                } else if (!validasiNominal(input.value)) {
                    hasInvalid = true; input.classList.add('border-red-500');
                    if (errorEl) { errorEl.textContent = 'Harus angka positif.'; errorEl.classList.remove('hidden'); }
                } else {
                    input.classList.remove('border-red-500');
                    if (errorEl) errorEl.classList.add('hidden');
                }
            });
        });

        if (hasEmpty) { alert('Silakan isi BBM/Rit dan Upah/Rit untuk semua tujuan yang memiliki ritase!'); return; }
        if (hasInvalid) { alert('Nilai harus berupa angka positif!'); return; }

        formDataGaji = new FormData(document.getElementById('formGaji'));
        var periodeSelect = document.getElementById('pilih_periode');
        var periodeText = periodeSelect.options[periodeSelect.selectedIndex].text;

        var detailHtml = '<div class="space-y-2"><div class="flex justify-between"><span class="text-gray-500">Periode:</span><span class="font-semibold text-gray-900">' + periodeText + '</span></div><div class="border-t pt-2 mt-2"><p class="text-xs text-gray-500">Detail Biaya per Tujuan:</p>';

        document.querySelectorAll('input[data-field="bbm_per_rit"]').forEach(function(input) {
            var kode = input.dataset.tujuan;
            var nama = allTujuans.find(function(t) { return t.kode_tujuan === kode; }) ? allTujuans.find(function(t) { return t.kode_tujuan === kode; }).nama : kode;
            var bbm = input.value || '0';
            var upahEl = document.querySelector('input[data-tujuan="' + kode + '"][data-field="upah_per_rit"]');
            var upah = upahEl ? upahEl.value || '0' : '0';
            detailHtml += '<div class="flex justify-between text-sm py-1"><span class="font-medium">' + nama + '</span><span class="text-gray-600">BBM: Rp ' + formatRupiah(bbm) + ' | Upah: Rp ' + formatRupiah(upah) + '</span></div>';
        });
        detailHtml += '</div><div class="border-t pt-2 mt-2 text-xs text-gray-500">Data akan dihitung ulang berdasarkan ritase yang ada.</div></div>';

        document.getElementById('konfirmasiDetail').innerHTML = detailHtml;
        var modal = document.getElementById('konfirmasiModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeKonfirmasiModal() {
        var modal = document.getElementById('konfirmasiModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    function submitGaji() {
        if (formDataGaji) {
            closeKonfirmasiModal();
            document.getElementById('formGaji').submit();
        }
    }

    // Expose to global scope for onclick handlers
    window.showDetail = showDetail;
    window.showKonfirmasi = showKonfirmasi;
    window.closeKonfirmasiModal = closeKonfirmasiModal;
    window.submitGaji = submitGaji;
    window.applySearch = applySearch;
    window.clearGajiFilter = clearGajiFilter;
    window.toggleGajiFilter = toggleGajiFilter;
    window.goToPage = goToPage;

    document.addEventListener('DOMContentLoaded', init);
})();
