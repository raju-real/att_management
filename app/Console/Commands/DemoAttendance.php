<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Database\Seeders\ShibuDemoSeeder;
use Illuminate\Console\Command;

/**
 * Demo data for Shibu High School, for a chosen date.
 *
 *   php artisan demo:attendance                    today
 *   php artisan demo:attendance --date=2026-11-12  that date
 *   php artisan demo:attendance --days=30          today and the 29 working days before it
 */
class DemoAttendance extends Command
{
    protected $signature = 'demo:attendance {--date= : Date (Y-m-d), default today} {--days=1 : How many working days, ending on the date}';

    protected $description = 'Seed demo teachers/staff (with photos), departments, shifts and attendance for Shibu High School';

    public function handle(): int
    {
        $date = $this->option('date') ?: today()->toDateString();
        try {
            $date = Carbon::createFromFormat('Y-m-d', $date)->toDateString();
        } catch (\Throwable $e) {
            $this->error('Use --date=YYYY-MM-DD');
            return self::FAILURE;
        }
        if ($date > today()->toDateString()) {
            $this->error('The date cannot be in the future.');
            return self::FAILURE;
        }

        ShibuDemoSeeder::$date = $date;
        ShibuDemoSeeder::$days = max(1, min(366, (int) $this->option('days')));
        $this->call('db:seed', ['--class' => ShibuDemoSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
