<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\User;
use Carbon\CarbonImmutable;

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

                    $query = Participant::query()->where('year', $year)
                        ->whereRaw('LOWER(TRIM(gender)) IN (?, ?)', [$sex, $sex === 'male' ? 'm' : 'f'])
                        ->whereRaw("REPLACE(LOWER(TRIM(distanceCategory)), ' ', '') IN (?, ?)", [$race, str_replace('km', 'k', $race)])
                        ->whereNotNull('birthDate')->where('birthDate', '>', '0000-00-00')
                        ->whereDate('birthDate', '<=', min($asOf->toDateString(), now()->toDateString()));

                    $participants = $query->orderBy('birthDate', $order === 'oldest' ? 'asc' : 'desc')
                        ->orderBy('lastName')->orderBy('firstName')->orderBy('id')->limit(10)->get()
                        ->map(fn (Participant $participant, int $index): array => [
                            'rank' => $index + 1,
                            'name' => trim(implode(' ', array_filter([$participant->firstName, $participant->middleInitial, $participant->lastName]))),
                            'birth_date' => CarbonImmutable::parse($participant->birthDate)->format('m/d/Y'),
                            'age' => (int) CarbonImmutable::parse($participant->birthDate)->diffInYears($asOf),
                            'category' => $participant->categoryDescription,
                            'subcategory' => $participant->subDescription ?: 'No subcategory',
                        ])->all();

                    $sections[] = ['title' => "Top 10 {$orderLabel} {$genderLabel} — {$distanceLabel}", 'participants' => $participants];
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
}
