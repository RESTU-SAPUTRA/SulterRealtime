<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->role === 'mahasiswa') {
                return redirect()->route('passenger.map');
            }

            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    
    public function showRegistrationForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->role === 'mahasiswa') {
                return redirect()->route('passenger.map');
            }

            return redirect()->route('admin.dashboard');
        }

        return view('auth.register');
    }

    
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'nim'      => ['required', 'string', 'max:25', 'unique:users,nim'],
            'email'    => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'nim.required'       => 'NIM wajib diisi.',
            'nim.unique'         => 'NIM ini sudah terdaftar di sistem.',
            'email.required'     => 'Alamat email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Alamat email ini sudah terdaftar.',
            'password.required'  => 'Kata sandi wajib diisi.',
            'password.min'       => 'Kata sandi minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = User::create([
            'name'     => trim($validated['name']),
            'nim'      => trim($validated['nim']),
            'email'    => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'role'     => 'mahasiswa',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('passenger.map')
            ->with('status', "Pendaftaran berhasil! Selamat datang di Shuttle Kampus, {$user->name}.");
    }

    
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $input = trim($credentials['email']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        
        $fieldType = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : 'nim';

        if (Auth::attempt([$fieldType => $input, 'password' => $password], $remember)) {
            $request->session()->regenerate();

            if (Auth::user()->role === 'mahasiswa') {
                return redirect()->route('passenger.map')->with('status', 'Selamat datang, ' . Auth::user()->name . '! Sesi Mahasiswa aktif.');
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()
            ->withErrors([
                'email' => 'Email/NIM atau kata sandi yang Anda masukkan tidak sesuai.',
            ])
            ->onlyInput('email');
    }

    
    public function loginMahasiswa(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_identifier' => ['required', 'string'],
            'student_password'   => ['required', 'string'],
        ], [
            'student_identifier.required' => 'NIM atau Email Mahasiswa wajib diisi.',
            'student_password.required'   => 'Kata sandi akun Mahasiswa wajib diisi.',
        ]);

        $identifier = trim($validated['student_identifier']);
        $password = $validated['student_password'];
        $remember = $request->boolean('student_remember');

        
        $fieldType = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'nim';

        
        if (Auth::attempt([$fieldType => $identifier, 'password' => $password], $remember)) {
            $request->session()->regenerate();

            return redirect()->route('passenger.map')->with('status', 'Halo ' . Auth::user()->name . ', Anda berhasil masuk sebagai Mahasiswa.');
        }

        
        if ($fieldType === 'nim') {
            if (Auth::attempt(['email' => $identifier . '@campus.ac.id', 'password' => $password], $remember)) {
                $request->session()->regenerate();

                return redirect()->route('passenger.map')->with('status', 'Halo ' . Auth::user()->name . ', Anda berhasil masuk sebagai Mahasiswa.');
            }
        }

        return back()
            ->withErrors([
                'student_identifier' => 'NIM/Email Mahasiswa atau kata sandi tidak cocok.',
            ])
            ->withInput($request->only('student_identifier'));
    }

    
    public function loginMahasiswaQuick(Request $request): RedirectResponse
    {
        $student = User::firstOrCreate(
            ['email' => 'mahasiswa@campus.ac.id'],
            [
                'name'     => 'Ahmad Mahasiswa (UNIPI)',
                'nim'      => '2024001',
                'password' => Hash::make('password123'),
                'role'     => 'mahasiswa',
            ]
        );

        Auth::login($student, true);
        $request->session()->regenerate();

        return redirect()->route('passenger.map')->with('status', 'Berhasil masuk langsung sebagai Mahasiswa (' . $student->name . ' - NIM: ' . ($student->nim ?? '2024001') . ').');
    }

    
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berhasil keluar dari sistem Shuttle Kampus.');
    }
}
