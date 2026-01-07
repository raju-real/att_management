<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Login</title>
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background */
        .background-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(78, 115, 223, 0.05);
            animation: float 15s infinite linear;
        }

        .bg-circle:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .bg-circle:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 20%;
            right: 10%;
            background: rgba(28, 200, 138, 0.05);
            animation-delay: 2s;
        }

        .bg-circle:nth-child(3) {
            width: 150px;
            height: 150px;
            bottom: 20%;
            left: 5%;
            background: rgba(246, 194, 62, 0.05);
            animation-delay: 4s;
        }

        .bg-circle:nth-child(4) {
            width: 90px;
            height: 90px;
            bottom: 10%;
            right: 15%;
            background: rgba(231, 74, 59, 0.05);
            animation-delay: 6s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        /* Login Container */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.2);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color), #2e59d9);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-logo {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .login-title {
            font-weight: 600;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-weight: 300;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .login-body {
            padding: 2rem;
        }

        /* Form styling */
        .form-group label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .input-group-text {
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-right: none;
            color: var(--secondary-color);
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group .form-control:focus {
            border-left: none;
        }

        /* Button styling */
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), #2e59d9);
            border: none;
            color: white;
            font-weight: 500;
            padding: 0.75rem;
            border-radius: 0.5rem;
            width: 100%;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #2e59d9, var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Remember me & Forgot password */
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .custom-checkbox .custom-control-input:checked ~ .custom-control-label::before {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background-color: #e3e6f0;
        }

        .divider span {
            padding: 0 1rem;
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        /* Social login buttons */
        .social-login {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .btn-social {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem;
            border-radius: 0.5rem;
            border: 1px solid #e3e6f0;
            background-color: white;
            color: var(--dark-color);
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-social:hover {
            background-color: #f8f9fc;
            transform: translateY(-2px);
        }

        .btn-social i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .btn-google {
            color: #db4437;
        }

        .btn-facebook {
            color: #4267B2;
        }

        /* Sign up link */
        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .signup-link a {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            padding: 1rem;
            color: var(--secondary-color);
            font-size: 0.85rem;
            border-top: 1px solid #f8f9fc;
            background-color: #f8f9fc;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .login-card {
                max-width: 100%;
            }

            .login-header {
                padding: 1.5rem;
            }

            .login-body {
                padding: 1.5rem;
            }

            .social-login {
                flex-direction: column;
            }
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

<!-- Login Container -->
<div class="login-container">
    <div class="login-card">
        <!-- Login Header -->
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-chart-line"></i>
            </div>
            <h1 class="login-title">AdminPanel</h1>
            <p class="login-subtitle">Sign in to access your dashboard</p>
        </div>

        <!-- Login Body -->
        <div class="login-body">
            <form action="{{ route('admin-login') }}" method="POST">
                @csrf
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email">Email {!! starSign() !!}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        </div>
                        <input
                            name="email"
                            type="email"
                            class="form-control {{ hasError('email') }}"
                            id="email"
                            placeholder="Enter your email"
                            value="{{ old('email') }}"
                            autocomplete="off"
                            autofocus
                        >
                    </div>
                    @error('email')
                        {!! displayError($message) !!}
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password">Password {!! starSign() !!}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input
                            name="password"
                            type="password"
                            class="form-control {!! hasError('password') !!}"
                            id="password"
                            placeholder="Enter your password"
                            autocomplete="off"
                        >
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    @error('password')
                        {!! displayError('password') !!}
                    @enderror
                </div>
                <!-- Remember Me & Forgot Password -->
                <div class="form-group form-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        id="rememberMe"
                        name="remember_me"
                        value="1"
                        {{ old('remember') ? 'checked' : '' }}
                    >
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                </button>

            </form>
        </div>

        <!-- Login Footer -->
        <div class="login-footer">
            © 2023 AdminPanel. All rights reserved.
        </div>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });


</script>
</body>
</html>
