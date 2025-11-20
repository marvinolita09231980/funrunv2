<?php

namespace App\Providers;

use Filament\Panel;
// use Illuminate\Support\ServiceProvider;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

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
      //
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
