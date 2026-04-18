<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query();

        if ($request->filled('actor')) {
            $query->where('actor', 'like', '%' . $request->string('actor') . '%');
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->string('action') . '%');
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', 'like', '%' . $request->string('target_type') . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query
            ->orderByDesc('created_at')
            ->paginate(40)
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
        ]);
    }
}
