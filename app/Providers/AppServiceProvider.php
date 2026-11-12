<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Services\AuditLogger;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
// use Illuminate\Support\ServiceProvider;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Forms\Components\DateTimePicker;
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
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DateTimePicker::configureUsing(function (DateTimePicker $field): void {
            if (! $field->hasDate()) {
                return;
            }

            $field
                ->view('filament.forms.components.typed-date-picker')
                ->native(false)
                ->displayFormat(fn (): string => $field->hasTime()
                    ? ($field->hasSeconds() ? 'm/d/Y H:i:s' : 'm/d/Y H:i')
                    : 'm/d/Y')
                ->placeholder(fn (): string => $field->hasTime()
                    ? ($field->hasSeconds() ? 'mm/dd/yyyy hh:mm:ss' : 'mm/dd/yyyy hh:mm')
                    : 'mm/dd/yyyy');
        });

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
