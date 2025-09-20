<?php
require 'config.php';
session_start();

require 'PHPMailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// reCAPTCHA configuration
define('RECAPTCHA_SITE_KEY', '6LciX5crAAAAAC2it-A4UJYDSDCp4wp8Hz5hE_N2');
define('RECAPTCHA_SECRET_KEY', '6LciX5crAAAAAJOSEAjZZCHgqESEl-aTnRLemz8N');

$notification = ['message' => '', 'type' => ''];
$firstname = $lastname = $institution = $username = $email = '';

function verifyRecaptcha($recaptcha_response) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $resultJson = json_decode($result);
    return $resultJson->success;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'verify_unverified_email') {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response = ['message' => 'Invalid email format', 'type' => 'error'];
        } else {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE email = ? AND isVerified = 0");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    $otp = sprintf("%06d", mt_rand(100000, 999999));
                    $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    $stmt = $pdo->prepare("
                        UPDATE teachers 
                        SET otp_code = ?, otp_purpose = 'EMAIL_VERIFICATION', otp_expires_at = ?, otp_is_used = 0, otp_created_at = NOW()
                        WHERE email = ? AND isVerified = 0
                    ");
                    $stmt->execute([$otp, $otp_expires, $email]);
                    $_SESSION['signup_email'] = $email;
                    $_SESSION['signup_teacher_id'] = $user['teacher_id'];

                    if (sendOtpEmail($email, $otp)) {
                        $response = ['message' => 'OTP sent successfully', 'type' => 'success'];
                    } else {
                        $response = ['message' => 'Failed to send OTP. Please try again.', 'type' => 'error'];
                    }
                } else {
                    $response = ['message' => 'No unverified account found with this email', 'type' => 'error'];
                }
            } catch (PDOException $e) {
                $response = ['message' => 'Database error: ' . $e->getMessage(), 'type' => 'error'];
            }
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        $firstname = htmlspecialchars(trim($_POST['firstname'] ?? ''), ENT_QUOTES, 'UTF-8');
        $lastname = htmlspecialchars(trim($_POST['lastname'] ?? ''), ENT_QUOTES, 'UTF-8');
        $institution = htmlspecialchars(trim($_POST['institution'] ?? ''), ENT_QUOTES, 'UTF-8');
        $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirmPassword'] ?? '';
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

        if (empty($firstname) || empty($lastname) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $notification = ['message' => 'Please fill in all required fields', 'type' => 'error'];
        } elseif (empty($recaptcha_response)) {
            $notification = ['message' => 'Please complete the reCAPTCHA verification', 'type' => 'error'];
        } elseif (!verifyRecaptcha($recaptcha_response)) {
            $notification = ['message' => 'reCAPTCHA verification failed. Please try again.', 'type' => 'error'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $notification = ['message' => 'Invalid email format', 'type' => 'error'];
        } elseif ($password !== $confirm_password) {
            $notification = ['message' => 'Passwords do not match', 'type' => 'error'];
        } elseif (strlen($password) < 8) {
            $notification = ['message' => 'Password must be at least 8 characters long', 'type' => 'error'];
        } else {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("SELECT teacher_id, isVerified FROM teachers WHERE email = ? OR username = ?");
                $stmt->execute([$email, $username]);
                $existing_user = $stmt->fetch();

                if ($existing_user) {
                    if ($existing_user['isVerified'] == 0 && $email == $email) {
                        $notification = ['message' => 'This email is already registered but not verified.', 'type' => 'unverified', 'email' => $email];
                    } else {
                        $notification = ['message' => 'Email or username already exists', 'type' => 'error'];
                    }
                } else {
                    $otp = sprintf("%06d", mt_rand(100000, 999999));
                    $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO teachers (firstname, lastname, institution, username, email, password, picture, otp_code, otp_purpose, otp_expires_at, otp_is_used, isActive, isVerified, created_at, otp_created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'no-icon.png', ?, 'EMAIL_VERIFICATION', ?, 0, 0, 0, CURRENT_TIMESTAMP, NOW())
                    ");
                    $stmt->execute([$firstname, $lastname, $institution, $username, $email, $hashed_password, $otp, $otp_expires]);
                    $_SESSION['signup_email'] = $email;
                    $_SESSION['signup_teacher_id'] = $pdo->lastInsertId();

                    if (sendOtpEmail($email, $otp)) {
                        header("Location: verify-email.php");
                        exit;
                    } else {
                        $notification = ['message' => 'Failed to send OTP. Please try again.', 'type' => 'error'];
                    }
                }
            } catch (PDOException $e) {
                $notification = ['message' => 'Database error: ' . $e->getMessage(), 'type' => 'error'];
            }
        }
    }
}

// Only render HTML if not handling an AJAX request
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Student Attendance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        /* Your existing CSS remains unchanged */
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
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --header-height: 70px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --transition-fast: 0.15s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
            --transition-slow: 0.5s ease-in-out;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, var(--primary-blue-light) 0%, var(--background) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--dark-gray);
            line-height: 1.6;
        }
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
            max-width: 1000px;
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
            display: flex;
            flex-direction: row;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
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
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
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
            padding: 2rem;
            flex: 1;
            width: 100%;
            overflow-y: auto;
            max-height: 700px;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-blue);
        }
        .signup-form::-webkit-scrollbar { width: 8px; }
        .signup-form::-webkit-scrollbar-track {
            background: var(--primary-blue);
            border-radius: 10px;
        }
        .signup-form::-webkit-scrollbar-thumb {
            background: var(--primary-blue);
            border-radius: 10px;
            transition: var(--transition);
        }
        .signup-form::-webkit-scrollbar-thumb:hover {
            background: var(--primary-blue);
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
            transition: var(--transition-normal);
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
        .input-icon .form-input { padding-left: 3rem; }
        .form-row { display: flex; gap: var(--spacing-md); flex-wrap: wrap; }
        .form-row .form-group { flex: 1; min-width: 200px; }
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
        .signup-btn:hover::before { left: 100%; }
        .signup-btn:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .signup-btn:active { transform: translateY(0); }
        .signup-btn.loading .spinner { display: inline-block; }
        .signup-btn.loading span { display: none; }
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
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
        .divider span { background: var(--white); padding: 0 var(--spacing-md); }
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
        .signin-link a:hover { color: var(--primary-blue-hover); text-decoration: underline; }
        .error-message {
            background: #fef2f2;
            color: var(--danger-red);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            margin-bottom: var(--spacing-lg);
            border: 1px solid #fecaca;
            display: <?php echo $notification['message'] && $notification['type'] !== 'unverified' ? 'block' : 'none'; ?>;
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
        .password-strength { font-size: var(--font-size-sm); margin-top: var(--spacing-xs); }
        .strength-weak { color: var(--danger-red); }
        .strength-medium { color: var(--warning-yellow); }
        .strength-strong { color: var(--success-green); }
        .password-match { font-size: var(--font-size-sm); margin-top: var(--spacing-xs); }
        .match-success { color: var(--success-green); }
        .match-error { color: var(--danger-red); }
        .recaptcha-container { margin: var(--spacing-lg) 0; display: flex; justify-content: center; }
        .terms-checkbox { display: flex; align-items: center; margin: var(--spacing-lg) 0; }
        .terms-checkbox input { margin-right: var(--spacing-sm); accent-color: var(--primary-blue); }
        .terms-checkbox label { font-size: var(--font-size-sm); color: var(--dark-gray); }
        .terms-checkbox a { color: var(--primary-blue); text-decoration: none; font-weight: 600; }
        .terms-checkbox a:hover { text-decoration: underline; }
        
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
            transition: var(--transition-normal);
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
        @media (max-width: 768px) {
            .nav-links, .auth-buttons { display: none; }
            .mobile-menu-toggle { display: block; }
            .navbar { padding: 0 var(--spacing-md); }
            .main-content { padding: var(--spacing-md); }
            .signup-container { flex-direction: column; max-width: 480px; }
            .signup-header { flex: 0 0 auto; padding: var(--spacing-lg); }
            .signup-form { padding: var(--spacing-lg); }
            .form-row { flex-direction: column; gap: 0; }
            .form-group { min-width: 100%; }
            .footer-grid { grid-template-columns: 1fr; gap: var(--spacing-xl); }
            .footer-bottom { flex-direction: column; text-align: center; }
        }
        @media (max-width: 480px) {
            .signup-header h1 { font-size: var(--font-size-xl); }
            .signup-header p { font-size: var(--font-size-sm); }
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
            max-width: 600px;
            width: 90%;
            text-align: left;
            max-height: 80vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-blue) var(--light-gray);
        }
        .modal-content::-webkit-scrollbar { width: 8px; }
        .modal-content::-webkit-scrollbar-track {
            background: var(--light-gray);
            border-radius: 10px;
        }
        .modal-content::-webkit-scrollbar-thumb {
            background: var(--primary-blue);
            border-radius: 10px;
        }
        .modal-content h1 {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            margin-bottom: var(--spacing-lg);
            color: var(--primary-blue);
        }
        .modal-content h2 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            margin: var(--spacing-lg) 0 var(--spacing-md);
        }
        .modal-content p, .modal-content li {
            font-size: var(--font-size-base);
            margin-bottom: var(--spacing-md);
        }
        .modal-content ul {
            list-style: disc;
            margin-left: var(--spacing-xl);
            margin-bottom: var(--spacing-md);
        }
        .modal-buttons { display: flex; justify-content: center; gap: var(--spacing-md); }
        .modal-btn {
            padding: var(--spacing-sm) var(--spacing-lg);
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition-normal);
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
        @media (max-width: 768px) {
            .nav-links, .auth-buttons { display: none; }
            .mobile-menu-toggle { display: block; }
            .navbar { padding: 0 var(--spacing-md); }
            .main-content { padding: var(--spacing-md); }
            .signup-container { flex-direction: column; max-width: 480px; }
            .signup-header { flex: 0 0 auto; padding: var(--spacing-lg); }
            .signup-form { padding: var(--spacing-lg); }
            .form-row { flex-direction: column; gap: 0; }
            .form-group { min-width: 100%; }
            .footer-grid { grid-template-columns: 1fr; gap: var(--spacing-xl); }
            .footer-bottom { flex-direction: column; text-align: center; }
            .modal-content { max-width: 90%; padding: var(--spacing-lg); }
        }
        @media (max-width: 480px) {
            .signup-header h1 { font-size: var(--font-size-xl); }
            .signup-header p { font-size: var(--font-size-sm); }
            .modal-content h1 { font-size: var(--font-size-xl); }
        }
    </style>
</head>
<body>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput) { emailInput.focus(); }
        });
    </script>
    <?php include 'header.php'; ?>
    <div class="main-content">
        <div class="signup-container">
            <div class="signup-header">
                <h1>Create Account</h1>
                <p>Join the Student Attendance Monitoring System</p>
            </div>
            <div class="signup-form">
                <div id="errorMessage" class="error-message"><?php echo htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div id="successMessage" class="success-message"></div>
                <form id="signupForm" action="sign-up.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-icon email-icon">
                                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-icon username-icon">
                                <input type="text" id="username" name="username" class="form-input" placeholder="Choose a username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div id="usernameFeedback" class="password-match"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="institution" class="form-label">Institution Name</label>
                            <div class="input-icon institution-icon">
                                <input type="text" id="institution" name="institution" class="form-input" placeholder="Enter your institution name" value="<?php echo htmlspecialchars($institution, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstname" class="form-label">First Name</label>
                            <div class="input-icon user-icon">
                                <input type="text" id="firstname" name="firstname" class="form-input" placeholder="Enter your first name" value="<?php echo htmlspecialchars($firstname, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="lastname" class="form-label">Last Name</label>
                            <div class="input-icon user-icon">
                                <input type="text" id="lastname" name="lastname" class="form-input" placeholder="Enter your last name" value="<?php echo htmlspecialchars($lastname, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-icon password-icon">
                                <input type="password" id="password" name="password" class="form-input" placeholder="Create a password" required>
                            </div>
                            <div id="passwordStrength" class="password-strength"></div>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <div class="input-icon confirm-password-icon">
                                <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" placeholder="Confirm your password" required>
                            </div>
                            <div id="passwordMatch" class="password-match"></div>
                        </div>
                    </div>
                    <div class="recaptcha-container">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="#" onclick="openModal('termsModal'); return false;">Terms of Service</a> and <a href="#" onclick="openModal('privacyModal'); return false;">Privacy Policy</a></label>
                    </div>
                    <button type="submit" class="signup-btn">
                        <span>Create Account</span>
                        <div class="spinner"></div>
                    </button>
                </form>
                <div class="signin-link">
                    Already have an account? <a href="sign-in.php">Sign in here</a>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <!-- Terms of Service Modal -->
    <div id="termsModal" class="modal">
        <div class="modal-content">
            <h1>Terms of Service</h1>
            <p>Last updated: September 16, 2025</p>
            <h2>1. Acceptance of Terms</h2>
            <p>By accessing or using the Student Attendance Monitoring System (SAMS), you agree to be bound by these Terms of Service. If you do not agree, please do not use our services.</p>
            <h2>2. Use of Services</h2>
            <p>You agree to use SAMS only for lawful purposes and in a manner consistent with all applicable laws and regulations.</p>
            <ul>
                <li>You must provide accurate and complete information during registration.</li>
                <li>You are responsible for maintaining the confidentiality of your account credentials.</li>
                <li>You agree not to use SAMS to transmit any harmful or illegal content.</li>
            </ul>
            <h2>3. Account Responsibilities</h2>
            <p>You are responsible for all activities that occur under your account. Notify us immediately of any unauthorized use.</p>
            <h2>4. Termination</h2>
            <p>We reserve the right to suspend or terminate your account if you violate these terms.</p>
            <h2>5. Limitation of Liability</h2>
            <p>SAMS is provided "as is" without warranties of any kind. We are not liable for any damages arising from your use of the service.</p>
            <h2>6. Changes to Terms</h2>
            <p>We may update these Terms of Service from time to time. You will be notified of significant changes via email or through the platform.</p>
            <h2>7. Contact Us</h2>
            <p>If you have any questions, please contact us at <a href="mailto:support@sams.com">support@sams.com</a>.</p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal('termsModal')">Close</button>
            </div>
        </div>
    </div>
    <!-- Privacy Policy Modal -->
    <div id="privacyModal" class="modal">
        <div class="modal-content">
            <h1>Privacy Policy</h1>
            <p>Last updated: September 16, 2025</p>
            <h2>1. Information We Collect</h2>
            <p>We collect personal information you provide, such as your name, email, username, and institution details, to provide and improve our services.</p>
            <h2>2. How We Use Your Information</h2>
            <p>Your information is used to:</p>
            <ul>
                <li>Manage your account and provide access to SAMS.</li>
                <li>Send you OTPs and other account-related communications.</li>
                <li>Improve our services and ensure security.</li>
            </ul>
            <h2>3. Data Sharing</h2>
            <p>We do not sell your personal information. We may share it with service providers (e.g., email services) to operate SAMS, or as required by law.</p>
            <h2>4. Data Security</h2>
            <p>We use industry-standard measures to protect your data, but no method is 100% secure.</p>
            <h2>5. Your Rights</h2>
            <p>You have the right to access, correct, or delete your personal information. Contact us at <a href="mailto:support@sams.com">support@sams.com</a> to exercise these rights.</p>
            <h2>6. Cookies</h2>
            <p>We use cookies to enhance your experience. You can manage cookie preferences in your browser settings.</p>
            <h2>7. Changes to This Policy</h2>
            <p>We may update this Privacy Policy. Significant changes will be communicated via email or through the platform.</p>
            <h2>8. Contact Us</h2>
            <p>For questions, contact us at <a href="mailto:support@sams.com">support@sams.com</a>.</p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal('privacyModal')">Close</button>
            </div>
        </div>
    </div>
    <!-- Existing Unverified Email Modal -->
    <div id="unverifiedModal" class="modal">
        <div class="modal-content">
            <p>This email is already registered but not verified. To continue, verify your account first.</p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal('unverifiedModal')">Cancel</button>
                <button class="modal-btn modal-btn-verify" onclick="verifyEmail()">Verify</button>
            </div>
        </div>
    </div>
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
        // Modal control functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
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
        const passwordInput = document.getElementById('password');
        const strengthElement = document.getElementById('passwordStrength');
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            strengthElement.textContent = '';
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            switch (strength) {
                case 0: case 1: case 2:
                    strengthElement.textContent = 'Weak password';
                    strengthElement.className = 'password-strength strength-weak';
                    break;
                case 3: case 4:
                    strengthElement.textContent = 'Medium password';
                    strengthElement.className = 'password-strength strength-medium';
                    break;
                case 5:
                    strengthElement.textContent = 'Strong password';
                    strengthElement.className = 'password-strength strength-strong';
                    break;
            }
            checkPasswordMatch();
        });
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const matchElement = document.getElementById('passwordMatch');
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            if (confirmPassword === '') {
                matchElement.textContent = '';
                return;
            }
            matchElement.textContent = password === confirmPassword ? 'Passwords match' : 'Passwords do not match';
            matchElement.className = password === confirmPassword ? 'password-match match-success' : 'password-match match-error';
        }
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        const usernameInput = document.getElementById('username');
        const usernameFeedback = document.getElementById('usernameFeedback');
        let usernameTimer;
        usernameInput.addEventListener('input', function() {
            clearTimeout(usernameTimer);
            usernameFeedback.textContent = '';
            if (this.value.trim().length >= 3) {
                usernameTimer = setTimeout(() => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'check-username.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            const response = JSON.parse(xhr.responseText);
                            usernameFeedback.textContent = response.message;
                            usernameFeedback.className = response.status === 'error' ? 'password-match match-error' : 'password-match match-success';
                        }
                    };
                    xhr.send('username=' + encodeURIComponent(this.value.trim()));
                }, 800);
            }
        });
        const emailInput = document.getElementById('email');
        let emailTimer;
        emailInput.addEventListener('input', function() {
            clearTimeout(emailTimer);
            if (this.value.trim().length >= 3 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim())) {
                emailTimer = setTimeout(() => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'check-email.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            const response = JSON.parse(xhr.responseText);
                            if (response.status === 'unverified') {
                                document.getElementById('unverifiedModal').style.display = 'flex';
                                sessionStorage.setItem('signup_email', emailInput.value.trim());
                            }
                        }
                    };
                    xhr.send('email=' + encodeURIComponent(this.value.trim()));
                }, 800);
            }
        });
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.getElementById('email').value = '';
        }
        function verifyEmail() {
            const email = sessionStorage.getItem('signup_email');
            if (!email) {
                document.getElementById('errorMessage').textContent = 'No email found. Please try again.';
                document.getElementById('errorMessage').style.display = 'block';
                return;
            }
            const formData = new FormData();
            formData.append('action', 'verify_unverified_email');
            formData.append('email', email);
            fetch('sign-up.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.type === 'error') {
                    document.getElementById('errorMessage').textContent = data.message;
                    document.getElementById('errorMessage').style.display = 'block';
                    document.getElementById('unverifiedModal').style.display = 'none';
                    setTimeout(() => {
                        document.getElementById('errorMessage').style.display = 'none';
                    }, 5000);
                } else {
                    window.location.href = 'verify-email.php';
                }
            })
            .catch(error => {
                console.error('Verify email error:', error);
                document.getElementById('errorMessage').textContent = 'Failed to process verification request. Please try again.';
                document.getElementById('errorMessage').style.display = 'block';
                document.getElementById('unverifiedModal').style.display = 'none';
                setTimeout(() => {
                    document.getElementById('errorMessage').style.display = 'none';
                }, 5000);
            });
        }
        const form = document.getElementById('signupForm');
        const submitButton = form.querySelector('.signup-btn');
        const spinner = submitButton.querySelector('.spinner');
        const buttonText = submitButton.querySelector('span');
        form.addEventListener('submit', function(e) {
            const firstname = document.getElementById('firstname').value.trim();
            const lastname = document.getElementById('lastname').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const terms = document.getElementById('terms').checked;
            const recaptcha = grecaptcha.getResponse();
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.style.display = 'none';
            if (!firstname || !lastname || !username || !email || !password || !confirmPassword) {
                e.preventDefault();
                errorMessage.textContent = 'Please fill in all required fields.';
                errorMessage.style.display = 'block';
                return;
            }
            if (firstname.length < 2) {
                e.preventDefault();
                errorMessage.textContent = 'First name must be at least 2 characters long.';
                errorMessage.style.display = 'block';
                return;
            }
            if (lastname.length < 2) {
                e.preventDefault();
                errorMessage.textContent = 'Last name must be at least 2 characters long.';
                errorMessage.style.display = 'block';
                return;
            }
            if (username.length < 3) {
                e.preventDefault();
                errorMessage.textContent = 'Username must be at least 3 characters long.';
                errorMessage.style.display = 'block';
                return;
            }
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                e.preventDefault();
                errorMessage.textContent = 'Username can only contain letters, numbers, and underscores.';
                errorMessage.style.display = 'block';
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                e.preventDefault();
                errorMessage.textContent = 'Please enter a valid email address.';
                errorMessage.style.display = 'block';
                return;
            }
            if (password.length < 8) {
                e.preventDefault();
                errorMessage.textContent = 'Password must be at least 8 characters long.';
                errorMessage.style.display = 'block';
                return;
            }
            if (password !== confirmPassword) {
                e.preventDefault();
                errorMessage.textContent = 'Passwords do not match.';
                errorMessage.style.display = 'block';
                return;
            }
            if (!terms) {
                e.preventDefault();
                errorMessage.textContent = 'You must agree to the Terms of Service and Privacy Policy.';
                errorMessage.style.display = 'block';
                return;
            }
            if (!recaptcha) {
                e.preventDefault();
                errorMessage.textContent = 'Please complete the reCAPTCHA verification.';
                errorMessage.style.display = 'block';
                return;
            }
            submitButton.disabled = true;
            spinner.style.display = 'inline-block';
            buttonText.textContent = 'Creating Account...';
        });
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
        <?php if ($notification['message'] && $notification['type'] !== 'unverified') { ?>
            document.getElementById('errorMessage').style.display = 'block';
            setTimeout(() => {
                document.getElementById('errorMessage').style.display = 'none';
            }, 5000);
        <?php } ?>
        <?php if ($notification['type'] === 'unverified') { ?>
            document.getElementById('unverifiedModal').style.display = 'flex';
            sessionStorage.setItem('signup_email', '<?php echo htmlspecialchars($notification['email'], ENT_QUOTES, 'UTF-8'); ?>');
        <?php } ?>
    </script>
</body>
</html>