<x-filament-panels::page>
    @php($stats = $this->getPotentialDuplicateStats())

    <style>
        .duplicate-audit {
            display: grid;
            min-width: 0;
            gap: 1.5rem;
        }

        .duplicate-audit > *,
        .duplicate-audit-guidance > * {
            min-width: 0;
        }

        .duplicate-audit-hero {
            position: relative;
            overflow: hidden;
            padding: clamp(1.5rem, 4vw, 2.25rem);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            color: #fff;
            background:
                radial-gradient(circle at 90% 10%, rgba(251, 146, 60, 0.2), transparent 30%),
                linear-gradient(135deg, #111827 0%, #1e293b 56%, #431407 135%);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
        }

        .duplicate-audit-hero::after {
            position: absolute;
            right: -4rem;
            bottom: -7rem;
            width: 16rem;
            height: 16rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 9999px;
            content: '';
        }

        .duplicate-audit-hero-copy {
            position: relative;
            z-index: 1;
            max-width: 52rem;
        }

        .duplicate-audit-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.7rem;
            border: 1px solid rgba(253, 186, 116, 0.25);
            border-radius: 9999px;
            color: #fed7aa;
            background: rgba(154, 52, 18, 0.22);
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .duplicate-audit-title {
            margin: 1rem 0 0;
            font-size: clamp(1.55rem, 4vw, 2.35rem);
            font-weight: 760;
            letter-spacing: -0.03em;
            line-height: 1.12;
        }

        .duplicate-audit-subtitle {
            max-width: 46rem;
            margin: 0.8rem 0 0;
            color: #cbd5e1;
            font-size: 0.94rem;
            line-height: 1.7;
        }

        .duplicate-audit-metrics {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.85rem;
            margin-top: 1.75rem;
        }

        .duplicate-audit-metric {
            min-width: 0;
            padding: 1rem 1.1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.9rem;
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(10px);
        }

        .duplicate-audit-metric-label {
            margin: 0;
            color: #cbd5e1;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.075em;
            text-transform: uppercase;
        }

        .duplicate-audit-metric-value {
            margin: 0.35rem 0 0;
            font-size: clamp(1.45rem, 3vw, 2rem);
            font-weight: 750;
            line-height: 1;
        }

        .duplicate-audit-guidance {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(16rem, 0.8fr);
            gap: 1.25rem;
        }

        .duplicate-audit-card {
            padding: 1.35rem;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        }

        .dark .duplicate-audit-card {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.04);
        }

        .duplicate-audit-card-heading {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin: 0;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .dark .duplicate-audit-card-heading {
            color: #f9fafb;
        }

        .duplicate-audit-card-copy {
            margin: 0.55rem 0 0;
            color: #6b7280;
            font-size: 0.82rem;
            line-height: 1.65;
        }

        .dark .duplicate-audit-card-copy {
            color: #9ca3af;
        }

        .duplicate-audit-rules {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            margin-top: 1rem;
        }

        .duplicate-audit-rule {
            display: inline-flex;
            align-items: center;
            gap: 0.38rem;
            padding: 0.48rem 0.7rem;
            border: 1px solid #e5e7eb;
            border-radius: 9999px;
            color: #374151;
            background: #f9fafb;
            font-size: 0.75rem;
            font-weight: 650;
        }

        .dark .duplicate-audit-rule {
            border-color: rgba(255, 255, 255, 0.1);
            color: #d1d5db;
            background: rgba(255, 255, 255, 0.05);
        }

        .duplicate-audit-warning {
            border-color: #fed7aa;
            background: linear-gradient(145deg, #fff7ed, #fffbeb);
        }

        .dark .duplicate-audit-warning {
            border-color: rgba(251, 146, 60, 0.28);
            background: rgba(124, 45, 18, 0.17);
        }

        .duplicate-audit-warning .duplicate-audit-card-heading {
            color: #9a3412;
        }

        .dark .duplicate-audit-warning .duplicate-audit-card-heading {
            color: #fdba74;
        }

        @media (max-width: 767px) {
            .duplicate-audit-metrics,
            .duplicate-audit-guidance {
                grid-template-columns: 1fr;
            }

            .duplicate-audit-hero {
                border-radius: 1rem;
            }
        }
    </style>

    <div class="duplicate-audit">
        <section class="duplicate-audit-hero" aria-labelledby="duplicate-audit-title">
            <div class="duplicate-audit-hero-copy">
                <span class="duplicate-audit-kicker">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-4 w-4" />
                    Registration Integrity
                </span>

                <h2 id="duplicate-audit-title" class="duplicate-audit-title">
                    High-Probability Duplicate Review
                </h2>

                <p class="duplicate-audit-subtitle">
                    Review similar—but not identical—participant identities across every category.
                    The report prioritizes records that share strong identity signals and leaves the final decision to an authorized reviewer.
                </p>
            </div>

            <div class="duplicate-audit-metrics" aria-label="Duplicate review summary">
                <article class="duplicate-audit-metric">
                    <p class="duplicate-audit-metric-label">Registration Year</p>
                    <p class="duplicate-audit-metric-value">{{ $stats['year'] }}</p>
                </article>

                <article class="duplicate-audit-metric">
                    <p class="duplicate-audit-metric-label">High-Probability Groups</p>
                    <p class="duplicate-audit-metric-value">{{ number_format($stats['groups']) }}</p>
                </article>

                <article class="duplicate-audit-metric">
                    <p class="duplicate-audit-metric-label">Records for Review</p>
                    <p class="duplicate-audit-metric-value">{{ number_format($stats['records']) }}</p>
                </article>
            </div>
        </section>

        <section class="duplicate-audit-guidance" aria-label="Matching and review guidance">
            <article class="duplicate-audit-card">
                <h3 class="duplicate-audit-card-heading">
                    <x-filament::icon icon="heroicon-o-finger-print" class="h-5 w-5 text-primary-500" />
                    What qualifies as a high-probability match?
                </h3>
                <p class="duplicate-audit-card-copy">
                    Exact normalized first-name matches are excluded. A record appears only when these stronger identity signals align.
                    Similarity includes shortened names, added initials, and small phonetic spelling differences.
                </p>

                <div class="duplicate-audit-rules">
                    @foreach ([
                        'Same registration year',
                        'Same birth date',
                        'Same normalized last name',
                        'Similar non-exact first name',
                    ] as $rule)
                        <span class="duplicate-audit-rule">
                            <x-filament::icon icon="heroicon-m-check" class="h-3.5 w-3.5 text-success-500" />
                            {{ $rule }}
                        </span>
                    @endforeach
                </div>
            </article>

            <aside class="duplicate-audit-card duplicate-audit-warning">
                <h3 class="duplicate-audit-card-heading">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5" />
                    Manual review required
                </h3>
                <p class="duplicate-audit-card-copy">
                    A match is not automatic proof of duplication. Compare the categories, participant numbers, creator, and registration timestamps before changing any record.
                </p>
            </aside>
        </section>

        <section aria-label="Potential duplicate participant records">
            {{ $this->table }}
        </section>
    </div>
</x-filament-panels::page>
