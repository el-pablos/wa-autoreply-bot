<?php

namespace App\Http\Controllers;

use App\Models\AiConversationHistory;
use App\Models\BotSetting;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

class AiController extends Controller
{
    private const SETTING_KEYS = [
        'ai_reply_enabled',
        'ai_model',
        'ai_system_prompt',
        'ai_max_context_messages',
        'ai_temperature',
        'ai_fallback_provider',
    ];

    public function index(Request $request)
    {
        $settings = BotSetting::query()
            ->whereIn('key', self::SETTING_KEYS)
            ->pluck('value', 'key');

        $historyQuery = AiConversationHistory::query()
            ->orderByDesc('created_at');

        if ($request->filled('phone_number')) {
            $historyQuery->where('phone_number', 'like', '%' . $request->string('phone_number') . '%');
        }

        $history = $historyQuery->paginate(30)->withQueryString();

        return view('ai.index', [
            'settings' => [
                'ai_reply_enabled' => (string) ($settings['ai_reply_enabled'] ?? 'false'),
                'ai_model' => (string) ($settings['ai_model'] ?? 'groq/llama-3.1-8b-instant'),
                'ai_system_prompt' => (string) ($settings['ai_system_prompt'] ?? 'Kamu adalah asisten WA bisnis yang ramah, singkat, dan to the point.'),
                'ai_max_context_messages' => (string) ($settings['ai_max_context_messages'] ?? '12'),
                'ai_temperature' => (string) ($settings['ai_temperature'] ?? '0.4'),
                'ai_fallback_provider' => (string) ($settings['ai_fallback_provider'] ?? 'none'),
            ],
            'history' => $history,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'ai_reply_enabled' => ['nullable', 'in:true,false'],
            'ai_model' => ['required', 'string', 'max:255'],
            'ai_system_prompt' => ['required', 'string', 'max:4000'],
            'ai_max_context_messages' => ['required', 'integer', 'min:1', 'max:50'],
            'ai_temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'ai_fallback_provider' => ['required', 'in:none,groq,openai'],
        ]);

        $oldValues = BotSetting::query()
            ->whereIn('key', self::SETTING_KEYS)
            ->pluck('value', 'key')
            ->toArray();

        $newValues = [
            'ai_reply_enabled' => $request->has('ai_reply_enabled') ? 'true' : 'false',
            'ai_model' => $data['ai_model'],
            'ai_system_prompt' => $data['ai_system_prompt'],
            'ai_max_context_messages' => (string) $data['ai_max_context_messages'],
            'ai_temperature' => (string) $data['ai_temperature'],
            'ai_fallback_provider' => $data['ai_fallback_provider'],
        ];

        foreach ($newValues as $key => $value) {
            BotSetting::setValue($key, $value);
        }

        AuditTrail::record(
            $request,
            'ai.settings.updated',
            ['type' => 'bot_settings', 'id' => null],
            $oldValues,
            $newValues
        );

        return redirect()->route('ai.index')->with('success', 'Konfigurasi AI berhasil disimpan.');
    }
}
