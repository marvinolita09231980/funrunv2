<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Subcategory extends Model
{
    public static function reportQuery(?int $year = null): Builder
    {
        $year ??= now()->year;
        $directCount = DB::table('participants')
            ->selectRaw('COUNT(*)')
            ->whereColumn('participants.categoryDescription', 'category_allocations.categoryDescription')
            ->where('year', $year)
            ->where(fn ($query) => $query->whereNull('subDescription')->orWhere('subDescription', ''));

        $categories = DB::table('category_allocations')
            ->selectRaw("-id AS id, categoryDescription, '' AS subDescription")
            ->selectSub($directCount, 'direct_count')
            ->addSelect('allocated_participants', 'created_at', 'updated_at')
            ->selectSub(DB::table('subcategories')->selectRaw('COUNT(*)')
                ->whereColumn('subcategories.categoryDescription', 'category_allocations.categoryDescription'), 'subcategory_count');

        $directRows = DB::query()->fromSub($categories, 'categories')
            ->selectRaw('id, categoryDescription, subDescription, CASE WHEN subcategory_count = 0 THEN allocated_participants ELSE direct_count END AS nop, created_at, updated_at')
            ->where(fn ($query) => $query->where('subcategory_count', 0)->orWhere('direct_count', '>', 0));

        $rows = DB::table('subcategories')->select('id', 'categoryDescription', 'subDescription', 'nop', 'created_at', 'updated_at')
            ->unionAll($directRows);

        return static::query()->fromSub($rows, 'subcategories');
    }

     use HasFactory;
    protected $fillable = [
        'categoryDescription',
        'nop',
        'subDescription',
        'username',
    ];

    public function focalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    public function getFocalUserNameAttribute(): string
    {
        return $this->focalUser?->name
            ?? $this->username
            ?? 'Not assigned';
    }

    public function registeredParticipants(?int $year = null): int
    {
        return Participant::query()
            ->where('categoryDescription', $this->categoryDescription)
            ->when(
                strcasecmp((string) $this->categoryDescription, 'OPEN CATEGORY') !== 0,
                fn ($query) => $query->where('subDescription', $this->subDescription),
            )
            ->where('year', $year ?? now()->year)
            ->count();
    }

    public function registrationSlotsRemaining(?int $year = null): int
    {
        return max(0, (int) $this->nop - $this->registeredParticipants($year));
    }

    public function minimumCapacity(?int $year = null): int
    {
        return $this->registeredParticipants($year);
    }

    public function canSetCapacity(int $capacity, ?int $year = null): bool
    {
        return $capacity >= $this->minimumCapacity($year);
    }
}
