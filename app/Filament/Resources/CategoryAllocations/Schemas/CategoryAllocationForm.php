<?php

namespace App\Filament\Resources\CategoryAllocations\Schemas;

use App\Models\CategoryAllocation;
use App\Models\OverallAllocation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryAllocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('categoryDescription')
                    ->label('Main Category')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?CategoryAllocation $record): bool => filled($record)),
                Select::make('administrativeOfficers')
                    ->label('Assigned Administrative Officers')
                    ->helperText('An Administrative Officer may be assigned to multiple main categories.')
                    ->relationship('administrativeOfficers', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                TextInput::make('allocated_participants')
                    ->label('Allocated Participants')
                    ->helperText(fn (?CategoryAllocation $record): string => collect([
                        'Available from the overall allocation: ' . number_format(
                            OverallAllocation::current()->availableForCategory($record?->getKey()),
                        ),
                        $record
                            ? 'Minimum allowed: ' . number_format($record->minimumAllocation())
                            : null,
                    ])->filter()->join(' • '))
                    ->numeric()
                    ->minValue(fn (?CategoryAllocation $record): int => $record?->minimumAllocation() ?? 0)
                    ->maxValue(fn (?CategoryAllocation $record): int => OverallAllocation::current()
                        ->availableForCategory($record?->getKey()))
                    ->required(),
            ]);
    }
}
