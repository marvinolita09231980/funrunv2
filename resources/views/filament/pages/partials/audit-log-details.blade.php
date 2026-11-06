<div class="space-y-6">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Performed by</p>
            <p class="mt-2 break-words text-sm font-semibold text-gray-950 dark:text-white">{{ $log->actor_display_name }}</p>
            @if ($log->actor_username)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ '@' . $log->actor_username }}</p>
            @endif
        </div>
        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Date &amp; time</p>
            <p class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">{{ $log->created_at?->format('M j, Y g:i:s A') }}</p>
        </div>
        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Record</p>
            <p class="mt-2 break-words text-sm font-semibold text-gray-950 dark:text-white">{{ $log->subject_type_label }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ID {{ $log->auditable_id ?? 'Not available' }}</p>
        </div>
        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">IP address</p>
            <p class="mt-2 break-all text-sm font-semibold text-gray-950 dark:text-white">{{ $log->ip_address ?? 'Not available' }}</p>
        </div>
    </div>

    <section>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ $log->event === 'deleted' ? 'Deleted record values' : 'Before and after values' }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $log->subject_label ?: 'No record label was available.' }}
                </p>
            </div>
            <x-filament::badge :color="$log->event === 'deleted' ? 'danger' : 'warning'">
                {{ ucfirst($log->event) }}
            </x-filament::badge>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($log->changedFields() as $change)
                <article class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                    <header class="border-b border-gray-200 bg-gray-50 px-4 py-2.5 dark:border-white/10 dark:bg-white/5">
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $change['label'] }}</p>
                    </header>
                    <div @class([
                        'grid',
                        'sm:grid-cols-2' => $log->event !== 'deleted',
                    ])>
                        <div class="min-w-0 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ $log->event === 'deleted' ? 'Deleted value' : 'Before' }}
                            </p>
                            <pre class="mt-2 whitespace-pre-wrap break-words font-sans text-sm leading-6 text-gray-800 dark:text-gray-200">{{ \App\Models\AuditLog::displayValue($change['old']) }}</pre>
                        </div>
                        @if ($log->event !== 'deleted')
                            <div class="min-w-0 border-t border-gray-200 p-4 dark:border-white/10 sm:border-l sm:border-t-0">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">After</p>
                                <pre class="mt-2 whitespace-pre-wrap break-words font-sans text-sm leading-6 text-gray-800 dark:text-gray-200">{{ \App\Models\AuditLog::displayValue($change['new']) }}</pre>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-white/15 dark:text-gray-400">
                    No field values were captured for this activity.
                </div>
            @endforelse
        </div>
    </section>

    @if ($log->user_agent)
        <details class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <summary class="cursor-pointer text-sm font-semibold text-gray-950 dark:text-white">Browser information</summary>
            <p class="mt-3 break-words text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $log->user_agent }}</p>
        </details>
    @endif
</div>
