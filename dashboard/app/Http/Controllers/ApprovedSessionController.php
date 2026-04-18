<?php

namespace App\Http\Controllers;

use App\Models\ApprovedSession;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

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

    public function revoke(Request $request, int $id)
    {
        $session = ApprovedSession::find($id);

        if (!$session) {
            return redirect()->back()->with('error', 'Sesi tidak ditemukan.');
        }

        if (!$session->is_active || $session->expires_at?->lte(now())) {
            return redirect()->back()->with('error', 'Sesi sudah tidak aktif atau sudah kedaluwarsa.');
        }

        $old = $session->only(['is_active', 'revoked_at', 'phone_number', 'expires_at']);
        $session->update([
            'is_active' => false,
            'revoked_at' => now(),
        ]);

        AuditTrail::record(
            $request,
            'approved_session.revoked',
            $session,
            $old,
            $session->fresh()?->only(['is_active', 'revoked_at', 'phone_number', 'expires_at'])
        );

        return redirect()->back()->with('success', "Sesi {$session->phone_number} berhasil dicabut.");
    }
}
