<?php

namespace App\Filament\Exports;

use App\Models\Participant;
use Illuminate\Support\Number;
use OpenSpout\Common\Entity\Row;
use Filament\Actions\Exports\Exporter;
use OpenSpout\Common\Entity\Style\Style;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;


class ParticipantExporter extends Exporter
{
    
    /**
     * @param array<mixed> $values
     */
    protected static ?string $model = Participant::class;
   
    public static function getColumns(): array
    {

        
        return [
           
            ExportColumn::make('singlet')
                ->label('Singlet No.')
                 ->default(''),
            ExportColumn::make('firstName')
                ->label('First Name'),
            ExportColumn::make('lastName')
                ->label('Last Name'),
            ExportColumn::make('subDescription')
                ->label('Organization')
                ->getStateUsing(fn ($record) => $record->subDescription ?? ''),
             ExportColumn::make('gender')
                 ->label('Gender'),
             ExportColumn::make('distanceCategory')
                 ->label('Distance'),
             ExportColumn::make('shirtSize')
                 ->label('Shirt Size'),
             ExportColumn::make('signature')
                  ->label('Signature')
                  ->default(''),
            
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
       
        $body = 'Your participant export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    

    public function getXlsxHeaderCellStyle(): ?Style
    {
        return (new Style())
            ->setFontBold(true)
            ->setFontSize(12)
            ->setFontName('Times New Roman')
            ->setBackgroundColor('CCCCCC')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    public function getXlsxCellStyle(): ?Style
    {
        return (new Style())
            ->setFontSize(12)
            ->setFontName('Times New Roman');
    }
    
    
}
