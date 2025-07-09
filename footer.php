<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
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
        }
    </style>
</head>
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
            <p>&copy; 2025 SAMS - Student Attendance Monitoring System. All rights reserved.</p>
            <div class="footer-bottom-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Cookie Policy</a>
            </div>
        </div>
    </div>
</footer>

</html>