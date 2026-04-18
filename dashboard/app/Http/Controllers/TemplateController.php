<?php

namespace App\Http\Controllers;

use App\Models\MessageTypeTemplate;
use App\Models\ReplyTemplate;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    private const SUPPORTED_MESSAGE_TYPES = [
        'text',
        'image',
        'video',
        'audio',
        'document',
        'sticker',
        'location',
        'contact',
        'unknown',
    ];

    public function index()
    {
        $replyTemplates = ReplyTemplate::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $messageTypeTemplates = MessageTypeTemplate::query()
            ->orderBy('message_type')
            ->get()
            ->keyBy('message_type');

        return view('templates.index', [
            'replyTemplates' => $replyTemplates,
            'messageTypeTemplates' => $messageTypeTemplates,
            'supportedMessageTypes' => self::SUPPORTED_MESSAGE_TYPES,
        ]);
    }

    public function storeReplyTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:reply_templates,name'],
            'body' => ['required', 'string', 'max:4000'],
            'conditions_json' => ['nullable', 'json'],
        ]);

        $isDefault = $request->boolean('is_default');
        $isActive = $request->boolean('is_active', true);

        $created = DB::transaction(function () use ($data, $isDefault, $isActive) {
            if ($isDefault) {
                ReplyTemplate::query()->update(['is_default' => false]);
            }

            return ReplyTemplate::query()->create([
                'name' => $data['name'],
                'body' => $data['body'],
                'conditions_json' => $data['conditions_json'] ?? null,
                'is_default' => $isDefault,
                'is_active' => $isActive,
            ]);
        });

        AuditTrail::record(
            $request,
            'templates.reply.created',
            $created,
            null,
            $created->only(['name', 'is_default', 'is_active'])
        );

        return redirect()->route('templates.index')->with('success', 'Reply template berhasil ditambahkan.');
    }

    public function updateReplyTemplate(Request $request, ReplyTemplate $replyTemplate)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('reply_templates', 'name')->ignore($replyTemplate->id)],
            'body' => ['required', 'string', 'max:4000'],
            'conditions_json' => ['nullable', 'json'],
        ]);

        $isDefault = $request->boolean('is_default');
        $isActive = $request->boolean('is_active');
        $old = $replyTemplate->only(['name', 'is_default', 'is_active', 'body', 'conditions_json']);

        DB::transaction(function () use ($replyTemplate, $data, $isDefault, $isActive) {
            if ($isDefault) {
                ReplyTemplate::query()
                    ->where('id', '!=', $replyTemplate->id)
                    ->update(['is_default' => false]);
            }

            $replyTemplate->update([
                'name' => $data['name'],
                'body' => $data['body'],
                'conditions_json' => $data['conditions_json'] ?? null,
                'is_default' => $isDefault,
                'is_active' => $isActive,
            ]);
        });

        AuditTrail::record(
            $request,
            'templates.reply.updated',
            $replyTemplate,
            $old,
            $replyTemplate->fresh()?->only(['name', 'is_default', 'is_active', 'body', 'conditions_json'])
        );

        return redirect()->route('templates.index')->with('success', 'Reply template berhasil diperbarui.');
    }

    public function destroyReplyTemplate(Request $request, ReplyTemplate $replyTemplate)
    {
        $old = $replyTemplate->only(['name', 'is_default', 'is_active', 'body']);
        $target = ['type' => $replyTemplate::class, 'id' => $replyTemplate->id];

        $replyTemplate->delete();

        AuditTrail::record(
            $request,
            'templates.reply.deleted',
            $target,
            $old,
            null
        );

        return redirect()->route('templates.index')->with('success', 'Reply template berhasil dihapus.');
    }

    public function setDefaultReplyTemplate(Request $request, ReplyTemplate $replyTemplate)
    {
        DB::transaction(function () use ($replyTemplate) {
            ReplyTemplate::query()->update(['is_default' => false]);
            $replyTemplate->update([
                'is_default' => true,
                'is_active' => true,
            ]);
        });

        AuditTrail::record(
            $request,
            'templates.reply.set_default',
            $replyTemplate,
            null,
            ['name' => $replyTemplate->name]
        );

        return redirect()->route('templates.index')->with('success', 'Template default berhasil diganti.');
    }

    public function upsertMessageTypeTemplate(Request $request)
    {
        $data = $request->validate([
            'message_type' => ['required', 'string', Rule::in(self::SUPPORTED_MESSAGE_TYPES)],
            'body' => ['required', 'string', 'max:4000'],
            'is_active' => ['nullable', 'in:true,false'],
        ]);

        $isActive = $request->boolean('is_active', true);

        $current = MessageTypeTemplate::query()->find($data['message_type']);

        $template = MessageTypeTemplate::query()->updateOrCreate(
            ['message_type' => $data['message_type']],
            [
                'body' => $data['body'],
                'is_active' => $isActive,
            ]
        );

        AuditTrail::record(
            $request,
            'templates.type.upserted',
            ['type' => MessageTypeTemplate::class, 'id' => $template->message_type],
            $current?->toArray(),
            $template->toArray()
        );

        return redirect()->route('templates.index')->with('success', 'Template per tipe pesan berhasil disimpan.');
    }

    public function toggleMessageTypeTemplate(Request $request, string $messageType)
    {
        if (!in_array($messageType, self::SUPPORTED_MESSAGE_TYPES, true)) {
            abort(404);
        }

        $template = MessageTypeTemplate::query()->findOrFail($messageType);
        $old = $template->only(['message_type', 'is_active', 'body']);

        $template->update([
            'is_active' => !$template->is_active,
        ]);

        AuditTrail::record(
            $request,
            'templates.type.toggled',
            ['type' => MessageTypeTemplate::class, 'id' => $template->message_type],
            $old,
            $template->fresh()?->only(['message_type', 'is_active', 'body'])
        );

        return redirect()->route('templates.index')->with('success', 'Status template tipe pesan berhasil diubah.');
    }
}
