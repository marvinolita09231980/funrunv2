<?php

namespace App\Exports;

use App\Filament\Pages\PotentialDuplicates;
use App\Models\AuditLog;
use App\Models\Participant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class InPlaceOfDuplicatesExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithHeadings, WithStyles, WithTitle
{
    private const PARTICIPANT_COLUMNS = [
        'Participant ID',
        'Registration Year',
        'Registration Number',
        'First Name',
        'Middle Initial',
        'Last Name',
        'Birth Date',
        'Gender',
        'Address',
        'Phone Number',
        'Category',
        'Subcategory',
        'Shirt Size',
        'Race Distance',
        'PWD',
        'RPWUDS',
        'Registered By',
    ];

    private ?Collection $logs = null;

    public function __construct(private readonly int $year)
    {
    }

    public function replacementLogs(): Collection
    {
        return $this->logs ??= AuditLog::query()
            ->where('event', 'replaced')
            ->where('auditable_type', Participant::class)
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->filter(function (AuditLog $log): bool {
                $newValues = $log->new_values ?? [];
                $oldValues = $log->old_values ?? [];

                return (int) ($newValues['year'] ?? $oldValues['year'] ?? 0) === $this->year
                    && ($newValues['categoryDescription'] ?? null) === PotentialDuplicates::REPLACEMENT_CATEGORY;
            })
            ->values();
    }

    /**
     * @return Collection<int, array{
     *     original: array<string, mixed>,
     *     replacement: array<string, mixed>,
     *     details: array<string, mixed>
     * }>
     */
    public function comparisonRows(): Collection
    {
        return $this->replacementLogs()
            ->map(fn (AuditLog $log): array => [
                'original' => $this->participantValues($log->old_values ?? []),
                'replacement' => $this->participantValues($log->new_values ?? []),
                'details' => [
                    'Replaced By' => $log->actor_display_name,
                    'Replaced At' => $log->created_at?->format('m/d/Y h:i A') ?? '',
                    'Audit Log ID' => $log->getKey(),
                ],
            ])
            ->values();
    }

    public function collection(): Collection
    {
        return $this->comparisonRows()
            ->map(fn (array $row): array => [
                ...array_values($row['original']),
                ...array_values($row['replacement']),
                ...array_values($row['details']),
            ]);
    }

    public function headings(): array
    {
        $participantColumnCount = count(self::PARTICIPANT_COLUMNS);
        $totalColumnCount = ($participantColumnCount * 2) + 3;
        $groupHeadings = array_fill(0, $totalColumnCount, '');
        $groupHeadings[0] = 'ORIGINAL DATA';
        $groupHeadings[$participantColumnCount] = 'REPLACED DATA';
        $groupHeadings[$participantColumnCount * 2] = 'REPLACEMENT DETAILS';

        return [
            $groupHeadings,
            [
                ...self::PARTICIPANT_COLUMNS,
                ...self::PARTICIPANT_COLUMNS,
                'Replaced By',
                'Replaced At',
                'Audit Log ID',
            ],
        ];
    }

    public function title(): string
    {
        return 'Replacement History';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $participantColumnCount = count(self::PARTICIPANT_COLUMNS);
                $originalEnd = Coordinate::stringFromColumnIndex($participantColumnCount);
                $replacementStart = Coordinate::stringFromColumnIndex($participantColumnCount + 1);
                $replacementEnd = Coordinate::stringFromColumnIndex($participantColumnCount * 2);
                $detailsStart = Coordinate::stringFromColumnIndex(($participantColumnCount * 2) + 1);
                $lastColumn = Coordinate::stringFromColumnIndex(($participantColumnCount * 2) + 3);
                $lastRow = max(2, $sheet->getHighestRow());

                $sheet->mergeCells("A1:{$originalEnd}1");
                $sheet->mergeCells("{$replacementStart}1:{$replacementEnd}1");
                $sheet->mergeCells("{$detailsStart}1:{$lastColumn}1");
                $sheet->freezePane('A3');
                $sheet->setAutoFilter("A2:{$lastColumn}2");
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_LEGAL)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 2);
                $sheet->getStyle("A1:{$originalEnd}1")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFB45309');
                $sheet->getStyle("{$replacementStart}1:{$replacementEnd}1")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF15803D');
                $sheet->getStyle("{$detailsStart}1:{$lastColumn}1")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF1D4ED8');
                $sheet->getStyle("A2:{$lastColumn}2")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE5E7EB');
                $sheet->getStyle("A3:{$lastColumn}{$lastRow}")->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP)
                    ->setWrapText(true);
            },
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /** @param array<string, mixed> $values */
    private function participantValues(array $values): array
    {
        return [
            'Participant ID' => $values['id'] ?? '',
            'Registration Year' => $values['year'] ?? '',
            'Registration Number' => $values['participantNumber'] ?? '',
            'First Name' => $this->text($values['firstName'] ?? null),
            'Middle Initial' => $this->text($values['middleInitial'] ?? null),
            'Last Name' => $this->text($values['lastName'] ?? null),
            'Birth Date' => $this->birthDate($values['birthDate'] ?? null),
            'Gender' => ucfirst($this->text($values['gender'] ?? null)),
            'Address' => $this->text($values['address'] ?? null),
            'Phone Number' => $this->text($values['contactNumber'] ?? null),
            'Category' => $this->text($values['categoryDescription'] ?? null),
            'Subcategory' => $this->text($values['subDescription'] ?? null),
            'Shirt Size' => strtoupper($this->text($values['shirtSize'] ?? null)),
            'Race Distance' => Participant::distanceLabel($values['distanceCategory'] ?? null),
            'PWD' => $this->booleanLabel($values['pwd'] ?? null),
            'RPWUDS' => $this->booleanLabel($values['rpwuds'] ?? null),
            'Registered By' => $this->text($values['created_by'] ?? null),
        ];
    }

    private function birthDate(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            return CarbonImmutable::parse((string) $value)->format('m/d/Y');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function booleanLabel(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
    }

    private function text(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }
}
