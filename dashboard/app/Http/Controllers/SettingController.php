<?php
// app/Http/Controllers/SettingController.php

namespace App\Http\Controllers;

use App\Models\BotSetting;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = BotSetting::all()->keyBy('key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'reply_message'        => 'required|string|max:1000',
            'reply_delay_ms'       => 'required|integer|min:0|max:10000',
            'auto_reply_enabled'   => 'in:true,false',
            'ignore_groups'        => 'in:true,false',
        ]);

        $keys = ['reply_message', 'reply_delay_ms', 'auto_reply_enabled', 'ignore_groups'];
        $oldValues = BotSetting::query()
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->toArray();

        $newValues = [
            'reply_message' => (string) $request->reply_message,
            'reply_delay_ms' => (string) $request->reply_delay_ms,
            'auto_reply_enabled' => $request->has('auto_reply_enabled') ? 'true' : 'false',
            'ignore_groups' => $request->has('ignore_groups') ? 'true' : 'false',
        ];

        BotSetting::setValue('reply_message', $newValues['reply_message']);
        BotSetting::setValue('reply_delay_ms', $newValues['reply_delay_ms']);
        BotSetting::setValue('auto_reply_enabled', $newValues['auto_reply_enabled']);
        BotSetting::setValue('ignore_groups', $newValues['ignore_groups']);

        AuditTrail::record(
            $request,
            'settings.updated',
            ['type' => 'bot_settings', 'id' => null],
            $oldValues,
            $newValues
        );

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan!');
    }
}
