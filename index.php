<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance Monitoring System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-blue-hover: #1d4ed8;
            --primary-blue-light: #dbeafe;
            --success-green: #059669;
            --warning-yellow: #d97706;
            --danger-red: #dc2626;
            --dark-gray: #1f2937;
            --medium-gray: #6b7280;
            --light-gray: #f8fafc;
            --background: #ffffff;
            --white: #ffffff;
            --border-color: #e5e7eb;
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

        /* Top Bar */
        .top-bar {
            background: var(--primary-blue);
            padding: 8px 0;
            font-size: 0.875rem;
            color: white;
        }

        .top-bar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .top-bar-right {
            display: flex;
            gap: 20px;
        }

        .top-bar-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }

        .top-bar-link:hover {
            color: white;
        }

        /* Header */
        .header {
            background: white;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            width: 48px;
            height: 48px;
            background: var(--primary-blue);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .logo-subtext {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        /* Navigation */
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 32px;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.95rem;
            padding: 8px 16px;
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-blue);
            background-color: var(--primary-blue-light);
        }

        /* Buttons */
        .auth-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
        }

        .btn-signin {
            color: var(--primary-blue);
            background: transparent;
            border: 1px solid var(--border-color);
        }

        .btn-signin:hover {
            border-color: var(--primary-blue);
            background: var(--primary-blue-light);
        }

        .btn-signup {
            background: var(--primary-blue);
            color: white;
        }

        .btn-signup:hover {
            background: var(--primary-blue-hover);
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            height: 80vh;
            min-height: 600px;
            overflow: hidden;
        }

        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .hero-slide.active {
            opacity: 1;
        }

        .hero-slide:nth-child(1) {
            background-image: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-color: var(--primary-blue);
        }

        .hero-slide:nth-child(2) {
            background-image: url('https://images.unsplash.com/photo-1434030216411-0b793f4b4173?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-color: var(--success-green);
        }

        .hero-slide:nth-child(3) {
            background-image: url('https://images.unsplash.com/photo-1509062522246-3755977927d7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-color: var(--warning-yellow);
        }

        .hero-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }

        .hero-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
            max-width: 900px;
            padding: 40px;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 24px;
            line-height: 1.1;
        }

        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 32px;
            opacity: 0.95;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-btn {
            padding: 16px 32px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: var(--radius);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hero-btn-primary {
            background: white;
            color: var(--primary-blue);
        }

        .hero-btn-primary:hover {
            background: var(--light-gray);
            transform: translateY(-2px);
        }

        .hero-btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .hero-btn-outline:hover {
            background: white;
            color: var(--primary-blue);
        }

        /* Navigation dots */
        .hero-nav {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
        }

        .hero-nav-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: var(--transition);
        }

        .hero-nav-dot.active {
            background: white;
            transform: scale(1.2);
        }

        /* About Section */
        .about-section {
            background: var(--light-gray);
            padding: 100px 0;
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .about-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .about-header h2 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--text-primary);
        }

        .about-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 900px;
            margin: 0 auto;
            line-height: 1.8;
        }

        /* Features Section */
        .features-section {
            padding: 100px 0;
            background: var(--white);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .features-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .features-header h2 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--text-primary);
        }

        .features-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 900px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 32px;
        }

        .feature-card {
            background: white;
            padding: 32px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            display: flex;
            align-items: flex-start;
            gap: 24px;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: var(--primary-blue);
            flex-shrink: 0;
        }

        .feature-content h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .feature-content p {
            color: var(--text-secondary);
            line-height: 1.7;
        }

        /* Contact Section */
        .contact-section {
            padding: 100px 0;
            background: var(--light-gray);
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .contact-header h2 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--text-primary);
        }

        .contact-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 900px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
        }

        .contact-info {
            padding: 40px;
        }

        .contact-info h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 24px;
            color: var(--text-primary);
        }

        .contact-info-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
        }

        .contact-info-item i {
            font-size: 1.5rem;
            color: var(--primary-blue);
            margin-top: 4px;
        }

        .contact-info-item div h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .contact-info-item div p {
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .contact-map {
            padding: 40px;
        }

        .contact-map h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 24px;
            color: var(--text-primary);
        }

        .contact-map iframe {
            width: 100%;
            height: 400px;
            border: none;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
        }

        /* Footer Section */
        .footer {
            background: linear-gradient(180deg, var(--dark-gray) 0%, #111827 100%);
            color: white;
            padding: 64px 0 32px;
            position: relative;
            overflow: hidden;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 32px;
            position: relative;
            z-index: 2;
        }

        .footer-column {
            display: flex;
            flex-direction: column;
        }

        .footer-logo-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .footer-logo .logo {
            width: 48px;
            height: 48px;
            background: var(--primary-blue);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .footer-logo-text {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
        }

        .footer-column h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: white;
            position: relative;
            padding-bottom: 6px;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 30px;
            height: 2px;
            background: var(--primary-blue);
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column ul li {
            margin-bottom: 12px;
        }

        .footer-column ul li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.85rem;
            transition: var(--transition);
            display: inline-block;
        }

        .footer-column ul li a:hover {
            color: white;
            transform: translateX(4px);
        }

        .footer-column .contact-info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
        }

        .footer-column .contact-info-item i {
            font-size: 1.2rem;
            color: var(--primary-blue);
            margin-top: 2px;
            flex-shrink: 0;
        }

        .footer-column .contact-info-item div h4 {
            font-size: 0.95rem;
            font-weight: 500;
            color: white;
            margin-bottom: 4px;
        }

        .footer-column .contact-info-item div p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.5;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 32px;
            padding-top: 16px;
            text-align: center;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            position: relative;
            z-index: 2;
        }

        .footer-bottom a {
            color: var(--primary-blue);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-bottom a:hover {
            color: white;
        }

        /* Footer Background Decoration */
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(37, 99, 235, 0.2) 0%, transparent 50%);
            z-index: 1;
        }

        /* Mobile Responsive */
        .mobile-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 8px;
        }

        .mobile-toggle span {
            width: 22px;
            height: 2px;
            background: var(--text-secondary);
            margin: 3px 0;
            transition: var(--transition);
            border-radius: 1px;
        }

        @media (max-width: 1024px) {
            .features-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .contact-content {
                grid-template-columns: 1fr;
            }

            .footer-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .top-bar-container {
                padding: 0 16px;
            }

            .top-bar-right a:not(:first-child) {
                display: none;
            }

            .nav-container {
                padding: 0 16px;
            }

            .logo-text {
                font-size: 1.1rem;
            }

            .logo-subtext {
                display: none;
            }

            .nav-menu {
                position: fixed;
                left: -100%;
                top: 118px;
                flex-direction: column;
                background: white;
                width: 100%;
                text-align: center;
                transition: left 0.3s ease;
                box-shadow: var(--shadow-lg);
                padding: 40px 0;
                gap: 20px;
            }

            .nav-menu.active {
                left: 0;
            }

            .mobile-toggle {
                display: flex;
            }

            .auth-buttons {
                flex-direction: column;
                width: 100%;
                gap: 16px;
                padding: 0 40px;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.125rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .hero-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .about-container,
            .features-container,
            .contact-container {
                padding: 0 16px;
            }

            .about-header h2,
            .features-header h2,
            .contact-header h2 {
                font-size: 2.2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .feature-card {
                flex-direction: column;
                text-align: center;
            }

            .feature-card i {
                margin-bottom: 16px;
            }

            .contact-content {
                grid-template-columns: 1fr;
            }

            .contact-info,
            .contact-map {
                padding: 24px;
            }

            .contact-info-item {
                display: flex;
                align-items: flex-start;
                gap: 16px;
                margin-bottom: 24px;
            }

            .contact-info-item p {
                text-wrap: wrap;
            }

            .contact-map iframe {
                height: 300px;
                width: 335px;
            }

            .footer-container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-logo {
                justify-content: center;
            }

            .footer-column h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .footer-column .contact-info-item {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-bar-left">
                <i class="fas fa-graduation-cap"></i>
                <span>Student Attendance Monitoring System</span>
            </div>
            <div class="top-bar-right">
                <a href="#support" class="top-bar-link">
                    <i class="fas fa-headset"></i>
                    Support
                </a>
                <a href="#help" class="top-bar-link">
                    <i class="fas fa-question-circle"></i>
                    Help
                </a>
                <a href="#contact" class="top-bar-link">
                    <i class="fas fa-phone"></i>
                    Contact
                </a>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <div class="logo-text">SAMS</div>
                    <div class="logo-subtext">Student Attendance Monitoring System</div>
                </div>
            </div>

            <ul class="nav-menu" id="nav-menu">
                <li><a href="#home" class="nav-link active">Home</a></li>
                <li><a href="#about" class="nav-link">About</a></li>
                <li><a href="#features" class="nav-link">Features</a></li>
                <li><a href="#contact" class="nav-link">Contact</a></li>
                <div class="auth-buttons">
                    <a href="sign-in.php" class="btn btn-signin">Sign In</a>
                    <a href="sign-up.php" class="btn btn-signup">Sign Up</a>
                </div>
            </ul>

            <div class="mobile-toggle" id="mobile-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="hero-slide active">
            <div class="hero-content">
                <h1>Smart QR Code Attendance</h1>
                <p>An attendance system that uses QR codes and manual options to make checking attendance quick and easy.</p>
                <div class="hero-buttons">
                    <a href="#features" class="hero-btn hero-btn-primary">
                        <i class="fas fa-qrcode"></i>
                        Start Scanning
                    </a>
                    <a href="#about" class="hero-btn hero-btn-outline">
                        <i class="fas fa-play"></i>
                        Learn More
                    </a>
                </div>
            </div>
        </div>

        <div class="hero-slide">
            <div class="hero-content">
                <h1>ARIMA Time Series Forecasting</h1>
                <p>Advanced predictive analytics using ARIMA methodology to forecast attendance patterns and identify trends based on recorded class data.</p>
                <div class="hero-buttons">
                    <a href="#analytics" class="hero-btn hero-btn-primary">
                        <i class="fas fa-chart-line"></i>
                        View Forecasts
                    </a>
                    <a href="#contact" class="hero-btn hero-btn-outline">
                        <i class="fas fa-database"></i>
                        Explore Data
                    </a>
                </div>
            </div>
        </div>

        <div class="hero-slide">
            <div class="hero-content">
                <h1>Early Risk Identification</h1>
                <p>Clear visualizations and forecast-driven insights help teachers identify at-risk students and implement timely interventions for better learning outcomes</p>
                <div class="hero-buttons">
                    <a href="#intervention" class="hero-btn hero-btn-primary">
                        <i class="fas fa-users"></i>
                        Monitor Students
                    </a>
                    <a href="#features" class="hero-btn hero-btn-outline">
                        <i class="fas fa-eye"></i>
                        View Analytics
                    </a>
                </div>
            </div>
        </div>

        <div class="hero-nav">
            <div class="hero-nav-dot active" onclick="currentSlide(1)"></div>
            <div class="hero-nav-dot" onclick="currentSlide(2)"></div>
            <div class="hero-nav-dot" onclick="currentSlide(3)"></div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="about-container">
            <div class="about-header">
                <h2>About Our System</h2>
                <p>Our Student Attendance Monitoring System, titled "Time Series Forecasting for Monitoring and Enhancing Student Attendance in Public Schools", is designed to address the critical issue of student absenteeism across public schools. By leveraging ARIMA-based Time Series Forecasting, our platform empowers teachers with a user-friendly, data-driven tool to track attendance trends, identify at-risk students, and implement timely interventions.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>Empowering Teachers</h3>
                    <p>Provides an intuitive platform for teachers to monitor attendance, access AI-driven insights, and receive actionable recommendations to reduce absenteeism and boost student performance.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Supporting Students</h3>
                    <p>Enables early identification of attendance issues, fostering timely interventions that promote responsibility and improve academic and social outcomes.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-school"></i>
                    <h3>Enhancing Schools</h3>
                    <p>Supports public schools in adopting data-driven attendance tracking, aligning with digital transformation goals and improving student success and accreditation standards.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Advanced Analytics</h3>
                    <p>Utilizes ARIMA-based Time Series Forecasting to detect attendance patterns, predict risks, and provide clear visualizations for informed decision-making.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="features-container">
            <div class="features-header">
                <h2>Our Features</h2>
                <p>Discover the powerful tools and features that make our Student Attendance Monitoring System the ideal choice for schools aiming to enhance attendance and student outcomes.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-qrcode"></i>
                    <div class="feature-content">
                        <h3>Real-time QR Code Scanning</h3>
                        <p>Quick attendance marking with instant data sync, enabling seamless and efficient tracking of student presence in real-time.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-line"></i>
                    <div class="feature-content">
                        <h3>ARIMA Predictive Analytics</h3>
                        <p>Forecast attendance patterns and identify trends using advanced ARIMA methodology for data-driven decision-making.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="feature-content">
                        <h3>Student Risk Assessment</h3>
                        <p>Early warning system for at-risk students, providing actionable insights to support timely interventions and improve outcomes.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <i class="fas fa-tachometer-alt"></i>
                    <div class="feature-content">
                        <h3>Teacher Dashboard</h3>
                        <p>Comprehensive analytics and reporting tools to empower teachers with clear, actionable data for attendance management.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" id="contact">
        <div class="contact-container">
            <div class="contact-header">
                <h2>Contact Us</h2>
                <p>We're here to help! Reach out with any questions, feedback, or support inquiries, and our team will get back to you promptly.</p>
            </div>
            <div class="contact-content">
                <div class="contact-info">
                    <h3>Contact Information</h3>
                    <div class="contact-info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>student.attendance.monitoring.sys@gmail.com</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Phone</h4>
                            <p>0910-031-0621</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Address</h4>
                            <p>Bulacan State University<br>(Bustos Campus)</p>
                        </div>
                    </div>
                </div>
                <div class="contact-map">
    <h3>Our Location</h3>
    <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3856.912401235189!2d120.9099939!3d14.9547300!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33970009c8e60931%3A0xee163fa5b5612c18!2sBulacan%20State%20University%20-%20Bustos%20Campus!5e0!3m2!1sen!2sph!4v1726758900000!5m2!1sen!2sph" 
        width="100%" 
        height="400" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
   
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column footer-logo-section">
                <div class="footer-logo">
                    <div class="logo">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="footer-logo-text">SAMS</div>
                </div>
                <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.85rem; line-height: 2;">
                    SAMS empowers schools with smart attendance tracking and predictive analytics to boost student success.
                </p>
            </div>
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Resources</h3>
                <ul>
                    <li><a href="#support">Support</a></li>
                    <li><a href="#help">Help Center</a></li>
                    <li><a href="terms.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms of Service</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Contact Us</h3>
                <ul>
                    <li>
                        <div class="contact-info-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                               
                                <p>student.attendance.monitoring.sys@gmail.com</p>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="contact-info-item">
                            <i class="fas fa-phone"></i>
                            <div>
                               
                                <p>0910-031-0321</p>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="contact-info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                
                                <p>Bulacan State University<br>(Bustos Campus)</p>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
    <p>&copy; 2025 Student Attendance Monitoring System | A Capstone Project of BSIT Students, BulSU Bustos Campus</p>
</div>

    </footer>

    <script>
        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobile-toggle');
        const navMenu = document.getElementById('nav-menu');

        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            mobileToggle.classList.toggle('active');
            // Animate hamburger to X
            if (mobileToggle.classList.contains('active')) {
                mobileToggle.children[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                mobileToggle.children[1].style.opacity = '0';
                mobileToggle.children[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
            } else {
                mobileToggle.children[0].style.transform = 'none';
                mobileToggle.children[1].style.opacity = '1';
                mobileToggle.children[2].style.transform = 'none';
            }
        });

        // Update active nav link based on scroll position
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').slice(1) === current) {
                    link.classList.add('active');
                }
            });
        });

        // Hero slideshow
        let slideIndex = 1;
        showSlides(slideIndex);

        function currentSlide(n) {
            showSlides(slideIndex = n);
        }

        function showSlides(n) {
            const slides = document.getElementsByClassName('hero-slide');
            const dots = document.getElementsByClassName('hero-nav-dot');
            
            if (n > slides.length) { slideIndex = 1; }
            if (n < 1) { slideIndex = slides.length; }
            
            for (let i = 0; i < slides.length; i++) {
                slides[i].classList.remove('active');
            }
            
            for (let i = 0; i < dots.length; i++) {
                dots[i].classList.remove('active');
            }
            
            slides[slideIndex - 1].classList.add('active');
            dots[slideIndex - 1].classList.add('active');
        }

        // Auto slideshow
        setInterval(() => {
            slideIndex++;
            if (slideIndex > 3) slideIndex = 1;
            showSlides(slideIndex);
        }, 5000);

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    // Close mobile menu after clicking a link
                    navMenu.classList.remove('active');
                    mobileToggle.classList.remove('active');
                    mobileToggle.children[0].style.transform = 'none';
                    mobileToggle.children[1].style.opacity = '1';
                    mobileToggle.children[2].style.transform = 'none';
                }
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!navMenu.contains(e.target) && !mobileToggle.contains(e.target) && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                mobileToggle.classList.remove('active');
                mobileToggle.children[0].style.transform = 'none';
                mobileToggle.children[1].style.opacity = '1';
                mobileToggle.children[2].style.transform = 'none';
            }
        });
    </script>
</body>
</html>