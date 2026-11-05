<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverallAllocation extends Model
{
    protected $fillable = [
        'total_participants',
    ];

    protected function casts(): array
    {
        return [
            'total_participants' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['id' => 1],
            ['total_participants' => (int) CategoryAllocation::query()->sum('allocated_participants')],
        );
    }

    public function assignedParticipants(?int $exceptCategoryAllocationId = null): int
    {
        return (int) CategoryAllocation::query()
            ->when(
                $exceptCategoryAllocationId,
                fn ($query) => $query->whereKeyNot($exceptCategoryAllocationId),
            )
            ->sum('allocated_participants');
    }

    public function remainingParticipants(): int
    {
        return max(0, $this->total_participants - $this->assignedParticipants());
    }

    public function availableForCategory(?int $exceptCategoryAllocationId = null): int
    {
        return max(
            0,
            $this->total_participants - $this->assignedParticipants($exceptCategoryAllocationId),
        );
    }

    public function canSetTotal(int $total): bool
    {
        return $total >= $this->assignedParticipants();
    }

    public function canAllocateToCategory(
        int $allocation,
        ?int $exceptCategoryAllocationId = null,
    ): bool {
        return $allocation <= $this->availableForCategory($exceptCategoryAllocationId);
    }
}
