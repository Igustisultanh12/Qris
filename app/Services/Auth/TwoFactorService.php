<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new 2FA secret key.
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Get the QR code URL for Google Authenticator.
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        $companyName = config('app.name', 'Qmis - PT Kreatif Sky Abadi');
        return $this->google2fa->getQRCodeUrl($companyName, $user->email, $secret);
    }

    /**
     * Verify a 6-digit TOTP code against a secret key.
     */
    public function verify(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, $code, 2);
    }

    /**
     * Generate fresh set of recovery codes.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::random(10) . '-' . Str::random(10);
        }
        return $codes;
    }

    /**
     * Verify and consume a recovery code.
     */
    public function verifyAndConsumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?: [];

        $index = array_search($code, $codes, true);
        if ($index === false) {
            return false;
        }

        // Remove the used code
        unset($codes[$index]);
        $user->update([
            'two_factor_recovery_codes' => array_values($codes),
        ]);

        return true;
    }
}
