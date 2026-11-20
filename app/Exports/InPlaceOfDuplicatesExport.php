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

    public function __construct(private readonly int $year)
    {
    }

    public function replacementLogs(): Collection
    {
        return $this->logs ??= AuditLog::query()
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
            ->map(fn (AuditLog $log): array => [
                'original' => [
                    ...$this->participantValues($log->old_values ?? []),
                    'Assigned Focal / AO' => $this->assignedFocalOrAo($log->old_values ?? []),
                ],
                'replacement' => $this->participantValues($log->new_values ?? []),
            ])
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
