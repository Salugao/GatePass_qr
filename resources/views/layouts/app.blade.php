<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Workspace') · GatePassQR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ route('local-assets.css') }}">
        <script type="module" src="{{ route('local-assets.js') }}"></script>
    @endif
    @stack('head')
</head>
<body>
@auth
    <div class="shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark"><i data-lucide="scan-qr-code"></i></span>
                <span><strong>GatePass<span>QR</span></strong><small>Campus access</small></span>
            </a>
            <div class="nav-caption">WORKSPACE</div>
            <nav class="main-nav" aria-label="Main navigation">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i data-lucide="layout-dashboard"></i>Overview</a>
                @if(in_array(auth()->user()->role, ['administrator', 'security', 'laboratory', 'office'], true))
                    <a class="nav-link {{ request()->routeIs('scanner') ? 'active' : '' }}" href="{{ route('scanner') }}"><i data-lucide="scan-line"></i>Scan access</a>
                    <a class="nav-link {{ request()->routeIs('visitors.*') ? 'active' : '' }}" href="{{ route('visitors.index') }}"><i data-lucide="contact-round"></i>Visitors</a>
                @endif
                @if(auth()->user()->role === 'administrator')
                    <a class="nav-link {{ request()->routeIs('members.*') ? 'active' : '' }}" href="{{ route('members.index') }}"><i data-lucide="id-card"></i>People & IDs</a>
                @endif
                @if(in_array(auth()->user()->role, ['administrator', 'security', 'laboratory', 'office'], true))
                    <a class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}"><i data-lucide="list-filter"></i>Access log</a>
                @endif
                @if(in_array(auth()->user()->role, ['student', 'faculty', 'staff', 'administrator'], true))
                    <a class="nav-link {{ request()->routeIs('my-id') ? 'active' : '' }}" href="{{ route('my-id') }}"><i data-lucide="badge-check"></i>Digital ID</a>
                @endif
                @if(auth()->user()->role === 'administrator')
                    <div class="nav-caption nav-caption-spaced">CONTROL</div>
                    <a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}"><i data-lucide="shield-check"></i>Audit trail</a>
                    <form method="POST" action="{{ route('backup.store') }}" class="nav-form">@csrf<button class="nav-link" type="submit"><i data-lucide="database-backup"></i>Download backup</button></form>
                @endif
            </nav>
            <div class="sidebar-bottom">
                <div class="campus-chip"><span class="live-dot"></span><span>Campus network</span><strong>Online</strong></div>
                <div class="profile-mini">
                    <span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    <span class="profile-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ str_replace('_', ' ', ucfirst(auth()->user()->role)) }}</small></span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-button" type="submit" title="Sign out" aria-label="Sign out"><i data-lucide="log-out"></i></button></form>
                </div>
            </div>
        </aside>
        <main class="main-area">
            <header class="topbar">
                <button class="icon-button menu-button" type="button" data-menu-toggle aria-label="Open navigation"><i data-lucide="menu"></i></button>
                <div class="breadcrumbs"><span>GatePassQR</span><i data-lucide="chevron-right"></i><strong>@yield('section', 'Overview')</strong></div>
                <div class="topbar-right"><span class="today-label">{{ now()->format('l, d M Y') }}</span><span class="topbar-separator"></span><span class="topbar-role">{{ str_replace('_', ' ', ucfirst(auth()->user()->role)) }}</span></div>
            </header>
            <div class="page-wrap">
                @if(session('status'))<div class="notice notice-success"><i data-lucide="circle-check"></i><span>{{ session('status') }}</span></div>@endif
                @if($errors->any())<div class="notice notice-error"><i data-lucide="triangle-alert"></i><span>{{ $errors->first() }}</span></div>@endif
                @yield('content')
            </div>
        </main>
    </div>
@else
    @yield('public-content')
@endauth
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
@stack('scripts')
</body>
</html>