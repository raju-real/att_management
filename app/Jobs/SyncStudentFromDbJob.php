<?php

namespace App\Jobs;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncStudentFromDbJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        Log::info('Job: SyncStudentFromDbJob started.');

        $students = DB::connection('student_db')
            ->table('student_info')
            ->orderBy('student_id')
            ->get();

        $synced  = 0;
        $updated = 0;

        foreach ($students as $s) {
            // Use Eloquent so auto-increment id is handled by MySQL naturally
            $student = Student::withTrashed()->where('student_id', $s->student_id)->first();

            $isNew = is_null($student);

            if ($isNew) {
                $student = new Student();
                // Only generate student_no for brand-new records
                $student->student_no = Student::getStudentNo();
                $student->student_id = $s->student_id;
                $synced++;
            } else {
                $updated++;
            }

            $student->firstname   = trim($s->firstname);
            $student->middlename  = $s->middlename;
            $student->lastname    = $s->lastname;
            $student->nickname    = $s->nickname;
            $student->photo       = $s->photo;
            $student->nationality = $s->nationality;
            $student->religion    = $s->religion;
            $student->gender      = $s->gender;
            $student->class       = $s->class;
            $student->roll        = $s->roll;
            $student->section     = $s->section;
            $student->shift       = $s->shift;
            $student->medium      = $s->medium;
            $student->group       = $s->group;
            $student->fname       = $s->fname;
            $student->fmobile     = $s->fmobile;
            $student->mname       = $s->mname;
            $student->mmobile     = $s->mmobile;
            $student->bloodgroup  = $s->bloodgroup;
            $student->mobile      = $s->mobile;
            $student->session     = $s->session;
            $student->school_id   = $s->school_id;
            $student->votter_id   = $s->votter_id;
            $student->user_id     = $s->user_id;
            $student->status      = $s->status;
            $student->sms_status  = $s->sms_status;

            if ($student->trashed()) {
                $student->restore();
            }

            $student->save();
        }

        Log::info("Job: SyncStudentFromDbJob completed. Inserted: {$synced}, Updated: {$updated}.");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job: SyncStudentFromDbJob failed. ' . $exception->getMessage());
    }
}
