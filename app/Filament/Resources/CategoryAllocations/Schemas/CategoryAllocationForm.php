<?php

namespace App\Filament\Resources\CategoryAllocations\Schemas;

use App\Models\CategoryAllocation;
use App\Models\OverallAllocation;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
                    ->helperText('An Administrative Officer may be assigned to multiple main categories. You may also assign yourself.')
                    ->relationship(
                        'administrativeOfficers',
                        'name',
                        modifyQueryUsing: fn (Builder $query, ?CategoryAllocation $record): Builder => self::applyAdministrativeOfficerOptionScope(
                            $query,
                            Auth::user(),
                            $record,
                        ),
                    )
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

    public static function applyAdministrativeOfficerOptionScope(
        Builder $query,
        ?User $actor,
        ?CategoryAllocation $record = null,
    ): Builder {
        $assignedUserIds = $record?->administrativeOfficers()
            ->pluck('users.id')
            ->all() ?? [];

        return $query->where(function (Builder $query) use ($actor, $assignedUserIds): void {
            $query->whereHas(
                'roles',
                fn (Builder $roleQuery): Builder => $roleQuery->whereIn(
                    'name',
                    ['administrative officer', 'ao'],
                ),
            );

            if ($actor?->hasAnyRole(['admin', 'superadmin', 'super_admin'])) {
                $query->orWhere(
                    $query->getModel()->getQualifiedKeyName(),
                    $actor->getKey(),
                );
            }

            if ($assignedUserIds !== []) {
                $query->orWhereIn(
                    $query->getModel()->getQualifiedKeyName(),
                    $assignedUserIds,
                );
            }
        });
    }
}
