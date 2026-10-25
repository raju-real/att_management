<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\Device;
use App\Models\Shift;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AttendanceReportService as Report;
use App\Services\MonthlySheetService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Multi-institute isolation, the device gate and every report page.
 * Runs inside a transaction on the dev database (rolled back afterwards).
 */
class MultiInstituteTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $a;
    protected User $b;
    protected array $dev = [];

    protected function setUp(): void
    {
        parent::setUp();
        cache()->flush();

        $this->admin = User::where('role', 'admin')->firstOrFail();
        $this->a = $this->institute('Alpha High School', 'alpha@test.local');
        $this->b = $this->institute('Beta College', 'beta@test.local');

        foreach (['A' => $this->a, 'B' => $this->b] as $key => $inst) {
            $shift = Shift::create([
                'institute_id' => $inst->id, 'title' => "Day {$key}", 'in_time' => '08:00:00', 'out_time' => '14:00:00',
                'late_count_time' => '08:10:00', 'early_out_count_time' => '13:50:00', 'punch_before_minutes' => 180,
                'punch_after_minutes' => 360, 'is_overnight' => false, 'status' => 'active',
            ]);
            $dept = Department::create(['institute_id' => $inst->id, 'name' => "Science {$key}", 'shift_id' => $shift->id, 'status' => 'active']);

            // the SAME device ID 101 in both institutes
            $t = new Teacher();
            $t->institute_id = $inst->id;
            $t->teacher_no = '101';
            $t->name = "Teacher {$key}";
            $t->department_id = $dept->id;
            $t->save();

            $d = new Device();
            $d->institute_id = $inst->id;
            $d->name = "Gate {$key}";
            $d->serial_no = "TESTSN{$key}" . random_int(1000, 9999);
            $d->slug = strtolower($d->serial_no);
            $d->device_for = 'student_teacher';
            $d->status = 'active';
            $d->use_push_mode = true;
            $d->device_port = 4370;
            $d->comm_key = 0;
            $d->created_by = $this->admin->id;
            $d->save();
            $this->dev[$key] = $d;
        }
    }

    protected function institute(string $name, string $email): User
    {
        $u = new User();
        $u->role = 'institute';
        $u->name = $name;
        $u->email = $email;
        $u->password = Hash::make('secret123');
        $u->password_plain = '';
        $u->status = 'active';
        $u->created_by = 1;
        $u->address = $name . ' Road, Dhaka';
        $u->eiin = '1234';
        $u->save();
        return $u;
    }

    protected function punch(string $sn, string $pin, string $time)
    {
        return $this->call('POST', '/iclock/cdata?SN=' . $sn . '&table=ATTLOG', [], [], [], ['CONTENT_TYPE' => 'text/plain'],
            "{$pin}\t{$time}\t0\t1\t0");
    }

    public function test_device_gate_and_institute_on_saved_punches(): void
    {
        $day = Carbon::today()->subDay()->format('Y-m-d');

        $this->punch($this->dev['A']->serial_no, '101', "$day 08:05:00")->assertOk();
        $this->punch($this->dev['B']->serial_no, '101', "$day 08:30:00")->assertOk();
        // unregistered device: answered "OK" but nothing saved, device not created
        $this->punch('UNKNOWN_SN_999', '101', "$day 08:00:00")->assertOk();

        $this->assertSame(1, AttendanceLog::where('device_serial', $this->dev['A']->serial_no)->where('institute_id', $this->a->id)->count());
        $this->assertSame(1, AttendanceLog::where('device_serial', $this->dev['B']->serial_no)->where('institute_id', $this->b->id)->count());
        $this->assertSame(0, AttendanceLog::where('device_serial', 'UNKNOWN_SN_999')->count());
        $this->assertFalse(Device::withoutGlobalScopes()->where('serial_no', 'UNKNOWN_SN_999')->exists());

        // inactive device is refused too
        $this->dev['B']->update(['status' => 'inactive']);
        $this->punch($this->dev['B']->serial_no, '101', "$day 09:00:00")->assertOk();
        $this->assertSame(1, AttendanceLog::where('device_serial', $this->dev['B']->serial_no)->count());

        // reports: same device ID 101 → two different people, each with their own punch
        Auth::login($this->admin);
        $rows = Report::dailyQuery($day, $day, ['user_type' => 'teacher'])->get()->keyBy('institute_id');
        $this->assertSame('Teacher A', $rows[$this->a->id]->name);
        $this->assertSame('Teacher B', $rows[$this->b->id]->name);
        $this->assertSame(0, (int) $rows[$this->a->id]->is_late);   // 08:05 < 08:10
        $this->assertSame(1, (int) $rows[$this->b->id]->is_late);   // 08:30 > 08:10

        // institute user: only its own rows, even when asking for the other institute
        Auth::login($this->a);
        $rows = Report::dailyQuery($day, $day, ['institute_id' => $this->b->id])->get();
        $this->assertCount(1, $rows);
        $this->assertSame('Teacher A', $rows->first()->name);
        $this->assertSame(1, Teacher::count());
        $this->assertSame(1, Device::count());
        $this->assertSame([$this->a->id], AttendanceLog::pluck('institute_id')->unique()->values()->all());

        $sheet = MonthlySheetService::buildRange($day, $day, ['institute_id' => $this->b->id]);
        $this->assertCount(1, $sheet['sections']);
        $this->assertSame('Teacher A', $sheet['sections'][0]['teachers'][0]['name']);

        $board = Report::teacherBoard($day, ['institute_id' => $this->b->id]);
        $this->assertSame(1, $board['summary']['total']);
    }

    public function test_access_rules(): void
    {
        $this->actingAs($this->a);
        $this->get('/institutes')->assertRedirect(route('permission-denied'));
        $this->get('/devices/create')->assertRedirect(route('permission-denied'));
        $this->get('/site-settings')->assertRedirect(route('permission-denied'));
        $this->get('/devices')->assertOk()->assertSee('Gate A')->assertDontSee('Gate B');

        $teacherB = Teacher::withoutGlobalScopes()->where('institute_id', $this->b->id)->first();
        $this->get('/teachers/' . $teacherB->id . '/edit')->assertNotFound();
        $this->get('/teacher-profiles/' . $teacherB->id)->assertNotFound();

        // creating a teacher as an institute: stamped with its institute, cannot pick another
        $deptA = Department::first();
        $this->post('/teachers', [
            'institute_id' => $this->b->id, 'name' => 'New Staff', 'teacher_no' => '102', 'department_id' => $deptA->id,
            'education' => [['exam' => 'SSC', 'institute' => 'X School', 'year' => '2005', 'gpa' => '5.00'], ['exam' => '', 'institute' => '', 'year' => '', 'gpa' => '']],
        ])->assertRedirect(route('teachers.index'));
        $new = Teacher::where('teacher_no', '102')->firstOrFail();
        $this->assertSame($this->a->id, (int) $new->institute_id);
        $this->assertCount(1, $new->education);

        // inactive institute cannot log in
        Auth::logout();
        $this->b->update(['status' => 'inactive']);
        $this->post('/admin-login', ['email' => 'beta@test.local', 'password' => 'secret123']);
        $this->assertGuest();
        $this->post('/admin-login', ['email' => 'alpha@test.local', 'password' => 'secret123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->a);
    }

    public function test_admin_crud_and_validation(): void
    {
        $this->actingAs($this->admin);

        // device: institute is mandatory
        $this->post('/devices', ['name' => 'X', 'serial_no' => 'NEWSN1', 'status' => 'active', 'device_for' => 'teacher'])
            ->assertSessionHasErrors('institute_id');

        // teacher: device ID unique per institute (101 exists in A) but free in a third institute
        $c = $this->institute('Gamma School', 'gamma@test.local');
        $sh = Shift::create(['institute_id' => $c->id, 'title' => 'Day C', 'in_time' => '08:00:00', 'out_time' => '14:00:00', 'status' => 'active']);
        $dc = Department::create(['institute_id' => $c->id, 'name' => 'Arts C', 'shift_id' => $sh->id, 'status' => 'active']);
        $deptA = Department::where('institute_id', $this->a->id)->first();

        $this->post('/teachers', ['institute_id' => $this->a->id, 'name' => 'Dup', 'teacher_no' => '101', 'department_id' => $deptA->id])
            ->assertSessionHasErrors('teacher_no');
        $this->post('/teachers', ['institute_id' => $c->id, 'name' => 'Wrong dept', 'teacher_no' => '101', 'department_id' => $deptA->id])
            ->assertSessionHasErrors('department_id');
        $this->post('/teachers', ['institute_id' => $c->id, 'name' => 'Gamma Teacher', 'teacher_no' => '101', 'department_id' => $dc->id])
            ->assertRedirect(route('teachers.index'));
        $this->assertSame(3, Teacher::where('teacher_no', '101')->count());

        // institute CRUD
        $this->post('/institutes', ['name' => 'Delta', 'email' => 'delta@test.local', 'status' => 'active',
            'password' => 'abc123', 'password_confirmation' => 'abc123'])->assertRedirect(route('institutes.index'));
        $this->assertTrue(User::institutes()->where('email', 'delta@test.local')->exists());
        $this->delete('/institutes/' . $this->a->id)->assertRedirect(route('institutes.index'));
        $this->assertTrue(User::whereKey($this->a->id)->exists()); // has data → not deleted
    }

    public function test_pages_render_for_admin_and_institute(): void
    {
        $day = Carbon::today()->subDay()->format('Y-m-d');
        $this->punch($this->dev['A']->serial_no, '101', "$day 08:05:00");
        $teacherA = Teacher::withoutGlobalScopes()->where('institute_id', $this->a->id)->first();
        $teacherA->forceFill(['education' => [['exam' => 'SSC', 'institute' => 'Y', 'year' => '2001', 'gpa' => '4.5']], 'gender' => 'Male'])->save();

        $pages = [
            '/dashboard', '/teachers', '/teachers/create', '/teachers/import', '/students', '/students/create',
            '/departments', '/departments/create', '/shifts', '/shifts/create', '/devices', '/devices-monitor',
            '/devices-monitor/data', '/attendance-logs?from_date=' . $day . '&to_date=' . $day,
            '/attendance-monthly-summary', '/attendance-month-day-summary', '/attendance-unmatched',
            '/attendance-board', '/attendance-board/data', '/teacher-profiles', '/teacher-profiles/' . $teacherA->id,
            '/teachers/' . $teacherA->id . '/edit',
        ];

        foreach ([$this->admin, $this->a] as $user) {
            $this->actingAs($user);
            foreach ($pages as $url) {
                $r = $this->get($url);
                $this->assertSame(200, $r->status(), "{$user->name}: {$url} → {$r->status()}");
            }
        }

        // admin-only pages
        $this->actingAs($this->admin);
        foreach (['/institutes', '/institutes/create', '/institutes/' . $this->a->id . '/edit', '/devices/create', '/site-settings',
                  '/dashboard?institute_id=' . $this->a->id, '/attendance-logs?institute_id=' . $this->a->id] as $url) {
            $this->assertSame(200, $this->get($url)->status(), $url);
        }

        // headings carry the institute name
        $this->actingAs($this->a);
        $this->get('/attendance-logs?from_date=' . $day . '&to_date=' . $day)->assertSee('Alpha High School')->assertSee('Teacher A')->assertDontSee('Teacher B');
        $this->get('/teacher-profiles/' . $teacherA->id)->assertSee('Teacher/Staff Profile')->assertSee('Alpha High School')->assertSee('SSC');

        // downloads
        $pdf = $this->get('/teacher-profiles/' . $teacherA->id . '?export=pdf');
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $sheetPdf = $this->get('/attendance-month-day-summary?export=pdf');
        $sheetPdf->assertOk();
        $this->assertStringStartsWith('%PDF', $sheetPdf->getContent());
        $this->get('/attendance-month-day-summary?export=xlsx')->assertOk();
        $csv = $this->get('/attendance-logs?export=csv&from_date=' . $day . '&to_date=' . $day);
        $csv->assertOk();
        $body = $csv->streamedContent();
        $this->assertStringContainsString('Alpha High School', $body);
        $this->assertStringContainsString('Teacher/Staff', $body);
        $this->assertStringNotContainsString('Teacher B', $body);
    }
}
