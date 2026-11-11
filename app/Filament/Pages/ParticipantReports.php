<?php

namespace App\Filament\Pages;

use App\Models\Participant;
use App\Models\ParticipantReportExclusion;
use App\Models\User;
use App\Services\ParticipantAgeReport;
use App\Services\ParticipantReportPdf;
use App\Services\RegistrationGroupReport;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParticipantReports extends Page
{
    use HasPageShield;

    public const VIEW_PERMISSION = 'View:ParticipantReports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Participant Reports';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.participant-reports';

    public int $selectedYear;

    public ?string $previewUrl = null;

    public string $previewTitle = 'Participant Report Preview';

    public ?string $previewReportType = null;

    /** @var array<string, int|string|null> */
    public array $previewReportParameters = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ($user->isSuperAdmin() || $user->can(self::VIEW_PERMISSION));
    }

    public function mount(): void
    {
        $this->selectedYear = (int) now()->year;
    }

    public function years(): array
    {
        return Participant::query()->whereNotNull('year')->distinct()->pluck('year')
            ->push((int) now()->year)->unique()->sortDesc()->values()->all();
    }

    public function downloadReport(string $ranking = 'all', ?string $gender = null, ?string $distance = null): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);
        validator(['year' => $this->selectedYear, 'ranking' => $ranking, 'gender' => $gender, 'distance' => $distance], [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'ranking' => ['required', Rule::in(['all', 'oldest', 'youngest'])],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'distance' => ['nullable', Rule::in(['5km', '10km'])],
        ])->validate();

        $document = ParticipantReportPdf::age(
            Auth::user(),
            $this->selectedYear,
            $ranking,
            $gender,
            $distance,
        );

        return response()->streamDownload(static function () use ($document): void {
            echo $document['content'];
        }, $document['filename'], ['Content-Type' => 'application/pdf']);
    }

    public function downloadRunnerList(string $type): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);
        validator(['year' => $this->selectedYear, 'type' => $type], [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'type' => ['required', Rule::in(['couple', 'family'])],
        ])->validate();

        $document = ParticipantReportPdf::group(
            Auth::user(),
            $this->selectedYear,
            $type,
        );

        return response()->streamDownload(static function () use ($document): void {
            echo $document['content'];
        }, $document['filename'], ['Content-Type' => 'application/pdf']);
    }

    public function openAgeReportPreview(
        string $ranking = 'all',
        ?string $gender = null,
        ?string $distance = null,
    ): void {
        abort_unless(static::canAccess(), 403);

        $parameters = validator([
            'year' => $this->selectedYear,
            'ranking' => $ranking,
            'gender' => $gender,
            'distance' => $distance,
        ], [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'ranking' => ['required', Rule::in(['all', 'oldest', 'youngest'])],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'distance' => ['nullable', Rule::in(['5km', '10km'])],
        ])->validate();

        $this->previewReportType = 'age';
        $this->previewReportParameters = $parameters;
        $this->previewUrl = $this->previewReportUrl(
            $parameters['ranking'],
            $parameters['gender'] ?? null,
            $parameters['distance'] ?? null,
        );
        $this->previewTitle = $this->agePreviewTitle(
            $parameters['ranking'],
            $parameters['gender'] ?? null,
            $parameters['distance'] ?? null,
            (int) $parameters['year'],
        );

        $this->dispatch('open-modal', id: 'participant-report-pdf-preview');
    }

    public function openRunnerListPreview(string $type): void
    {
        abort_unless(static::canAccess(), 403);

        $parameters = validator([
            'year' => $this->selectedYear,
            'type' => $type,
        ], [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'type' => ['required', Rule::in(['couple', 'family'])],
        ])->validate();

        $this->previewReportType = 'group';
        $this->previewReportParameters = $parameters;
        $this->previewUrl = $this->previewRunnerListUrl($parameters['type']);
        $this->previewTitle = ucfirst($parameters['type']).' Runner List · '.$parameters['year'];

        $this->dispatch('open-modal', id: 'participant-report-pdf-preview');
    }

    /** @return array<string, mixed> */
    public function previewReport(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        return match ($this->previewReportType) {
            'age' => ParticipantAgeReport::build(
                $user,
                (int) ($this->previewReportParameters['year'] ?? 0),
                (string) ($this->previewReportParameters['ranking'] ?? 'all'),
                $this->previewReportParameters['gender'] ?? null,
                $this->previewReportParameters['distance'] ?? null,
            ),
            'group' => RegistrationGroupReport::build(
                $user,
                (int) ($this->previewReportParameters['year'] ?? 0),
                (string) ($this->previewReportParameters['type'] ?? ''),
            ),
            default => [],
        };
    }

    public function excludePreviewParticipant(
        int $participantId,
        ?string $ranking = null,
        ?string $gender = null,
        ?string $distance = null,
    ): void {
        abort_unless(static::canAccess(), 403);

        $year = (int) ($this->previewReportParameters['year'] ?? 0);

        if ($this->previewReportType === 'age') {
            $parameters = validator([
                'year' => $year,
                'ranking' => $ranking,
                'gender' => $gender,
                'distance' => $distance,
            ], [
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'ranking' => ['required', Rule::in(['oldest', 'youngest'])],
                'gender' => ['required', Rule::in(['male', 'female'])],
                'distance' => ['required', Rule::in(['5km', '10km'])],
            ])->validate();

            $previewRanking = $this->previewReportParameters['ranking'] ?? 'all';
            abort_unless($previewRanking === 'all' || $previewRanking === $parameters['ranking'], 404);
            abort_unless(
                ($this->previewReportParameters['gender'] ?? null) === null
                    || $this->previewReportParameters['gender'] === $parameters['gender'],
                404,
            );
            abort_unless(
                ($this->previewReportParameters['distance'] ?? null) === null
                    || $this->previewReportParameters['distance'] === $parameters['distance'],
                404,
            );

            $participant = ParticipantAgeReport::candidateQuery(
                $year,
                $parameters['gender'],
                $parameters['distance'],
            )->whereKey($participantId)->firstOrFail();
            $reportKey = ParticipantReportExclusion::ageKey(
                $parameters['ranking'],
                $parameters['gender'],
                $parameters['distance'],
            );
        } elseif ($this->previewReportType === 'group') {
            $type = validator([
                'year' => $year,
                'type' => $this->previewReportParameters['type'] ?? null,
            ], [
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'type' => ['required', Rule::in(['couple', 'family'])],
            ])->validate()['type'];

            $participant = RegistrationGroupReport::candidateQuery($year, $type)
                ->whereKey($participantId)
                ->firstOrFail();
            $reportKey = ParticipantReportExclusion::groupKey($type);
        } else {
            abort(404);
        }

        ParticipantReportExclusion::query()->updateOrCreate([
            'participant_id' => $participant->getKey(),
            'year' => $year,
            'report_key' => $reportKey,
        ], [
            'excluded_by' => Auth::id(),
        ]);

        Notification::make()
            ->success()
            ->title('Participant excluded')
            ->body("{$participant->firstName} {$participant->lastName} was removed from this report. The preview has been refreshed.")
            ->send();
    }

    /** @return array<int, array{report_key: string, title: string, participants: array<int, array<string, int|string>>}> */
    public function previewExcludedSections(): array
    {
        $definitions = $this->previewReportDefinitions();
        $year = (int) ($this->previewReportParameters['year'] ?? 0);

        if ($definitions === [] || $year === 0) {
            return [];
        }

        $asOf = $year === (int) now()->year
            ? CarbonImmutable::today()
            : CarbonImmutable::create($year, 12, 31)->startOfDay();

        $exclusions = ParticipantReportExclusion::query()
            ->with('participant')
            ->where('year', $year)
            ->whereIn('report_key', array_keys($definitions))
            ->orderBy('id')
            ->get()
            ->groupBy('report_key');

        $sections = [];

        foreach ($definitions as $reportKey => $title) {
            $participants = $exclusions->get($reportKey, collect())
                ->map(function (ParticipantReportExclusion $exclusion) use ($asOf): ?array {
                    $participant = $exclusion->participant;

                    if (! $participant instanceof Participant) {
                        return null;
                    }

                    return [
                        'participant_id' => $participant->getKey(),
                        'name' => trim(implode(' ', array_filter([
                            $participant->firstName,
                            $participant->middleInitial,
                            $participant->lastName,
                        ]))),
                        'birth_date' => filled($participant->birthDate) && $participant->birthDate !== '0000-00-00'
                            ? Carbon::parse($participant->birthDate)->format('m/d/Y')
                            : 'Not recorded',
                        'age' => filled($participant->birthDate)
                            && $participant->birthDate !== '0000-00-00'
                            && CarbonImmutable::parse($participant->birthDate)->lessThanOrEqualTo($asOf)
                                ? (int) CarbonImmutable::parse($participant->birthDate)->diffInYears($asOf)
                                : 'Not recorded',
                        'phone_number' => $participant->contactNumber ?: 'Not provided',
                        'category' => $participant->categoryDescription,
                        'subcategory' => $participant->subDescription ?: 'No subcategory',
                    ];
                })
                ->filter()
                ->values()
                ->all();

            if ($participants !== []) {
                $sections[] = [
                    'report_key' => $reportKey,
                    'title' => $title,
                    'participants' => $participants,
                ];
            }
        }

        return $sections;
    }

    public function includePreviewParticipant(int $participantId, string $reportKey): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless(array_key_exists($reportKey, $this->previewReportDefinitions()), 404);

        $exclusion = ParticipantReportExclusion::query()
            ->with('participant')
            ->where('participant_id', $participantId)
            ->where('year', (int) ($this->previewReportParameters['year'] ?? 0))
            ->where('report_key', $reportKey)
            ->firstOrFail();
        $participantName = trim(implode(' ', array_filter([
            $exclusion->participant?->firstName,
            $exclusion->participant?->lastName,
        ])));

        $exclusion->delete();

        Notification::make()
            ->success()
            ->title('Participant included again')
            ->body(($participantName !== '' ? $participantName : 'The participant').' is eligible to appear in this report again.')
            ->send();
    }

    public function downloadPreview(): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);

        $document = match ($this->previewReportType) {
            'age' => $this->previewAgeDocument(),
            'group' => $this->previewGroupDocument(),
            default => abort(404),
        };

        return response()->streamDownload(static function () use ($document): void {
            echo $document['content'];
        }, $document['filename'], ['Content-Type' => 'application/pdf']);
    }

    public function previewReportUrl(
        string $ranking = 'all',
        ?string $gender = null,
        ?string $distance = null,
    ): string {
        return route('participant-reports.preview.age', array_filter([
            'year' => $this->selectedYear,
            'ranking' => $ranking,
            'gender' => $gender,
            'distance' => $distance,
        ], fn (mixed $value): bool => $value !== null));
    }

    public function previewRunnerListUrl(string $type): string
    {
        return route('participant-reports.preview.group', [
            'type' => $type,
            'year' => $this->selectedYear,
        ]);
    }

    /** @return array{content: string, filename: string} */
    private function previewAgeDocument(): array
    {
        $parameters = validator($this->previewReportParameters, [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'ranking' => ['required', Rule::in(['all', 'oldest', 'youngest'])],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'distance' => ['nullable', Rule::in(['5km', '10km'])],
        ])->validate();

        return ParticipantReportPdf::age(
            Auth::user(),
            (int) $parameters['year'],
            $parameters['ranking'],
            $parameters['gender'] ?? null,
            $parameters['distance'] ?? null,
        );
    }

    /** @return array{content: string, filename: string} */
    private function previewGroupDocument(): array
    {
        $parameters = validator($this->previewReportParameters, [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'type' => ['required', Rule::in(['couple', 'family'])],
        ])->validate();

        return ParticipantReportPdf::group(
            Auth::user(),
            (int) $parameters['year'],
            $parameters['type'],
        );
    }

    private function agePreviewTitle(
        string $ranking,
        ?string $gender,
        ?string $distance,
        int $year,
    ): string {
        if ($ranking === 'all') {
            return "All 8 Participant Age Reports · {$year}";
        }

        return collect([
            'Top 10 '.ucfirst($ranking),
            $gender ? ucfirst($gender) : null,
            $distance ? strtoupper(str_replace('km', 'K', $distance)) : null,
            (string) $year,
        ])->filter()->join(' · ');
    }

    /** @return array<string, string> */
    private function previewReportDefinitions(): array
    {
        if ($this->previewReportType === 'group') {
            $type = (string) ($this->previewReportParameters['type'] ?? '');

            return in_array($type, ['couple', 'family'], true)
                ? [ParticipantReportExclusion::groupKey($type) => ucfirst($type).' Runner List']
                : [];
        }

        if ($this->previewReportType !== 'age') {
            return [];
        }

        $selectedRanking = (string) ($this->previewReportParameters['ranking'] ?? 'all');
        $selectedGender = $this->previewReportParameters['gender'] ?? null;
        $selectedDistance = $this->previewReportParameters['distance'] ?? null;
        $rankings = $selectedRanking === 'all' ? ['oldest', 'youngest'] : [$selectedRanking];
        $genders = $selectedGender === null ? ['male', 'female'] : [$selectedGender];
        $distances = $selectedDistance === null ? ['5km', '10km'] : [$selectedDistance];
        $definitions = [];

        foreach ($rankings as $ranking) {
            foreach ($genders as $gender) {
                foreach ($distances as $distance) {
                    if (! in_array($ranking, ['oldest', 'youngest'], true)
                        || ! in_array($gender, ['male', 'female'], true)
                        || ! in_array($distance, ['5km', '10km'], true)) {
                        continue;
                    }

                    $definitions[ParticipantReportExclusion::ageKey($ranking, $gender, $distance)] = collect([
                        'Top 10 '.ucfirst($ranking),
                        ucfirst($gender),
                        strtoupper(str_replace('km', 'K', $distance)),
                    ])->join(' · ');
                }
            }
        }

        return $definitions;
    }
}
