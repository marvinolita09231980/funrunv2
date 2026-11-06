<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subcategory extends Model
{
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
