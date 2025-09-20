<?php
require 'config.php'; // Your provided config file
session_start();

require 'PHPMailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$notification = ['message' => '', 'type' => ''];
$email = isset($_SESSION['signup_email']) ? $_SESSION['signup_email'] : '';

if (empty($email) || !isset($_SESSION['signup_teacher_id'])) {
    session_unset();
    session_destroy();
    header("Location: sign-up.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDBConnection();

    if (isset($_POST['action']) && $_POST['action'] === 'resend') {
        try {
            $otp = sprintf("%06d", mt_rand(100000, 999999));
            $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $stmt = $pdo->prepare("
                UPDATE teachers 
                SET otp_code = ?, otp_purpose = 'EMAIL_VERIFICATION', otp_expires_at = ?, otp_is_used = 0
                WHERE email = ? AND isVerified = 0
            ");
            $stmt->execute([$otp, $otp_expires, $email]);

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
                    <p>Your new OTP for email verification is: <strong>$otp</strong></p>
                    <p>This code is valid for 15 minutes. Please enter it on the verification page.</p>
                    <p>If you did not request this, please ignore this email.</p>
                ";

                $mail->send();
                $notification = ['message' => 'OTP resent successfully', 'type' => 'success'];
            } catch (Exception $e) {
                $notification = ['message' => 'Failed to resend OTP. Please try again.', 'type' => 'error'];
            }
        } catch (PDOException $e) {
            $notification = ['message' => 'Database error: ' . $e->getMessage(), 'type' => 'error'];
        }
    } else {
        $otp = filter_var($_POST['otp'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        if (empty($otp)) {
            $notification = ['message' => 'Please enter the OTP', 'type' => 'error'];
        } elseif (!preg_match('/^\d{6}$/', $otp)) {
            $notification = ['message' => 'OTP must be a 6-digit number', 'type' => 'error'];
        } else {
            try {
                $stmt = $pdo->prepare("
                    SELECT otp_code, otp_expires_at, otp_is_used 
                    FROM teachers 
                    WHERE teacher_id = ? AND otp_purpose = 'EMAIL_VERIFICATION'
                ");
                $stmt->execute([$_SESSION['signup_teacher_id']]);
                $user = $stmt->fetch();

                if (!$user) {
                    $notification = ['message' => 'Invalid or expired OTP', 'type' => 'error'];
                } elseif ($user['otp_is_used']) {
                    $notification = ['message' => 'OTP has already been used', 'type' => 'error'];
                } elseif (strtotime($user['otp_expires_at']) < time()) {
                    $notification = ['message' => 'OTP has expired. Please request a new one.', 'type' => 'error'];
                } elseif ($user['otp_code'] !== $otp) {
                    $notification = ['message' => 'Incorrect OTP', 'type' => 'error'];
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE teachers 
                        SET isVerified = 1, isActive = 1, otp_is_used = 1, otp_code = NULL, otp_expires_at = NULL
                        WHERE teacher_id = ?
                    ");
                    $stmt->execute([$_SESSION['signup_teacher_id']]);

                    unset($_SESSION['signup_email'], $_SESSION['signup_teacher_id']);
                    $notification = ['message' => 'Email verified successfully! Redirecting to login', 'type' => 'success'];
                }
            } catch (PDOException $e) {
                $notification = ['message' => 'Database error: ' . $e->getMessage(), 'type' => 'error'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAMS - Verify Email</title>
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
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }

        body {
            background: linear-gradient(135deg, var(--primary-blue-light) 0%, var(--background) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--dark-gray);
            line-height: 1.6;
            position: relative;
        }

        /* Header and Navbar styles (unchanged from original SAMS) */
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

        /* Verification Box (Adapted from LAB Jewels) */
        .verify-container {
            width: 100%;
            max-width: 500px;
            background: var(--white);
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            position: relative;
            z-index: 1;
            margin: 50px auto;
            padding: 40px;
        }

        .form-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-gray);
            text-align: center;
        }

        .form-description {
            font-size: 16px;
            color: var(--medium-gray);
            margin-bottom: 25px;
            text-align: center;
            line-height: 1.5;
        }

        #notification {
            display: <?php echo $notification['message'] ? 'block' : 'none'; ?>;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 16px;
            font-weight: 500;
            color: var(--white);
            background-color: <?php echo $notification['type'] === 'error' ? 'var(--danger-red)' : 'var(--success-green)'; ?>;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .input-group {
            margin-bottom: 18px;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--dark-gray);
            font-size: 15px;
            text-align: center;
        }

        .otp-input-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            background: rgba(37, 99, 235, 0.02);
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .otp-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }

        .otp-inputs input {
            width: 55px;
            height: 55px;
            padding: 10px;
            font-size: 20px;
            font-weight: 600;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            outline: none;
            transition: var(--transition);
            background-color: var(--background);
            text-align: center;
            letter-spacing: 0;
        }

        .otp-inputs input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background-color: var(--white);
        }

        .btn-verify {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-hover) 100%);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-verify::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: var(--transition);
            z-index: -1;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.35);
        }

        .btn-verify:hover::before {
            left: 100%;
            transition: 0.7s;
        }

        .btn-verify:active {
            transform: translateY(0);
        }

        .btn-verify.loading {
            opacity: 0.8;
            pointer-events: none;
        }

        .btn-verify .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--white);
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        .btn-verify.loading .spinner {
            display: block;
        }

        .btn-verify.loading span {
            display: none;
        }

        .resend-link {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
            color: var(--medium-gray);
        }

        .resend-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .resend-link a:hover {
            color: var(--primary-blue-hover);
            text-decoration: underline;
        }

        /* Footer styles (unchanged from original SAMS) */
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
            transition: var(--transition);
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
            transition: var(--transition);
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
            transition: var(--transition);
        }

        .footer-bottom-links a:hover {
            color: var(--primary-blue);
        }

        /* Animations from LAB Jewels */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes ripple {
            to { transform: scale(4); opacity: 0; }
        }

        .ripple {
            position: absolute;
            width: 100px;
            height: 100px;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .error-shake {
            animation: shake 0.5s cubic-bezier(.36, .07, .19, .97) both;
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

            .verify-container {
                max-width: 90%;
                margin: 20px auto;
                padding: 30px;
            }

            .otp-inputs input {
                width: 45px;
                height: 45px;
                font-size: 18px;
            }

            .otp-input-wrapper {
                padding: 10px;
            }

            .otp-inputs {
                gap: 6px;
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

        @media (max-width: 576px) {
            .verify-container {
                padding: 20px;
            }

            .form-title {
                font-size: 20px;
            }

            .form-description {
                font-size: 14px;
            }

            .input-group label {
                font-size: 14px;
            }

            .otp-inputs input {
                width: 40px;
                height: 40px;
                font-size: 16px;
                padding: 8px;
            }

            .otp-input-wrapper {
                padding: 8px;
            }

            .otp-inputs {
                gap: 5px;
            }

            .btn-verify {
                padding: 12px;
                font-size: 15px;
            }

            .resend-link {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .verify-container {
                max-width: 95%;
                border-radius: 16px;
                padding: 15px;
            }

            .form-title {
                font-size: 18px;
                margin-bottom: 10px;
            }

            .form-description {
                font-size: 13px;
                margin-bottom: 15px;
            }

            .input-group {
                margin-bottom: 12px;
            }

            .input-group label {
                font-size: 13px;
                margin-bottom: 6px;
            }

            .otp-inputs input {
                width: 35px;
                height: 35px;
                font-size: 14px;
                padding: 6px;
            }

            .otp-input-wrapper {
                padding: 6px;
            }

            .otp-inputs {
                gap: 4px;
            }

            .btn-verify {
                padding: 10px;
                font-size: 14px;
            }

            .resend-link {
                font-size: 13px;
                margin-top: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="verify-container">
        <h2 class="form-title">Verify Your Email</h2>
        <p class="form-description">
            We've sent a 6-digit OTP to <strong><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></strong>.
            Please enter it below to verify your account.
        </p>
        <div id="notification"><?php echo htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8'); ?></div>
        <form id="verifyForm" method="post" action="">
            <div class="input-group">
                <label for="otp1">Enter OTP</label>
                <div class="otp-input-wrapper">
                    <div class="otp-inputs">
                        <input type="text" id="otp1" name="otp1" maxlength="1" aria-label="OTP digit 1" required>
                        <input type="text" id="otp2" name="otp2" maxlength="1" aria-label="OTP digit 2" required>
                        <input type="text" id="otp3" name="otp3" maxlength="1" aria-label="OTP digit 3" required>
                        <input type="text" id="otp4" name="otp4" maxlength="1" aria-label="OTP digit 4" required>
                        <input type="text" id="otp5" name="otp5" maxlength="1" aria-label="OTP digit 5" required>
                        <input type="text" id="otp6" name="otp6" maxlength="1" aria-label="OTP digit 6" required>
                    </div>
                    <input type="hidden" id="otp" name="otp">
                </div>
            </div>
            <button type="submit" class="btn-verify">
                <span>Verify Email</span>
                <div class="spinner"></div>
            </button>
            <div class="resend-link">
                Didn't receive the OTP? <a href="#" id="resendOtp">Resend OTP</a>
            </div>
        </form>
    </div>

    <?php include('footer.php'); ?>

    <script>
        // Mobile menu toggle
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

        // Smooth scroll for hash links
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

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            header.style.boxShadow = window.scrollY > 50 ? 'var(--shadow-lg)' : 'var(--shadow-md)';
            header.style.backgroundColor = window.scrollY > 50 ? 'rgba(255, 255, 255, 0.95)' : '';
        });

        // Handle hash navigation on load
        window.addEventListener('load', function() {
            if (window.location.hash) {
                const target = document.querySelector(window.location.hash);
                if (target) {
                    setTimeout(() => {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                }
            }
        });

        // OTP input handling (from LAB Jewels)
        const otpInputs = document.querySelectorAll('.otp-inputs input:not([type=hidden])');
        const hiddenOtpInput = document.getElementById('otp');
        const form = document.getElementById('verifyForm');
        const notification = document.getElementById('notification');
        const submitButton = document.querySelector('.btn-verify');

        otpInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                const value = e.target.value;

                if (!/^\d$/.test(value) && value !== '') {
                    e.target.value = '';
                    return;
                }

                updateHiddenOTP();

                if (value && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }

                if (index === otpInputs.length - 1 && value && hiddenOtpInput.value.length === 6) {
                    submitButton.classList.add('loading');
                    form.submit();
                }
            });

            input.addEventListener('keydown', function(e) {
                const value = e.target.value;

                if (e.key === 'Backspace' && !value && index > 0) {
                    otpInputs[index - 1].focus();
                }

                if (e.key === 'v' && e.ctrlKey) {
                    navigator.clipboard.readText().then(text => {
                        if (/^\d{6}$/.test(text)) {
                            text.split('').forEach((digit, i) => {
                                if (i < otpInputs.length) {
                                    otpInputs[i].value = digit;
                                }
                            });
                            otpInputs[otpInputs.length - 1].focus();
                            updateHiddenOTP();
                            if (hiddenOtpInput.value.length === 6) {
                                submitButton.classList.add('loading');
                                form.submit();
                            }
                        }
                    });
                }
            });

            input.addEventListener('keypress', function(e) {
                if (!/^\d$/.test(e.key)) {
                    e.preventDefault();
                }
            });
        });

        function updateHiddenOTP() {
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            hiddenOtpInput.value = otp;
        }

        // Form submission with client-side validation
        form.addEventListener('submit', function(e) {
            const otp = hiddenOtpInput.value;

            if (!otp) {
                e.preventDefault();
                notification.textContent = 'Please enter the OTP';
                notification.style.backgroundColor = 'var(--danger-red)';
                notification.style.display = 'block';
                otpInputs.forEach(input => input.classList.add('error-shake'));
                setTimeout(() => {
                    otpInputs.forEach(input => input.classList.remove('error-shake'));
                    notification.style.display = 'none';
                }, 1000);
                return;
            }

            if (!/^\d{6}$/.test(otp)) {
                e.preventDefault();
                notification.textContent = 'OTP must be a 6-digit number';
                notification.style.backgroundColor = 'var(--danger-red)';
                notification.style.display = 'block';
                otpInputs.forEach(input => input.classList.add('error-shake'));
                setTimeout(() => {
                    otpInputs.forEach(input => input.classList.remove('error-shake'));
                    notification.style.display = 'none';
                }, 1000);
                return;
            }

            submitButton.classList.add('loading');
        });

        // Resend OTP via AJAX
        document.getElementById('resendOtp').addEventListener('click', function(e) {
            e.preventDefault();
            const resendLink = this;
            resendLink.style.pointerEvents = 'none';
            resendLink.textContent = 'Sending...';

            const formData = new FormData();
            formData.append('action', 'resend');
            formData.append('email', '<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const newNotification = doc.getElementById('notification');
                notification.textContent = newNotification.textContent;
                notification.style.backgroundColor = newNotification.style.backgroundColor;
                notification.style.display = 'block';
                setTimeout(() => { notification.style.display = 'none'; }, 5000);
                resendLink.style.pointerEvents = 'auto';
                resendLink.textContent = 'Resend OTP';
                otpInputs.forEach(input => input.value = '');
                hiddenOtpInput.value = '';
                otpInputs[0].focus();
            })
            .catch(error => {
                console.error('Resend OTP error:', error);
                notification.textContent = 'Failed to resend OTP. Please try again.';
                notification.style.backgroundColor = 'var(--danger-red)';
                notification.style.display = 'block';
                setTimeout(() => { notification.style.display = 'none'; }, 5000);
                resendLink.style.pointerEvents = 'auto';
                resendLink.textContent = 'Resend OTP';
            });
        });

        // Add ripple effect to verify button
        document.querySelector('.btn-verify').addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            this.appendChild(ripple);

            const x = e.clientX - e.target.getBoundingClientRect().left;
            const y = e.clientY - e.target.getBoundingClientRect().top;

            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;

            setTimeout(() => {
                ripple.remove();
            }, 600);
        });

        // Auto-focus first OTP input and handle success redirect
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('otp1').focus();
            const notification = document.getElementById('notification');
            if (notification.textContent === 'Email verified successfully! Redirecting to login') {
                setTimeout(function() {
                    window.location.href = 'sign-in.php';
                }, 3000);
            }
        });
    </script>
</body>
</html>