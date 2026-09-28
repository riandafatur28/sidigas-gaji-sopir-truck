<x-layouts.dashboard
    :title="'Riwayat Gaji'"
    :pageTitle="'Riwayat Gaji'"
    >

    <div class="mb-8">
        <h1 class="text-2xl font-bold" style="color:var(--text)">Riwayat Gaji</h1>
        <p class="text-sm mt-1" style="color:var(--text-muted)">Daftar semua periode gaji yang telah dihitung</p>
    </div>

    {{-- TABEL RIWAYAT GAJI --}}
    <div class="card mb-6" data-live-root>
        <div class="border-b border-gray-200 px-5 py-3 bg-gray-50">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Daftar Riwayat
                        <span class="text-xs ml-2" style="color:var(--text-dims);font-weight:400">Total: {{ $periodes->total() }} periode</span>
                    </p>
                </div>

                <div class="flex flex-row gap-2 items-center w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-72 sm:flex-none">
                        <input type="text" id="liveSearch" value="{{ $search ?? '' }}"
                            class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white"
                            placeholder="Cari periode..." autocomplete="off">

                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--text-dims)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>

                        <div id="searchLoading" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        <button id="clearSearch" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-100 rounded transition" title="Hapus pencarian">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="relative" id="riwFilterWrap">
                        <button onclick="toggleRiwFilter()" class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium bg-white hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filter
                            @if($bulan || $tahun || $sort != 'terbaru')
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            @endif
                        </button>
                        <div class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4" id="riwFilterPanel">
                            <form method="GET" class="space-y-3">
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Urutkan</label>
                                    <select name="sort" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                        <option value="terbaru" {{ $sort == 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                                        <option value="terlama" {{ $sort == 'terlama' ? 'selected' : '' }}>Terlama</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Bulan</label>
                                    <select name="bulan" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                        <option value="">Semua Bulan</option>
                                        @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $nama)
                                            <option value="{{ $i + 1 }}" {{ $bulan == $i + 1 ? 'selected' : '' }}>{{ $nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tahun</label>
                                    <select name="tahun" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                        <option value="">Semua Tahun</option>
                                        @foreach($availableYears as $th)
                                            <option value="{{ $th }}" {{ $tahun == $th ? 'selected' : '' }}>{{ $th }}</option>
                                        @endforeach
                                    </select>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        function toggleRiwFilter(){document.getElementById('riwFilterPanel').classList.toggle('hidden');}
        document.addEventListener('click',function(e){const w=document.getElementById('riwFilterWrap');if(w&&!w.contains(e.target)){document.getElementById('riwFilterPanel').classList.add('hidden');}});
        </script>

        <div id="liveResults" class="p-5">
        <div class="border border-gray-200 rounded bg-white table-responsive">
        <table class="w-full">
            <thead style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                <tr>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Periode</th>
                    <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Sopir</th>
                    <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Ritase</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Solar</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Upah</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">DT</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Grand Total</th>
                    <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($periodes as $periode)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5">
                            <div class="text-sm font-medium text-gray-800">{{ $periode['nama_periode'] }}</div>
                            <div class="text-xs text-gray-400">
                                {{ \Carbon\Carbon::parse($periode['tanggal_mulai'])->format('d/m/Y') }}
                                -
                                {{ \Carbon\Carbon::parse($periode['tanggal_selesai'])->format('d/m/Y') }}
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-center text-sm font-medium text-gray-700">{{ $periode['jumlah_sopir'] }} org</td>
                        <td class="px-4 py-2.5 text-center text-sm text-gray-600">{{ $periode['total_ritase'] }} rit</td>
                        <td class="px-4 py-2.5 text-right text-sm font-medium text-gray-800">Rp {{ number_format($periode['total_solar'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm font-medium text-gray-800">Rp {{ number_format($periode['total_upah'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm font-medium text-gray-800">Rp {{ number_format($periode['total_dt'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right text-sm font-bold text-gray-900">Rp {{ number_format($periode['grand_total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('gaji.index', ['periode' => $periode['id']]) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-green-50 text-green-700 rounded text-xs font-medium hover:bg-green-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Detail
                                </a>
                                <button onclick="lihatSlipModal('{{ $periode['id'] }}')"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-gray-50 text-gray-700 rounded text-xs font-medium hover:bg-gray-100 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Lihat
                                </button>
                                <a href="{{ route('gaji.slip-pdf', $periode['id']) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-gray-50 text-gray-700 rounded text-xs font-medium hover:bg-gray-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Slip PDF
                                </a>
                                <a href="{{ route('gaji.laporan-pdf', $periode['id']) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-green-50 text-green-700 rounded text-xs font-medium hover:bg-green-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Laporan
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-400">Belum ada data gaji.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($periodes->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            <div class="flex items-center justify-between w-full gap-3">
                <p class="text-sm text-gray-600 whitespace-nowrap hidden sm:block">Halaman {{ $periodes->currentPage() }} dari {{ $periodes->lastPage() }}</p>
                <div class="flex items-center space-x-1.5">
                    @if($periodes->onFirstPage())
                        <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Sebelumnya</span>
                    @else
                        <a href="{{ $periodes->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Sebelumnya</a>
                    @endif

                    @php
                        $w = 2;
                        $ss = max(1, $periodes->currentPage() - $w);
                        $ee = min($periodes->lastPage(), $periodes->currentPage() + $w);
                    @endphp

                    @if($ss > 1)
                        <a href="{{ $periodes->url(1) }}" class="page-num px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">1</a>
                        @if($ss > 2)
                            <span class="page-ellipsis px-3 py-1.5 text-sm text-gray-400">...</span>
                        @endif
                    @endif

                    @for($p = $ss; $p <= $ee; $p++)
                        @if($p == $periodes->currentPage())
                            <span class="px-3 py-1.5 text-sm font-bold text-white bg-[#2d6a4f] border border-[#2d6a4f] rounded">{{ $p }}</span>
                        @else
                            <a href="{{ $periodes->url($p) }}" class="page-num px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">{{ $p }}</a>
                        @endif
                    @endfor

                    @if($ee < $periodes->lastPage())
                        @if($ee < $periodes->lastPage() - 1)
                            <span class="page-ellipsis px-3 py-1.5 text-sm text-gray-400">...</span>
                        @endif
                        <a href="{{ $periodes->url($periodes->lastPage()) }}" class="page-num px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">{{ $periodes->lastPage() }}</a>
                    @endif

                    @if($periodes->hasMorePages())
                        <a href="{{ $periodes->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Selanjutnya</a>
                    @else
                        <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Selanjutnya</span>
                    @endif
                </div>
            </div>
        </div>
        @endif
        </div>
    </div>
<script>
function lihatSlipModal(periodeId) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/40 z-50 flex items-center justify-center';
    modal.onclick = function(e) { if(e.target === this) this.remove(); };
    modal.innerHTML = `
        <div class="bg-white rounded border border-gray-200 w-full max-w-6xl max-h-[95vh] overflow-y-auto p-4" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Slip Gaji</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="slipViewContent" class="text-center text-gray-500 py-8">Loading slip...</div>
        </div>
    `;
    document.body.appendChild(modal);

    fetch('/gaji/slip-view/' + periodeId)
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const styles = doc.querySelectorAll('style');
            let styleHtml = '';
            styles.forEach(s => {
                let css = s.textContent;
                css = css.replace(/@page\s*\{[^}]*\}/g, '');
                css = css.replace(/(?:^|\n)\s*\*\s*\{[^}]*\}/g, '');
                css = css.replace(/(?:^|\n)\s*html\s*\{[^}]*\}/g, '');
                css = css.replace(/(?:^|\n)\s*body\s*\{[^}]*\}/g, '');
                if (css.trim()) {
                    styleHtml += '<style>' + css + '<\/style>';
                }
            });
            const blocks = doc.querySelectorAll('.slip-block');
            let slipHtml = '';
            blocks.forEach(b => slipHtml += b.outerHTML);
            document.getElementById('slipViewContent').innerHTML = styleHtml + (slipHtml || '<p class="text-gray-500">Tidak ada data slip</p>');
        })
        .catch(() => {
            document.getElementById('slipViewContent').innerHTML = '<p class="text-red-500">Gagal memuat slip</p>';
        });
}
</script>
    @push('scripts')
    <script src="{{ asset('js/live-search.js') }}"></script>
    @endpush
</x-layouts.dashboard>


