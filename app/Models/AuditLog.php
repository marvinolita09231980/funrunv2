<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    protected $fillable = [
        'event',
        'actor_user_id',
        'actor_name',
        'actor_username',
        'impersonated_user_id',
        'impersonated_name',
        'impersonated_username',
        'auditable_type',
        'auditable_id',
        'subject_label',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function getActorDisplayNameAttribute(): string
    {
        $actor = $this->actor_name
            ?? $this->actor_username
            ?? 'System / Public visitor';

        if (blank($this->impersonated_name) && blank($this->impersonated_username)) {
            return $actor;
        }

        $impersonated = $this->impersonated_name ?? $this->impersonated_username;

        return "{$actor} (acting as {$impersonated})";
    }

    public function getSubjectTypeLabelAttribute(): string
    {
        return match ($this->auditable_type) {
            Participant::class => 'Participant',
            Finisher::class => 'Finisher',
            User::class => 'User',
            Subcategory::class => 'Subcategory',
            CategoryAllocation::class => 'Category Allocation',
            OverallAllocation::class => 'Overall Allocation',
            RegistrationSetting::class => 'Registration Setting',
            Feedback::class => 'Feedback',
            default => Str::headline(class_basename($this->auditable_type)),
        };
    }

    public function getChangeSummaryAttribute(): string
    {
        $fields = array_keys(
            $this->event === 'deleted'
                ? ($this->old_values ?? [])
                : ($this->new_values ?? []),
        );

        if ($fields === []) {
            return $this->event === 'deleted' ? 'Record deleted' : 'Record updated';
        }

        $labels = collect($fields)
            ->map(fn (string $field): string => Str::headline($field));

        if ($this->event === 'deleted') {
            return 'Deleted record with '.$labels->count().' captured fields';
        }

        return $labels->join(', ');
    }

    public function changedFields(): array
    {
        $oldValues = $this->old_values ?? [];
        $newValues = $this->new_values ?? [];
        $fields = array_values(array_unique([
            ...array_keys($oldValues),
            ...array_keys($newValues),
        ]));

        return collect($fields)
            ->map(fn (string $field): array => [
                'field' => $field,
                'label' => Str::headline($field),
                'old' => $oldValues[$field] ?? null,
                'new' => $newValues[$field] ?? null,
            ])
            ->all();
    }

    public static function displayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Not set';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                ?: 'Unable to display value';
        }

        return (string) $value;
    }
}
