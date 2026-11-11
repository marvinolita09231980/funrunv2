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
}
