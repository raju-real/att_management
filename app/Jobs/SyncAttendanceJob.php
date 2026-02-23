<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SyncAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fromDate;
    protected $toDate;

    public function __construct($fromDate, $toDate)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function handle()
    {
        Log::info("Job: SyncAttendanceJob started. From {$this->fromDate} to {$this->toDate}");
        try {
            Artisan::call('zk:sync-attendance', [
                '--from' => $this->fromDate,
                '--to'   => $this->toDate,
            ]);
            Log::info("Job: SyncAttendanceJob completed.");
        } catch (\Exception $e) {
            Log::error("Job: SyncAttendanceJob failed. " . $e->getMessage());
        }
    }
}
