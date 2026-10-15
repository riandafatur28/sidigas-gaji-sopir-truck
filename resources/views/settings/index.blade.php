<x-layouts.dashboard
    :title="'Pengaturan'"
    :pageTitle="'Pengaturan'"
    >

    <div class="mb-8">
        <h1 class="text-2xl font-bold" style="color:var(--text)">Pengaturan</h1>
        <p class="text-sm mt-1" style="color:var(--text-muted)">Kelola aturan validasi dan parameter sistem.</p>
    </div>

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- ATURAN VALIDASI --}}
        <div class="card">
            <div class="card-header">
                <span class="text-sm font-semibold uppercase" style="color:var(--text-muted)">Aturan Validasi Bukti</span>
            </div>
            <div class="card-body">
                <p class="text-sm text-gray-500 mb-4">Mengaktifkan atau menonaktifkan wajibnya sopir mengirim bukti sebelum ritase.</p>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-sm" style="color:var(--text)">Status Aturan</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ cache('aturan_validasi_enabled', false) ? 'Aktif — Sopir wajib kirim bukti' : 'Nonaktif — Bukti tidak wajib' }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('settings.toggle-validasi') }}">
                        @csrf
                        <button type="submit"
                            class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors
                                {{ cache('aturan_validasi_enabled', false) ? 'bg-green-600' : 'bg-gray-300' }}">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform
                                {{ cache('aturan_validasi_enabled', false) ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- NOMINAL SEWA DT --}}
        <div class="card">
            <div class="card-header">
                <span class="text-sm font-semibold uppercase" style="color:var(--text-muted)">Nominal Sewa DT</span>
            </div>
            <div class="card-body">
                <p class="text-sm text-gray-500 mb-4">Atur nominal uang transport (DT) per ritase. Nilai awal Rp 330.000. Setiap perubahan otomatis memperbarui DT ritase & gaji pada periode aktif (periode sebelumnya tidak berubah).</p>
                <form method="POST" action="{{ route('settings.dt-nominal') }}">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Nominal DT (Rp)</label>
                            <input type="number" name="nominal" value="{{ $dtNominal }}" required
                                class="form-input" placeholder="330000" min="1">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- KETENTUAN SEWA DT --}}
        <div class="card lg:col-span-2">
            <div class="card-header">
                <span class="text-sm font-semibold uppercase" style="color:var(--text-muted)">Ketentuan Sewa DT per Kabupaten</span>
            </div>
            <div class="card-body">
                <p class="text-sm text-gray-500 mb-4">Centang kabupaten yang hanya mendapat <strong>maksimal 1 DT per hari per waktu</strong> (rit ke-2 di kabupaten, tanggal, dan waktu yang sama = DT Rp 0). Kabupaten yang tidak dicentang mendapat DT penuh untuk setiap rit. Perubahan otomatis menyesuaikan DT ritase & gaji pada periode aktif.</p>
                <form method="POST" action="{{ route('settings.dt-aturan') }}">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 mb-4">
                        @foreach($allKabupaten as $kab)
                            <label class="flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg text-sm cursor-pointer hover:bg-gray-50 transition" style="color:var(--text)">
                                <input type="checkbox" name="kabupaten[]" value="{{ $kab }}"
                                    {{ in_array($kab, $dtSingleRegencies) ? 'checked' : '' }}
                                    class="h-4 w-4 rounded accent-green-700">
                                <span>{{ $kab }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Simpan Ketentuan</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.dashboard>
