<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Month & Day Wise Summary as .xlsx — rendered from the same Blade
 * partial as the web page and the PDF (attendance/sheet/_section).
 */
class MonthlySheetExport implements FromView, WithTitle, WithEvents
{
    public function __construct(protected array $sheet)
    {
    }

    public function view(): View
    {
        return view('attendance.sheet.xlsx', ['sheet' => $this->sheet]);
    }

    public function title(): string
    {
        return substr($this->sheet['month_name'], 0, 3) . ' ' . $this->sheet['year'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $ws = $event->sheet->getDelegate();

                // Print like the paper sheet: landscape, fit all columns on one page width.
                $ws->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                // Compact day columns, wider name column.
                $ws->getColumnDimension('A')->setWidth(5);
                $ws->getColumnDimension('B')->setWidth(8);
                $ws->getColumnDimension('C')->setWidth(26);
                $ws->getColumnDimension('D')->setWidth(9);
                $days = count($this->sheet['days']);
                for ($i = 5; $i < 5 + $days; $i++) {
                    $ws->getColumnDimensionByColumn($i)->setWidth(6.5);
                }
                for ($i = 5 + $days; $i < 10 + $days; $i++) {
                    $ws->getColumnDimensionByColumn($i)->setWidth(11);
                }
                $ws->getStyle($ws->calculateWorksheetDimension())->getAlignment()->setWrapText(true);
                $ws->freezePane('E1');
            },
        ];
    }
}
