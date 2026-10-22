<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Shuttle Kampus Real-Time Tracking - Kelompok 5')</title>

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
                    <li>
                        <a href="{{ route('passenger.map') }}"
                           class="nav-link {{ request()->routeIs('passenger.map') ? 'active' : '' }}">
                            📍 Peta Live
                        </a>
                    </li>
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
                </ul>

                @auth
                    <div class="navbar-user">
                        <div class="user-badge" title="{{ auth()->user()->email }}">
                            <span class="user-avatar">👤</span>
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
                        <a href="{{ route('login') }}"
                           class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
                            🔐 Login Operator
                        </a>
                    </div>
                @endauth
            </nav>
        </div>
    </header>

    <main class="main-container">
        @yield('content')
    </main>

    <footer class="footer">
        <p>&copy; {{ date('Y') }} Shuttle Kampus — Universitas Insan Pembangunan Indonesia (UNIPI)</p>
    </footer>

    @stack('scripts')
</body>
</html>
