<?php
namespace App\Exports;

use Illuminate\Contracts\View\View;

class UserWiseSummaryExport implements \Maatwebsite\Excel\Concerns\FromView
{
    protected $report;
    protected $in_time;
    protected $out_time;
    protected $from_date;
    protected $to_date;

    public function __construct($report, $from_date, $to_date, $in_time, $out_time)
    {
        $this->report   = $report;
        $this->from_date   = $from_date;
        $this->to_date   = $to_date;
        $this->in_time  = $in_time;
        $this->out_time = $out_time;
    }

    public function view(): View
    {
        return view('exports.month_wise_user_summery', [
            'report'   => $this->report,
            'from_date'   => $this->from_date,
            'to_date'   => $this->to_date,
            'in_time'  => $this->in_time,
            'out_time' => $this->out_time,
        ]);
    }
}
