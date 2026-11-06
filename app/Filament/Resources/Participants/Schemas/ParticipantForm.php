<?php

namespace App\Filament\Resources\Participants\Schemas;

use livewire;
use App\Models\Participant;
use App\Models\Subcategory;
use App\Models\User;
use Filament\Support\RawJs;
use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Html;
use Illuminate\Support\Facades\Route;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Validation\ValidationException;

class ParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
             ->components([
               
                Fieldset::make('Personal Information')
                    ->schema([
                        TextInput::make('firstName')
                            ->required()
                            ->columnSpan(2)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get, $component, $livewire) {
                                    self::checkAndFillParticipant($get, $set, $livewire);
                                    
                            }),
                        TextInput::make('lastName')
                            ->required()
                            ->columnSpan(2)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get, $component, $livewire) {
                                    self::checkAndFillParticipant($get, $set, $livewire);
                                    
                            }),

                        TextInput::make('middleInitial')
                            ->maxLength(1),
                        DatePicker::make('birthDate')
                            ->columnSpan(2)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get, $component, $livewire) {
                                   self::checkAndFillParticipant($get, $set, $livewire);
                                  
                            }),

                        Select::make('gender')
                            ->required()
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
                            ->columnSpan(3),
                        TextInput::make('address')
                            ->columnSpan(5)
                            ->required(),

                        TextInput::make('contactNumber')
                            ->minLength(11)
                            ->mask(RawJs::make(<<<'JS'
                                    '9999 999 9999'
                                JS))
                            ->stripCharacters(' ')
                            ->columnSpan(2)
                            ->required(),
                            
                        Radio::make('pwd')
                            ->label('Person with Disability')
                            ->required()
                            ->options([
                                    false =>    'NO',
                                    true =>    'YES',
                                   
                                ])
                            ->inline()
                            ->reactive(),
                        Radio::make('rpwuds')
                            ->label('Recovering Person Who Used Drugs')
                            ->required()
                            ->options([
                                    false =>    'NO',
                                    true =>    'YES',
                                   
                                ])
                            ->inline()
                            ->columnSpan(2)
                            ->reactive(),
                        ])
                        ->columns(5),
                        Fieldset::make('Funrun Information')
                            ->schema([
                                Select::make('categoryDescription')
                                   ->required()
                                   ->options(fn (): array => self::categoryOptionsForUser(Auth::user()))
                                    ->searchable()
                                    ->reactive()
                                    ->columnSpanFull(),
                                TextInput::make('subDescription')
                                    ->label('Description')
                                    ->hint('Name of your organization (e.g., Individual, Family/Group, Couple, Team, or Company)')
                                    ->required()
                                    ->columnSpanFull()
                                    ->visible(fn ($get) => $get('categoryDescription') === 'OPEN CATEGORY') // ✅ Only show when Open Category
                                    ->reactive(),

                                Select::make('subDescription')
                                    ->options(function (callable $get) {
                                        $category = $get('categoryDescription');

                                        if (!$category) {
                                            return []; 
                                        }

                                        return self::subcategoryOptionsForUser(
                                            Auth::user(),
                                            $category,
                                        );
                                               
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columnSpanFull()
                                    ->visible(fn ($get) => $get('categoryDescription') !== 'OPEN CATEGORY') // ✅ Hide if Open Category
                                    ->hint(function ($get) {
                                        $year = now()->year;
                                        $category = $get('categoryDescription');
                                        $subCategory = $get('subDescription');

                                        // 🔹 Only run when both values are selected
                                        if (! $category || ! $subCategory) {
                                            return null;
                                        }

                                        $cat = DB::table('subcategories as s')
                                            ->leftJoin('participants as p', function ($join) use ($year) {
                                                $join->on('p.categoryDescription', '=', 's.categoryDescription')
                                                    ->on('p.subDescription', '=', 's.subDescription')
                                                    ->where('p.year', '=', $year);
                                            })
                                            ->select(
                                                's.nop',
                                                's.categoryDescription',
                                                's.subDescription',
                                                DB::raw('COUNT(p.id) as registered_count')
                                            )
                                            ->where('s.categoryDescription', $category)
                                            ->where('s.subDescription', $subCategory)
                                            ->groupBy('s.nop', 's.categoryDescription', 's.subDescription')
                                            ->first();

                                        if ($cat) {
                                           
                                            return $cat->registered_count >= $cat->nop
                                                ? '⚠️ Participant slots already full.'
                                                : null;
                                        }

                                        return null;
                                    })
                                    ->hintColor('danger')
                                    ->reactive(),
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
                                            '3km' =>    'Rave Run (3K)',
                                            '5km' =>    '5KM',
                                            '10km' =>   '10KM',
                                        ])
                                    ->inline()
                                    ->reactive(),
                                // Radio::make('couple')
                                //     ->label('Couple Runner')
                                //     ->required()
                                //     ->options([
                                //            false =>    'NO',
                                //            true =>    'YES',
                                //         ])
                                //     ->inline()
                                //     ->reactive(),
                        ]),
                 Toggle::make('waiver')
                    ->label('Waiver?')
                    ->default(false)
                    ->inline(false)
                    ->hidden(),    
        ]);
    }

    


    private static function checkAndFillParticipant(
        callable $get,
        callable $set,
        object $livewire,
    ): bool
    {
        $firstName  = $get('firstName');
        $lastName   = $get('lastName');
        $birthDate  = $get('birthDate');
        $yearNow    =  date('Y');
       
        
        
        if ($firstName && $lastName && $birthDate) {
            $participant = Participant::where('year', $yearNow)
                ->where('firstName', $firstName)
                ->where('lastName', $lastName)
                ->whereDate('birthDate', $birthDate)
                ->first();

            if ($participant) {
                if (method_exists($livewire, 'showExistingParticipantModal')) {
                    $livewire->showExistingParticipantModal($participant);
                }
              
                $set('middleInitial', $participant->middleInitial);
                $set('gender', $participant->gender);
                $set('address', $participant->address);
                $set('contactNumber', $participant->contactNumber);
                $set('categoryDescription', $participant->categoryDescription);
                $set('subDescription', $participant->subDescription);
                $set('shirtSize', $participant->shirtSize);
                $set('distanceCategory', $participant->distanceCategory);
                $set('referenceNumber', $participant->referenceNumber);
                return true;
            } 
        }
        return false;
    }

    public static function categoryOptionsForUser(?User $user): array
    {
        $query = Subcategory::query()->whereNotNull('categoryDescription');

        if ($user?->isAdministrativeOfficer()) {
            $query->whereIn('categoryDescription', $user->administeredCategoryNames());
        } elseif (! $user?->hasAnyRole(['admin', 'superadmin', 'super_admin'])) {
            $query->where('username', $user?->username ?? 'opencategory');
        }

        return $query
            ->distinct()
            ->orderBy('categoryDescription')
            ->pluck('categoryDescription', 'categoryDescription')
            ->all();
    }

    public static function subcategoryOptionsForUser(?User $user, ?string $category): array
    {
        if (blank($category)) {
            return [];
        }

        if (
            $user?->isAdministrativeOfficer()
            && ! $user->administersCategory($category)
        ) {
            return [];
        }

        $query = Subcategory::query()->where('categoryDescription', $category);

        if (
            ! $user?->isAdministrativeOfficer()
            && ! $user?->hasAnyRole(['admin', 'superadmin', 'super_admin'])
        ) {
            $query->where('username', $user?->username ?? 'opencategory');
        }

        return $query
            ->distinct()
            ->orderBy('subDescription')
            ->pluck('subDescription', 'subDescription')
            ->all();
    }
}
