<x-filament-panels::page>
    <style>
        .create-finisher-table .fi-ta-filters-header {
            display: none;
        }

        @media (max-width: 767px) {
            .create-finisher-table .fi-ta-filters-above-content-ctn {
                padding: 1rem;
            }

            .create-finisher-table .fi-ta-table > thead {
                display: none;
            }

            .create-finisher-table .fi-ta-table > tbody {
                display: grid;
                gap: 1rem;
                padding: 1rem;
                white-space: normal;
            }

            .create-finisher-table .fi-ta-table .fi-ta-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                overflow: hidden;
                border: 1px solid rgba(148, 163, 184, 0.35);
                border-radius: 0.75rem;
                background: #fff;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            }

            .dark .create-finisher-table .fi-ta-table .fi-ta-row {
                border-color: rgba(255, 255, 255, 0.1);
                background: rgba(255, 255, 255, 0.04);
            }

            .create-finisher-table .fi-ta-cell-mobile-runner-summary {
                display: block;
            }

            .create-finisher-table .fi-ta-row > .fi-ta-cell:has(.fi-ta-actions) {
                display: block;
                width: 100%;
                padding: 0 1rem 1rem;
            }

            .create-finisher-table .fi-ta-row .fi-ta-actions,
            .create-finisher-table .fi-ta-row .fi-ta-actions .fi-btn {
                width: 100%;
            }

            .create-finisher-table .fi-ta-row .fi-ta-actions .fi-btn {
                justify-content: center;
                white-space: nowrap;
            }
        }
    </style>

    <div class="create-finisher-table">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
