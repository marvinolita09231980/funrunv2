<x-filament-panels::page>
    <style>
        .participant-report-preview {
            display: grid;
            gap: 1.25rem;
            padding: 1rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 0.85rem;
            background: #f8fafc;
        }

        .dark .participant-report-preview {
            border-color: rgba(255, 255, 255, 0.1);
            background: #111827;
        }

        .participant-report-preview-section {
            overflow: hidden;
            border: 1px solid #dbe3ec;
            border-radius: 0.75rem;
            background: #fff;
        }

        .dark .participant-report-preview-section {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.04);
        }

        .participant-report-preview-heading {
            margin: 0;
            padding: 0.85rem 1rem;
            color: #78350f;
            background: #fef3c7;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .dark .participant-report-preview-heading {
            color: #fde68a;
            background: rgba(146, 64, 14, 0.25);
        }

        .participant-report-preview-heading.is-excluded {
            color: #991b1b;
            background: #fee2e2;
        }

        .dark .participant-report-preview-heading.is-excluded {
            color: #fecaca;
            background: rgba(127, 29, 29, 0.3);
        }

        .participant-report-preview-table .action-column {
            width: 1%;
            text-align: center;
            white-space: nowrap;
        }

        .participant-report-preview-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .participant-report-preview-table {
            width: 100%;
            min-width: 64rem;
            border-collapse: collapse;
            font-size: 0.78rem;
        }

        .participant-report-preview-table th,
        .participant-report-preview-table td {
            padding: 0.7rem 0.75rem;
            border: 1px solid #dbe3ec;
            vertical-align: middle;
            text-align: left;
        }

        .participant-report-preview-table th {
            color: #fff;
            background: #1f2937;
            font-weight: 650;
            white-space: nowrap;
        }

        .participant-report-preview-table .group-row td {
            color: #78350f;
            background: #fffbeb;
            font-weight: 700;
        }

        .dark .participant-report-preview-table td {
            border-color: rgba(255, 255, 255, 0.1);
            color: #e5e7eb;
        }

        .dark .participant-report-preview-table .group-row td {
            color: #fde68a;
            background: rgba(146, 64, 14, 0.18);
        }

        .participant-report-modal-header {
            display: flex;
            width: 100%;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding-right: 0.25rem;
        }

        .participant-report-modal-header-actions {
            display: flex;
            flex: none;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        @media (max-width: 767px) {
            .participant-report-modal-header {
                align-items: stretch;
                flex-direction: column;
            }

            .participant-report-preview {
                gap: 1rem;
                padding: 0.625rem;
            }

            .participant-report-preview-section {
                overflow: visible;
            }

            .participant-report-preview-table-wrap {
                overflow: visible;
                padding: 0.75rem;
            }

            .participant-report-preview-table {
                display: block;
                min-width: 0;
                font-size: 0.84rem;
            }

            .participant-report-preview-table thead {
                display: none;
            }

            .participant-report-preview-table tbody {
                display: grid;
                gap: 0.75rem;
            }

            .participant-report-preview-table tr {
                display: grid;
                overflow: hidden;
                border: 1px solid #dbe3ec;
                border-radius: 0.75rem;
                background: #fff;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            }

            .dark .participant-report-preview-table tr {
                border-color: rgba(255, 255, 255, 0.12);
                background: rgba(255, 255, 255, 0.04);
            }

            .participant-report-preview-table td,
            .participant-report-preview-table .action-column {
                display: grid;
                width: auto;
                grid-template-columns: minmax(6.75rem, 38%) minmax(0, 1fr);
                gap: 0.75rem;
                align-items: center;
                padding: 0.65rem 0.75rem;
                border: 0;
                border-bottom: 1px solid #e5e7eb;
                text-align: right;
                white-space: normal;
                overflow-wrap: anywhere;
            }

            .dark .participant-report-preview-table td,
            .dark .participant-report-preview-table .action-column {
                border-bottom-color: rgba(255, 255, 255, 0.09);
            }

            .participant-report-preview-table td:last-child {
                border-bottom: 0;
            }

            .participant-report-preview-table td::before {
                content: attr(data-label);
                color: #64748b;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                line-height: 1.2;
                text-align: left;
                text-transform: uppercase;
            }

            .dark .participant-report-preview-table td::before {
                color: #94a3b8;
            }

            .participant-report-preview-table .action-column > * {
                justify-self: end;
            }

            .participant-report-preview-table .group-row {
                border: 0;
                box-shadow: none;
            }

            .participant-report-preview-table .group-row td {
                display: block;
                border: 1px solid #fde68a;
                border-radius: 0.65rem;
                text-align: left;
            }

            .dark .participant-report-preview-table .group-row td {
                border-color: rgba(245, 158, 11, 0.25);
            }

            .participant-report-preview-table .group-row td::before,
            .participant-report-preview-table .empty-row td::before {
                content: none;
            }

            .participant-report-preview-table .empty-row td {
                display: block;
                border: 0;
                text-align: center;
            }
        }
    </style>

    <div style="display: grid; gap: 1.5rem;">
        <x-filament::section heading="Participant Age Reports" description="Preview the top 10 oldest and youngest participants using separate or integrated gender rankings.">
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: end; justify-content: space-between;">
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: end;">
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

                    <label style="display: grid; gap: 0.5rem; min-width: 16rem;">
                        <span>Gender grouping</span>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="genderGrouping" aria-label="Gender grouping">
                                <option value="separate">Separate Male and Female</option>
                                <option value="integrated">Integrated Male and Female</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                    <x-filament::button
                        type="button"
                        wire:click="openSelectedAgeReportPreview"
                        wire:loading.attr="disabled"
                        wire:target="openSelectedAgeReportPreview"
                        color="gray"
                        icon="heroicon-o-eye"
                    >
                        {{ $genderGrouping === 'integrated'
                            ? 'View All 4 Integrated Reports'
                            : 'View All 8 Separate Reports' }}
                    </x-filament::button>
                </div>
            </div>
            <p style="margin-top: 1rem; font-size: 0.875rem; color: #6b7280;">
                Rankings use birth dates. Integrated mode ranks male and female participants together for each race distance. Reports include all categories, regardless of who registered the participants.
            </p>
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
                            Preview {{ $typeLabel }} Report
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        @foreach (['oldest' => 'Oldest', 'youngest' => 'Youngest'] as $ranking => $label)
            <x-filament::section :heading="'Top 10 '.$label.' Participants'" :description="'Individual PDF reports for '.$selectedYear.'.'">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr)); gap: 1rem;">
                    @foreach ($genderGrouping === 'integrated'
                        ? ['integrated' => 'Male & Female']
                        : ['male' => 'Male', 'female' => 'Female'] as $gender => $genderLabel)
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
                                        Preview Report
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
        :close-button="false"
        :sticky-header="true"
    >
        <x-slot name="heading">{{ $previewTitle }}</x-slot>
        <x-slot name="header">
            <div class="participant-report-modal-header">
                <div>
                    <h2 id="participant-report-pdf-preview.heading" class="fi-modal-heading">{{ $previewTitle }}</h2>
                    <p class="fi-modal-description">
                        Review the report data below. Marking a row as DQ refreshes the list and promotes the next eligible participant when applicable. DQ participants can be restored below.
                    </p>
                </div>
                <div class="participant-report-modal-header-actions">
                    <x-filament::button
                        size="sm"
                        icon="heroicon-o-arrow-down-tray"
                        wire:click="downloadPreview"
                        wire:loading.attr="disabled"
                        wire:target="downloadPreview"
                    >
                        Download PDF
                    </x-filament::button>
                    <x-filament::icon-button
                        size="sm"
                        color="gray"
                        icon="heroicon-m-x-mark"
                        label="Close report preview"
                        tooltip="Close"
                        x-on:click="$dispatch('close-modal', { id: 'participant-report-pdf-preview' })"
                    />
                </div>
            </div>
        </x-slot>

        @if ($previewReportType)
            @php($reportPreview = $this->previewReport())

            <div class="participant-report-preview" wire:loading.class="opacity-60" wire:target="excludePreviewParticipant">
                @if ($previewReportType === 'age')
                    @forelse ($reportPreview['sections'] ?? [] as $section)
                        <section class="participant-report-preview-section">
                            <h3 class="participant-report-preview-heading">{{ $section['title'] }}</h3>
                            <div class="participant-report-preview-table-wrap">
                                <table class="participant-report-preview-table">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Participant</th>
                                            <th>Birth Date</th>
                                            <th>Age</th>
                                            <th>Gender</th>
                                            <th>Phone Number</th>
                                            <th>Category</th>
                                            <th>Subcategory</th>
                                            <th class="action-column">DQ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($section['participants'] as $participant)
                                            <tr wire:key="age-preview-{{ $section['ranking'] }}-{{ $section['gender'] }}-{{ $section['distance'] }}-{{ $participant['participant_id'] }}">
                                                <td data-label="Rank">{{ $participant['rank'] }}</td>
                                                <td data-label="Participant">{{ $participant['name'] }}</td>
                                                <td data-label="Birth Date">{{ $participant['birth_date'] }}</td>
                                                <td data-label="Age">{{ $participant['age'] }}</td>
                                                <td data-label="Gender">{{ $participant['gender'] }}</td>
                                                <td data-label="Phone Number">{{ $participant['phone_number'] }}</td>
                                                <td data-label="Category">{{ $participant['category'] }}</td>
                                                <td data-label="Subcategory">{{ $participant['subcategory'] }}</td>
                                                <td class="action-column" data-label="DQ">
                                                    <x-filament::button
                                                        type="button"
                                                        size="xs"
                                                        color="danger"
                                                        outlined
                                                        icon="heroicon-m-x-mark"
                                                        aria-label="Mark {{ $participant['name'] }} as DQ"
                                                        wire:click="excludePreviewParticipant({{ $participant['participant_id'] }}, '{{ $section['ranking'] }}', '{{ $section['gender'] }}', '{{ $section['distance'] }}')"
                                                        wire:confirm="Mark {{ $participant['name'] }} as DQ for this report?"
                                                        wire:loading.attr="disabled"
                                                        wire:target="excludePreviewParticipant"
                                                    >
                                                        <span aria-hidden="true" style="display: block; width: 0; height: 0; overflow: hidden;">&nbsp;</span>
                                                    </x-filament::button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row"><td colspan="9">No eligible participants registered in this group.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @empty
                        <div>No age-report sections are available.</div>
                    @endforelse
                @elseif ($previewReportType === 'group')
                    <section class="participant-report-preview-section">
                        <h3 class="participant-report-preview-heading">
                            {{ $reportPreview['title'] ?? 'Runner List' }} · {{ $reportPreview['total_runners'] ?? 0 }} runner(s)
                        </h3>
                        <div class="participant-report-preview-table-wrap">
                            <table class="participant-report-preview-table">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Runner</th>
                                        <th>Birth Date</th>
                                        <th>Phone Number</th>
                                        <th>Gender</th>
                                        <th>Distance</th>
                                        <th>Shirt Size</th>
                                        <th class="action-column">DQ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reportPreview['groups'] ?? [] as $group)
                                        <tr class="group-row">
                                            <td colspan="8">
                                                Registration #{{ $group['reference'] }} · {{ $group['category'] }} · {{ count($group['members']) }} runner(s)
                                            </td>
                                        </tr>
                                        @foreach ($group['members'] as $member)
                                            <tr wire:key="group-preview-{{ $member['participant_id'] }}">
                                                <td data-label="No.">{{ $loop->iteration }}</td>
                                                <td data-label="Runner">{{ $member['name'] }}</td>
                                                <td data-label="Birth Date">{{ $member['birth_date'] }}</td>
                                                <td data-label="Phone Number">{{ $member['phone_number'] }}</td>
                                                <td data-label="Gender">{{ $member['gender'] }}</td>
                                                <td data-label="Distance">{{ $member['distance'] }}</td>
                                                <td data-label="Shirt Size">{{ $member['shirt_size'] }}</td>
                                                <td class="action-column" data-label="DQ">
                                                    <x-filament::button
                                                        type="button"
                                                        size="xs"
                                                        color="danger"
                                                        outlined
                                                        icon="heroicon-m-x-mark"
                                                        aria-label="Mark {{ $member['name'] }} as DQ"
                                                        wire:click="excludePreviewParticipant({{ $member['participant_id'] }})"
                                                        wire:confirm="Mark {{ $member['name'] }} as DQ for this report?"
                                                        wire:loading.attr="disabled"
                                                        wire:target="excludePreviewParticipant"
                                                    >
                                                        <span aria-hidden="true" style="display: block; width: 0; height: 0; overflow: hidden;">&nbsp;</span>
                                                    </x-filament::button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @empty
                                        <tr class="empty-row"><td colspan="8">No runners are available for this report.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                @php($excludedPreviewSections = $this->previewExcludedSections())
                @foreach ($excludedPreviewSections as $excludedSection)
                    <section class="participant-report-preview-section">
                        <h3 class="participant-report-preview-heading is-excluded">
                            DQ · {{ $excludedSection['title'] }} · {{ count($excludedSection['participants']) }} participant(s)
                        </h3>
                        <div class="participant-report-preview-table-wrap">
                            <table class="participant-report-preview-table">
                                <thead>
                                    <tr>
                                        <th>Participant</th>
                                        <th>Birth Date</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Phone Number</th>
                                        <th>Category</th>
                                        <th>Subcategory</th>
                                        <th class="action-column">Restore</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($excludedSection['participants'] as $participant)
                                        <tr wire:key="excluded-preview-{{ $excludedSection['report_key'] }}-{{ $participant['participant_id'] }}">
                                            <td data-label="Participant">{{ $participant['name'] }}</td>
                                            <td data-label="Birth Date">{{ $participant['birth_date'] }}</td>
                                            <td data-label="Age">{{ $participant['age'] }}</td>
                                            <td data-label="Gender">{{ $participant['gender'] }}</td>
                                            <td data-label="Phone Number">{{ $participant['phone_number'] }}</td>
                                            <td data-label="Category">{{ $participant['category'] }}</td>
                                            <td data-label="Subcategory">{{ $participant['subcategory'] }}</td>
                                            <td class="action-column" data-label="Restore">
                                                <x-filament::button
                                                    type="button"
                                                    size="xs"
                                                    color="success"
                                                    icon="heroicon-m-arrow-uturn-left"
                                                    wire:click="includePreviewParticipant({{ $participant['participant_id'] }}, '{{ $excludedSection['report_key'] }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="includePreviewParticipant"
                                                >
                                                    Restore
                                                </x-filament::button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endforeach
            </div>
        @endif

    </x-filament::modal>

</x-filament-panels::page>
