{{-- Dropdown aksi baris tabel — pakai: <x-shared.row-actions><a ...>..</a><button ...>..</button></x-shared.row-actions> --}}
{{-- Tandai item berbahaya dengan atribut data-danger. Styling global ada di layout dashboard. --}}
<div class="relative row-actions" style="display:inline-block">
    <button type="button" onclick="toggleRowActions(this)" aria-label="Aksi"
        class="inline-flex items-center justify-center w-8 h-8 border border-gray-200 rounded hover:bg-gray-50 text-gray-500 transition bg-white">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/></svg>
    </button>
    <div class="row-actions-menu hidden absolute right-0 mt-1 w-44 rounded-lg shadow-lg z-50 py-1"
        style="background:var(--card-bg,#ffffff);border:1px solid var(--border,#e5e7eb)">
        {{ $slot }}
    </div>
</div>
