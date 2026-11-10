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
        $this->get('/fee-settings')->assertRedirect(route('permission-denied'));
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

        // dashboard: admin sees nothing until an institute is chosen; an institute sees its own
        $this->get('/dashboard')->assertSee('Select an institute')->assertDontSee('id="liveDash"', false);
        $this->get('/dashboard?institute_id=' . $this->a->id)->assertSee('Alpha High School')->assertSee('id="liveDash"', false);
        $this->actingAs($this->a);
        $this->get('/dashboard?institute_id=' . $this->b->id)->assertSee('Alpha High School')->assertDontSee('Beta College')->assertSee('id="liveDash"', false);
        $this->getJson('/attendance-board/data?institute_id=' . $this->b->id)->assertJsonPath('teachers.0.name', 'Teacher A')->assertJsonCount(1, 'teachers');
        $this->actingAs($this->admin);

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

    public function test_institute_manages_only_its_own_devices(): void
    {
        $form = ['status' => 'active', 'device_for' => 'teacher'];

        // adding: stamped with its own institute, whatever the request says
        $this->actingAs($this->a);
        $this->get('/devices/create')->assertOk()->assertSee('Alpha High School');
        $this->post('/devices', $form + ['institute_id' => $this->b->id, 'name' => 'Office', 'serial_no' => 'OWNSN1', 'ip_address' => '192.168.1.201'])
            ->assertRedirect(route('devices.index'));
        $own = Device::where('serial_no', 'OWNSN1')->firstOrFail();
        $this->assertSame($this->a->id, (int) $own->institute_id);
        $this->assertContains('OWNSN1', activeDeviceSerials());

        // punches from it are accepted for this institute
        $day = Carbon::today()->subDay()->format('Y-m-d');
        $this->punch('OWNSN1', '101', "$day 08:05:00")->assertOk();
        $this->assertSame(1, AttendanceLog::where('device_serial', 'OWNSN1')->where('institute_id', $this->a->id)->count());

        // another institute's device: not reachable, and editing cannot move a device
        $this->get('/devices/' . $this->dev['B']->slug . '/edit')->assertNotFound();
        $this->put('/devices/' . $this->dev['B']->id, $form + ['name' => 'Stolen', 'serial_no' => $this->dev['B']->serial_no])->assertNotFound();
        $this->delete('/devices/' . $this->dev['B']->id)->assertNotFound();
        $this->put('/devices/' . $own->id, $form + ['institute_id' => $this->b->id, 'name' => 'Office 2', 'serial_no' => 'OWNSN1'])
            ->assertRedirect(route('devices.index'));
        $this->assertSame($this->a->id, (int) $own->fresh()->institute_id);

        // a serial in use elsewhere is refused; the same LAN IP in another school is fine
        $this->actingAs($this->b);
        $this->post('/devices', $form + ['name' => 'Copy', 'serial_no' => 'OWNSN1'])->assertSessionHasErrors('serial_no');
        $this->post('/devices', $form + ['name' => 'Lab', 'serial_no' => 'OWNSN2', 'ip_address' => '192.168.1.201'])->assertRedirect(route('devices.index'));
        $this->assertSame($this->b->id, (int) Device::where('serial_no', 'OWNSN2')->value('institute_id'));

        // deleted by one institute, added by another: the machine moves, its old users do not
        $this->actingAs($this->a);
        \App\Models\DeviceUser::create(['device_id' => $own->id, 'pin' => '555', 'name' => 'Old User', 'privilege' => 0, 'institute_id' => $this->a->id]);
        $this->delete('/devices/' . $own->id)->assertRedirect(route('devices.index'));
        $this->assertNotContains('OWNSN1', activeDeviceSerials());
        $this->actingAs($this->b);
        $this->post('/devices', $form + ['name' => 'Moved', 'serial_no' => 'OWNSN1'])->assertRedirect(route('devices.index'));
        $this->assertSame($this->b->id, (int) Device::where('serial_no', 'OWNSN1')->value('institute_id'));
        $this->assertSame(0, \App\Models\DeviceUser::withoutGlobalScopes()->where('device_id', $own->id)->count());

        // a user of the institute with the Devices menu may manage them; without it, not
        $clerk = $this->institute('Clerk', 'clerk2@test.local');
        $clerk->forceFill(['role' => 'inst_user', 'institute_id' => $this->a->id, 'permissions' => ['devices']])->save();
        $this->actingAs($clerk);
        $this->get('/devices/create')->assertOk();
        $clerk->forceFill(['permissions' => ['teachers']])->save();
        $this->actingAs($clerk->fresh());
        $this->get('/devices/create')->assertRedirect(route('permission-denied'));
    }

    public function test_teachers_are_ordered_by_number_not_name(): void
    {
        $dept = Department::where('institute_id', $this->a->id)->first();
        foreach (['10' => 'Amy', '9' => 'Zed'] as $no => $name) { // names would give Amy, Teacher A, Zed
            $t = new Teacher();
            $t->institute_id = $this->a->id;
            $t->teacher_no = (string) $no;
            $t->name = $name;
            $t->department_id = $dept->id;
            $t->save();
        }
        $day = Carbon::today()->subDay()->format('Y-m-d');
        foreach (['101' => '07:50:00', '10' => '08:30:00', '9' => '08:00:00'] as $no => $time) {
            $this->punch($this->dev['A']->serial_no, (string) $no, "$day $time");
        }

        $this->actingAs($this->a);
        $expected = ['9', '10', '101'];
        $range = ['from_date' => $day, 'to_date' => $day];

        $this->assertSame($expected, array_map(fn ($t) => $t['teacher_no'], Report::teacherBoard($day)['teachers']));
        $this->assertSame($expected, Report::logs($range)->pluck('user_no')->all());
        $this->assertSame($expected, Report::monthlySummary($range)->pluck('user_no')->all());
        $this->assertSame($expected, array_column(MonthlySheetService::buildRange($day, $day)['sections'][0]['teachers'], 'no'));

        // pages list them in the same order
        foreach (['/teachers', '/teacher-profiles', '/dashboard', '/attendance-logs?from_date=' . $day . '&to_date=' . $day] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertLessThan(strpos($html, 'Amy'), strpos($html, 'Zed'), $url);
        }
    }

    public function test_login_page_shows_the_site_logo_under_the_name(): void
    {
        $logo = siteSettings()->logo ?? null;
        $html = $this->get('/')->assertOk()->getContent();

        if ($logo && is_file(public_path($logo))) {
            $this->assertStringContainsString('login-brand-logo"><img src="' . asset($logo) . '"', $html);
            $this->assertLessThan(strpos($html, 'login-brand-logo"><img'), strpos($html, 'class="login-title"'));
        } else {
            $this->assertStringNotContainsString('login-brand-logo"><img', $html);
        }
    }

    /** Save an institute's settings through its own form. */
    protected function saveSettings(User $institute, array $values): void
    {
        $this->actingAs($institute);
        $this->put('/update-site-settings', $values + ['site_name' => $institute->name])
            ->assertRedirect(route('site-settings'))->assertSessionHasNoErrors();
    }

    public function test_each_institute_has_its_own_settings_and_holidays(): void
    {
        $globalFile = md5_file(jsonSettingsPath('site_setting.json'));
        $untouched  = weeklyHolidays(); // Beta has saved nothing yet: global values

        $this->saveSettings($this->a, [
            'site_name' => 'Alpha Renamed', 'email' => 'office@alpha.test', 'mobile' => '01700000000', 'address' => 'New Road',
            'in_time' => '09:00', 'out_time' => '15:00',
            'weekly_holidays' => ['Sunday'], 'office_holidays' => json_encode(['2026-10-14']),
        ]);

        // saved on the institute only: global file and the other institute untouched, login unchanged
        $this->assertSame($globalFile, md5_file(jsonSettingsPath('site_setting.json')));
        $a = $this->a->fresh();
        $this->assertSame('alpha@test.local', $a->email);
        $this->assertSame('Alpha Renamed', $a->name);
        $this->assertSame(['Sunday'], weeklyHolidays($a->id));
        $this->assertSame(['2026-10-14'], officeHolidays($a->id));
        $this->assertSame($untouched, weeklyHolidays($this->b->id));

        // letterhead shows the contact email, the settings page its own values
        $this->assertSame('office@alpha.test', schoolInfo($a->id)->email);
        $this->get('/site-settings')->assertOk()->assertSee('Institute Settings')->assertSee('Alpha Renamed')->assertSee('office@alpha.test');

        // Beta: Monday off. Week 11–17 Oct 2026 (Sun–Sat), Alpha also has Wed 14th off.
        $this->saveSettings($this->b, ['weekly_holidays' => ['Monday'], 'in_time' => '07:30', 'office_holidays' => '[]']);
        $this->assertSame(5, Report::workingDays('2026-10-11', '2026-10-17', $this->a->id));
        $this->assertSame(6, Report::workingDays('2026-10-11', '2026-10-17', $this->b->id));

        $dept = Department::where('institute_id', $this->a->id)->first();
        $noShift = fn (int $institute) => tap(new Teacher(), function ($t) use ($institute) {
            $t->institute_id = $institute;
            $t->teacher_no = '200';
            $t->name = "No shift {$institute}";
            $t->department_id = 0;
            $t->save();
        });
        $noShift($this->a->id);
        $noShift($this->b->id);

        // default times: each punch against its own institute, also on the all-institutes query
        Auth::logout(); // devices are not logged in
        $this->punch($this->dev['A']->serial_no, '200', '2026-10-13 08:30:00');
        $this->punch($this->dev['B']->serial_no, '200', '2026-10-13 08:30:00');
        $this->actingAs($this->admin);
        $rows = Report::dailyQuery('2026-10-13', '2026-10-13', ['user_type' => 'teacher'])->get()->keyBy('institute_id');
        $this->assertSame('09:00:00', $rows[$this->a->id]->std_in);
        $this->assertSame(0, (int) $rows[$this->a->id]->is_late);
        $this->assertSame('07:30:00', $rows[$this->b->id]->std_in);
        $this->assertSame(1, (int) $rows[$this->b->id]->is_late);

        // Month & Day sheet, all institutes: each section uses its own holidays
        $sheet = MonthlySheetService::buildRange('2026-10-11', '2026-10-17');
        $bySection = collect($sheet['sections'])->keyBy(fn ($s) => str_contains($s['title'], 'Renamed') ? 'a' : 'b');
        $this->assertTrue($bySection['a']['days']['2026-10-11']['holiday']);   // Sunday: Alpha off
        $this->assertFalse($bySection['b']['days']['2026-10-11']['holiday']);
        $this->assertTrue($bySection['b']['days']['2026-10-12']['holiday']);   // Monday: Beta off
        $this->assertTrue($bySection['a']['days']['2026-10-14']['holiday']);   // Alpha's office holiday
        $this->assertSame(5, $bySection['a']['working_days']);
        $this->assertSame(6, $bySection['b']['working_days']);
        $this->assertSame('holiday', $bySection['a']['teachers'][0]['cells']['2026-10-11']['type']);
        $this->assertSame('absent', $bySection['b']['teachers'][0]['cells']['2026-10-11']['type']);

        // the same pages render for all institutes, and the PDF / CSV still build
        $range = '?from_date=2026-10-11&to_date=2026-10-17';
        $this->get('/attendance-monthly-summary' . $range)->assertOk()->assertSee('working days vary by institute');
        $this->get('/attendance-monthly-summary' . $range . '&institute_id=' . $this->b->id)->assertOk()->assertSee('6 working day(s)');
        $this->get('/attendance-monthly-summary' . $range . '&export=csv')->assertOk();
        $this->assertStringStartsWith('%PDF', $this->get('/attendance-month-day-summary' . $range . '&export=pdf')->getContent());

        // board and dashboard data: holiday is not absent; a punch on a holiday still counts as present
        $board = Report::teacherBoard('2026-10-11');
        $status = collect($board['teachers'])->groupBy(fn ($t) => $t['name'])->map(fn ($g) => $g[0]['status']);
        $this->assertSame('holiday', $status['Teacher A']);
        $this->assertSame('absent', $status['Teacher B']);
        $this->punch($this->dev['A']->serial_no, '101', '2026-10-11 08:00:00');
        $this->assertSame('on_time', collect(Report::teacherBoard('2026-10-11', ['institute_id' => $this->a->id])['teachers'])
            ->firstWhere('name', 'Teacher A')['status']);

        // profile counts the teacher's own institute
        $teacherA = Teacher::where('institute_id', $this->a->id)->where('teacher_no', '101')->first();
        $teacherB = Teacher::where('institute_id', $this->b->id)->where('teacher_no', '101')->first();
        $this->assertNotSame(
            \App\Services\TeacherProfileService::yearly($teacherA, 2026)['months'][10]['working'],
            \App\Services\TeacherProfileService::yearly($teacherB, 2026)['months'][10]['working']
        );

        // the super admin's page edits the global settings, not an institute's
        $this->get('/site-settings')->assertOk()->assertSee('Update Site Settings');

        // an institute user needs the permission; never the fee settings
        $clerk = $this->institute('Clerk', 'clerk3@test.local');
        $clerk->forceFill(['role' => 'inst_user', 'institute_id' => $this->a->id, 'permissions' => ['teachers']])->save();
        $this->actingAs($clerk);
        $this->get('/site-settings')->assertRedirect(route('permission-denied'));
        $this->put('/update-site-settings', ['site_name' => 'Hacked'])->assertRedirect(route('permission-denied'));
        $clerk->forceFill(['permissions' => ['site-settings']])->save();
        $this->actingAs($clerk->fresh());
        $this->get('/site-settings')->assertOk()->assertSee('Alpha Renamed');
        $this->get('/fee-settings')->assertRedirect(route('permission-denied'));
    }

    public function test_daily_report_is_department_wise_per_institute(): void
    {
        // Alpha: Science A (Teacher A #101) + a second department with two people
        $shift = Shift::where('institute_id', $this->a->id)->first();
        $office = Department::create(['institute_id' => $this->a->id, 'name' => 'Office Staff', 'shift_id' => $shift->id, 'status' => 'active']);
        foreach (['301' => 'Clerk One', '302' => 'Clerk Two'] as $no => $name) {
            $t = new Teacher();
            $t->institute_id = $this->a->id;
            $t->teacher_no = (string) $no;
            $t->name = $name;
            $t->department_id = $office->id;
            $t->save();
        }
        // a past working day (Wed 21 Oct 2026; global weekly holidays are Fri/Sat)
        $day = '2026-10-21';
        $this->punch($this->dev['A']->serial_no, '101', "$day 08:25:00"); // late by 25 min (shift 08:00, late after 08:10)
        $this->punch($this->dev['A']->serial_no, '301', "$day 07:55:00"); // on time; 302 absent
        $this->punch($this->dev['B']->serial_no, '101', "$day 08:00:00"); // Beta's #101 must not count for Alpha

        $r = \App\Services\DailyReportService::build($this->a->id, $day);
        $rows = collect($r['rows'])->keyBy('department');
        $this->assertSame(['Science A', 'Office Staff'], array_column($r['rows'], 'department'));
        $this->assertSame(['employees' => 1, 'present' => 1, 'absent' => 0, 'late_present' => 1, 'late_time' => '0:25'],
            array_intersect_key($rows['Science A'], array_flip(['employees', 'present', 'absent', 'late_present', 'late_time'])));
        $this->assertSame(['employees' => 2, 'present' => 1, 'absent' => 1, 'late_present' => 0],
            array_intersect_key($rows['Office Staff'], array_flip(['employees', 'present', 'absent', 'late_present'])));
        $this->assertSame(3, $r['total']['employees']);
        $this->assertSame(2, $r['total']['present']);
        $this->assertSame(66.7, $r['total']['percent']);

        // on the institute's own holiday nobody is absent
        $this->saveSettings($this->a, ['office_holidays' => json_encode([$day])]);
        $h = \App\Services\DailyReportService::build($this->a->id, $day);
        $this->assertTrue($h['is_holiday']);
        $this->assertSame(0, $h['total']['absent']);
        $this->assertSame(1, $h['total']['holiday']);

        // institute: no institute choice needed, always its own; today by default
        $this->actingAs($this->a);
        $this->get('/attendance-daily-report?institute_id=' . $this->b->id . '&date=' . $day)
            ->assertOk()->assertSee('Office Staff')->assertSee('Alpha High School')->assertDontSee('Beta College');
        $this->get('/attendance-daily-report')->assertOk()->assertSee(now()->format('d M Y'));
        $this->get('/attendance-daily-report?date=2099-01-01')->assertSessionHasErrors('date');
        $this->assertStringStartsWith('%PDF', $this->get('/attendance-daily-report?date=' . $day . '&export=pdf')->getContent());

        // super admin: nothing until an institute is chosen
        $this->actingAs($this->admin);
        $this->get('/attendance-daily-report')->assertOk()->assertSee('Select an institute')->assertDontSee('Employee Report');
        $this->get('/attendance-daily-report?institute_id=' . $this->b->id . '&date=' . $day)->assertOk()->assertSee('Beta College')->assertDontSee('Office Staff');

        // institute user needs the menu permission
        $clerk = $this->institute('Clerk', 'clerk4@test.local');
        $clerk->forceFill(['role' => 'inst_user', 'institute_id' => $this->a->id, 'permissions' => ['teachers']])->save();
        $this->actingAs($clerk);
        $this->get('/attendance-daily-report')->assertRedirect(route('permission-denied'));
    }

    public function test_an_institute_never_shows_the_super_admin_logo(): void
    {
        // the institutes in this test have no logo of their own
        $this->assertNull($this->a->logo);
        $this->assertNull(schoolInfo($this->a->id)->logo_url);
        $this->assertNull(schoolInfo($this->a->id)->logo_path);
        // the global letterhead (no institute) still uses Site Settings
        $site = siteSettings()->logo ?? null;
        $this->assertSame($site && is_file(public_path($site)) ? asset($site) : null, schoolInfo()->logo_url);

        // own logo when it has one
        $logo = 'assets/files/images/institutes/test-logo-' . uniqid() . '.png';
        @mkdir(public_path('assets/files/images/institutes'), 0777, true);
        file_put_contents(public_path($logo), base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        try {
            $this->a->forceFill(['logo' => $logo])->save();
            $this->assertSame(asset($logo), schoolInfo($this->a->id)->logo_url);

            // super admin with the institute selected: that institute's logo on the sheet and reports
            $this->actingAs($this->admin);
            $this->get('/attendance-logs?institute_id=' . $this->a->id)->assertSee(asset($logo), false);
            $this->get('/attendance-daily-report?institute_id=' . $this->a->id)->assertSee(asset($logo), false);
            $sheet = $this->get('/attendance-month-day-summary?institute_id=' . $this->a->id)->getContent();
            $this->assertStringContainsString(asset($logo), $sheet);
            if ($site) {
                $this->assertStringNotContainsString(asset($site), $sheet);
            }
        } finally {
            @unlink(public_path($logo));
        }
    }

    public function test_institute_users_and_menu_permissions(): void
    {
        // the institute creates a user with two menus; institute_id in the request is ignored
        $this->actingAs($this->a);
        $form = [
            'institute_id' => $this->b->id, 'name' => 'Office Clerk', 'email' => 'clerk@test.local', 'status' => 'active',
            'password' => 'abc123', 'password_confirmation' => 'abc123',
        ];
        $this->post('/institute-users', $form + ['permissions' => ['teachers', 'bogus']])->assertSessionHasErrors('permissions.1');
        $this->post('/institute-users', $form + ['permissions' => ['teachers', 'attendance.logs']])->assertRedirect(route('institute-users.index'));
        $clerk = User::where('email', 'clerk@test.local')->firstOrFail();
        $this->assertSame('inst_user', $clerk->role);
        $this->assertSame($this->a->id, (int) $clerk->institute_id);
        $this->assertSame(['attendance.logs', 'teachers'], $clerk->permissions);

        // another institute cannot see or edit it
        $this->actingAs($this->b);
        $this->get('/institute-users/' . $clerk->id . '/edit')->assertNotFound();

        // the clerk: own institute's data, only the permitted menus
        Auth::logout();
        $this->post('/admin-login', ['email' => 'clerk@test.local', 'password' => 'abc123']);
        $this->assertAuthenticatedAs($clerk);
        $this->assertSame($this->a->id, authInstituteId());
        $this->get('/dashboard')->assertRedirect(route('attendance.logs')); // no dashboard permission → first menu
        $this->get('/teachers')->assertOk()->assertSee('Teacher A')->assertDontSee('Teacher B')
            ->assertSee('Teachers/Staff')->assertDontSee('Departments')->assertDontSee('Institute Users');
        $this->get('/attendance-logs')->assertOk()->assertSee('Alpha High School');
        $this->get('/students')->assertRedirect(route('permission-denied'));
        $this->get('/teacher-profiles')->assertRedirect(route('permission-denied'));
        $this->get('/institute-users')->assertRedirect(route('permission-denied'));
        $this->get('/institutes')->assertRedirect(route('permission-denied'));
        $this->post('/device-push/student', ['scope' => 'all'])->assertRedirect(route('permission-denied'));
        $this->getJson('/attendance-board/data')->assertForbidden();
        $this->get('/profile')->assertOk();
        $this->assertSame(1, Teacher::count());

        // institute deactivated → the clerk is logged out and cannot log in again
        $this->a->update(['status' => 'inactive']);
        $this->get('/teachers')->assertRedirect(route('home'));
        $this->assertGuest();
        $this->post('/admin-login', ['email' => 'clerk@test.local', 'password' => 'abc123']);
        $this->assertGuest();

        // the super admin manages users of any institute; an institute with users is not deleted
        $this->a->update(['status' => 'active']);
        $this->actingAs($this->admin);
        $this->get('/institute-users')->assertOk()->assertSee('Office Clerk');
        $this->put('/institute-users/' . $clerk->id, ['permissions' => ['dashboard']] + $form + ['institute_id' => $this->a->id])
            ->assertRedirect(route('institute-users.index'));
        $this->assertSame(['dashboard'], $clerk->fresh()->permissions);
        $this->delete('/institutes/' . $this->a->id);
        $this->assertTrue(User::whereKey($this->a->id)->exists());
    }
}
