<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Shuttle Kampus Real-Time Tracking - Kelompok 5')</title>

    
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    
    @stack('styles')
</head>
<body>
    
    <header class="navbar">
        <div class="navbar-container">
            <a href="{{ route('passenger.map') }}" class="navbar-brand">
                <div class="brand-icon">🚌</div>
                <div>
                    <span class="brand-title">Shuttle Kampus — UNIPI</span>
                    <span class="brand-subtitle">Universitas Insan Pembangunan Indonesia</span>
                </div>
            </a>

            <nav class="navbar-actions">
                <ul class="navbar-nav">
                    @auth
                        <li>
                            <a href="{{ route('passenger.map') }}"
                               class="nav-link {{ request()->routeIs('passenger.map') ? 'active' : '' }}">
                                📍 Peta Live
                            </a>
                        </li>
                        @if(in_array(auth()->user()->role, ['admin', 'operator']))
                            <li>
                                <a href="{{ route('admin.dashboard') }}"
                                   class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                    🛠️ Admin
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.audit') }}"
                                   class="nav-link {{ request()->routeIs('admin.audit') || request()->routeIs('dashboard.audit') ? 'active' : '' }}">
                                    📋 Audit Trail
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>

                @auth
                    <div class="navbar-user">
                        <div class="user-badge" title="{{ auth()->user()->email }}">
                            <span class="user-avatar">{{ auth()->user()->role === 'mahasiswa' ? '🎓' : '👤' }}</span>
                            <div class="user-info">
                                <span class="user-name">{{ auth()->user()->name }}</span>
                                <span class="user-role">{{ strtoupper(auth()->user()->role ?? 'operator') }}</span>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="logout-form">
                            @csrf
                            <button type="submit" class="btn-logout">
                                🚪 Logout
                            </button>
                        </form>
                    </div>
                @else
                    <div class="navbar-user">
                        @if(request()->routeIs('login'))
                            <a href="{{ route('register') }}"
                               class="nav-link">
                                📝 Daftar Akun Baru
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="nav-link">
                                🔐 Masuk
                            </a>
                        @endif
                    </div>
                @endauth
            </nav>
        </div>
    </header>

    
    <main class="main-container">
        @if (!request()->routeIs('login') && !request()->routeIs('register'))
            @if (session('status'))
                <div class="alert-info alert-flash">
                    ℹ️ {{ session('status') }}
                </div>
            @endif
            @if (session('warning'))
                <div class="alert-warning alert-flash">
                    ⚠️ {{ session('warning') }}
                </div>
            @endif
        @endif
        @yield('content')
    </main>

    
    <footer class="footer">
        <p>&copy; {{ date('Y') }} Shuttle Kampus — Universitas Insan Pembangunan Indonesia (UNIPI)</p>
    </footer>

    
    @stack('scripts')
</body>
</html>
