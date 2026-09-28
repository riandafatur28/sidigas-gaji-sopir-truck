<x-layouts.dashboard
    :title="'Validasi Bukti'"
    :pageTitle="'Validasi Bukti'"
    >

    <div class="mb-8">
        <h1 class="text-2xl font-bold" style="color:var(--text)">Validasi Bukti</h1>
        <p class="text-sm mt-1" style="color:var(--text-muted)">Verifikasi bukti dari sopir sebelum menambah ritase.</p>
    </div>

    <div class="card mb-6">
        <div class="grid grid-cols-2 lg:grid-cols-4">
            <div style="padding:15px 20px" class="ledger-cell lg:border-r">
                <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">TOTAL BUKTI</p>
                <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($totalValidasi ?? 0) }}</p>
                <p class="dash-num" style="font-size:12px;color:var(--text-muted)">diajukan</p>
            </div>
            <div style="padding:15px 20px" class="ledger-cell lg:border-r">
                <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">PENDING</p>
                <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($validasiPending ?? 0) }}</p>
                <p class="dash-num" style="font-size:12px;color:var(--text-muted)">menunggu review</p>
            </div>
            <div style="padding:15px 20px" class="ledger-cell lg:border-r">
                <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">DISETUJUI</p>
                <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($validasiDisetujui ?? 0) }}</p>
                <p class="dash-num" style="font-size:12px;color:var(--text-muted)">lolos verifikasi</p>
            </div>
            <div style="padding:15px 20px" data-ledger>
                <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;color:var(--text-dims)">DITOLAK</p>
                <p class="dash-num" style="font-size:22px;font-weight:650;color:var(--text);line-height:1.25;letter-spacing:-0.01em">{{ number_format($validasiDitolak ?? 0) }}</p>
                <p class="dash-num" style="font-size:12px;color:var(--text-muted)">tidak valid</p>
            </div>
        </div>
    </div>

    <div class="card mb-6" data-live-root>
        <div class="border-b border-gray-200 px-5 py-3 bg-gray-50">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Daftar Validasi</p>
                </div>

                <div class="flex flex-row gap-2 items-center w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-72 sm:flex-none">
                        <input type="text" id="liveSearch" value="{{ $search ?? '' }}"
                            class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded text-sm focus:outline-none focus:border-[#2d6a4f] focus:ring-1 focus:ring-[#2d6a4f]/20 transition bg-white"
                            placeholder="Ketik untuk mencari..." autocomplete="off">

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

                    <div class="relative shrink-0" id="valFilterWrap">
                        <button onclick="toggleValFilter()" class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium bg-white hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filter
                            @if($status && $status !== 'pending')<span class="w-2 h-2 rounded-full bg-green-500"></span>@endif
                        </button>
                        <div class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-4" id="valFilterPanel">
                            <form method="GET" action="{{ route('validasi-bukti.kelola') }}" class="space-y-3">
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</label>
                                    <select name="status" id="filterStatus" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white mt-1">
                                        <option value="">Semua Status</option>
                                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="disetujui" {{ $status === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                                        <option value="ditolak" {{ $status === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                                    </select>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<div id="liveResults" class="p-5">
            <div class="table-responsive">
                <table class="w-full">
                    <thead style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                        <tr>
                            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Sopir</th>
                            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tujuan</th>
                            <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tanggal</th>
                            <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Status</th>
                            <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($list as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <span class="text-sm font-semibold text-gray-900">{{ $item->nama_sopir }}</span>
                                    @if($item->sopir_baru)
                                        <span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded ml-1">Baru</span>
                                    @endif
                                    @if($item->kode_sopir)
                                        <br><span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-medium rounded mt-1">{{ $item->kode_sopir }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm font-semibold text-gray-900">{{ $item->nama_tujuan }}</span>
                                    @if($item->tujuan_baru)
                                        <span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded ml-1">Baru</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600">
                                    {{ \Carbon\Carbon::parse($item->tanggal)->locale('id')->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($item->status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">
                                            <span class="w-2 h-2 bg-yellow-500 rounded-full mr-1.5"></span>
                                            Pending
                                        </span>
                                    @elseif($item->status === 'disetujui')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                                            Disetujui
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                                            <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                                            Ditolak
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center space-x-1.5">
                                    <a href="{{ route('validasi-bukti.detail', $item->id) }}"
                                        class="inline-flex items-center gap-1 text-xs text-gray-600 border border-gray-200 px-2.5 py-1.5 rounded hover:bg-gray-50 font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Detail
                                    </a>
                                    <form method="POST" action="{{ route('validasi-bukti.destroy', $item->id) }}" class="inline"
                                        id="deleteValidasi_{{ $item->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="confirmDeleteValidasi({{ $item->id }})"
                                            class="inline-flex items-center gap-1 text-xs text-red-600 border border-red-200 px-2.5 py-1.5 rounded hover:bg-red-50 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Hapus
                                        </button>
                                    </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-gray-500 font-semibold">Belum ada data bukti</p>
                                    <p class="text-gray-400 text-sm mt-1">Data validasi yang masuk akan tampil di sini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($list->hasPages())
                <div class="border-t border-gray-200 px-5 py-3 bg-gray-50">
                    <div class="flex items-center justify-between w-full gap-3">
                        <p class="text-sm text-gray-600 whitespace-nowrap">Halaman {{ $list->currentPage() }} dari {{ $list->lastPage() }}</p>
                        <div class="flex items-center space-x-1.5">
                            @if($list->onFirstPage())
                                <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">Sebelumnya</span>
                            @else
                                <a href="{{ $list->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Sebelumnya</a>
                            @endif

                            @php
                                $window = 2; $current = $list->currentPage(); $last = $list->lastPage();
                                $start = max(1, $current - $window); $end = min($last, $current + $window);
                            @endphp

                            @if($start > 1)
                                <a href="{{ $list->url(1) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">1</a>
                                @if($start > 2) <span class="px-3 py-1.5 text-sm text-gray-400">...</span> @endif
                            @endif

                            @for($page = $start; $page <= $end; $page++)
                                @if($page == $current)
                                    <span class="px-3 py-1.5 text-sm font-bold text-white bg-[#2d6a4f] border border-[#2d6a4f] rounded">{{ $page }}</span>
                                @else
                                    <a href="{{ $list->url($page) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">{{ $page }}</a>
                                @endif
                            @endfor

                            @if($end < $last)
                                @if($end < $last - 1) <span class="px-3 py-1.5 text-sm text-gray-400">...</span> @endif
                                <a href="{{ $list->url($last) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">{{ $last }}</a>
                            @endif

                            @if($list->hasMorePages())
                                <a href="{{ $list->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">Selanjutnya</a>
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
        function toggleValFilter() {
            var panel = document.getElementById('valFilterPanel');
            panel.classList.toggle('hidden');
        }
        document.addEventListener('click', function(e) {
            var wrap = document.getElementById('valFilterWrap');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('valFilterPanel').classList.add('hidden');
            }
        });
        function confirmDeleteValidasi(id) {
            showConfirmModal({
                title: 'Hapus Permintaan Validasi?',
                message: 'Anda yakin ingin menghapus permintaan validasi ini? Tindakan ini tidak dapat dibatalkan.',
                type: 'danger',
                confirmText: 'Ya, Hapus',
                onConfirm: function() {
                    document.getElementById('deleteValidasi_' + id).submit();
                }
            });
        }
    </script>

    @push('scripts')
    <script src="{{ asset('js/live-search.js') }}"></script>
    @endpush
</x-layouts.dashboard>



