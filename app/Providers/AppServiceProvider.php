<?php

namespace App\Providers;

use App\Services\AuditLogger;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
// use Illuminate\Support\ServiceProvider;
use Filament\Panel;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            'eloquent.updated: *',
            fn (string $event, array $models) => AuditLogger::recordUpdated($models[0] ?? null),
        );

        Event::listen(
            'eloquent.deleted: *',
            fn (string $event, array $models) => AuditLogger::recordDeleted($models[0] ?? null),
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->plugins([
                FilamentShieldPlugin::make(),
            ]);
    }
}
