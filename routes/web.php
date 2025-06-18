<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\Neo\NeoController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Siade\DataMahasiswaController;
use App\Http\Controllers\Siade\KrsController;
use App\Http\Controllers\Siade\LaporanNilaiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::resource('menus', MenuController::class)->except('show')->middleware('CheckPermission');
    Route::get('menus/json', [MenuController::class, 'getMenuJson'])->name('menus.json')->middleware('CheckPermission');
    Route::get('menus/sort', [MenuController::class, 'menuSort'])->name('menus.sort')->middleware('CheckPermission');
    Route::post('menus/update-order', [MenuController::class, 'updateOrder'])->name('menus.updateOrder')->middleware('CheckPermission');
    Route::resource('permissions', PermissionController::class)->except('show', 'edit', 'update')->middleware('CheckPermission');
    Route::resource('roles', RoleController::class)->except('show')->middleware('CheckPermission');
    Route::resource('users', UserController::class)->except('show')->middleware('CheckPermission');

    Route::prefix('siade')->middleware('CheckPermission')->group(function () {
        Route::resource('laporan-nilai', LaporanNilaiController::class);
        Route::get('/krs-mahasiswa', [KrsController::class, 'KrsMahasiswa'])->name('siade.krsmahasiswa');
        Route::get('/data-mahasiswa', [DataMahasiswaController::class, 'data_mahasiswa'])->name('siade.datamahasiswa');
    });

    Route::prefix('neo-feeder')->middleware(['CheckPermission', 'CheckIP'])->group(function () {
        Route::get('/get-mahasiswa', [NeoController::class, 'getMahasiswa'])->name('neofeeder.getmahasiswa');
        Route::get('/export-krs', [NeoController::class, 'export_krs'])->name('neofeeder.exportkrs');
        Route::get('/get-kelas-perkuliahan', [NeoController::class, 'get_kelas_perkuliahan'])->name('neofeeder.getkelasperkuliahan');
    });
    Route::post('/kirim-pesan', [WhatsAppController::class, 'kirimPesan'])->name('kirim-pesan');
});
