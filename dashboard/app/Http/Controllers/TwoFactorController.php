<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class TwoFactorController extends Controller
{
    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $setupSecret = $request->session()->get('2fa_setup_secret');
        $qrInline = null;

        if (is_string($setupSecret) && $setupSecret !== '') {
            try {
                $qrInline = Google2FA::getQRCodeInline(
                    config('app.name', 'WA Bot Operator'),
                    (string) $user->email,
                    $setupSecret
                );
            } catch (\Throwable) {
                $qrInline = null;
            }
        }

        return view('settings.two-factor', [
            'user' => $user,
            'setupSecret' => $setupSecret,
            'qrInline' => $qrInline,
            'backupCodes' => $request->session()->get('two_factor_backup_codes', []),
        ]);
    }

    public function showChallenge(Request $request)
    {
        $pendingUser = $this->resolvePendingUser($request);
        if (!$pendingUser) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', [
            'pendingEmail' => $pendingUser->email,
        ]);
    }

    public function verifyChallenge(Request $request)
    {
        $request->validate([
            'code' => 'required|string|min:6|max:64',
        ]);

        $pendingUser = $this->resolvePendingUser($request);
        if (!$pendingUser) {
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi login 2FA sudah berakhir. Silakan login ulang.',
            ]);
        }

        $normalizedCode = $this->normalizeCode((string) $request->input('code'));
        $isTotpValid = $this->verifyTotpCode($pendingUser, $normalizedCode);
        $isBackupValid = false;

        if (!$isTotpValid) {
            $isBackupValid = $this->consumeBackupCode($pendingUser, $normalizedCode);
        }

        if (!$isTotpValid && !$isBackupValid) {
            AuditTrail::record(
                $request,
                'auth.2fa_failed',
                $pendingUser,
                null,
                ['email' => $pendingUser->email]
            );

            return back()->withErrors([
                'code' => 'Kode OTP atau backup code tidak valid.',
            ]);
        }

        $remember = (bool) $request->session()->pull('2fa_pending_remember', false);
        $request->session()->forget('2fa_pending_user_id');
        $request->session()->regenerate();

        Auth::login($pendingUser, $remember);
        $pendingUser->forceFill([
            'last_login_at' => now(),
        ])->save();

        AuditTrail::record(
            $request,
            'auth.login',
            $pendingUser,
            null,
            ['email' => $pendingUser->email, 'role' => $pendingUser->role]
        );

        return redirect()->route('dashboard');
    }

    public function setup(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->two_factor_enabled && $user->totp_secret) {
            return back()->withErrors([
                'two_factor' => '2FA sudah aktif untuk akun ini.',
            ]);
        }

        $secret = Google2FA::generateSecretKey();
        $request->session()->put('2fa_setup_secret', $secret);

        AuditTrail::record(
            $request,
            'auth.2fa_setup_started',
            $user,
            null,
            ['email' => $user->email]
        );

        return redirect()->route('settings.2fa.index')
            ->with('success', 'Secret 2FA berhasil dibuat. Scan QR lalu verifikasi kodenya.');
    }

    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|min:6|max:64',
        ]);

        /** @var User|null $user */
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $secret = $request->session()->get('2fa_setup_secret');
        if (!is_string($secret) || $secret === '') {
            return back()->withErrors([
                'two_factor' => 'Secret 2FA belum dibuat. Klik tombol setup dulu.',
            ]);
        }

        $normalizedCode = $this->normalizeCode((string) $request->input('code'));
        if (!Google2FA::verifyKey($secret, $normalizedCode)) {
            return back()->withErrors([
                'code' => 'Kode OTP tidak valid untuk secret saat ini.',
            ]);
        }

        $plainBackupCodes = $this->generateBackupCodes();
        $hashedBackupCodes = array_map(static fn (string $code): string => Hash::make($code), $plainBackupCodes);
        $oldValue = $user->only(['two_factor_enabled', 'totp_secret', 'backup_codes']);

        $user->forceFill([
            'totp_secret' => Crypt::encryptString($secret),
            'two_factor_enabled' => true,
            'backup_codes' => $hashedBackupCodes,
        ])->save();

        $request->session()->forget('2fa_setup_secret');
        $request->session()->put('two_factor_backup_codes', $plainBackupCodes);

        AuditTrail::record(
            $request,
            'auth.2fa_enabled',
            $user,
            $oldValue,
            ['two_factor_enabled' => true, 'backup_codes_count' => count($plainBackupCodes)]
        );

        return redirect()->route('settings.2fa.index')
            ->with('success', '2FA berhasil diaktifkan. Simpan backup code di tempat aman.');
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'code' => 'required|string|min:6|max:64',
        ]);

        /** @var User|null $user */
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->two_factor_enabled || !$user->totp_secret) {
            return back()->withErrors([
                'two_factor' => '2FA belum aktif di akun ini.',
            ]);
        }

        $normalizedCode = $this->normalizeCode((string) $request->input('code'));
        $isTotpValid = $this->verifyTotpCode($user, $normalizedCode);
        $isBackupValid = false;

        if (!$isTotpValid) {
            $isBackupValid = $this->consumeBackupCode($user, $normalizedCode);
        }

        if (!$isTotpValid && !$isBackupValid) {
            return back()->withErrors([
                'code' => 'Kode OTP atau backup code tidak valid.',
            ]);
        }

        $oldValue = $user->only(['two_factor_enabled', 'totp_secret', 'backup_codes']);

        $user->forceFill([
            'totp_secret' => null,
            'two_factor_enabled' => false,
            'backup_codes' => null,
        ])->save();

        $request->session()->forget('2fa_setup_secret');

        AuditTrail::record(
            $request,
            'auth.2fa_disabled',
            $user,
            $oldValue,
            ['two_factor_enabled' => false]
        );

        return redirect()->route('settings.2fa.index')
            ->with('success', '2FA berhasil dinonaktifkan.');
    }

    private function resolvePendingUser(Request $request): ?User
    {
        $pendingUserId = (int) $request->session()->get('2fa_pending_user_id', 0);
        if ($pendingUserId <= 0) {
            return null;
        }

        return User::query()->find($pendingUserId);
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper(str_replace([' ', '-'], '', $code));
    }

    private function verifyTotpCode(User $user, string $normalizedCode): bool
    {
        $secret = $this->decryptSecret($user);
        if (!$secret) {
            return false;
        }

        return (bool) Google2FA::verifyKey($secret, $normalizedCode);
    }

    private function decryptSecret(User $user): ?string
    {
        if (!$user->totp_secret) {
            return null;
        }

        $raw = (string) $user->totp_secret;

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return $raw;
        }
    }

    private function consumeBackupCode(User $user, string $normalizedCode): bool
    {
        $backupCodes = $user->backup_codes;
        if (!is_array($backupCodes) || $backupCodes === []) {
            return false;
        }

        $matchedIndex = null;

        foreach ($backupCodes as $index => $hash) {
            if (is_string($hash) && Hash::check($normalizedCode, $hash)) {
                $matchedIndex = $index;
                break;
            }
        }

        if ($matchedIndex === null) {
            return false;
        }

        unset($backupCodes[$matchedIndex]);

        $user->forceFill([
            'backup_codes' => array_values($backupCodes),
        ])->save();

        return true;
    }

    /**
     * @return string[]
     */
    private function generateBackupCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::upper(Str::random(10));
        }

        return $codes;
    }
}
