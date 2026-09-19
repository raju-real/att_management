<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Services\ZkTecoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    protected ZkTecoService $zkService;

    public function __construct(ZkTecoService $zkService)
    {
        $this->zkService = $zkService;
    }

    public function index()
    {
        $teachers = Teacher::latest('teacher_no')->paginate(20);
        return view('teacher.teacher_list', compact('teachers'));
    }

    public function pushToDevice()
    {
        \App\Jobs\SyncTeachersToDeviceJob::dispatch();
        return redirect()->back()->with(successMessage('success', 'Pushing teachers to devices queued and running in background.'));
    }

    public function create()
    {
        $route = route('teachers.store');
        return view('teacher.teacher_add_edit', compact('route'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('teachers')->whereNull('deleted_at'),
            ],
            'email' => [
                'required',
                'email',
                'string',
                'max:50',
                Rule::unique('teachers')->whereNull('deleted_at'),
            ],
            'mobile' => [
                'required',
                'string',
                'max:50',
                Rule::unique('teachers')->whereNull('deleted_at'),
            ],
            'designation' => ['required', 'max:50'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $teacher              = new Teacher();
        $teacher->teacher_no  = Teacher::getTeacherNo();
        $teacher->name        = $request->name;
        $teacher->email       = $request->email;
        $teacher->mobile      = $request->mobile;
        $teacher->designation = $request->designation;

        if ($request->hasFile('image')) {
            $teacher->image = uploadImage($request->file('image'), 'teachers');
        }

        $teacher->save();
        return redirect()->route('teachers.index')->with(successMessage());
    }

    public function edit($teacher_no)
    {
        $teacher = Teacher::whereTeacherNo($teacher_no)->first();
        $route   = route('teachers.update', $teacher->id);
        return view('teacher.teacher_add_edit', compact('teacher', 'route'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('teachers')->whereNull('deleted_at')->ignore($id),
            ],
            'email' => [
                'required',
                'email',
                'string',
                'max:50',
                Rule::unique('teachers')->whereNull('deleted_at')->ignore($id),
            ],
            'mobile' => [
                'required',
                'string',
                'max:50',
                Rule::unique('teachers')->whereNull('deleted_at')->ignore($id),
            ],
            'designation' => ['required', 'max:50'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $teacher              = Teacher::findOrFail($id);
        $teacher->name        = $request->name;
        $teacher->email       = $request->email;
        $teacher->mobile      = $request->mobile;
        $teacher->designation = $request->designation;

        // Replace image if new one uploaded
        if ($request->hasFile('image')) {
            if ($teacher->image !== null && file_exists($teacher->image)) {
                unlink($teacher->image);
            }
            $teacher->image = uploadImage($request->file('image'), 'teachers');
        }

        // Remove image if checkbox checked
        if ($request->has('remove_image') && $request->remove_image == '1') {
            if ($teacher->image !== null && file_exists($teacher->image)) {
                unlink($teacher->image);
            }
            $teacher->image = null;
        }

        $teacher->save();
        return redirect()->route('teachers.index')->with(infoMessage());
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        if ($teacher->image !== null && file_exists($teacher->image)) {
            unlink($teacher->image);
        }
        $teacher->delete();
        return redirect()->route('teachers.index')->with(deleteMessage());
    }
}
