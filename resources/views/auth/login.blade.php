@extends('layouts.master')

@section('title', 'Portal Masuk - Shuttle Kampus (Kelompok 5)')

@section('content')
    <div class="auth-wrapper">
        <div class="card auth-card">
            <div class="auth-header">
                <div class="auth-icon">🚌</div>
                <h1 class="auth-title">Portal Masuk Shuttle Kampus</h1>
                <p class="auth-subtitle">Universitas Insan Pembangunan Indonesia (UNIPI)</p>
            </div>

            <div class="card-body">
                @if (session('status'))
                    <div class="alert-info">
                        ℹ️ {{ session('status') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert-warning">
                        ⚠️ {{ session('warning') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert-error">
                        ⚠️ {{ $errors->first() }}
                    </div>
                @endif

                
                <form method="POST" action="{{ route('login.submit') }}" class="auth-form">
                    @csrf

                    <div class="form-group">
                        <label for="email" class="form-label">NIM atau Alamat Email</label>
                        <input
                            type="text"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input @error('email') is-invalid @enderror"
                            placeholder="NIM atau Email Kampus"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Kata Sandi</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input @error('password') is-invalid @enderror"
                            placeholder="Masukkan kata sandi"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        🔐 Masuk ke Sistem
                    </button>
                </form>

                
                <div class="auth-footer-link">
                    Belum memiliki akun? <a href="{{ route('register') }}">Daftar Akun Baru</a>
                </div>
            </div>
        </div>
    </div>
@endsection
