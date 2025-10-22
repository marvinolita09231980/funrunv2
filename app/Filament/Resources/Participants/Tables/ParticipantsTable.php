<?php

namespace App\Filament\Resources\Participants\Tables;

use Filament\Tables\Table;
use App\Models\Subcategory;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
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
            
            ->columns([
                TextColumn::make('firstName')->label('First Name')
                ->searchable(),
                TextColumn::make('lastName')->label('Last Name')
                ->searchable(),
                TextColumn::make('subDescription')->label('Category')
                ->searchable(),
                TextColumn::make('gender')->label('Gender')
                ->searchable(),
            ])
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
                ExportAction::make('attendance_export')
                ->label('Attendance Export')
                ->exporter(ParticipantExporter::class)
                ->columnMapping(false)
                ->schema([
                    Select::make('subcategory')
                        ->label('Subcategory')
                        ->options(Subcategory::pluck('subDescription', 'subDescription'))
                        ->searchable()
                        ->required(),
                     Select::make('distanceCategory')
                        ->label('Distance')
                        ->options([
                                            '3km' =>    '3KM',
                                            '5km' =>    '5KM',
                                            '10km' =>   '10KM',
                                        ])
                        ->searchable()
                        
                ])
                ->modifyQueryUsing(function(Builder $query,array $data){
                    
                    $category = $data['subcategory']; // get selected category
                    $distance = $data['distanceCategory'];
                     
                    if (!$category) {
                        return []; 
                    }
                    if(!$distance)
                    {
                        $queryResult = $query 
                        ->where('subDescription', $category);
                        return $queryResult;
                    }
                    else{
                        $queryResult = $query 
                        ->where('subDescription', $category)
                        ->Where('distanceCategory', $distance);
                        return $queryResult;
                    }

                })
                ->fileName(fn (array $data): string =>
                    'participants-' . str($data['subcategory'])->slug()
                ),
                
                   
                
                     
                            
            
            ]);
            
    }
}
