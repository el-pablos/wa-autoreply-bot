<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(25);

        return view('users.index', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::in(['owner', 'admin', 'viewer'])],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'password' => $data['password'],
            'two_factor_enabled' => false,
            'totp_secret' => null,
            'backup_codes' => null,
        ]);

        AuditTrail::record(
            $request,
            'users.created',
            $user,
            null,
            $user->only(['name', 'email', 'role', 'two_factor_enabled'])
        );

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['owner', 'admin', 'viewer'])],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        $old = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        if ($user->id === optional($request->user())->id && $data['role'] !== $user->role) {
            return back()->withErrors(['role' => 'Role akun aktif tidak boleh diubah oleh dirinya sendiri.'])->withInput();
        }

        $isDemotingOwner = $user->role === 'owner' && $data['role'] !== 'owner';
        if ($isDemotingOwner && $this->ownerCount() <= 1) {
            return back()->withErrors(['role' => 'Minimal harus ada 1 owner aktif di sistem.'])->withInput();
        }

        $user->name = $data['name'];
        $user->email = strtolower($data['email']);
        $user->role = $data['role'];

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        AuditTrail::record(
            $request,
            'users.updated',
            $user,
            $old,
            $user->fresh()?->only(['name', 'email', 'role'])
        );

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        $actor = $request->user();
        if (!$actor) {
            abort(403);
        }

        if ($actor->id === $user->id) {
            return back()->withErrors(['user' => 'Akun aktif tidak boleh menghapus dirinya sendiri.']);
        }

        if ($actor->role === 'admin' && $user->role === 'owner') {
            abort(403, 'Admin tidak boleh menghapus owner.');
        }

        if ($user->role === 'owner' && $this->ownerCount() <= 1) {
            return back()->withErrors(['user' => 'Owner terakhir tidak boleh dihapus.']);
        }

        $old = $user->only(['name', 'email', 'role']);
        $target = ['type' => $user::class, 'id' => $user->id];

        $user->delete();

        AuditTrail::record(
            $request,
            'users.deleted',
            $target,
            $old,
            null
        );

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }

    private function ownerCount(): int
    {
        return (int) User::query()->where('role', 'owner')->count();
    }
}
