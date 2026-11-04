<x-filament-panels::page>
    @php($stats = $this->getShirtSizeStats())

    <style>
        .shirt-size-dashboard {
            display: grid;
            gap: 1.5rem;
        }

        .shirt-size-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.4rem;
            border: 1px solid rgba(37, 99, 235, 0.16);
            border-radius: 1rem;
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 58%, #eef2ff 100%);
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        }

        .shirt-size-eyebrow {
            margin: 0 0 0.3rem;
            color: #2563eb;
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .shirt-size-title {
            margin: 0;
            color: #111827;
            font-size: clamp(1.25rem, 3vw, 1.75rem);
            font-weight: 750;
            line-height: 1.2;
        }

        .shirt-size-subtitle {
            margin: 0.4rem 0 0;
            color: #6b7280;
            font-size: 0.84rem;
        }

        .shirt-size-year-control {
            flex: 0 0 auto;
            min-width: 9rem;
        }

        .shirt-size-year-control .table-year-filter {
            display: block;
        }

        .shirt-size-summary {
            display: grid;
            grid-template-columns: minmax(10rem, 1.35fr) repeat(9, minmax(5.4rem, 1fr));
            gap: 0.75rem;
        }

        .shirt-size-total-card,
        .shirt-size-card {
            min-width: 0;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.9rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: transform 150ms ease, box-shadow 150ms ease;
        }

        .shirt-size-total-card:hover,
        .shirt-size-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }

        .shirt-size-total-card {
            color: #ffffff;
            border-color: transparent;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
        }

        .shirt-size-card-label,
        .shirt-size-total-label {
            margin: 0;
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .shirt-size-card-label { color: #6b7280; }
        .shirt-size-total-label { color: rgba(255, 255, 255, 0.78); }

        .shirt-size-card-value,
        .shirt-size-total-value {
            margin: 0.4rem 0 0;
            font-size: 1.75rem;
            font-weight: 780;
            letter-spacing: -0.035em;
            line-height: 1;
        }

        .shirt-size-card-value { color: #111827; }
        .shirt-size-total-value { color: #ffffff; }

        .shirt-size-card:nth-child(3n + 2) { border-top: 3px solid #3b82f6; }
        .shirt-size-card:nth-child(3n + 3) { border-top: 3px solid #8b5cf6; }
        .shirt-size-card:nth-child(3n + 4) { border-top: 3px solid #f59e0b; }

        .shirt-size-detail-section {
            display: grid;
            gap: 0.75rem;
        }

        .shirt-size-section-title {
            margin: 0;
            color: #111827;
            font-size: 1rem;
            font-weight: 700;
        }

        .shirt-size-section-copy {
            margin: 0.2rem 0 0;
            color: #6b7280;
            font-size: 0.8rem;
        }

        .dark .shirt-size-header {
            border-color: rgba(96, 165, 250, 0.18);
            background: linear-gradient(135deg, #111c33 0%, #111827 58%, #17172f 100%);
        }

        .dark .shirt-size-title,
        .dark .shirt-size-card-value,
        .dark .shirt-size-section-title {
            color: #f9fafb;
        }

        .dark .shirt-size-subtitle,
        .dark .shirt-size-card-label,
        .dark .shirt-size-section-copy {
            color: #9ca3af;
        }

        .dark .shirt-size-card {
            border-color: rgba(255, 255, 255, 0.1);
            background: #111827;
        }

        @media (max-width: 1279px) {
            .shirt-size-summary {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }

            .shirt-size-total-card {
                grid-column: span 2;
            }
        }

        @media (max-width: 639px) {
            .shirt-size-dashboard { gap: 1rem; }

            .shirt-size-header {
                align-items: stretch;
                flex-direction: column;
                padding: 1.1rem;
            }

            .shirt-size-year-control {
                width: 100%;
            }

            .shirt-size-summary {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.6rem;
            }

            .shirt-size-total-card {
                grid-column: span 3;
            }

            .shirt-size-total-card,
            .shirt-size-card {
                padding: 0.85rem;
            }

            .shirt-size-card-value,
            .shirt-size-total-value {
                font-size: 1.5rem;
            }

            .shirt-size-detail-section {
                min-width: 0;
            }

            .shirt-size-detail-section .fi-ta-content-ctn {
                overflow-x: hidden;
            }

            .shirt-size-detail-section .fi-ta-header-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .shirt-size-detail-section .fi-ta-header-toolbar > *,
            .shirt-size-detail-section .fi-ta-search-field,
            .shirt-size-detail-section .fi-input-wrp {
                width: 100%;
                max-width: none;
            }

            .shirt-size-detail-section .fi-ta-cell-mobile-size-summary .fi-ta-col {
                padding: 1rem;
                white-space: normal;
            }
        }

        @media (max-width: 479px) {
            .shirt-size-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .shirt-size-total-card {
                grid-column: span 2;
            }
        }
    </style>

    <div class="shirt-size-dashboard">
        <section class="shirt-size-header">
            <div>
                <p class="shirt-size-eyebrow">{{ $stats['year'] }} Inventory Overview</p>
                <h2 class="shirt-size-title">Participant Shirt Sizes</h2>
                <p class="shirt-size-subtitle">Review shirt requirements and organization-level distribution for the selected year.</p>
            </div>

            <div class="shirt-size-year-control">
                @include('filament.pages.partials.table-year-filter')
            </div>
        </section>

        <section class="shirt-size-summary" aria-label="Shirt size totals">
            <article class="shirt-size-total-card">
                <p class="shirt-size-total-label">Total Shirts</p>
                <p class="shirt-size-total-value">{{ number_format($stats['total']) }}</p>
            </article>

            @foreach ($stats['sizes'] as $size)
                <article class="shirt-size-card">
                    <p class="shirt-size-card-label">{{ $size['label'] }}</p>
                    <p class="shirt-size-card-value">{{ number_format($size['count']) }}</p>
                </article>
            @endforeach
        </section>

        <section class="shirt-size-detail-section">
            <header>
                <h3 class="shirt-size-section-title">Sizes by Organization</h3>
                <p class="shirt-size-section-copy">Detailed shirt-size counts for each category and organization in {{ $stats['year'] }}.</p>
            </header>
            {{ $this->table }}
        </section>
    </div>
</x-filament-panels::page>
