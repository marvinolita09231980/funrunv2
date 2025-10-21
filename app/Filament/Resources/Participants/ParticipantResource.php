<?php

namespace App\Filament\Resources\Participants;

use BackedEnum;
use Filament\Tables\Table;
use App\Models\Participant;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\Participants\Pages\EditParticipant;
use App\Filament\Resources\Participants\Pages\ListParticipants;
use App\Filament\Resources\Participants\Pages\CreateParticipant;
use App\Filament\Resources\Participants\Schemas\ParticipantForm;
use App\Filament\Resources\Participants\Tables\ParticipantsTable;

class ParticipantResource extends Resource
{
    protected static ?string $model = Participant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static ?string $recordTitleAttribute = 'participantNumber';

    public static function form(Schema $schema): Schema
    {
        return ParticipantForm::configure($schema)->columns(1);
    }

    public static function table(Table $table): Table
    {
        return ParticipantsTable::configure($table);
           
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
            'index' => ListParticipants::route('/'),
            'create' => CreateParticipant::route('/create'),
            'edit' => EditParticipant::route('/{record}/edit'),
        ];
    }
    public static function getRecordTitle($record): string
    {
        return ' - ' .$record->firstName . ' ' . $record->lastName;
    }
}
