<?php

namespace App\Filament\Pages;

use App\Models\Participant;
use App\Models\User;
use App\Services\ParticipantReportPdf;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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
}
