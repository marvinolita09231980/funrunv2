<x-filament-panels::page>
    @php
        $publicRegistrationUrl = rtrim(request()->root(), '/') . '/kalamboan/funrun-register';
    @endphp

    <style>
        .registration-admin {
            width: min(100%, 70rem);
            margin-inline: auto;
        }

        .registration-status,
        .registration-link-panel,
        .registration-note {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
        }

        .registration-status {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 2rem;
            overflow: hidden;
            padding: 1.75rem 2rem;
            border-left: 5px solid var(--registration-state);
            border-radius: 1rem;
        }

        .registration-status.is-active {
            --registration-state: #059669;
            --registration-state-soft: #ecfdf5;
            --registration-state-text: #047857;
        }

        .registration-status.is-inactive {
            --registration-state: #dc2626;
            --registration-state-soft: #fef2f2;
            --registration-state-text: #b91c1c;
        }

        .registration-state-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            color: var(--registration-state-text);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: var(--registration-state-soft);
        }

        .registration-state-dot {
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 999px;
            background: var(--registration-state);
        }

        .registration-status h2 {
            margin: 0.8rem 0 0;
            color: #111827;
            font-size: clamp(1.25rem, 2vw, 1.65rem);
            font-weight: 750;
            line-height: 1.25;
        }

        .registration-status p {
            max-width: 47rem;
            margin: 0.6rem 0 0;
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.65;
        }

        .registration-status-icon {
            display: grid;
            width: 4rem;
            height: 4rem;
            place-items: center;
            border-radius: 1rem;
            color: var(--registration-state-text);
            background: var(--registration-state-soft);
        }

        .registration-status-icon svg {
            width: 2rem;
            height: 2rem;
        }

        .registration-link-panel {
            margin-top: 1.25rem;
            overflow: hidden;
            border-radius: 1rem;
        }

        .registration-link-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem 1.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .registration-link-icon {
            display: grid;
            width: 3rem;
            height: 3rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 0.8rem;
            color: #ffffff;
            background: #b45309;
        }

        .registration-link-icon svg {
            width: 1.5rem;
            height: 1.5rem;
        }

        .registration-eyebrow {
            margin: 0;
            color: #b45309;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .registration-link-header h3 {
            margin: 0.3rem 0 0;
            color: #111827;
            font-size: 1.15rem;
            font-weight: 750;
            line-height: 1.3;
        }

        .registration-link-body {
            padding: 1.75rem;
        }

        .registration-link-body > p {
            margin: 0 0 1rem;
            color: #64748b;
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .registration-url {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem 1.1rem;
            border: 1px solid #334155;
            border-radius: 0.8rem;
            color: #fbbf24;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.5;
            text-decoration: none;
            background: #0f172a;
            transition: border-color 150ms ease, transform 150ms ease;
        }

        .registration-url:hover {
            border-color: #f59e0b;
            transform: translateY(-1px);
        }

        .registration-url span {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .registration-url svg {
            width: 1.25rem;
            height: 1.25rem;
            flex: 0 0 auto;
        }

        .registration-link-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .registration-link-action {
            display: inline-flex;
            min-height: 2.75rem;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.7rem 1rem;
            border: 1px solid transparent;
            border-radius: 0.7rem;
            font-size: 0.82rem;
            font-weight: 750;
            text-decoration: none;
            cursor: pointer;
            transition: background 150ms ease, border-color 150ms ease, transform 150ms ease;
        }

        .registration-link-action:hover {
            transform: translateY(-1px);
        }

        .registration-link-action svg {
            width: 1.05rem;
            height: 1.05rem;
        }

        .registration-link-action.is-primary {
            color: #ffffff;
            background: #b45309;
        }

        .registration-link-action.is-primary:hover {
            background: #92400e;
        }

        .registration-link-action.is-secondary {
            color: #334155;
            border-color: #cbd5e1;
            background: #ffffff;
        }

        .registration-link-action.is-secondary:hover {
            border-color: #94a3b8;
            background: #f8fafc;
        }

        .registration-link-action.is-messenger {
            color: #ffffff;
            background: #0866ff;
        }

        .registration-link-action.is-messenger:hover {
            background: #075ce5;
        }

        .registration-state-note {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            margin-top: 1rem;
            padding: 0.8rem 0.9rem;
            border-radius: 0.7rem;
            color: #475569;
            font-size: 0.8rem;
            line-height: 1.55;
            background: #f8fafc;
        }

        .registration-state-note svg {
            width: 1rem;
            height: 1rem;
            flex: 0 0 auto;
            margin-top: 0.15rem;
            color: #b45309;
        }

        .registration-notes {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1.25rem;
        }

        .registration-note {
            display: flex;
            gap: 0.8rem;
            padding: 1rem;
            border-radius: 0.85rem;
        }

        .registration-note-icon {
            display: grid;
            width: 2.25rem;
            height: 2.25rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 0.65rem;
            color: #b45309;
            background: #fffbeb;
        }

        .registration-note-icon svg {
            width: 1.15rem;
            height: 1.15rem;
        }

        .registration-note h4 {
            margin: 0;
            color: #1e293b;
            font-size: 0.82rem;
            font-weight: 750;
        }

        .registration-note p {
            margin: 0.25rem 0 0;
            color: #64748b;
            font-size: 0.75rem;
            line-height: 1.5;
        }

        .dark .registration-status,
        .dark .registration-link-panel,
        .dark .registration-note {
            border-color: rgba(255, 255, 255, 0.1);
            background: #111827;
        }

        .dark .registration-status h2,
        .dark .registration-link-header h3,
        .dark .registration-note h4 {
            color: #f8fafc;
        }

        .dark .registration-status p,
        .dark .registration-link-body > p,
        .dark .registration-note p {
            color: #94a3b8;
        }

        .dark .registration-link-header {
            border-color: rgba(255, 255, 255, 0.1);
        }

        .dark .registration-link-action.is-secondary {
            color: #e2e8f0;
            border-color: #475569;
            background: #1e293b;
        }

        .dark .registration-state-note {
            color: #cbd5e1;
            background: #1e293b;
        }

        .dark .registration-note-icon {
            color: #fbbf24;
            background: rgba(245, 158, 11, 0.12);
        }

        @media (max-width: 767px) {
            .registration-status {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 1.25rem;
            }

            .registration-status-icon {
                display: none;
            }

            .registration-link-header,
            .registration-link-body {
                padding: 1.25rem;
            }

            .registration-link-actions {
                flex-direction: column;
            }

            .registration-link-action {
                width: 100%;
            }

            .registration-notes {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="registration-admin">
        <section @class([
            'registration-status',
            'is-active' => $registrationEnabled,
            'is-inactive' => ! $registrationEnabled,
        ])>
            <div>
                <span class="registration-state-badge">
                    <span class="registration-state-dot"></span>
                    {{ $registrationEnabled ? 'Active' : 'Inactive' }}
                </span>
                <h2>Kalamboan Fun Run Registration</h2>
                <p>
                    @if ($registrationEnabled)
                        The online form is open and accepting new participant registrations.
                    @else
                        The online form is temporarily closed. Existing participant records remain safe and unchanged.
                    @endif
                </p>
            </div>

            <div class="registration-status-icon" aria-hidden="true">
                <x-filament::icon :icon="$registrationEnabled ? 'heroicon-o-check-circle' : 'heroicon-o-lock-closed'" />
            </div>
        </section>

        <section class="registration-link-panel">
            <header class="registration-link-header">
                <div class="registration-link-icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-globe-alt" />
                </div>
                <div>
                    <p class="registration-eyebrow">Official Online Registration</p>
                    <h3>Share this link with participants</h3>
                </div>
            </header>

            <div class="registration-link-body">
                <p>This is the official public address for the Kalamboan Fun Run registration form.</p>

                <a class="registration-url" href="{{ $publicRegistrationUrl }}" target="_blank" rel="noopener noreferrer">
                    <span>{{ $publicRegistrationUrl }}</span>
                    <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" />
                </a>

                <div class="registration-link-actions">
                    <a class="registration-link-action is-primary" href="{{ $publicRegistrationUrl }}" target="_blank" rel="noopener noreferrer">
                        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" />
                        Open Online Registration
                    </a>

                    <button
                        type="button"
                        class="registration-link-action is-secondary"
                        data-registration-url="{{ $publicRegistrationUrl }}"
                        onclick="
                            const copyButton = this;
                            const copyLabel = copyButton.querySelector('[data-copy-label]');
                            const showCopiedState = () => {
                                copyLabel.textContent = 'Link Copied';
                                window.setTimeout(() => copyLabel.textContent = 'Copy Registration Link', 2000);
                            };

                            if (navigator.clipboard?.writeText) {
                                navigator.clipboard.writeText(copyButton.dataset.registrationUrl).then(showCopiedState);
                            } else {
                                const input = document.createElement('textarea');
                                input.value = copyButton.dataset.registrationUrl;
                                input.style.position = 'fixed';
                                input.style.opacity = '0';
                                document.body.appendChild(input);
                                input.select();
                                document.execCommand('copy');
                                input.remove();
                                showCopiedState();
                            }
                        "
                    >
                        <x-filament::icon icon="heroicon-o-clipboard-document" />
                        <span data-copy-label>Copy Registration Link</span>
                    </button>

                    <a
                        class="registration-link-action is-messenger"
                        href="https://www.messenger.com/"
                        target="_blank"
                        rel="noopener noreferrer"
                        data-registration-url="{{ $publicRegistrationUrl }}"
                        onclick="
                            if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent) && navigator.share) {
                                event.preventDefault();
                                const messengerButton = this;

                                navigator.share({
                                    title: 'Kalamboan Fun Run Registration',
                                    text: 'Register online for the Kalamboan Fun Run.',
                                    url: messengerButton.dataset.registrationUrl,
                                }).catch((error) => {
                                    if (error.name !== 'AbortError') {
                                        navigator.clipboard?.writeText(messengerButton.dataset.registrationUrl).catch(() => {});
                                        window.open(messengerButton.href, '_blank', 'noopener,noreferrer');
                                    }
                                });
                            } else {
                                navigator.clipboard?.writeText(this.dataset.registrationUrl).catch(() => {});
                            }
                        "
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2C6.5 2 2 6.1 2 11.2c0 2.9 1.4 5.5 3.7 7.2V22l3.4-1.9c.9.2 1.9.4 2.9.4 5.5 0 10-4.1 10-9.2S17.5 2 12 2Zm1 12.4-2.5-2.7-4.8 2.7 5.3-5.6 2.5 2.7 4.8-2.7-5.3 5.6Z" />
                        </svg>
                        Share on Messenger
                    </a>
                </div>

                <div class="registration-state-note">
                    <x-filament::icon icon="heroicon-o-information-circle" />
                    <span>
                        @if ($registrationEnabled)
                            Visitors using this link can submit registrations now.
                        @else
                            The link remains shareable, but visitors will see a closure notice until registration is activated.
                        @endif
                    </span>
                </div>
            </div>
        </section>

        <section class="registration-notes" aria-label="Registration management notes">
            <article class="registration-note">
                <div class="registration-note-icon"><x-filament::icon icon="heroicon-o-bolt" /></div>
                <div><h4>Immediate updates</h4><p>Status changes apply to every visitor immediately.</p></div>
            </article>
            <article class="registration-note">
                <div class="registration-note-icon"><x-filament::icon icon="heroicon-o-circle-stack" /></div>
                <div><h4>Records protected</h4><p>Closing registration never deletes participant records.</p></div>
            </article>
            <article class="registration-note">
                <div class="registration-note-icon"><x-filament::icon icon="heroicon-o-shield-check" /></div>
                <div><h4>Administrator controlled</h4><p>Only administrators and super administrators can change status.</p></div>
            </article>
        </section>
    </div>
</x-filament-panels::page>
