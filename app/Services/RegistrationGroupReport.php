<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\ParticipantReportExclusion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class RegistrationGroupReport
{
    public static function build(User $user, int $year, string $type): array
    {
        $reportKey = ParticipantReportExclusion::groupKey($type);
        $query = self::candidateQuery($year, $type)
            ->whereDoesntHave(
                'reportExclusions',
                fn (Builder $query): Builder => $query
                    ->where('year', $year)
                    ->where('report_key', $reportKey),
            );

        $groups = $query->orderBy('id')->get()->groupBy(function (Participant $participant): string {
            $number = trim((string) $participant->participantNumber);

            return json_encode([$participant->categoryDescription, $number !== '' && $number !== '0' ? $number : 'record-'.$participant->id]);
        })->map(function ($members): array {
            $first = $members->first();

            return [
                'reference' => filled($first->participantNumber) && $first->participantNumber !== '0' ? $first->participantNumber : 'Not recorded',
                'category' => $first->categoryDescription,
                'members' => $members->map(fn (Participant $member): array => [
                    'participant_id' => $member->getKey(),
                    'name' => trim(implode(' ', array_filter([$member->firstName, $member->middleInitial, $member->lastName]))),
                    'birth_date' => filled($member->birthDate) && $member->birthDate !== '0000-00-00' ? Carbon::parse($member->birthDate)->format('m/d/Y') : 'Not recorded',
                    'phone_number' => filled($member->contactNumber)
                        ? trim((string) $member->contactNumber)
                        : 'Not provided',
                    'gender' => ucfirst((string) $member->gender),
                    'distance' => Participant::distanceLabel($member->distanceCategory),
                    'shirt_size' => strtoupper((string) $member->shirtSize),
                ])->all(),
            ];
        })->values()->all();

        return [
            'title' => $type === 'couple' ? 'Couple Runners' : 'Family Runners',
            'year' => $year, 'groups' => $groups,
            'total_runners' => array_sum(array_map(fn (array $group): int => count($group['members']), $groups)),
            'generated_at' => now('Asia/Shanghai')->format('m/d/Y h:i A'),
        ];
    }

    public static function candidateQuery(int $year, string $type): Builder
    {
        $query = Participant::query()->where('year', $year);

        if ($type === 'couple') {
            return $query->where(
                fn (Builder $query): Builder => $query
                    ->where('couple', true)
                    ->orWhereRaw("LOWER(TRIM(subDescription)) = 'couple'"),
            );
        }

        return $query
            ->whereRaw("LOWER(TRIM(subDescription)) = 'family'")
            ->where('couple', false);
    }
}
