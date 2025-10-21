<?php

namespace App\Filament\Exports;

use App\Models\Participant;
use Illuminate\Support\Number;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;


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
                ->label('Organization'),
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

      
    
    public function makeXlsxRow(array $values, ?Style $style = null): Row
    {
        $rows = [];

    
            $titleStyle = Style::make()
                ->fontBold()
                ->fontSize(16)
                ->alignmentHorizontal('center');

            $columns = array_values($this->getColumns());

            $titleRow = array_merge(['Participant Report'], array_fill(1, count($columns) - 1, ''));

            $rows[] = Row::fromValues($titleRow, $titleStyle);

           
            $headerStyle = Style::make()
                ->fontBold()
                ->backgroundColor('4472c4')
                ->fontColor('ffffff')
                ->alignmentHorizontal('center');

            $rows[] = Row::fromValues($columns, $headerStyle);
    }
}
