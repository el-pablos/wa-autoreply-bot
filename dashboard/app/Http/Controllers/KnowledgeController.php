<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    public function index(Request $request)
    {
        $query = KnowledgeBase::query();

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $entries = $query
            ->orderByDesc('is_active')
            ->orderByDesc('match_count')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('knowledge.index', [
            'entries' => $entries,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        $entry = KnowledgeBase::query()->create($data);

        AuditTrail::record(
            $request,
            'knowledge.created',
            $entry,
            null,
            $entry->only(['question', 'keywords', 'is_active'])
        );

        return redirect()->route('knowledge.index')->with('success', 'Knowledge base entry berhasil ditambahkan.');
    }

    public function update(Request $request, KnowledgeBase $knowledgeBase)
    {
        $old = $knowledgeBase->only(['question', 'keywords', 'answer', 'is_active']);
        $data = $this->validatePayload($request);

        $knowledgeBase->update($data);

        AuditTrail::record(
            $request,
            'knowledge.updated',
            $knowledgeBase,
            $old,
            $knowledgeBase->fresh()?->only(['question', 'keywords', 'answer', 'is_active'])
        );

        return redirect()->route('knowledge.index')->with('success', 'Knowledge base entry berhasil diperbarui.');
    }

    public function toggle(Request $request, KnowledgeBase $knowledgeBase)
    {
        $old = ['is_active' => $knowledgeBase->is_active];

        $knowledgeBase->update([
            'is_active' => !$knowledgeBase->is_active,
        ]);

        AuditTrail::record(
            $request,
            'knowledge.toggled',
            $knowledgeBase,
            $old,
            ['is_active' => $knowledgeBase->is_active]
        );

        return redirect()->route('knowledge.index')->with('success', 'Status knowledge entry berhasil diubah.');
    }

    public function destroy(Request $request, KnowledgeBase $knowledgeBase)
    {
        $old = $knowledgeBase->only(['question', 'keywords', 'answer', 'is_active', 'match_count']);
        $target = ['type' => $knowledgeBase::class, 'id' => $knowledgeBase->id];

        $knowledgeBase->delete();

        AuditTrail::record(
            $request,
            'knowledge.deleted',
            $target,
            $old,
            null
        );

        return redirect()->route('knowledge.index')->with('success', 'Knowledge base entry berhasil dihapus.');
    }

    private function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'keywords' => ['nullable', 'string', 'max:2000'],
            'answer' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'in:true,false'],
        ]);

        $keywords = collect(explode(',', (string) ($data['keywords'] ?? '')))
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter()
            ->values()
            ->all();

        return [
            'question' => $data['question'],
            'keywords' => !empty($keywords) ? $keywords : null,
            'answer' => $data['answer'],
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
