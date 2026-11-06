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
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .shirt-size-total-card,
        .shirt-size-card,
        .shirt-size-matrix-card {
            min-width: 0;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.9rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: transform 150ms ease, box-shadow 150ms ease;
        }

        .shirt-size-total-card:hover,
        .shirt-size-card:hover,
        .shirt-size-matrix-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }

        .shirt-size-total-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            grid-column: 1 / -1;
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

        .shirt-size-breakdown,
        .shirt-size-total-breakdown {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.5rem;
            margin-top: 0.9rem;
        }

        .shirt-size-total-breakdown {
            flex: 1 1 30rem;
            max-width: 42rem;
            margin-top: 0;
        }

        .shirt-size-distance {
            min-width: 0;
            padding: 0.65rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.7rem;
            background: #f9fafb;
        }

        .shirt-size-total-breakdown .shirt-size-distance {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.11);
        }

        .shirt-size-distance-label {
            display: block;
            overflow: hidden;
            color: #6b7280;
            font-size: 0.65rem;
            font-weight: 700;
            line-height: 1.2;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .shirt-size-distance-value {
            display: block;
            margin-top: 0.25rem;
            color: #111827;
            font-size: 1.05rem;
            font-weight: 780;
        }

        .shirt-size-total-breakdown .shirt-size-distance-label {
            color: rgba(255, 255, 255, 0.72);
        }

        .shirt-size-total-breakdown .shirt-size-distance-value {
            color: #ffffff;
        }

        .shirt-size-card:nth-child(3n + 2) { border-top: 3px solid #3b82f6; }
        .shirt-size-card:nth-child(3n + 3) { border-top: 3px solid #8b5cf6; }
        .shirt-size-card:nth-child(3n + 4) { border-top: 3px solid #f59e0b; }

        .shirt-size-matrix-card {
            grid-column: 1 / -1;
        }

        .shirt-size-matrix-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.85rem;
        }

        .shirt-size-matrix-title {
            margin: 0;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 750;
        }

        .shirt-size-matrix-copy {
            margin: 0.2rem 0 0;
            color: #6b7280;
            font-size: 0.76rem;
        }

        .shirt-size-matrix-legend {
            flex: 0 0 auto;
            color: #6b7280;
            font-size: 0.7rem;
        }

        .shirt-size-matrix-scroll {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            background: #ffffff;
            overscroll-behavior-inline: contain;
        }

        .shirt-size-matrix {
            width: 100%;
            min-width: 48rem;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }

        .shirt-size-matrix th,
        .shirt-size-matrix td {
            padding: 0.75rem 0.45rem;
            border-right: 1px solid #eef2f7;
            border-bottom: 1px solid #eef2f7;
            text-align: center;
            vertical-align: middle;
        }

        .shirt-size-matrix th:last-child,
        .shirt-size-matrix td:last-child {
            border-right: 0;
        }

        .shirt-size-matrix thead th {
            color: #475569;
            background: #f8fafc;
            font-size: 0.68rem;
            font-weight: 780;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .shirt-size-matrix thead th:first-child,
        .shirt-size-matrix tbody th,
        .shirt-size-matrix tfoot th {
            position: sticky;
            left: 0;
            z-index: 2;
            width: 10rem;
            text-align: left;
        }

        .shirt-size-matrix thead th:first-child { z-index: 3; }
        .shirt-size-matrix tbody th { background: #ffffff; }

        .shirt-size-matrix tbody tr:last-child th,
        .shirt-size-matrix tbody tr:last-child td {
            border-bottom-color: #dbeafe;
        }

        .shirt-size-matrix tfoot th,
        .shirt-size-matrix tfoot td {
            border-bottom: 0;
            color: #1e3a8a;
            background: #eff6ff;
            font-weight: 800;
        }

        .shirt-size-matrix tfoot th { background: #eff6ff; }

        .shirt-size-distance-badge {
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            padding: 0.36rem 0.58rem;
            border-radius: 999px;
            color: #1d4ed8;
            background: #dbeafe;
            font-size: 0.7rem;
            font-weight: 750;
            line-height: 1.2;
            white-space: normal;
        }

        .shirt-size-cell-value {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.8rem;
            min-height: 1.8rem;
            padding: 0.2rem;
            border-radius: 0.5rem;
            color: #1e40af;
            background: #eff6ff;
            font-size: 0.82rem;
            font-weight: 780;
        }

        .shirt-size-cell-value.is-zero {
            color: #94a3b8;
            background: transparent;
            font-weight: 650;
        }

        .shirt-size-row-total {
            color: #111827;
            font-size: 0.9rem;
            font-weight: 820;
        }

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
        .dark .shirt-size-matrix-title,
        .dark .shirt-size-section-title {
            color: #f9fafb;
        }

        .dark .shirt-size-subtitle,
        .dark .shirt-size-card-label,
        .dark .shirt-size-matrix-copy,
        .dark .shirt-size-matrix-legend,
        .dark .shirt-size-section-copy {
            color: #9ca3af;
        }

        .dark .shirt-size-card,
        .dark .shirt-size-matrix-card,
        .dark .shirt-size-matrix-scroll {
            border-color: rgba(255, 255, 255, 0.1);
            background: #111827;
        }

        .dark .shirt-size-distance {
            border-color: rgba(255, 255, 255, 0.1);
            background: #182235;
        }

        .dark .shirt-size-distance-label { color: #9ca3af; }
        .dark .shirt-size-distance-value { color: #f9fafb; }

        .dark .shirt-size-matrix th,
        .dark .shirt-size-matrix td {
            border-color: rgba(255, 255, 255, 0.08);
        }

        .dark .shirt-size-matrix thead th { color: #cbd5e1; background: #182235; }
        .dark .shirt-size-matrix tbody th { background: #111827; }
        .dark .shirt-size-matrix tfoot th,
        .dark .shirt-size-matrix tfoot td { color: #bfdbfe; background: #172554; }
        .dark .shirt-size-distance-badge { color: #bfdbfe; background: #1e3a8a; }
        .dark .shirt-size-cell-value { color: #bfdbfe; background: #1e3a5f; }
        .dark .shirt-size-cell-value.is-zero { color: #64748b; background: transparent; }
        .dark .shirt-size-row-total { color: #f9fafb; }

        @media (max-width: 1279px) {
            .shirt-size-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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
                grid-template-columns: 1fr;
                gap: 0.6rem;
            }

            .shirt-size-total-card {
                align-items: stretch;
                flex-direction: column;
            }

            .shirt-size-total-card,
            .shirt-size-card,
            .shirt-size-matrix-card {
                padding: 0.85rem;
            }

            .shirt-size-card-value,
            .shirt-size-total-value {
                font-size: 1.5rem;
            }

            .shirt-size-matrix-header {
                align-items: flex-start;
                flex-direction: column;
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
            .shirt-size-breakdown,
            .shirt-size-total-breakdown {
                grid-template-columns: 1fr;
            }

            .shirt-size-distance-label {
                overflow: visible;
                text-overflow: clip;
                white-space: normal;
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
                <div>
                    <p class="shirt-size-total-label">Total Shirts</p>
                    <p class="shirt-size-total-value">{{ number_format($stats['total']) }}</p>
                </div>

                <div class="shirt-size-total-breakdown" aria-label="Total shirts by race distance">
                    <div class="shirt-size-distance">
                        <span class="shirt-size-distance-label">Rave Run (3K)</span>
                        <strong class="shirt-size-distance-value">{{ number_format($stats['distance_totals']['3km']) }}</strong>
                    </div>
                    <div class="shirt-size-distance">
                        <span class="shirt-size-distance-label">5K</span>
                        <strong class="shirt-size-distance-value">{{ number_format($stats['distance_totals']['5km']) }}</strong>
                    </div>
                    <div class="shirt-size-distance">
                        <span class="shirt-size-distance-label">10K</span>
                        <strong class="shirt-size-distance-value">{{ number_format($stats['distance_totals']['10km']) }}</strong>
                    </div>
                </div>
            </article>

            <article class="shirt-size-matrix-card">
                <header class="shirt-size-matrix-header">
                    <div>
                        <h3 class="shirt-size-matrix-title">Shirt Size by Race Distance</h3>
                        <p class="shirt-size-matrix-copy">Each race distance is one row, with shirt sizes arranged in columns.</p>
                    </div>
                    <span class="shirt-size-matrix-legend">Selected year: {{ $stats['year'] }}</span>
                </header>

                <div
                    class="shirt-size-matrix-scroll"
                    role="region"
                    aria-label="Shirt size counts by race distance"
                    tabindex="0"
                >
                    <table class="shirt-size-matrix">
                        <thead>
                            <tr>
                                <th scope="col">Race Distance</th>
                                @foreach ($stats['sizes'] as $size)
                                    <th scope="col">{{ $size['label'] }}</th>
                                @endforeach
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ([
                                '3km' => 'Rave Run (3K)',
                                '5km' => '5K',
                                '10km' => '10K',
                            ] as $distanceKey => $distanceLabel)
                                <tr>
                                    <th scope="row">
                                        <span class="shirt-size-distance-badge">{{ $distanceLabel }}</span>
                                    </th>
                                    @foreach ($stats['sizes'] as $size)
                                        @php($count = $size['distances'][$distanceKey]['count'])
                                        <td>
                                            <span @class(['shirt-size-cell-value', 'is-zero' => $count === 0])>
                                                {{ number_format($count) }}
                                            </span>
                                        </td>
                                    @endforeach
                                    <td>
                                        <strong class="shirt-size-row-total">{{ number_format($stats['distance_totals'][$distanceKey]) }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th scope="row">All Distances</th>
                                @foreach ($stats['sizes'] as $size)
                                    <td>{{ number_format($size['count']) }}</td>
                                @endforeach
                                <td>{{ number_format($stats['total']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </article>
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
