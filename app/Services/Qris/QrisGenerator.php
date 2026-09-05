<?php

namespace App\Services\Qris;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrisGenerator
{
    /**
     * Generate pure SVG string from QRIS payload.
     */
    public static function generateSvg(string $payload, int $size = 400, int $margin = 2): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($payload);
    }

    /**
     * Generate SVG Data URI (base64 encoded).
     */
    public static function generateSvgDataUri(string $payload, int $size = 400, int $margin = 2): string
    {
        $svg = self::generateSvg($payload, $size, $margin);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate PNG Data URI if Imagick or GD is available, or fallback to SVG Data URI.
     */
    public static function generatePngDataUri(string $payload, int $size = 400, int $margin = 2): string
    {
        if (extension_loaded('imagick') && class_exists(\BaconQrCode\Renderer\Image\ImagickImageBackEnd::class)) {
            try {
                $renderer = new ImageRenderer(
                    new RendererStyle($size, $margin),
                    new \BaconQrCode\Renderer\Image\ImagickImageBackEnd()
                );
                $writer = new Writer($renderer);
                $png = $writer->writeString($payload);
                return 'data:image/png;base64,' . base64_encode($png);
            } catch (\Throwable) {
                // Fallback to SVG Data URI
            }
        }

        return self::generateSvgDataUri($payload, $size, $margin);
    }
}
