<x-filament-panels::page>
    <style>
        .participant-report-preview {
            position: relative;
            min-height: min(72vh, 54rem);
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 0.85rem;
            background: #e5e7eb;
        }

        .dark .participant-report-preview {
            border-color: rgba(255, 255, 255, 0.1);
            background: #111827;
        }

        .participant-report-preview iframe {
            display: block;
            width: 100%;
            min-height: min(72vh, 54rem);
            border: 0;
            background: #fff;
            transition: opacity 180ms ease;
        }

        .participant-report-preview-loading {
            position: absolute;
            z-index: 1;
            inset: 0;
            display: grid;
            place-content: center;
            gap: 0.8rem;
            color: #475569;
            background: #f8fafc;
            text-align: center;
        }

        .dark .participant-report-preview-loading {
            color: #cbd5e1;
            background: #111827;
        }

        @media (max-width: 767px) {
            .participant-report-preview,
            .participant-report-preview iframe {
                min-height: 65vh;
            }
        }
    </style>

    <div style="display: grid; gap: 1.5rem;">
        <x-filament::section heading="Participant Age Reports" description="Download the top 10 oldest and youngest participants by gender and race distance.">
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: end; justify-content: space-between;">
                <label style="display: grid; gap: 0.5rem; min-width: 10rem;">
                    <span>Registration year</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedYear" aria-label="Registration year">
                            @foreach ($this->years() as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                    <x-filament::button
                        type="button"
                        wire:click="openAgeReportPreview"
                        wire:loading.attr="disabled"
                        wire:target="openAgeReportPreview"
                        color="gray"
                        icon="heroicon-o-eye"
                    >
                        View All 8 Reports
                    </x-filament::button>
                </div>
            </div>
            <p style="margin-top: 1rem; font-size: 0.875rem; color: #6b7280;">Rankings use birth dates. Ages are calculated as of today for the current year, or December 31 for other years. Ties are ordered by name. Reports include all categories, regardless of who registered the participants.</p>
        </x-filament::section>

        <x-filament::section heading="Couple & Family Runner Lists" description="All categories for the selected year, grouped by registration reference, regardless of who entered the runners.">
            <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                @foreach (['couple' => 'Couple', 'family' => 'Family'] as $type => $typeLabel)
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        <x-filament::button
                            type="button"
                            wire:click="openRunnerListPreview('{{ $type }}')"
                            wire:loading.attr="disabled"
                            wire:target="openRunnerListPreview"
                            color="gray"
                            outlined
                            icon="heroicon-o-eye"
                        >
                            View {{ $typeLabel }} PDF
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        @foreach (['oldest' => 'Oldest', 'youngest' => 'Youngest'] as $ranking => $label)
            <x-filament::section :heading="'Top 10 '.$label.' Participants'" :description="'Individual PDF reports for '.$selectedYear.'.'">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr)); gap: 1rem;">
                    @foreach (['male' => 'Male', 'female' => 'Female'] as $gender => $genderLabel)
                        @foreach (['5km' => '5K', '10km' => '10K'] as $distance => $distanceLabel)
                            <div style="padding: 1.25rem; border: 1px solid #9ca3af55; border-radius: 0.75rem; display: grid; gap: 1rem;">
                                <div><h3 style="font-weight: 600;">{{ $genderLabel }} · {{ $distanceLabel }}</h3><p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">10 {{ $ranking }} registered participants</p></div>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    <x-filament::button
                                        type="button"
                                        wire:click="openAgeReportPreview('{{ $ranking }}', '{{ $gender }}', '{{ $distance }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="openAgeReportPreview"
                                        color="gray"
                                        outlined
                                        icon="heroicon-o-eye"
                                    >
                                        View PDF
                                    </x-filament::button>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::modal
        id="participant-report-pdf-preview"
        width="7xl"
        :close-by-clicking-away="false"
    >
        <x-slot name="heading">{{ $previewTitle }}</x-slot>
        <x-slot name="description">
            Review the generated document below or download a copy for printing and records.
        </x-slot>

        @if ($previewUrl)
            <div
                wire:key="participant-report-preview-{{ md5($previewUrl) }}"
                class="participant-report-preview"
                x-data="{ loaded: false }"
            >
                <div
                    x-show="! loaded"
                    class="participant-report-preview-loading"
                    role="status"
                    aria-live="polite"
                >
                    <x-filament::loading-indicator class="mx-auto h-8 w-8" />
                    <div>
                        <p style="font-weight: 650;">Generating PDF preview</p>
                        <p style="margin-top: 0.25rem; font-size: 0.8rem;">Please wait while the report is prepared.</p>
                    </div>
                </div>

                <iframe
                    src="{{ $previewUrl }}"
                    title="{{ $previewTitle }}"
                    x-on:load="loaded = true"
                    x-bind:style="loaded ? 'opacity: 1' : 'opacity: 0'"
                ></iframe>
            </div>
        @endif

        <x-slot name="footerActions">
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'participant-report-pdf-preview' })"
            >
                Close
            </x-filament::button>
            <x-filament::button
                icon="heroicon-o-arrow-down-tray"
                wire:click="downloadPreview"
                wire:loading.attr="disabled"
                wire:target="downloadPreview"
            >
                Download PDF
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
