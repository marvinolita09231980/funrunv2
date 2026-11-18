@php
    use App\Models\Award;
    use App\Models\Finisher;
    use App\Models\Participant;
    use Filament\Notifications\Notification;

    $selectedAward = Award::query()->find($get('award_id'));
    $selectedFinisher = $record instanceof Finisher
        ? $record
        : ($record instanceof Participant
            ? Finisher::query()->where('participants_id', $record->getKey())->first()
            : null);
    $categoryAward = $selectedFinisher?->awardInCategory($selectedAward?->category);
    $hasCategoryConflict = $categoryAward
        && (! ($record instanceof Finisher) || ! $categoryAward->is($selectedAward));
    $holder = Finisher::awardHolder(
        $get('award_id'),
        $selectedFinisher?->getKey(),
    );

    $notification = $hasCategoryConflict
        ? Notification::make()
            ->title('Runner already has an award in this category')
            ->body(
                $selectedFinisher->runner_name.' already received '.$categoryAward->name
                .' in '.$selectedAward->category.'. A finisher can receive only one award per category. '
                .'Choose an award from a different category.'
            )
            ->danger()
            ->persistent()
            ->inline()
        : ($holder
        ? Notification::make()
            ->title('Award has a current winner')
            ->body(
                $holder->runner_name.' already received this award'
                .(filled($holder->created_by) ? " (recorded by {$holder->created_by})" : '')
                .'. Enable “Transfer this award” only after the contest has been resolved.'
            )
            ->warning()
            ->persistent()
            ->inline()
        : null);
@endphp

@if ($notification)
    <div class="award-conflict-notice">
        <style>
            .award-conflict-notice .fi-no-notification-title {
                color: var(--warning-950);
                font-weight: 700;
            }

            .award-conflict-notice .fi-no-notification-body {
                color: var(--warning-900);
                font-weight: 500;
            }

            .award-conflict-notice .fi-no-notification-close-btn {
                color: var(--warning-800);
            }

            .dark .award-conflict-notice .fi-no-notification-title {
                color: var(--warning-100);
            }

            .dark .award-conflict-notice .fi-no-notification-body,
            .dark .award-conflict-notice .fi-no-notification-close-btn {
                color: var(--warning-200);
            }
        </style>

        {{ $notification }}
    </div>
@endif
