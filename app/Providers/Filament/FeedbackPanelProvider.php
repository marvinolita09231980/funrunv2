<?php

namespace App\Providers\Filament;

use App\Filament\Feedback\Pages\FeedbackForm;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class FeedbackPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('feedback')
            ->path('feedback')
            ->brandName('PADAC Furn Run 2026')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->topbar(false)
            ->navigation(false)
            ->discoverResources(in: app_path('Filament/Feedback/Resources'), for: 'App\Filament\Feedback\Resources')
            ->discoverPages(in: app_path('Filament/Feedback/Pages'), for: 'App\Filament\Feedback\Pages')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.feedback.partials.social-meta'),
                scopes: FeedbackForm::class,
            )
            ->pages([
                // Dashboard::class,
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
                // Authenticate::class,
            ])
            ->maxContentWidth(Width::TwoExtraLarge)
            ->viteTheme('resources/css/filament/register/theme.css');
    }
}
