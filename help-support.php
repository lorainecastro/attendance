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
                <h3>1. How do I add a new class? <span class="arrow"> ❯ </span></h3>
                <div class="content">You can add a new class by clicking “Add Class” on the dashboard, filling out the required fields (grade level, section name, schedule, etc.), and saving the class.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>2. Can I edit a class after creating it? <span class="arrow"> ❯ </span></h3>
                <div class="content">Yes. Locate the class in the dashboard, click “Edit”, update the details in the modal, and save your changes.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>3. What happens when I archive a class? <span class="arrow"> ❯ </span></h3>
                <div class="content">Archived classes are moved out of the active list. They cannot be edited unless unarchived, but you can still view details and student records.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>4. How do I import students into a class? <span class="arrow"> ❯ </span></h3>
                <div class="content">Open the Students section of the class, upload an Excel file with the required columns (LRN, names, email, etc.), preview, and import. QR codes will be generated automatically if missing.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>5. How do QR codes work for attendance? <span class="arrow"> ❯ </span></h3>
                <div class="content">Each student has a unique QR code generated from their LRN and name. Teachers can scan these codes using a camera or scanner to mark attendance.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>6. Can I manually mark attendance without QR codes? <span class="arrow"> ❯ </span></h3>
                <div class="content">Yes. You can manually mark students as Present, Absent, or Late in the attendance table. Bulk marking is also supported.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>7. What is the “Grace Period”? <span class="arrow"> ❯ </span></h3>
                <div class="content">It is the number of minutes allowed for students to arrive late but still be marked Present. Scans after this period are marked Late.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>8. What does “Late to Absent” mean? <span class="arrow"> ❯ </span></h3>
                <div class="content">This setting converts a number of late marks into one absence (e.g., 3 lates = 1 absence).</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>9. Can I edit attendance records from past dates? <span class="arrow"> ❯ </span></h3>
                <div class="content">No. Attendance can only be marked or modified for the current date. Past attendance is view-only.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>10. How do I generate reports? <span class="arrow"> ❯ </span></h3>
                <div class="content">Go to the Reports section, set filters (class, student, date range, type), generate the report, and export it as PDF or Excel.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>11. Why can’t I import my Excel file? <span class="arrow"> ❯ </span></h3>
                <div class="content">Ensure the file is in .xlsx or .xls format and includes all required columns with the correct headers.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>12. Why is my QR code not generating? <span class="arrow"> ❯ </span></h3>
                <div class="content">Check that the LRN, first name, and last name fields are filled in. QR codes are auto-generated when these fields are valid.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>13. Can I change my username or password? <span class="arrow"> ❯ </span></h3>
                <div class="content">Yes. In the Profile section, update your username or password. The system ensures usernames are unique, and passwords must match the confirmation field.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>14. How do I contact support? <span class="arrow"> ❯ </span></h3>
                <div class="content">For further assistance, email student.attendance.monitoring.sys@gmail.com</div>
            </div>
        </div>
    </section>
</body>

</html>