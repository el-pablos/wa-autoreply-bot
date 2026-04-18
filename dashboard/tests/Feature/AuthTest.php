<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
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

    public function test_login_with_correct_password(): void
    {
        config(['app.dashboard_password' => 'testpassword123']);

        $response = $this->post('/login', ['password' => 'testpassword123']);
        $response->assertRedirect('/');
    }

    public function test_login_with_wrong_password(): void
    {
        config(['app.dashboard_password' => 'testpassword123']);

        $response = $this->post('/login', ['password' => 'wrongpassword']);
        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
    }

    public function test_logout_clears_session(): void
    {
        $this->withSession(['authenticated' => true]);
        $response = $this->post('/logout');
        $response->assertRedirect('/login');
    }
}
