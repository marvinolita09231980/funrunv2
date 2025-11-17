<?php

namespace App\Filament\Resources\Finishers;
use BackedEnum;
use App\Models\Finisher;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Finishers\Pages\EditFinisher;
use App\Filament\Resources\Finishers\Pages\ListFinishers;
use App\Filament\Resources\Finishers\Pages\CreateFinisher;
use App\Filament\Resources\Finishers\Schemas\FinisherForm;
use App\Filament\Resources\Finishers\Tables\FinishersTable;

class FinisherResource extends Resource
{
    protected static ?string $model = Finisher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Finisher';

    public static function form(Schema $schema): Schema
    {
        return FinisherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinishersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinishers::route('/'),
            'create' => CreateFinisher::route('/create'),
            'edit' => EditFinisher::route('/{record}/edit'),
        ];
    }
     public static function getEloquentQuery(): Builder
     {
         return parent::getEloquentQuery();
     }
}
