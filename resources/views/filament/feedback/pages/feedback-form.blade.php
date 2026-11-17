<x-filament-panels::page>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl shadow-gray-950/5 dark:border-white/10 dark:bg-gray-900">
        <section class="border-b border-gray-200 bg-gray-50/80 dark:border-white/10 dark:bg-white/5">
            <div class="flex flex-nowrap items-center justify-center gap-2 px-3 py-2.5 text-sm">
                <span class="shrink-0 whitespace-nowrap font-medium text-gray-600 dark:text-gray-300">
                    Already submitted?
                </span>
                <button
                    type="button"
                    class="group inline-flex shrink-0 flex-nowrap items-center gap-1 whitespace-nowrap font-semibold text-primary-600 transition hover:text-primary-700 focus-visible:rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:text-primary-400 dark:hover:text-primary-300 dark:focus-visible:ring-offset-gray-900"
                    style="display: inline-flex; align-items: center; flex-wrap: nowrap; gap: 0.25rem; white-space: nowrap;"
                    wire:click="openCertificateLookup"
                >
                    <span style="white-space: nowrap;">Download certificate</span>
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        style="display: block; width: 1rem; height: 1rem; flex: 0 0 1rem;"
                        class="transition-transform group-hover:translate-x-0.5"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5 5 5-5 5" />
                    </svg>
                </button>
            </div>
        </section>

        <form class="p-4 sm:p-5" wire:submit="create">
            {{ $this->form }}
            <div class="mt-5 border-t border-gray-200 pt-4 dark:border-white/10">
                <div class="flex w-full justify-end">
                    @if (! $data['feedback_exists'])
                        <x-filament::button
                            type="submit"
                            color="warning"
                            icon="heroicon-o-paper-airplane"
                            class="shadow-sm"
                            wire:loading.attr="disabled"
                            wire:target="create"
                        >
                            Submit Feedback
                        </x-filament::button>
                    @endif
                    @if ($data['feedback_exists'] && ($certificateUrl = $this->certificateUrl()))
                        <div class="flex w-full flex-col items-center justify-between gap-3 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-success-800 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-300 sm:flex-row">
                            <p class="flex items-center gap-2 text-sm font-semibold">
                                <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 shrink-0" />
                                Your feedback has already been submitted.
                            </p>
                            <x-filament::button
                                tag="a"
                                :href="$certificateUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                size="sm"
                                icon="heroicon-o-arrow-down-tray"
                            >
                                Download Certificate
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <x-filament::modal id="certificate-lookup" alignment="center" width="md">
        @if ($certificateUrl = $this->certificateLookupUrl())
            <div class="space-y-5 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-success-50 text-success-600 ring-8 ring-success-50/60 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-500/5">
                    <x-filament::icon icon="heroicon-o-check-badge" class="h-9 w-9" />
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                        Certificate found
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Your current-year feedback has been verified. Your certificate is ready to download.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 border-t border-gray-200 pt-5 dark:border-white/10 sm:grid-cols-2">
                    <x-filament::button
                        type="button"
                        color="gray"
                        outlined
                        size="lg"
                        class="w-full justify-center"
                        x-on:click="$dispatch('close-modal', { id: 'certificate-lookup' })"
                    >
                        Close
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        :href="$certificateUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        icon="heroicon-o-arrow-down-tray"
                        size="lg"
                        class="w-full justify-center"
                    >
                        Download Certificate
                    </x-filament::button>
                </div>
            </div>
        @else
            <x-slot name="heading">
                Find your certificate
            </x-slot>

            <x-slot name="description">
                Enter the same details used for your {{ now('Asia/Manila')->year }} registration.
            </x-slot>

            <form wire:submit="findCertificate" class="space-y-5">
                <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-400/20 dark:bg-amber-400/10">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-amber-600 shadow-sm dark:bg-amber-400/10 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">
                            Secure identity verification
                        </p>
                        <p class="mt-1 text-sm leading-5 text-gray-600 dark:text-gray-300">
                            Only a current-year participant with submitted feedback can retrieve a certificate.
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="block" for="certificate-first-name">
                        <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">First name</span>
                        <x-filament::input.wrapper prefix-icon="heroicon-m-user" :valid="! $errors->has('certificateLookup.firstName')">
                            <x-filament::input
                                id="certificate-first-name"
                                type="text"
                                wire:model="certificateLookup.firstName"
                                autocomplete="given-name"
                                placeholder="First name"
                                autofocus
                            />
                        </x-filament::input.wrapper>
                        @error('certificateLookup.firstName')
                            <p class="mt-1.5 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block" for="certificate-last-name">
                        <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Last name</span>
                        <x-filament::input.wrapper prefix-icon="heroicon-m-user" :valid="! $errors->has('certificateLookup.lastName')">
                            <x-filament::input
                                id="certificate-last-name"
                                type="text"
                                wire:model="certificateLookup.lastName"
                                autocomplete="family-name"
                                placeholder="Last name"
                            />
                        </x-filament::input.wrapper>
                        @error('certificateLookup.lastName')
                            <p class="mt-1.5 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block" for="certificate-birth-date">
                        <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Birth date</span>
                        <x-filament::input.wrapper prefix-icon="heroicon-m-calendar-days" :valid="! $errors->has('certificateLookup.birthDate')">
                            <x-filament::input
                                id="certificate-birth-date"
                                type="date"
                                wire:model="certificateLookup.birthDate"
                                max="{{ now('Asia/Manila')->toDateString() }}"
                                autocomplete="bday"
                            />
                        </x-filament::input.wrapper>
                        @error('certificateLookup.birthDate')
                            <p class="mt-1.5 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </label>
                </div>

                @error('certificateLookup.feedback')
                    <div role="alert" class="flex items-start gap-3 rounded-xl border border-danger-200 bg-danger-50 p-3.5 text-sm text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300">
                        <x-filament::icon icon="heroicon-o-exclamation-circle" class="h-5 w-5 shrink-0" />
                        <div>
                            <p class="font-semibold">Certificate not found</p>
                            <p class="mt-0.5 leading-5">{{ $message }}</p>
                        </div>
                    </div>
                @enderror

                <div class="grid grid-cols-1 gap-3 border-t border-gray-200 pt-5 dark:border-white/10 sm:grid-cols-2">
                    <x-filament::button
                        type="button"
                        color="gray"
                        outlined
                        size="lg"
                        class="w-full justify-center"
                        x-on:click="$dispatch('close-modal', { id: 'certificate-lookup' })"
                    >
                        Cancel
                    </x-filament::button>

                    <x-filament::button
                        type="submit"
                        icon="heroicon-o-magnifying-glass"
                        size="lg"
                        class="w-full justify-center"
                        wire:loading.attr="disabled"
                        wire:target="findCertificate"
                    >
                        <span wire:loading.remove wire:target="findCertificate">Find Certificate</span>
                        <span wire:loading wire:target="findCertificate">Checking...</span>
                    </x-filament::button>
                </div>
            </form>
        @endif
    </x-filament::modal>

    <x-filament::modal id="not-found" alignment="center" width="md">
        <div class="flex flex-col items-center justify-center space-y-6 p-8">
           
            <div class="flex items-center justify-center w-20 h-20 rounded-full bg-red-600/10 border-4 border-red-500 shadow-lg">
                <x-heroicon-o-x-circle class="w-12 h-12 text-red-600" />
            </div>

            
            <h2 class="text-3xl font-extrabold text-red-700 dark:text-red-400 text-center tracking-tight">
                {{ $errorTitle ?? 'Registration Failed' }}
            </h2>

           
            <p class="text-center text-gray-700 dark:text-gray-300 text-lg leading-relaxed">
                {{ $errorMessage ?? 'Something went wrong. Please try again.' }}
            </p>

          
            <div class="w-full border-t border-red-300 dark:border-red-700 my-2"></div>

            
            <div class="pt-2">
                <x-filament::button color="danger" size="lg"
                    x-on:click="$dispatch('close-modal', { id: 'not-found' })"
                    icon="heroicon-o-x-mark">
                    Close
                </x-filament::button>
            </div>
        </div>
    </x-filament::modal>
     
</x-filament-panels::page>
