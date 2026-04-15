<?php
// app/Http/Controllers/SettingController.php

namespace App\Http\Controllers;

use App\Models\BotSetting;
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

        BotSetting::setValue('reply_message',      $request->reply_message);
        BotSetting::setValue('reply_delay_ms',     (string) $request->reply_delay_ms);
        BotSetting::setValue('auto_reply_enabled', $request->has('auto_reply_enabled') ? 'true' : 'false');
        BotSetting::setValue('ignore_groups',      $request->has('ignore_groups')      ? 'true' : 'false');

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan!');
    }
}
