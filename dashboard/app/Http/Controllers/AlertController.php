<?php

namespace App\Http\Controllers;

use App\Models\AlertChannel;
use App\Models\AlertHistory;
use App\Models\MessageLog;
use App\Support\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index()
    {
        $channels = AlertChannel::query()
            ->orderByDesc('is_active')
            ->orderBy('type')
            ->orderBy('target')
            ->get();

        $history = AlertHistory::query()
            ->with('channel')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('alerts.index', [
            'channels' => $channels,
            'history' => $history,
        ]);
    }

    public function storeChannel(Request $request)
    {
        $data = $request->validate([
            'target' => ['required', 'email:rfc', 'max:255'],
            'is_active' => ['nullable', 'in:true,false'],
        ]);

        $channel = AlertChannel::query()->create([
            'type' => 'email',
            'target' => $data['target'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditTrail::record(
            $request,
            'alerts.channel.created',
            $channel,
            null,
            $channel->only(['type', 'target', 'is_active'])
        );

        return redirect()->route('alerts.index')->with('success', 'Alert channel berhasil ditambahkan.');
    }

    public function updateChannel(Request $request, AlertChannel $channel)
    {
        $data = $request->validate([
            'target' => ['required', 'email:rfc', 'max:255'],
            'is_active' => ['nullable', 'in:true,false'],
        ]);

        $old = $channel->only(['type', 'target', 'is_active']);

        $channel->update([
            'type' => 'email',
            'target' => $data['target'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditTrail::record(
            $request,
            'alerts.channel.updated',
            $channel,
            $old,
            $channel->fresh()?->only(['type', 'target', 'is_active'])
        );

        return redirect()->route('alerts.index')->with('success', 'Alert channel berhasil diperbarui.');
    }

    public function toggleChannel(Request $request, AlertChannel $channel)
    {
        $old = ['is_active' => $channel->is_active];

        $channel->update([
            'is_active' => !$channel->is_active,
        ]);

        AuditTrail::record(
            $request,
            'alerts.channel.toggled',
            $channel,
            $old,
            ['is_active' => $channel->is_active]
        );

        return redirect()->route('alerts.index')->with('success', 'Status alert channel berhasil diubah.');
    }

    public function destroyChannel(Request $request, AlertChannel $channel)
    {
        $old = $channel->only(['type', 'target', 'is_active']);
        $target = ['type' => $channel::class, 'id' => $channel->id];

        $channel->delete();

        AuditTrail::record(
            $request,
            'alerts.channel.deleted',
            $target,
            $old,
            null
        );

        return redirect()->route('alerts.index')->with('success', 'Alert channel berhasil dihapus.');
    }

    public function sendTest(Request $request, AlertChannel $channel)
    {
        $history = AlertHistory::query()->create([
            'channel_id' => $channel->id,
            'severity' => 'info',
            'message' => 'Test laporan email dari dashboard pada ' . now()->format('Y-m-d H:i:s'),
            'delivered_at' => now(),
            'success' => true,
        ]);

        $channel->update([
            'last_alert_at' => now(),
        ]);

        AuditTrail::record(
            $request,
            'alerts.channel.tested',
            $channel,
            null,
            [
                'history_id' => $history->id,
                'severity' => $history->severity,
                'success' => $history->success,
            ]
        );

        return redirect()->route('alerts.index')->with('success', 'Test alert berhasil dikirim (simulasi).');
    }

    public function reportData(): JsonResponse
    {
        $totalMessages = MessageLog::count();
        $todayMessages = MessageLog::whereDate('created_at', today())->count();
        $activeChannels = AlertChannel::where('is_active', true)->count();

        $recentHistory = AlertHistory::with('channel')
            ->latest()
            ->take(5)
            ->get(['id', 'channel_id', 'severity', 'message', 'success', 'created_at']);

        return response()->json([
            'total_messages' => $totalMessages,
            'today_messages' => $todayMessages,
            'active_channels' => $activeChannels,
            'recent_alerts' => $recentHistory,
            'generated_at' => now()->toISOString(),
        ]);
    }
}
