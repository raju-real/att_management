<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Super-admin only: institutes are rows in `users` with role = 'institute'.
 * An institute logs in with its email/password and only ever sees its own
 * teachers/staff, students, devices and attendance (InstituteScope).
 * Its name/address/EIIN/logo are used as the heading of its reports.
 */
class InstituteController extends Controller
{
    public function index(Request $request)
    {
        $institutes = User::institutes()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%' . trim($request->search) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $s)->orWhere('email', 'like', $s)
                    ->orWhere('mobile', 'like', $s)->orWhere('eiin', 'like', $s));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->withCount([
                'teachers'          => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at'),
                'students'          => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at'),
                'instituteDevices'  => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at'),
            ])
            ->orderBy('name')
            ->paginate(20);

        return view('institute.institute_list', compact('institutes'));
    }

    public function create()
    {
        $route = route('institutes.store');
        return view('institute.institute_add_edit', compact('route'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $institute = new User();
        $institute->role       = User::ROLE_INSTITUTE;
        $institute->created_by = authUser()->id;
        $this->fill($institute, $data, $request);
        $institute->save();

        return redirect()->route('institutes.index')->with(successMessage('success', "Institute \"{$institute->name}\" created. It can now log in with {$institute->email}."));
    }

    public function edit($id)
    {
        $institute = User::institutes()->findOrFail($id);
        $route     = route('institutes.update', $institute->id);
        return view('institute.institute_add_edit', compact('institute', 'route'));
    }

    public function update(Request $request, $id)
    {
        $institute = User::institutes()->findOrFail($id);
        $data      = $this->validated($request, $institute->id);

        $this->fill($institute, $data, $request);
        $institute->save();

        return redirect()->route('institutes.index')->with(infoMessage());
    }

    public function destroy($id)
    {
        $institute = User::institutes()->findOrFail($id);

        $used = Teacher::withoutGlobalScopes()->where('institute_id', $institute->id)->exists()
            || Student::withoutGlobalScopes()->where('institute_id', $institute->id)->exists()
            || Device::withoutGlobalScopes()->where('institute_id', $institute->id)->whereNull('deleted_at')->exists();

        if ($used) {
            return redirect()->route('institutes.index')->with(dangerMessage('danger',
                'Cannot delete: this institute still has teachers/staff, students or devices. Set it to Inactive instead (it can no longer log in).'));
        }

        $institute->deleted_by = authUser()->id;
        $institute->save();
        $institute->delete();

        return redirect()->route('institutes.index')->with(deleteMessage());
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        return $this->validate($request, [
            'name'     => ['required', 'string', 'max:191'],
            'email'    => ['required', 'email', 'max:100', Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($ignoreId)],
            'mobile'   => ['nullable', 'regex:/^[0-9]{6,11}$/', Rule::unique('users', 'mobile')->whereNull('deleted_at')->ignore($ignoreId)],
            'eiin'     => ['nullable', 'string', 'max:30'],
            'address'  => ['nullable', 'string', 'max:255'],
            'website'  => ['nullable', 'string', 'max:100'],
            'status'   => ['required', 'in:active,inactive'],
            'logo'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
            'password' => [$ignoreId ? 'nullable' : 'required', 'confirmed', Password::min(6)],
        ], [
            'mobile.regex' => 'Mobile must be 6 to 11 digits (e.g. 017XXXXXXXX).',
        ]);
    }

    protected function fill(User $institute, array $data, Request $request): void
    {
        $institute->name    = trim($data['name']);
        $institute->email   = strtolower(trim($data['email']));
        $institute->mobile  = $data['mobile'] ?? null;
        $institute->eiin    = $data['eiin'] ?? null;
        $institute->address = $data['address'] ?? null;
        $institute->website = $data['website'] ?? null;
        $institute->status  = $data['status'];

        if (! empty($data['password'])) {
            $institute->password       = Hash::make($data['password']);
            $institute->password_plain = ''; // never keep a readable copy
        } elseif (! $institute->exists) {
            $institute->password_plain = '';
        }

        $removeOld = fn () => $institute->logo && file_exists($institute->logo) ? @unlink($institute->logo) : null;
        if ($request->hasFile('logo')) {
            $removeOld();
            $institute->logo = uploadImage($request->file('logo'), 'institutes');
        } elseif ($request->boolean('remove_logo')) {
            $removeOld();
            $institute->logo = null;
        }
    }
}
