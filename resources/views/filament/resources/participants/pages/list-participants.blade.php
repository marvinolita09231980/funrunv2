<x-filament-panels::page>
    <style>
        .participants-table .fi-ta-header-toolbar > :nth-child(2) {
            flex-wrap: wrap;
        }

        .participants-table .table-year-filter {
            min-width: 8rem;
            flex: 0 0 auto;
        }
    </style>

    <div class="participants-table">
        {{ $this->table }}
    </div>

    {{-- Custom Modal --}}
    <x-filament::modal id="no-participants-found">
        <x-slot name="heading">
            No Participants Found
        </x-slot>

        <p class="text-gray-600">
            There are no participants for the selected subcategory.
        </p>

        <x-slot name="footer">
            <x-filament::button color="primary" x-on:click="$dispatch('close-modal', { id: 'no-participants-found' })">
                OK
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
