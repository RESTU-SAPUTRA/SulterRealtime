<?php

use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\PassengerController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/login/mahasiswa', [AuthController::class, 'loginMahasiswa'])->name('login.mahasiswa');
    Route::post('/login/mahasiswa/quick', [AuthController::class, 'loginMahasiswaQuick'])->name('login.mahasiswa.quick');
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    
    Route::get('/', [PassengerController::class, 'index'])->name('passenger.map');

    
    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::post('/shuttles', [AdminController::class, 'storeShuttle'])->name('admin.shuttles.store');
        Route::put('/shuttles/{shuttle}', [AdminController::class, 'updateShuttle'])->name('admin.shuttles.update');
        Route::patch('/shuttles/{shuttle}/status', [AdminController::class, 'updateStatus'])->name('admin.shuttles.status');
        Route::delete('/shuttles/{shuttle}', [AdminController::class, 'destroyShuttle'])->name('admin.shuttles.destroy');
        Route::post('/stops', [AdminController::class, 'storeStop'])->name('admin.stops.store');
        Route::put('/stops/{stop}', [AdminController::class, 'updateStop'])->name('admin.stops.update');
        Route::delete('/stops/{stop}', [AdminController::class, 'destroyStop'])->name('admin.stops.destroy');
        Route::get('/audit-trail', [AdminController::class, 'auditLogs'])->name('admin.audit');
    });

    
    Route::get('/audit-trail', [AdminController::class, 'auditLogs'])->name('dashboard.audit');
});
