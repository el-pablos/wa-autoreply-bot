<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_two_factor_challenge_when_enabled(): void
    {
        $secret = Google2FA::generateSecretKey();

        $user = User::factory()->create([
            'email' => 'owner-2fa@local.test',
            'password' => Hash::make('testpassword123'),
            'role' => 'owner',
            'two_factor_enabled' => true,
            'totp_secret' => Crypt::encryptString($secret),
            'backup_codes' => [],
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'testpassword123',
        ]);

        $response->assertRedirect('/two-factor/challenge');
        $response->assertSessionHas('2fa_pending_user_id', $user->id);
        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', [
            'actor' => $user->email,
            'action' => 'auth.2fa_challenge_required',
        ]);
    }

    public function test_user_can_complete_two_factor_challenge_with_totp_code(): void
    {
        $secret = Google2FA::generateSecretKey();

        $user = User::factory()->create([
            'email' => 'owner-2fa-ok@local.test',
            'password' => Hash::make('testpassword123'),
            'role' => 'owner',
            'two_factor_enabled' => true,
            'totp_secret' => Crypt::encryptString($secret),
            'backup_codes' => [],
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'testpassword123',
        ])->assertRedirect('/two-factor/challenge');

        $otpCode = Google2FA::getCurrentOtp($secret);

        $response = $this->post('/two-factor/challenge', [
            'code' => $otpCode,
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', [
            'actor' => $user->email,
            'action' => 'auth.login',
        ]);
    }

    public function test_user_can_complete_two_factor_challenge_with_backup_code(): void
    {
        $secret = Google2FA::generateSecretKey();
        $backupCode = 'RECOVERYCODE';

        $user = User::factory()->create([
            'email' => 'owner-2fa-backup@local.test',
            'password' => Hash::make('testpassword123'),
            'role' => 'owner',
            'two_factor_enabled' => true,
            'totp_secret' => Crypt::encryptString($secret),
            'backup_codes' => [Hash::make($backupCode)],
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'testpassword123',
        ])->assertRedirect('/two-factor/challenge');

        $response = $this->post('/two-factor/challenge', [
            'code' => $backupCode,
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertCount(0, $user->fresh()?->backup_codes ?? []);
    }

    public function test_user_can_setup_and_enable_two_factor_from_settings(): void
    {
        $user = User::factory()->create([
            'email' => 'owner-setup@local.test',
            'password' => Hash::make('testpassword123'),
            'role' => 'owner',
            'two_factor_enabled' => false,
            'totp_secret' => null,
            'backup_codes' => null,
        ]);

        $this->actingAs($user);

        $setupResponse = $this->post('/settings/2fa/setup');
        $setupResponse->assertRedirect('/settings/2fa');
        $setupResponse->assertSessionHas('2fa_setup_secret');

        $setupSecret = (string) session('2fa_setup_secret');
        $otpCode = Google2FA::getCurrentOtp($setupSecret);

        $enableResponse = $this->post('/settings/2fa/enable', [
            'code' => $otpCode,
        ]);

        $enableResponse->assertRedirect('/settings/2fa');

        $fresh = $user->fresh();
        $this->assertNotNull($fresh);
        $this->assertTrue((bool) $fresh->two_factor_enabled);
        $this->assertNotNull($fresh->totp_secret);
        $this->assertCount(8, $fresh->backup_codes ?? []);
        $this->assertDatabaseHas('activity_logs', [
            'actor' => $user->email,
            'action' => 'auth.2fa_enabled',
        ]);
    }

    public function test_user_can_disable_two_factor_with_password_and_otp(): void
    {
        $secret = Google2FA::generateSecretKey();

        $user = User::factory()->create([
            'email' => 'owner-disable@local.test',
            'password' => Hash::make('testpassword123'),
            'role' => 'owner',
            'two_factor_enabled' => true,
            'totp_secret' => Crypt::encryptString($secret),
            'backup_codes' => [Hash::make('ONETIMECODE')],
        ]);

        $this->actingAs($user);

        $otpCode = Google2FA::getCurrentOtp($secret);

        $response = $this->post('/settings/2fa/disable', [
            'password' => 'testpassword123',
            'code' => $otpCode,
        ]);

        $response->assertRedirect('/settings/2fa');

        $fresh = $user->fresh();
        $this->assertNotNull($fresh);
        $this->assertFalse((bool) $fresh->two_factor_enabled);
        $this->assertNull($fresh->totp_secret);
        $this->assertNull($fresh->backup_codes);
        $this->assertDatabaseHas('activity_logs', [
            'actor' => $user->email,
            'action' => 'auth.2fa_disabled',
        ]);
    }
}
