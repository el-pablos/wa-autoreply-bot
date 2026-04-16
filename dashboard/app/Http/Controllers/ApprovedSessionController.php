<?php

namespace App\Http\Controllers;

use App\Models\ApprovedSession;

class ApprovedSessionController extends Controller
{
    public function index()
    {
        $activeSessions = ApprovedSession::query()
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->paginate(20, ['*'], 'active_page');

        $historySessions = ApprovedSession::query()
            ->where(function ($query) {
                $query->where('is_active', false)
                    ->orWhere('expires_at', '<=', now());
            })
            ->orderByDesc('approved_at')
            ->paginate(20, ['*'], 'history_page');

        return view('approved.index', compact('activeSessions', 'historySessions'));
    }

    public function revoke(int $id)
    {
        $session = ApprovedSession::find($id);

        if (!$session) {
            return redirect()->back()->with('error', 'Sesi tidak ditemukan.');
        }

        if (!$session->is_active || $session->expires_at?->lte(now())) {
            return redirect()->back()->with('error', 'Sesi sudah tidak aktif atau sudah kedaluwarsa.');
        }

        $session->update([
            'is_active' => false,
            'revoked_at' => now(),
        ]);

        return redirect()->back()->with('success', "Sesi {$session->phone_number} berhasil dicabut.");
    }
}
