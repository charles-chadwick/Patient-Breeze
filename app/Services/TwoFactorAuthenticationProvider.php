<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationProvider
{
    public function __construct(private Google2FA $engine) {}

    /**
     * Generate a new TOTP secret key.
     */
    public function generateSecretKey(): string
    {
        return $this->engine->generateSecretKey();
    }

    /**
     * Build the otpauth:// URL that authenticator apps encode into a QR code.
     */
    public function qrCodeUrl(string $companyName, string $accountEmail, string $secret): string
    {
        return $this->engine->getQRCodeUrl($companyName, $accountEmail, $secret);
    }

    /**
     * Verify that the given one-time code is valid for the secret.
     */
    public function verify(string $secret, string $code): bool
    {
        return $this->engine->verifyKey($secret, $code);
    }

    /**
     * Render the given otpauth URL as an inline SVG QR code.
     */
    public function qrCodeSvg(string $url): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0),
                new SvgImageBackEnd,
            )
        ))->writeString($url);

        return trim(substr($svg, (int) strpos($svg, "\n") + 1));
    }
}
