<?php

namespace App\Http\Controllers;

use App\Models\AlertChannel;
use App\Models\AlertHistory;
use App\Support\AuditTrail;
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
            'type' => ['required', 'in:wa,email'],
            'target' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'in:true,false'],
        ]);

        $channel = AlertChannel::query()->create([
            'type' => $data['type'],
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
            'type' => ['required', 'in:wa,email'],
            'target' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'in:true,false'],
        ]);

        $old = $channel->only(['type', 'target', 'is_active']);

        $channel->update([
            'type' => $data['type'],
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
            'message' => 'Test alert dari dashboard pada ' . now()->format('Y-m-d H:i:s'),
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
}
