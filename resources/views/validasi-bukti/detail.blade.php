<x-layouts.dashboard
    :title="'Detail Validasi Bukti'"
    :pageTitle="'Detail Validasi Bukti'"
    :user="auth()->user()">

    <div class="border-b border-gray-200 pb-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Detail Bukti</h1>
                <p class="text-base text-gray-500 mt-1">Periksa bukti sebelum menyetujui atau menolak</p>
            </div>
            <a href="{{ route('validasi-bukti.kelola') }}" class="text-sm text-gray-600 border border-gray-200 px-3 py-1.5 rounded hover:bg-gray-50 font-medium">&larr; Kembali</a>
        </div>
    </div>

    @if(session('success'))
        <div class="border border-green-200 bg-green-50 text-green-700 px-4 py-3 rounded mb-4 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="border border-red-200 bg-red-50 text-red-700 px-4 py-3 rounded mb-4 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
            <div class="border border-gray-200 rounded bg-white overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                    <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Foto Bukti</p>
                </div>
                <div class="p-4">
                    <img src="/storage/{{ $item->foto }}" class="w-full rounded border border-gray-200">
                </div>
            </div>

            <div class="border border-gray-200 rounded bg-white mt-4 overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                    <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Lokasi & Waktu</p>
                </div>
                <div class="p-4 space-y-2 text-sm">
                    <div><span class="font-medium text-gray-600">Lokasi:</span> <span class="text-gray-800">{{ $item->lokasi ?? '-' }}</span></div>
                    <div><span class="font-medium text-gray-600">Koordinat:</span> <span class="text-gray-800">{{ $item->latitude }}, {{ $item->longitude }}</span></div>
                    <div><span class="font-medium text-gray-600">Waktu Foto:</span> <span class="text-gray-800">{{ $item->waktu_foto ? \Carbon\Carbon::parse($item->waktu_foto)->format('d/m/Y H:i:s') : '-' }}</span></div>
                    <div><span class="font-medium text-gray-600">Periode:</span> <span class="text-gray-800">{{ $item->periode?->nama_periode ?? '-' }}</span></div>
                </div>
            </div>
        </div>

        <div>
            <div class="border border-gray-200 rounded bg-white overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                    <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Data Sopir & Tujuan</p>
                </div>
                <div class="p-4 space-y-3 text-sm">
                    <div>
                        <span class="font-medium text-gray-600">Nama Sopir:</span>
                        <span class="text-gray-800">{{ $item->nama_sopir }}</span>
                        @if($item->sopir_baru)
                            <span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded ml-1">Sopir Baru</span>
                        @elseif($item->kode_sopir)
                            <span class="text-xs text-gray-400 ml-1">({{ $item->kode_sopir }})</span>
                        @endif
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Tujuan:</span>
                        <span class="text-gray-800">{{ $item->nama_tujuan }}</span>
                        @if($item->tujuan_baru)
                            <span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded ml-1">Tujuan Baru</span>
                        @elseif($item->kode_tujuan)
                            <span class="text-xs text-gray-400 ml-1">({{ $item->kode_tujuan }})</span>
                        @endif
                    </div>
                    <div><span class="font-medium text-gray-600">Tanggal:</span> <span class="text-gray-800">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</span></div>
                    <div><span class="font-medium text-gray-600">Catatan Sopir:</span> <span class="text-gray-800">{{ $item->catatan ?? '-' }}</span></div>
                    <div><span class="font-medium text-gray-600">Status:</span>
                        @if($item->status === 'pending')
                            <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded font-medium">Pending</span>
                        @elseif($item->status === 'disetujui')
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded font-medium">Disetujui</span>
                        @else
                            <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded font-medium">Ditolak</span>
                        @endif
                    </div>
                    @if($item->catatan_mitra)
                        <div><span class="font-medium text-gray-600">Catatan Mitra:</span> <span class="text-gray-800">{{ $item->catatan_mitra }}</span></div>
                    @endif
                </div>
            </div>

            @if($item->status === 'pending')
                <div class="border border-gray-200 rounded bg-white mt-4 overflow-hidden">
                    <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                        <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Tindakan</p>
                    </div>
                    <div class="p-4 space-y-4">
                        @if($item->sopir_baru || $item->tujuan_baru)
                            <div class="border border-yellow-200 bg-yellow-50 text-yellow-700 px-4 py-3 rounded text-sm">
                                <p class="font-medium">Perhatian: Data Baru</p>
                                <p class="mt-1">Sopir atau tujuan baru akan ditambahkan ke database setelah disetujui.</p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('validasi-bukti.setujui', $item->id) }}" class="border border-gray-200 rounded p-4 bg-gray-50">
                            @csrf
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                            <textarea name="catatan_mitra" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-blue-500"></textarea>
                            <button type="submit" class="mt-2 w-full bg-green-600 text-white rounded text-sm font-semibold px-4 py-2.5 hover:bg-green-700 transition">
                                Setujui
                            </button>
                        </form>

                        <form method="POST" action="{{ route('validasi-bukti.tolak', $item->id) }}" class="border border-red-200 rounded p-4 bg-red-50" onsubmit="return confirm('Yakin menolak bukti ini?')">
                            @csrf
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                            <textarea name="catatan_mitra" rows="2" required class="w-full px-3 py-2 border border-gray-200 rounded text-sm focus:outline-none focus:border-red-500"></textarea>
                            <button type="submit" class="mt-2 w-full bg-red-600 text-white rounded text-sm font-semibold px-4 py-2.5 hover:bg-red-700 transition">
                                Tolak
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if($item->status === 'disetujui')
                <div class="border border-gray-200 rounded bg-white mt-4 overflow-hidden">
                    <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                        <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Tambah Ritase</p>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="{{ route('validasi-bukti.ritase', $item->id) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="kode_sopir" value="{{ $item->kode_sopir }}">
                            <input type="hidden" name="kode_tujuan" value="{{ $item->kode_tujuan }}">
                            <input type="hidden" name="tanggal" value="{{ $item->tanggal }}">

                            <div class="border border-blue-200 bg-blue-50 text-blue-700 px-4 py-3 rounded text-sm">
                                Sopir: <strong>{{ $item->nama_sopir }}</strong> ({{ $item->kode_sopir }})
                                &middot; Tujuan: <strong>{{ $item->nama_tujuan }}</strong> ({{ $item->kode_tujuan }})
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Kabupaten <span class="text-red-500">*</span></label>
                                    <select name="kabupaten" required class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white focus:outline-none focus:border-blue-500">
                                        <option value="">Pilih</option>
                                        <option value="bangkalan">Bangkalan</option>
                                        <option value="sampang">Sampang</option>
                                        <option value="pamekasan">Pamekasan</option>
                                        <option value="sumenep">Sumenep</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu <span class="text-red-500">*</span></label>
                                    <select name="waktu" required class="w-full px-3 py-2 border border-gray-200 rounded text-sm bg-white focus:outline-none focus:border-blue-500">
                                        <option value="">Pilih</option>
                                        <option value="pagi">Pagi</option>
                                        <option value="malam">Malam</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-blue-600 text-white rounded text-sm font-semibold px-4 py-2.5 hover:bg-blue-700 transition"
                                onclick="return confirm('Pastikan data sudah benar. Lanjutkan?')">
                                Simpan Ritase
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if($item->status === 'ditolak' && $item->catatan_mitra)
                <div class="border border-red-200 bg-red-50 rounded mt-4 p-4">
                    <p class="text-sm font-medium text-red-700">Alasan ditolak:</p>
                    <p class="text-sm text-red-600 mt-1">{{ $item->catatan_mitra }}</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.dashboard>
