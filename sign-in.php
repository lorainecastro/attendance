<?php
session_start();
require 'config.php';
require 'PHPMailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Initialize notification and variables
$notification = ['message' => '', 'type' => ''];
$loginInput = '';
$rememberMe = false;

// Only redirect if not a POST request and session is valid
if ($_SERVER["REQUEST_METHOD"] !== "POST" && isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    header("Location: teacherDashboard.php");
    exit;
}

function sendOtpEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'student.attendance.monitoring.sys@gmail.com';
        $mail->Password = 'cajlpvkqvphqchro';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('student.attendance.monitoring.sys@gmail.com', 'SAMS');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your SAMS Account';
        $mail->Body = "
            <h2>Welcome to SAMS!</h2>
            <p>Your OTP for email verification is: <strong>$otp</strong></p>
            <p>This code is valid for 15 minutes. Please enter it on the verification page.</p>
            <p>If you did not request this, please ignore this email.</p>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $loginInput = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember']);

    // Handle real-time validation or resend OTP
    if ($action === 'check_login' || $action === 'resend_verification') {
        ob_start(); // Start output buffering
        // Sanitize input by trimming and escaping special characters
        $loginInput = trim($_POST['login'] ?? '');
        $loginInput = htmlspecialchars($loginInput, ENT_QUOTES, 'UTF-8');
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'username';

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                SELECT teacher_id, email, isActive, isVerified
                FROM teachers 
                WHERE $field = ?
            ");
            $stmt->execute([$loginInput]);
            $teacher = $stmt->fetch();

            if (!$teacher) {
                $response = ['message' => 'Account does not exist.', 'type' => 'error'];
            } elseif ($teacher['isVerified'] == 0) {
                $response = ['message' => 'Email not verified.', 'type' => 'unverified', 'email' => $teacher['email']];
                if ($action === 'resend_verification') {
                    $otp = sprintf("%06d", mt_rand(100000, 999999));
                    $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    $stmt = $pdo->prepare("
                        UPDATE teachers 
                        SET otp_code = ?, otp_purpose = 'EMAIL_VERIFICATION', otp_expires_at = ?, otp_is_used = 0, otp_created_at = NOW()
                        WHERE email = ? AND isVerified = 0
                    ");
                    $stmt->execute([$otp, $otp_expires, $teacher['email']]);
                    $_SESSION['signup_email'] = $teacher['email'];
                    $_SESSION['signup_teacher_id'] = $teacher['teacher_id'];

                    if (sendOtpEmail($teacher['email'], $otp)) {
                        $response = ['message' => 'OTP sent successfully', 'type' => 'success', 'redirect' => 'verify-email.php'];
                    } else {
                        $response = ['message' => 'Failed to send OTP. Please try again.', 'type' => 'error'];
                    }
                }
            } elseif ($teacher['isActive'] == 0) {
                $response = ['message' => 'Account is not active.', 'type' => 'error'];
            } else {
                $response = ['message' => 'Account is valid.', 'type' => 'success'];
            }
        } catch (PDOException $e) {
            error_log("Check login error: " . $e->getMessage()); // Log the error
            $response = ['message' => 'Database error occurred. Please try again.', 'type' => 'error'];
        }
        ob_clean(); // Clear any stray output
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Handle login submission
    if (empty($loginInput) || empty($password)) {
        $notification = ['message' => 'Please enter both username/email and password', 'type' => 'error'];
    } else {
        try {
            $pdo = getDBConnection();
            $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
            $field = $isEmail ? 'email' : 'username';

            $stmt = $pdo->prepare("
                SELECT teacher_id, username, email, password, isActive, isVerified
                FROM teachers 
                WHERE $field = ?
            ");
            $stmt->execute([$loginInput]);
            $teacher = $stmt->fetch();

            if (!$teacher) {
                $notification = ['message' => 'Account does not exist.', 'type' => 'error'];
            } elseif ($teacher['isVerified'] == 0) {
                $notification = ['message' => 'Email not verified.', 'type' => 'unverified', 'email' => $teacher['email']];
            } elseif ($teacher['isActive'] == 0) {
                $notification = ['message' => 'Account is not active.', 'type' => 'error'];
            } elseif (password_verify($password, $teacher['password'])) {
                // Login successful
                $sessionToken = createUserSession($teacher['teacher_id']);
                if ($sessionToken) {
                    $stmt = $pdo->prepare("UPDATE teachers SET created_at = NOW() WHERE teacher_id = ?");
                    $stmt->execute([$teacher['teacher_id']]);

                    $_SESSION['loggedin'] = true;
                    $_SESSION['teacher_id'] = $teacher['teacher_id'];
                    $_SESSION['username'] = $teacher['username'];
                    $_SESSION['session_token'] = $sessionToken;

                    if ($rememberMe) {
                        $cookieLifetime = 7 * 24 * 60 * 60; // 7 days
                        session_set_cookie_params($cookieLifetime);
                        session_regenerate_id(true);
                    }

                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        ob_clean();
                        echo json_encode([
                            'success' => true,
                            'message' => "Welcome back, {$teacher['username']}!",
                            'redirect' => 'teacherDashboard.php'
                        ]);
                        exit;
                    } else {
                        header("Location: teacherDashboard.php");
                        exit;
                    }
                } else {
                    $notification = ['message' => 'Failed to create session.', 'type' => 'error'];
                }
            } else {
                $notification = ['message' => 'Invalid username/email or password', 'type' => 'error'];
            }
        } catch (PDOException $e) {
            $notification = ['message' => 'An error occurred. Please try again.', 'type' => 'error'];
            error_log("Login error: " . $e->getMessage());
        }
    }

    // Return JSON response for AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => $notification['message'],
            'type' => $notification['type'],
            'email' => $notification['email'] ?? ''
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Student Attendance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-blue-hover: #1d4ed8;
            --primary-blue-light: #dbeafe;
            --success-green: #16a34a;
            --warning-yellow: #ca8a04;
            --danger-red: #dc2626;
            --info-cyan: #0891b2;
            --dark-gray: #374151;
            --medium-gray: #6b7280;
            --light-gray: #d1d5db;
            --background: #f9fafb;
            --white: #ffffff;
            --border-color: #e5e7eb;
            --font-family: 'Inter', sans-serif;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --transition: 0.3s ease-in-out;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, var(--primary-blue-light) 0%, var(--background) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: linear-gradient(135deg, var(--white) 0%, rgba(37, 99, 235, 0.02) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 70px;
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
            transition: var(--transition);
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
            transition: var(--transition);
        }
        .nav-links a:hover::before { left: 100%; }
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
            font-size: var(--font-size-base);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
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
            transition: var(--transition);
        }
        .btn:hover::before { left: 100%; }
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
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-hover) 100%);
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
            transition: var(--transition);
        }
        .mobile-menu-toggle:hover {
            color: var(--primary-blue);
            background-color: var(--primary-blue-light);
        }
        .mobile-menu {
            position: fixed;
            top: 70px;
            left: -100%;
            width: 100%;
            height: calc(100vh - 70px);
            background: var(--white);
            z-index: 999;
            transition: var(--transition);
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }
        .mobile-menu.active { left: 0; }
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
            transition: var(--transition);
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
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-xl);
        }
        .signin-container {
            width: 100%;
            max-width: 400px;
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .signin-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-hover) 100%);
            color: var(--white);
            padding: var(--spacing-xl);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .signin-header::before {
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
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        .signin-header h1 {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
            position: relative;
            z-index: 1;
        }
        .signin-header p {
            font-size: var(--font-size-base);
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        .signin-form {
            padding: var(--spacing-2xl);
        }
        .form-group { margin-bottom: var(--spacing-lg); }
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
            transition: var(--transition);
            background: var(--white);
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .form-input::placeholder { color: var(--medium-gray); }
        .input-icon { position: relative; }
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
        .login-icon::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207'/%3E%3C/svg%3E");
        }
        .password-icon::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'/%3E%3C/svg%3E");
        }
        .input-icon .form-input { padding-left: 3rem; }
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-xl);
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        .checkbox {
            width: 16px;
            height: 16px;
            accent-color: var(--primary-blue);
        }
        .checkbox-label {
            font-size: var(--font-size-sm);
            color: var(--dark-gray);
            cursor: pointer;
        }
        .forgot-password {
            color: var(--primary-blue);
            text-decoration: none;
            font-size: var(--font-size-sm);
            font-weight: 500;
            transition: var(--transition);
        }
        .forgot-password:hover {
            color: var(--primary-blue-hover);
            text-decoration: underline;
        }
        .signin-btn {
            width: 100%;
            padding: var(--spacing-md);
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-hover) 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        .signin-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: var(--transition);
        }
        .signin-btn:hover::before { left: 100%; }
        .signin-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .signin-btn:active { transform: translateY(0); }
        .signin-btn.loading {
            opacity: 0.8;
            pointer-events: none;
        }
        .signin-btn .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--white);
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        .signin-btn.loading .spinner { display: block; }
        .signin-btn.loading span { display: none; }
        .signup-link {
            margin-top: 20px;
            text-align: center;
            color: var(--medium-gray);
            font-size: var(--font-size-sm);
        }
        .signup-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        .signup-link a:hover {
            color: var(--primary-blue-hover);
            text-decoration: underline;
        }
        .notification {
            display: none;
            background: #fef2f2;
            color: var(--danger-red);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            margin-bottom: var(--spacing-lg);
            border: 1px solid #fecaca;
            text-align: center;
        }
        .notification.success {
            background: #f0fdf4;
            color: var(--success-green);
            border: 1px solid #bbf7d0;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: var(--white);
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .modal-content p {
            font-size: var(--font-size-base);
            color: var(--dark-gray);
            margin-bottom: var(--spacing-lg);
        }
        .modal-buttons { display: flex; justify-content: center; gap: var(--spacing-md); }
        .modal-btn {
            padding: var(--spacing-sm) var(--spacing-lg);
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        .modal-btn-cancel {
            background: transparent;
            color: var(--medium-gray);
            border: 1px solid var(--border-color);
        }
        .modal-btn-cancel:hover {
            background-color: var(--light-gray);
            transform: translateY(-2px);
        }
        .modal-btn-verify {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-hover) 100%);
            color: var(--white);
            border: none;
        }
        .modal-btn-verify:hover {
            background: linear-gradient(135deg, var(--primary-blue-hover), var(--primary-blue));
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        @media (max-width: 768px) {
            .nav-links, .auth-buttons { display: none; }
            .mobile-menu-toggle { display: block; }
            .navbar { padding: 0 var(--spacing-md); }
            .main-content { padding: var(--spacing-md); }
            .signin-form { padding: var(--spacing-lg); }
            .signin-header { padding: var(--spacing-lg); }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    <div class="main-content">
        <div class="signin-container">
            <div class="signin-header">
                <h1>Welcome Back</h1>
                <p>Student Attendance Monitoring System</p>
            </div>
            <div class="signin-form">
                <div id="notification" class="notification <?php echo $notification['type']; ?>" style="display: <?php echo $notification['message'] ? 'block' : 'none'; ?>">
                    <?php echo htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <form id="signinForm" method="POST" action="">
                    <div class="form-group">
                        <label for="login" class="form-label">Username or Email</label>
                        <div class="input-icon login-icon">
                            <input type="text" id="login" name="login" class="form-input" placeholder="Enter your username or email" value="<?php echo htmlspecialchars($loginInput, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-icon password-icon">
                            <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                        </div>
                    </div>
                    <div class="remember-forgot">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember" class="checkbox" <?php echo $rememberMe ? 'checked' : ''; ?>>
                            <label for="remember" class="checkbox-label">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
                    </div>
                    <button type="submit" class="signin-btn">
                        <span>Sign In</span>
                        <div class="spinner"></div>
                    </button>
                    <div class="signup-link">
                        Don't have an account? <a href="sign-up.php">Sign up here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="unverifiedModal" class="modal">
        <div class="modal-content">
            <p>Email Not Verified<br>Your account is registered, but your email hasn’t been verified yet.<br>Please verify your email to continue.</p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="modal-btn modal-btn-verify" onclick="verifyEmail()">Verify</button>
            </div>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <script>
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const toggleBtn = document.querySelector('.mobile-menu-toggle i');
            mobileMenu.classList.toggle('active');
            toggleBtn.classList.toggle('fa-bars');
            toggleBtn.classList.toggle('fa-times');
        }
        function closeMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const toggleBtn = document.querySelector('.mobile-menu-toggle i');
            mobileMenu.classList.remove('active');
            toggleBtn.classList.remove('fa-times');
            toggleBtn.classList.add('fa-bars');
        }
        function closeModal() {
            document.getElementById('unverifiedModal').style.display = 'none';
            document.getElementById('login').value = '';
            sessionStorage.removeItem('unverified_email');
        }
        function verifyEmail() {
            const email = sessionStorage.getItem('unverified_email');
            if (!email) {
                showNotification('No email found. Please try again.', 'error');
                return;
            }
            const formData = new FormData();
            formData.append('action', 'resend_verification');
            formData.append('login', email);
            fetch('', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.type === 'error') {
                    showNotification(data.message, 'error');
                    document.getElementById('unverifiedModal').style.display = 'none';
                } else {
                    window.location.href = data.redirect;
                }
            })
            .catch(error => {
                showNotification('Failed to process verification request. Please try again.', 'error');
                document.getElementById('unverifiedModal').style.display = 'none';
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            const signinForm = document.getElementById('signinForm');
            const signinBtn = document.querySelector('.signin-btn');
            const notification = document.getElementById('notification');
            const inputs = document.querySelectorAll('.form-input');
            const signinContainer = document.querySelector('.signin-container');
            const loginInput = document.getElementById('login');
            let loginTimer;

            document.getElementById('login').focus();
            function showNotification(message, type) {
                notification.textContent = message;
                notification.className = `notification ${type}`;
                notification.style.display = 'block';
                setTimeout(() => { notification.style.display = 'none'; }, 5000);
            }
            inputs.forEach(input => {
                input.addEventListener('focus', function() { this.parentElement.style.transform = 'translateY(-2px)'; });
                input.addEventListener('blur', function() { this.parentElement.style.transform = 'translateY(0)'; });
                if (input.value.length > 0) { input.classList.add('has-value'); }
                input.addEventListener('input', function() {
                    if (this.value.length > 0) { this.classList.add('has-value'); } else { this.classList.remove('has-value'); }
                });
            });
            loginInput.addEventListener('input', function() {
                clearTimeout(loginTimer);
                if (this.value.trim().length >= 3) {
                    loginTimer = setTimeout(() => {
                        const formData = new FormData();
                        formData.append('action', 'check_login');
                        formData.append('login', this.value.trim());
                        fetch('', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.type === 'unverified') {
                                document.getElementById('unverifiedModal').style.display = 'flex';
                                sessionStorage.setItem('unverified_email', data.email);
                            } else if (data.type === 'error') {
                                showNotification(data.message, 'error');
                            } else if (data.type === 'success') {
                                showNotification('Account is valid.', 'success'); // Optional: Show success message
                            }
                        })
                        .catch(error => {
                            console.error('Check login error:', error);
                            showNotification('Error checking login. Please try again.', 'error');
                        });
                    }, 800);
                }
            });
            signinForm.addEventListener('submit', function(e) {
                e.preventDefault();
                notification.style.display = 'none';
                signinBtn.classList.add('loading');
                const formData = new FormData(this);
                fetch('', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    signinBtn.classList.remove('loading');
                    if (data.type === 'unverified') {
                        document.getElementById('unverifiedModal').style.display = 'flex';
                        sessionStorage.setItem('unverified_email', data.email);
                    } else {
                        showNotification(data.message, data.success ? 'success' : 'error');
                        if (data.success) {
                            signinContainer.style.transform = 'scale(0.95)';
                            signinContainer.style.opacity = '0.8';
                            setTimeout(() => { window.location.href = data.redirect; }, 1500);
                        } else {
                            signinContainer.style.animation = 'shake 0.5s ease-in-out';
                            setTimeout(() => { signinContainer.style.animation = ''; }, 500);
                        }
                    }
                })
                .catch(error => {
                    signinBtn.classList.remove('loading');
                    showNotification('An error occurred. Please try again.', 'error');
                    console.error('Error:', error);
                });
            });
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
            document.querySelectorAll('.nav-links a, .mobile-nav-links a').forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href.includes('#') && href.startsWith('index.php#')) {
                        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
                        if (currentPage === 'index.php') {
                            e.preventDefault();
                            const sectionId = href.split('#')[1];
                            const target = document.getElementById(sectionId);
                            if (target) {
                                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                        }
                    }
                    document.querySelectorAll('.nav-links a, .mobile-nav-links a').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    closeMobileMenu();
                });
            });
            window.addEventListener('scroll', function() {
                const header = document.querySelector('.header');
                header.style.boxShadow = window.scrollY > 50 ? 'var(--shadow-lg)' : 'var(--shadow-md)';
                header.style.backgroundColor = window.scrollY > 50 ? 'rgba(255, 255, 255, 0.95)' : '';
            });
            if (window.location.hash) {
                const target = document.querySelector(window.location.hash);
                if (target) {
                    setTimeout(() => {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                }
            }
            inputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        signinForm.dispatchEvent(new Event('submit'));
                    }
                });
            });
            notification.addEventListener('click', function() { this.style.display = 'none'; });
            <?php if ($notification['type'] === 'unverified') { ?>
                document.getElementById('unverifiedModal').style.display = 'flex';
                sessionStorage.setItem('unverified_email', '<?php echo htmlspecialchars($notification['email'], ENT_QUOTES, 'UTF-8'); ?>');
            <?php } ?>
        });
    </script>
</body>
</html>