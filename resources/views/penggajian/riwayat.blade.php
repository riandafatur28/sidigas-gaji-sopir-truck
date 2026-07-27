<x-layouts.dashboard
    :title="'Riwayat Gaji'"
    :pageTitle="'Riwayat Gaji'"
    >

    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold" style="color:var(--text)">Riwayat Gaji</h1>
                <p class="text-sm mt-1" style="color:var(--text-muted)">Daftar semua periode gaji yang telah dihitung</p>
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <table class="w-full">
            <thead>
                <tr style="background:rgba(255,253,252,0.6);border-bottom:1.5px solid var(--border)">
                    <th class="text-left text-xs font-semibold uppercase tracking-wider px-5 py-3" style="color:var(--text-muted)" colspan="8">
                        Riwayat Gaji
                        <span class="text-xs ml-2" style="color:var(--text-dims);font-weight:400">Total: {{ $periodes->total() }} periode</span>
                    </th>
                </tr>
            </thead>
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
                                   class="inline-flex items-center px-2.5 py-1.5 bg-blue-50 text-blue-700 rounded text-xs font-medium hover:bg-blue-100 transition">
                                    Detail
                                </a>
                                <button onclick="lihatSlipModal('{{ $periode['id'] }}')"
                                   class="inline-flex items-center px-2.5 py-1.5 bg-gray-50 text-gray-700 rounded text-xs font-medium hover:bg-gray-100 transition cursor-pointer">
                                    Lihat
                                </button>
                                <a href="{{ route('gaji.slip-pdf', $periode['id']) }}"
                                   class="inline-flex items-center px-2.5 py-1.5 bg-gray-50 text-gray-700 rounded text-xs font-medium hover:bg-gray-100 transition">
                                    Slip PDF
                                </a>
                                <a href="{{ route('gaji.laporan-pdf', $periode['id']) }}"
                                   class="inline-flex items-center px-2.5 py-1.5 bg-green-50 text-green-700 rounded text-xs font-medium hover:bg-green-100 transition">
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
        @if($periodes->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $periodes->links() }}
        </div>
        @endif
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
            styles.forEach(s => styleHtml += s.outerHTML);
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
</x-layouts.dashboard>