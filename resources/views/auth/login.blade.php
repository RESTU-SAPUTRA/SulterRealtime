@extends('layouts.master')

@section('title', 'Login Operator & Admin - Shuttle Kampus (Kelompok 5)')

@section('content')
    <div class="auth-wrapper">
        <div class="card auth-card">
            <div class="auth-header">
                <div class="auth-icon">🔐</div>
                <h1 class="auth-title">Login Operator & Admin</h1>
                <p class="auth-subtitle">Masuk untuk mengakses Dashboard Operasional & Audit Trail</p>
            </div>

            <div class="card-body">
                @if (session('status'))
                    <div class="alert-info">
                        ℹ️ {{ session('status') }}
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
                        <label for="email" class="form-label">Alamat Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input @error('email') is-invalid @enderror"
                            placeholder="Masukkan email operator"
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
                            placeholder="••••••••"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Masuk ke Dashboard
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
