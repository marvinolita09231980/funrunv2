<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

class Finisher extends Model
{
    protected $table = 'finishers'; // optional if table is 'finishers'
    protected $fillable = [
        'participants_id',
        'firstName',
        'lastName',
        'middleInitial',
        'categoryDescription',
        'subDescription',
        'gender',
        'birthDate',
        'distanceCategory',
        'racebib',
        'finish_time',
        'created_by',
        'finisher_rank',
        'award_id',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participants_id');
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function awards(): BelongsToMany
    {
        return $this->belongsToMany(Award::class)->withTimestamps();
    }

    protected static function booted(): void
    {
        static::saved(function (Finisher $finisher): void {
            if (
                ! Schema::hasTable('award_finisher')
                || (! $finisher->wasRecentlyCreated && ! $finisher->wasChanged('award_id'))
            ) {
                return;
            }

            $previousAwardId = $finisher->getOriginal('award_id');

            if (filled($previousAwardId)) {
                $finisher->awards()->detach($previousAwardId);
            }

            if (filled($finisher->award_id)) {
                $finisher->awards()->syncWithoutDetaching([$finisher->award_id]);
            }
        });
    }

    public static function awardHolder(mixed $awardId, ?int $exceptFinisherId = null): ?self
    {
        if (blank($awardId)) {
            return null;
        }

        return self::query()
            ->whereHas('awards', fn ($query): mixed => $query->whereKey($awardId))
            ->when(
                $exceptFinisherId,
                fn ($query): mixed => $query->whereKeyNot($exceptFinisherId),
            )
            ->orderBy('id')
            ->first();
    }

    public function awardInCategory(?string $category): ?Award
    {
        if (blank($category)) {
            return null;
        }

        return $this->awards()
            ->where('category', $category)
            ->orderBy('awards.id')
            ->first();
    }

    public function assignAward(Award $award): void
    {
        $finisherDistance = Award::normalizeDistance($this->distanceCategory);
        $awardDistance = Award::normalizeDistance($award->distance_category);

        if (! $finisherDistance || $finisherDistance !== $awardDistance) {
            throw new \DomainException(
                'A finisher can receive awards only from their registered distance category.'
            );
        }

        $categoryAward = $this->awardInCategory($award->category);

        if ($categoryAward && ! $categoryAward->is($award)) {
            throw new \DomainException(
                "A finisher can receive only one award in the {$award->category} category."
            );
        }

        $this->awards()->syncWithoutDetaching([$award->getKey()]);

        if (blank($this->award_id)) {
            $this->updateQuietly(['award_id' => $award->getKey()]);
        }

        $this->unsetRelation('awards');
    }

    public function removeAward(Award|int $award): void
    {
        $awardId = $award instanceof Award ? $award->getKey() : $award;

        $this->awards()->detach($awardId);

        if ((int) $this->award_id === (int) $awardId) {
            $this->updateQuietly([
                'award_id' => $this->awards()->orderBy('awards.id')->value('awards.id'),
            ]);
        }

        $this->unsetRelation('awards');
    }

    public function getAwardNamesAttribute(): string
    {
        return $this->awards
            ->sortBy(fn (Award $award): array => [$award->category, $award->name])
            ->pluck('name')
            ->join(', ');
    }

    public function getRunnerNameAttribute(): string
    {
        return collect([
            $this->firstName,
            filled($this->middleInitial)
                ? rtrim((string) $this->middleInitial, '.').'.'
                : null,
            $this->lastName,
        ])->filter()->join(' ');
    }

    public static function raceBibPrefixForDistance(?string $distance): ?string
    {
        $normalized = strtolower((string) preg_replace('/\s+/', '', trim((string) $distance)));

        return match ($normalized) {
            '3k', '3km' => '3',
            '5k', '5km' => '5',
            '10k', '10km' => '1',
            default => null,
        };
    }
     
    public function getRankAttribute()
    {
        // Get all finishers for this distance
        $finishers = self::where('distanceCategory', $this->distanceCategory)
            ->whereNotNull('finish_time')
            ->orderBy('finish_time', 'asc')
            ->pluck('id')
            ->toArray();

        $position = array_search($this->id, $finishers);

        return $position !== false ? $position + 1 : null;
    }

}
