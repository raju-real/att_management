<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Teacher's Attendance Report (date range) as .xlsx — rendered from the same
 * Blade partials as the web page (letterhead from Site Settings, grid,
 * signatures). The school logo is added as a picture in the top-left corner.
 */
class MonthlySheetExport implements FromView, WithTitle, WithEvents, WithDrawings
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
        // Sheet names: max 31 chars, no "/" — e.g. "01 Oct - 31 Oct 2026"
        $from = \Illuminate\Support\Carbon::parse($this->sheet['from']);
        $to   = \Illuminate\Support\Carbon::parse($this->sheet['to']);
        return $from->format('d M') . ' - ' . $to->format('d M Y');
    }

    public function drawings()
    {
        $logo = schoolInfo()->logo_path;
        if (!$logo) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setPath($logo);
        $drawing->setHeight(48);
        $drawing->setCoordinates('B2');
        return [$drawing];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $ws = $event->sheet->getDelegate();

                // Print like the PDF: landscape, all columns on one page width.
                $ws->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize($this->sheet['day_count'] > 31 ? PageSetup::PAPERSIZE_A3 : PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $ws->getHeaderFooter()->setOddFooter('&L' . schoolInfo()->name . '&RPage &P of &N');

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
                $ws->getRowDimension(2)->setRowHeight(26);
                $ws->freezePane('E1');
            },
        ];
    }
}
