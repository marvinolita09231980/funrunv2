<x-filament-panels::page>
    @php($stats = $this->getDashboardStats())

    <style>
        .professional-dashboard {
            display: grid;
            gap: 1.5rem;
        }

        .dashboard-hero {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            overflow: hidden;
            padding: 1.5rem;
            border: 1px solid rgba(245, 158, 11, 0.22);
            border-radius: 1rem;
            background: linear-gradient(135deg, #fffbeb 0%, #ffffff 55%, #fef3c7 100%);
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }

        .dashboard-hero::after {
            position: absolute;
            top: -5rem;
            right: -4rem;
            width: 14rem;
            height: 14rem;
            border-radius: 9999px;
            background: rgba(245, 158, 11, 0.09);
            content: '';
        }

        .dashboard-eyebrow {
            position: relative;
            z-index: 1;
            margin: 0 0 0.35rem;
            color: #b45309;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .dashboard-title {
            position: relative;
            z-index: 1;
            margin: 0;
            color: #111827;
            font-size: clamp(1.35rem, 3vw, 2rem);
            font-weight: 750;
            line-height: 1.2;
        }

        .dashboard-subtitle {
            position: relative;
            z-index: 1;
            max-width: 44rem;
            margin: 0.5rem 0 0;
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .dashboard-hero-copy {
            min-width: 0;
        }

        .dashboard-year-control {
            position: relative;
            z-index: 2;
            flex: 0 0 auto;
            min-width: 9rem;
        }

        .dashboard-year-control .table-year-filter {
            display: block;
        }

        .dashboard-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
            gap: 1rem;
        }

        .dashboard-stat-card {
            position: relative;
            min-width: 0;
            padding: 1.1rem;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 0.9rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px rgba(15, 23, 42, 0.035);
            transition: transform 160ms ease, box-shadow 160ms ease;
        }

        .dashboard-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.09);
        }

        button.dashboard-stat-card { text-align: left; width: 100%; cursor: pointer; }
        button.dashboard-stat-card:focus-visible { outline: 2px solid #d97706; outline-offset: 3px; }
        .allotment-breakdown { display: grid; gap: 1.25rem; }
        .allotment-breakdown-group { border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; }
        .allotment-breakdown-heading, .allotment-breakdown-total { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem; font-weight: 700; background: #f8fafc; color: #111827; }
        .allotment-breakdown-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 0.875rem; }
        .allotment-breakdown-table th, .allotment-breakdown-table td { padding: 0.85rem 1rem; text-align: left; overflow-wrap: anywhere; border-top: 1px solid #e5e7eb; }
        .allotment-breakdown-table th:last-child, .allotment-breakdown-table td:last-child { width: 6.5rem; text-align: right; }
        .allotment-breakdown-type { display: block; margin-top: 0.25rem; font-size: 0.75rem; color: #6b7280; }
        .allotment-breakdown-total { border-radius: 0.75rem; background: #fffbeb; color: #92400e; }
        .dark .allotment-breakdown-group { border-color: #374151; }
        .dark .allotment-breakdown-heading { background: #1f2937; color: #f9fafb; }
        .dark .allotment-breakdown-table th, .dark .allotment-breakdown-table td { border-color: #374151; }
        .dark .allotment-breakdown-type { color: #9ca3af; }

        .dashboard-stat-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .dashboard-stat-label {
            margin: 0;
            color: #6b7280;
            font-size: 0.78rem;
            font-weight: 650;
            letter-spacing: 0.025em;
            text-transform: uppercase;
        }

        .dashboard-stat-value {
            margin: 0.35rem 0 0;
            color: #111827;
            font-size: clamp(1.65rem, 4vw, 2.2rem);
            font-weight: 750;
            letter-spacing: -0.035em;
            line-height: 1;
        }

        .dashboard-stat-note {
            margin: 0.55rem 0 0;
            color: #9ca3af;
            font-size: 0.75rem;
        }

        .dashboard-stat-icon-wrap {
            display: grid;
            flex: 0 0 auto;
            width: 2.5rem;
            height: 2.5rem;
            place-items: center;
            border-radius: 0.75rem;
        }

        .dashboard-stat-icon {
            width: 1.25rem;
            height: 1.25rem;
        }

        .dashboard-tone-blue .dashboard-stat-icon-wrap { color: #2563eb; background: #eff6ff; }
        .dashboard-tone-slate .dashboard-stat-icon-wrap { color: #475569; background: #f1f5f9; }
        .dashboard-tone-emerald .dashboard-stat-icon-wrap { color: #059669; background: #ecfdf5; }
        .dashboard-tone-amber .dashboard-stat-icon-wrap { color: #d97706; background: #fffbeb; }
        .dashboard-tone-violet .dashboard-stat-icon-wrap { color: #7c3aed; background: #f5f3ff; }

        .dashboard-capacity-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: center;
            padding: 1.25rem;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        }

        .dashboard-capacity-heading {
            margin: 0;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .dashboard-capacity-copy {
            margin: 0.25rem 0 0;
            color: #6b7280;
            font-size: 0.8rem;
        }

        .dashboard-progress-track {
            height: 0.5rem;
            margin-top: 0.9rem;
            overflow: hidden;
            border-radius: 9999px;
            background: #f3f4f6;
        }

        .dashboard-progress-bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #f59e0b, #f97316);
            transition: width 300ms ease;
        }

        .dashboard-capacity-value {
            color: #111827;
            font-size: 1.5rem;
            font-weight: 750;
            white-space: nowrap;
        }

        .dashboard-table-section {
            display: grid;
            min-width: 0;
            gap: 0.75rem;
        }

        .dashboard-section-heading {
            margin: 0;
            color: #111827;
            font-size: 1rem;
            font-weight: 700;
        }

        .dashboard-section-copy {
            margin: 0.2rem 0 0;
            color: #6b7280;
            font-size: 0.8rem;
        }

        .dark .dashboard-hero {
            border-color: rgba(245, 158, 11, 0.22);
            background: linear-gradient(135deg, #1f1b12 0%, #111827 58%, #241b0c 100%);
        }

        .dark .dashboard-title,
        .dark .dashboard-stat-value,
        .dark .dashboard-capacity-heading,
        .dark .dashboard-capacity-value,
        .dark .dashboard-section-heading {
            color: #f9fafb;
        }

        .dark .dashboard-subtitle,
        .dark .dashboard-stat-label,
        .dark .dashboard-capacity-copy,
        .dark .dashboard-section-copy {
            color: #9ca3af;
        }

        .dark .dashboard-stat-card,
        .dark .dashboard-capacity-card {
            border-color: rgba(255, 255, 255, 0.1);
            background: #111827;
        }

        .dark .dashboard-progress-track {
            background: #374151;
        }

        @media (max-width: 639px) {
            .professional-dashboard { gap: 1rem; }
            .dashboard-hero { align-items: stretch; flex-direction: column; padding: 1.15rem; }
            .dashboard-year-control { width: 100%; }
            .dashboard-stats-grid { grid-template-columns: 1fr; gap: 0.75rem; }
            .dashboard-stat-card { padding: 0.9rem; }
            .dashboard-stat-icon-wrap { width: 2.1rem; height: 2.1rem; }
            .dashboard-capacity-card { grid-template-columns: 1fr; }
            .dashboard-capacity-value { font-size: 1.25rem; }

            .dashboard-stat-card:hover {
                transform: none;
            }

            .dashboard-table-section .fi-ta-content-ctn {
                overflow-x: hidden;
            }

            .dashboard-table-section .fi-ta-header-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .dashboard-table-section .fi-ta-header-toolbar > *,
            .dashboard-table-section .fi-ta-search-field,
            .dashboard-table-section .fi-input-wrp {
                width: 100%;
                max-width: none;
            }

            .dashboard-table-section .fi-ta-cell-mobile-summary .fi-ta-col {
                padding: 1rem;
                white-space: normal;
            }
        }
    </style>

    <div class="professional-dashboard">
        <section class="dashboard-hero">
            <div class="dashboard-hero-copy">
                <p class="dashboard-eyebrow">{{ $stats['year'] }} Event Dashboard</p>
                <h2 class="dashboard-title">Fun Run Registration Overview</h2>
                <p class="dashboard-subtitle">
                    A live summary of participant registrations, race distances, and available event capacity.
                </p>
            </div>

            <div class="dashboard-year-control">
                <label class="table-year-filter">
                    <span class="fi-sr-only">Year</span>
                    <x-filament::input.wrapper prefix="Year">
                        <x-filament::input.select wire:model.live="selectedYear" aria-label="Year">
                            @foreach (range((int) date('Y'), (int) date('Y') - 5) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>
            </div>
        </section>

        <section class="dashboard-stats-grid" aria-label="Registration statistics">
            <button type="button" class="dashboard-stat-card dashboard-tone-slate" wire:click="openAllocationBreakdown" aria-haspopup="dialog" aria-label="View Total Distributed Allotment breakdown">
                <div class="dashboard-stat-top">
                    <div>
                        <p class="dashboard-stat-label">Total Distributed Allotment</p>
                        <p class="dashboard-stat-value">{{ number_format($stats['allocated']) }}</p>
                    </div>
                    <span class="dashboard-stat-icon-wrap">
                        <x-filament::icon icon="heroicon-o-ticket" class="dashboard-stat-icon" />
                    </span>
                </div>
                <p class="dashboard-stat-note">View allotment breakdown →</p>
            </button>

            <article class="dashboard-stat-card dashboard-tone-blue">
                <div class="dashboard-stat-top">
                    <div>
                        <p class="dashboard-stat-label">Registered</p>
                        <p class="dashboard-stat-value">{{ number_format($stats['registered']) }}</p>
                    </div>
                    <span class="dashboard-stat-icon-wrap">
                        <x-filament::icon icon="heroicon-o-users" class="dashboard-stat-icon" />
                    </span>
                </div>
                <p class="dashboard-stat-note">Total participants for {{ $stats['year'] }}</p>
            </article>

            <article class="dashboard-stat-card dashboard-tone-emerald">
                <div class="dashboard-stat-top">
                    <div>
                        <p class="dashboard-stat-label">Rave Run (3K)</p>
                        <p class="dashboard-stat-value">{{ number_format($stats['total_3k']) }}</p>
                    </div>
                    <span class="dashboard-stat-icon-wrap">
                        <x-filament::icon icon="heroicon-o-bolt" class="dashboard-stat-icon" />
                    </span>
                </div>
                <p class="dashboard-stat-note">Short-distance runners</p>
            </article>

            <article class="dashboard-stat-card dashboard-tone-amber">
                <div class="dashboard-stat-top">
                    <div>
                        <p class="dashboard-stat-label">5 KM</p>
                        <p class="dashboard-stat-value">{{ number_format($stats['total_5k']) }}</p>
                    </div>
                    <span class="dashboard-stat-icon-wrap">
                        <x-filament::icon icon="heroicon-o-fire" class="dashboard-stat-icon" />
                    </span>
                </div>
                <p class="dashboard-stat-note">Mid-distance runners</p>
            </article>

            <article class="dashboard-stat-card dashboard-tone-violet">
                <div class="dashboard-stat-top">
                    <div>
                        <p class="dashboard-stat-label">10 KM</p>
                        <p class="dashboard-stat-value">{{ number_format($stats['total_10k']) }}</p>
                    </div>
                    <span class="dashboard-stat-icon-wrap">
                        <x-filament::icon icon="heroicon-o-trophy" class="dashboard-stat-icon" />
                    </span>
                </div>
                <p class="dashboard-stat-note">Long-distance runners</p>
            </article>
        </section>

        <section class="dashboard-capacity-card">
            <div>
                <h3 class="dashboard-capacity-heading">Registration Capacity</h3>
                <p class="dashboard-capacity-copy">
                    {{ number_format($stats['available']) }} slots remaining from {{ number_format($stats['allocated']) }} allocated slots.
                </p>
                <div class="dashboard-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $stats['capacity_percentage'] }}">
                    <div class="dashboard-progress-bar" style="width: {{ $stats['capacity_percentage'] }}%"></div>
                </div>
            </div>
            <div class="dashboard-capacity-value">{{ $stats['capacity_percentage'] }}% filled</div>
        </section>

        <section class="dashboard-table-section">
            <header>
                <h3 class="dashboard-section-heading">Registration by Category</h3>
                <p class="dashboard-section-copy">Detailed allocation and distance totals for each subcategory in {{ $stats['year'] }}.</p>
            </header>
            {{ $this->table }}
        </section>

    </div>

    <x-filament::modal id="distributed-allotment-breakdown" width="3xl">
        <x-slot name="heading">Distributed Allotment Breakdown</x-slot>
        <x-slot name="description">Each amount is listed once. Add the rows to get the total for {{ $stats['year'] }}.</x-slot>

        @if ($showAllocationBreakdown)
            @php($breakdown = $this->getAllocationBreakdown())
            <div class="allotment-breakdown">
                    <div class="allotment-breakdown-group">
                        <table class="allotment-breakdown-table">
                            <thead><tr><th scope="col">Category / Subcategory</th><th scope="col">Allotment</th></tr></thead>
                            <tbody>
                                @forelse ($breakdown['groups'] as $group)
                                @foreach ($group['items'] as $item)
                                    <tr>
                                        <td>
                                            {{ $group['category'] }}
                                            <span class="allotment-breakdown-type">{{ $item['label'] === 'Main category' ? 'No subcategories' : $item['label'] }}</span>
                                        </td>
                                        <td>{{ number_format($item['amount']) }}</td>
                                    </tr>
                                @endforeach
                                @empty
                                    <tr><td colspan="2">No allotments have been configured.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="allotment-breakdown-total-row">
                                    <th scope="row">Total Distributed Allotment</th>
                                    <td><strong>{{ number_format($breakdown['total']) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
            </div>
        @endif
        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'distributed-allotment-breakdown' })">Close</x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
