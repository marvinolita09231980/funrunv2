<?php

namespace App\Exports;

use Carbon\Carbon;
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


class FilteredSheetExport implements WithHeadings, WithEvents, WithStyles
{
        protected string $subcategory;
        protected string $letterStart;
        protected string $letterEnd;
        protected string $year;

        protected ?string $distanceCategory = null;
        protected ?string $categoryDescription = null;
        protected ?string $subDescription = null;
        protected ?string $gender = null;
        protected ?string $pwd = null;
        protected ?string $order_by_column = null;
        protected ?string $order_by_desc_asc = null;

    public function __construct(array $data)
    {
        $this->subcategory          = $data['subcategory'] ?? '';
        $this->year                 = $data['year'] ?? date('Y');
        $this->distanceCategory     = $data['distanceCategory'] ?? null;
        $this->categoryDescription  = $data['categoryDescription'] ?? null;
        $this->subDescription       = $data['subDescription'] ?? null;
        $this->gender               = $data['gender'] ?? null;
        $this->pwd                  = $data['pwd'] ?? null;
        $this->order_by_column      = $data['order_by_column'] ?? null;
        $this->order_by_desc_asc    = $data['order_by_desc_asc'] ?? 'ASC';
    }

    public function headings(): array
    {
        // Main column headings (row 12)
        return [
            'No.',
            'Name',
            'Birth Date',
            'Age',
            'Distance',
            'Gender',
            'Description',
            'Group/Agency',
            'Contact Number' 
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
            
                // === PARTICIPANT DATA ===

                $category = $this->subcategory;


                 $participants = Participant::select(
                        'firstName', 
                        'middleInitial', 
                        'lastName', 
                        'distanceCategory', 
                        'shirtSize', 
                        'gender',
                        'categoryDescription',
                        'subDescription',
                        'birthDate',
                        'contactNumber'
                    )
                    ->when($this->year, fn($query, $year) => $query->where('year', $year))
                    ->when($this->distanceCategory, fn($query, $distanceCategory) => $query->where('distanceCategory', $distanceCategory))
                    ->when($this->categoryDescription, fn($query, $categoryDescription) => $query->where('categoryDescription', $categoryDescription))
                    ->when($this->subDescription, fn($query, $subDescription) => $query->where('subDescription', $subDescription))
                    ->when($this->gender, fn($query, $gender) => $query->where('gender', $gender))
                    ->when($this->pwd !== null, fn($query, $pwd) => $query->where('pwd', $pwd)) // if you have pwd field
                    // ->whereRaw("LEFT(UPPER(TRIM(lastName)), 1) BETWEEN ? AND ?", [$this->letterStart, $this->letterEnd])
                    ->when($this->order_by_column, function($query) {
                        $query->orderBy($this->order_by_column, $this->order_by_desc_asc ?? 'ASC');
                    })
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
                $sheet->getColumnDimension('C')->setWidth(32);  // Race Bib
                $sheet->getColumnDimension('D')->setWidth(15);  // Name
                $sheet->getColumnDimension('E')->setWidth(6);  // Distance
                $sheet->getColumnDimension('F')->setWidth(8);  // shirsize
                $sheet->getColumnDimension('G')->setWidth(12);   // Ethnicity (IP) 
                $sheet->getColumnDimension('H')->setWidth(30);   // Ethnicity (Non-IP)
                $sheet->getColumnDimension('I')->setWidth(40);  // Gender (single column)
                $sheet->getColumnDimension('J')->setWidth(15);  // Signature


                // === HEADER TEXT ===
                $sheet->mergeCells('B1:J1');
                $sheet->setCellValue('B1', 'Republic of the Philippines');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('B1')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B2:J2');
                $sheet->setCellValue('B2', 'Province of Davao de Oro');
                $sheet->getStyle('B2')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B3:J3');
                $sheet->setCellValue('B3', 'OFFICE OF THE GOVERNOR');
                $sheet->getStyle('B3')->getFont()->setBold(true);
                $sheet->getStyle('B3')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B4:J4');
                $sheet->setCellValue('B4', '4th Floor, Executive Bldg., Provincial Capitol Complex, Cabidianan, Nabunturan, Davao de Oro Province');
                $sheet->getStyle('B4')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('B5:J5');
                $sheet->setCellValue('B5', 'SINGLET ATTENDANCE SHEET');
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

                // // === TABLE HEADER ROW ===
                 $sheet->fromArray($this->headings(), null, 'B12');
                 $sheet->getStyle('B12:J12')->getFont()->setBold(true);
                 $sheet->getStyle('B12:J12')->getAlignment()->setHorizontal('center');
                 $sheet->getStyle('B12:J12')->getBorders()->getAllBorders()->setBorderStyle('thin');

             

                // $sheet->getStyle('F13:G13')->getAlignment()->setHorizontal('center');
                // $sheet->getStyle('F13:G13')->getFont()->setBold(true);
                // $sheet->getStyle('B12:J13')->getBorders()->getAllBorders()->setBorderStyle('thin');

               


                
                $data = [];
                $ctr = 1;
                foreach ($participants as $p) {
                    
                    $middle = $p->middleInitial ? "{$p->middleInitial}." : '';
                    $fullName = trim("{$p->lastName}, {$p->firstName} {$middle}");
                    $fullName = Str::title(strtolower($fullName));
                    $age = $p->birthDate ? Carbon::parse($p->birthDate)->age : '';

                    $data[] = [
                        $ctr++, 
                        $fullName, 
                        $p->birthDate, 
                        $age,               
                        $p->distanceCategory ?? '',
                        $p->gender ?? '',
                        $p->categoryDescription ?? '',                      
                        $p->subDescription ?? '',                      
                        $p->contactNumber ?? '',                        
                    ];
                }



                // Insert participant rows starting from row 12, column B
                $sheet->fromArray($data, null, 'B13');

                // Add border to all rows
                $lastRow = 12 + count($data);
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
