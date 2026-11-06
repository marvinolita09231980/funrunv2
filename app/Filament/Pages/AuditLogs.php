<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\CategoryAllocation;
use App\Models\Feedback;
use App\Models\Finisher;
use App\Models\OverallAllocation;
use App\Models\Participant;
use App\Models\RegistrationSetting;
use App\Models\Subcategory;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static ?int $navigationSort = 101;

    protected string $view = 'filament.pages.audit-logs';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && $user->isSuperAdmin();
    }

    public function getTitle(): string
    {
        return 'Audit Logs';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AuditLog::query())
            ->columns([
                TextColumn::make('mobile_summary')
                    ->label('Activity')
                    ->getStateUsing(fn (AuditLog $record): string => ucfirst($record->event)
                        .' '.$record->subject_type_label)
                    ->description(fn (AuditLog $record): string => collect([
                        $record->subject_label,
                        $record->actor_display_name,
                        $record->created_at?->format('M j, Y g:i A'),
                    ])->filter()->join(' • '))
                    ->weight('bold')
                    ->wrap()
                    ->hiddenFrom('md'),
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('event')
                    ->label('Action')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deleted' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('actor_display_name')
                    ->label('Performed By')
                    ->description(fn (AuditLog $record): ?string => filled($record->actor_username)
                        ? "@{$record->actor_username}"
                        : null)
                    ->searchable(['actor_name', 'actor_username'])
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('subject_type_label')
                    ->label('Record')
                    ->description(fn (AuditLog $record): string => collect([
                        $record->subject_label,
                        filled($record->auditable_id) ? "ID {$record->auditable_id}" : null,
                    ])->filter()->join(' • '))
                    ->searchable(['subject_label', 'auditable_id'])
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('change_summary')
                    ->label('What Changed')
                    ->wrap()
                    ->limit(55)
                    ->tooltip(fn (AuditLog $record): string => $record->change_summary)
                    ->visibleFrom('lg'),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Action')
                    ->options([
                        'updated' => 'Edited',
                        'deleted' => 'Deleted',
                    ]),
                SelectFilter::make('auditable_type')
                    ->label('Record Type')
                    ->options(self::recordTypeOptions()),
            ])
            ->heading('Edit and Delete History')
            ->description('A permanent record of who changed or deleted application data and when it happened.')
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->recordUrl(null)
            ->recordActions([
                Action::make('details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->tooltip('View audit details')
                    ->modalHeading(fn (AuditLog $record): string => ucfirst($record->event)
                        .' '.$record->subject_type_label)
                    ->modalDescription(fn (AuditLog $record): string => collect([
                        $record->subject_label,
                        filled($record->auditable_id) ? "Record ID {$record->auditable_id}" : null,
                    ])->filter()->join(' • '))
                    ->modalWidth('4xl')
                    ->modalContent(fn (AuditLog $record) => view(
                        'filament.pages.partials.audit-log-details',
                        ['log' => $record],
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->emptyStateHeading('No audit activity yet')
            ->emptyStateDescription('Edits and deletions will appear here automatically.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }

    public static function recordTypeOptions(): array
    {
        return [
            Participant::class => 'Participant',
            Finisher::class => 'Finisher',
            User::class => 'User',
            Subcategory::class => 'Subcategory',
            CategoryAllocation::class => 'Category Allocation',
            OverallAllocation::class => 'Overall Allocation',
            RegistrationSetting::class => 'Registration Setting',
            Feedback::class => 'Feedback',
        ];
    }
}
