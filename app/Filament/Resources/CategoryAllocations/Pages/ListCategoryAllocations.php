<?php

namespace App\Filament\Resources\CategoryAllocations\Pages;

use App\Filament\Resources\CategoryAllocations\CategoryAllocationResource;
use App\Filament\Resources\CategoryAllocations\Widgets\AllocationOverview;
use App\Models\OverallAllocation;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ListCategoryAllocations extends ListRecords
{
    protected static string $resource = CategoryAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setOverallAllocation')
                ->label('Set Overall Allocation')
                ->icon('heroicon-o-calculator')
                ->visible(fn (): bool => Auth::user()?->hasAnyRole([
                    'admin',
                    'superadmin',
                    'super_admin',
                ]) ?? false)
                ->fillForm(fn (): array => [
                    'total_participants' => OverallAllocation::current()->total_participants,
                ])
                ->schema([
                    TextInput::make('total_participants')
                        ->label('Overall Participant Allocation')
                        ->helperText(fn (): string => 'Currently assigned to categories: '
                            . number_format(OverallAllocation::current()->assignedParticipants()))
                        ->numeric()
                        ->minValue(fn (): int => OverallAllocation::current()->assignedParticipants())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $allocation = OverallAllocation::current();
                    $total = (int) $data['total_participants'];
                    $assigned = $allocation->assignedParticipants();

                    if (! $allocation->canSetTotal($total)) {
                        throw ValidationException::withMessages([
                            'total_participants' => "Overall allocation cannot be lower than the {$assigned} participants already assigned to categories.",
                        ]);
                    }

                    $allocation->update(['total_participants' => $total]);

                    Notification::make()
                        ->success()
                        ->title('Overall allocation updated')
                        ->body(number_format($allocation->fresh()->remainingParticipants()) . ' participants remain available.')
                        ->send();

                    $this->redirect(CategoryAllocationResource::getUrl('index'));
                }),
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AllocationOverview::class,
        ];
    }
}
