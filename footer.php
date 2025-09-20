<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #2563eb;
            --dark-gray: #1f2937;
            --light-gray: #f8fafc;
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
            width: 36px;
            height: 36px;
            background: var(--primary-blue);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
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
        @media (max-width: 1024px) {
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
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
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column footer-logo-section">
                <div class="footer-logo">
                    <div class="logo">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="footer-logo-text">SAMS</div>
                </div>
                <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.85rem;">
                    SAMS empowers schools with smart attendance tracking and predictive analytics to boost student success.
                </p>
            </div>
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="index.php#features">Features</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Resources</h3>
                <ul>
                    <li><a href="#support">Support</a></li>
                    <li><a href="#help">Help Center</a></li>
                    <li><a href="#privacy">Privacy Policy</a></li>
                    <li><a href="#terms">Terms of Service</a></li>
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
                                
                                <p>0910-031-0361</p>
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
</body>
</html>