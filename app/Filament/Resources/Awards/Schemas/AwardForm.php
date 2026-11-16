<?php

namespace App\Filament\Resources\Awards\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AwardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label('Award Name')
                    ->placeholder('e.g. Champion')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Toggle::make('is_active')
                    ->label('Available for finisher recording')
                    ->helperText('Inactive awards remain on existing finisher records but are hidden from new selections.')
                    ->default(true),
            ]);
    }
}
