<?php include "header.php" ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service & Privacy Policy - SAMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-blue-hover: #1d4ed8;
            --primary-blue-light: #dbeafe;
            --dark-gray: #1f2937;
            --medium-gray: #6b7280;
            --light-gray: #f8fafc;
            --background: #ffffff;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Container */
        .terms-privacy-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px;
        }

        /* Header */
        .terms-privacy-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .terms-privacy-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--text-primary);
        }

        .terms-privacy-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 900px;
            margin: 0 auto;
            line-height: 1.8;
        }

        /* Section */
        .terms-section, .privacy-section {
            margin-bottom: 80px;
        }

        .terms-section h2, .privacy-section h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 24px;
            color: var(--text-primary);
            position: relative;
            padding-bottom: 10px;
        }

        .terms-section h2::after, .privacy-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-blue);
        }

        .terms-section p, .privacy-section p {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 20px;
            line-height: 1.7;
        }

        .terms-section ul, .privacy-section ul {
            list-style: none;
            margin-bottom: 20px;
        }

        .terms-section ul li, .privacy-section ul li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
        }

        .terms-section ul li i, .privacy-section ul li i {
            color: var(--primary-blue);
            font-size: 1.2rem;
            margin-top: 4px;
        }

        .terms-section ul li div, .privacy-section ul li div {
            flex: 1;
        }

        .terms-section ul li div h4, .privacy-section ul li div h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .terms-section ul li div p, .privacy-section ul li div p {
            font-size: 0.95rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Back Link */
        .back-link {
            display: inline-block;
            margin-top: 40px;
            padding: 12px 24px;
            background: var(--primary-blue);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .back-link:hover {
            background: var(--primary-blue-hover);
            transform: translateY(-2px);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .terms-privacy-container {
                padding: 60px 16px;
            }

            .terms-privacy-header h1 {
                font-size: 2.2rem;
            }

            .terms-privacy-header p {
                font-size: 1.1rem;
            }

            .terms-section h2, .privacy-section h2 {
                font-size: 1.8rem;
            }

            .terms-section ul li, .privacy-section ul li {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .terms-section ul li i, .privacy-section ul li i {
                margin-bottom: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="terms-privacy-container">
        <div class="terms-privacy-header">
            <h1>Terms of Service & Privacy Policy</h1>
            <p>Welcome to the Student Attendance Monitoring System (SAMS). Below are our Terms of Service and Privacy Policy, outlining your rights, responsibilities, and how we handle your data.</p>
        </div>

        <div class="terms-section">
            <h2>Terms of Service</h2>
            <p>By using SAMS, you agree to these Terms of Service. These terms govern your access to and use of our platform, including all features and services provided.</p>
            <ul>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h4>Account Responsibilities</h4>
                        <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities under your account. Notify us immediately of any unauthorized use.</p>
                    </div>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h4>Acceptable Use</h4>
                        <p>You may not use SAMS for any unlawful or unauthorized purpose, including but not limited to violating intellectual property rights or attempting to gain unauthorized access to our systems.</p>
                    </div>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h4>Service Availability</h4>
                        <p>We strive to ensure SAMS is available but do not guarantee uninterrupted access. We may modify or discontinue features without prior notice.</p>
                    </div>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h4>Termination</h4>
                        <p>We reserve the right to suspend or terminate your access to SAMS for violation of these terms or for any reason at our discretion.</p>
                    </div>
                </li>
            </ul>
        </div>

        <div class="privacy-section">
            <h2>Privacy Policy</h2>
            <p>At SAMS, we are committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your personal information.</p>
            <ul>
                <li>
                    <i class="fas fa-lock"></i>
                    <div>
                        <h4>Information Collection</h4>
                        <p>We collect personal information such as your name, email, and attendance data when you use SAMS. This data is used to provide and improve our services.</p>
                    </div>
                </li>
                <li>
                    <i class="fas fa-lock"></i>
                    <div>
                        <h4>Data Usage</h4>
                        <p>Your data is used to manage attendance, generate analytics, and provide personalized insights. We may also use anonymized data for research and improvement purposes.</p>
                    </div>
                </li>
                <li>
                    <i class="fas fa-lock"></i>
                    <div>
                        <h4>Data Sharing</h4>
                        <p>We do not sell your personal information. Data may be shared with authorized school personnel or third-party service providers who assist in operating SAMS, under strict confidentiality agreements.</p>
                    </div>
                </li>
                <li>
                    <i class="fas fa-lock"></i>
                    <div>
                        <h4>Data Security</h4>
                        <p>We implement industry-standard security measures to protect your data. However, no system is completely secure, and we cannot guarantee absolute security.</p>
                    </div>
                </li>
                <li>
                    <i class="fas fa-lock"></i>
                    <div>
                        <h4>Your Rights</h4>
                        <p>You have the right to access, correct, or delete your personal information. Contact us at support@sams.edu to exercise these rights.</p>
                    </div>
                </li>
            </ul>
        </div>

        <!--<a href="index.html" class="back-link">Back to Home</a>-->
    </div>
    <?php include "footer.php" ?>
</body>
</html>