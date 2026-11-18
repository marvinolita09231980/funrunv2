<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Award extends Model
{
    public const CATEGORY_TOP_FINISHERS = 'Top Finishers';

    public const CATEGORY_SPECIAL_AWARDS = 'Special Awards';

    public const DISTANCE_3KM = '3Km';

    public const DISTANCE_5KM = '5Km';

    public const DISTANCE_10KM = '10Km';

    protected $fillable = [
        'name',
        'category',
        'distance_category',
        'is_active',
    ];

    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_TOP_FINISHERS => self::CATEGORY_TOP_FINISHERS,
            self::CATEGORY_SPECIAL_AWARDS => self::CATEGORY_SPECIAL_AWARDS,
        ];
    }

    public static function distanceOptions(): array
    {
        return [
            self::DISTANCE_3KM => self::DISTANCE_3KM,
            self::DISTANCE_5KM => self::DISTANCE_5KM,
            self::DISTANCE_10KM => self::DISTANCE_10KM,
        ];
    }

    public static function groupedOptions(
        ?int $includeAwardId = null,
        ?string $distanceCategory = null,
    ): array
    {
        $awardDistance = self::normalizeDistance($distanceCategory);
        $awards = self::query()
            ->where(function ($query) use ($includeAwardId): void {
                $query->where('is_active', true);

                if ($includeAwardId) {
                    $query->orWhere(
                        $query->getModel()->getQualifiedKeyName(),
                        $includeAwardId,
                    );
                }
            })
            ->get()
            ->filter(
                fn (Award $award): bool => ! $awardDistance
                    || (int) $award->getKey() === $includeAwardId
                    || $award->distance_category === $awardDistance,
            );

        return collect(self::categoryOptions())
            ->mapWithKeys(function (string $label, string $category) use ($awards): array {
                $options = $awards
                    ->where('category', $category)
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->pluck('name', 'id')
                    ->all();

                return $options === [] ? [] : [$label => $options];
            })
            ->all();
    }

    public static function normalizeDistance(?string $distance): ?string
    {
        $normalized = strtolower((string) preg_replace('/\s+/', '', trim((string) $distance)));

        return match ($normalized) {
            '3k', '3km' => self::DISTANCE_3KM,
            '5k', '5km' => self::DISTANCE_5KM,
            '10k', '10km' => self::DISTANCE_10KM,
            default => null,
        };
    }

    public static function isSelectableForDistance(mixed $awardId, ?string $distanceCategory): bool
    {
        $awardDistance = self::normalizeDistance($distanceCategory);

        if (blank($awardId) || ! $awardDistance) {
            return false;
        }

        return self::query()
            ->whereKey($awardId)
            ->where('is_active', true)
            ->where('distance_category', $awardDistance)
            ->exists();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function finishers(): HasMany
    {
        return $this->hasMany(Finisher::class);
    }
}
