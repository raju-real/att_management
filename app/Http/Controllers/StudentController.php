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

    public function index()
    {
        $data = Student::query();
        $students = $data->latest('student_no')->paginate(50);
        return view('student.student_list', compact('students'));
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
