<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use Illuminate\Http\Request;

class ChatLiveController extends Controller
{
    public function index(Request $request)
    {
        $query = MessageLog::query();

        if ($request->filled('number')) {
            $query->where('from_number', 'like', '%' . $request->string('number') . '%');
        }

        if ($request->filled('replied')) {
            $query->where('replied', $request->input('replied') === 'yes');
        }

        if ($request->filled('is_allowed')) {
            $query->where('is_allowed', $request->input('is_allowed') === 'yes');
        }

        $messages = $query
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $latestId = MessageLog::query()->max('id');

        return view('chat-live.index', [
            'messages' => $messages,
            'latestId' => (int) ($latestId ?? 0),
        ]);
    }

    public function feed(Request $request)
    {
        $afterId = max(0, (int) $request->integer('after_id', 0));

        $rows = MessageLog::query()
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(100)
            ->get([
                'id',
                'from_number',
                'message_type',
                'message_text',
                'is_allowed',
                'replied',
                'reply_text',
                'received_at',
            ]);

        return response()->json([
            'count' => $rows->count(),
            'latest_id' => (int) ($rows->max('id') ?? $afterId),
            'messages' => $rows->map(fn (MessageLog $row): array => [
                'id' => $row->id,
                'from_number' => $row->from_number,
                'message_type' => $row->message_type,
                'message_text' => $row->message_text,
                'is_allowed' => (bool) $row->is_allowed,
                'replied' => (bool) $row->replied,
                'reply_text' => $row->reply_text,
                'received_at' => optional($row->received_at)->toIso8601String(),
            ])->values()->all(),
        ]);
    }
}
