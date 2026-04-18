<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role = 'owner')
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);

        return $this->actingAs($user);
    }

    public function test_backups_index_accessible(): void
    {
        $response = $this->actingAsRole()->get('/backups');

        $response->assertOk();
        $response->assertSee('Manual Backup Trigger');
    }

    public function test_owner_can_run_backup_metadata(): void
    {
        $response = $this->actingAsRole('owner')->post('/backups/run', [
            'type' => 'db',
        ]);

        $response->assertRedirect('/backups');

        $this->assertDatabaseHas('backups', [
            'type' => 'db',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'backups.run',
        ]);
    }

    public function test_owner_can_delete_backup_entry(): void
    {
        $backup = Backup::query()->create([
            'path' => 'backups/db_test.sql.gz',
            'size_bytes' => 1200,
            'type' => 'db',
            'checksum' => str_repeat('a', 64),
        ]);

        $response = $this->actingAsRole('owner')->delete("/backups/{$backup->id}");

        $response->assertRedirect('/backups');

        $this->assertDatabaseMissing('backups', [
            'id' => $backup->id,
        ]);
    }

    public function test_viewer_cannot_run_or_delete_backup_entry(): void
    {
        $backup = Backup::query()->create([
            'path' => 'backups/session_test.tar.gz',
            'size_bytes' => 800,
            'type' => 'session',
            'checksum' => str_repeat('b', 64),
        ]);

        $run = $this->actingAsRole('viewer')->post('/backups/run', [
            'type' => 'session',
        ]);
        $run->assertForbidden();

        $delete = $this->actingAsRole('viewer')->delete("/backups/{$backup->id}");
        $delete->assertForbidden();
    }
}
