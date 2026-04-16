<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AllowListController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ApprovedSessionController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login',        [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',       [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',      [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('simple.auth')->group(function () {
    Route::get('/',                                    [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard',                           [DashboardController::class, 'index'])->name('dashboard.home');

    // Allow-list CRUD
    Route::get('/allowlist',                           [AllowListController::class, 'index'])->name('allowlist.index');
    Route::get('/allowlist/create',                    [AllowListController::class, 'create'])->name('allowlist.create');
    Route::post('/allowlist',                          [AllowListController::class, 'store'])->name('allowlist.store');
    Route::get('/allowlist/{allowlist}/edit',          [AllowListController::class, 'edit'])->name('allowlist.edit');
    Route::put('/allowlist/{allowlist}',               [AllowListController::class, 'update'])->name('allowlist.update');
    Route::delete('/allowlist/{allowlist}',            [AllowListController::class, 'destroy'])->name('allowlist.destroy');
    Route::patch('/allowlist/{allowlist}/toggle',      [AllowListController::class, 'toggleActive'])->name('allowlist.toggle');

    // Logs
    Route::get('/logs',                                [LogController::class, 'index'])->name('logs.index');

    // Settings
    Route::get('/settings',                            [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings',                           [SettingController::class, 'update'])->name('settings.update');

    // Approved sessions
    Route::get('/approved-sessions',                   [ApprovedSessionController::class, 'index'])->name('approved.index');
    Route::post('/approved-sessions/{id}/revoke',      [ApprovedSessionController::class, 'revoke'])->name('approved.revoke');
});
