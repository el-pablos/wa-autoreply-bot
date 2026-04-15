<?php
// app/Http/Controllers/AllowListController.php

namespace App\Http\Controllers;

use App\Models\AllowedNumber;
use Illuminate\Http\Request;

class AllowListController extends Controller
{
    public function index(Request $request)
    {
        $query = AllowedNumber::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('phone_number', 'like', "%{$s}%")
                  ->orWhere('label', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $numbers = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('allowlist.index', compact('numbers'));
    }

    public function create()
    {
        return view('allowlist.form', ['number' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone_number' => [
                'required',
                'string',
                'regex:/^(628[0-9]{7,13}|[0-9]{8,20})$/',
                'unique:allowed_numbers,phone_number',
            ],
            'label'     => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ], [
            'phone_number.regex' => 'Format harus 628xxx atau numeric sender ID (8-20 digit).',
        ]);

        AllowedNumber::create($data);

        return redirect()->route('allowlist.index')
            ->with('success', "Nomor {$data['phone_number']} berhasil ditambahkan!");
    }

    public function edit(AllowedNumber $allowlist)
    {
        return view('allowlist.form', ['number' => $allowlist]);
    }

    public function update(Request $request, AllowedNumber $allowlist)
    {
        $data = $request->validate([
            'phone_number' => [
                'required',
                'string',
                'regex:/^(628[0-9]{7,13}|[0-9]{8,20})$/',
                "unique:allowed_numbers,phone_number,{$allowlist->id}",
            ],
            'label'     => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ], [
            'phone_number.regex' => 'Format harus 628xxx atau numeric sender ID (8-20 digit).',
        ]);

        $allowlist->update($data);

        return redirect()->route('allowlist.index')
            ->with('success', 'Nomor berhasil diperbarui!');
    }

    public function destroy(AllowedNumber $allowlist)
    {
        $number = $allowlist->phone_number;
        $allowlist->delete();

        return redirect()->route('allowlist.index')
            ->with('success', "Nomor {$number} berhasil dihapus!");
    }

    public function toggleActive(AllowedNumber $allowlist)
    {
        $allowlist->update(['is_active' => !$allowlist->is_active]);
        $status = $allowlist->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('allowlist.index')
            ->with('success', "Nomor {$allowlist->phone_number} berhasil {$status}!");
    }
}
