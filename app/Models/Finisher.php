<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public static function awardHolder(mixed $awardId, ?int $exceptFinisherId = null): ?self
    {
        if (blank($awardId)) {
            return null;
        }

        return self::query()
            ->where('award_id', $awardId)
            ->when(
                $exceptFinisherId,
                fn ($query): mixed => $query->whereKeyNot($exceptFinisherId),
            )
            ->orderBy('id')
            ->first();
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
