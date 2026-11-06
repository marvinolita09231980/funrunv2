@php
    use Filament\Support\Enums\Width;

    $livewire ??= null;

    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (filament()->getSimplePageMaxContentWidth() ?? Width::Large);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    @props([
        'after' => null,
        'heading' => null,
        'subheading' => null,
    ])

    <style>
        .ddo-login-card {
            display: flex;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow: hidden;
            box-sizing: border-box;
            border-radius: 1rem;
            background: #fdfcea;
            box-shadow: 0 25px 50px -12px rgba(69, 43, 2, 0.32);
        }

        .ddo-login-brand-panel,
        .ddo-login-form-panel {
            min-width: 0;
            flex: 1 1 50%;
            box-sizing: border-box;
        }

        .ddo-login-brand-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            padding: 2.5rem 1.5rem;
            color: #ffffff;
            text-align: center;
            background: linear-gradient(to top, #452b02, #edca25);
        }

        .ddo-login-logo {
            width: 8rem;
            height: 8rem;
            flex: 0 0 auto;
            background: url('{{ asset('images/login-logo.png') }}') center / contain no-repeat;
        }

        .ddo-login-brand-name {
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 750;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .ddo-login-form-panel {
            padding: 2.5rem;
            color: #292524;
            color-scheme: light;
            background: #fdfcea;
        }

        .ddo-login-title {
            margin-bottom: 1.25rem;
            color: #44403c;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.3;
        }

        /* Keep the light login card readable when the phone uses dark mode. */
        .ddo-login-form-panel .fi-fo-field-label,
        .ddo-login-form-panel .fi-fo-field-label-content {
            color: #292524 !important;
        }

        .ddo-login-form-panel .fi-input-wrp {
            color: #1c1917 !important;
            background: #ffffff !important;
        }

        .ddo-login-form-panel input.fi-input,
        .ddo-login-form-panel input[type='text'],
        .ddo-login-form-panel input[type='email'],
        .ddo-login-form-panel input[type='password'] {
            color: #1c1917 !important;
            opacity: 1 !important;
            background-color: transparent !important;
            caret-color: #92400e;
            -webkit-text-fill-color: #1c1917 !important;
            text-shadow: none !important;
        }

        .ddo-login-form-panel input.fi-input::placeholder,
        .ddo-login-form-panel input[type='text']::placeholder,
        .ddo-login-form-panel input[type='email']::placeholder,
        .ddo-login-form-panel input[type='password']::placeholder {
            color: #78716c !important;
            opacity: 1;
            -webkit-text-fill-color: #78716c !important;
        }

        .ddo-login-form-panel input:-webkit-autofill,
        .ddo-login-form-panel input:-webkit-autofill:hover,
        .ddo-login-form-panel input:-webkit-autofill:focus,
        .ddo-login-form-panel input:-webkit-autofill:active {
            color: #1c1917 !important;
            caret-color: #92400e !important;
            -webkit-text-fill-color: #1c1917 !important;
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
            box-shadow: 0 0 0 1000px #ffffff inset !important;
        }

        .ddo-login-form-panel .fi-checkbox-input {
            background-color: #ffffff;
        }

        .ddo-login-form-panel form,
        .ddo-login-form-panel .fi-sc,
        .ddo-login-form-panel .fi-fo-field,
        .ddo-login-form-panel .fi-input-wrp,
        .ddo-login-form-panel .fi-input {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        @media (max-width: 767px) {
            html.fi,
            .fi-body,
            .fi-simple-layout {
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
            }

            .fi-simple-main-ctn {
                display: block;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                padding: max(0.75rem, env(safe-area-inset-top)) max(0.75rem, env(safe-area-inset-right)) max(0.75rem, env(safe-area-inset-bottom)) max(0.75rem, env(safe-area-inset-left));
                overflow-x: hidden;
                box-sizing: border-box;
            }

            .fi-simple-main {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box;
            }

            .ddo-login-card {
                display: block;
                width: 100%;
                max-width: 100%;
                margin: 0;
                border-radius: 0.875rem;
            }

            .ddo-login-brand-panel,
            .ddo-login-form-panel {
                flex-basis: auto;
                width: 100%;
            }

            .ddo-login-brand-panel {
                gap: 0.75rem;
                padding: 1.25rem 1rem;
            }

            .ddo-login-logo {
                width: 5.5rem;
                height: 5.5rem;
            }

            .ddo-login-brand-name {
                font-size: 1.1rem;
            }

            .ddo-login-form-panel {
                overflow: hidden;
                padding: 1.35rem 1rem 1.5rem;
            }

            .ddo-login-title {
                margin-bottom: 1rem;
                font-size: 1.35rem;
            }

            .ddo-login-form-panel .fi-ac {
                width: 100%;
            }

            .ddo-login-form-panel .fi-ac-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="fi-simple-layout">
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_START, scopes: $renderHookScopes) }}

        @if (($hasTopbar ?? true) && filament()->auth()->check())
            <div class="fi-simple-layout-header">
                @if (filament()->hasDatabaseNotifications())
                    @livewire(Filament\Livewire\DatabaseNotifications::class, [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                    ])
                @endif

                @if (filament()->hasUserMenu())
                    @livewire(Filament\Livewire\SimpleUserMenu::class)
                @endif
            </div>
        @endif

        <div class="fi-simple-main-ctn bg-radial from-[#fdfae8] from-40% to-[#fef9c3]">
            <main
                @class([
                    'fi-simple-main',
                    'px-0',
                    'py-0',
                    'm-0',
                    'rounded-none',
                    'border-none',
                    'bg-transparent',
                    'ring-0',
                    'shadow-none',
                    ($maxContentWidth instanceof Width) ? "fi-width-{$maxContentWidth->value}" : $maxContentWidth,
                ])
            >
                <div class="ddo-login-card">
                    <section class="ddo-login-brand-panel" aria-label="{{ config('app.name', 'System Name') }}">
                        <div class="ddo-login-logo" role="img" aria-label="{{ config('app.name', 'System Name') }} logo"></div>
                        <div class="ddo-login-brand-name">
                            {{ config('app.name', 'System Name') }}
                        </div>
                    </section>

                    <section class="ddo-login-form-panel" aria-labelledby="ddo-login-title">
                        <h1 id="ddo-login-title" class="ddo-login-title">Login</h1>
                        {{ $slot }}
                    </section>
                </div>
            </main>
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
    </div>
</x-filament-panels::layout.base>
