<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIDIGAS') - SIDIGAS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f0f2; }
        .sidebar {
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            transition: transform 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 280px;
            z-index: 50;
            overflow-y: auto;
        }
        .sidebar-hidden { transform: translateX(-100%) !important; }
        .main-content {
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .nav-item {
            color: #6b7280;
            transition: all 0.2s ease;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nav-item:hover { background: #f3f4f6; color: #1f2937; }
        .nav-item.active { background: #eff6ff; color: #2563eb; font-weight: 600; }
        .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
        .header-bar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 40;
        }
        @media (max-width: 1023px) {
            .main-content { margin-left: 0 !important; }
        }
        @media (min-width: 1024px) {
            .main-content { margin-left: 280px; }
            .main-content.full-width { margin-left: 0 !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
    {{-- Sidebar --}}
    @auth
    <div id="sidebar" class="sidebar">
        <div class="p-4 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-900">SIDIGAS</h1>
            <p class="text-sm text-gray-500">{{ auth()->user()->name }}</p>
        </div>
        <nav class="p-3 space-y-1">
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="{{ route('ritase.index') }}" class="nav-item {{ request()->routeIs('ritase.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Ritase
            </a>
            <a href="{{ route('ritase.parser') }}" class="nav-item {{ request()->routeIs('ritase.parser*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Parser
            </a>
            <a href="{{ route('gaji.index') }}" class="nav-item {{ request()->routeIs('penggajian*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Penggajian
            </a>
        </nav>
    </div>
    @endauth

    {{-- Main content --}}
    <div id="mainContent" class="main-content">
        @auth
        <div class="header-bar px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 hover:bg-gray-100 rounded">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="text-lg font-semibold text-gray-900">@yield('title', 'Dashboard')</h2>
            </div>
            <div class="flex items-center gap-3">
                <span id="liveTime" class="text-sm text-gray-500"></span>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm bg-red-50 text-red-600 rounded hover:bg-red-100">Logout</button>
                </form>
            </div>
        </div>
        @endauth

        <div class="p-6 flex-1">
            @yield('content')
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sb = document.getElementById('sidebar');
            const mc = document.getElementById('mainContent');
            sb.classList.toggle('sidebar-hidden');
            mc.classList.toggle('full-width');
        }
        function updateDateTime() {
            const now = new Date();
            const time = String(now.getHours()).padStart(2,'0') + ':' +
                         String(now.getMinutes()).padStart(2,'0') + ':' +
                         String(now.getSeconds()).padStart(2,'0');
            const el = document.getElementById('liveTime');
            if (el) el.textContent = time;
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('userDropdown');
            const menu = document.getElementById('userMenu');
            if (dropdown && !dropdown.contains(e.target) && menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                const chevron = document.getElementById('userChevron');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
