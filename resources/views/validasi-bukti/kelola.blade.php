<x-layouts.dashboard
    :title="'Validasi Bukti'"
    :pageTitle="'Validasi Bukti'"
    >

    <div class="border-b border-gray-200 pb-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Validasi Bukti</h1>
                <p class="text-base text-gray-500 mt-1">Verifikasi bukti dari sopir sebelum menambah ritase</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="border border-green-200 bg-green-50 text-green-700 px-4 py-3 rounded mb-4 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="border border-red-200 bg-red-50 text-red-700 px-4 py-3 rounded mb-4 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex gap-2 mb-4">
        @foreach(['pending' => 'Pending', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $val => $label)
            <a href="{{ route('validasi-bukti.kelola', ['status' => $val, 'search' => $search ?? '']) }}"
                class="px-4 py-2 rounded text-sm font-medium border transition
                    {{ $status === $val ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('validasi-bukti.kelola') }}" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama sopir, lokasi tujuan, atau tanggal"
            class="px-3 py-2 border border-gray-200 rounded text-sm bg-white w-full md:w-96">
        <button type="submit" class="px-4 py-2 rounded text-sm font-medium bg-[#1a1a2e] text-white hover:opacity-90 transition">Cari</button>
        @if(!empty($search))
            <a href="{{ route('validasi-bukti.kelola', ['status' => $status]) }}" class="px-3 py-2 border border-gray-200 rounded text-sm text-gray-600 hover:bg-gray-50 bg-white">Reset</a>
        @endif
    </form>

    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500">Aturan Validasi:</span>
            <span class="text-sm font-semibold {{ cache('aturan_validasi_enabled', false) ? 'text-green-600' : 'text-red-600' }}">
                {{ cache('aturan_validasi_enabled', false) ? 'AKTIF' : 'NONAKTIF' }}
            </span>
            <form method="POST" action="{{ route('settings.toggle-validasi') }}" class="inline">
                @csrf
                <button type="submit"
                        class="text-xs border px-2 py-1 rounded font-medium hover:bg-gray-50 transition
                            {{ cache('aturan_validasi_enabled', false) ? 'border-red-200 text-red-600' : 'border-green-200 text-green-600' }}">
                    {{ cache('aturan_validasi_enabled', false) ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </form>
        </div>
        <p class="text-xs text-gray-400">Sopir wajib kirim bukti sebelum ritase & gaji</p>
    </div>

    <div class="w-full border border-gray-200 rounded overflow-hidden bg-white">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Sopir</th>
                    <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tujuan</th>
                    <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Tanggal</th>
                    <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Status</th>
                    <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($list as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5">
                            <p class="text-sm font-medium text-gray-800">
                                {{ $item->nama_sopir }}
                                @if($item->sopir_baru)
                                    <span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded ml-1">Baru</span>
                                @endif
                            </p>
                            @if($item->kode_sopir)
                                <p class="text-xs text-gray-400">{{ $item->kode_sopir }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-2.5">
                            <p class="text-sm text-gray-700">
                                {{ $item->nama_tujuan }}
                                @if($item->tujuan_baru)
                                    <span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded ml-1">Baru</span>
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-2.5 text-center text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            @if($item->status === 'pending')
                                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded font-medium">Pending</span>
                            @elseif($item->status === 'disetujui')
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded font-medium">Disetujui</span>
                            @else
                                <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded font-medium">Ditolak</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <a href="{{ route('validasi-bukti.detail', $item->id) }}"
                                class="text-xs text-blue-600 border border-blue-200 px-2.5 py-1.5 rounded hover:bg-blue-50 font-medium">
                                Detail
                            </a>
                            <form method="POST" action="{{ route('validasi-bukti.destroy', $item->id) }}" class="inline"
                                onsubmit="return confirm('Yakin ingin menghapus permintaan validasi ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-xs text-red-600 border border-red-200 px-2.5 py-1.5 rounded hover:bg-red-50 font-medium">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">Belum ada data bukti.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($list->hasPages())
        <div class="border-t border-gray-200 px-5 py-3 bg-gray-50">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-600">Halaman {{ $list->currentPage() }} dari {{ $list->lastPage() }}</p>
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
                            <span class="px-3 py-1.5 text-sm font-bold text-white bg-[#1a1a2e] border border-[#1a1a2e] rounded">{{ $page }}</span>
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
</x-layouts.dashboard>
