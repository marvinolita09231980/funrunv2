<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationSetting extends Model
{
    protected $fillable = [
        'kalamboan_registration_enabled',
    ];

    protected function casts(): array
    {
        return [
            'kalamboan_registration_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['id' => 1],
            ['kalamboan_registration_enabled' => true],
        );
    }

    public static function kalamboanRegistrationEnabled(): bool
    {
        return (bool) self::current()->kalamboan_registration_enabled;
    }

    public static function setKalamboanRegistrationEnabled(bool $enabled): void
    {
        self::current()->update([
            'kalamboan_registration_enabled' => $enabled,
        ]);
    }
}
