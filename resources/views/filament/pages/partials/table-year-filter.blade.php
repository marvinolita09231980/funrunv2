<style>
    .fi-ta-header-toolbar > :nth-child(2) {
        flex-wrap: wrap;
    }

    .table-year-filter {
        min-width: 8rem;
        flex: 0 0 auto;
    }

    @media (max-width: 639px) {
        .table-year-filter {
            width: 100%;
        }
    }
</style>

<label class="table-year-filter">
    <span class="fi-sr-only">Year</span>

    <x-filament::input.wrapper prefix="Year">
        <x-filament::input.select
            wire:model.live="tableFilters.year.value"
            aria-label="Year"
        >
            @foreach (range((int) date('Y'), (int) date('Y') - 5) as $year)
                <option value="{{ $year }}">{{ $year }}</option>
            @endforeach
        </x-filament::input.select>
    </x-filament::input.wrapper>
</label>
