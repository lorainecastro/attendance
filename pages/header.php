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

        /* Demo content */
        .demo-content {
            padding: var(--spacing-2xl);
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-section {
            text-align: center;
            padding: var(--spacing-2xl) 0;
            background: linear-gradient(135deg, var(--primary-blue-light), var(--background));
            border-radius: var(--radius-xl);
            margin-bottom: var(--spacing-xl);
        }

        .hero-section h1 {
            font-size: var(--font-size-2xl);
            color: var(--primary-blue);
            margin-bottom: var(--spacing-md);
        }

        .hero-section p {
            color: var(--medium-gray);
            font-size: var(--font-size-lg);
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

    <!-- Demo Content 
    <div class="demo-content">
        <div class="hero-section">
            <h1>Student Attendance Monitoring System</h1>
            <p>Streamline attendance tracking with our comprehensive and user-friendly platform</p>
        </div>
    </div> -->

    <script>
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

        // Handle navigation based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop() || 'index.php';
            const navLinks = document.querySelectorAll('.nav-links a, .mobile-nav-links a');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                
                // Check if we're on the home page and this is a home link
                if (currentPage === 'index.php' && href === 'index.php') {
                    link.classList.add('active');
                }
                // Check if we're on the same page as the link
                else if (href.includes(currentPage) && !href.includes('#')) {
                    link.classList.add('active');
                }
            });
        });

        // Enhanced navigation handling for hash links
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-links a, .mobile-nav-links a');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    
                    // If it's a hash link and we're on the same page
                    if (href.includes('#') && href.startsWith('index.php#')) {
                        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
                        
                        if (currentPage === 'index.php') {
                            // We're already on index.php, just scroll to section
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
                        // If we're not on index.php, let the link redirect normally
                    }
                    
                    // Update active state
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Close mobile menu if open
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

        // Handle hash navigation when page loads
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
    </script>
</body>
</html>