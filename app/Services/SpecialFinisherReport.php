<?php

namespace App\Services;

use App\Models\Finisher;
use App\Models\Participant;
use App\Models\ParticipantReportExclusion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class SpecialFinisherReport
{
    /** @return array<string, mixed> */
    public static function build(User $user, int $year, string $type, string $gender): array
    {
        $reportKey = ParticipantReportExclusion::finisherKey($type, $gender);
        $finishers = self::candidateQuery($year, $type, $gender)
            ->whereHas(
                'participant',
                fn (Builder $query): Builder => $query->whereDoesntHave(
                    'reportExclusions',
                    fn (Builder $query): Builder => $query
                        ->where('year', $year)
                        ->where('report_key', $reportKey),
                ),
            )
            ->with('participant')
            ->orderBy('finish_time')
            ->orderBy('id')
            ->limit(10)
            ->get()
            ->map(function (Finisher $finisher, int $index): array {
                $participant = $finisher->participant;

                return [
                    'participant_id' => $finisher->participants_id,
                    'rank' => $index + 1,
                    'name' => $finisher->runner_name,
                    'race_bib' => $finisher->racebib,
                    'gender' => self::genderLabel($participant?->gender ?? $finisher->gender),
                    'distance' => Participant::distanceLabel(
                        $participant?->distanceCategory ?? $finisher->distanceCategory,
                    ),
                    'finish_time' => Carbon::parse($finisher->finish_time)->format('h:i:s A'),
                    'category' => $participant?->categoryDescription
                        ?? $finisher->categoryDescription
                        ?? 'Not recorded',
                    'subcategory' => $participant?->subDescription
                        ?: ($finisher->subDescription ?: 'No subcategory'),
                ];
            })
            ->all();

        return [
            'title' => self::title($type, $gender),
            'year' => $year,
            'type' => $type,
            'gender' => $gender,
            'generated_at' => now('Asia/Shanghai')->format('m/d/Y h:i A'),
            'finishers' => $finishers,
        ];
    }

    public static function candidateQuery(int $year, string $type, string $gender): Builder
    {
        return Finisher::query()
            ->whereNotNull('finish_time')
            ->whereHas('participant', function (Builder $query) use ($year, $type, $gender): void {
                $query
                    ->where('year', $year)
                    ->whereRaw(
                        'LOWER(TRIM(gender)) IN (?, ?)',
                        [$gender, $gender === 'male' ? 'm' : 'f'],
                    )
                    ->when(
                        $type === 'rpwuds',
                        fn (Builder $query): Builder => $query->where('rpwuds', true),
                        fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                            $query
                                ->whereRaw("LOWER(TRIM(categoryDescription)) IN ('padac', 'padac members', 'pnp')")
                                ->orWhere(function (Builder $query): void {
                                    $query
                                        ->whereRaw("LOWER(TRIM(categoryDescription)) = 'ona'")
                                        ->whereRaw("LOWER(TRIM(subDescription)) = 'pdea'");
                                });
                        }),
                    );
            });
    }

    public static function title(string $type, string $gender): string
    {
        $group = $type === 'padac' ? 'PADAC' : 'RPWUDS';

        return 'Top 10 '.$group.' '.ucfirst($gender).' Finishers';
    }

    private static function genderLabel(?string $gender): string
    {
        return match (strtolower(trim((string) $gender))) {
            'm', 'male' => 'Male',
            'f', 'female' => 'Female',
            default => ucfirst(trim((string) $gender)),
        };
    }
}
