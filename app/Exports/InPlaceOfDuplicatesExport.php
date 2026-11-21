<?php

namespace App\Exports;

use App\Filament\Pages\PotentialDuplicates;
use App\Models\AuditLog;
use App\Models\CategoryAllocation;
use App\Models\Participant;
use App\Models\Subcategory;
use Illuminate\Support\Collection;

class InPlaceOfDuplicatesExport
{
    private ?Collection $logs = null;

    private ?Collection $activeParticipants = null;

    public function __construct(private readonly int $year)
    {
    }

    public function replacementLogs(): Collection
    {
        if ($this->logs !== null) {
            return $this->logs;
        }

        $logs = AuditLog::query()
            ->where('event', 'replaced')
            ->where('auditable_type', Participant::class)
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->filter(function (AuditLog $log): bool {
                $newValues = $log->new_values ?? [];
                $oldValues = $log->old_values ?? [];

                return (int) ($newValues['year'] ?? $oldValues['year'] ?? 0) === $this->year
                    && ($newValues['categoryDescription'] ?? null) === PotentialDuplicates::REPLACEMENT_CATEGORY;
            })
            ->unique(fn (AuditLog $log): string => (string) $log->auditable_id)
            ->values();

        $this->activeParticipants = Participant::query()
            ->whereKey($logs->pluck('auditable_id')->filter()->all())
            ->get()
            ->keyBy(fn (Participant $participant): string => (string) $participant->getKey());

        return $this->logs = $logs
            ->filter(function (AuditLog $log): bool {
                $participant = $this->activeParticipants?->get((string) $log->auditable_id);

                return $participant instanceof Participant
                    && $participant->categoryDescription === PotentialDuplicates::REPLACEMENT_CATEGORY;
            })
            ->unique(function (AuditLog $log): string {
                /** @var Participant $participant */
                $participant = $this->activeParticipants->get((string) $log->auditable_id);
                $name = collect([
                    $participant->firstName,
                    $participant->middleInitial,
                    $participant->lastName,
                ])->filter()->join(' ');

                return Participant::normalizedIdentityName($name)
                    ?: 'participant-'.(string) $participant->getKey();
            })
            ->values();
    }

    /**
     * @return Collection<int, array{
     *     original: array<string, string>,
     *     replacement: array<string, string>
     * }>
     */
    public function comparisonRows(): Collection
    {
        return $this->replacementLogs()
            ->map(function (AuditLog $log): array {
                /** @var Participant $activeReplacement */
                $activeReplacement = $this->activeParticipants->get((string) $log->auditable_id);

                return [
                    'original' => [
                        ...$this->participantValues($log->old_values ?? []),
                        'Assigned Focal / AO' => $this->assignedFocalOrAo($log->old_values ?? []),
                    ],
                    'replacement' => $this->participantValues($activeReplacement->getAttributes()),
                ];
            })
            ->values();
    }

    /** @param array<string, mixed> $values */
    private function participantValues(array $values): array
    {
        return [
            'Name' => collect([
                $this->text($values['firstName'] ?? null),
                filled($values['middleInitial'] ?? null)
                    ? rtrim($this->text($values['middleInitial']), '.').'.'
                    : null,
                $this->text($values['lastName'] ?? null),
            ])->filter()->join(' '),
            'Gender' => ucfirst($this->text($values['gender'] ?? null)),
            'Category' => $this->text($values['categoryDescription'] ?? null),
            'Subcategory' => $this->text($values['subDescription'] ?? null),
        ];
    }

    private function text(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    /** @param array<string, mixed> $values */
    private function assignedFocalOrAo(array $values): string
    {
        $category = $this->text($values['categoryDescription'] ?? null);
        $subcategory = $this->text($values['subDescription'] ?? null);

        if ($category === '') {
            return 'Not assigned';
        }

        if ($subcategory !== '') {
            $subcategoryAssignment = Subcategory::query()
                ->with('focalUser')
                ->whereRaw('LOWER(TRIM(categoryDescription)) = ?', [mb_strtolower($category)])
                ->whereRaw('LOWER(TRIM(subDescription)) = ?', [mb_strtolower($subcategory)])
                ->whereNotNull('username')
                ->where('username', '!=', '')
                ->first();

            if ($subcategoryAssignment) {
                return $subcategoryAssignment->focal_user_name;
            }
        }

        $assignedAoNames = CategoryAllocation::query()
            ->with('administrativeOfficers')
            ->whereRaw('LOWER(TRIM(categoryDescription)) = ?', [mb_strtolower($category)])
            ->first()?->assigned_ao_names;

        return filled($assignedAoNames) ? $assignedAoNames : 'Not assigned';
    }
}
