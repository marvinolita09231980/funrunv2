<?php

namespace App\Filament\Resources\Participants\Tables;

use Filament\Tables\Table;
use App\Models\Subcategory;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use App\Exports\AttendanceSheetExport;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
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
            })
            ->columns([
                TextColumn::make('firstName')->label('First Name')
                ->searchable(),
                TextColumn::make('lastName')->label('Last Name')
                ->searchable(),
                TextColumn::make('subDescription')->label('Category')
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
                // ExportAction::make('attendance_export')
                // ->label('Attendance Export')
                // ->action(fn() => Excel::download(new AttendanceSheetExport, 'attendance_sheet.xlsx'))
                // ->columnMapping(false)
                // ->schema([
                //     Select::make('subcategory')
                //         ->label('Subcategory')
                //         ->options(Subcategory::pluck('subDescription', 'subDescription'))
                //         ->searchable()
                //         ->required(),
                        
                // ])
                // ->modifyQueryUsing(function(Builder $query,array $data){
                    
                //     $category = $data['subcategory']; // get selected category
                //     if (!$category) {
                //         return []; 
                //     }
                   
                //     $queryResult = $query 
                //     ->where('year', date('Y'))
                //     ->where('subDescription', $category);
                   
                //     return $queryResult;
                   

                // })
                // ->fileName(fn (array $data): string =>
                //     'participants-' . str($data['subcategory'])->slug()
                // ),
                Action::make('attendance_export')
                    ->label('Attendance Export')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (array $data) {
                        return Excel::download(
                            new AttendanceSheetExport($data['subcategory']),
                            'attendance_sheet.xlsx'
                        );
                    })
                    ->form([
                        Select::make('subcategory')
                            ->label('Subcategory')
                            ->options(Subcategory::pluck('subDescription', 'subDescription'))
                            ->searchable()
                            ->required(),
                    ]),
                                
                
                     
                            
            
            ]);
            
    }
}
