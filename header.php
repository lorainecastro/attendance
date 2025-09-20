<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <li><a href="index.php" class="nav-link active">Home</a></li>
                <li><a href="index.php#about" class="nav-link">About</a></li>
                <li><a href="index.php#features" class="nav-link">Features</a></li>
                <li><a href="index.php#contact" class="nav-link">Contact</a></li>
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
    </script>
</body>
</html>