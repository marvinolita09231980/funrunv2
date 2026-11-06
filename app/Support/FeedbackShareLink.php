<?php

namespace App\Support;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;

class FeedbackShareLink
{
    private const DOWNLOAD_HEIGHT = 1120;

    private const DOWNLOAD_WIDTH = 900;

    public static function url(): string
    {
        return rtrim(request()->root(), '/').'/feedback/feedback-form';
    }

    public static function messengerShareUrl(): string
    {
        return 'https://www.messenger.com/';
    }

    public static function qrCodeDownloadUrl(): string
    {
        return rtrim(request()->root(), '/').'/feedback/feedback-qr-code.png';
    }

    public static function qrCodePreviewUrl(): string
    {
        return rtrim(request()->root(), '/').'/feedback/feedback-qr-code-preview.png';
    }

    public static function shareUrl(): string
    {
        return rtrim(request()->root(), '/').'/feedback/share-feedback-form';
    }

    public static function qrCodeDataUri(): string
    {
        return (new QRCode(new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'scale' => 10,
            'imageTransparent' => false,
        ])))->render(self::url());
    }

    public static function qrCodePng(): string
    {
        return (new QRCode(new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => false,
            'scale' => 10,
            'imageTransparent' => false,
        ])))->render(self::url());
    }

    public static function downloadableQrCodePng(): string
    {
        $canvas = imagecreatetruecolor(self::DOWNLOAD_WIDTH, self::DOWNLOAD_HEIGHT);
        $background = imagecolorallocate($canvas, 255, 251, 235);
        $border = imagecolorallocate($canvas, 252, 211, 77);
        $heading = imagecolorallocate($canvas, 68, 64, 60);
        $accent = imagecolorallocate($canvas, 146, 64, 14);
        $muted = imagecolorallocate($canvas, 87, 83, 78);
        $white = imagecolorallocate($canvas, 255, 255, 255);

        imagefill($canvas, 0, 0, $background);
        imagefilledrectangle($canvas, 48, 48, 851, 1071, $white);
        imagerectangle($canvas, 48, 48, 851, 1071, $border);

        self::drawCenteredBitmapText($canvas, 'PADAC FUN RUN 2026', 3.2, 80, $accent);
        self::drawCenteredBitmapText($canvas, 'FEEDBACK FORM', 2.6, 140, $heading);
        self::drawCenteredBitmapText($canvas, 'Scan the QR code or open the link below', 1.5, 195, $muted);

        $qrImage = imagecreatefromstring(self::qrCodePng());
        $qrSize = 650;
        $qrX = (int) ((self::DOWNLOAD_WIDTH - $qrSize) / 2);
        $qrY = 245;

        imagecopyresampled(
            $canvas,
            $qrImage,
            $qrX,
            $qrY,
            0,
            0,
            $qrSize,
            $qrSize,
            imagesx($qrImage),
            imagesy($qrImage),
        );

        self::drawCenteredBitmapText($canvas, 'FEEDBACK FORM LINK', 1.5, 930, $heading);
        self::drawCenteredFittedBitmapText($canvas, self::url(), 1.5, 975, 760, $accent);
        self::drawCenteredBitmapText($canvas, 'Tap or type the link above to open the form', 1.2, 1025, $muted);

        ob_start();
        imagepng($canvas, null, 9);
        $png = (string) ob_get_clean();

        imagedestroy($qrImage);
        imagedestroy($canvas);

        return $png;
    }

    private static function drawCenteredFittedBitmapText(
        \GdImage $image,
        string $text,
        float $preferredScale,
        int $top,
        int $maximumWidth,
        int $color,
    ): void {
        $naturalWidth = imagefontwidth(5) * strlen($text);
        $scale = min($preferredScale, $maximumWidth / max(1, $naturalWidth));

        self::drawCenteredBitmapText($image, $text, $scale, $top, $color);
    }

    private static function drawCenteredBitmapText(
        \GdImage $image,
        string $text,
        float $scale,
        int $top,
        int $color,
    ): void {
        $font = 5;
        $sourceWidth = max(1, imagefontwidth($font) * strlen($text));
        $sourceHeight = imagefontheight($font);
        $textImage = imagecreatetruecolor($sourceWidth, $sourceHeight);
        $transparent = imagecolorallocatealpha($textImage, 0, 0, 0, 127);
        $red = ($color >> 16) & 0xFF;
        $green = ($color >> 8) & 0xFF;
        $blue = $color & 0xFF;
        $textColor = imagecolorallocate($textImage, $red, $green, $blue);

        imagealphablending($textImage, false);
        imagesavealpha($textImage, true);
        imagefill($textImage, 0, 0, $transparent);
        imagestring($textImage, $font, 0, 0, $text, $textColor);

        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $x = (int) ((self::DOWNLOAD_WIDTH - $targetWidth) / 2);

        imagecopyresampled(
            $image,
            $textImage,
            $x,
            $top,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        imagedestroy($textImage);
    }
}
