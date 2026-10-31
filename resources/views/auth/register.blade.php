@extends('layouts.master')

@section('title', 'Pendaftaran Akun Mahasiswa - Shuttle Kampus (Kelompok 5)')

@section('content')
    <div class="auth-wrapper">
        <div class="card auth-card register-card">
            <div class="auth-header">
                <div class="auth-icon">🎓</div>
                <h1 class="auth-title">Pendaftaran Akun Mahasiswa</h1>
                <p class="auth-subtitle">Universitas Insan Pembangunan Indonesia (UNIPI)</p>
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

                
                <form method="POST" action="{{ route('register.submit') }}" class="auth-form">
                    @csrf

                    <div class="form-group">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            class="form-input @error('name') is-invalid @enderror"
                            placeholder="Nama lengkap"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="nim" class="form-label">Nomor Induk Mahasiswa (NIM)</label>
                        <input
                            type="text"
                            id="nim"
                            name="nim"
                            value="{{ old('nim') }}"
                            class="form-input @error('nim') is-invalid @enderror"
                            placeholder="Contoh: 2024002"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Alamat Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input @error('email') is-invalid @enderror"
                            placeholder="email@campus.ac.id"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Kata Sandi</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input @error('password') is-invalid @enderror"
                            placeholder="Minimal 6 karakter"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-input"
                            placeholder="Ulangi kata sandi"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-student btn-block">
                        📝 Daftarkan Akun Mahasiswa
                    </button>
                </form>

                <div class="auth-footer-link">
                    Sudah memiliki akun? <a href="{{ route('login') }}">Masuk di sini</a>
                </div>
            </div>
        </div>
    </div>
@endsection
