<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class ParticipantReportPdf
{
    /**
     * @return array{content: string, filename: string}
     */
    public static function age(
        User $user,
        int $year,
        string $ranking = 'all',
        ?string $gender = null,
        ?string $distance = null,
    ): array {
        $report = ParticipantAgeReport::build(
            $user,
            $year,
            $ranking,
            $gender,
            $distance,
        );

        return [
            'content' => Pdf::loadView('pdf.participant-age-report', [
                'report' => $report,
            ])->setPaper('a4', 'landscape')->output(),
            'filename' => implode('-', array_filter([
                'top-10',
                $ranking,
                $gender,
                $distance,
                $year,
            ])).'.pdf',
        ];
    }

    /**
     * @return array{content: string, filename: string}
     */
    public static function group(User $user, int $year, string $type): array
    {
        $report = RegistrationGroupReport::build($user, $year, $type);

        return [
            'content' => Pdf::loadView('pdf.registration-group-report', [
                'report' => $report,
            ])->setPaper('a4', 'landscape')->output(),
            'filename' => "{$type}-runners-{$year}.pdf",
        ];
    }
}
