<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;

class QrService
{
    /**
     * ساخت تصویر QR به صورت Data URI (SVG)
     */
    public static function svgDataUri(string $data): string
    {
        $result = (new QRCode([
            'eccLevel' => EccLevel::L,
            'scale' => 5,
            'imageTransparent' => false,
        ]))->render($data);

        if (is_string($result) && str_starts_with($result, 'data:')) {
            return $result;
        }

        return 'data:image/svg+xml;base64,'.base64_encode((string) $result);
    }

    /**
     * ساخت فایل موقت PNG برای ارسال در تلگرام (نیازمند ext-gd) — در صورت عدم پشتیبانی null
     */
    public static function pngTempFile(string $data): ?string
    {
        try {
            $result = (new QRCode([
                'eccLevel' => EccLevel::L,
                'scale' => 6,
                'imageTransparent' => false,
                'outputInterface' => QRGdImagePNG::class,
                'outputBase64' => false,
            ]))->render($data);

            if (! is_string($result)) {
                return null;
            }

            $path = storage_path('app/qr-'.uniqid().'.png');
            file_put_contents($path, $result);

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }
}
