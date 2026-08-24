<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} - SIDIGAS</title>
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"></noscript>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <noscript><link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet"></noscript>
    <script defer src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <script>
        // Apply theme BEFORE paint — prevents flash gelap-terang
        (function() {
            var saved = localStorage.getItem('theme');
            if (saved === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (!saved) {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>
    <style>
        /* ============================================
           SIDIGAS DESIGN SYSTEM — hangat, modern, gak kaku
           ============================================ */
        :root {
            --sidebar-w: 270px;
            --bg: #f6f3ee;
            --card-bg: #fffdfc;
            --primary: #2d6a4f;
            --primary-light: #e8f5e9;
            --accent: #c9774d;
            --accent-light: #f5ede7;
            --success: #5b8c6f;
            --success-light: #eaf3ed;
            --danger: #c45a5a;
            --danger-light: #f8eaea;
            --warning: #d4a55a;
            --warning-light: #f7f0e0;
            --text: #1e1e2a;
            --text-muted: #8a8698;
            --text-dims: #b0acbc;
            --border: #e8e4de;
            --header-bg: rgba(255,253,252,0.85);
            --sidebar-bg: #fffdfc;
            --input-bg: #fff;
            --table-hover: rgba(232,229,239,0.3);
            --scrollbar-thumb: rgba(0,0,0,0.12);
            --scrollbar-thumb-hover: rgba(0,0,0,0.2);
        }

        .dark {
            --bg: #000000;
            --card-bg: #111111;
            --primary: #4ade80;
            --primary-light: #1a1a1a;
            --accent: #d4926a;
            --accent-light: #1a1a1a;
            --success: #6da883;
            --success-light: #1a1a1a;
            --danger: #f87171;
            --danger-light: #1a1a1a;
            --warning: #d4b06a;
            --warning-light: #1a1a1a;
            --text: #e5e5e5;
            --text-muted: #9e9e9e;
            --text-dims: #6b6b6b;
            --border: #2a2a2a;
            --header-bg: rgba(0,0,0,0.92);
            --sidebar-bg: #0a0a0a;
            --input-bg: #1a1a1a;
            --table-hover: rgba(255,255,255,0.04);
            --scrollbar-thumb: rgba(255,255,255,0.12);
            --scrollbar-thumb-hover: rgba(255,255,255,0.2);
        }
        /* ===== DARK MODE: Tailwind utility overrides ===== */
        /* Text colors — map to dark palette */
        .dark .text-gray-900,
        .dark .text-gray-800,
        .dark .text-gray-700 { color: var(--text) !important; }
        .dark .text-gray-600,
        .dark .text-gray-500 { color: var(--text-muted) !important; }
        .dark .text-gray-400 { color: var(--text-dims) !important; }
        .dark .text-gray-300 { color: var(--text-dims) !important; }
        .dark .text-gray-200 { color: var(--text-dims) !important; }
        /* Containers & cards */
        .dark .bg-white { background: var(--card-bg) !important; }
        /* General form inputs — catch any without explicit bg class */
        .dark input:not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
        .dark textarea,
        .dark select {
            background: var(--input-bg) !important;
            color: var(--text) !important;
        }
        /* Calendar icon in date inputs — make white in dark mode */
        .dark input[type="date"]::-webkit-calendar-picker-indicator,
        .dark input[type="month"]::-webkit-calendar-picker-indicator,
        .dark input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }
        .dark .bg-gray-50 { background: rgba(255,255,255,0.04) !important; }
        .dark .bg-gray-100 { background: rgba(255,255,255,0.06) !important; }
        .dark .bg-gray-200 { background: rgba(255,255,255,0.09) !important; }
        /* Borders */
        .dark .border-gray-200 { border-color: var(--border) !important; }
        .dark .border-gray-300 { border-color: var(--border) !important; }
        .dark .divide-gray-100 > * + *,
        .dark .divide-gray-200 > * + * { border-color: var(--border) !important; }
        /* Hover — override Tailwind hover:bg-gray-50 */
        .dark .hover\:bg-gray-50:hover { background-color: var(--table-hover) !important; }
        /* Table header inline background override */
        .dark thead[style*="background"],
        .dark tr[style*="background"] { background: var(--card-bg) !important; }
        .dark tfoot[style*="background"] { background: var(--card-bg) !important; }
        /* Buttons */
        .dark .btn-primary { background: #2d6a4f !important; }  /* green primary */
        .dark .btn-primary:hover { background: #1b4332 !important; }
        .dark .btn-secondary { background: rgba(255,255,255,0.08) !important; }
        .dark .btn-secondary:hover { background: rgba(255,255,255,0.12) !important; }
        /* Badges */
        .dark .badge-success,
        .dark .badge-warning,
        .dark .badge-danger,
        .dark .badge-info { color: var(--text) !important; }
        .dark .badge-neutral { background: rgba(255,255,255,0.08) !important; }
        /* Injected slip/PDF content (modal) — all dark */
        .dark .slip-container,
        .dark .slip-block { background: var(--card-bg) !important; border-color: var(--border) !important; }
        .dark .slip-container th,
        .dark .slip-container td,
        .dark .slip-block th,
        .dark .slip-block td { background: var(--card-bg) !important; border-color: var(--border) !important; color: var(--text) !important; }
        .dark .slip-header,
        .dark .block-header,
        .dark .block-footer { background: var(--card-bg) !important; border-color: var(--border) !important; color: var(--text) !important; }
        .dark .slip-block td.label { background: var(--card-bg) !important; color: var(--text) !important; }
        .dark .slip-container .text-right,
        .dark .slip-container .font-bold,
        .dark .slip-container .label-tujuan-nama { color: var(--text) !important; }
        .dark .page-break { border-color: var(--border) !important; }
        .dark .slip-container .print-btn { background: #2d6a4f !important; color: var(--text) !important; border: 1px solid var(--border) !important; }
        /* Red utility — hapus button, error, required marker */
        .dark .text-red-500 { color: #f87171 !important; }
        .dark .text-red-600 { color: #f87171 !important; }
        .dark .text-red-700 { color: #fca5a5 !important; }
        .dark .border-red-200 { border-color: rgba(248,113,113,0.25) !important; }
        .dark .hover\:bg-red-50:hover { background: rgba(248,113,113,0.1) !important; }
        .dark .hover\:bg-red-100:hover { background: rgba(248,113,113,0.15) !important; }
        .dark .bg-red-50 { background: rgba(248,113,113,0.1) !important; }
        .dark .bg-red-100 { background: rgba(248,113,113,0.15) !important; }
        /* Alerts — satu warna dark mode */
        .dark .alert-info,
        .dark .alert-success,
        .dark .alert-error,
        .dark .alert-danger,
        .dark .alert-warning {
            background: rgba(255,255,255,0.06) !important;
            color: var(--text) !important;
        }
        /* Semantic backgrounds — satu warna, no tints */
        .dark .bg-green-50,
        .dark .bg-green-100,
        .dark .bg-green-500,
        .dark .bg-green-50,
        .dark .bg-green-100,
        .dark .bg-amber-50,
        .dark .bg-amber-50\/30,
        .dark .bg-green-50,
        .dark .bg-green-100,
        .dark .bg-green-50\/30,
        .dark .bg-yellow-100,
        .dark .bg-orange-100 { background: transparent !important; }
        /* Semantic text — satu warna, pakai var(--text) */
        .dark .text-green-600,
        .dark .text-green-700,
        .dark .text-green-800,
        .dark .text-green-600,
        .dark .text-green-700,
        .dark .text-green-800,
        .dark .text-amber-500,
        .dark .text-amber-600,
        .dark .text-green-600,
        .dark .text-green-700,
        .dark .text-yellow-600,
        .dark .text-yellow-700,
        .dark .text-orange-600,
        .dark .text-orange-700 { color: var(--text) !important; }
        /* Semantic borders */
        .dark .border-green-200,
        .dark .border-green-200 { border-color: var(--border) !important; }
        /* Hover backgrounds — satu warna */
        .dark .hover\:bg-green-100:hover,
        .dark .hover\:bg-green-100:hover { background: var(--table-hover) !important; }
        /* Ritase detail-table — semua gelap, satu warna */
        /* TomSelect dropdown — dark mode */
        .dark .ts-wrapper .ts-control {
            background: var(--input-bg) !important;
            border-color: var(--border) !important;
            color: var(--text) !important;
        }
        .dark .ts-wrapper .ts-control input {
            color: var(--text) !important;
        }
        .dark .ts-wrapper .ts-control .item {
            color: var(--text) !important;
        }
        .dark .ts-dropdown {
            background: var(--input-bg) !important;
            border-color: var(--border) !important;
        }
        .dark .ts-dropdown .option {
            color: var(--text) !important;
            background: transparent !important;
        }
        .dark .ts-dropdown .option.active,
        .dark .ts-dropdown .option:hover {
            background: var(--table-hover) !important;
            color: var(--text) !important;
        }
        .dark .ts-dropdown .option.active.focus {
            background: var(--table-hover) !important;
        }
        .dark .ts-wrapper.multi .ts-control > div {
            background: rgba(255,255,255,0.08) !important;
            color: var(--text) !important;
        }
        /* All th/td — prevent injected slip styles from leaking globally */
        .dark th { background: var(--card-bg) !important; }
        .dark td { background: var(--card-bg) !important; }
        /* Ritase detail-table — semua gelap, satu warna */
        .dark .detail-table th,
        .dark .detail-table td,
        .dark .detail-table tr { background: var(--card-bg) !important; }
        .dark .detail-table th,
        .dark .detail-table td { border-color: var(--border) !important; }

        * { font-family: 'Inter', 'system-ui', sans-serif !important; }

        body {
            background: var(--bg) !important;
            color: var(--text) !important;
        }

        /* ============================================
           SIDEBAR
           ============================================ */
        .sidebar {
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-w);
            z-index: 50;
            overflow-y: auto;
            transition: transform 0.3s ease;
            will-change: transform;
        }
        .sidebar-hidden { transform: translateX(-100%) !important; }

        .sidebar-logo {
            padding: 24px 24px 16px;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-logo h1 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.02em;
        }
        .sidebar-logo p {
            font-size: 10px;
            color: var(--text-dims);
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .sidebar-nav {
            padding: 12px 12px;
        }
        .sidebar-section {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text);
            padding: 20px 14px 6px;
            display: block;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            margin-bottom: 2px;
            border-radius: 10px;
            color: var(--text);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .nav-item:hover {
            background: var(--primary-light);
            color: var(--primary);
        }
        .nav-item.active {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }
        .nav-item svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            opacity: 0.6;
        }
        .nav-item.active svg { opacity: 1; }
        .nav-item.logout {
            margin-top: 8px;
            border-top: 1px solid var(--border);
            padding-top: 14px;
            color: var(--danger);
        }
        .nav-item.logout:hover {
            background: var(--danger-light);
            color: var(--danger);
        }
        .nav-item.logout svg { opacity: 1; }

        /* ============================================
           MAIN CONTENT
           ============================================ */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }
        .main-content.full-width { margin-left: 0 !important; }

        /* ============================================
           HEADER BAR
           ============================================ */
        .header-bar {
            background: var(--header-bg);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 40;
            height: 64px;
            display: flex;
            align-items: center;
        }

        /* ============================================
           OVERLAY (mobile sidebar)
           ============================================ */
        .overlay {
            background: rgba(0,0,0,0.35);
            position: fixed;
            inset: 0;
            z-index: 45;
            display: none;
        }
        .overlay.show { display: block; }

        /* ============================================
           CARDS — hangat, shadow, no harsh borders
           ============================================ */
        .card {
            background: var(--card-bg);
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(74,63,107,0.05);
            overflow: hidden;
        }
        .card-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-body {
            padding: 20px 24px;
        }

        /* stat cards */
        .stat-card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 20px 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(74,63,107,0.05);
        }
        .stat-card .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        /* ============================================
           BUTTONS
           ============================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-primary:hover {
            background: #1b4332;
            box-shadow: 0 4px 14px rgba(45,106,79,0.25);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #f0ede8;
            color: var(--text);
        }
        .btn-secondary:hover {
            background: #e5e0d8;
        }
        .btn-outline {
            background: transparent;
            border: 1.5px solid var(--border);
            color: var(--text);
        }
        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }
        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
            border-radius: 8px;
        }
        .btn-xs {
            padding: 4px 10px;
            font-size: 12px;
            border-radius: 6px;
        }

        /* ============================================
           TABLES
           ============================================ */
        .table-wrap {
            overflow-x: auto;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }

        /* Pagination responsive */
        @media (max-width: 640px) {
            .pagination-wrap .page-num { display: none !important; }
            .pagination-wrap .page-ellipsis { display: none !important; }
        }
        .table-wrap table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-wrap th {
            text-align: left;
            padding: 12px 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            border-bottom: 1.5px solid var(--border);
            background: var(--card-bg);
        }
        .table-wrap td {
            padding: 14px 20px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }
        .table-wrap tr:last-child td { border-bottom: none; }
        .table-wrap tr:hover td {
            background: var(--table-hover);
        }

        /* ============================================
           FORM ELEMENTS
           ============================================ */
        .form-input {
            width: 100%;
            padding: 10px 16px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            background: var(--input-bg);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            outline: none;
            color: var(--text);
        }
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74,63,107,0.1);
        }
        .form-input::placeholder { color: var(--text-dims); }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%238a8698' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        .form-input option,
        .form-select option,
        select option {
            background: var(--input-bg);
            color: var(--text);
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
            letter-spacing: 0.02em;
        }

        /* ============================================
           ALERTS
           ============================================ */
        .alert {
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert-success {
            background: var(--success-light);
            color: #2d5a42;
        }
        .alert-error, .alert-danger {
            background: var(--danger-light);
            color: #8a3a3a;
        }
        .alert-warning {
            background: var(--warning-light);
            color: #7a5e2a;
        }
        .alert-info {
            background: #e8f5e9;
            color: #2d6a4f;
        }

        /* ============================================
           BADGE / STATUS TAG
           ============================================ */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        .badge-success { background: var(--success-light); color: #2d5a42; }
        .badge-warning { background: var(--warning-light); color: #7a5e2a; }
        .badge-danger { background: var(--danger-light); color: #8a3a3a; }
        .badge-info { background: var(--primary-light); color: var(--primary); }
        .badge-neutral { background: #f0ede8; color: var(--text-muted); }

        /* ============================================
           HAMBURGER
           ============================================ */
        .hamburger-btn {
            padding: 6px;
            border-radius: 8px;
            transition: background 0.15s ease;
            cursor: pointer;
            background: transparent;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hamburger-btn:hover { background: var(--primary-light); }
        .hamburger-btn svg { width: 18px; height: 18px; color: var(--text-muted); }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 1023px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-show { transform: translateX(0) !important; }
            .main-content { margin-left: 0 !important; }
        }
        @media (min-width: 1024px) {
            .sidebar { transform: translateX(0); }
            .sidebar.hidden-desktop { transform: translateX(-100%) !important; }
            .main-content { margin-left: var(--sidebar-w); }
            .main-content.full-width { margin-left: 0 !important; }
        }

        /* ============================================
           SCROLLBAR
           ============================================ */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--scrollbar-thumb-hover); }

        /* ============================================
           TOGGLER (sidebar desktop show/hide)
           ============================================ */
        .sidebar-toggler {
            position: fixed;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            z-index: 49;
            width: 24px;
            height: 48px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-left: none;
            border-radius: 0 10px 10px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 2px 0 8px rgba(0,0,0,0.08);
            color: var(--text-muted);
        }
        .sidebar-toggler:hover { background: var(--primary-light); color: var(--primary); }
        .sidebar-toggler svg { width: 14px; height: 14px; }
    </style>
    @stack('styles')
</head>
<body>

    <div id="overlay" class="overlay" onclick="toggleSidebar()"></div>

    {{-- ========================================= --}}
    {{-- SIDEBAR --}}
    {{-- ========================================= --}}
    <aside id="sidebar" class="sidebar">
        <div class="sidebar-logo">
            <div class="flex items-center justify-between">
                <div style="flex:1;min-width:0">
                    <h1>SIDIGAS</h1>
                    <p>Sistem Distribusi Gaji</p>
                </div>
                <button class="hamburger-btn hamburger-sidebar" onclick="(window.innerWidth>=1024?toggleSidebarDesktop():toggleSidebar())" aria-label="Toggle sidebar">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <nav class="sidebar-nav">

            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('sopir.index') }}" class="nav-item {{ request()->routeIs('sopir.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <span>Kelola Sopir</span>
            </a>

            <a href="{{ route('tujuan.index') }}" class="nav-item {{ request()->routeIs('tujuan.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span>Kelola Tujuan</span>
            </a>

            <a href="{{ route('periode.index') }}" class="nav-item {{ request()->routeIs('periode.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span>Kelola Periode</span>
            </a>

            <a href="{{ route('ritase.index') }}" class="nav-item {{ request()->routeIs('ritase.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                <span>Kelola Ritase</span>
            </a>

            <span class="sidebar-section">Keuangan</span>

            <a href="{{ route('gaji.index') }}" class="nav-item {{ request()->routeIs('gaji.index') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Hitung Gaji</span>
            </a>

            <a href="{{ route('gaji.riwayat') }}" class="nav-item {{ request()->routeIs('gaji.riwayat') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span>Riwayat Gaji</span>
            </a>

            <span class="sidebar-section">Lainnya</span>

            <a href="{{ route('validasi-bukti.kelola') }}" class="nav-item {{ request()->routeIs('validasi-bukti.*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Validasi Bukti</span>
            </a>

            <a href="{{ route('gaji.laporan') }}" class="nav-item {{ request()->routeIs('gaji.laporan') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Laporan Gaji</span>
            </a>

            <div class="relative" id="userDropdownWrapper" style="margin-top:8px;border-top:1px solid var(--border);padding-top:14px;">
                <button onclick="toggleUserDropdown()" class="nav-item w-full" style="justify-content:space-between">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[var(--primary)] text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">{{ substr(Auth::user()->name ?? 'A', 0, 1) }}</div>
                        <span class="truncate">{{ Auth::user()->name ?? 'Admin' }}</span>
                    </span>
                    <svg id="userDropdownArrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="userDropdownMenu" class="hidden absolute left-0 right-0 bottom-full mb-2 bg-[var(--card-bg)] border border-[var(--border)] rounded-lg shadow-lg overflow-hidden z-50">
                    <a href="{{ route('profil') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-[var(--text)] hover:bg-[var(--primary-light)] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Profil
                    </a>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('sidebarLogoutForm').submit();" class="flex items-center gap-3 px-4 py-3 text-sm text-[var(--danger)] hover:bg-[var(--danger-light)] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Keluar
                    </a>
                </div>
            </div>
            <form id="sidebarLogoutForm" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
        </nav>
    </aside>

    {{-- SIDEBAR TOGGLER (desktop) — shows when sidebar is hidden --}}
    <div id="sidebarToggler" class="sidebar-toggler" onclick="toggleSidebarDesktop()" title="Toggle sidebar" style="display:none">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </div>

    {{-- ========================================= --}}
    {{-- MAIN CONTENT --}}
    {{-- ========================================= --}}
    <div id="mainContent" class="main-content" style="position:relative">

        {{-- HEADER BAR --}}
        <header class="header-bar px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    {{-- Mobile hamburger --}}
                    <button class="hamburger-btn lg:hidden" onclick="toggleSidebar()" aria-label="Buka menu">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="hidden sm:block">
                        <h2 class="text-base font-semibold text-[var(--text)]">{{ $pageTitle ?? $title ?? 'Dashboard' }}</h2>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Theme toggle --}}
                    <button id="themeToggle" onclick="toggleTheme()" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-[var(--primary-light)] transition text-sm" style="color:var(--text-muted)" title="Ganti tema">
                        <svg id="themeIconLight" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg id="themeIconDark" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>
                    <span id="liveDate" class="text-sm text-[var(--text)] font-medium">{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('j F Y') }}</span>
                    <span class="text-sm text-[var(--text)]">|</span>
                    <span id="liveTime" class="text-sm text-[var(--text)] font-mono tabular-nums">00:00:00</span>
                </div>
            </div>
        </header>

        {{-- CONTENT --}}
        <main class="flex-1 p-6 sm:p-8 lg:p-10">
            {{ $slot }}
        </main>

        {{-- SKELETON OVERLAY — dalem mainContent biar sidebar keliatan --}}
    {{-- ========================================= --}}
    {{-- SCRIPTS --}}
    {{-- ========================================= --}}
    @stack('scripts')

    <script>
        (function() {
            'use strict';

            // ===== TOGGLE SIDEBAR (mobile) =====
            window.toggleSidebar = function() {
                var sidebar = document.getElementById('sidebar');
                var overlay = document.getElementById('overlay');
                sidebar.classList.toggle('mobile-show');
                overlay.classList.toggle('show');
                document.body.style.overflow = sidebar.classList.contains('mobile-show') ? 'hidden' : '';
            };

            // ===== TOGGLE SIDEBAR (desktop) =====
            window.toggleSidebarDesktop = function() {
                var sidebar = document.getElementById('sidebar');
                var main = document.getElementById('mainContent');
                var toggler = document.getElementById('sidebarToggler');
                var hamburger = document.querySelector('.hamburger-sidebar');
                sidebar.classList.toggle('hidden-desktop');
                main.classList.toggle('full-width');
                if (sidebar.classList.contains('hidden-desktop')) {
                    toggler.style.display = 'flex';
                    if (hamburger) hamburger.style.display = 'none';
                } else {
                    toggler.style.display = 'none';
                    if (hamburger) hamburger.style.display = 'flex';
                }
            };

            // (hover toggler removed — hamburger in sidebar handles toggle)

            // ===== USER DROPDOWN =====
            window.toggleUserDropdown = function() {
                var menu = document.getElementById('userDropdownMenu');
                var arrow = document.getElementById('userDropdownArrow');
                menu.classList.toggle('hidden');
                if (arrow) arrow.style.transform = menu.classList.contains('hidden') ? '' : 'rotate(180deg)';
            };
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                var wrapper = document.getElementById('userDropdownWrapper');
                var menu = document.getElementById('userDropdownMenu');
                var arrow = document.getElementById('userDropdownArrow');
                if (wrapper && !wrapper.contains(e.target) && menu && !menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                    if (arrow) arrow.style.transform = '';
                }
            });

            // ===== LIVE CLOCK =====
            function updateDateTime() {
                var now = new Date();
                var time = String(now.getHours()).padStart(2,'0') + ':' +
                           String(now.getMinutes()).padStart(2,'0') + ':' +
                           String(now.getSeconds()).padStart(2,'0');
                var el = document.getElementById('liveTime');
                if (el) el.textContent = time;
            }
            updateDateTime();
            window._liveTimeInterval = setInterval(updateDateTime, 1000);
        })();
    </script>
    <script>
        // Desktop sidebar: klik di luar sidebar → collapse
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 1024) return;
            // Skip if inside a modal
            if (e.target.closest('.fixed.inset-0.z-50')) return;
            var sidebar = document.getElementById('sidebar');
            if (!sidebar || sidebar.classList.contains('hidden-desktop')) return;
            if (sidebar.contains(e.target) || e.target.closest('#sidebarToggler') || e.target.closest('.hamburger-btn')) return;
            toggleSidebarDesktop();
        });

        // Modal click-outside — tutup modal kalo klik backdrop
        document.addEventListener('click', function(e) {
            var el = e.target;
            if (el.classList.contains('fixed') && el.classList.contains('inset-0') && el.classList.contains('z-50')) {
                if (el.classList.contains('hidden')) return;
                if (el.style.display !== 'none') {
                    el.classList.contains('flex') ? el.remove() : el.classList.add('hidden');
                }
            }
        });

        // ===== THEME TOGGLE =====
        function toggleTheme() {
            var html = document.documentElement;
            var isDark = html.classList.contains('dark');
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            updateThemeIcons();
        }
        function updateThemeIcons() {
            var isDark = document.documentElement.classList.contains('dark');
            var lightIcon = document.getElementById('themeIconLight');
            var darkIcon = document.getElementById('themeIconDark');
            if (lightIcon) lightIcon.style.display = isDark ? 'none' : '';
            if (darkIcon) darkIcon.style.display = isDark ? '' : 'none';
        }
        // Theme already applied in <head> — no flash on load

        // Cegah browser autofill — paksa autocomplete=off di semua form
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form').forEach(function(f) {
                f.setAttribute('autocomplete', 'off');
            });
        });
    </script>

    {{-- ========================================= --}}
    {{-- SHARED CONFIRMATION MODAL --}}
    {{-- ========================================= --}}
    <div id="confirmModal" class="fixed inset-0 bg-black/40 z-[9999] hidden items-center justify-center">
        <div class="bg-white rounded-lg border border-gray-200 w-full max-w-md mx-4 shadow-xl">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4">
                    <div id="confirmIcon" class="w-12 h-12 rounded-full flex items-center justify-center"></div>
                </div>
                <h3 id="confirmTitle" class="text-lg font-semibold text-gray-900 text-center mb-2"></h3>
                <p id="confirmMessage" class="text-sm text-gray-600 text-center mb-6"></p>
                <div class="flex gap-3">
                    <button onclick="closeConfirmModal()" class="flex-1 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                    <button id="confirmActionBtn" class="flex-1 rounded-lg text-sm font-semibold px-4 py-2.5 text-white transition"></button>
                </div>
            </div>
        </div>
    </div>

    {{-- TOAST NOTIFICATION --}}
    <div id="toast" class="fixed top-4 right-4 z-[9999] hidden">
        <div id="toastBox" class="bg-white border rounded-lg shadow-lg px-4 py-3 flex items-center gap-3 max-w-sm">
            <div id="toastIcon" class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"></div>
            <p id="toastMessage" class="text-sm text-gray-700"></p>
        </div>
    </div>

    <script>
    // ===== SHARED CONFIRM MODAL =====
    window._confirmCallback = null;

    window.showConfirmModal = function(opts) {
        var modal = document.getElementById('confirmModal');
        var icon = document.getElementById('confirmIcon');
        var title = document.getElementById('confirmTitle');
        var msg = document.getElementById('confirmMessage');
        var btn = document.getElementById('confirmActionBtn');

        title.textContent = opts.title || 'Konfirmasi';
        msg.textContent = opts.message || 'Apakah Anda yakin?';

        if (opts.type === 'danger') {
            icon.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-red-100';
            icon.innerHTML = '<svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
            btn.className = 'flex-1 rounded-lg text-sm font-semibold px-4 py-2.5 bg-red-600 text-white hover:bg-red-700 transition';
        } else if (opts.type === 'warning') {
            icon.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-amber-100';
            icon.innerHTML = '<svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
            btn.className = 'flex-1 rounded-lg text-sm font-semibold px-4 py-2.5 bg-amber-600 text-white hover:bg-amber-700 transition';
        } else {
            icon.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-blue-100';
            icon.innerHTML = '<svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            btn.className = 'flex-1 rounded-lg text-sm font-semibold px-4 py-2.5 bg-blue-600 text-white hover:bg-blue-700 transition';
        }

        btn.textContent = opts.confirmText || 'Ya, Lanjutkan';
        window._confirmCallback = opts.onConfirm || null;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closeConfirmModal = function() {
        var modal = document.getElementById('confirmModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        window._confirmCallback = null;
    };

    document.getElementById('confirmActionBtn').addEventListener('click', function() {
        if (window._confirmCallback) window._confirmCallback();
        closeConfirmModal();
    });

    // ===== TOAST NOTIFICATION =====
    window.showToast = function(message, type) {
        var toast = document.getElementById('toast');
        var icon = document.getElementById('toastIcon');
        var box = document.getElementById('toastBox');
        var msg = document.getElementById('toastMessage');

        msg.textContent = message;

        if (type === 'success') {
            icon.className = 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-green-100';
            icon.innerHTML = '<svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            box.className = 'bg-white border border-green-200 rounded-lg shadow-lg px-4 py-3 flex items-center gap-3 max-w-sm';
        } else if (type === 'error') {
            icon.className = 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-red-100';
            icon.innerHTML = '<svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
            box.className = 'bg-white border border-red-200 rounded-lg shadow-lg px-4 py-3 flex items-center gap-3 max-w-sm';
        } else {
            icon.className = 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-amber-100';
            icon.innerHTML = '<svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
            box.className = 'bg-white border border-amber-200 rounded-lg shadow-lg px-4 py-3 flex items-center gap-3 max-w-sm';
        }

        toast.classList.remove('hidden');
        setTimeout(function() { toast.classList.add('hidden'); }, 3000);
    };
    </script>
</body>
</html>
