<?php 
include('header.php')
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Student Attendance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        }

        /* Header Space */
        .header-space {
            height: var(--header-height);
            background: var(--white);
            box-shadow: var(--shadow-sm);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .header-placeholder {
            color: var(--medium-gray);
            font-size: var(--font-size-sm);
            font-weight: 500;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-xl);
        }

        .forgot-password-container {
            width: 100%;
            max-width: 400px;
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
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

        .forgot-password-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-hover) 100%);
            color: var(--white);
            padding: var(--spacing-xl);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .forgot-password-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .forgot-password-header h1 {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
            position: relative;
            z-index: 1;
        }

        .forgot-password-header p {
            font-size: var(--font-size-base);
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .forgot-password-form {
            padding: var(--spacing-2xl);
        }

        .info-section {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: var(--radius-md);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
            text-align: center;
        }

        .info-section .icon {
            width: 48px;
            height: 48px;
            margin: 0 auto var(--spacing-md);
            background: var(--info-cyan);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-section .icon::before {
            content: '';
            width: 24px;
            height: 24px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ffffff'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'/%3E%3C/svg%3E") center/contain no-repeat;
        }

        .info-section h3 {
            color: var(--info-cyan);
            font-size: var(--font-size-lg);
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
        }

        .info-section p {
            color: var(--dark-gray);
            font-size: var(--font-size-sm);
            line-height: 1.6;
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

        .email-icon::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207'/%3E%3C/svg%3E");
        }

        .input-icon .form-input {
            padding-left: 3rem;
        }

        .reset-btn {
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
        }

        .reset-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition-normal);
        }

        .reset-btn:hover::before {
            left: 100%;
        }

        .reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .reset-btn:active {
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

        .back-to-signin {
            text-align: center;
            color: var(--medium-gray);
            font-size: var(--font-size-sm);
        }

        .back-to-signin a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition-fast);
        }

        .back-to-signin a:hover {
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: var(--spacing-md);
            }
            
            .forgot-password-form {
                padding: var(--spacing-lg);
            }
            
            .forgot-password-header {
                padding: var(--spacing-lg);
            }
        }
    </style>
</head>
<body>
   
    <!-- Main Content -->
    <div class="main-content">
        <div class="forgot-password-container">
            <!-- Header -->
            <div class="forgot-password-header">
                <h1>Forgot Password</h1>
                <p>Student Attendance Enhancement System</p>
            </div>

            <!-- Form -->
            <div class="forgot-password-form">
                <!-- Info Section -->
                <div class="info-section">
                    <div class="icon"></div>
                    <h3>Reset Your Password</h3>
                    <p>Enter your email address and we'll send you a link to reset your password.</p>
                </div>

                <!-- Success/Error Messages -->
                <div id="errorMessage" class="error-message"></div>
                <div id="successMessage" class="success-message"></div>

                <form id="forgotPasswordForm" action="process-forgot-password.php" method="POST">
                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-icon email-icon">
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="Enter your email address"
                                required
                            >
                        </div>
                    </div>

                    <!-- Reset Button -->
                    <button type="submit" class="reset-btn">
                        Send Reset Link
                    </button>
                </form>

                <!-- Divider -->
                <div class="divider">
                    <span>or</span>
                </div>

                <!-- Back to Sign In Link -->
                <div class="back-to-signin">
                    Remember your password? <a href="sign-in.php">Sign in here</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form handling
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            
            // Hide previous messages
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
            
            // Basic validation
            if (!email) {
                errorMessage.textContent = 'Please enter your email address.';
                errorMessage.style.display = 'block';
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errorMessage.textContent = 'Please enter a valid email address.';
                errorMessage.style.display = 'block';
                return;
            }
            
            // Simulate form submission (replace with actual form submission)
            setTimeout(() => {
                successMessage.textContent = 'Password reset link has been sent to your email address.';
                successMessage.style.display = 'block';
                document.getElementById('email').value = '';
            }, 1000);
            
            // Uncomment the line below to actually submit the form
            // this.submit();
        });
    </script>

    <?php 
include('footer.php')
?>
</body>
</html>