<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Support\AuditTrail;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index()
    {
        $backups = Backup::query()
            ->orderByDesc('created_at')
            ->paginate(25);

        $summary = [
            'total' => Backup::query()->count(),
            'db' => Backup::query()->where('type', 'db')->count(),
            'session' => Backup::query()->where('type', 'session')->count(),
            'size_bytes' => (int) (Backup::query()->sum('size_bytes') ?? 0),
        ];

        return view('backups.index', [
            'backups' => $backups,
            'summary' => $summary,
        ]);
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:db,session'],
        ]);

        $type = $data['type'];
        $timestamp = now()->format('Ymd_His');
        $filename = $type . '_' . $timestamp . '.sql.gz';
        if ($type === 'session') {
            $filename = $type . '_' . $timestamp . '.tar.gz';
        }

        $backup = Backup::query()->create([
            'path' => 'backups/' . $filename,
            'size_bytes' => random_int(150_000, 8_000_000),
            'type' => $type,
            'checksum' => hash('sha256', $filename . '|' . microtime(true)),
        ]);

        AuditTrail::record(
            $request,
            'backups.run',
            $backup,
            null,
            $backup->only(['path', 'size_bytes', 'type', 'checksum'])
        );

        return redirect()->route('backups.index')->with('success', 'Backup ' . strtoupper($type) . ' berhasil dibuat (simulasi metadata).');
    }

    public function destroy(Request $request, Backup $backup)
    {
        $old = $backup->only(['path', 'size_bytes', 'type', 'checksum']);
        $target = ['type' => $backup::class, 'id' => $backup->id];

        $backup->delete();

        AuditTrail::record(
            $request,
            'backups.deleted',
            $target,
            $old,
            null
        );

        return redirect()->route('backups.index')->with('success', 'Backup entry berhasil dihapus.');
    }
}
