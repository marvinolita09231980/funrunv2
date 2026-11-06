<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryAllocation extends Model
{
    protected $fillable = [
        'categoryDescription',
        'allocated_participants',
    ];

    protected function casts(): array
    {
        return [
            'allocated_participants' => 'integer',
        ];
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(
            Subcategory::class,
            'categoryDescription',
            'categoryDescription',
        );
    }

    public function administrativeOfficers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'category_allocation_user',
            'category_allocation_id',
            'user_id',
        )->whereHas(
            'roles',
            fn ($query) => $query->whereIn('name', ['administrative officer', 'ao']),
        )->withTimestamps();
    }

    public function getAssignedAoNamesAttribute(): string
    {
        return $this->administrativeOfficers
            ->pluck('name')
            ->filter()
            ->sort()
            ->join(', ');
    }

    public function distributedParticipants(?int $exceptSubcategoryId = null): int
    {
        return (int) $this->subcategories()
            ->when(
                $exceptSubcategoryId,
                fn ($query) => $query->whereKeyNot($exceptSubcategoryId),
            )
            ->sum('nop');
    }

    public function remainingParticipants(): int
    {
        return max(0, $this->allocated_participants - $this->distributedParticipants());
    }

    public function registeredParticipants(?int $year = null): int
    {
        return Participant::query()
            ->where('categoryDescription', $this->categoryDescription)
            ->where('year', $year ?? now()->year)
            ->count();
    }

    public function registrationSlotsRemaining(?int $year = null): int
    {
        return max(
            0,
            $this->allocated_participants - $this->registeredParticipants($year),
        );
    }

    public function minimumAllocation(?int $year = null): int
    {
        return max(
            $this->distributedParticipants(),
            $this->registeredParticipants($year),
        );
    }

    public function canSetAllocation(int $allocation, ?int $year = null): bool
    {
        return $allocation >= $this->minimumAllocation($year);
    }

    public function accommodatesSubcategoryCapacity(
        int $capacity,
        ?int $exceptSubcategoryId = null,
    ): bool {
        return ($this->distributedParticipants($exceptSubcategoryId) + $capacity)
            <= $this->allocated_participants;
    }
}
