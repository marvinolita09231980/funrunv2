<?php

namespace App\Filament\Resources\Participants\Tables;

use livewire;
use Filament\Tables\Table;
use App\Models\Participant;
use App\Models\Subcategory;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use App\Exports\AttendanceSheetExport;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Filters\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;

use App\Exports\FoodAttendanceSheetExport;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Exports\ParticipantExporter;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;


class ParticipantsTable
{
    public static function configure(Table $table): Table
    {
        
        return $table
            ->modifyQueryUsing(function ($query) {
                // Only show records created by the logged-in user
                if (Auth::check() && Auth::user()->username !== 'superadmin' && Auth::user()->username !== 'phrmdo') {
                    $query->where('created_by', Auth::user()->username)
                          ->where('year',date('Y'));
                }
                else if (Auth::check() && Auth::user()->username === 'phrmdo')
                {
                   $query->where('categoryDescription', 'PLGU')
                         ->where('year',date('Y'));
                }
                else if(Auth::check() && Auth::user()->username === 'superadmin')
                {
                      $query->where('year',date('Y'));
                }
                else if(Auth::check() && Auth::user()->username === 'superadmin')
                {
                      $query->where('year',date('Y'));
                }
            })
            ->columns([
                TextColumn::make('firstName')->label('First Name')
                ->searchable(),
                TextColumn::make('lastName')->label('Last Name')
                ->searchable(),
                TextColumn::make('categoryDescription')->label('Category')
                ->searchable(),
                TextColumn::make('subDescription')->label('Organization')
                ->searchable(),
                TextColumn::make('gender')->label('Gender')
                ->searchable(),
                TextColumn::make('created_by')->label('Created By')
                ->searchable(),
            ])
            ->recordUrl(null)
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        SelectConstraint::make('year')
                        ->options(
                                collect(range(date('Y'), date('Y') - 5))
                                    ->mapWithKeys(fn($year) => [$year => $year])
                                    ->toArray()
                            ),
                            
                        ])
                        ->modifyQueryUsing(function(Builder $query, array $data){
                            $year = date('Y');

                            if (!empty($data['rules']) && is_array($data['rules'])) {
                                foreach ($data['rules'] as $rule) {
                                    if (($rule['type'] ?? null) === 'year') {
                                        $year = $rule['data']['settings']['value'] ?? $year;
                                        break;
                                    }
                                }
                            }

                            return $query->where('year', $year);
                        })
                     
            ])
            ->recordActions([
                 EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
                
                Action::make('attendance_export')
                    ->label('Attendance Export')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (array $data) {
                            $year = date('Y');
                            $letterStart = strtoupper($data['letterStart']);
                            $letterEnd = strtoupper($data['letterEnd']);

                            if ($letterStart > $letterEnd) {
                                [$letterStart, $letterEnd] = [$letterEnd, $letterStart];
                            }

                            $participants = Participant::select(
                            'firstName', 
                            'middleInitial', 
                            'lastName', 
                            'distanceCategory', 
                            'shirtSize', 
                            'gender',
                            'categoryDescription'
                            )
                            ->when($data['subcategory'] === 'OPEN CATEGORY', function ($query) use($data) {
                                $query->where('categoryDescription', $data['subcategory']);
                            }, function ($query) use($data) {
                                $query->where('subDescription', $data['subcategory'])
                                            ->where('categoryDescription', '!=', 'OPEN CATEGORY');
                            })
                            ->where('year', $year)
                            ->whereRaw("LEFT(UPPER(TRIM(lastName)), 1) BETWEEN ? AND ?", [$letterStart, $letterEnd])
                            ->orderBy('lastName')
                            ->orderBy('firstName')
                            ->get();

                        //    $participants = self::updateParticipantCount($set, $get);
                            
                            

                            if ($participants->isEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title('No data found!')
                                    ->body('No participants were found in this category')
                                    ->send();
                                return;
                            }

                            return Excel::download(
                                new AttendanceSheetExport($data['subcategory'],$letterStart,$letterEnd),
                                'attendance_sheet.xlsx'
                            );
                    })
                   ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                Select::make('subcategory')
                                    ->label('Subcategory')
                                    ->options(Subcategory::pluck('subDescription', 'subDescription'))
                                    ->reactive()
                                    ->afterStateUpdated(function(callable $set,callable $get){
                                        
                                        self::updateParticipantCount($set, $get);
                                    
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columnSpanFull(),

                                Select::make('letterStart')
                                    ->label('List from')
                                    ->options(
                                        collect(range('A', 'Z'))
                                            ->mapWithKeys(fn ($letter) => [$letter => $letter])
                                            ->toArray()
                                    )
                                    ->reactive()
                                    ->afterStateUpdated(function(callable $set,callable $get){
                                        
                                        self::updateParticipantCount($set, $get);
                                    
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columns(1),
                                Select::make("letterEnd")
                                    ->label('List to')
                                    ->options(
                                        collect(range('A', 'Z'))
                                            ->mapWithKeys(fn ($letter) => [$letter => $letter])
                                            ->toArray()
                                    )
                                    ->reactive()
                                    ->afterStateUpdated(function(callable $set,callable $get){
                                        
                                        self::updateParticipantCount($set, $get);
                                    
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columns(1),
                                TextEntry ::make('participantCount')
                                    ->label('Participants Found')
                                    ->columnSpanFull()
                                    ->disabled(),

                                
                            ]),
                    ]),
                                
                Action::make('foood_attendance_export')
                    ->label('Food Attendance Export')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (array $data) {
                            $year = date('Y');
                            $letterStart = strtoupper($data['letterStart']);
                            $letterEnd = strtoupper($data['letterEnd']);

                            if ($letterStart > $letterEnd) {
                                [$letterStart, $letterEnd] = [$letterEnd, $letterStart];
                            }

                            $participants = Participant::select(
                            'firstName', 
                            'middleInitial', 
                            'lastName', 
                            'distanceCategory', 
                            'shirtSize', 
                            'gender',
                            'categoryDescription'
                            )
                            ->when($data['subcategory'] === 'OPEN CATEGORY', function ($query) use($data) {
                                $query->where('categoryDescription', $data['subcategory']);
                            }, function ($query) use($data) {
                                 $query->where('subDescription', $data['subcategory'])
                                            ->where('categoryDescription', '!=', 'OPEN CATEGORY');
                            })
                            ->where('year', $year)
                            ->whereRaw("LEFT(UPPER(TRIM(lastName)), 1) BETWEEN ? AND ?", [$letterStart, $letterEnd])
                            ->orderBy('lastName')
                            ->orderBy('firstName')
                            ->get();

                        //    $participants = self::updateParticipantCount($set, $get);
                            
                            

                            if ($participants->isEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title('No data found!')
                                    ->body('No participants were found in this category')
                                    ->send();
                                return;
                            }

                            return Excel::download(
                                new FoodAttendanceSheetExport($data['subcategory'],$letterStart,$letterEnd),
                                'attendance_sheet.xlsx'
                            );
                    })
                   ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                Select::make('subcategory')
                                    ->label('Subcategory')
                                    ->options(Subcategory::pluck('subDescription', 'subDescription'))
                                    ->reactive()
                                    ->afterStateUpdated(function(callable $set,callable $get){
                                        
                                        self::updateParticipantCount($set, $get);
                                    
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columnSpanFull(),

                                Select::make('letterStart')
                                    ->label('List from')
                                    ->options(
                                        collect(range('A', 'Z'))
                                            ->mapWithKeys(fn ($letter) => [$letter => $letter])
                                            ->toArray()
                                    )
                                    ->reactive()
                                    ->afterStateUpdated(function(callable $set,callable $get){
                                        
                                        self::updateParticipantCount($set, $get);
                                    
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columns(1),
                                Select::make("letterEnd")
                                    ->label('List to')
                                    ->options(
                                        collect(range('A', 'Z'))
                                            ->mapWithKeys(fn ($letter) => [$letter => $letter])
                                            ->toArray()
                                    )
                                    ->reactive()
                                    ->afterStateUpdated(function(callable $set,callable $get){
                                        
                                        self::updateParticipantCount($set, $get);
                                    
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columns(1),
                                TextEntry ::make('participantCount')
                                    ->label('Participants Found')
                                    ->columnSpanFull()
                                    ->disabled(),

                                
                            ]),
                    ]),
                     
                            
            
            ]);
            
    }

    private static function updateParticipantCount(callable $set, callable $get): int
    {
        
        $year = date('Y');
        $letterStart = strtoupper($get('letterStart'));
        $letterEnd   = strtoupper($get('letterEnd'));
        $subcategory = $get('subcategory');

      

        if (! $subcategory || ! $letterStart || ! $letterEnd) {
            $set('participantCount', 0);
            return 0;
        }

        if ($letterStart > $letterEnd) {
            [$letterStart, $letterEnd] = [$letterEnd, $letterStart];
        }

        $count = Participant::select(
                            'firstName', 
                            'middleInitial', 
                            'lastName', 
                            'distanceCategory', 
                            'shirtSize', 
                            'gender',
                            'categoryDescription'
                            )
                            ->when($subcategory === 'OPEN CATEGORY', function ($query) use($subcategory) {
                                $query->where('categoryDescription', $subcategory);
                            }, function ($query) use($subcategory) {
                                $query->where('subDescription', $subcategory)
                                            ->where('categoryDescription', '!=', 'OPEN CATEGORY');
                            })
                            ->where('year', $year)
                            ->whereRaw("LEFT(UPPER(TRIM(lastName)), 1) BETWEEN ? AND ?", [$letterStart, $letterEnd])
                            ->orderBy('lastName')
                            ->orderBy('firstName')
                            ->get()
                            ->count();
        
         
        $set('participantCount', $count);
        return  $count;
    }
}
