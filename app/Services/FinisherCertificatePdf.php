<?php

namespace App\Services;

use App\Models\Participant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class FinisherCertificatePdf
{
    /**
     * @return array{content: string, filename: string}
     */
    public static function make(Participant $participant): array
    {
        $fullName = trim(collect([
            $participant->firstName,
            filled($participant->middleInitial)
                ? rtrim((string) $participant->middleInitial, '.').'.'
                : null,
            $participant->lastName,
        ])->filter()->join(' '));

        $templatePath = public_path('images/certificates/finisher-certificate-2026.jpg');

        abort_unless(is_file($templatePath), 500, 'The certificate template is unavailable.');

        $templateDataUri = 'data:image/jpeg;base64,'.base64_encode(
            (string) file_get_contents($templatePath),
        );

        $safeName = Str::slug($fullName) ?: 'runner';

        return [
            'content' => Pdf::loadView('pdf.finisher-certificate', [
                'distance' => self::distanceLabel($participant->distanceCategory),
                'fullName' => $fullName,
                'nameFontSize' => self::nameFontSize($fullName),
                'templateDataUri' => $templateDataUri,
            ])->setPaper('a4', 'landscape')->output(),
            'filename' => "{$safeName}-finisher-certificate-{$participant->year}.pdf",
        ];
    }

    public static function distanceLabel(?string $distance): string
    {
        $normalized = strtolower((string) preg_replace('/\s+/', '', trim((string) $distance)));

        return match ($normalized) {
            '3k', '3km' => 'Rave Run (3 km)',
            '5k', '5km' => '5 km',
            '10k', '10km' => '10 km',
            default => abort(422, 'The participant has an invalid distance category.'),
        };
    }

    private static function nameFontSize(string $fullName): int
    {
        return match (true) {
            mb_strlen($fullName) <= 30 => 30,
            mb_strlen($fullName) <= 40 => 25,
            mb_strlen($fullName) <= 50 => 21,
            default => 18,
        };
    }
}
