<x-filament-panels::page>
    <style>
        .duplicate-generation {
            position: relative;
            display: grid;
            min-height: min(34rem, 68vh);
            place-items: center;
            overflow: hidden;
            padding: clamp(2rem, 6vw, 4rem) 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 1.25rem;
            background:
                radial-gradient(circle at 50% 10%, rgba(249, 115, 22, 0.11), transparent 34%),
                linear-gradient(145deg, #ffffff, #f8fafc);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.07);
        }

        .dark .duplicate-generation {
            border-color: rgba(255, 255, 255, 0.1);
            background:
                radial-gradient(circle at 50% 10%, rgba(249, 115, 22, 0.14), transparent 34%),
                linear-gradient(145deg, rgba(17, 24, 39, 0.98), rgba(15, 23, 42, 0.98));
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.2);
        }

        .duplicate-generation-content {
            position: relative;
            z-index: 1;
            max-width: 34rem;
            text-align: center;
        }

        .duplicate-generation-spinner {
            position: relative;
            display: grid;
            width: 6rem;
            height: 6rem;
            margin: 0 auto 1.75rem;
            place-items: center;
        }

        .duplicate-generation-spinner::before,
        .duplicate-generation-spinner::after {
            position: absolute;
            border-radius: 9999px;
            content: '';
        }

        .duplicate-generation-spinner::before {
            inset: 0;
            border: 3px solid rgba(249, 115, 22, 0.15);
            border-top-color: #f97316;
            animation: duplicate-generation-spin 1s linear infinite;
        }

        .duplicate-generation-spinner::after {
            inset: 0.65rem;
            border: 2px solid rgba(59, 130, 246, 0.12);
            border-right-color: #3b82f6;
            animation: duplicate-generation-spin 1.45s linear infinite reverse;
        }

        .duplicate-generation-icon {
            width: 2rem;
            height: 2rem;
            color: #f97316;
        }

        .duplicate-generation-eyebrow {
            margin: 0 0 0.65rem;
            color: #ea580c;
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .dark .duplicate-generation-eyebrow {
            color: #fb923c;
        }

        .duplicate-generation-title {
            margin: 0;
            color: #111827;
            font-size: clamp(1.35rem, 4vw, 1.9rem);
            font-weight: 760;
            letter-spacing: -0.025em;
            line-height: 1.2;
        }

        .dark .duplicate-generation-title {
            color: #f9fafb;
        }

        .duplicate-generation-copy {
            margin: 0.8rem auto 0;
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.7;
        }

        .dark .duplicate-generation-copy {
            color: #9ca3af;
        }

        .duplicate-generation-progress {
            width: min(18rem, 80%);
            height: 0.3rem;
            margin: 1.5rem auto 0;
            overflow: hidden;
            border-radius: 9999px;
            background: #e5e7eb;
        }

        .dark .duplicate-generation-progress {
            background: rgba(255, 255, 255, 0.1);
        }

        .duplicate-generation-progress::after {
            display: block;
            width: 42%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #f97316, #fb923c);
            content: '';
            animation: duplicate-generation-progress 1.55s ease-in-out infinite;
        }

        @keyframes duplicate-generation-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes duplicate-generation-progress {
            0% {
                transform: translateX(-115%);
            }

            100% {
                transform: translateX(350%);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .duplicate-generation-spinner::before,
            .duplicate-generation-spinner::after,
            .duplicate-generation-progress::after {
                animation-duration: 3s;
            }
        }

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

    <div wire:init="loadPotentialDuplicates">
        @if (! $duplicatesReady)
            <section
                class="duplicate-generation"
                role="status"
                aria-live="polite"
                aria-busy="true"
                aria-labelledby="duplicate-generation-title"
            >
                <div class="duplicate-generation-content">
                    <div class="duplicate-generation-spinner" aria-hidden="true">
                        <x-filament::icon
                            icon="heroicon-o-document-magnifying-glass"
                            class="duplicate-generation-icon"
                        />
                    </div>

                    <p class="duplicate-generation-eyebrow">Registration Integrity Check</p>
                    <h2 id="duplicate-generation-title" class="duplicate-generation-title">
                        Generating potential duplicates
                    </h2>
                    <p class="duplicate-generation-copy">
                        Please wait while we carefully compare participant records. This may take a few moments.
                    </p>
                    <div class="duplicate-generation-progress" aria-hidden="true"></div>
                </div>
            </section>
        @else
            @php($stats = $this->getPotentialDuplicateStats())

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
        @endif
    </div>
</x-filament-panels::page>
