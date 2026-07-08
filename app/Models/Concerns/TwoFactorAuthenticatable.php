<?php

namespace App\Models\Concerns;

use App\Services\TwoFactorAuthenticationProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Adds TOTP-based two-factor authentication behaviour to an Authenticatable model.
 *
 * The consuming model must cast the backing columns:
 *   'two_factor_secret' => 'encrypted',
 *   'two_factor_recovery_codes' => 'encrypted:array',
 *   'two_factor_confirmed_at' => 'datetime',
 */
trait TwoFactorAuthenticatable
{
    /**
     * Determine if two-factor authentication is fully enabled and confirmed.
     */
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Determine if a secret has been generated but not yet confirmed with a code.
     */
    public function hasPendingTwoFactorAuthentication(): bool
    {
        return ! is_null($this->two_factor_secret) && is_null($this->two_factor_confirmed_at);
    }

    /**
     * Generate a fresh secret and recovery codes, leaving the setup unconfirmed.
     */
    public function enableTwoFactorAuthentication(): void
    {
        $this->forceFill([
            'two_factor_secret' => app(TwoFactorAuthenticationProvider::class)->generateSecretKey(),
            'two_factor_recovery_codes' => $this->generateNewRecoveryCodes(),
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Confirm two-factor setup by verifying the first code from the user's app.
     */
    public function confirmTwoFactorAuthentication(string $code): bool
    {
        if (is_null($this->two_factor_secret)) {
            return false;
        }

        if (! app(TwoFactorAuthenticationProvider::class)->verify($this->two_factor_secret, $code)) {
            return false;
        }

        $this->forceFill(['two_factor_confirmed_at' => now()])->save();

        return true;
    }

    /**
     * Disable two-factor authentication and clear all related state.
     */
    public function disableTwoFactorAuthentication(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Replace the current recovery codes with a fresh set.
     */
    public function regenerateRecoveryCodes(): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => $this->generateNewRecoveryCodes(),
        ])->save();
    }

    /**
     * Get the model's current recovery codes.
     *
     * @return array<int, string>
     */
    public function recoveryCodes(): array
    {
        return $this->two_factor_recovery_codes ?? [];
    }

    /**
     * Consume a single recovery code, removing it from the stored set.
     */
    public function replaceRecoveryCode(string $used_code): void
    {
        $remaining_codes = array_values(array_filter(
            $this->recoveryCodes(),
            fn (string $stored_code) => ! hash_equals($stored_code, $used_code),
        ));

        $this->forceFill(['two_factor_recovery_codes' => $remaining_codes])->save();
    }

    /**
     * Build the otpauth URL for the current secret.
     */
    public function twoFactorQrCodeUrl(): string
    {
        return app(TwoFactorAuthenticationProvider::class)->qrCodeUrl(
            config('app.name'),
            $this->email,
            $this->two_factor_secret,
        );
    }

    /**
     * Render the current secret as an inline SVG QR code.
     */
    public function twoFactorQrCodeSvg(): string
    {
        return app(TwoFactorAuthenticationProvider::class)->qrCodeSvg($this->twoFactorQrCodeUrl());
    }

    /**
     * Generate a new set of one-time recovery codes.
     *
     * @return array<int, string>
     */
    protected function generateNewRecoveryCodes(): array
    {
        return Collection::times(8, fn () => Str::random(10).'-'.Str::random(10))->all();
    }
}
