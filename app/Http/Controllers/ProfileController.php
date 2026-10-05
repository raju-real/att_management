<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function profile()
    {
        return view('edit_profile');
    }

    public function updateProfile(Request $request)
    {
        $id = authUser()->id;

        // Column sizes: users.email varchar(30), users.mobile varchar(11).
        $this->validate($request, [
            'name'   => ['required', 'string', 'max:50'],
            'email'  => ['required', 'email', 'max:30', Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($id)],
            'mobile' => ['required', 'regex:/^[0-9]{6,11}$/', Rule::unique('users', 'mobile')->whereNull('deleted_at')->ignore($id)],
            'image'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(6)->letters()->numbers()],
        ], [
            'mobile.regex'                    => 'Mobile must be 6 to 11 digits (e.g. 017XXXXXXXX).',
            'current_password.required_with'  => 'Enter your current password to set a new one.',
            'current_password.current_password' => 'The current password is incorrect.',
        ]);

        $admin = User::findOrFail($id);
        $admin->name   = $request->name;
        $admin->email  = $request->email;
        $admin->mobile = $request->mobile;

        if ($request->hasFile('image')) {
            if ($admin->image !== null && file_exists($admin->image)) {
                unlink($admin->image);
            }
            $admin->image = uploadImage($request->file('image'), 'admin');
        }
        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
            // Never keep a readable copy of the password.
            $admin->password_plain = '';
        }
        $admin->save();

        return redirect()->route('profile')->with(infoMessage('info', 'Profile updated successfully.'));
    }
}
