{{-- Shared Pagination Component --}}
@props(['paginator'])

@if($paginator->hasPages())
    <div class="border-t border-gray-200 px-5 py-3 bg-gray-50">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-600">
                Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
            </p>
            <div class="flex items-center space-x-1.5">
                @if($paginator->onFirstPage())
                    <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">
                        Sebelumnya
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">
                        Sebelumnya
                    </a>
                @endif

                @php
                    $window = 2;
                    $current = $paginator->currentPage();
                    $last = $paginator->lastPage();
                    $start = max(1, $current - $window);
                    $end = min($last, $current + $window);
                @endphp

                @if($start > 1)
                    <a href="{{ $paginator->url(1) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">1</a>
                    @if($start > 2)
                        <span class="px-3 py-1.5 text-sm text-gray-400">...</span>
                    @endif
                @endif

                @for($page = $start; $page <= $end; $page++)
                    @if($page == $current)
                        <span class="px-3 py-1.5 text-sm font-bold text-white bg-[#2d6a4f] border border-[#2d6a4f] rounded">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">{{ $page }}</a>
                    @endif
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)
                        <span class="px-3 py-1.5 text-sm text-gray-400">...</span>
                    @endif
                    <a href="{{ $paginator->url($last) }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded font-medium">{{ $last }}</a>
                @endif

                @if($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-200 rounded hover:bg-gray-50 font-medium">
                        Selanjutnya
                    </a>
                @else
                    <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">
                        Selanjutnya
                    </span>
                @endif
            </div>
        </div>
    </div>
@endif