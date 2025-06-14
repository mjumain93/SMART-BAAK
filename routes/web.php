<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\Neo\NeoController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Siade\DataMahasiswaController;
use App\Http\Controllers\Siade\KrsController;
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

Route::get('/whatsapp', [WhatsAppController::class, 'index']);
Route::post('/whatsapp/send-text', [WhatsAppController::class, 'sendText']);
Route::post('/whatsapp/send-media', [WhatsAppController::class, 'sendMedia']);
Route::post('/whatsapp/send-location', [WhatsAppController::class, 'sendLocation']);
Route::post('/whatsapp/send-contact', [WhatsAppController::class, 'sendContact']);
Route::post('/whatsapp/send-mention', [WhatsAppController::class, 'sendMention']);
Route::post('/whatsapp/send-buttons', [WhatsAppController::class, 'sendButtons']);
Route::post('/whatsapp/send-list', [WhatsAppController::class, 'sendList']);
Route::post('/whatsapp/send-group-message', [WhatsAppController::class, 'sendGroupMessage']);
Route::post('/whatsapp/blast-messages', [WhatsAppController::class, 'blastMessages']);

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/login/callback', [AuthController::class, 'attemptLogin'])->name('login.callback');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::resource('menus', MenuController::class)->except('show')->middleware('CheckPermission');
    Route::get('menus/json', [MenuController::class, 'getMenuJson'])->name('menus.json')->middleware('CheckPermission');
    Route::get('menus/sort', [MenuController::class, 'menuSort'])->name('menus.sort')->middleware('CheckPermission');
    Route::post('menus/update-order', [MenuController::class, 'updateOrder'])->name('menus.updateOrder')->middleware('CheckPermission');
    Route::resource('permissions', PermissionController::class)->except('show', 'edit', 'update')->middleware('CheckPermission');
    Route::resource('roles', RoleController::class)->except('show')->middleware('CheckPermission');

    Route::prefix('siade')->middleware('CheckPermission')->group(function () {
        Route::get('/laporan-input-nilai-semester', [KrsController::class, 'LaporanNilaiSemester'])->name('siade.laporaninputnilaisemester');
        Route::get('/detail-nilai-mahasiswa/{id}', [KrsController::class, 'detailNilai'])->name('siade.detailnilaimahasiswa');
        Route::post('/update-nilai', [KrsController::class, 'updateNilai'])->name('siade.updatenilai');
        Route::get('/krs-mahasiswa', [KrsController::class, 'KrsMahasiswa'])->name('siade.krsmahasiswa');
        Route::get('/data-mahasiswa', [DataMahasiswaController::class, 'data_mahasiswa'])->name('siade.datamahasiswa');
    });

    Route::prefix('neo-feeder')->middleware(['CheckPermission', 'CheckIP'])->group(function () {
        Route::get('/get-mahasiswa', [NeoController::class, 'getMahasiswa'])->name('neofeeder.getmahasiswa');
        Route::get('/export-krs', [NeoController::class, 'export_krs'])->name('neofeeder.exportkrs');
        Route::get('/get-kelas-perkuliahan', [NeoController::class, 'get_kelas_perkuliahan'])->name('neofeeder.getkelasperkuliahan');
    });
    Route::post('/kirim-pesan', [KrsController::class, 'kirimPesan'])->name('kirim-pesan');
});
