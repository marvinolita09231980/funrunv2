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

        $regularFont = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
        $boldFont = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');

        self::drawCenteredText($canvas, 'PADAC FUN RUN 2026', $boldFont, 34, 115, $accent);
        self::drawCenteredText($canvas, 'Feedback Form', $boldFont, 30, 165, $heading);
        self::drawCenteredText($canvas, 'Scan the QR code or open the link below', $regularFont, 18, 205, $muted);

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

        self::drawCenteredText($canvas, 'Feedback form link', $boldFont, 17, 950, $heading);
        self::drawCenteredFittedText($canvas, self::url(), $regularFont, 20, 995, 760, $accent);
        self::drawCenteredText($canvas, 'Tap or type the link above to open the form', $regularFont, 15, 1035, $muted);

        ob_start();
        imagepng($canvas, null, 9);
        $png = (string) ob_get_clean();

        imagedestroy($qrImage);
        imagedestroy($canvas);

        return $png;
    }

    private static function drawCenteredFittedText(
        \GdImage $image,
        string $text,
        string $font,
        int $fontSize,
        int $baseline,
        int $maximumWidth,
        int $color,
    ): void {
        while (($fontSize > 11) && (self::textWidth($text, $font, $fontSize) > $maximumWidth)) {
            $fontSize--;
        }

        self::drawCenteredText($image, $text, $font, $fontSize, $baseline, $color);
    }

    private static function drawCenteredText(
        \GdImage $image,
        string $text,
        string $font,
        int $fontSize,
        int $baseline,
        int $color,
    ): void {
        $x = (int) ((self::DOWNLOAD_WIDTH - self::textWidth($text, $font, $fontSize)) / 2);

        imagettftext($image, $fontSize, 0, $x, $baseline, $color, $font, $text);
    }

    private static function textWidth(string $text, string $font, int $fontSize): int
    {
        $box = imagettfbbox($fontSize, 0, $font, $text);

        return (int) abs($box[2] - $box[0]);
    }
}
