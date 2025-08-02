<?php
ob_start(); // Start output buffering
require 'config.php';
session_start();

require 'PHPMailer/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$notification = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $notification = ['message' => 'Please enter your email address', 'type' => 'error'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $notification = ['message' => 'Invalid email format', 'type' => 'error'];
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $notification = ['message' => 'Email not found', 'type' => 'error'];
            } else {
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $stmt = $pdo->prepare("UPDATE teachers SET otp_code = ?, otp_purpose = 'PASSWORD_RESET', otp_expires_at = ?, otp_is_used = 0 WHERE email = ?");
                $stmt->execute([$otp, $otp_expires, $email]);

                $_SESSION['reset_email'] = $email;

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'elci.bank@gmail.com';
                    $mail->Password = 'misxfqnfsovohfwh';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('elci.bank@gmail.com', 'SAMS');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'SAMS Password Reset OTP';
                    $mail->Body = "
                        <h2>Password Reset Request</h2>
                        <p>Your OTP for password reset is: <strong>$otp</strong></p>
                        <p>This OTP is valid for 15 minutes. If you did not request this, please ignore this email.</p>
                    ";

                    $mail->send();
                    $notification = ['message' => 'OTP sent to your email', 'type' => 'success'];
                } catch (Exception $e) {
                    $notification = ['message' => 'Failed to send OTP: ' . $e->getMessage(), 'type' => 'error'];
                    error_log("PHPMailer error: " . $e->getMessage());
                }
            }
        } catch (PDOException $e) {
            $notification = ['message' => 'Database error: ' . $e->getMessage(), 'type' => 'error'];
            error_log("PDO error: " . $e->getMessage());
        }
    }

    ob_end_clean(); // Clear output buffer
    header('Content-Type: application/json');
    echo json_encode($notification);
    exit();
}

// For GET requests, render the HTML form
ob_end_clean(); // Clear any output before rendering HTML
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Student Attendance System</title>
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
        body { font-family: var(--font-family); background: linear-gradient(135deg, var(--primary-blue-light) 0%, var(--background) 100%); min-height: 100vh; display: flex; flex-direction: column; }
        .header { background: linear-gradient(135deg, var(--white) 0%, rgba(37, 99, 235, 0.02) 100%); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border-color); box-shadow: var(--shadow-md); position: sticky; top: 0; z-index: 1000; height: 70px; }
        .navbar { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 var(--spacing-lg); height: 100%; }
        .logo { display: flex; align-items: center; gap: var(--spacing-sm); text-decoration: none; color: var(--primary-blue); font-weight: 700; font-size: var(--font-size-xl); transition: var(--transition); }
        .logo i { font-size: 1.5rem; background: linear-gradient(135deg, var(--primary-blue), var(--info-cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .logo:hover { transform: translateY(-1px); color: var(--primary-blue-hover); }
        .nav-links { display: flex; list-style: none; gap: var(--spacing-xl); align-items: center; }
        .nav-links a { text-decoration: none; color: var(--dark-gray); font-weight: 500; font-size: var(--font-size-base); padding: var(--spacing-sm) var(--spacing-md); border-radius: var(--radius-md); transition: var(--transition); position: relative; overflow: hidden; }
        .nav-links a::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent); transition: var(--transition); }
        .nav-links a:hover::before { left: 100%; }
        .nav-links a:hover { color: var(--primary-blue); background-color: var(--primary-blue-light); transform: translateY(-2px); }
        .nav-links a.active { color: var(--primary-blue); background-color: var(--primary-blue-light); border: 1px solid rgba(37, 99, 235, 0.2); }
        .auth-buttons { display: flex; gap: var(--spacing-md); align-items: center; }
        .btn { padding: var(--spacing-sm) var(--spacing-lg); border: none; border-radius: var(--radius-md); font-size: var(--font-size-base); font-weight: 500; cursor: pointer; transition: var(--transition); text-decoration: none; display: inline-flex; align-items: center; gap: var(--spacing-xs); position: relative; overflow: hidden; }
        .btn::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); transition: var(--transition); }
        .btn:hover::before { left: 100%; }
        .btn-outline { background: transparent; color: var(--primary-blue); border: 1px solid var(--primary-blue); }
        .btn-outline:hover { background-color: var(--primary-blue); color: var(--white); transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .btn-primary { background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-hover) 100%); color: var(--white); border: 1px solid transparent; }
        .btn-primary:hover { background: linear-gradient(135deg, var(--primary-blue-hover), var(--primary-blue)); transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .mobile-menu-toggle { display: none; background: none; border: none; font-size: var(--font-size-xl); color: var(--dark-gray); cursor: pointer; padding: var(--spacing-sm); border-radius: var(--radius-md); transition: var(--transition); }
        .mobile-menu-toggle:hover { color: var(--primary-blue); background-color: var(--primary-blue-light); }
        .mobile-menu { position: fixed; top: 70px; left: -100%; width: 100%; height: calc(100vh - 70px); background: var(--white); z-index: 999; transition: var(--transition); overflow-y: auto; box-shadow: var(--shadow-lg); }
        .mobile-menu.active { left: 0; }
        .mobile-nav-links { display: flex; flex-direction: column; padding: var(--spacing-xl); gap: var(--spacing-md); }
        .mobile-nav-links a { text-decoration: none; color: var(--dark-gray); font-weight: 500; font-size: var(--font-size-lg); padding: var(--spacing-md); border-radius: var(--radius-md); transition: var(--transition); border-left: 4px solid transparent; }
        .mobile-nav-links a:hover, .mobile-nav-links a.active { background-color: var(--primary-blue-light); color: var(--primary-blue); border-left-color: var(--primary-blue); }
        .mobile-auth-buttons { display: flex; flex-direction: column; gap: var(--spacing-md); padding: var(--spacing-xl); border-top: 1px solid var(--border-color); }
        .main-content { flex: 1; display: flex; align-items: center; justify-content: center; padding: var(--spacing-xl); }
        .forgot-password-container { width: 100%; max-width: 500px; background: var(--white); border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); overflow: hidden; animation: slideUp 0.6s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .forgot-password-header { background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-hover) 100%); color: var(--white); padding: var(--spacing-xl); text-align: center; position: relative; overflow: hidden; }
        .forgot-password-header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: pulse 3s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.1); opacity: 0.8; } }
        .forgot-password-header h1 { font-size: var(--font-size-2xl); font-weight: 700; margin-bottom: var(--spacing-sm); position: relative; z-index: 1; }
        .forgot-password-header p { font-size: var(--font-size-base); opacity: 0.9; position: relative; z-index: 1; }
        .forgot-password-form { padding: var(--spacing-2xl); }
        .info-section { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: var(--radius-md); padding: var(--spacing-lg); margin-bottom: var(--spacing-xl); text-align: center; }
        .info-section .icon { width: 48px; height: 48px; margin: 0 auto var(--spacing-md); background: var(--info-cyan); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .info-section .icon::before { content: ''; width: 24px; height: 24px; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ffffff'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'/%3E%3C/svg%3E") center/contain no-repeat; }
        .info-section h3 { color: var(--info-cyan); font-size: var(--font-size-lg); font-weight: 600; margin-bottom: var(--spacing-sm); }
        .info-section p { color: var(--dark-gray); font-size: var(--font-size-sm); line-height: 1.6; }
        .form-group { margin-bottom: var(--spacing-lg); }
        .form-label { display: block; font-size: var(--font-size-sm); font-weight: 600; color: var(--dark-gray); margin-bottom: var(--spacing-sm); }
        .form-input { width: 100%; padding: var(--spacing-md); border: 2px solid var(--border-color); border-radius: var(--radius-md); font-size: var(--font-size-base); font-family: var(--font-family); transition: var(--transition); }
        .form-input:focus { outline: none; border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .form-input::placeholder { color: var(--medium-gray); }
        .input-icon { position: relative; }
        .input-icon::before { content: ''; position: absolute; left: var(--spacing-md); top: 50%; transform: translateY(-50%); width: 20px; height: 20px; background-size: contain; background-repeat: no-repeat; }
        .email-icon::before { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207'/%3E%3C/svg%3E"); }
        .input-icon .form-input { padding-left: 3rem; }
        .reset-btn { width: 100%; padding: var(--spacing-md); background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-hover) 100%); color: var(--white); border: none; border-radius: var(--radius-md); font-size: var(--font-size-base); font-weight: 600; cursor: pointer; transition: var(--transition); }
        .reset-btn:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .error-message { background: #fef2f2; color: var(--danger-red); padding: var(--spacing-md); border-radius: var(--radius-md); font-size: var(--font-size-sm); margin-bottom: var(--spacing-lg); border: 1px solid #fecaca; display: none; }
        .success-message { background: #f0fdf4; color: var(--success-green); padding: var(--spacing-md); border-radius: var(--radius-md); font-size: var(--font-size-sm); margin-bottom: var(--spacing-lg); border: 1px solid #bbf7d0; display: none; }
        .back-to-signin { margin-top: var(--spacing-lg); text-align: center; color: var(--medium-gray); font-size: var(--font-size-sm); }
        .back-to-signin a { color: var(--primary-blue); text-decoration: none; font-weight: 600; transition: var(--transition); }
        .back-to-signin a:hover { color: var(--primary-blue-hover); text-decoration: underline; }
        @media (max-width: 768px) {
            .nav-links, .auth-buttons { display: none; }
            .mobile-menu-toggle { display: block; }
            .navbar { padding: 0 var(--spacing-md); }
            .forgot-password-form { padding: var(--spacing-lg); }
            .forgot-password-header { padding: var(--spacing-lg); }
            .main-content { padding: var(--spacing-md); }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="main-content">
        <div class="forgot-password-container">
            <div class="forgot-password-header">
                <h1>Forgot Password</h1>
                <p>Student Attendance Monitoring System</p>
            </div>
            <div class="forgot-password-form">
                <div class="info-section">
                    <div class="icon"></div>
                    <h3>Reset Your Password</h3>
                    <p>Enter your email address to receive a one-time password (OTP).</p>
                </div>
                <div id="errorMessage" class="error-message"></div>
                <div id="successMessage" class="success-message"></div>
                <form id="forgotPasswordForm" method="POST">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-icon email-icon">
                            <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required>
                        </div>
                    </div>
                    <button type="submit" class="reset-btn">Send OTP</button>
                </form>
                <div class="back-to-signin">
                    Remember your password? <a href="sign-in.php">Sign in here</a>
                </div>
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

        document.addEventListener('DOMContentLoaded', function() {
            const forgotPasswordForm = document.getElementById('forgotPasswordForm');
            const resetBtn = document.querySelector('.reset-btn');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            const inputs = document.querySelectorAll('.form-input');
            const forgotPasswordContainer = document.querySelector('.forgot-password-container');

            document.getElementById('email').focus();

            function showNotification(message, type) {
                const target = type === 'error' ? errorMessage : successMessage;
                target.textContent = message;
                target.style.display = 'block';
                setTimeout(() => target.style.display = 'none', 5000);
            }

            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
                if (input.value.length > 0) {
                    input.classList.add('has-value');
                }
                input.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        input.classList.add('has-value');
                    } else {
                        input.classList.remove('has-value');
                    }
                });
            });

            forgotPasswordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                errorMessage.style.display = 'none';
                successMessage.style.display = 'none';
                resetBtn.disabled = true;

                const email = document.getElementById('email').value;

                if (!email) {
                    showNotification('Please enter your email address.', 'error');
                    resetBtn.disabled = false;
                    return;
                }

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showNotification('Please enter a valid email address.', 'error');
                    resetBtn.disabled = false;
                    return;
                }

                const formData = new FormData(this);
                fetch('forgot-password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    resetBtn.disabled = false;
                    showNotification(data.message, data.type);
                    if (data.type === 'success') {
                        forgotPasswordContainer.style.transform = 'scale(0.95)';
                        forgotPasswordContainer.style.opacity = '0.8';
                        setTimeout(() => window.location.href = 'reset-password.php', 1000);
                    } else {
                        forgotPasswordContainer.style.animation = 'shake 0.5s ease-in-out';
                        setTimeout(() => forgotPasswordContainer.style.animation = '', 500);
                    }
                })
                .catch(error => {
                    resetBtn.disabled = false;
                    showNotification('An error occurred: ' + error.message, 'error');
                    console.error('Fetch error:', error);
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
                        forgotPasswordForm.dispatchEvent(new Event('submit'));
                    }
                });
            });

            errorMessage.addEventListener('click', function() {
                this.style.display = 'none';
            });
            successMessage.addEventListener('click', function() {
                this.style.display = 'none';
            });
        });
    </script>
</body>
</html>