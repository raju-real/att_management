<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time move to multi-institute: give every row that has no institute
 * yet (data from before the upgrade) to one institute.
 *
 *   php artisan institute:assign 5            → assign
 *   php artisan institute:assign 5 --dry-run  → only show the counts
 *
 * Devices are included, so after this the device is registered again and
 * its punches are accepted (see ZktecoAdmsController::registered()).
 */
class AssignInstitute extends Command
{
    protected $signature = 'institute:assign {institute_id : users.id of the institute} {--dry-run : only count, change nothing}';

    protected $description = 'Assign all rows without an institute (teachers, students, departments, shifts, devices, attendance, device users) to one institute';

    public const TABLES = ['devices', 'departments', 'shifts', 'teachers', 'students', 'device_users', 'attendance_logs'];

    public function handle(): int
    {
        $institute = User::institutes()->find((int) $this->argument('institute_id'));
        if (! $institute) {
            $this->error('No institute with id ' . $this->argument('institute_id') . '. Create it in Configuration → Institutes first.');
            $this->line('Institutes: ' . (User::institutes()->pluck('name', 'id')->map(fn ($n, $id) => "{$id} = {$n}")->implode(', ') ?: 'none'));
            return self::FAILURE;
        }

        $rows = [];
        foreach (self::TABLES as $table) {
            $rows[] = [$table, DB::table($table)->whereNull('institute_id')->count()];
        }
        $this->info("Rows without an institute → \"{$institute->name}\" (id {$institute->id}):");
        $this->table(['Table', 'Rows'], $rows);

        if ($this->option('dry-run')) {
            $this->comment('Dry run: nothing changed.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($institute) {
            foreach (self::TABLES as $table) {
                DB::table($table)->whereNull('institute_id')->update(['institute_id' => $institute->id]);
            }
        });

        // devices may now send attendance again
        \App\Observers\DeviceObserver::refresh();

        $this->info('Done. Active devices allowed to send attendance: ' . implode(', ', activeDeviceSerials() ?: ['none']));
        return self::SUCCESS;
    }
}
