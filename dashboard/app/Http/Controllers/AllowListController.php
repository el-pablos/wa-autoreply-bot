<?php
// app/Http/Controllers/AllowListController.php

namespace App\Http\Controllers;

use App\Models\AllowedNumber;
use App\Models\ReplyTemplate;
use App\Support\AuditTrail;
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
        return view('allowlist.form', [
            'number' => null,
            'templates' => $this->templateOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAndNormalize($request);

        $created = AllowedNumber::create($data);

        AuditTrail::record(
            $request,
            'allowlist.created',
            $created,
            null,
            $created->only(['phone_number', 'label', 'template_id', 'is_active'])
        );

        return redirect()->route('allowlist.index')
            ->with('success', "Nomor {$data['phone_number']} berhasil ditambahkan!");
    }

    public function edit(AllowedNumber $allowlist)
    {
        return view('allowlist.form', [
            'number' => $allowlist,
            'templates' => $this->templateOptions(),
        ]);
    }

    public function update(Request $request, AllowedNumber $allowlist)
    {
        $old = $allowlist->only(['phone_number', 'label', 'template_id', 'is_active']);
        $data = $this->validateAndNormalize($request, $allowlist);

        $allowlist->update($data);

        AuditTrail::record(
            $request,
            'allowlist.updated',
            $allowlist,
            $old,
            $allowlist->fresh()?->only(['phone_number', 'label', 'template_id', 'is_active'])
        );

        return redirect()->route('allowlist.index')
            ->with('success', 'Nomor berhasil diperbarui!');
    }

    public function destroy(Request $request, AllowedNumber $allowlist)
    {
        $old = $allowlist->only(['phone_number', 'label', 'is_active']);
        $number = $allowlist->phone_number;
        $target = ['type' => $allowlist::class, 'id' => $allowlist->id];

        $allowlist->delete();

        AuditTrail::record(
            $request,
            'allowlist.deleted',
            $target,
            $old,
            null
        );

        return redirect()->route('allowlist.index')
            ->with('success', "Nomor {$number} berhasil dihapus!");
    }

    public function toggleActive(Request $request, AllowedNumber $allowlist)
    {
        $old = ['is_active' => $allowlist->is_active];
        $allowlist->update(['is_active' => !$allowlist->is_active]);
        $status = $allowlist->is_active ? 'diaktifkan' : 'dinonaktifkan';

        AuditTrail::record(
            $request,
            'allowlist.toggled',
            $allowlist,
            $old,
            ['is_active' => $allowlist->is_active]
        );

        return redirect()->route('allowlist.index')
            ->with('success', "Nomor {$allowlist->phone_number} berhasil {$status}!");
    }

    private function validateAndNormalize(Request $request, ?AllowedNumber $allowlist = null): array
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^(\+62|62|08)[0-9]{7,13}$/'],
            'label' => 'nullable|string|max:100',
            'template_id' => ['nullable', 'integer', Rule::exists('reply_templates', 'id')],
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

    private function templateOptions()
    {
        return ReplyTemplate::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default', 'is_active'])
            ->map(function (ReplyTemplate $template) {
                $label = $template->name;
                if ($template->is_default) {
                    $label .= ' [DEFAULT]';
                }
                if (!$template->is_active) {
                    $label .= ' [NONAKTIF]';
                }

                return [
                    'id' => $template->id,
                    'label' => $label,
                ];
            });
    }
}
