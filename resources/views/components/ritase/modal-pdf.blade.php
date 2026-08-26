{{-- Modal PDF Viewer --}}
<div id="pdfModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
    <div class="bg-white rounded border border-gray-200 w-full max-w-5xl mx-4" style="height:90vh">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Detail Ritase per Sopir</h3>
            <button onclick="closePdfModal()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-2" style="height:calc(100% - 52px)">
            <iframe id="pdfIframe" src="about:blank" style="width:100%;height:100%;border:none"></iframe>
        </div>
    </div>
</div>
