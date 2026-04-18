<?php
// app/Http/Controllers/LogController.php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = MessageLog::query();

        if ($request->filled('level')) {
            switch ($request->level) {
                case 'info':
                    $query->where('is_allowed', true)->where('replied', true);
                    break;
                case 'warn':
                    $query->where('is_allowed', false);
                    break;
                case 'err':
                    $query->where('is_allowed', true)->where('replied', false);
                    break;
                default:
                    break;
            }
        }

        if ($request->filled('number')) {
            $query->where('from_number', 'like', "%{$request->number}%");
        }

        if ($request->filled('replied')) {
            $query->where('replied', $request->replied === 'yes' ? 1 : 0);
        }

        if ($request->filled('is_allowed')) {
            $query->where('is_allowed', $request->is_allowed === 'yes' ? 1 : 0);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('received_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('received_at', '<=', $request->date_to);
        }

        $logs = $query->orderByDesc('received_at')->paginate(50)->withQueryString();

        return view('logs.index', compact('logs'));
    }
}
