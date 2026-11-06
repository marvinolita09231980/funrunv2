<div>
    @if ($shouldPrompt)
        <div
            wire:init="openPrompt"
        >
            <x-filament::modal
                id="contact-information-prompt"
                width="lg"
                alignment="center"
                :close-button="false"
                :close-by-clicking-away="false"
                :close-by-escaping="false"
                icon="heroicon-o-envelope"
                icon-color="primary"
                wire:submit.prevent="save"
            >
                <x-slot name="heading">
                    Complete Your Contact Information
                </x-slot>

                <x-slot name="description">
                    Add an email address for password recovery and account lookup. You may save these details now or continue without updating them.
                </x-slot>

                <div class="space-y-5">
                    <div class="rounded-xl bg-primary-50 p-4 ring-1 ring-primary-600/10 dark:bg-primary-400/10 dark:ring-primary-400/20">
                        <div class="flex items-start gap-3">
                            <x-filament::icon icon="heroicon-o-shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-primary-600 dark:text-primary-400" />
                            <div>
                                <p class="text-sm font-semibold text-primary-900 dark:text-primary-100">
                                    Why add an email?
                                </p>
                                <p class="mt-1 text-sm leading-relaxed text-primary-800 dark:text-primary-200">
                                    If you forget your password or username, enter this email address on the recovery page to find and recover your account.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-gray-950 dark:text-white">
                                Email Address
                            </span>
                            <x-filament::input.wrapper :valid="! $errors->has('email')">
                                <x-filament::input
                                    type="email"
                                    wire:model="email"
                                    autocomplete="email"
                                    placeholder="name@example.com"
                                />
                            </x-filament::input.wrapper>
                            @error('email')
                                <p class="mt-1.5 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-gray-950 dark:text-white">
                                Cellphone Number
                            </span>
                            <x-filament::input.wrapper :valid="! $errors->has('cellphoneNumber')">
                                <x-filament::input
                                    type="tel"
                                    wire:model="cellphoneNumber"
                                    autocomplete="tel"
                                    placeholder="09XX XXX XXXX"
                                />
                            </x-filament::input.wrapper>
                            @error('cellphoneNumber')
                                <p class="mt-1.5 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                        These details are stored with your account and are only available to authorized system administrators.
                    </p>
                </div>

                <x-slot name="footer">
                    <div class="flex w-full flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <x-filament::button
                            type="button"
                            color="gray"
                            wire:click="dismiss"
                        >
                            Not Now, Continue
                        </x-filament::button>

                        <x-filament::button type="submit" icon="heroicon-o-check">
                            Save Contact Information
                        </x-filament::button>
                    </div>
                </x-slot>
            </x-filament::modal>
        </div>
    @endif
</div>
