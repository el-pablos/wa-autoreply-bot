<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AllowListController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BusinessHoursController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\ApprovedSessionController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login',        [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',       [AuthController::class, 'login'])->name('login.post');
Route::get('/two-factor/challenge', [TwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
Route::post('/two-factor/challenge', [TwoFactorController::class, 'verifyChallenge'])->name('two-factor.verify');
Route::post('/logout',      [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/',                                    [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard',                           [DashboardController::class, 'index'])->name('dashboard.home');

    // Allow-list CRUD
    Route::get('/allowlist',                           [AllowListController::class, 'index'])->name('allowlist.index');
    Route::get('/allowlist/create',                    [AllowListController::class, 'create'])->name('allowlist.create');
    Route::post('/allowlist',                          [AllowListController::class, 'store'])->middleware('role:owner,admin')->name('allowlist.store');
    Route::get('/allowlist/{allowlist}/edit',          [AllowListController::class, 'edit'])->name('allowlist.edit');
    Route::put('/allowlist/{allowlist}',               [AllowListController::class, 'update'])->middleware('role:owner,admin')->name('allowlist.update');
    Route::delete('/allowlist/{allowlist}',            [AllowListController::class, 'destroy'])->middleware('role:owner,admin')->name('allowlist.destroy');
    Route::patch('/allowlist/{allowlist}/toggle',      [AllowListController::class, 'toggleActive'])->middleware('role:owner,admin')->name('allowlist.toggle');

    // Logs
    Route::get('/logs',                                [LogController::class, 'index'])->name('logs.index');

    // Templates (A1 + A3)
    Route::get('/templates',                           [TemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates/reply',                    [TemplateController::class, 'storeReplyTemplate'])->middleware('role:owner,admin')->name('templates.reply.store');
    Route::put('/templates/reply/{replyTemplate}',     [TemplateController::class, 'updateReplyTemplate'])->middleware('role:owner,admin')->name('templates.reply.update');
    Route::delete('/templates/reply/{replyTemplate}',  [TemplateController::class, 'destroyReplyTemplate'])->middleware('role:owner,admin')->name('templates.reply.destroy');
    Route::post('/templates/reply/{replyTemplate}/default', [TemplateController::class, 'setDefaultReplyTemplate'])->middleware('role:owner,admin')->name('templates.reply.default');
    Route::post('/templates/type',                     [TemplateController::class, 'upsertMessageTypeTemplate'])->middleware('role:owner,admin')->name('templates.type.upsert');
    Route::patch('/templates/type/{messageType}/toggle', [TemplateController::class, 'toggleMessageTypeTemplate'])->middleware('role:owner,admin')->name('templates.type.toggle');

    // Knowledge base (D1)
    Route::get('/knowledge',                           [KnowledgeController::class, 'index'])->name('knowledge.index');
    Route::post('/knowledge',                          [KnowledgeController::class, 'store'])->middleware('role:owner,admin')->name('knowledge.store');
    Route::put('/knowledge/{knowledgeBase}',           [KnowledgeController::class, 'update'])->middleware('role:owner,admin')->name('knowledge.update');
    Route::patch('/knowledge/{knowledgeBase}/toggle',  [KnowledgeController::class, 'toggle'])->middleware('role:owner,admin')->name('knowledge.toggle');
    Route::delete('/knowledge/{knowledgeBase}',        [KnowledgeController::class, 'destroy'])->middleware('role:owner,admin')->name('knowledge.destroy');

    // Webhooks & API keys (D3)
    Route::get('/webhooks',                            [WebhookController::class, 'index'])->name('webhooks.index');
    Route::post('/webhooks/endpoints',                 [WebhookController::class, 'storeEndpoint'])->middleware('role:owner,admin')->name('webhooks.endpoints.store');
    Route::put('/webhooks/endpoints/{endpoint}',       [WebhookController::class, 'updateEndpoint'])->middleware('role:owner,admin')->name('webhooks.endpoints.update');
    Route::patch('/webhooks/endpoints/{endpoint}/toggle', [WebhookController::class, 'toggleEndpoint'])->middleware('role:owner,admin')->name('webhooks.endpoints.toggle');
    Route::delete('/webhooks/endpoints/{endpoint}',    [WebhookController::class, 'destroyEndpoint'])->middleware('role:owner,admin')->name('webhooks.endpoints.destroy');
    Route::post('/webhooks/api-keys',                  [WebhookController::class, 'storeApiKey'])->middleware('role:owner,admin')->name('webhooks.api-keys.store');
    Route::patch('/webhooks/api-keys/{apiKey}/revoke', [WebhookController::class, 'revokeApiKey'])->middleware('role:owner,admin')->name('webhooks.api-keys.revoke');

    // Settings
    Route::get('/settings',                            [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings',                           [SettingController::class, 'update'])->middleware('role:owner,admin')->name('settings.update');
    Route::get('/business-hours',                      [BusinessHoursController::class, 'index'])->name('business-hours.index');
    Route::post('/business-hours',                     [BusinessHoursController::class, 'update'])->middleware('role:owner,admin')->name('business-hours.update');
    Route::get('/settings/2fa',                        [TwoFactorController::class, 'index'])->name('settings.2fa.index');
    Route::post('/settings/2fa/setup',                 [TwoFactorController::class, 'setup'])->name('settings.2fa.setup');
    Route::post('/settings/2fa/enable',                [TwoFactorController::class, 'enable'])->name('settings.2fa.enable');
    Route::post('/settings/2fa/disable',               [TwoFactorController::class, 'disable'])->name('settings.2fa.disable');

    // Approved sessions
    Route::get('/approved-sessions',                   [ApprovedSessionController::class, 'index'])->name('approved.index');
    Route::post('/approved-sessions/{id}/revoke',      [ApprovedSessionController::class, 'revoke'])->middleware('role:owner,admin')->name('approved.revoke');
});
