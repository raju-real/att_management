<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shift rules for exact day + night shift reporting:
 *  - late_count_time        first punch AFTER this = Late In   (default: in_time)
 *  - early_out_count_time   last punch BEFORE this = Early Out (default: out_time)
 *  - punch_before_minutes   punches this long before in_time belong to the shift
 *  - punch_after_minutes    punches this long after out_time belong to the shift
 *  - is_overnight           out_time is on the next day (out_time <= in_time)
 *
 * Existing shifts are back-filled so they report exactly as before.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('shifts', 'late_count_time')) {
                $table->time('late_count_time')->nullable()->after('out_time');
            }
            if (! Schema::hasColumn('shifts', 'early_out_count_time')) {
                $table->time('early_out_count_time')->nullable()->after('late_count_time');
            }
            if (! Schema::hasColumn('shifts', 'punch_before_minutes')) {
                $table->unsignedSmallInteger('punch_before_minutes')->default(180)->after('early_out_count_time');
            }
            if (! Schema::hasColumn('shifts', 'punch_after_minutes')) {
                $table->unsignedSmallInteger('punch_after_minutes')->default(360)->after('punch_before_minutes');
            }
            if (! Schema::hasColumn('shifts', 'is_overnight')) {
                $table->boolean('is_overnight')->default(false)->after('punch_after_minutes');
            }
        });

        DB::table('shifts')->whereNull('late_count_time')->update(['late_count_time' => DB::raw('in_time')]);
        DB::table('shifts')->whereNull('early_out_count_time')->update(['early_out_count_time' => DB::raw('out_time')]);
        DB::table('shifts')->update(['is_overnight' => DB::raw('CASE WHEN out_time <= in_time THEN 1 ELSE 0 END')]);
    }

    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['late_count_time', 'early_out_count_time', 'punch_before_minutes', 'punch_after_minutes', 'is_overnight']);
        });
    }
};
