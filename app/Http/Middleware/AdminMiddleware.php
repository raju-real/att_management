<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Super-admin only pages (institutes, site settings, device add/edit). */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! isSuperAdmin()) {
            if ($request->expectsJson()) {
                abort(403, 'Only the super admin can do this.');
            }
            return redirect()->route('permission-denied');
        }
        return $next($request);
    }
}
