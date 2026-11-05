<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Full Name')
                    ->helperText('Enter the first and last name. The username is generated automatically.')
                    ->required()
                    ->live()
                    ->afterStateUpdated(
                        fn (?string $state, Set $set, ?User $record): mixed => $record
                            ? null
                            : $set('username', User::usernameFromName($state)),
                    ),
                TextInput::make('office')
                    ->required()
                    ->maxLength(255),
                TextInput::make('cellphone_number')
                    ->label('Cellphone Number')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(
                        fn (?string $state): ?string => filled($state) ? trim($state) : null,
                    ),
                TextInput::make('username')
                    ->readOnly()
                    ->helperText('Lowercase full name without spaces.')
                    ->unique(ignoreRecord: true),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->formatStateUsing(fn (): null => null)
                    ->required(),
            ]);
    }
}
