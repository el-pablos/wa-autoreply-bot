<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role = 'owner', array $overrides = [])
    {
        $user = User::factory()->create(array_merge([
            'role' => $role,
        ], $overrides));

        return $this->actingAs($user);
    }

    public function test_users_index_accessible(): void
    {
        $response = $this->actingAsRole('owner')->get('/users');

        $response->assertOk();
        $response->assertSee('User Management');
    }

    public function test_owner_can_create_user(): void
    {
        $response = $this->actingAsRole('owner')->post('/users', [
            'name' => 'Operator Baru',
            'email' => 'operator.baru@example.test',
            'role' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/users');

        $this->assertDatabaseHas('users', [
            'email' => 'operator.baru@example.test',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'users.created',
        ]);
    }

    public function test_admin_cannot_delete_owner(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'email' => 'owner.target@example.test',
        ]);

        $response = $this->actingAsRole('admin')->delete('/users/' . $owner->id);

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
        ]);
    }

    public function test_viewer_cannot_create_user(): void
    {
        $response = $this->actingAsRole('viewer')->post('/users', [
            'name' => 'Blocked',
            'email' => 'blocked@example.test',
            'role' => 'viewer',
            'password' => 'password123',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'blocked@example.test',
        ]);
    }

    public function test_owner_cannot_delete_self(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'email' => 'owner.self@example.test',
        ]);

        $response = $this->actingAs($owner)->delete('/users/' . $owner->id);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
        ]);
    }
}
