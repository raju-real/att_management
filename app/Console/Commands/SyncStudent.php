<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Student;

class SyncStudent extends Command
{
    protected $signature = 'sync-student';
    protected $description = 'Sync students from student_db to main db';

    public function handle()
    {
        $this->info('Student sync started...');

        $students = DB::connection('student_db')
            ->table('student_info')
            ->orderBy('student_id') // STRING, but STABLE
            ->get();

        foreach ($students as $s) {
            $student_no = Student::getStudentNo();
            $data = [
                'student_no' => $student_no,
                'student_id' => $s->student_id,
                'firstname' => trim($s->firstname),
                'middlename' => $s->middlename,
                'lastname' => $s->lastname,
                'nickname' => $s->nickname,
                'photo' => $s->photo,
                'nationality' => $s->nationality,
                'religion' => $s->religion,
                'gender' => $s->gender,
                'class' => $s->class,
                'roll' => $s->roll,
                'section' => $s->section,
                'shift' => $s->shift,
                'medium' => $s->medium,
                'group' => $s->group,
                'fname' => $s->fname,
                'fmobile' => $s->fmobile,
                'mname' => $s->mname,
                'mmobile' => $s->mmobile,
                'bloodgroup' => $s->bloodgroup,
                'mobile' => $s->mobile,
                'session' => $s->session,
                'school_id' => $s->school_id,
                'votter_id' => $s->votter_id,
                'user_id' => $s->user_id,
                'status' => $s->status,
                'sms_status' => $s->sms_status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            Student::updateOrInsert(['student_id' => $s->student_id], $data);
        }


        $this->info('Student sync completed at ' . now());
    }
}
