<?php

namespace App\Services;

use App\Filament\Resources\Participants\Tables\ParticipantsTable;
use App\Models\Participant;
use App\Models\User;
use Carbon\Carbon;

class RegistrationGroupReport
{
    public static function build(User $user, int $year, string $type): array
    {
        $query = Participant::query()->where('year', $year);
        ParticipantsTable::applyVisibilityForUser($query, $user);

        if ($type === 'couple') {
            $query->where(fn ($query) => $query->where('couple', true)->orWhereRaw("LOWER(TRIM(subDescription)) = 'couple'"));
        } else {
            $query->whereRaw("LOWER(TRIM(subDescription)) = 'family'")->where('couple', false);
        }

        if ($user->isAdministrativeOfficer() && ! $user->hasAnyRole(['admin', 'superadmin', 'super_admin'])) {
            $query->whereIn('categoryDescription', $user->administeredCategoryNames());
        }

        $groups = $query->orderBy('id')->get()->groupBy(function (Participant $participant): string {
            $number = trim((string) $participant->participantNumber);

            return json_encode([$participant->categoryDescription, $number !== '' && $number !== '0' ? $number : 'record-'.$participant->id]);
        })->map(function ($members): array {
            $first = $members->first();

            return [
                'reference' => filled($first->participantNumber) && $first->participantNumber !== '0' ? $first->participantNumber : 'Not recorded',
                'category' => $first->categoryDescription,
                'members' => $members->map(fn (Participant $member): array => [
                    'name' => trim(implode(' ', array_filter([$member->firstName, $member->middleInitial, $member->lastName]))),
                    'birth_date' => filled($member->birthDate) && $member->birthDate !== '0000-00-00' ? Carbon::parse($member->birthDate)->format('m/d/Y') : 'Not recorded',
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
}
