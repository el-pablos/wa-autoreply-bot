<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

class BlacklistController extends Controller
{
    public function index(Request $request)
    {
        $query = Blacklist::query();

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('phone_number', 'like', '%' . $search . '%')
                    ->orWhere('reason', 'like', '%' . $search . '%')
                    ->orWhere('blocked_by', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $rows = $query
            ->orderByDesc('is_active')
            ->orderByDesc('blocked_at')
            ->paginate(25)
            ->withQueryString();

        return view('blacklist.index', [
            'rows' => $rows,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:64'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'unblock_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'in:true,false'],
        ]);

        $normalized = $this->normalizePhone($data['phone_number']);

        if ($normalized === null) {
            return back()->withErrors(['phone_number' => 'Format nomor tidak valid.'])->withInput();
        }

        $exists = Blacklist::query()->where('phone_number', $normalized)->exists();
        if ($exists) {
            return back()->withErrors(['phone_number' => 'Nomor sudah ada di blacklist.'])->withInput();
        }

        $entry = Blacklist::query()->create([
            'phone_number' => $normalized,
            'reason' => $data['reason'] ?? null,
            'blocked_at' => now(),
            'unblock_at' => $data['unblock_at'] ?? null,
            'blocked_by' => optional($request->user())->email,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditTrail::record(
            $request,
            'blacklist.created',
            $entry,
            null,
            $entry->only(['phone_number', 'reason', 'unblock_at', 'is_active'])
        );

        return redirect()->route('blacklist.index')->with('success', 'Nomor berhasil ditambahkan ke blacklist.');
    }

    public function update(Request $request, Blacklist $blacklist)
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:64'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'unblock_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'in:true,false'],
        ]);

        $normalized = $this->normalizePhone($data['phone_number']);
        if ($normalized === null) {
            return back()->withErrors(['phone_number' => 'Format nomor tidak valid.'])->withInput();
        }

        $duplicate = Blacklist::query()
            ->where('phone_number', $normalized)
            ->where('id', '!=', $blacklist->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['phone_number' => 'Nomor sudah digunakan entry lain.'])->withInput();
        }

        $old = $blacklist->only(['phone_number', 'reason', 'unblock_at', 'is_active']);

        $blacklist->update([
            'phone_number' => $normalized,
            'reason' => $data['reason'] ?? null,
            'unblock_at' => $data['unblock_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditTrail::record(
            $request,
            'blacklist.updated',
            $blacklist,
            $old,
            $blacklist->fresh()?->only(['phone_number', 'reason', 'unblock_at', 'is_active'])
        );

        return redirect()->route('blacklist.index')->with('success', 'Entry blacklist berhasil diperbarui.');
    }

    public function toggle(Request $request, Blacklist $blacklist)
    {
        $old = ['is_active' => $blacklist->is_active];

        $blacklist->update([
            'is_active' => !$blacklist->is_active,
        ]);

        AuditTrail::record(
            $request,
            'blacklist.toggled',
            $blacklist,
            $old,
            ['is_active' => $blacklist->is_active]
        );

        return redirect()->route('blacklist.index')->with('success', 'Status blacklist berhasil diubah.');
    }

    public function destroy(Request $request, Blacklist $blacklist)
    {
        $old = $blacklist->only(['phone_number', 'reason', 'unblock_at', 'is_active']);
        $target = ['type' => $blacklist::class, 'id' => $blacklist->id];

        $blacklist->delete();

        AuditTrail::record(
            $request,
            'blacklist.deleted',
            $target,
            $old,
            null
        );

        return redirect()->route('blacklist.index')->with('success', 'Entry blacklist berhasil dihapus.');
    }

    private function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        if (!str_starts_with($digits, '62')) {
            return null;
        }

        if (strlen($digits) < 10 || strlen($digits) > 16) {
            return null;
        }

        return $digits;
    }
}
