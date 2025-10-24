<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Enums\Width;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class FeedbackPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('feedback')
            ->path('feedback')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->topbar(false)
            ->navigation(false)
            ->discoverResources(in: app_path('Filament/Feedback/Resources'), for: 'App\Filament\Feedback\Resources')
            ->discoverPages(in: app_path('Filament/Feedback/Pages'), for: 'App\Filament\Feedback\Pages')
            ->pages([
                //Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Feedback/Widgets'), for: 'App\Filament\Feedback\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                //Authenticate::class,
            ])
            ->maxContentWidth(Width::TwoExtraLarge)
            ->viteTheme('resources/css/filament/register/theme.css');
    }
}
