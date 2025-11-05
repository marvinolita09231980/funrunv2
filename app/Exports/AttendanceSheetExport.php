<?php

namespace App\Exports;

use App\Models\Participant;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceSheetExport implements WithHeadings, WithEvents, WithStyles, ShouldAutoSize
{
    protected string $subcategory; 
    protected string $datenow; 

    public function __construct(string $subcategory)
    {
        $this->subcategory = $subcategory;
        $this->datenow = date('Y');
    }
    public function headings(): array
    {
        // Table header row (starting at column B)
        return [
            'Racebib No.',
            'Name',
            'Distance',
            'Shirt Size',
            'Ethnicity',
            'Gender',
            'Signature',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // === HEADER TEXT ===
                $sheet->mergeCells('B1:H1');
                $sheet->setCellValue('B1', 'Republic of the Philippines');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('B1')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B2:H2');
                $sheet->setCellValue('B2', 'Province of Davao de Oro');
                $sheet->getStyle('B2')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B3:H3');
                $sheet->setCellValue('B3', 'PROVINCIAL INFORMATION AND COMMUNICATIONS TECHNOLOGY OFFICE');
                $sheet->getStyle('B3')->getFont()->setBold(true);
                $sheet->getStyle('B3')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B4:H4');
                $sheet->setCellValue('B4', '3rd Floor, Capitol Bldg., Provincial Capitol, Cabidianan, Nabunturan, Davao de Oro Province');
                $sheet->getStyle('B4')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B5:H5');
                $sheet->setCellValue('B5', 'ATTENDANCE SHEET');
                $sheet->getStyle('B5')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('B5')->getAlignment()->setHorizontal('center');

                // === DATA PRIVACY NOTICE (Rich Text) ===
                $richText = new RichText();
                $boldPart = $richText->createTextRun('DATA PRIVACY NOTICE: ');
                $boldPart->getFont()->setBold(true);

                $richText->createText(
                    'Data and information in this form are intended exclusively for the purpose of this activity. This will be kept by the process owner for the purpose of verifying and authenticating identity of the participants. Serving other purposes not intended by the process owner is a violation of Data Privacy Act of 2012. Data subjects voluntarily provided these data and information explicitly consenting the process owner to serve its purpose. Affixing your signature to this attendance sheet signifies your consent to the recording of statements, photographs, and/or audio or video and that these materials may be used by the process owner on internal and external channels/platforms.'
                );

                $sheet->mergeCells('B6:H8');
                $sheet->setCellValue('B6', $richText);

                // Formatting: font size 9, wrap text, and vertical alignment
                $sheet->getStyle('B6:H8')->getFont()->setSize(9);
                $sheet->getStyle('B6:H8')->getAlignment()->setWrapText(true);
                $sheet->getStyle('B6:H8')->getAlignment()->setVertical('top');

                // === ACTIVITY & DATE FIELDS ===
                $sheet->setCellValue('B9', 'Activity:');
                $sheet->mergeCells('C9:H9');
                $sheet->setCellValue('B10', 'Date:');
                $sheet->mergeCells('C10:H10');

                // === TABLE HEADER ROW ===
                $sheet->fromArray($this->headings(), null, 'B12');
                $sheet->getStyle('B12:H12')->getFont()->setBold(true);
                $sheet->getStyle('B12:H12')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('B12:H12')->getBorders()->getAllBorders()->setBorderStyle('thin');

                // === PARTICIPANT DATA ===
                $participants = Participant::select('firstName', 'middleInitial', 'lastName', 'distanceCategory', 'shirtSize')
                                ->where('subDescription',$this->subcategory)
                                ->where('year',$this->datenow)
                                ->get();

                $data = [];
                foreach ($participants as $p) {
                    $middle = $p->middleInitial ? "{$p->middleInitial}." : '';
                    $fullName = trim("{$p->firstName} {$middle} {$p->lastName}");
                    $fullName = Str::title(strtolower($fullName));
                    $data[] = [
                        $p->singlet ?? '',          // Racebib No.
                        $fullName,                  // Name
                        $p->distanceCategory ?? '',
                        $p->shirtSize ?? '',
                        '',                         // Ethnicity (blank)
                        '',                         // Gender (blank)
                        '',                         // Signature (blank)
                    ];
                }

                // Insert participant rows starting from row 12, column B
                $sheet->fromArray($data, null, 'B13');

                // Add border to all rows
                $lastRow = 11 + count($data) + 1;
                $sheet->getStyle("B12:H{$lastRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle('thin');

                // Ensure column A stays blank
                $sheet->getColumnDimension('A')->setWidth(2);

                $highestRow = $sheet->getHighestRow();
                for ($row = 1; $row <= $highestRow; $row++) {
                    $sheet->setCellValue('A' . $row, '');
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
