<?php

namespace App\Filament\Pages;

use App\Models\RegistrationSetting;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RegistrationControl extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Public Registration';

    protected static string|\UnitEnum|null $navigationGroup = 'Registration';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'public-registration';

    protected string $view = 'filament.pages.registration-control';

    public bool $registrationEnabled = true;

    public function mount(): void
    {
        $this->registrationEnabled = RegistrationSetting::kalamboanRegistrationEnabled();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && $user->hasAnyRole(['admin', 'superadmin', 'super_admin']);
    }

    public function getTitle(): string
    {
        return 'Public Registration';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleRegistration')
                ->label(fn (): string => $this->registrationEnabled
                    ? 'Deactivate Registration'
                    : 'Activate Registration')
                ->icon(fn (): string => $this->registrationEnabled
                    ? 'heroicon-o-lock-closed'
                    : 'heroicon-o-play')
                ->color(fn (): string => $this->registrationEnabled ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $this->registrationEnabled
                    ? 'Deactivate public registration?'
                    : 'Activate public registration?')
                ->modalDescription(fn (): string => $this->registrationEnabled
                    ? 'Visitors will see a registration closed notice and will not be able to submit the form.'
                    : 'Visitors will immediately be able to open and submit the public registration form.')
                ->modalSubmitActionLabel(fn (): string => $this->registrationEnabled
                    ? 'Deactivate'
                    : 'Activate')
                ->action(fn () => $this->toggleRegistration()),
        ];
    }

    public function toggleRegistration(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->registrationEnabled = ! RegistrationSetting::kalamboanRegistrationEnabled();

        RegistrationSetting::setKalamboanRegistrationEnabled($this->registrationEnabled);

        Notification::make()
            ->title($this->registrationEnabled
                ? 'Public registration activated'
                : 'Public registration deactivated')
            ->body($this->registrationEnabled
                ? 'Visitors can now access and submit the Kalamboan registration form.'
                : 'The Kalamboan registration form is now closed to visitors.')
            ->color($this->registrationEnabled ? 'success' : 'danger')
            ->icon($this->registrationEnabled
                ? 'heroicon-o-check-circle'
                : 'heroicon-o-lock-closed')
            ->send();
    }
}
