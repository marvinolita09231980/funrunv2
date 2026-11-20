@php
    use App\Filament\Pages\PotentialDuplicates;
    use App\Filament\Resources\Participants\ParticipantResource;
    use Carbon\CarbonImmutable;
    use Illuminate\Support\Facades\Auth;
@endphp

<div class="potential-duplicate-comparison" data-duplicate-comparison>
    <style>
        .potential-duplicate-comparison-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
        }

        .potential-duplicate-comparison-card {
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--gray-200);
            border-radius: 0.85rem;
            background: white;
        }

        .potential-duplicate-comparison-header {
            padding: 1rem 1.1rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--warning-50);
        }

        .potential-duplicate-comparison-header p {
            margin-top: 0.2rem;
            color: var(--gray-600);
            font-size: 0.8rem;
        }

        .potential-duplicate-comparison-name {
            color: var(--gray-950);
            font-size: 1rem;
            font-weight: 700;
        }

        .potential-duplicate-comparison-details {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0;
            padding: 0.35rem 1.1rem;
        }

        .potential-duplicate-comparison-detail {
            display: grid;
            grid-template-columns: minmax(7rem, 0.42fr) minmax(0, 1fr);
            gap: 0.75rem;
            padding: 0.65rem 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .potential-duplicate-comparison-detail:last-child {
            border-bottom: 0;
        }

        .potential-duplicate-comparison-detail dt {
            color: var(--gray-500);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .potential-duplicate-comparison-detail dd {
            min-width: 0;
            overflow-wrap: anywhere;
            color: var(--gray-900);
            font-size: 0.84rem;
            font-weight: 500;
        }

        .potential-duplicate-comparison-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.5rem;
            padding: 0.85rem 1.1rem 1rem;
            border-top: 1px solid var(--gray-100);
        }

        .dark .potential-duplicate-comparison-card {
            border-color: var(--gray-700);
            background: var(--gray-900);
        }

        .dark .potential-duplicate-comparison-header {
            border-color: var(--gray-700);
            background: color-mix(in srgb, var(--warning-500) 12%, var(--gray-900));
        }

        .dark .potential-duplicate-comparison-name,
        .dark .potential-duplicate-comparison-detail dd {
            color: var(--gray-100);
        }

        .dark .potential-duplicate-comparison-detail,
        .dark .potential-duplicate-comparison-actions {
            border-color: var(--gray-800);
        }

        @media (min-width: 768px) {
            .potential-duplicate-comparison-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div class="potential-duplicate-comparison-grid">
        @foreach ($participants as $participant)
            @php
                $birthDate = filled($participant->birthDate)
                    ? CarbonImmutable::parse($participant->birthDate)
                    : null;
                $name = collect([
                    $participant->lastName.',',
                    $participant->firstName,
                    filled($participant->middleInitial)
                        ? rtrim((string) $participant->middleInitial, '.').'.'
                        : null,
                ])->filter()->join(' ');
                $details = [
                    'Birth Date' => $birthDate?->format('F j, Y') ?? 'Not provided',
                    'Age' => $birthDate && $birthDate->lessThanOrEqualTo(now())
                        ? $birthDate->age.' years old'
                        : 'Not available',
                    'Gender' => filled($participant->gender) ? ucfirst((string) $participant->gender) : 'Not provided',
                    'Phone Number' => $participant->contactNumber ?: 'Not provided',
                    'Address' => $participant->address ?: 'Not provided',
                    'Category' => $participant->categoryDescription ?: 'Not provided',
                    'Subcategory' => $participant->subDescription ?: 'Not provided',
                    'Distance' => $participant->distance_label,
                    'Shirt Size' => filled($participant->shirtSize)
                        ? strtoupper(trim((string) $participant->shirtSize))
                        : 'Not provided',
                    'Recorded By' => $participant->creator_name,
                    'Registered At' => $participant->created_at?->format('F j, Y g:i A') ?? 'Not recorded',
                ];
                $isLatestRegistration = (int) $participant->getKey() === (int) $replaceableParticipantId;
                $canReplaceParticipant = $isLatestRegistration
                    && PotentialDuplicates::canReplacePotentialDuplicate(
                        Auth::user(),
                        $participant,
                    );
                $canOpenParticipant = Auth::user()?->can('update', $participant) ?? false;
            @endphp

            <article class="potential-duplicate-comparison-card" data-comparison-participant="{{ $participant->getKey() }}">
                <header class="potential-duplicate-comparison-header">
                    <h3 class="potential-duplicate-comparison-name">{{ $name }}</h3>
                    <p>Participant record #{{ $participant->getKey() }}</p>
                    @if ($isLatestRegistration)
                        <p><strong>Latest registration — eligible for replacement</strong></p>
                    @endif
                </header>

                <dl class="potential-duplicate-comparison-details">
                    @foreach ($details as $label => $value)
                        <div class="potential-duplicate-comparison-detail">
                            <dt>{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($canReplaceParticipant || $canOpenParticipant)
                    <footer class="potential-duplicate-comparison-actions">
                        @if ($canOpenParticipant)
                            <x-filament::button
                                tag="a"
                                :href="ParticipantResource::getUrl('edit', ['record' => $participant])"
                                size="sm"
                                color="gray"
                                icon="heroicon-o-pencil-square"
                            >
                                Open Participant Record
                            </x-filament::button>
                        @endif

                        @if ($canReplaceParticipant)
                            <x-filament::button
                                type="button"
                                size="sm"
                                color="success"
                                icon="heroicon-o-user-plus"
                                wire:click="mountTableAction('replaceDuplicate', '{{ $participant->getKey() }}')"
                                wire:loading.attr="disabled"
                                wire:target="mountTableAction"
                                data-replace-duplicate="{{ $participant->getKey() }}"
                            >
                                Choose This Record to Update
                            </x-filament::button>
                        @endif
                    </footer>
                @endif
            </article>
        @endforeach
    </div>
</div>
