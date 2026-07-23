@extends('layouts.app')

@section('title', 'Hasil Parser Ritase')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Hasil Parsing Teks Ritase</h1>
        <a href="{{ route('ritase.parser.form') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">← Kembali</a>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <strong>Error:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm text-gray-500">Tanggal</p>
            <p class="text-2xl font-bold text-gray-900">{{ $summary['date'] ?? '-' }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm text-gray-500">Total Paket/Rute</p>
            <p class="text-2xl font-bold text-blue-600">{{ $summary['total_packages'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm text-gray-500">Total Sopir</p>
            <p class="text-2xl font-bold text-green-600">{{ $summary['total_drivers'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm text-gray-500">Sopir Ter-match</p>
            <p class="text-2xl font-bold text-purple-600">{{ $summary['drivers_matched'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Driver Matches -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Pencocokan Sopir ({{ $summary['drivers_matched'] }}/{{ $summary['total_drivers'] }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama Input</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Match DB</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Confidence</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($driver_matches as $m)
                        <tr class="{{ !$m['matched'] ? 'bg-red-50' : '' }}">
                            <td class="px-4 py-2 text-sm font-medium">{{ $m['input_name'] }}</td>
                            <td class="px-4 py-2 text-sm">
                                @if($m['matched'])
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ditemukan</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Tidak Ditemukan</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $m['sopir']['nama'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $m['sopir']['kode_sopir'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm">
                                @if($m['matched'])
                                    <span class="{{ $m['confidence'] >= 90 ? 'text-green-600' : ($m['confidence'] >= 75 ? 'text-yellow-600' : 'text-orange-600') }}">
                                        {{ $m['confidence'] }}%
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Route Matches -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Pencocokan Rute ({{ $summary['routes_matched'] }}/{{ count($route_matches) }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rute Input</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Match DB</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Confidence</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($route_matches as $m)
                        <tr class="{{ !$m['matched'] ? 'bg-red-50' : '' }}">
                            <td class="px-4 py-2 text-sm font-medium">{{ $m['input_route'] }}</td>
                            <td class="px-4 py-2 text-sm">
                                @if($m['matched'])
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ditemukan</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Tidak Ditemukan</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $m['tujuan']['nama'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $m['tujuan']['kode_tujuan'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm">
                                @if($m['matched'])
                                    <span class="{{ $m['confidence'] >= 80 ? 'text-green-600' : 'text-yellow-600' }}">
                                        {{ $m['confidence'] }}%
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Package Details -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Detail Paket & Sopir</h2>
        </div>
        <div class="p-4">
            @foreach($parsed['packages'] as $index => $pkg)
                <div class="mb-6 pb-6 border-b border-gray-200 last:border-0 last:pb-0">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-md font-semibold text-gray-900">Paket {{ $index + 1 }}: {{ $pkg['route_name'] }}</h3>
                        <span class="text-sm text-gray-500">{{ count($pkg['drivers']) }} sopir</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                        @foreach($pkg['drivers'] as $driverName)
                            @php
                                $match = collect($driver_matches)->firstWhere('input_name', $driverName);
                            @endphp
                            <div class="px-3 py-2 text-sm rounded border
                                @if($match && $match['matched'])
                                    bg-green-50 border-green-200 text-green-800
                                @else
                                    bg-red-50 border-red-200 text-red-800
                                @endif">
                                {{ $driverName }}
                                @if($match && $match['matched'])
                                    <span class="ml-1 text-xs opacity-75">({{ $match['confidence'] }}%)</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Action Buttons -->
    @if(!$auto_create)
        <div class="mt-6 flex justify-end gap-3">
            <form action="{{ route('ritase.parser.process') }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="periode_id" value="{{ $periode_id }}">
                <input type="hidden" name="text" value="{{ $original_text }}">
                <input type="hidden" name="auto_create" value="1">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700">
                    Simpan Semua ke Database
                </button>
            </form>
            <a href="{{ route('ritase.parser.form') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300">
                Edit Ulang
            </a>
        </div>
    @else
        <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-800"><strong>Berhasil!</strong> Data ritase telah disimpan ke database.</p>
            <div class="mt-2">
                <a href="{{ route('ritase.index') }}" class="text-green-600 hover:underline">Lihat Data Ritase</a>
                <span class="text-gray-400 mx-2">|</span>
                <a href="{{ route('ritase.parser.form') }}" class="text-green-600 hover:underline">Parse Lagi</a>
            </div>
        </div>
    @endif
</div>
@endsection