<?php

namespace App\Filament\Resources\Participants\Tables;

use livewire;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use App\Models\Participant;
use App\Models\Subcategory;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Illuminate\Support\HtmlString;
use App\Exports\FilteredSheetExport;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use App\Exports\AttendanceSheetExport;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Filters\SelectFilter;

use App\Exports\FoodAttendanceSheetExport;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Exports\ParticipantExporter;


class ParticipantsTable
{
    public static function configure(Table $table): Table
    {
        
        return $table
            ->modifyQueryUsing(function ($query, $livewire) {
                $year = (int) ($livewire->getTableFilterState('year')['value'] ?? date('Y'));

                $query
                    ->with('creator')
                    ->where('year', $year);

                if (! Auth::check()) {
                    return;
                }

                self::applyVisibilityForUser($query, Auth::user());
            })
            ->columns([
                TextColumn::make('mobile_participant_summary')
                    ->label('Participant')
                    ->getStateUsing(function (Participant $record): string {
                        $middleInitial = filled($record->middleInitial)
                            ? ' ' . rtrim($record->middleInitial, '.') . '.'
                            : '';

                        return "{$record->lastName}, {$record->firstName}{$middleInitial}";
                    })
                    ->description(function (Participant $record): string {
                        return collect([
                            $record->categoryDescription,
                            $record->subDescription,
                            filled($record->gender) ? ucfirst($record->gender) : null,
                            filled($record->created_by) ? "Added by {$record->creator_name}" : null,
                        ])->filter()->join(' • ');
                    })
                    ->weight('bold')
                    ->wrap()
                    ->hiddenFrom('md'),
                TextColumn::make('firstName')
                    ->label('First Name')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('lastName')
                    ->label('Last Name')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('categoryDescription')
                    ->label('Category')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('subDescription')
                    ->label('Organization')
                    ->searchable()
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('gender')
                    ->label('Gender')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('creator_name')
                    ->label('Created By')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query
                                ->where('created_by', 'like', "%{$search}%")
                                ->orWhereHas(
                                    'creator',
                                    fn (Builder $creatorQuery): Builder => $creatorQuery
                                        ->where('name', 'like', "%{$search}%"),
                                );
                        });
                    })
                    ->visibleFrom('md'),
            ])
            ->recordUrl(null)
            ->filters([
                SelectFilter::make('year')
                    ->label('Year')
                    ->options(
                        collect(range((int) date('Y'), (int) date('Y') - 5))
                            ->mapWithKeys(fn (int $year): array => [$year => $year])
                            ->all()
                    )
                    ->default(date('Y'))
                    ->selectablePlaceholder(false)
                    ->query(fn ($query) => $query),
            ], layout: FiltersLayout::Hidden)
            ->deferFilters(false)
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
                    ->visible(fn (): bool => self::canUseAttendanceExports(Auth::user()))
                    ->action(fn (array $data) => self::downloadAttendanceExport($data))
                   ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                Select::make('subcategory')
                                    ->label('Subcategories')
                                    ->options(fn (): array => self::attendanceSubcategoryOptions(Auth::user()))
                                    ->multiple()
                                    ->reactive()
                                    ->afterStateUpdated(function(callable $set,callable $get){
                                        if (self::hasMultipleSelectedSubcategories($get)) {
                                            $set('letterStart', null);
                                            $set('letterEnd', null);
                                        }

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
                                    ->required(fn (callable $get): bool => ! self::hasMultipleSelectedSubcategories($get))
                                    ->hidden(fn (callable $get): bool => self::hasMultipleSelectedSubcategories($get))
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
                                    ->required(fn (callable $get): bool => ! self::hasMultipleSelectedSubcategories($get))
                                    ->hidden(fn (callable $get): bool => self::hasMultipleSelectedSubcategories($get))
                                    ->columns(1),
                                TextEntry ::make('participantCount')
                                    ->label('Participants Found')
                                    ->columnSpanFull()
                                    ->disabled(),

                                
                            ]),
                    ]),
                                
                Action::make('food_attendance_export')
                    ->label('Food Attendance Export')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn (): bool => self::canUseAttendanceExports(Auth::user()))
                    ->action(fn (array $data) => self::downloadAttendanceExport($data, food: true))
                   ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                Select::make('subcategory')
                                    ->label('Subcategories')
                                    ->options(fn (): array => self::attendanceSubcategoryOptions(Auth::user()))
                                    ->multiple()
                                    ->reactive()
                                    ->afterStateUpdated(function(callable $set,callable $get){
                                        if (self::hasMultipleSelectedSubcategories($get)) {
                                            $set('letterStart', null);
                                            $set('letterEnd', null);
                                        }

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
                                    ->required(fn (callable $get): bool => ! self::hasMultipleSelectedSubcategories($get))
                                    ->hidden(fn (callable $get): bool => self::hasMultipleSelectedSubcategories($get))
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
                                    ->required(fn (callable $get): bool => ! self::hasMultipleSelectedSubcategories($get))
                                    ->hidden(fn (callable $get): bool => self::hasMultipleSelectedSubcategories($get))
                                    ->columns(1),
                                TextEntry ::make('participantCount')
                                    ->label('Participants Found')
                                    ->columnSpanFull()
                                    ->disabled(),

                                
                            ]),
                    ]),

                Action::make('filtered_export')
                    ->label('Filtered Export')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (array $data) {
                        
                          
                    //    dd($data);
                        $year                 = $data['year'] ?? date('Y');
                        $distanceCategory     = $data['distanceCategory'] ?? null;
                        $categoryDescription  = $data['categoryDescription'] ?? null;
                        $subDescription       = $data['subDescription'] ?? null;
                        $gender               = $data['gender'] ?? null;
                        $pwd                  = $data['pwd'] ?? null;
                        

                        $participants = Participant::select(
                        'firstName', 
                        'middleInitial', 
                        'lastName', 
                        'distanceCategory', 
                        'shirtSize', 
                        'gender',
                        'categoryDescription',
                        'subDescription'
                        )
                        ->when($year, fn($query, $year) => $query->where('year', $year))
                        ->when($distanceCategory, fn($query, $distanceCategory) => $query->where('distanceCategory', $distanceCategory))
                        ->when($categoryDescription, fn($query, $categoryDescription) => $query->where('categoryDescription', $categoryDescription))
                        ->when($subDescription, fn($query, $subDescription) => $query->where('subDescription', $subDescription))
                        ->when($gender, fn($query, $gender) => $query->where('gender', $gender))
                        ->when($pwd !== null, fn($query, $pwd) => $query->where('pwd', $pwd)) // if you have pwd field
                        // ->whereRaw("LEFT(UPPER(TRIM(lastName)), 1) BETWEEN ? AND ?", [$this->letterStart, $this->letterEnd])
                        ->get();
                    

                       
                            
                            

                            if ($participants->isEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title('No data found!')
                                    ->body('No participants were found in this category')
                                    ->send();
                                return;
                            }

                            return Excel::download(
                                new FilteredSheetExport($data),
                                'filtered_export_sheet.xlsx'
                            );
                    })
                   ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                Select::make('year')
                                    ->label('Year')
                                    ->options(
                                        collect(range(date('Y'), date('Y') - 5))
                                            ->mapWithKeys(fn($year) => [$year => $year])
                                            ->toArray()
                                    )
                                    ->default(date('Y'))  
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(1),

                                Select::make('distanceCategory')
                                    ->label('Category')
                                    ->options(
                                        [
                                            '3km'=>'Rave Run (3K)',
                                            '5km'=>'5km',
                                            '10km'=>'10km'
                                        ]
                                    )
                                    ->searchable()
                                    ->columnSpan(1),
                                Select::make('categoryDescription')
                                    ->label('Description')
                                    ->options(Subcategory::pluck('categoryDescription', 'categoryDescription'))
                                    ->searchable()
                                    ->columnSpan(1),
                                Select::make('subDescription')
                                    ->label('Agency')
                                    ->options(function (callable $get) {
                                        $category = $get('categoryDescription');

                                        if (!$category) {
                                            return []; 
                                        }

                                        $query = Subcategory::query()
                                                    ->where('categoryDescription', $category);

                                        if (Auth::check() && Auth::user()->username !== 'superadmin') {
                                            $query->where('username', Auth::user()->username);
                                        }

                                        return $query->distinct()
                                                    ->pluck('subDescription', 'subDescription');
                                               
                                    })
                                    ->searchable()
                                    ->columnSpan(1),
                                Select::make('gender')
                                    ->label('Gender')
                                    ->options([
                                        '' =>     'All',
                                        'male' =>     'Male',
                                        'female' => 'Female',
                                        'non-binary' => 'Non-binary',
                                        'transgender' => 'Transgender',
                                        'genderqueer' => 'Genderqueer',
                                        'agender' => 'Agender',
                                        'prefer not to say' => 'Prefer not to say'
                                    ])
                                    ->default('') 
                                    ->searchable()
                                    ->columnSpan(1),
                                Select::make('pwd')
                                    ->label('Person with Disability')
                                    ->options([
                                        true =>  'Yes',
                                        false => 'No',
                                    ])
                                    ->columnSpan(1),
                                Select::make('rpwuds')
                                    ->label('Recovering Persons Who Used Drugs')
                                    ->options([
                                        true =>  'Yes',
                                        false => 'No',
                                    ])
                                    ->columnSpan(1),
                                Select::make('order_by_column')
                                    ->label('Order By Column')
                                    ->options([
                                        'firstName'        => 'First Name',
                                        'lastName'         => 'Last Name',
                                        'categoryDescription' => 'Category Description',
                                        'subDescription'   => 'Sub Description',
                                        'shirtSize'        => 'Shirt Size',
                                        'birthDate'        => 'Birth Date',
                                        'address'          => 'Address',
                                        'gender'           => 'Gender',
                                        'distanceCategory' => 'Distance Category',
                                    ])
                                    ->searchable()
                                    ->reactive()
                                    ->columnSpan(1),
                                Select::make('order_by_desc_asc')
                                    ->label('Order By Desc/Asc')
                                    ->options([
                                        'DESC' =>  'DESC',
                                        'ASC' => 'ASC',
                                    ])
                                    ->required(fn ($get) => !empty($get('order_by_column')))
                                    ->visible(fn ($get) => !empty($get('order_by_column')))
                                    ->columnSpan(1),
                                TextEntry ::make('participantCount')
                                    ->label('Participants Found')
                                    ->columnSpanFull()
                                    ->disabled(),

                                
                            ]),
                    ])
                    ->visible(fn () => Auth::check() 
                      && Auth::user()->username === 'superadmin'),

                     
                            
            
            ]);
            
    }

    public static function applyVisibilityForUser(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->hasAnyRole(['admin', 'superadmin', 'super_admin'])) {
            return $query;
        }

        if ($user->isAdministrativeOfficer()) {
            return $query;
        }

        if ($user->hasRole('focal user')) {
            $assignments = Subcategory::query()
                ->where('username', $user->username)
                ->get(['categoryDescription', 'subDescription']);

            if ($assignments->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where(function (Builder $query) use ($assignments): void {
                foreach ($assignments as $assignment) {
                    $query->orWhere(function (Builder $query) use ($assignment): void {
                        $query->where('categoryDescription', $assignment->categoryDescription);

                        if ($assignment->categoryDescription !== 'OPEN CATEGORY') {
                            $query->where('subDescription', $assignment->subDescription);
                        }
                    });
                }
            });
        }

        if ($user->username === 'phrmdo') {
            return $query->where('categoryDescription', 'PLGU');
        }

        return $query->where('created_by', $user->username);
    }

    public static function canUseAttendanceExports(?User $user): bool
    {
        return $user?->isAdministrativeOfficer()
            || ($user?->hasAnyRole(['admin', 'superadmin', 'super_admin']) ?? false);
    }

    public static function attendanceSubcategoryOptions(?User $user): array
    {
        if (! self::canUseAttendanceExports($user)) {
            return [];
        }

        $query = Subcategory::reportQuery()
            ->orderBy('categoryDescription')
            ->orderBy('subDescription');

        if ($user?->isAdministrativeOfficer()) {
            $query->whereIn('categoryDescription', $user->administeredCategoryNames());
        }

        return $query
            ->get(['id', 'categoryDescription', 'subDescription'])
            ->mapWithKeys(fn (Subcategory $subcategory): array => [
                $subcategory->getKey() => $subcategory->categoryDescription . ' — ' . ($subcategory->subDescription ?: 'No subcategory'),
            ])
            ->all();
    }

    public static function exportSubcategoryForUser(
        ?User $user,
        mixed $subcategoryId,
    ): ?Subcategory {
        return self::exportSubcategoriesForUser($user, [$subcategoryId])->first();
    }

    public static function exportSubcategoriesForUser(
        ?User $user,
        mixed $subcategoryIds,
    ): EloquentCollection {
        $ids = collect(is_array($subcategoryIds) ? $subcategoryIds : [$subcategoryIds])
            ->filter(fn (mixed $id): bool => filled($id))
            ->unique()
            ->values();

        if (! self::canUseAttendanceExports($user) || $ids->isEmpty()) {
            return new EloquentCollection();
        }

        $query = Subcategory::reportQuery()->whereIn('id', $ids->all());

        if ($user?->isAdministrativeOfficer()) {
            $query->whereIn('categoryDescription', $user->administeredCategoryNames());
        }

        return $query->get();
    }

    private static function participantsForExport(EloquentCollection $subcategories): Builder
    {
        return Participant::query()->where(function (Builder $query) use ($subcategories): void {
            foreach ($subcategories as $subcategory) {
                $query->orWhere(function (Builder $query) use ($subcategory): void {
                    $query->where('categoryDescription', $subcategory->categoryDescription);

                    if ($subcategory->categoryDescription !== 'OPEN CATEGORY') {
                        $query->where('subDescription', $subcategory->subDescription);
                    }
                });
            }
        });
    }

    private static function downloadAttendanceExport(array $data, bool $food = false): mixed
    {
        $selectedIds = collect((array) ($data['subcategory'] ?? []))
            ->filter(fn (mixed $id): bool => filled($id))
            ->unique()
            ->values();
        $subcategories = self::exportSubcategoriesForUser(Auth::user(), $selectedIds->all());

        if ($selectedIds->isEmpty() || $subcategories->count() !== $selectedIds->count()) {
            throw ValidationException::withMessages([
                'subcategory' => 'Select only subcategories assigned to your account.',
            ]);
        }

        $multipleSubcategories = $selectedIds->count() > 1;
        $letterStart = $multipleSubcategories
            ? null
            : strtoupper((string) ($data['letterStart'] ?? ''));
        $letterEnd = $multipleSubcategories
            ? null
            : strtoupper((string) ($data['letterEnd'] ?? ''));

        if (! $multipleSubcategories && (blank($letterStart) || blank($letterEnd))) {
            throw ValidationException::withMessages([
                'letterStart' => 'Select the first letter for a single subcategory.',
                'letterEnd' => 'Select the last letter for a single subcategory.',
            ]);
        }

        if ($letterStart && $letterEnd && $letterStart > $letterEnd) {
            [$letterStart, $letterEnd] = [$letterEnd, $letterStart];
        }

        $participants = self::participantsForExport($subcategories)
            ->where('year', date('Y'))
            ->when(
                $letterStart && $letterEnd,
                fn (Builder $query): Builder => $query->whereRaw(
                    'SUBSTR(UPPER(TRIM(lastName)), 1, 1) BETWEEN ? AND ?',
                    [$letterStart, $letterEnd],
                ),
            )
            ->exists();

        if (! $participants) {
            Notification::make()
                ->danger()
                ->title('No data found!')
                ->body('No participants were found in the selected subcategories.')
                ->send();

            return null;
        }

        $selections = $subcategories
            ->map(fn (Subcategory $subcategory): array => [
                'category' => $subcategory->categoryDescription,
                'subcategory' => $subcategory->subDescription,
            ])
            ->values()
            ->all();

        $export = $food
            ? new FoodAttendanceSheetExport($selections, $letterStart, $letterEnd)
            : new AttendanceSheetExport($selections, $letterStart, $letterEnd);

        return Excel::download(
            $export,
            $food ? 'food_attendance_sheet.xlsx' : 'singlet_attendance_sheet.xlsx',
        );
    }

    private static function hasMultipleSelectedSubcategories(callable $get): bool
    {
        return collect((array) $get('subcategory'))
            ->filter(fn (mixed $id): bool => filled($id))
            ->unique()
            ->count() > 1;
    }

    private static function updateParticipantCount(callable $set, callable $get): int
    {
        $year = date('Y');
        $letterStart = strtoupper((string) $get('letterStart'));
        $letterEnd   = strtoupper((string) $get('letterEnd'));
        $selectedIds = collect((array) $get('subcategory'))
            ->filter(fn (mixed $id): bool => filled($id))
            ->unique()
            ->values();
        $subcategories = self::exportSubcategoriesForUser(
            Auth::user(),
            $selectedIds->all(),
        );
        $multipleSubcategories = $selectedIds->count() > 1;

        if (
            $selectedIds->isEmpty()
            || $subcategories->count() !== $selectedIds->count()
            || (! $multipleSubcategories && (! $letterStart || ! $letterEnd))
        ) {
            $set('participantCount', 0);

            return 0;
        }

        if (! $multipleSubcategories && $letterStart > $letterEnd) {
            [$letterStart, $letterEnd] = [$letterEnd, $letterStart];
        }

        $count = self::participantsForExport($subcategories)
            ->where('year', $year)
            ->when(
                ! $multipleSubcategories,
                fn (Builder $query): Builder => $query->whereRaw(
                    'SUBSTR(UPPER(TRIM(lastName)), 1, 1) BETWEEN ? AND ?',
                    [$letterStart, $letterEnd],
                ),
            )
            ->count();

        $set('participantCount', $count);

        return $count;
    }
}
