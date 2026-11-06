<x-filament-panels::page>
    <div class="grid gap-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950 p-6 text-white shadow-xl sm:p-8">
            <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full border border-white/10 bg-amber-400/5"></div>
            <div class="relative max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-300/20 bg-amber-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-amber-200">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-4 w-4" />
                    Accountability &amp; Security
                </div>
                <h2 class="mt-5 text-2xl font-semibold tracking-tight sm:text-3xl">
                    Application Audit Trail
                </h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                    Review edits and deletions across participants, users, allocations, subcategories, finishers, feedback, and registration settings. Passwords and security tokens are always redacted.
                </p>
            </div>
        </section>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
