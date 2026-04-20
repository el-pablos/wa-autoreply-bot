<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AllowListController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BusinessHoursController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ChatLiveController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ApprovedSessionController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/',          [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.home');

    // Allow-list CRUD
    Route::get('/allowlist',                      [AllowListController::class, 'index'])->name('allowlist.index');
    Route::get('/allowlist/create',               [AllowListController::class, 'create'])->name('allowlist.create');
    Route::post('/allowlist',                     [AllowListController::class, 'store'])->name('allowlist.store');
    Route::get('/allowlist/{allowlist}/edit',     [AllowListController::class, 'edit'])->name('allowlist.edit');
    Route::put('/allowlist/{allowlist}',          [AllowListController::class, 'update'])->name('allowlist.update');
    Route::delete('/allowlist/{allowlist}',       [AllowListController::class, 'destroy'])->name('allowlist.destroy');
    Route::patch('/allowlist/{allowlist}/toggle', [AllowListController::class, 'toggleActive'])->name('allowlist.toggle');

    // Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    // Chat live
    Route::get('/chat-live',      [ChatLiveController::class, 'index'])->name('chat-live.index');
    Route::get('/chat-live/feed', [ChatLiveController::class, 'feed'])->name('chat-live.feed');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Alerts
    Route::get('/alerts',                             [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/channels',                   [AlertController::class, 'storeChannel'])->name('alerts.channels.store');
    Route::put('/alerts/channels/{channel}',          [AlertController::class, 'updateChannel'])->name('alerts.channels.update');
    Route::patch('/alerts/channels/{channel}/toggle', [AlertController::class, 'toggleChannel'])->name('alerts.channels.toggle');
    Route::delete('/alerts/channels/{channel}',       [AlertController::class, 'destroyChannel'])->name('alerts.channels.destroy');
    Route::post('/alerts/channels/{channel}/test',    [AlertController::class, 'sendTest'])->name('alerts.channels.test');

    // Blacklist
    Route::get('/blacklist',                      [BlacklistController::class, 'index'])->name('blacklist.index');
    Route::post('/blacklist',                     [BlacklistController::class, 'store'])->name('blacklist.store');
    Route::put('/blacklist/{blacklist}',          [BlacklistController::class, 'update'])->name('blacklist.update');
    Route::patch('/blacklist/{blacklist}/toggle', [BlacklistController::class, 'toggle'])->name('blacklist.toggle');
    Route::delete('/blacklist/{blacklist}',       [BlacklistController::class, 'destroy'])->name('blacklist.destroy');

    // Audit
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // Templates
    Route::get('/templates',                                 [TemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates/reply',                          [TemplateController::class, 'storeReplyTemplate'])->name('templates.reply.store');
    Route::put('/templates/reply/{replyTemplate}',           [TemplateController::class, 'updateReplyTemplate'])->name('templates.reply.update');
    Route::delete('/templates/reply/{replyTemplate}',        [TemplateController::class, 'destroyReplyTemplate'])->name('templates.reply.destroy');
    Route::post('/templates/reply/{replyTemplate}/default',  [TemplateController::class, 'setDefaultReplyTemplate'])->name('templates.reply.default');
    Route::post('/templates/type',                           [TemplateController::class, 'upsertMessageTypeTemplate'])->name('templates.type.upsert');
    Route::patch('/templates/type/{messageType}/toggle',     [TemplateController::class, 'toggleMessageTypeTemplate'])->name('templates.type.toggle');

    // Settings
    Route::get('/settings',         [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings',        [SettingController::class, 'update'])->name('settings.update');
    Route::get('/business-hours',   [BusinessHoursController::class, 'index'])->name('business-hours.index');
    Route::post('/business-hours',  [BusinessHoursController::class, 'update'])->name('business-hours.update');

    // Approved sessions
    Route::get('/approved-sessions',              [ApprovedSessionController::class, 'index'])->name('approved.index');
    Route::post('/approved-sessions/{id}/revoke', [ApprovedSessionController::class, 'revoke'])->name('approved.revoke');
});
