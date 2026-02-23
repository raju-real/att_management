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
            <a class="navbar-brand" href="{{ route('dashboard') }}" {!! tooltip(siteSettings()->site_name ?? 'Attendance Management') !!}>
                <i class="fas fa-chart-line"></i>
                {{ textLimit(siteSettings()->site_name ?? 'Attendance Management', 15) }}
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
                    {{-- Manage Attendance --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs(['attendance-summery', 'present-logs']) ? 'active' : '' }}"
                            href="#" id="attendanceDropdown" role="button" data-toggle="dropdown">
                            <i class="fas fa-clock mr-1"></i> Manage Attendance
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item {{ request()->routeIs('attendance-summery') ? 'active' : '' }}"
                                href="{{ route('attendance-summery') }}">
                                <i class="fas fa-history mr-2"></i> Attendance Logs
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('present-logs') ? 'active' : '' }}"
                                href="{{ route('present-logs') }}">
                                <i class="fas fa-user-check mr-2"></i> Present Logs
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('month-wise-present-report') ? 'active' : '' }}"
                                href="{{ route('month-wise-present-report') }}">
                                <i class="fas fa-gift mr-2"></i> Month Wise Present
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('month-wise-user-summery') ? 'active' : '' }}"
                                href="{{ route('month-wise-user-summery') }}">
                                <i class="fas fa-list mr-2"></i>Month Wise User Summary
                            </a>
                        </div>
                    </li>

                    {{-- Configuration --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs(['students.*', 'teachers.*', 'devices.*']) ? 'active' : '' }}"
                            href="#" id="configDropdown" role="button" data-toggle="dropdown">
                            <i class="fas fa-cogs mr-1"></i> Configuration
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item {{ request()->routeIs('students.*') ? 'active' : '' }}"
                                href="{{ route('students.index') }}">
                                <i class="fas fa-user-graduate mr-2"></i> Students
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('teachers.*') ? 'active' : '' }}"
                                href="{{ route('teachers.index') }}">
                                <i class="fas fa-chalkboard-teacher mr-2"></i> Teachers
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('devices.*') ? 'active' : '' }}"
                                href="{{ route('devices.index') }}">
                                <i class="fas fa-fingerprint mr-2"></i> Devices
                            </a>
                        </div>
                    </li>

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
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item {{ request()->routeIs('profile') ? 'active' : '' }}"
                                href="{{ route('profile') }}">
                                <i class="fas fa-user mr-2"></i> Profile
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('site-settings') ? 'active' : '' }}"
                                href="{{ route('site-settings') }}">
                                <i class="fas fa-sliders-h mr-2"></i> Site Settings
                            </a>
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
                    {{ siteSettings()->site_name ?? 'Attendance Management' }}. All rights reserved.</span>
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
    @stack('js')

</body>

</html>
