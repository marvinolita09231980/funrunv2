<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'username');
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

   
}
