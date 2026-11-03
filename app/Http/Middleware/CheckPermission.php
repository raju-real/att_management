<?php

namespace App\Http\Middleware;

use App\Support\Permissions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Institute users (role "inst_user") may only open pages of the menus they
 * were given (App\Support\Permissions). Every other role passes through.
 *
 *  - a route that is not in the permission list is refused (safe default)
 *  - the dashboard without permission → their first permitted page
 *  - institute inactive / deleted → logged out
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (! $user || ! $user->isInstUser()) {
            return $next($request);
        }

        $institute = $user->institute_id ? \App\Models\User::institutes()->find($user->institute_id) : null;
        if (! $institute || $institute->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('home')->with(dangerMessage('danger', 'Your institute account is inactive. Please contact the institute.'));
        }

        $route = $request->route();
        $name  = $route?->getName();
        if ($name === null || in_array($name, Permissions::ALWAYS, true)) {
            return $next($request);
        }

        // push/remove share one route for teachers and students
        $key = in_array($name, ['device-push', 'device-remove'], true) ? $name . ':' . $route->parameter('group') : $name;

        foreach (Permissions::forRoute($key) as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        // no dashboard permission: go to the first page they may open
        if ($name === 'dashboard') {
            foreach ((array) $user->permissions as $permission) {
                if ($landing = Permissions::landingRoute($permission)) {
                    return redirect()->route($landing);
                }
            }
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'You do not have permission for this page.'], 403)
            : redirect()->route('permission-denied');
    }
}
