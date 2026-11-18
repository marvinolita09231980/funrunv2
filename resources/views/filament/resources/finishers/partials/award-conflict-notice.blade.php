@php
    use App\Models\Finisher;
    use Filament\Notifications\Notification;

    $holder = Finisher::awardHolder(
        $get('award_id'),
        $record instanceof Finisher ? $record->getKey() : null,
    );

    $notification = $holder
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
        : null;
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
