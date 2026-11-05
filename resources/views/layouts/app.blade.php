<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" base_url="{!! url('/') !!}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel')</title>
    <link rel="shortcut icon" href="{{ asset('assets/common/images/favicon.png') }}">
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/sweetalert2.min.css') }}">
    {{-- Datetimepicker --}}
    <link rel="stylesheet" href="{{ asset('assets/common/datetimepicker/css/bootstrap-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/common/datetimepicker/css/tempusdominus-bootstrap-4.min.css') }}"
        crossorigin="anonymous" />
    {{-- Flat date time picker --}}
    <link rel="stylesheet" href="{{ asset('assets/common/flatpicker/flatpicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/common/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    @stack('css')
    <style>
        /* Dropdown on hover for desktop */
        @media (min-width: 992px) {
            .navbar .dropdown:hover .dropdown-menu {
                display: block;
                margin-top: 0;
                animation: fadeIn 0.3s ease;
            }
            .navbar .dropdown .dropdown-menu {
                display: none;
            }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Improve Topbar aesthetics */
        .navbar-admin {
            font-size: 15px;
            font-weight: 500;
        }
        .navbar-admin .brand-name {
            font-size: 15px;
            line-height: 1.2;
            white-space: normal;
            max-width: 420px;
            display: inline-block;
        }
        @media (max-width: 991px) {
            .navbar-admin .brand-name { font-size: 13px; max-width: 230px; }
        }
        .navbar-admin .nav-link {
            padding: 0.8rem 1.2rem !important;
            transition: all 0.3s ease;
        }
    </style>
</head>

<body>
    <!-- Animated background -->
    <div class="background-animation">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-admin">
        <div class="container-fluid">
            <!-- Logo/Brand on left -->
            {{-- full name (no cut), in a smaller font so long institute names fit --}}
            @php $brandName = authInstitute()->name ?? (siteSettings()->site_name ?? 'Attendance Management'); @endphp
            <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}" {!! tooltip(e($brandName)) !!}>
                <i class="fas fa-chart-line"></i>
                <span class="brand-name ml-1">{{ $brandName }}</span>
            </a>

            <!-- Mobile toggle button -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"><i class="fas fa-bars"></i></span>
            </button>

            <!-- Menu items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                {{--                menus from left side --}}
                <ul class="navbar-nav mr-auto">
                    {{--                <li class="nav-item"> --}}
                    {{--                    <a class="nav-link {{ segmentOne() == 'present-logs' ? 'active' : '' }}" --}}
                    {{--                       href="{{ route('present-logs') }}"> --}}
                    {{--                        <i class="fas fa-user-clock"></i> Present Logs --}}
                    {{--                    </a> --}}
                    {{--                </li> --}}

                </ul>
                {{-- menus from right side --}}
                <ul class="navbar-nav">
                    {{-- Attendance & Configuration: built from App\Support\Permissions::MENU,
                         so an institute user only sees the submenus it was given --}}
                    @php
                        $navGroups = ['Attendance' => 'fa-clock', 'Configuration' => 'fa-cogs'];
                        $navRoutes = fn ($item) => array_values(array_filter($item['routes'], fn ($r) => ! str_contains($r, ':')));
                    @endphp
                    @foreach ($navGroups as $group => $groupIcon)
                        @php
                            $navItems = collect(\App\Support\Permissions::MENU[$group])
                                ->filter(fn ($item, $key) => ($item['menu'] ?? true) && userCan($key));
                            $extra = $group === 'Configuration' && ! isInstUser(); // Institutes / Users links
                            $groupActive = $navItems->contains(fn ($item) => request()->routeIs($navRoutes($item)))
                                || ($group === 'Configuration' && request()->routeIs(['institutes.*', 'institute-users.*']));
                        @endphp
                        @if ($navItems->isNotEmpty() || $extra)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ $groupActive ? 'active' : '' }}" href="#" role="button" data-toggle="dropdown">
                                    <i class="fas {{ $groupIcon }} mr-1"></i> {{ $group }}
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    @if ($extra)
                                        @if (isSuperAdmin())
                                            <a class="dropdown-item {{ request()->routeIs('institutes.*') ? 'active' : '' }}" href="{{ route('institutes.index') }}">
                                                <i class="fas fa-university mr-2"></i> Institutes
                                            </a>
                                        @endif
                                        <a class="dropdown-item {{ request()->routeIs('institute-users.*') ? 'active' : '' }}" href="{{ route('institute-users.index') }}">
                                            <i class="fas fa-users-cog mr-2"></i> Institute Users
                                        </a>
                                        @if ($navItems->isNotEmpty())<div class="dropdown-divider"></div>@endif
                                    @endif
                                    @foreach ($navItems as $key => $item)
                                        @if ($landing = \App\Support\Permissions::landingRoute($key))
                                            <a class="dropdown-item {{ request()->routeIs($navRoutes($item)) ? 'active' : '' }}" href="{{ route($landing) }}">
                                                <i class="fas {{ $item['icon'] }} mr-2"></i> {{ $item['label'] }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </li>
                        @endif
                    @endforeach

                    {{-- Fees Management --}}
                    {{-- <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs(['fee-settings', 'update-fee-settings', 'fee-lots.*']) ? 'active' : '' }}"
                            href="#" id="feesDropdown" role="button" data-toggle="dropdown">
                            <i class="fas fa-money-bill-wave mr-1"></i> Fees Management
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item {{ request()->routeIs('fee-lots.*') ? 'active' : '' }}"
                                href="{{ route('fee-lots.index') }}">
                                <i class="fas fa-layer-group mr-2"></i> Fee Collect Lots
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('fee-settings') ? 'active' : '' }}"
                                href="{{ route('fee-settings') }}">
                                <i class="fas fa-cog mr-2"></i> Fee Settings
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('gateway-settings') ? 'active' : '' }}"
                                href="{{ route('gateway-settings') }}">
                                <i class="fas fa-cog mr-2"></i> Gateway Settings
                            </a>
                        </div>
                    </li> --}}
                </ul>

                <!-- User dropdown on right -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs(['profile', 'site-settings']) ? 'active' : '' }}"
                            href="#" id="userDropdown" role="button" data-toggle="dropdown">
                            @if (authUser()->image && file_exists(authUser()->image))
                                <img src="{{ asset(authUser()->image) }}" alt=""
                                    class="img-fluid rounded-circle img-30 mr-2">
                            @else
                                <i class="fas fa-user-circle mr-1"></i>
                            @endif
                            {{ textLimit(authUser()->name, 10) ?? 'Admin User' }}
                            <span class="badge badge-{{ isSuperAdmin() ? 'danger' : (isInstUser() ? 'secondary' : 'info') }} ml-1" style="font-size:.65em">{{ isSuperAdmin() ? 'Super Admin' : (isInstUser() ? 'Institute User' : 'Institute') }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item {{ request()->routeIs('profile') ? 'active' : '' }}"
                                href="{{ route('profile') }}">
                                <i class="fas fa-user mr-2"></i> Profile
                            </a>
                            @if (userCan('site-settings'))
                                <a class="dropdown-item {{ request()->routeIs('site-settings') ? 'active' : '' }}"
                                    href="{{ route('site-settings') }}">
                                    <i class="fas fa-sliders-h mr-2"></i> {{ isSuperAdmin() ? 'Site Settings' : 'Institute Settings' }}
                                </a>
                            @endif
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('logout') }}">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <x-alert-message />

        <div class="main-portion" style="min-height: 100vh">
            @yield('content')
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <span class="text-muted">© {{ date('Y') }}
                    {{ $brandName }}. All rights reserved.</span>
            </div>
        </footer>
    </div>

    <!-- jQuery -->
    <script src="{{ asset('assets/js/jquery.js') }}"></script>
    <!-- Popper.js -->
    <script src="{{ asset('assets/js/popper.min.js') }}"></script>
    <!-- Bootstrap JS -->
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
    {{-- Datetimepicker --}}
    <script src="{{ asset('assets/common/datetimepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('assets/common/datetimepicker/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/common/datetimepicker/js/moment-timezone-with-data.min.js') }}"></script>
    <script src="{{ asset('assets/common/datetimepicker/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    {{-- Flat date time picker --}}
    <script src="{{ asset('assets/common/flatpicker/flatpicker.min.js') }}"></script>
    <script src="{{ asset('assets/common/select2/js/select2.min.js') }}"></script>

    <script src="{{ asset('assets/common/datetimepicker/js/custom_picker.js') }}"></script>
    <script src="{{ asset('assets/js/custom.js') }}"></script>
    <script>
        // .fit-one-line: shrink the font until the text fits on one line (long institute names in headings).
        function fitOneLine() {
            document.querySelectorAll('.fit-one-line').forEach(function (el) {
                el.style.whiteSpace = 'nowrap';
                el.style.overflow = 'hidden';
                if (!el.dataset.maxFont) el.dataset.maxFont = parseFloat(getComputedStyle(el).fontSize);
                var size = +el.dataset.maxFont;
                el.style.fontSize = size + 'px';
                while (el.scrollWidth > el.clientWidth + 1 && size > 10) {
                    size -= 0.5;
                    el.style.fontSize = size + 'px';
                }
            });
        }
        window.fitOneLine = fitOneLine;
        window.addEventListener('load', fitOneLine);
        window.addEventListener('resize', fitOneLine);
        document.addEventListener('DOMContentLoaded', fitOneLine);

        // Institute dropdowns (partials/institute_select): searchable; locked for institute users.
        if ($.fn.select2) {
            $('.institute-select2').each(function () {
                $(this).select2({
                    width: '100%',
                    placeholder: $(this).data('placeholder') || 'All Institutes',
                    allowClear: $(this).data('allow-clear') == 1,
                });
            });
        }
    </script>
    @stack('js')

</body>

</html>
