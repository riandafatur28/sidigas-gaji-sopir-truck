<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} - SIDIGAS</title>
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

        /* SIDEBAR */
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

        /* MAIN CONTENT */
        .main-content {
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* NAVIGASI */
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
        .nav-item:hover {
            background: #f3f4f6;
            color: #1f2937;
        }
        .nav-item.active {
            background: #eff6ff;
            color: #2563eb;
            font-weight: 600;
        }
        .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }

        /* HEADER */
        .header-bar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 40;
            height: 64px;
            display: flex;
            align-items: center;
        }

        /* OVERLAY */
        .overlay {
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(2px);
            position: fixed;
            inset: 0;
            z-index: 45;
            display: none;
        }
        .overlay.show { display: block; }

        /* SIDEBAR LABEL */
        .sidebar-label {
            font-size: 11px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0 12px;
            margin-bottom: 8px;
            display: block;
        }

        /* LOGO */
        .logo-text { font-size: 18px; font-weight: 700; color: #1f2937; }
        .logo-sub { font-size: 10px; color: #9ca3af; }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: #f3f4f6; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        /* HAMBURGER BUTTON */
        .hamburger-btn {
            padding: 8px;
            border-radius: 8px;
            transition: background 0.2s ease;
            cursor: pointer;
            background: transparent;
            border: none;
        }
        .hamburger-btn:hover { background: #f3f4f6; }
        .hamburger-btn svg { width: 24px; height: 24px; color: #4b5563; }

        /* RESPONSIVE */
        @media (max-width: 1023px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-show { transform: translateX(0) !important; }
            .main-content { margin-left: 0 !important; }
        }
        @media (min-width: 1024px) {
            .sidebar { transform: translateX(0); }
            .sidebar.hidden-desktop { transform: translateX(-100%) !important; }
            .main-content { margin-left: 280px; }
            .main-content.full-width { margin-left: 0 !important; }
        }
    </style>
    @stack('styles')
</head>
<body>

    {{-- OVERLAY --}}
    <div id="overlay" class="overlay" onclick="toggleSidebar()"></div>

    {{-- ========================================= --}}
    {{-- SIDEBAR --}}
    {{-- ========================================= --}}
    <aside id="sidebar" class="sidebar">
        {{-- LOGO --}}
        <div class="p-6 border-b border-gray-200">
            <h1 class="logo-text text-gray-900">SIDIGAS</h1>
        </div>

        {{-- MENU --}}
        <nav class="p-4 space-y-0.5">
            <span class="sidebar-label">Menu</span>

            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('sopir.index') }}" class="nav-item {{ request()->routeIs('sopir.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>Kelola Sopir</span>
            </a>

            <a href="{{ route('tujuan.index') }}" class="nav-item {{ request()->routeIs('tujuan.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span>Kelola Tujuan</span>
            </a>

            <a href="{{ route('validasi-bukti.kelola') }}" class="nav-item {{ request()->routeIs('validasi-bukti.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Validasi Bukti</span>
            </a>

            <a href="{{ route('periode.index') }}" class="nav-item {{ request()->routeIs('periode.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span>Kelola Periode</span>
            </a>

            <a href="{{ route('ritase.index') }}" class="nav-item {{ request()->routeIs('ritase.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                <span>Kelola Ritase</span>
            </a>

            <span class="sidebar-label mt-5">Keuangan</span>

            <a href="{{ route('gaji.index') }}" class="nav-item {{ request()->routeIs('gaji.index') || request()->routeIs('gaji.edit') || request()->routeIs('gaji.slip') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Hitung Gaji</span>
            </a>

            <a href="{{ route('gaji.riwayat') }}" class="nav-item {{ request()->routeIs('gaji.riwayat') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Riwayat Gaji</span>
            </a>

            <a href="{{ route('gaji.laporan') }}" class="nav-item {{ request()->routeIs('gaji.laporan') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Laporan Gaji</span>
            </a>

            <span class="sidebar-label mt-5">Akun</span>

            <a href="{{ route('profil') }}" class="nav-item {{ request()->routeIs('profil') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>Profil</span>
            </a>
        </nav>

        {{-- USER DROPDOWN --}}
        <div class="p-4 border-t border-gray-200 mt-auto relative" id="userDropdown">
            <button onclick="toggleUserMenu()" class="w-full flex items-center gap-3 px-3 py-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-all cursor-pointer">
                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-gray-600">{{ substr($user->name, 0, 1) }}</span>
                </div>
                <div class="flex-1 min-w-0 text-left">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $user->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                </div>
                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" id="userChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div id="userMenu" class="absolute bottom-full left-4 right-4 mb-2 bg-white border border-gray-200 rounded-lg shadow-lg hidden overflow-hidden">
            <a href="{{ route('profil') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>Profil</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ========================================= --}}
    {{-- MAIN CONTENT --}}
    {{-- ========================================= --}}
    <div id="mainContent" class="main-content">

        {{-- HEADER BAR --}}
        <header class="header-bar">
            <div class="px-4 sm:px-6 lg:px-8 w-full">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        {{-- HAMBURGER BUTTON --}}
                        <button id="hamburgerBtn" class="hamburger-btn" onclick="toggleSidebar()" title="Toggle Sidebar">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <h1 class="text-lg font-semibold text-gray-800">{{ $pageTitle ?? 'Dashboard' }}</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-500 hidden md:inline">{{ now()->translatedFormat('d F Y') }}</span>
                        <span class="text-gray-300 hidden md:inline">|</span>
                        <span id="liveTime" class="text-sm text-gray-600 font-mono">00:00:00</span>
                    </div>
                </div>
            </div>
        </header>

        {{-- CONTENT --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>

    </div>

    {{-- ========================================= --}}
    {{-- SCRIPTS --}}
    {{-- ========================================= --}}
    <script>
        (function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const mainContent = document.getElementById('mainContent');
            const isMobile = () => window.innerWidth < 1024;

            let isSidebarOpen = !isMobile();

            function updateSidebarState() {
                const mobile = isMobile();

                if (mobile) {
                    // Mobile: pakai class mobile-show
                    sidebar.classList.remove('hidden-desktop');
                    if (isSidebarOpen) {
                        sidebar.classList.add('mobile-show');
                        overlay.classList.add('show');
                    } else {
                        sidebar.classList.remove('mobile-show');
                        overlay.classList.remove('show');
                    }
                    mainContent.classList.remove('full-width');
                } else {
                    // Desktop: pakai class hidden-desktop
                    sidebar.classList.remove('mobile-show');
                    overlay.classList.remove('show');
                    if (isSidebarOpen) {
                        sidebar.classList.remove('hidden-desktop');
                        mainContent.classList.remove('full-width');
                    } else {
                        sidebar.classList.add('hidden-desktop');
                        mainContent.classList.add('full-width');
                    }
                }
            }

            window.toggleSidebar = function() {
                isSidebarOpen = !isSidebarOpen;
                updateSidebarState();
            };

            window.addEventListener('resize', function() {
                // Reset state sesuai ukuran layar
                if (isMobile()) {
                    // Jika di mobile, sidebar default tertutup
                    if (!sidebar.classList.contains('mobile-show') && isSidebarOpen) {
                        isSidebarOpen = false;
                    }
                } else {
                    // Jika di desktop, sidebar default terbuka
                    if (sidebar.classList.contains('hidden-desktop') && !isSidebarOpen) {
                        isSidebarOpen = true;
                    }
                }
                updateSidebarState();
            });

            // ESC close sidebar
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isSidebarOpen) {
                    if (isMobile()) {
                        isSidebarOpen = false;
                        updateSidebarState();
                    }
                }
            });

            // Click outside (mobile only)
            overlay.addEventListener('click', function() {
                if (isMobile() && isSidebarOpen) {
                    isSidebarOpen = false;
                    updateSidebarState();
                }
            });

            // USER DROPDOWN
            window.toggleUserMenu = function() {
                const menu = document.getElementById('userMenu');
                const chevron = document.getElementById('userChevron');
                const isHidden = menu.classList.contains('hidden');
                menu.classList.toggle('hidden');
                if (chevron) {
                    chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            };
            document.addEventListener('click', function(e) {
                const dropdown = document.getElementById('userDropdown');
                const menu = document.getElementById('userMenu');
                if (dropdown && !dropdown.contains(e.target) && !menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                    const chevron = document.getElementById('userChevron');
                    if (chevron) chevron.style.transform = 'rotate(0deg)';
                }
            });

            // INIT
            updateSidebarState();

            // LIVE TIME
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

        })();
    </script>
    @stack('scripts')
</body>
</html>
