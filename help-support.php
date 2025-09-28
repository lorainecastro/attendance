<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Help and Support - Student Attendance System</title>
    <style>
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-hover: #2563eb;
            --primary-blue-light: #dbeafe;
            --success-green: #22c55e;
            --warning-yellow: #f59e0b;
            --danger-red: #ef4444;
            --info-cyan: #06b6d4;
            --dark-gray: #1f2937;
            --medium-gray: #6b7280;
            --light-gray: #e5e7eb;
            --background: #f9fafb;
            --white: #ffffff;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
            --blackfont-color: #1e293b;
            --whitefont-color: #ffffff;
            --grayfont-color: #64748b;
            --primary-gradient: linear-gradient(135deg, #3b82f6, #3b82f6);
            --secondary-gradient: linear-gradient(135deg, #ec4899, #f472b6);
            --inputfield-color: #f8fafc;
            --inputfieldhover-color: #f1f5f9;
            --font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.875rem;
            --spacing-xs: 0.5rem;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --transition-fast: 0.2s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
            --transition-slow: 0.5s ease-in-out;
            --status-present-bg: #e6ffed;
            --status-absent-bg: #ffe6e6;
            --status-late-bg: #fff8e6;
            --status-none-bg: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            background-color: var(--card-bg);
            color: var(--blackfont-color);
            padding: 20px;
        }

        h1 { 
            font-size: 24px; 
            margin-bottom: 20px; 
            color: var(--blackfont-color); 
            position: relative; 
            padding-bottom: 10px; 
        }

        h1:after { 
            content: ''; 
            position: absolute; 
            left: 0; 
            bottom: 0; 
            height: 4px; 
            width: 80px; 
            background: var(--primary-gradient); 
            border-radius: var(--radius-sm); 
        }

        .faq {
            background-color: var(--background);
            padding: var(--spacing-2xl) var(--spacing-xl);
        }

        .faq h2 {
            font-size: var(--font-size-2xl);
            font-weight: 800;
            text-align: center;
            margin-bottom: var(--spacing-lg);
            color: var(--blackfont-color);
        }

        .faq p {
            font-size: var(--font-size-lg);
            color: var(--grayfont-color);
            text-align: center;
            margin-bottom: var(--spacing-xl);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .faq .grid {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-lg);
        }

        .faq-item {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .faq-item:hover {
            background-color: var(--inputfieldhover-color);
        }

        .faq-item h3 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-item .content {
            display: none;
            margin-top: var(--spacing-sm);
            font-size: var(--font-size-base);
            color: var(--grayfont-color);
        }

        .faq-item.active .content {
            display: block;
        }

        .arrow {
            transition: transform var(--transition-fast);
        }

        .faq-item.active .arrow {
            transform: rotate(180deg);
        }

        @media (max-width: 992px) {
            .faq .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <h1>Help & Support</h1>

    <!-- FAQ Section -->
    <section id="faq" class="faq">
        <h2>Frequently Asked Questions</h2>
        <p>Find answers to common questions about using the Student Attendance Monitoring System. For further assistance, contact us via the Contact Page.</p>
        <div class="grid">
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>How do I mark attendance using the QR code feature? <span class="arrow"> ❯ </span></h3>
                <div class="content">Log in to your teacher account on https://attendancemonitoring.site, navigate to the attendance section, and scan the student's QR code using a compatible device. The system will automatically record the attendance in real-time.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>Can I manually record attendance if QR codes are not available? <span class="arrow"> ❯ </span></h3>
                <div class="content">Yes, you can manually mark attendance by accessing the class roster on the platform, selecting the student, and updating their attendance status (Present, Absent, or Late).</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>How does the ARIMA forecasting feature work? <span class="arrow"> ❯ </span></h3>
                <div class="content">The ARIMA-based Time Series Forecasting analyzes historical attendance data to predict future patterns. Access the analytics dashboard to view trends and forecasts for your class or individual students.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>How can I identify at-risk students? <span class="arrow"> ❯ </span></h3>
                <div class="content">The system’s risk assessment tool highlights students with frequent absences or irregular attendance patterns. Check the Teacher Dashboard for alerts and detailed reports to take timely action.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>What should I do if I encounter technical issues? <span class="arrow"> ❯ </span></h3>
                <div class="content">Contact our support team at student.attendance.monitoring.sys@gmail.com or call 0910-031-0621. Provide details of the issue, and we’ll assist you promptly.</div>
            </div>
        </div>
    </section>
</body>

</html>