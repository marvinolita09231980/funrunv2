<?php

namespace App\Filament\Resources\Participants\Schemas;

use App\Models\Subcategory;
use Filament\Support\RawJs;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Fieldset;

class ParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
             ->components([
                // TextInput::make('participantNumber')
                //     ->required(),
                // TextInput::make('year')
                // ->label('Year')
                // ->numeric()
                // ->default(date('Y'))
                // ->required(),
                Fieldset::make('Personal Information')
                    ->schema([
                        TextInput::make('firstName')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('lastName')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('middleInitial')
                            ->maxLength(1),
                        DatePicker::make('birthDate')
                            ->columnSpan(2),

                        Select::make('gender')
                            ->options([
                                'male' =>     'Male',
                                'female' => 'Female',
                                'non-binary' => 'Non-binary',
                                'transgender' => 'Transgender',
                                'genderqueer' => 'Genderqueer',
                                'agender' => 'Agender',
                                'prefer not to say' => 'Prefer not to say'
                            ])
                            ->searchable()
                            ->required()
                            ->columnSpan(3),
                        TextInput::make('address')
                            ->columnSpan(5),

                        TextInput::make('contactNumber')
                                ->minLength(11)
                                ->mask(RawJs::make(<<<'JS'
                                        '9999 999 9999'
                                    JS))
                                ->stripCharacters(' ')
                                ->columnSpan(2)
                        ])
                        ->columns(5),
                        Fieldset::make('Funrun Information')
                            ->schema([
                                Select::make('categoryDescription')
                                    ->required()
                                    ->options(Subcategory::query()->distinct('categoryDescription')->pluck('categoryDescription', 'categoryDescription'))
                                    ->searchable()
                                    ->reactive()
                                    ->columnSpanFull(),
                                Select::make('subDescription')
                                    ->options(function (callable $get) {
                                        $category = $get('categoryDescription'); // get selected category
                                        if (!$category) {
                                            return []; // no category selected yet
                                        }
                
                                            return Subcategory::query()
                                            ->where('categoryDescription', $category)
                                            ->pluck('subDescription', 'subDescription');
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('shirtSize')
                                    ->options([
                                        'xs' =>     'XS',
                                        'small' => 'Small',
                                        'medium' => 'Medium',
                                        'large' => 'Large',
                                        'xl' => 'XL',
                                        '2xl' => '2XL',
                                        '3xl' => '3XL',
                                        '4xl' => '4XL',
                                        '5xl' => '5XL'
                                    ])
                                    ->searchable()
                                    ->required(),
                            
                                Radio::make('distanceCategory')
                                    ->required()
                                    ->options([
                                            '3km' =>    '3KM',
                                            '5km' =>    '5KM',
                                            '10km' =>   '10KM',
                                        ])
                                    ->inline()
                                    ->reactive(),
                                TextInput::make('referenceNumber')
                                    ->label('Reference Number')
                                    ->visible(fn (callable $get) => $get('distanceCategory') === '10km')
                                    ->required(fn (callable $get) => 
                                        $get('distanceCategory') === '10km'
                                        && Filament::getCurrentPanel()?->getId() === 'admin'
                                    ),
                        ]),
                 Toggle::make('waiver')
                    ->label('Waiver?')
                    ->default(false)
                    ->inline(false)
                    ->hidden(),
       
        ]);
    }
}
