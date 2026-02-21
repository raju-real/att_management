<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Student;
use App\Services\ZkTecoService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    protected ZkTecoService $zkService;

    public function __construct(ZkTecoService $zkService)
    {
        $this->zkService = $zkService;
    }

    public function index(Request $request)
    {
        $data = Student::query();

        if ($request->name) {
            $data->where(function ($query) use ($request) {
                $query->where('firstname', 'like', "%{$request->name}%")
                    ->orWhere('middlename', 'like', "%{$request->name}%")
                    ->orWhere('lastname', 'like', "%{$request->name}%");
            });
        }

        if ($request->student_id) {
            $data->where('student_id', 'like', "%{$request->student_id}%");
        }

        if ($request->student_no) {
            $data->where('student_no', 'like', "%{$request->student_no}%");
        }

        if ($request->class) {
            $data->where('class', $request->class);
        }

        $students = $data->latest('student_no')->paginate(50);
        return view('student.student_list', compact('students'));
    }

    public function sync()
    {
        \Illuminate\Support\Facades\Artisan::queue('sync-student');
        return redirect()->back()->with(successMessage('success', 'Student sync started in background.'));
    }

    public function pushToDevice()
    {
        \App\Jobs\SyncStudentsToDeviceJob::dispatch();
        return redirect()->back()->with(successMessage('success', 'Pushing students to device started in background.'));
    }

    public function import()
    {
        return view('student.import');
    }

    public function upload(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:xls,xlsx,csv|max:2048'
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\StudentsImport, $request->file('file'));
            return redirect()->route('students.index')->with(successMessage('success', 'Students imported successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error importing file: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('student.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'student_id' => 'required|unique:students,student_id',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'class' => 'nullable|string|max:255',
            'section' => 'nullable|string|max:255',
            'roll' => 'nullable|string|max:255',
            'shift' => 'nullable|string|max:255',
            'medium' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
        ]);

        $student = new Student();
        $student->student_no = Student::getStudentNo(); // Auto Generate
        $student->student_id = $request->student_id;
        $student->firstname = $request->firstname;
        $student->middlename = $request->middlename;
        $student->lastname = $request->lastname;
        $student->nickname = $request->nickname;
        $student->class = $request->class;
        $student->section = $request->section;
        $student->roll = $request->roll;
        $student->shift = $request->shift;
        $student->medium = $request->medium;
        $student->group = $request->group;
        $student->save();

        return redirect()->route('students.index')->with(successMessage());
    }

    public function show($student_no)
    {
        $student = Student::where('student_no', $student_no)->firstOrFail();
        return view('student.show', compact('student'));
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        foreach (Device::active()->get() as $device) {
            $zk = $this->zkService->connect($device);
            if (!$zk) {
                continue;
            }

            //$userId = $student->student_no;
            //$uid = $this->zkService->findUidByUserId($zk, $userId);
            $uid = $student->student_no;
            if ($uid !== null) {
                $this->zkService->deleteUser($zk, $uid);
            }
            $this->zkService->disconnect($zk);
        }
        $student->delete();

        return redirect()
            ->route('students.index')
            ->with(deleteMessage());
    }
}
