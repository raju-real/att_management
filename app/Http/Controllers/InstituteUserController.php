<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Institute users (role "inst_user"): extra logins an institute creates for
 * its staff. Each one works inside its institute only and sees only the
 * menus / submenus ticked here (App\Support\Permissions, enforced by the
 * CheckPermission middleware).
 *
 * Managed by the institute itself (its own users only) and by the super
 * admin (any institute). An inst_user can never manage users.
 */
class InstituteUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(isInstUser(), 403);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $users = $this->query()
            ->with('parentInstitute:id,name')
            ->when(isSuperAdmin() && selectedInstituteId(), fn ($q) => $q->where('institute_id', selectedInstituteId()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%' . trim($request->search) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $s)->orWhere('email', 'like', $s)->orWhere('mobile', 'like', $s));
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('institute_user.index', compact('users'));
    }

    public function create()
    {
        $route = route('institute-users.store');
        return view('institute_user.add_edit', compact('route'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $user = new User();
        $user->role       = User::ROLE_INST_USER;
        $user->created_by = authUser()->id;
        $this->fill($user, $data, $request);
        $user->save();

        return redirect()->route('institute-users.index')->with(successMessage('success', "User \"{$user->name}\" created. Login: {$user->email}"));
    }

    public function edit($id)
    {
        $user  = $this->query()->findOrFail($id);
        $route = route('institute-users.update', $user->id);
        return view('institute_user.add_edit', compact('user', 'route'));
    }

    public function update(Request $request, $id)
    {
        $user = $this->query()->findOrFail($id);
        $data = $this->validated($request, $user->id);

        $this->fill($user, $data, $request);
        $user->save();

        return redirect()->route('institute-users.index')->with(infoMessage());
    }

    public function destroy($id)
    {
        $user = $this->query()->findOrFail($id);
        $user->deleted_by = authUser()->id;
        $user->save();
        $user->delete();

        return redirect()->route('institute-users.index')->with(deleteMessage());
    }

    /** inst_users the logged-in account may manage. */
    protected function query()
    {
        return User::instituteUsers()->when(authInstituteId(), fn ($q, $id) => $q->where('institute_id', $id));
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        return $this->validate($request, [
            'institute_id'  => isSuperAdmin()
                ? ['required', Rule::exists('users', 'id')->where('role', 'institute')->whereNull('deleted_at')]
                : ['nullable'],
            'name'          => ['required', 'string', 'max:191'],
            'email'         => ['required', 'email', 'max:100', Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($ignoreId)],
            'mobile'        => ['nullable', 'regex:/^[0-9]{6,11}$/', Rule::unique('users', 'mobile')->whereNull('deleted_at')->ignore($ignoreId)],
            'status'        => ['required', 'in:active,inactive'],
            'password'      => [$ignoreId ? 'nullable' : 'required', 'confirmed', Password::min(6)],
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(Permissions::keys())],
        ], [
            'institute_id.required' => 'Select the institute this user belongs to.',
            'permissions.required'  => 'Tick at least one menu this user may open.',
            'mobile.regex'          => 'Mobile must be 6 to 11 digits (e.g. 017XXXXXXXX).',
        ]);
    }

    protected function fill(User $user, array $data, Request $request): void
    {
        // institute: always the logged-in institute; the admin chooses
        $user->institute_id = authInstituteId() ?? (int) $data['institute_id'];
        $user->name         = trim($data['name']);
        $user->email        = strtolower(trim($data['email']));
        $user->mobile       = $data['mobile'] ?? null;
        $user->status       = $data['status'];
        $user->permissions  = Permissions::clean($data['permissions']);

        if (! empty($data['password'])) {
            $user->password       = Hash::make($data['password']);
            $user->password_plain = ''; // never keep a readable copy
        } elseif (! $user->exists) {
            $user->password_plain = '';
        }
    }
}
