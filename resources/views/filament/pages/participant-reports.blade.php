<x-filament-panels::page>
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
                <x-filament::button icon="heroicon-o-arrow-down-tray" wire:click="downloadReport" wire:loading.attr="disabled" wire:target="downloadReport">Download All 8 Reports</x-filament::button>
            </div>
            <p style="margin-top: 1rem; font-size: 0.875rem; color: #6b7280;">Rankings use birth dates. Ages are calculated as of today for the current year, or December 31 for other years. Ties are ordered by name. Reports include all categories, regardless of who registered the participants.</p>
        </x-filament::section>

        <x-filament::section heading="Couple & Family Runner Lists" description="All categories for the selected year, grouped by registration reference, regardless of who entered the runners.">
            <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" wire:click="downloadRunnerList('couple')" wire:loading.attr="disabled" wire:target="downloadRunnerList">Couple Runners PDF</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" wire:click="downloadRunnerList('family')" wire:loading.attr="disabled" wire:target="downloadRunnerList">Family Runners PDF</x-filament::button>
            </div>
        </x-filament::section>

        @foreach (['oldest' => 'Oldest', 'youngest' => 'Youngest'] as $ranking => $label)
            <x-filament::section :heading="'Top 10 '.$label.' Participants'" :description="'Individual PDF reports for '.$selectedYear.'.'">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr)); gap: 1rem;">
                    @foreach (['male' => 'Male', 'female' => 'Female'] as $gender => $genderLabel)
                        @foreach (['5km' => '5K', '10km' => '10K'] as $distance => $distanceLabel)
                            <div style="padding: 1.25rem; border: 1px solid #9ca3af55; border-radius: 0.75rem; display: grid; gap: 1rem;">
                                <div><h3 style="font-weight: 600;">{{ $genderLabel }} · {{ $distanceLabel }}</h3><p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">10 {{ $ranking }} registered participants</p></div>
                                <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" wire:click="downloadReport('{{ $ranking }}', '{{ $gender }}', '{{ $distance }}')" wire:loading.attr="disabled" wire:target="downloadReport">Download PDF</x-filament::button>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
