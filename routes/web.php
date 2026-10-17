<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BoothController;
use App\Http\Controllers\Admin\CheckinController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PriceController;
use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SponsorshipController as AdminSponsorshipController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BoothFeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicMapController;
use App\Http\Controllers\RegistrationPaymentController;
use App\Http\Controllers\SponsorshipPublicController;
use App\Http\Controllers\StatusLookupController;
use App\Http\Controllers\UmkmRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/peta', PublicMapController::class)->name('map');
Route::get('/stan.json', BoothFeedController::class)->middleware('throttle:60,1')->name('booths.feed');

Route::middleware('throttle:register')->group(function () {
    Route::get('/daftar', [UmkmRegistrationController::class, 'create'])->name('register.create');
    Route::get('/daftar/pemohon', fn () => redirect()->route('register.create'));
    Route::post('/daftar/pemohon', [UmkmRegistrationController::class, 'storeApplicant'])->name('register.applicant');
    Route::get('/daftar/stan', [UmkmRegistrationController::class, 'booths'])->name('register.booths');
    Route::post('/daftar/stan', [UmkmRegistrationController::class, 'storeBooths'])->name('register.booths.store');
    Route::get('/daftar/konfirmasi', [UmkmRegistrationController::class, 'confirm'])->name('register.confirm');
    Route::post('/daftar/konfirmasi', [UmkmRegistrationController::class, 'store'])->name('register.store');
});

Route::middleware('signed')->group(function () {
    Route::get('/pendaftaran/{registration}/pembayaran', [RegistrationPaymentController::class, 'show'])->name('register.payment');
    Route::post('/pendaftaran/{registration}/bukti', [RegistrationPaymentController::class, 'storeProof'])
        ->middleware('throttle:upload')
        ->name('register.proof');
    Route::get('/pendaftaran/{registration}/pdf/{type}', [RegistrationPaymentController::class, 'pdf'])->name('register.pdf');
    Route::get('/sponsorship/{sponsorship}', [SponsorshipPublicController::class, 'status'])->name('sponsorship.status');
});

Route::get('/sponsorship', [SponsorshipPublicController::class, 'create'])->name('sponsorship.create');
Route::post('/sponsorship', [SponsorshipPublicController::class, 'store'])->middleware('throttle:register')->name('sponsorship.store');

Route::get('/cek', [StatusLookupController::class, 'create'])->name('status.create');
Route::post('/cek', [StatusLookupController::class, 'store'])->middleware('throttle:lookup')->name('status.store');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/masuk', [AuthController::class, 'create'])->name('login');
        Route::post('/masuk', [AuthController::class, 'store'])->middleware('throttle:login');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/keluar', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

        Route::middleware('permission:registrations.view')->group(function () {
            Route::get('/pendaftaran', [AdminRegistrationController::class, 'index'])->name('registrations.index');
            Route::get('/pendaftaran/{registration}', [AdminRegistrationController::class, 'show'])->name('registrations.show');
            Route::get('/pendaftaran/{registration}/logo', [RegistrationPaymentController::class, 'logo'])->name('registrations.logo');
            Route::get('/pendaftaran/{registration}/pdf/{type}', [RegistrationPaymentController::class, 'pdf'])->name('registrations.pdf');
        });

        Route::middleware('permission:registrations.manage')->group(function () {
            Route::post('/pendaftaran/{registration}/batal', [AdminRegistrationController::class, 'cancel'])->name('registrations.cancel');
        });

        Route::middleware('permission:payments.verify')->group(function () {
            Route::post('/pendaftaran/{registration}/terima', [AdminRegistrationController::class, 'approve'])->name('registrations.approve');
            Route::post('/pendaftaran/{registration}/tolak', [AdminRegistrationController::class, 'reject'])->name('registrations.reject');
            Route::get('/pendaftaran/{registration}/bukti-bayar', [RegistrationPaymentController::class, 'proof'])->name('registrations.proof');
        });

        Route::middleware('permission:sponsorships.manage')->group(function () {
            Route::get('/sponsorship', [AdminSponsorshipController::class, 'index'])->name('sponsorships.index');
            Route::get('/sponsorship/{sponsorship}', [AdminSponsorshipController::class, 'show'])->name('sponsorships.show');
            Route::post('/sponsorship/{sponsorship}/penawaran', [AdminSponsorshipController::class, 'offer'])->name('sponsorships.offer');
            Route::post('/sponsorship/{sponsorship}/status', [AdminSponsorshipController::class, 'status'])->name('sponsorships.status');
            Route::post('/sponsorship/{sponsorship}/setujui', [AdminSponsorshipController::class, 'approve'])->name('sponsorships.approve');
        });

        Route::middleware('permission:booths.manage')->group(function () {
            Route::get('/stan', [BoothController::class, 'index'])->name('booths.index');
            Route::post('/stan', [BoothController::class, 'store'])->name('booths.store');
            Route::patch('/stan/{booth}', [BoothController::class, 'update'])->name('booths.update');
            Route::delete('/stan/{booth}', [BoothController::class, 'destroy'])->name('booths.destroy');
            Route::get('/zona', [BoothController::class, 'zones'])->name('zones.index');
            Route::post('/zona', [BoothController::class, 'storeZone'])->name('zones.store');
            Route::patch('/zona/{zone}', [BoothController::class, 'updateZone'])->name('zones.update');
            Route::get('/peta', [BoothController::class, 'editor'])->name('map.edit');
            Route::post('/peta', [BoothController::class, 'saveLayout'])->name('map.update');
        });

        Route::middleware('permission:prices.manage')->group(function () {
            Route::get('/harga', [PriceController::class, 'index'])->name('prices.index');
            Route::post('/harga', [PriceController::class, 'store'])->name('prices.store');
            Route::patch('/harga/{price}', [PriceController::class, 'update'])->name('prices.update');
        });

        Route::middleware('permission:settings.manage')->group(function () {
            Route::get('/event', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('/event', [SettingController::class, 'update'])->name('settings.update');
        });

        Route::middleware('permission:users.manage')->group(function () {
            Route::get('/akun', [UserController::class, 'index'])->name('users.index');
            Route::post('/akun', [UserController::class, 'store'])->name('users.store');
            Route::patch('/akun/{user}', [UserController::class, 'update'])->name('users.update');
        });

        Route::middleware('permission:reports.export')->group(function () {
            Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/laporan/unduh', [ReportController::class, 'download'])->name('reports.download');
        });

        Route::middleware('permission:checkin.perform')->group(function () {
            Route::get('/checkin', [CheckinController::class, 'index'])->name('checkin.index');
            Route::post('/checkin', [CheckinController::class, 'store'])->name('checkin.store');
        });

        Route::get('/audit', [AuditController::class, 'index'])->middleware('permission:audit.view')->name('audit.index');
    });
});
