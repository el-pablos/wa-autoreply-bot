<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSeeText("Operator's Console");
    }

    public function test_redirect_to_login_when_not_authenticated(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@local.test',
            'password' => Hash::make('testpassword123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'testpassword123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', [
            'actor' => $user->email,
            'action' => 'auth.login',
        ]);
    }

    public function test_login_with_wrong_credentials(): void
    {
        User::factory()->create([
            'email' => 'owner@local.test',
            'password' => Hash::make('testpassword123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'owner@local.test',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseHas('activity_logs', [
            'actor' => 'guest:owner@local.test',
            'action' => 'auth.login_failed',
        ]);
    }

    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', [
            'actor' => $user->email,
            'action' => 'auth.logout',
        ]);
    }
}
