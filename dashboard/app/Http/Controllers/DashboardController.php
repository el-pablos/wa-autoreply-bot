<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\AllowedNumber;
use App\Models\BotSetting;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_messages'   => MessageLog::count(),
            'total_replied'    => MessageLog::where('replied', true)->count(),
            'total_allowed'    => MessageLog::where('is_allowed', true)->count(),
            'total_numbers'    => AllowedNumber::count(),
            'active_numbers'   => AllowedNumber::active()->count(),
            'today_messages'   => MessageLog::whereDate('received_at', today())->count(),
            'bot_status'       => BotSetting::getValue('bot_status', 'offline'),
            'auto_reply'       => BotSetting::getValue('auto_reply_enabled', 'false'),
        ];

        // 7 hari terakhir (chart)
        $daily = MessageLog::selectRaw('DATE(received_at) as date, COUNT(*) as total')
            ->where('received_at', '>=', now()->subDays(6)->startOfDay())
            ->groupByRaw('DATE(received_at)')
            ->orderBy('date')
            ->get();

        // Top 5 nomor paling banyak kirim pesan
        $topNumbers = MessageLog::selectRaw('from_number, COUNT(*) as total')
            ->groupBy('from_number')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Pesan terbaru 10
        $recentLogs = MessageLog::orderByDesc('received_at')->limit(10)->get();

        return view('dashboard.index', compact('stats', 'daily', 'topNumbers', 'recentLogs'));
    }
}
