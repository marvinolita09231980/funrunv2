<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantReportExclusion extends Model
{
    protected $fillable = [
        'participant_id',
        'year',
        'report_key',
        'excluded_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function excludedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'excluded_by');
    }

    public static function ageKey(string $ranking, string $gender, string $distance): string
    {
        return "age.{$ranking}.{$gender}.{$distance}";
    }

    public static function groupKey(string $type): string
    {
        return "group.{$type}";
    }
}
