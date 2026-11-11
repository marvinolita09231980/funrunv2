<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\ParticipantReportExclusion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ParticipantAgeReport
{
    public static function build(User $user, int $year, string $ranking = 'all', ?string $gender = null, ?string $distance = null): array
    {
        $asOf = $year === (int) now()->year
            ? CarbonImmutable::today()
            : CarbonImmutable::create($year, 12, 31)->startOfDay();
        $sections = [];

        foreach (['oldest' => 'Oldest', 'youngest' => 'Youngest'] as $order => $orderLabel) {
            if ($ranking !== 'all' && $ranking !== $order) {
                continue;
            }

            foreach (['male' => 'Male', 'female' => 'Female'] as $sex => $genderLabel) {
                foreach (['5km' => '5K', '10km' => '10K'] as $race => $distanceLabel) {
                    if (($gender !== null && $gender !== $sex) || ($distance !== null && $distance !== $race)) {
                        continue;
                    }

                    $reportKey = ParticipantReportExclusion::ageKey($order, $sex, $race);
                    $query = self::candidateQuery($year, $sex, $race, $asOf)
                        ->whereDoesntHave(
                            'reportExclusions',
                            fn (Builder $query): Builder => $query
                                ->where('year', $year)
                                ->where('report_key', $reportKey),
                        );

                    $participants = $query->orderBy('birthDate', $order === 'oldest' ? 'asc' : 'desc')
                        ->orderBy('lastName')->orderBy('firstName')->orderBy('id')->limit(10)->get()
                        ->map(fn (Participant $participant, int $index): array => [
                            'participant_id' => $participant->getKey(),
                            'rank' => $index + 1,
                            'name' => trim(implode(' ', array_filter([$participant->firstName, $participant->middleInitial, $participant->lastName]))),
                            'birth_date' => CarbonImmutable::parse($participant->birthDate)->format('m/d/Y'),
                            'age' => (int) CarbonImmutable::parse($participant->birthDate)->diffInYears($asOf),
                            'phone_number' => filled($participant->contactNumber)
                                ? trim((string) $participant->contactNumber)
                                : 'Not provided',
                            'category' => $participant->categoryDescription,
                            'subcategory' => $participant->subDescription ?: 'No subcategory',
                        ])->all();

                    $sections[] = [
                        'title' => "Top 10 {$orderLabel} {$genderLabel} — {$distanceLabel}",
                        'ranking' => $order,
                        'gender' => $sex,
                        'distance' => $race,
                        'participants' => $participants,
                    ];
                }
            }
        }

        return [
            'year' => $year, 'as_of' => $asOf->format('m/d/Y'),
            'generated_at' => now('Asia/Shanghai')->format('m/d/Y h:i A'),
            'scope' => 'All categories',
            'sections' => $sections,
        ];
    }

    public static function candidateQuery(
        int $year,
        string $gender,
        string $distance,
        ?CarbonImmutable $asOf = null,
    ): Builder {
        $asOf ??= $year === (int) now()->year
            ? CarbonImmutable::today()
            : CarbonImmutable::create($year, 12, 31)->startOfDay();

        return Participant::query()
            ->where('year', $year)
            ->whereRaw(
                'LOWER(TRIM(gender)) IN (?, ?)',
                [$gender, $gender === 'male' ? 'm' : 'f'],
            )
            ->whereRaw(
                "REPLACE(LOWER(TRIM(distanceCategory)), ' ', '') IN (?, ?)",
                [$distance, str_replace('km', 'k', $distance)],
            )
            ->whereNotNull('birthDate')
            ->where('birthDate', '>', '0000-00-00')
            ->whereDate(
                'birthDate',
                '<=',
                min($asOf->toDateString(), now()->toDateString()),
            );
    }
}
