<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - RSU PKU MUHAMMADIYAH DELANGGU</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-delanggu.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar glass">
            <a class="brand" href="{{ route('dashboard') }}">
                <img class="brand-logo" src="{{ asset('images/logo-delanggu.png') }}" alt="Logo RSU PKU Muhammadiyah Delanggu">
                <span><strong>RSU PKU</strong><small>MUHAMMADIYAH DELANGGU</small></span>
            </a>

            <div class="nav-caption">MENU UTAMA</div>
            <nav class="main-nav" aria-label="Navigasi utama">
                <a class="nav-link nav-dashboard {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg></span> Dashboard
                </a>
                <a class="nav-link nav-master {{ request()->routeIs('elements.*') ? 'active' : '' }}" href="{{ route('elements.index') }}">
                    <span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16M4 12h16M4 19h16"/><circle cx="8" cy="5" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="10" cy="19" r="2"/></svg></span> Master Data
                </a>
                @can('manage-units')
                    <a class="nav-link nav-units {{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}">
                        <span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20v-1a7 7 0 0 1 14 0v1M4 4h3m-3 4h1"/></svg></span> Setting Unit
                    </a>
                @endcan
                <a class="nav-link nav-documents {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                    <span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"/><path d="M13 3v7h7M8 15h8M8 18h6"/></svg></span> Dokumen
                </a>
            </nav>

            <div class="sidebar-note">
                <span class="status-dot"></span>
                <div><strong>Sistem aktif</strong><small>Data tersimpan dengan aman</small></div>
            </div>

            <div class="sidebar-user">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="user-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->isAdmin() ? 'Administrator' : 'User' }}</small></span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="logout-button" type="submit" title="Keluar" aria-label="Keluar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3"/><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg></button>
                </form>
            </div>
        </aside>

        <main class="main-area {{ request()->routeIs('dashboard') ? 'dashboard-page' : '' }}">
            <header class="topbar">
                <div class="topbar-heading">
                    <div class="eyebrow"><span class="topbar-mark" aria-hidden="true"></span>RSU PKU MUHAMMADIYAH DELANGGU</div>
                    <h1>@yield('heading', 'Dashboard')</h1>
                </div>
                <div class="topbar-date">
                    <span class="topbar-date-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg></span>
                    <span>{{ now()->translatedFormat('l, d F Y') }}</span>
                </div>
            </header>

            @yield('content')

            <footer class="footer">© {{ date('Y') }} RSU PKU MUHAMMADIYAH DELANGGU <span>•</span> Sistem Manajemen Akreditasi</footer>
        </main>
    </div>
    <dialog class="confirm-dialog" id="confirm-dialog">
        <form method="dialog" class="confirm-card">
            <span class="confirm-icon" data-confirm-icon><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5m0 4h.01"/><circle cx="12" cy="12" r="9"/></svg></span>
            <h2 data-confirm-title>Konfirmasi</h2>
            <p data-confirm-message>Apakah Anda yakin ingin melanjutkan?</p>
            <div class="confirm-actions">
                <button class="button button-quiet" value="cancel" data-confirm-cancel>Batal</button>
                <button class="button button-danger" value="confirm" data-confirm-submit>Ya, lanjutkan</button>
            </div>
        </form>
    </dialog>
    @if (session('success'))
        <dialog class="confirm-dialog success-dialog" id="success-dialog" aria-labelledby="success-title" aria-describedby="success-description" role="status">
            <form method="dialog" class="confirm-card success-card">
                <button class="success-close" type="submit" value="close" aria-label="Tutup notifikasi">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
                <span class="confirm-icon success-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg></span>
                <h2 id="success-title">Berhasil</h2>
                <p id="success-description">{{ session('success') }}</p>
            </form>
        </dialog>
    @endif
    @if ($errors->any())
        <dialog class="confirm-dialog validation-dialog" id="validation-dialog" aria-labelledby="validation-title" aria-describedby="validation-description">
            <form method="dialog" class="confirm-card validation-card">
                <span class="confirm-icon validation-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5m0 4h.01"/><circle cx="12" cy="12" r="9"/></svg></span>
                <h2 id="validation-title">Periksa kembali data</h2>
                <p id="validation-description">Ada beberapa bagian yang perlu diperbaiki sebelum data dapat disimpan.</p>
                <ul class="validation-errors" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <div class="confirm-actions">
                    <button class="button button-primary" value="close">Mengerti</button>
                </div>
            </form>
        </dialog>
    @endif
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</body>
</html>
