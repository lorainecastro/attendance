<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAMS - Student Attendance Monitoring System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            /* Primary Colors */
            --primary-blue: #3b82f6;
            --primary-blue-hover: #2563eb;
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
            background-color: var(--background);
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
        }

        /* Hero Section */
        .hero-container {
            position: relative;
            height: 70vh;
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
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--white);
        }

        .hero-slide.active {
            opacity: 1;
        }

        .hero-slide:nth-child(1) {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.95), rgba(8, 145, 178, 0.85)), 
                        radial-gradient(ellipse at top left, rgba(37, 99, 235, 0.3), transparent),
                        radial-gradient(ellipse at bottom right, rgba(8, 145, 178, 0.3), transparent);
            background-size: cover;
            background-position: center;
        }

        .hero-slide:nth-child(2) {
            background: linear-gradient(135deg, rgba(22, 163, 74, 0.95), rgba(37, 99, 235, 0.85)), 
                        radial-gradient(ellipse at top right, rgba(22, 163, 74, 0.3), transparent),
                        radial-gradient(ellipse at bottom left, rgba(37, 99, 235, 0.3), transparent);
            background-size: cover;
            background-position: center;
        }

        .hero-slide:nth-child(3) {
            background: linear-gradient(135deg, rgba(202, 138, 4, 0.95), rgba(220, 38, 38, 0.85)), 
                        radial-gradient(ellipse at center, rgba(202, 138, 4, 0.3), transparent),
                        radial-gradient(ellipse at top, rgba(220, 38, 38, 0.3), transparent);
            background-size: cover;
            background-position: center;
        }

        .hero-content {
            max-width: 800px;
            padding: var(--spacing-xl);
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: var(--spacing-lg);
            line-height: 1.2;
        }

        .hero-content p {
            font-size: var(--font-size-xl);
            margin-bottom: var(--spacing-xl);
            opacity: 0.9;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: var(--spacing-lg);
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-buttons .btn {
            padding: var(--spacing-md) var(--spacing-xl);
            font-size: var(--font-size-lg);
            border-radius: var(--radius-lg);
        }

        .hero-nav {
            position: absolute;
            bottom: var(--spacing-xl);
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: var(--spacing-md);
        }

        .hero-nav-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .hero-nav-dot.active {
            background: var(--white);
            transform: scale(1.2);
        }

        /* Section Styles */
        .section {
            padding: var(--spacing-2xl) 0;
            max-width: 1200px;
            margin: 0 auto;
            padding-left: var(--spacing-lg);
            padding-right: var(--spacing-lg);
        }

        .section-header {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }

        .section-title {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: var(--spacing-md);
            font-weight: 700;
        }

        .section-subtitle {
            font-size: var(--font-size-xl);
            color: var(--medium-gray);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* About Section */
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-2xl);
            align-items: center;
        }

        .about-text {
            font-size: var(--font-size-lg);
            line-height: 1.8;
            color: var(--dark-gray);
        }

        .about-text h3 {
            color: var(--primary-blue);
            margin-bottom: var(--spacing-md);
            font-size: var(--font-size-xl);
        }

        .about-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-lg);
        }

        .stat-card {
            background: var(--white);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: var(--transition-normal);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .stat-label {
            color: var(--medium-gray);
            font-size: var(--font-size-sm);
            margin-top: var(--spacing-xs);
        }

        /* Features Section */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--spacing-xl);
            margin-top: var(--spacing-2xl);
        }

        .feature-card {
            background: var(--white);
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: var(--transition-normal);
            border: 1px solid var(--border-color);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-blue);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto var(--spacing-lg);
            background: linear-gradient(135deg, var(--primary-blue), var(--info-cyan));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--white);
        }

        .feature-card h3 {
            color: var(--primary-blue);
            margin-bottom: var(--spacing-md);
            font-size: var(--font-size-xl);
        }

        .feature-card p {
            color: var(--medium-gray);
            line-height: 1.6;
        }

        /* Contact Section */
        .contact-container {
            background: var(--background);
            padding: var(--spacing-2xl);
            border-radius: var(--radius-xl);
        }

        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-2xl);
        }

        .contact-info h3 {
            color: var(--primary-blue);
            margin-bottom: var(--spacing-lg);
            font-size: var(--font-size-xl);
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }

        .contact-item i {
            width: 40px;
            height: 40px;
            background: var(--primary-blue);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-form {
            background: var(--white);
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-group label {
            display: block;
            margin-bottom: var(--spacing-xs);
            color: var(--dark-gray);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-family: var(--font-family);
            font-size: var(--font-size-base);
            transition: var(--transition-fast);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            width: 100%;
            padding: var(--spacing-md);
            background: var(--primary-blue);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-lg);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .submit-btn:hover {
            background: var(--primary-blue-hover);
            transform: translateY(-2px);
        }

        /* About Section Enhancement */
        .about-section {
            background: linear-gradient(135deg, var(--white), var(--primary-blue-light));
            border-radius: var(--radius-xl);
            padding: var(--spacing-2xl);
            margin: var(--spacing-xl) 0;
        }

        .about-text {
            font-size: var(--font-size-lg);
            line-height: 1.8;
            color: var(--dark-gray);
        }

        .about-text h3 {
            color: var(--primary-blue);
            margin-bottom: var(--spacing-md);
            font-size: var(--font-size-xl);
            position: relative;
            padding-bottom: var(--spacing-sm);
        }

        .about-text h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-blue), var(--info-cyan));
            border-radius: 2px;
        }

        .about-text p {
            margin-bottom: var(--spacing-lg);
            text-align: justify;
        }

        .about-visual {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .about-image-placeholder {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, var(--primary-blue), var(--info-cyan));
            border-radius: var(--radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 4rem;
            position: relative;
            overflow: hidden;
        }

        .about-image-placeholder::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255, 255, 255, 0.1) 10px,
                rgba(255, 255, 255, 0.1) 20px
            );
            animation: move 10s linear infinite;
        }

        @keyframes move {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .floating-element {
            position: absolute;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
        .floating-element:nth-child(2) { top: 60%; left: 85%; animation-delay: 2s; }
        .floating-element:nth-child(3) { top: 80%; left: 20%; animation-delay: 4s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Features Section Enhancement */
        .features-section {
            background: var(--background);
            padding: var(--spacing-2xl);
            margin: var(--spacing-xl) 0;
        }

        .feature-card {
            background: var(--white);
            padding: var(--spacing-xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: var(--transition-normal);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent);
            transition: var(--transition-normal);
        }

        .feature-card:hover::before {
            left: 100%;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-blue);
        }

        .feature-icon {
            width: 90px;
            height: 90px;
            margin: 0 auto var(--spacing-lg);
            background: linear-gradient(135deg, var(--primary-blue), var(--info-cyan));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .feature-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Contact Section Enhancement */
        .contact-section {
            background: linear-gradient(135deg, var(--primary-blue-light), var(--background));
            padding: var(--spacing-2xl);
            margin: var(--spacing-xl) 0;
        }

        .contact-form {
            background: var(--white);
            padding: var(--spacing-2xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .contact-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--info-cyan), var(--success-green));
        }

        .contact-info {
            background: var(--white);
            padding: var(--spacing-2xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            height: fit-content;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            transition: var(--transition-fast);
        }

        .contact-item:hover {
            background: var(--primary-blue-light);
            transform: translateX(5px);
        }

        .contact-item i {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-blue), var(--info-cyan));
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        /* Enhanced Footer */
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
            .footer-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-xl);
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }

            .about-content {
                grid-template-columns: 1fr;
            }

            .about-image-placeholder {
                height: 250px;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: var(--font-size-lg);
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .about-content,
            .contact-content {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 2rem;
            }

            .about-stats {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <a href="#" class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>SAMS</span>
            </a>

            <ul class="nav-links">
                <li><a href="#home" class="active">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#contact">Contact</a></li>
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
                <a href="#home" class="active" onclick="closeMobileMenu()">Home</a>
                <a href="#about" onclick="closeMobileMenu()">About</a>
                <a href="#features" onclick="closeMobileMenu()">Features</a>
                <a href="#contact" onclick="closeMobileMenu()">Contact</a>
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

    <!-- Hero Section -->
    <section class="hero-container">
        <div class="hero-slide active">
            <div class="hero-content">
                <h1>Smart Attendance Monitoring</h1>
                <p>Revolutionize attendance tracking with AI-powered insights and data-driven solutions for educational institutions</p>
                <div class="hero-buttons">
                    <a href="#features" class="btn btn-primary">
                        <i class="fas fa-rocket"></i>
                        Get Started
                    </a>
                    <a href="#about" class="btn btn-outline" style="color: white; border-color: white;">
                        <i class="fas fa-play"></i>
                        Learn More
                    </a>
                </div>
            </div>
        </div>
        <div class="hero-slide">
            <div class="hero-content">
                <h1>Data-Driven Insights</h1>
                <p>Harness the power of Time Series Forecasting and Regression Analysis to predict attendance patterns and improve student outcomes</p>
                <div class="hero-buttons">
                    <a href="#features" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i>
                        View Analytics
                    </a>
                    <a href="#contact" class="btn btn-outline" style="color: white; border-color: white;">
                        <i class="fas fa-envelope"></i>
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
        <div class="hero-slide">
            <div class="hero-content">
                <h1>Early Intervention System</h1>
                <p>Identify students at risk of chronic absenteeism and provide timely support to enhance learning achievement</p>
                <div class="hero-buttons">
                    <a href="#about" class="btn btn-primary">
                        <i class="fas fa-users"></i>
                        Support Students
                    </a>
                    <a href="#features" class="btn btn-outline" style="color: white; border-color: white;">
                        <i class="fas fa-cogs"></i>
                        Explore Features
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
    <section id="about" class="section">
        <div class="about-section">
            <div class="section-header">
                <h2 class="section-title">About Our Mission</h2>
                <p class="section-subtitle">Addressing the critical challenge of student absenteeism through innovative technology and data-driven solutions</p>
            </div>
            <div class="about-content">
                <div class="about-text">
                    <h3>The Challenge</h3>
                    <p>Student absenteeism in educational institutions has become a significant concern, contributing to low academic performance and hindering learning outcomes. Traditional manual attendance tracking methods are inefficient and fail to identify patterns that could predict chronic absenteeism.</p>
                    
                    <h3>Our Solution</h3>
                    <p>SAMS leverages advanced Time Series Forecasting and Regression Analysis to transform how educational institutions monitor and manage student attendance. Our system doesn't just track attendance—it predicts patterns, identifies at-risk students, and provides actionable insights for timely intervention.</p>
                    
                    <h3>Key Factors We Analyze</h3>
                    <p>Our system considers multiple attributes that influence attendance including geographical location, family structure, health conditions, and socioeconomic factors to provide comprehensive insights and personalized recommendations.</p>
                </div>
                <div class="about-visual">
                    <div class="about-image-placeholder">
                        <i class="fas fa-chart-line"></i>
                        <div class="floating-elements">
                            <div class="floating-element"></div>
                            <div class="floating-element"></div>
                            <div class="floating-element"></div>
                        </div>
                    </div>
                </div>
            </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section">
        <div class="features-section">
            <div class="section-header">
                <h2 class="section-title">Comprehensive Features</h2>
                <p class="section-subtitle">Powerful tools designed to enhance attendance monitoring and support student success</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Time Series Forecasting</h3>
                    <p>Advanced predictive analytics using machine learning algorithms to forecast attendance patterns and identify trends before they become problematic.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3>Regression Analysis</h3>
                    <p>Sophisticated statistical analysis to understand the relationship between various factors and student attendance, enabling targeted interventions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Intelligent Reports</h3>
                    <p>Generate comprehensive reports with AI-powered recommendations for improving attendance and supporting students at risk of chronic absenteeism.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Real-Time Notifications</h3>
                    <p>Instant alerts and notifications for teachers and administrators when attendance patterns indicate potential issues requiring immediate attention.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-analytics"></i>
                    </div>
                    <h3>Class Analytics Dashboard</h3>
                    <p>Interactive dashboards providing comprehensive attendance analytics with visual representations of patterns, trends, and actionable insights.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3>Exportable Reports</h3>
                    <p>Generate and export detailed attendance reports in multiple formats for administrative purposes, parent communication, and academic planning.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Multi-Factor Analysis</h3>
                    <p>Analyze attendance patterns considering gender, geographical location, family structure, health conditions, and socioeconomic factors.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>ISO 25010 Compliance</h3>
                    <p>Built with highest quality standards ensuring functionality, performance efficiency, usability, reliability, security, and maintainability.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section">
        <div class="contact-section">
            <div class="section-header">
                <h2 class="section-title">Get in Touch</h2>
                <p class="section-subtitle">Ready to transform your attendance monitoring? Contact us to learn more about SAMS</p>
            </div>
            <div class="contact-content">
                <div class="contact-info">
                    <h3>Contact Information</h3>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email</strong><br>
                            info@sams-system.com
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Phone</strong><br>
                            +63 (02) 123-4567
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Address</strong><br>
                            Metro Manila, Philippines
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Business Hours</strong><br>
                            Monday - Friday: 8:00 AM - 6:00 PM
                        </div>
                    </div>
                </div>
                <div class="contact-form">
                    <form id="contactForm">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="institution">Institution/Organization</label>
                            <input type="text" id="institution" name="institution">
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" placeholder="Tell us about your attendance monitoring needs..." required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i>
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Real-time Monitoring</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Data-Driven Insights</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">ISO</div>
                    <div class="stat-label">25010 Compliant</div>
                </div>
            </div>
        </div>
    </section>

 

    <!-- Contact Section -->
    <section id="contact" class="section">
        <div class="section-header">
            <h2 class="section-title">Get in Touch</h2>
            <p class="section-subtitle">Ready to transform your attendance monitoring? Contact us to learn more about SAMS</p>
        </div>
        <div class="contact-container">
            <div class="contact-content">
                <div class="contact-info">
                    <h3>Contact Information</h3>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email</strong><br>
                            info@sams-system.com
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Phone</strong><br>
                            +63 (02) 123-4567
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Address</strong><br>
                            Metro Manila, Philippines
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Business Hours</strong><br>
                            Monday - Friday: 8:00 AM - 6:00 PM
                        </div>
                    </div>
                </div>
                <div class="contact-form">
                    <form id="contactForm">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="institution">Institution/Organization</label>
                            <input type="text" id="institution" name="institution">
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" placeholder="Tell us about your attendance monitoring needs..." required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i>
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

       <!-- Footer -->
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
                <p>&copy; 2024 SAMS - Student Attendance Monitoring System. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>  
    
    <script>
        // Hero slider functionality
        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.hero-nav-dot');
        
        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            slides[index].classList.add('active');
            dots[index].classList.add('active');
        }
        
        function nextSlide() {
            currentSlideIndex = (currentSlideIndex + 1) % slides.length;
            showSlide(currentSlideIndex);
        }
        
        function currentSlide(index) {
            currentSlideIndex = index - 1;
            showSlide(currentSlideIndex);
        }
        
        // Auto-advance slides
        setInterval(nextSlide, 5000);

        // Mobile menu toggle functionality
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const toggleBtn = document.querySelector('.mobile-menu-toggle i');
            
            mobileMenu.classList.toggle('active');
            
            // Change icon based on menu state
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

        // Navigation active state management
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-links a, .mobile-nav-links a');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links
                    navLinks.forEach(l => l.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Find corresponding link in mobile/desktop menu and sync
                    const href = this.getAttribute('href');
                    const correspondingLink = document.querySelector(
                        this.closest('.mobile-nav-links') ? 
                        `.nav-links a[href="${href}"]` : 
                        `.mobile-nav-links a[href="${href}"]`
                    );
                    
                    if (correspondingLink) {
                        correspondingLink.classList.add('active');
                    }
                });
            });
        });

        // Contact form submission
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Simulate form submission
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Message Sent!';
                submitBtn.style.background = 'var(--success-green)';
                
                // Reset form
                this.reset();
                
                // Reset button after 3 seconds
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.style.background = 'var(--primary-blue)';
                    submitBtn.disabled = false;
                }, 3000);
            }, 2000);
        });

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
                }
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
    </script>
</body>
</html>