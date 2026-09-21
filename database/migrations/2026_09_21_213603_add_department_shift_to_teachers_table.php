<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable at the DB level so existing teachers aren't broken — the
     * "every teacher must have a department & shift" rule is enforced by
     * TeacherController validation for new saves, not a NOT NULL constraint.
     */
    public function up()
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('designation')->constrained('departments')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->after('department_id')->constrained('shifts')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('shift_id');
        });
    }
};
