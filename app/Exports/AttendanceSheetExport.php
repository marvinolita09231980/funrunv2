<?php

namespace App\Exports;

use App\Models\Participant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class AttendanceSheetExport implements WithHeadings, WithEvents, WithStyles
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
        // Main column headings (row 12)
        return [
            'No.',
            'Race Bib',
            'Name',
            'Distance',
            'Shirt Size',
            'Ethnicity', // Parent heading (merged for IP / Non-IP)
            '',          // placeholder for Ethnicity subheader
            'Gender',
            'Signature',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
            
                // === PARTICIPANT DATA ===
                $participants = Participant::select('firstName', 'middleInitial', 'lastName', 'distanceCategory', 'shirtSize', 'gender','categoryDescription')
                    ->where('subDescription', $this->subcategory)
                    ->where('year', $this->datenow)
                    ->orderBy('lastName')
                    ->orderBy('firstName')
                    ->get();



                $pageSetup = $sheet->getPageSetup();
                $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $pageSetup->setPaperSize(PageSetup::PAPERSIZE_LEGAL);
                $pageSetup->setFitToWidth(1);
                $pageSetup->setFitToHeight(0);

                 // === MARGINS (0.5 inch) ===
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setBottom(0.5);
                $sheet->getPageMargins()->setLeft(0.5);
                $sheet->getPageMargins()->setRight(0.5);

                $leftLogoPath  = public_path('images/login-logo.png');
                $rightLogoPath = public_path('images/BP LOGO.png');

                // Helper to attach drawing safely
                $attachImage = function(string $path, string $cell, int $height = 80, int $offsetX = 60, int $offsetY = 5) use ($sheet) {
                    if (! file_exists($path)) {
                        throw new \RuntimeException("Logo file not found: {$path}");
                    }

                    // Create drawing
                    $drawing = new Drawing();
                    $drawing->setName(pathinfo($path, PATHINFO_FILENAME));
                    $drawing->setDescription(pathinfo($path, PATHINFO_FILENAME));
                    $drawing->setPath($path);

                    // Set size and position
                    $drawing->setHeight($height);        // height in pixels, adjust as needed
                    $drawing->setCoordinates($cell);     // cell to anchor the image
                    $drawing->setOffsetX($offsetX);
                    $drawing->setOffsetY($offsetY);

                    
                    $drawing->setWorksheet($sheet);
                };

                try {
                    
                    $attachImage($leftLogoPath, 'B1', 90, 60, 4);

                    $attachImage($rightLogoPath, 'J1', 90, 8, 4);

                } catch (\Throwable $e) {
                   
                    Log::error('Excel export logo error: ' . $e->getMessage());
                   
                    throw $e;
                }

                // === SET FIXED COLUMN WIDTHS ===
                $sheet->getColumnDimension('A')->setWidth(4);   // No.
                $sheet->getColumnDimension('B')->setWidth(4);   // No.
                $sheet->getColumnDimension('C')->setWidth(18);  // Race Bib
                $sheet->getColumnDimension('D')->setWidth(32);  // Name
                $sheet->getColumnDimension('E')->setWidth(15);  // Distance
                $sheet->getColumnDimension('F')->setWidth(15);  // shirsize
                $sheet->getColumnDimension('G')->setWidth(8);   // Ethnicity (IP) 
                $sheet->getColumnDimension('H')->setWidth(8);   // Ethnicity (Non-IP)
                $sheet->getColumnDimension('I')->setWidth(10);  // Gender (single column)
                $sheet->getColumnDimension('J')->setWidth(25);  // Signature


                // === HEADER TEXT ===
                $sheet->mergeCells('B1:J1');
                $sheet->setCellValue('B1', 'Republic of the Philippines');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('B1')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B2:J2');
                $sheet->setCellValue('B2', 'Province of Davao de Oro');
                $sheet->getStyle('B2')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B3:J3');
                $sheet->setCellValue('B3', $participants->first()->categoryDescription);
                $sheet->getStyle('B3')->getFont()->setBold(true);
                $sheet->getStyle('B3')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B4:J4');
                $sheet->setCellValue('B4', '3rd Floor, Capitol Bldg., Provincial Capitol, Cabidianan, Nabunturan, Davao de Oro Province');
                $sheet->getStyle('B4')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B5:J5');
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

                $sheet->mergeCells('B6:J8');
                $sheet->setCellValue('B6', $richText);

                // Formatting: font size 9, wrap text, and vertical alignment
                $sheet->getStyle('B6:J8')->getFont()->setSize(9);
                $sheet->getStyle('B6:J8')->getAlignment()->setWrapText(true);
                $sheet->getStyle('B6:J8')->getAlignment()->setVertical('top');

                // === ACTIVITY & DATE FIELDS ===
                $sheet->setCellValue('B9', 'Activity:');
                $sheet->mergeCells('B9:C9');
                $sheet->mergeCells('D9:J9');
                $sheet->setCellValue('B10', 'Date:');
                $sheet->mergeCells('B10:C10');
                $sheet->mergeCells('D10:J10');

                // === TABLE HEADER ROW ===
                $sheet->fromArray($this->headings(), null, 'B12');
                $sheet->getStyle('B12:J12')->getFont()->setBold(true);
                $sheet->getStyle('B12:J12')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('B12:J12')->getBorders()->getAllBorders()->setBorderStyle('thin');

                
                // SUBHEADINGS ROW (row 13) — only for Ethnicity
                $sheet->setCellValue('G13', 'IP');
                $sheet->setCellValue('H13', 'Non-IP');

                $sheet->getStyle('F13:G13')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('F13:G13')->getFont()->setBold(true);
                $sheet->getStyle('B12:J13')->getBorders()->getAllBorders()->setBorderStyle('thin');

                // === MERGE MAIN HEADERS ===
                $sheet->mergeCells('B12:B13'); // No.
                $sheet->mergeCells('C12:C13'); // Race Bib
                $sheet->mergeCells('D12:D13'); // Name
                $sheet->mergeCells('E12:E13'); // Distance
                $sheet->mergeCells('F12:F13'); // shirsize
                $sheet->mergeCells('G12:H12'); // Ethnicity (IP / Non-IP)
                $sheet->mergeCells('I12:I13'); // Gender (single column)
                $sheet->mergeCells('J12:J13'); // Signature


                
                $data = [];
                $ctr = 1;
                foreach ($participants as $p) {
                    
                    $middle = $p->middleInitial ? "{$p->middleInitial}." : '';
                    $fullName = trim("{$p->lastName}, {$p->firstName} {$middle}");
                    $fullName = Str::title(strtolower($fullName));

                    $data[] = [
                        $ctr++, 
                        $p->singlet ?? '',          // Racebib No.
                        $fullName,                  // Name
                        $p->distanceCategory ?? '',
                        $p->shirtSize ?? '',
                        '',                         // IP
                        '',                         // Non-IP
                        $p->gender ?? '',                         // Gender
                        '',                         // Signature
                    ];
                }

                // Insert participant rows starting from row 12, column B
                $sheet->fromArray($data, null, 'B14');

                

                // Add border to all rows
                $lastRow = 12 + count($data) + 1;
                $sheet->getStyle("B12:J{$lastRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle('thin');

                $certRow = 12 + $ctr + 2;
                $sheet->setCellValue("B{$certRow}", 'Certified Correct:');
                $sheet->setCellValue("C" . ($certRow + 2), '_________________________');
                $sheet->setCellValue("C" . ($certRow + 3), 'Signature over Printed Name');

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
