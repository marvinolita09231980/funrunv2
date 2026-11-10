<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Finishers\Pages\ListFinishers;
use App\Filament\Resources\Participants\Pages\ListParticipants;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Javarex\DdoLogin\Pages\Login as DdoLogin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->globalSearch(false)
            ->brandName('Fun Run '.date('Y'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                'Overview',
                'Registration',
                'Event Operations',
                'Reports',
                'Administration',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->renderHook(
                TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
                fn () => view('filament.pages.partials.table-year-filter'),
                scopes: ListParticipants::class,
            )
            ->renderHook(
                TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
                fn () => view('filament.pages.partials.table-year-filter'),
                scopes: ListFinishers::class,
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('<livewire:contact-information-prompt />'),
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_END,
                fn () => view('filament.auth.partials.feedback-share-card'),
                scopes: DdoLogin::class,
            )
            ->pages([
                // Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
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
                Authenticate::class,
            ])
            ->plugins([
                \Javarex\DdoLogin\LoginDdoPlugin::make(),
                FilamentShieldPlugin::make()
                    ->navigationGroup('Administration')
                    ->navigationSort(20),
            ])
            ->databaseNotifications();
    }
}
