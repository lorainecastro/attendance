<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Student Attendance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            /* Primary Colors */
            --primary-blue: #2563eb;
            --primary-blue-hover: #1d4ed8;
            --primary-blue-light: #dbeafe;

            /* Status Colors */
            --success-green: #16a34a;
            --warning-yellow: #ca8a04;
            --danger-red: #dc2626;
            --info-cyan: #0891b2;

            /* Neutral Colors */
            --dark-gray: #374151;
            --medium-gray: #6b7280;
            --light-gray: #d1d5db;
            --background: #f9fafb;
            --white: #ffffff;
            --border-color: #e5e7eb;

            /* Typography */
            --font-family: 'Inter', sans-serif;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;

            /* Spacing */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;

            /* Layout */
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --header-height: 70px;

            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);

            /* Border Radius */
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;

            /* Transitions */
            --transition-fast: 0.15s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
            --transition-slow: 0.5s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, var(--primary-blue-light) 0%, var(--background) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--dark-gray);
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--white) 0%, rgba(37, 99, 235, 0.02) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: var(--header-height);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
            height: 100%;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            text-decoration: none;
            color: var(--primary-blue);
            font-weight: 700;
            font-size: var(--font-size-xl);
            transition: var(--transition-fast);
        }

        .logo i {
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--info-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo:hover {
            transform: translateY(-1px);
            color: var(--primary-blue-hover);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: var(--spacing-xl);
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark-gray);
            font-weight: 500;
            font-size: var(--font-size-base);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent);
            transition: var(--transition-normal);
        }

        .nav-links a:hover::before {
            left: 100%;
        }

        .nav-links a:hover {
            color: var(--primary-blue);
            background-color: var(--primary-blue-light);
            transform: translateY(-2px);
        }

        .nav-links a.active {
            color: var(--primary-blue);
            background-color: var(--primary-blue-light);
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        .auth-buttons {
            display: flex;
            gap: var(--spacing-md);
            align-items: center;
        }

        .btn {
            padding: var(--spacing-sm) var(--spacing-lg);
            border: none;
            border-radius: var(--radius-md);
            font-family: var(--font-family);
            font-size: var(--font-size-base);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition-normal);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: var(--transition-fast);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-blue);
            border: 1px solid var(--primary-blue);
        }

        .btn-outline:hover {
            background-color: var(--primary-blue);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-hover));
            color: var(--white);
            border: 1px solid transparent;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-blue-hover), var(--primary-blue));
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: var(--font-size-xl);
            color: var(--dark-gray);
            cursor: pointer;
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            transition: var(--transition-fast);
        }

        .mobile-menu-toggle:hover {
            color: var(--primary-blue);
            background-color: var(--primary-blue-light);
        }

        .mobile-menu {
            position: fixed;
            top: var(--header-height);
            left: -100%;
            width: 100%;
            height: calc(100vh - var(--header-height));
            background: var(--white);
            z-index: 999;
            transition: var(--transition-normal);
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }

        .mobile-menu.active {
            left: 0;
        }

        .mobile-nav-links {
            display: flex;
            flex-direction: column;
            padding: var(--spacing-xl);
            gap: var(--spacing-md);
        }

        .mobile-nav-links a {
            text-decoration: none;
            color: var(--dark-gray);
            font-weight: 500;
            font-size: var(--font-size-lg);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            transition: var(--transition-fast);
            border-left: 4px solid transparent;
        }

        .mobile-nav-links a:hover,
        .mobile-nav-links a.active {
            background-color: var(--primary-blue-light);
            color: var(--primary-blue);
            border-left-color: var(--primary-blue);
        }

        .mobile-auth-buttons {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
            padding: var(--spacing-xl);
            border-top: 1px solid var(--border-color);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-xl);
            width: 100%;
            max-width: 960px;
            margin: 0 auto;
        }

        .signup-container {
            width: 100%;
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
            display: flex;
            flex-direction: row;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .signup-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-hover) 100%);
            color: var(--white);
            padding: var(--spacing-xl);
            text-align: center;
            position: relative;
            overflow: hidden;
            flex: 0 0 30%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .signup-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.5;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        .signup-header h1 {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
            position: relative;
            z-index: 1;
        }

        .signup-header p {
            font-size: var(--font-size-base);
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .signup-form {
            padding: var(--spacing-2xl);
            flex: 1;
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-label {
            display: block;
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: var(--spacing-sm);
        }

        .form-input {
            width: 100%;
            padding: var(--spacing-md);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            font-family: var(--font-family);
            transition: var(--transition-normal);
            background: var(--white);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-input::placeholder {
            color: var(--medium-gray);
        }

        .input-icon {
            position: relative;
        }

        .input-icon::before {
            content: '';
            position: absolute;
            left: var(--spacing-md);
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            background-size: contain;
            background-repeat: no-repeat;
            z-index: 1;
        }

        .user-icon::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'/%3E%3C/svg%3E");
        }

        .institution-icon::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a2 2 0 012-2h2a2 2 0 012 2v5m-4 5h4'/%3E%3C/svg%3E");
        }

        .username-icon::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z'/%3E%3C/svg%3E");
        }

        .email-icon::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207'/%3E%3C/svg%3E");
        }

        .password-icon::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'/%3E%3C/svg%3E");
        }

        .confirm-password-icon::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'/%3E%3C/svg%3E");
        }

        .input-icon .form-input {
            padding-left: 3rem;
        }

        .form-row {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 200px;
        }

        .signup-btn {
            width: 100%;
            padding: var(--spacing-md);
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-hover) 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
            margin-top: var(--spacing-md);
        }

        .signup-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: var(--transition-normal);
        }

        .signup-btn:hover::before {
            left: 100%;
        }

        .signup-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .signup-btn:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: var(--spacing-xl) 0;
            position: relative;
            color: var(--medium-gray);
            font-size: var(--font-size-sm);
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-color);
        }

        .divider span {
            background: var(--white);
            padding: 0 var(--spacing-md);
        }

        .signin-link {
            margin-top: 20px;
            text-align: center;
            color: var(--medium-gray);
            font-size: var(--font-size-sm);
        }

        .signin-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition-fast);
        }

        .signin-link a:hover {
            color: var(--primary-blue-hover);
            text-decoration: underline;
        }

        .error-message {
            background: #fef2f2;
            color: var(--danger-red);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            margin-bottom: var(--spacing-lg);
            border: 1px solid #fecaca;
            display: none;
        }

        .success-message {
            background: #f0fdf4;
            color: var(--success-green);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            margin-bottom: var(--spacing-lg);
            border: 1px solid #bbf7d0;
            display: none;
        }

        .password-strength {
            font-size: var(--font-size-sm);
            margin-top: var(--spacing-xs);
        }

        .strength-weak {
            color: var(--danger-red);
        }

        .strength-medium {
            color: var(--warning-yellow);
        }

        .strength-strong {
            color: var(--success-green);
        }

        .password-match {
            font-size: var(--font-size-sm);
            margin-top: var(--spacing-xs);
        }

        .match-success {
            color: var(--success-green);
        }

        .match-error {
            color: var(--danger-red);
        }

        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, var(--dark-gray), #1f2937);
            color: var(--white);
            padding: var(--spacing-2xl) 0 var(--spacing-lg);
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--info-cyan), var(--success-green), var(--warning-yellow));
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-lg);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: var(--spacing-2xl);
            margin-bottom: var(--spacing-2xl);
        }

        .footer-brand {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--white);
            text-decoration: none;
            margin-bottom: var(--spacing-md);
        }

        .footer-logo i {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--info-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .footer-description {
            line-height: 1.8;
            color: #d1d5db;
            margin-bottom: var(--spacing-lg);
        }

        .social-links {
            display: flex;
            gap: var(--spacing-md);
        }

        .social-link {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary-blue), var(--info-cyan));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            text-decoration: none;
            transition: var(--transition-normal);
            font-size: 1.2rem;
        }

        .social-link:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .footer-section h4 {
            font-size: var(--font-size-lg);
            margin-bottom: var(--spacing-lg);
            color: var(--white);
            position: relative;
            padding-bottom: var(--spacing-sm);
        }

        .footer-section h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 30px;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-blue), var(--info-cyan));
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .footer-links a {
            color: #d1d5db;
            text-decoration: none;
            transition: var(--transition-fast);
            padding: var(--spacing-xs) 0;
            display: inline-block;
        }

        .footer-links a:hover {
            color: var(--primary-blue);
            transform: translateX(5px);
        }

        .footer-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #4b5563, transparent);
            margin: var(--spacing-xl) 0;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            padding-top: var(--spacing-lg);
            border-top: 1px solid #374151;
        }

        .footer-bottom p {
            color: #9ca3af;
            margin: 0;
        }

        .footer-bottom-links {
            display: flex;
            gap: var(--spacing-lg);
        }

        .footer-bottom-links a {
            color: #9ca3af;
            text-decoration: none;
            transition: var(--transition-fast);
        }

        .footer-bottom-links a:hover {
            color: var(--primary-blue);
        }

        /* Responsive Design */
        @media (max-width: 768px) {

            .nav-links,
            .auth-buttons {
                display: none;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .navbar {
                padding: 0 var(--spacing-md);
            }

            .main-content {
                padding: var(--spacing-md);
            }

            .signup-container {
                flex-direction: column;
                max-width: 480px;
            }

            .signup-header {
                flex: 0 0 auto;
                padding: var(--spacing-lg);
            }

            .signup-form {
                padding: var(--spacing-lg);
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-group {
                min-width: 100%;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-xl);
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .signup-header h1 {
                font-size: var(--font-size-xl);
            }

            .signup-header p {
                font-size: var(--font-size-sm);
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <nav class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>SAMS</span>
            </a>

            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#about">About</a></li>
                <li><a href="index.php#features">Features</a></li>
                <li><a href="index.php#contact">Contact</a></li>
            </ul>

            <div class="auth-buttons">
                <a href="sign-in.php" class="btn btn-outline">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </a>
                <a href="sign-up.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Sign Up
                </a>
            </div>

            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </nav>

        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-nav-links">
                <a href="index.php" onclick="closeMobileMenu()">Home</a>
                <a href="index.php#about" onclick="closeMobileMenu()">About</a>
                <a href="index.php#features" onclick="closeMobileMenu()">Features</a>
                <a href="index.php#contact" onclick="closeMobileMenu()">Contact</a>
            </div>
            <div class="mobile-auth-buttons">
                <a href="sign-in.php" class="btn btn-outline" onclick="closeMobileMenu()">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </a>
                <a href="sign-up.php" class="btn btn-primary" onclick="closeMobileMenu()">
                    <i class="fas fa-user-plus"></i>
                    Sign Up
                </a>
            </div>
        </div>
    </header>

    <div class="main-content">
        <div class="signup-container">
            <div class="signup-header">
                <h1>Create Account</h1>
                <p>Join the Student Attendance Monitoring System</p>
            </div>

            <div class="signup-form">
                <div id="errorMessage" class="error-message"></div>
                <div id="successMessage" class="success-message"></div>

                <form id="signupForm" action="sign-up.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstname" class="form-label">First Name</label>
                            <div class="input-icon user-icon">
                                <input
                                    type="text"
                                    id="firstname"
                                    name="firstname"
                                    class="form-input"
                                    placeholder="Enter your first name"
                                    required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="lastname" class="form-label">Last Name</label>
                            <div class="input-icon user-icon">
                                <input
                                    type="text"
                                    id="lastname"
                                    name="lastname"
                                    class="form-input"
                                    placeholder="Enter your last name"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="institution" class="form-label">Institution Name (Optional)</label>
                            <div class="input-icon institution-icon">
                                <input
                                    type="text"
                                    id="institution"
                                    name="institution"
                                    class="form-input"
                                    placeholder="Enter your institution name (Optional)"
                                    required>
                            </div>
                        </div>

                        <!-- <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-icon username-icon">
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    class="form-input"
                                    placeholder="Choose a username"
                                    required>
                            </div>
                        </div> -->

                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-icon email-icon">
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-input"
                                    placeholder="Enter your email address"
                                    required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-icon username-icon">
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    class="form-input"
                                    placeholder="Choose a username"
                                    required>
                            </div>
                        </div>

                        
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-icon password-icon">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-input"
                                    placeholder="Create a password"
                                    required>
                            </div>
                            <div id="passwordStrength" class="password-strength"></div>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <div class="input-icon confirm-password-icon">
                                <input
                                    type="password"
                                    id="confirmPassword"
                                    name="confirmPassword"
                                    class="form-input"
                                    placeholder="Confirm your password"
                                    required>
                            </div>
                            <div id="passwordMatch" class="password-match"></div>
                        </div>
                    </div>

                    <button type="submit" class="signup-btn">
                        Create Account
                    </button>
                </form>

                <div class="signin-link">
                    Already have an account? <a href="sign-in.php">Sign in here</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="#" class="footer-logo">
                        <i class="fas fa-graduation-cap"></i>
                        <span>SAMS</span>
                    </a>
                    <p class="footer-description">
                        Revolutionizing attendance monitoring through advanced AI and data analytics.
                        Empowering educational institutions to support student success and reduce absenteeism
                        with intelligent, data-driven solutions.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Solutions</h4>
                    <ul class="footer-links">
                        <li><a href="#">Time Series Forecasting</a></li>
                        <li><a href="#">Regression Analysis</a></li>
                        <li><a href="#">Real-time Analytics</a></li>
                        <li><a href="#">Predictive Insights</a></li>
                        <li><a href="#">Custom Reports</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Resources</h4>
                    <ul class="footer-links">
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">API Reference</a></li>
                        <li><a href="#">Support Center</a></li>
                        <li><a href="#">System Status</a></li>
                        <li><a href="#">Training Materials</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-divider"></div>

            <div class="footer-bottom">
                <p>&copy; 2025 SAMS - Student Attendance Monitoring System. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle functionality
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const toggleBtn = document.querySelector('.mobile-menu-toggle i');

            mobileMenu.classList.toggle('active');

            if (mobileMenu.classList.contains('active')) {
                toggleBtn.classList.remove('fa-bars');
                toggleBtn.classList.add('fa-times');
            } else {
                toggleBtn.classList.remove('fa-times');
                toggleBtn.classList.add('fa-bars');
            }
        }

        function closeMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const toggleBtn = document.querySelector('.mobile-menu-toggle i');

            mobileMenu.classList.remove('active');
            toggleBtn.classList.remove('fa-times');
            toggleBtn.classList.add('fa-bars');
        }

        // Navigation handling
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop() || 'index.php';
            const navLinks = document.querySelectorAll('.nav-links a, .mobile-nav-links a');

            navLinks.forEach(link => {
                const href = link.getAttribute('href');

                if (currentPage === 'index.php' && href === 'index.php') {
                    link.classList.add('active');
                } else if (href.includes(currentPage) && !href.includes('#')) {
                    link.classList.add('active');
                }
            });
        });

        // Enhanced navigation for hash links
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-links a, .mobile-nav-links a');

            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');

                    if (href.includes('#') && href.startsWith('index.php#')) {
                        const currentPage = window.location.pathname.split('/').pop() || 'index.php';

                        if (currentPage === 'index.php') {
                            e.preventDefault();
                            const sectionId = href.split('#')[1];
                            const target = document.getElementById(sectionId);

                            if (target) {
                                target.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });
                            }
                        }
                    }

                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');

                    closeMobileMenu();
                });
            });
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.style.boxShadow = 'var(--shadow-lg)';
                header.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
            } else {
                header.style.boxShadow = 'var(--shadow-md)';
                header.style.backgroundColor = '';
            }
        });

        // Handle hash navigation on load
        window.addEventListener('load', function() {
            if (window.location.hash) {
                const target = document.querySelector(window.location.hash);
                if (target) {
                    setTimeout(() => {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                }
            }
        });

        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthElement = document.getElementById('passwordStrength');
            let strength = 0;
            let feedback = '';

            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            switch (strength) {
                case 0:
                case 1:
                case 2:
                    feedback = 'Weak password';
                    strengthElement.className = 'password-strength strength-weak';
                    break;
                case 3:
                case 4:
                    feedback = 'Medium password';
                    strengthElement.className = 'password-strength strength-medium';
                    break;
                case 5:
                    feedback = 'Strong password';
                    strengthElement.className = 'password-strength strength-strong';
                    break;
            }

            strengthElement.textContent = password ? feedback : '';
            return strength;
        }

        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const matchElement = document.getElementById('passwordMatch');

            if (confirmPassword === '') {
                matchElement.textContent = '';
                return true;
            }

            if (password === confirmPassword) {
                matchElement.textContent = 'Passwords match';
                matchElement.className = 'password-match match-success';
                return true;
            } else {
                matchElement.textContent = 'Passwords do not match';
                matchElement.className = 'password-match match-error';
                return false;
            }
        }

        // Form validation and submission
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const firstname = document.getElementById('firstname').value.trim();
            const lastname = document.getElementById('lastname').value.trim();
            const institution = document.getElementById('institution').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');

            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';

            if (!firstname || !lastname || !institution || !username || !email || !password || !confirmPassword) {
                showError('Please fill in all required fields.');
                return;
            }

            if (firstname.length < 2) {
                showError('First name must be at least 2 characters long.');
                return;
            }

            if (lastname.length < 2) {
                showError('Last name must be at least 2 characters long.');
                return;
            }

            if (institution.length < 3) {
                showError('Institution name must be at least 3 characters long.');
                return;
            }

            if (username.length < 3) {
                showError('Username must be at least 3 characters long.');
                return;
            }

            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                showError('Username can only contain letters, numbers, and underscores.');
                return;
            }

            if (!isValidEmail(email)) {
                showError('Please enter a valid email address.');
                return;
            }

            if (password.length < 8) {
                showError('Password must be at least 8 characters long.');
                return;
            }

            if (checkPasswordStrength(password) < 3) {
                showError('Password is too weak. Please use a stronger password.');
                return;
            }

            if (password !== confirmPassword) {
                showError('Passwords do not match.');
                return;
            }

            showSuccess('Creating account...');

            setTimeout(() => {
                showSuccess('Account created successfully! Please check your email for verification.');

                setTimeout(() => {
                    showSuccess('Ready to redirect to sign in page!');
                }, 2000);
            }, 1000);
        });

        function showError(message) {
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';

            errorMessage.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function showSuccess(message) {
            const successMessage = document.getElementById('successMessage');
            successMessage.textContent = message;
            successMessage.style.display = 'block';
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });

        document.getElementById('confirmPassword').addEventListener('input', function() {
            checkPasswordMatch();
        });
    </script>
</body>

</html>