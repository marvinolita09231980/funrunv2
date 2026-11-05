<x-filament-panels::page>
    {{ $this->content }}

    <x-filament::modal
        id="duplicate-participant"
        width="xl"
        alignment="center"
        :close-button="false"
        :close-by-clicking-away="false"
        :close-by-escaping="false"
        icon="heroicon-o-exclamation-triangle"
        icon-color="danger"
    >
        <x-slot name="heading">
            Registration Conflict
        </x-slot>

        <x-slot name="description">
            This participant is already registered for the current year. Review the existing record below before continuing.
        </x-slot>

        @if ($duplicateParticipant)
            <div class="space-y-6">
                {{-- Participant summary, based on the Kalamboan registration modal. --}}
                <div class="rounded-xl bg-gray-50 p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-danger-100 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400">
                            <x-filament::icon icon="heroicon-o-user" class="h-6 w-6" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Registered Participant
                            </p>
                            <h3 class="mt-1 break-words text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $duplicateParticipant['name'] }}
                            </h3>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <x-filament::badge color="danger">
                                    Already Registered
                                </x-filament::badge>
                                <x-filament::badge color="gray">
                                    Year {{ $duplicateParticipant['year'] }}
                                </x-filament::badge>
                            </div>
                        </div>
                    </div>
                </div>

                <section aria-labelledby="duplicate-registration-details">
                    <h4 id="duplicate-registration-details" class="text-sm font-semibold text-gray-950 dark:text-white">
                        Registration Details
                    </h4>

                    <dl class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ([
                            'birth_date' => 'Birth Date',
                            'category' => 'Main Category',
                            'distance' => 'Race Distance',
                            'shirt_size' => 'Shirt Size',
                        ] as $key => $label)
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                    {{ $label }}
                                </dt>
                                <dd class="mt-1 break-words text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $duplicateParticipant[$key] ?: 'Not recorded' }}
                                </dd>
                            </div>
                        @endforeach

                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700 sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                Subcategory / Organization
                            </dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $duplicateParticipant['subcategory'] ?: 'Not recorded' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="border-t border-gray-200 pt-5 dark:border-gray-700" aria-labelledby="duplicate-registration-history">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-clock" class="h-4 w-4 text-gray-400" />
                        <h4 id="duplicate-registration-history" class="text-sm font-semibold text-gray-950 dark:text-white">
                            Registration History
                        </h4>
                    </div>

                    <dl class="mt-3 space-y-3 text-sm">
                        <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Registered By</dt>
                            <dd class="break-words font-medium text-gray-950 dark:text-white sm:text-right">
                                {{ $duplicateParticipant['creator_name'] }}
                                @if ($duplicateParticipant['creator_role'])
                                    · {{ $duplicateParticipant['creator_role'] }}
                                @endif
                            </dd>
                        </div>

                        @if ($duplicateParticipant['creator_phone'])
                            <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                                <dt class="text-gray-500 dark:text-gray-400">Please contact</dt>
                                <dd class="font-medium sm:text-right">
                                    <a class="inline-flex items-center gap-2 text-danger-700 hover:underline dark:text-danger-300" href="tel:{{ $duplicateParticipant['creator_phone'] }}">
                                        <x-filament::icon icon="heroicon-o-phone" class="h-4 w-4" />
                                        {{ $duplicateParticipant['creator_phone'] }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </section>

                <div class="flex items-start gap-3 rounded-lg bg-danger-50 p-4 text-danger-800 dark:bg-danger-400/10 dark:text-danger-300">
                    <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5 shrink-0" />
                    <p class="text-sm leading-relaxed">
                        No additional registration is needed. If these details are incorrect, please contact the person who recorded this participant.
                    </p>
                </div>
            </div>
        @endif

        <x-slot name="footerActions">
            <x-filament::button
                color="danger"
                icon="heroicon-o-x-mark"
                wire:click="clearDuplicateParticipantForm"
            >
                Close and Clear Form
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
