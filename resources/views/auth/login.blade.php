<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - RSU PKU MUHAMMADIYAH DELANGGU</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-delanggu.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="login-page">
    <main class="login-layout">
        <section class="login-visual" aria-label="RSU PKU Muhammadiyah Delanggu">
            <img class="login-building" src="{{ asset('images/gedung-rsu-pku.png') }}" alt="Gedung RSU PKU Muhammadiyah Delanggu">
            <div class="login-visual-overlay"></div>
            <a class="login-visual-brand" href="{{ route('login') }}">
                <img src="{{ asset('images/logo-delanggu.png') }}" alt="">
                <span><strong>RSU PKU MUHAMMADIYAH</strong><small>DELANGGU</small></span>
            </a>
            <div class="login-visual-copy">
                <span class="login-visual-kicker">SISTEM INFORMASI AKREDITASI</span>
                <h1>Mutu pelayanan,<br>komitmen kita bersama.</h1>
                <p>Kelola elemen penilaian dan dokumen akreditasi rumah sakit dengan lebih mudah dan terstruktur.</p>
            </div>
            <span class="login-visual-caption">RSU PKU Muhammadiyah Delanggu</span>
        </section>

        <section class="login-side">
            <div class="login-card">
                <a class="login-mobile-brand" href="{{ route('login') }}">
                    <img src="{{ asset('images/logo-delanggu.png') }}" alt="Logo RSU PKU Muhammadiyah Delanggu">
                    <span><strong>RSU PKU</strong><small>MUHAMMADIYAH DELANGGU</small></span>
                </a>
                <div class="login-intro">
                    <span class="login-intro-kicker">SELAMAT DATANG</span>
                    <h2>Masuk ke akun</h2>
                    <p>Gunakan akun Anda untuk melanjutkan ke dashboard.</p>
                </div>
                @if ($errors->any())
                    <div class="alert alert-error login-error" role="alert">{{ $errors->first() }}</div>
                @endif
                <form action="{{ route('login.store') }}" method="POST" class="login-form">
                    @csrf
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Masukkan username" autocomplete="username" required autofocus>
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required>
                        <button class="password-toggle" type="button" aria-label="Tampilkan password" aria-pressed="false" aria-controls="password" data-password-toggle>
                            <svg class="password-eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.8"/></svg>
                            <svg class="password-eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c6.1 0 9.5 6 9.5 6a15 15 0 0 1-3.1 3.6M6.2 6.3C3.9 7.8 2.5 12 2.5 12s3.4 6 9.5 6a9.5 9.5 0 0 0 3-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </div>
                    <button type="submit" class="button button-primary button-wide">Masuk ke Dashboard <span>→</span></button>
                </form>
                <div class="login-security-note">
                    <span class="login-security-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3"/></svg></span>
                    <span><strong>Akses aman dan terbatas</strong><small>Gunakan akun yang diberikan oleh administrator rumah sakit.</small></span>
                </div>
                <div class="login-foot"><span class="status-dot"></span> Sistem Manajemen Akreditasi</div>
            </div>
        </section>
    </main>
    <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
