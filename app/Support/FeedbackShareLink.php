<?php

namespace App\Support;

use chillerlan\QRCode\QRCode;

class FeedbackShareLink
{
    public static function url(): string
    {
        return rtrim(request()->root(), '/').'/feedback/feedback-form';
    }

    public static function messengerShareUrl(): string
    {
        return 'https://www.messenger.com/';
    }

    public static function qrCodeDataUri(): string
    {
        return (new QRCode)->render(self::url());
    }
}
