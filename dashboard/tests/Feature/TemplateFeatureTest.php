<?php

namespace Tests\Feature;

use App\Models\MessageTypeTemplate;
use App\Models\ReplyTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser()
    {
        $user = User::factory()->create();

        return $this->actingAs($user);
    }

    public function test_templates_index_accessible(): void
    {
        $response = $this->actingAsUser()->get('/templates');

        $response->assertOk();
        $response->assertSee('Reply Template Library');
    }

    public function test_can_create_reply_template(): void
    {
        $response = $this->actingAsUser()->post('/templates/reply', [
            'name' => 'Template Default Baru',
            'body' => 'Halo {{nama}}, ini template baru.',
            'is_default' => '1',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/templates');
        $this->assertDatabaseHas('reply_templates', [
            'name' => 'Template Default Baru',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'templates.reply.created',
        ]);
    }

    public function test_set_default_reply_template_deactivates_other_default_flag(): void
    {
        $first = ReplyTemplate::query()->create([
            'name' => 'Template 1',
            'body' => 'Body 1',
            'is_default' => true,
            'is_active' => true,
        ]);
        $second = ReplyTemplate::query()->create([
            'name' => 'Template 2',
            'body' => 'Body 2',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAsUser()->post("/templates/reply/{$second->id}/default");
        $response->assertRedirect('/templates');

        $this->assertTrue((bool) $second->fresh()?->is_default);
        $this->assertFalse((bool) $first->fresh()?->is_default);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'templates.reply.set_default',
        ]);
    }

    public function test_can_upsert_message_type_template(): void
    {
        $response = $this->actingAsUser()->post('/templates/type', [
            'message_type' => 'text',
            'body' => 'Balasan khusus text.',
            'is_active' => 'true',
        ]);

        $response->assertRedirect('/templates');

        $this->assertDatabaseHas('message_type_templates', [
            'message_type' => 'text',
            'body' => 'Balasan khusus text.',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'templates.type.upserted',
        ]);
    }

    public function test_can_toggle_message_type_template_status(): void
    {
        MessageTypeTemplate::query()->create([
            'message_type' => 'image',
            'body' => 'Balasan image',
            'is_active' => true,
        ]);

        $response = $this->actingAsUser()->patch('/templates/type/image/toggle');
        $response->assertRedirect('/templates');

        $this->assertDatabaseHas('message_type_templates', [
            'message_type' => 'image',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'templates.type.toggled',
        ]);
    }

    public function test_name_must_be_unique_when_creating_template(): void
    {
        ReplyTemplate::query()->create([
            'name' => 'Template Unik',
            'body' => 'Body awal',
            'is_active' => '1',
        ]);

        $response = $this->actingAsUser()->post('/templates/reply', [
            'name' => 'Template Unik',
            'body' => 'Body duplikat',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
