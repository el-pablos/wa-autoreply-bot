<?php
// app/Http/Controllers/AllowListController.php

namespace App\Http\Controllers;

use App\Models\AllowedNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

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
        $data = $this->validateAndNormalize($request);

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
        $data = $this->validateAndNormalize($request, $allowlist);

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

    private function validateAndNormalize(Request $request, ?AllowedNumber $allowlist = null): array
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^(\+62|62|08)[0-9]{7,13}$/'],
            'label' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ], [
            'phone_number.regex' => 'Nomor WA harus diawali +62, 62, atau 08 dan hanya berisi angka.',
        ]);

        $data['phone_number'] = $this->normalizePhoneNumber($data['phone_number']);

        Validator::make($data, [
            'phone_number' => [
                'required',
                'regex:/^628[0-9]{7,13}$/',
                Rule::unique('allowed_numbers', 'phone_number')->ignore($allowlist?->id),
            ],
        ], [
            'phone_number.regex' => 'Nomor WA tidak valid. Gunakan format +62, 62, atau 08 dengan nomor seluler aktif.',
            'phone_number.unique' => 'Nomor WA sudah ada di allowlist.',
        ])->validate();

        return $data;
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        if (str_starts_with($phoneNumber, '+62')) {
            return '62' . substr($phoneNumber, 3);
        }

        if (str_starts_with($phoneNumber, '08')) {
            return '62' . substr($phoneNumber, 1);
        }

        return $phoneNumber;
    }
}
