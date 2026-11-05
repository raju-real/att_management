<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Menu permissions for institute users (role "inst_user").
 *
 * ONE list drives everything:
 *  - the checkboxes on the institute-user form (grouped by menu)
 *  - the navbar (a submenu is shown only when permitted)
 *  - CheckPermission middleware (every route an inst_user opens must belong
 *    to a permitted submenu; routes not listed here are refused)
 *
 * Super admin and the main institute account always have everything (the
 * super-admin-only pages are still protected by the "admin" middleware).
 *
 * Route keys may use * wildcards. The push/remove buttons share one route
 * for teachers and students, so they are listed as "device-push:teacher".
 */
class Permissions
{
    public const MENU = [
        'Dashboard' => [
            'dashboard'        => ['label' => 'Dashboard', 'icon' => 'fa-tv',
                'routes' => ['dashboard', 'attendance-board.data']],
            'attendance-board' => ['label' => 'Attendance Board (full page)', 'icon' => 'fa-crown',
                'routes' => ['attendance-board', 'attendance-board.data']],
        ],
        'Attendance' => [
            'attendance.logs'            => ['label' => 'Attendance Logs', 'icon' => 'fa-history',
                'routes' => ['attendance.logs', 'attendance.sync.background', 'present-logs', 'attendance-summery', 'month-wise-present-report']],
            'attendance.monthly-summary' => ['label' => 'Monthly Summary', 'icon' => 'fa-calendar-check',
                'routes' => ['attendance.monthly-summary', 'month-wise-user-summery']],
            'attendance.month-sheet'     => ['label' => 'Month & Day Wise Summary', 'icon' => 'fa-table',
                'routes' => ['attendance.month-sheet']],
            'teacher-profiles'           => ['label' => 'Teacher/Staff Profile', 'icon' => 'fa-id-card',
                'routes' => ['teacher-profiles.*']],
            'attendance.unmatched'       => ['label' => 'Unmatched Attendance', 'icon' => 'fa-question-circle', 'menu' => false,
                'routes' => ['attendance.unmatched']],
        ],
        'Configuration' => [
            'students'        => ['label' => 'Students', 'icon' => 'fa-user-graduate',
                'routes' => ['students.*', 'device-push:student', 'device-remove:student']],
            'teachers'        => ['label' => 'Teachers/Staff', 'icon' => 'fa-chalkboard-teacher',
                'routes' => ['teachers.*', 'device-push:teacher', 'device-remove:teacher']],
            'devices'         => ['label' => 'Devices', 'icon' => 'fa-fingerprint',
                'routes' => ['devices.*', 'commands.*']],
            'devices-monitor' => ['label' => 'Device Monitor', 'icon' => 'fa-satellite-dish',
                'routes' => ['devices-monitor', 'devices-monitor.data']],
            'shifts'          => ['label' => 'Shifts', 'icon' => 'fa-business-time',
                'routes' => ['shifts.*']],
            'departments'     => ['label' => 'Departments', 'icon' => 'fa-building',
                'routes' => ['departments.*']],
            // linked from the user dropdown, not this menu
            'site-settings'   => ['label' => 'Institute Settings', 'icon' => 'fa-sliders-h', 'menu' => false,
                'routes' => ['site-settings', 'update-site-settings']],
        ],
    ];

    /** Open to every logged-in user (own profile, logout...). */
    public const ALWAYS = ['profile', 'update-profile', 'permission-denied', 'logout', 'home'];

    /** @return string[] every permission key */
    public static function keys(): array
    {
        return array_keys(array_merge(...array_values(self::MENU)));
    }

    /** Only known keys, unique, in menu order. */
    public static function clean($keys): array
    {
        $keys = array_map('strval', (array) $keys);
        return array_values(array_filter(self::keys(), fn ($k) => in_array($k, $keys, true)));
    }

    public static function label(string $key): string
    {
        foreach (self::MENU as $items) {
            if (isset($items[$key])) {
                return $items[$key]['label'];
            }
        }
        return $key;
    }

    /** Permission keys that cover this route key (e.g. "teachers.edit"). */
    public static function forRoute(string $routeKey): array
    {
        $found = [];
        foreach (self::MENU as $items) {
            foreach ($items as $key => $item) {
                foreach ($item['routes'] as $pattern) {
                    if (Str::is($pattern, $routeKey)) {
                        $found[] = $key;
                        break;
                    }
                }
            }
        }
        return $found;
    }

    /** Landing page of a permission (its first plain route), or null. */
    public static function landingRoute(string $key): ?string
    {
        foreach (self::MENU as $items) {
            if (isset($items[$key])) {
                foreach ($items[$key]['routes'] as $r) {
                    $r = str_replace('.*', '.index', $r);
                    if (! str_contains($r, ':') && ! str_ends_with($r, '.data') && \Illuminate\Support\Facades\Route::has($r)) {
                        return $r;
                    }
                }
            }
        }
        return null;
    }
}
