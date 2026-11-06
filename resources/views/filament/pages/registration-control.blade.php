<x-filament-panels::page>
    @php
        $publicRegistrationUrl = rtrim(request()->root(), '/') . '/kalamboan/funrun-register';
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:col-span-2">
            <div @class([
                'h-1.5 w-full',
                'bg-emerald-500' => $registrationEnabled,
                'bg-red-500' => ! $registrationEnabled,
            ])></div>

            <div class="p-6 sm:p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                    <div @class([
                        'flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl',
                        'bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400' => $registrationEnabled,
                        'bg-red-50 text-red-600 dark:bg-red-400/10 dark:text-red-400' => ! $registrationEnabled,
                    ])>
                        <x-filament::icon
                            :icon="$registrationEnabled ? 'heroicon-o-check-circle' : 'heroicon-o-lock-closed'"
                            class="h-8 w-8"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-xl font-semibold text-gray-950 dark:text-white">
                                Kalamboan Fun Run Registration
                            </h2>

                            <x-filament::badge :color="$registrationEnabled ? 'success' : 'danger'">
                                {{ $registrationEnabled ? 'Active' : 'Inactive' }}
                            </x-filament::badge>
                        </div>

                        <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                            @if ($registrationEnabled)
                                The public form is accepting registrations. Visitors can enter participant details and submit new registrations.
                            @else
                                The public form is closed. Visitors will see a temporary closure notice, and new submissions are blocked.
                            @endif
                        </p>

                        <div class="mt-6 rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Public registration address
                            </p>
                            <a
                                href="{{ $publicRegistrationUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-2 inline-flex items-center gap-2 break-all text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                {{ $publicRegistrationUrl }}
                                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-4 w-4 shrink-0" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <aside class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                    <x-filament::icon icon="heroicon-o-information-circle" class="h-6 w-6" />
                </div>
                <h3 class="font-semibold text-gray-950 dark:text-white">How it works</h3>
            </div>

            <ul class="mt-5 space-y-4 text-sm leading-6 text-gray-600 dark:text-gray-300">
                <li class="flex gap-3">
                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary-500"></span>
                    Changes take effect immediately for all visitors.
                </li>
                <li class="flex gap-3">
                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary-500"></span>
                    Deactivation does not delete existing participant records.
                </li>
                <li class="flex gap-3">
                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary-500"></span>
                    Only administrators and super administrators can change this setting.
                </li>
            </ul>
        </aside>
    </div>
</x-filament-panels::page>
