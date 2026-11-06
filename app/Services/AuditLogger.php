<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CategoryAllocation;
use App\Models\Feedback;
use App\Models\Finisher;
use App\Models\OverallAllocation;
use App\Models\Participant;
use App\Models\RegistrationSetting;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AuditLogger
{
    private static ?bool $tableExists = null;

    public static function recordUpdated(mixed $model): void
    {
        if (! self::shouldAudit($model)) {
            return;
        }

        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $oldValues = [];

        foreach (array_keys($changes) as $field) {
            $oldValues[$field] = $model->getRawOriginal($field);
        }

        self::store(
            event: 'updated',
            model: $model,
            oldValues: $oldValues,
            newValues: $changes,
        );
    }

    public static function recordDeleted(mixed $model): void
    {
        if (! self::shouldAudit($model)) {
            return;
        }

        $values = $model->getAttributes();
        unset($values['created_at'], $values['updated_at']);

        self::store(
            event: 'deleted',
            model: $model,
            oldValues: $values,
            newValues: [],
        );
    }

    private static function shouldAudit(mixed $model): bool
    {
        return $model instanceof Model
            && ! $model instanceof AuditLog
            && str_starts_with($model::class, 'App\\Models\\')
            && (self::$tableExists ??= Schema::hasTable('audit_logs'));
    }

    private static function store(
        string $event,
        Model $model,
        array $oldValues,
        array $newValues,
    ): void {
        [$actor, $impersonated] = self::resolveActor();

        AuditLog::query()->create([
            'event' => $event,
            'actor_user_id' => $actor?->getAuthIdentifier(),
            'actor_name' => $actor?->name,
            'actor_username' => $actor?->username,
            'impersonated_user_id' => $impersonated?->getAuthIdentifier(),
            'impersonated_name' => $impersonated?->name,
            'impersonated_username' => $impersonated?->username,
            'auditable_type' => $model::class,
            'auditable_id' => filled($model->getKey()) ? (string) $model->getKey() : null,
            'subject_label' => self::subjectLabel($model),
            'old_values' => self::sanitize($oldValues),
            'new_values' => self::sanitize($newValues),
            'ip_address' => app()->bound('request') ? request()->ip() : null,
            'user_agent' => app()->bound('request') ? request()->userAgent() : null,
        ]);
    }

    private static function resolveActor(): array
    {
        $currentUser = Auth::user();

        try {
            if (app()->bound('impersonate') && app('impersonate')->isImpersonating()) {
                return [app('impersonate')->getImpersonator(), $currentUser];
            }
        } catch (Throwable) {
            // Fall back to the currently authenticated user when impersonation
            // state cannot be resolved.
        }

        return [$currentUser, null];
    }

    private static function subjectLabel(Model $model): string
    {
        return match (true) {
            $model instanceof Participant,
            $model instanceof Finisher => collect([
                $model->firstName,
                $model->middleInitial,
                $model->lastName,
            ])->filter()->join(' '),
            $model instanceof User => collect([
                $model->name,
                filled($model->username) ? "({$model->username})" : null,
            ])->filter()->join(' '),
            $model instanceof Subcategory => collect([
                $model->categoryDescription,
                $model->subDescription,
            ])->filter()->join(' — '),
            $model instanceof CategoryAllocation => (string) $model->categoryDescription,
            $model instanceof Feedback => (string) ($model->full_name
                ?? collect([$model->firstName, $model->lastName])->filter()->join(' ')),
            $model instanceof OverallAllocation => 'Overall Participant Allocation',
            $model instanceof RegistrationSetting => 'Public Registration Settings',
            default => Str::headline(class_basename($model)).' #'.$model->getKey(),
        };
    }

    private static function sanitize(array $values): array
    {
        return collect($values)
            ->mapWithKeys(function (mixed $value, string $field): array {
                $normalizedField = Str::lower($field);

                if (
                    str_contains($normalizedField, 'password')
                    || str_contains($normalizedField, 'token')
                    || str_contains($normalizedField, 'secret')
                ) {
                    return [$field => '[REDACTED]'];
                }

                return [$field => $value];
            })
            ->all();
    }
}
