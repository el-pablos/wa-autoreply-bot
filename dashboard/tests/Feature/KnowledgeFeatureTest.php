<?php

namespace Tests\Feature;

use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role = 'owner')
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);

        return $this->actingAs($user);
    }

    public function test_knowledge_index_accessible(): void
    {
        $response = $this->actingAsRole()->get('/knowledge');

        $response->assertOk();
        $response->assertSee('Knowledge Base Entries');
    }

    public function test_owner_can_create_knowledge_entry(): void
    {
        $response = $this->actingAsRole('owner')->post('/knowledge', [
            'question' => 'Jam operasional hari ini?',
            'keywords' => 'jam operasional,buka,tutup',
            'answer' => 'Jam operasional hari ini 09:00-17:00.',
            'is_active' => 'true',
        ]);

        $response->assertRedirect('/knowledge');

        $this->assertDatabaseHas('knowledge_base', [
            'question' => 'Jam operasional hari ini?',
            'answer' => 'Jam operasional hari ini 09:00-17:00.',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'knowledge.created',
        ]);
    }

    public function test_owner_can_toggle_knowledge_status(): void
    {
        $entry = KnowledgeBase::query()->create([
            'question' => 'Q1',
            'keywords' => ['a', 'b'],
            'answer' => 'A1',
            'is_active' => true,
        ]);

        $response = $this->actingAsRole('owner')->patch("/knowledge/{$entry->id}/toggle");

        $response->assertRedirect('/knowledge');
        $this->assertFalse((bool) $entry->fresh()?->is_active);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'knowledge.toggled',
        ]);
    }

    public function test_owner_can_delete_knowledge_entry(): void
    {
        $entry = KnowledgeBase::query()->create([
            'question' => 'Q1',
            'keywords' => ['a'],
            'answer' => 'A1',
            'is_active' => true,
        ]);

        $response = $this->actingAsRole('owner')->delete("/knowledge/{$entry->id}");

        $response->assertRedirect('/knowledge');
        $this->assertDatabaseMissing('knowledge_base', [
            'id' => $entry->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'knowledge.deleted',
        ]);
    }

    public function test_search_filters_knowledge_entries(): void
    {
        KnowledgeBase::query()->create([
            'question' => 'Cara reset password?',
            'keywords' => ['password', 'reset'],
            'answer' => 'Klik menu reset password.',
            'is_active' => true,
        ]);
        KnowledgeBase::query()->create([
            'question' => 'Harga langganan?',
            'keywords' => ['harga'],
            'answer' => 'Cek halaman pricing.',
            'is_active' => true,
        ]);

        $response = $this->actingAsRole()->get('/knowledge?search=password');

        $response->assertOk();
        $response->assertSee('Cara reset password?');
        $response->assertDontSee('Harga langganan?');
    }

    public function test_viewer_cannot_mutate_knowledge_entries(): void
    {
        $response = $this->actingAsRole('viewer')->post('/knowledge', [
            'question' => 'Tidak boleh',
            'answer' => 'Tidak boleh',
            'is_active' => 'true',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('knowledge_base', [
            'question' => 'Tidak boleh',
        ]);
    }
}
