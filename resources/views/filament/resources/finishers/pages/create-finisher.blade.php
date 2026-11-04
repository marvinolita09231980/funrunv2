<x-filament-panels::page>
    <style>
        .create-finisher-table .fi-ta-filters-header {
            display: none;
        }

        @media (max-width: 767px) {
            .create-finisher-table .fi-ta-filters-above-content-ctn {
                padding: 1rem;
            }

            .create-finisher-table .fi-ta-record-content-ctn {
                gap: 1rem;
                padding: 1rem;
            }

            .create-finisher-table .fi-ta-record .fi-ta-actions {
                width: 100%;
                padding: 0;
            }

            .create-finisher-table .fi-ta-record .fi-ta-actions .fi-btn {
                width: 100%;
                justify-content: center;
                white-space: nowrap;
            }
        }
    </style>

    <div class="create-finisher-table">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
