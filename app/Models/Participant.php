<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Participant extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'username');
    }

    public function reportExclusions(): HasMany
    {
        return $this->hasMany(ParticipantReportExclusion::class);
    }

    public function registrationMembers(): HasMany
    {
        return $this->hasMany(self::class, 'participantNumber', 'participantNumber')
            ->whereNotNull('participantNumber')
            ->where('participantNumber', '!=', '0');
    }

    public function getCreatorNameAttribute(): string
    {
        if ($this->isSelfRegistered()) {
            return 'Registrant (Self-Registered)';
        }

        return $this->creator?->name
            ?? $this->created_by
            ?? 'Not recorded';
    }

    public function isSelfRegistered(): bool
    {
        return strcasecmp(trim((string) $this->created_by), 'opencategory') === 0;
    }

    public static function distanceLabel(?string $distance): string
    {
        $value = trim((string) $distance);
        $normalized = strtolower((string) preg_replace('/\s+/', '', $value));

        return match ($normalized) {
            '3k', '3km' => 'Rave Run (3K)',
            default => $value !== '' ? strtoupper($value) : 'Not provided',
        };
    }

    public function getDistanceLabelAttribute(): string
    {
        return self::distanceLabel($this->distanceCategory);
    }

    public function isPadacMember(): bool
    {
        $category = strtolower(trim((string) $this->categoryDescription));
        $subcategory = strtolower(trim((string) $this->subDescription));

        return in_array($category, ['padac', 'padac member', 'padac members', 'pnp'], true)
            || ($category === 'ona' && $subcategory === 'pdea');
    }

    public function getRelatedRunnersLabelAttribute(): ?string
    {
        $participantNumber = trim((string) $this->participantNumber);

        if ($participantNumber === '' || $participantNumber === '0') {
            return null;
        }

        $names = $this->relationshipGroupMembers()
            ->where('id', '!=', $this->getKey())
            ->sortBy(fn (Participant $participant): string => strtolower(
                $participant->lastName.' '.$participant->firstName,
            ))
            ->map(fn (Participant $participant): string => trim(collect([
                $participant->firstName,
                filled($participant->middleInitial)
                    ? rtrim((string) $participant->middleInitial, '.').'.'
                    : null,
                $participant->lastName,
            ])->filter()->join(' ')))
            ->filter()
            ->values();

        return $names->isEmpty() ? null : $names->join(', ');
    }

    public function relationshipGroupMembers(): Collection
    {
        $participantNumber = trim((string) $this->participantNumber);

        if ($participantNumber === '' || $participantNumber === '0') {
            return collect([$this]);
        }

        return $this->registrationMembers
            ->where('year', $this->year)
            ->where('categoryDescription', $this->categoryDescription)
            ->sortBy('id')
            ->values();
    }

    public function getRelationshipGroupMarkerAttribute(): ?string
    {
        $participantNumber = trim((string) $this->participantNumber);

        if ($participantNumber === '' || $participantNumber === '0') {
            return null;
        }

        $members = $this->relationshipGroupMembers();
        $position = $members->search(
            fn (Participant $participant): bool => $participant->getKey() === $this->getKey(),
        );

        $symbol = match (true) {
            $members->count() <= 1 || $position === false => '•',
            $position === 0 => '┌─',
            $position === $members->count() - 1 => '└─',
            default => '├─',
        };

        return "{$symbol} #{$participantNumber}";
    }

    public function getRelationshipGroupSizeAttribute(): int
    {
        return $this->relationshipGroupMembers()->count();
    }

    public function scopeInRunnerGroup(Builder $query, string $group): Builder
    {
        if ($group === 'rpwuds') {
            return $query->where('rpwuds', true);
        }

        if ($group === 'padac') {
            return $query->where(function (Builder $query): void {
                $query
                    ->whereRaw(
                        "LOWER(TRIM(categoryDescription)) IN ('padac', 'padac member', 'padac members', 'pnp')"
                    )
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->whereRaw("LOWER(TRIM(categoryDescription)) = 'ona'")
                            ->whereRaw("LOWER(TRIM(subDescription)) = 'pdea'");
                    });
            });
        }

        if ($group === 'couple') {
            return $query->where(function (Builder $query): void {
                $query
                    ->where('couple', true)
                    ->orWhereRaw("LOWER(TRIM(subDescription)) = 'couple'");
            });
        }

        if ($group === 'family') {
            return $query
                ->where('couple', false)
                ->whereRaw("LOWER(TRIM(subDescription)) = 'family'");
        }

        return $query;
    }
}
