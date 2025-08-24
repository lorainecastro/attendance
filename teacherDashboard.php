<?php
require 'config.php';
session_start();

$currentUser = validateSession();
if (!$currentUser) {
    header("Location: index.php");
    exit;
}

$profileImageUrl = $currentUser['picture'] ?? 'no-icon.png';
$profileInitials = strtoupper(substr($currentUser['firstname'] ?? 'D', 0, 1) . substr($currentUser['lastname'] ?? 'S', 0, 1));
$profileName = htmlspecialchars($currentUser['firstname'] . ' ' . $currentUser['lastname'], ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance Monitoring System</title>

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
            --header-height: 70px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
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

        /* Header Styles */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background-color: var(--white);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 var(--spacing-lg);
            transition: var(--transition-normal);
        }

        .header.sidebar-collapsed {
            left: var(--sidebar-collapsed-width);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .menu-toggle {
            background: none;
            border: none;
            color: var(--medium-gray);
            font-size: var(--font-size-xl);
            cursor: pointer;
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            transition: var(--transition-fast);
        }

        .menu-toggle:hover {
            color: var(--primary-blue);
            background-color: var(--primary-blue-light);
        }

        .system-title {
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .system-title i {
            color: var(--primary-blue);
            font-size: var(--font-size-2xl);
        }

        .header-center {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
        }

        .datetime-widget {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            background-color: var(--primary-blue-light);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-lg);
            border: 1px solid var(--primary-blue);
        }

        .datetime-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: var(--primary-blue);
            font-weight: 500;
        }

        .datetime-item i {
            font-size: var(--font-size-sm);
        }

        .datetime-text {
            font-size: var(--font-size-sm);
            font-weight: 600;
        }

        .datetime-separator {
            width: 1px;
            height: 20px;
            background-color: var(--primary-blue);
            opacity: 0.3;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            color: var(--medium-gray);
            font-size: var(--font-size-lg);
            cursor: pointer;
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            transition: var(--transition-fast);
        }

        .notification-btn:hover {
            color: var(--primary-blue);
            background-color: var(--primary-blue-light);
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background-color: var(--danger-red);
            color: var(--white);
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 50px;
            min-width: 18px;
            text-align: center;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-btn {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            background: none;
            border: none;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .profile-btn:hover {
            background-color: var(--primary-blue-light);
        }

        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--border-color);
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .profile-name {
            font-weight: 600;
            color: var(--dark-gray);
            font-size: var(--font-size-sm);
        }

        .profile-role {
            font-size: 0.75rem;
            color: var(--medium-gray);
        }

        /* Profile Dropdown Menu */
        .profile-dropdown-menu {
            position: absolute;
            top: calc(100% + var(--spacing-sm));
            right: 0;
            background-color: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            min-width: 200px;
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: var(--transition-fast);
        }

        .profile-dropdown.active .profile-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-dropdown-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            color: var(--medium-gray);
            text-decoration: none;
            font-size: var(--font-size-sm);
            transition: var(--transition-fast);
        }

        .profile-dropdown-item:hover {
            background-color: var(--primary-blue-light);
            color: var(--primary-blue);
        }

        .profile-dropdown-item.logout {
            color: var(--danger-red);
            border-top: 1px solid var(--border-color);
        }

        .profile-dropdown-item.logout:hover {
            background-color: #fef2f2;
            color: var(--danger-red);
        }

        .profile-dropdown-item i {
            width: 16px;
            text-align: center;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--white);
            border-right: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            z-index: 999;
            transition: var(--transition-normal);
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            min-height: var(--header-height);
            padding: 10px;
        }

        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-blue), var(--info-cyan));
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: var(--font-size-xl);
            flex-shrink: 0;
        }

        .sidebar-brand {
            font-size: var(--font-size-lg);
            font-weight: 700;
            color: var(--dark-gray);
            white-space: nowrap;
            opacity: 1;
            transition: var(--transition-normal);
        }

        .sidebar.collapsed .sidebar-brand {
            opacity: 0;
            width: 0;
        }

        .sidebar-nav {
            padding: var(--spacing-lg) 0;
        }

        .nav-section {
            margin-bottom: var(--spacing-xl);
        }

        .nav-section-title {
            padding: 0 var(--spacing-lg);
            margin-bottom: var(--spacing-md);
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--medium-gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: var(--transition-normal);
        }

        .sidebar.collapsed .nav-section-title {
            opacity: 0;
            height: 0;
            margin: 0;
            padding: 0;
        }

        .nav-item {
            margin-bottom: var(--spacing-xs);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md) var(--spacing-lg);
            color: var(--medium-gray);
            text-decoration: none;
            font-weight: 500;
            font-size: var(--font-size-sm);
            transition: var(--transition-fast);
            position: relative;
        }

        .nav-link:hover {
            background-color: var(--primary-blue-light);
            color: var(--primary-blue);
        }

        .nav-link.active {
            background-color: var(--primary-blue-light);
            color: var(--primary-blue);
            font-weight: 600;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background-color: var(--primary-blue);
        }

        .nav-icon {
            font-size: var(--font-size-lg);
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .nav-text {
            white-space: nowrap;
            transition: var(--transition-normal);
        }

        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
        }

        .nav-badge {
            margin-left: auto;
            background-color: var(--primary-blue);
            color: var(--white);
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 50px;
            min-width: 20px;
            text-align: center;
            transition: var(--transition-normal);
        }

        .sidebar.collapsed .nav-badge {
            opacity: 0;
            width: 0;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 0;
            transition: var(--transition-normal);
            min-height: calc(100vh - var(--header-height));
        }

        .main-content.sidebar-collapsed {
            margin-left: var(--sidebar-collapsed-width);
        }

        .dashboard-body {
            display: flex;
            flex-grow: 1;
            overflow: hidden;
            padding: 0;
            height: 100%;
        }

        #dashboard-frame {
            flex-grow: 1;
            width: 100%;
            height: 100%;
            border: none;
            /* overflow: hidden; */
        }


@media (max-width: 1023px) {
    .main-content {
        margin-left: 0;
    }
}

@media (max-width: 767px) {
    .main-content {
        padding: 0;
    }

    .dashboard-body, #dashboard-frame {
        height: 100%;
    }
}

        /* Notification Modal */
        .notification-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1002;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-fast);
        }

        .notification-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .notification-modal-content {
            background-color: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .notification-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
        }

        .notification-modal-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--dark-gray);
        }

        .notification-modal-close {
            background: none;
            border: none;
            color: var(--medium-gray);
            font-size: var(--font-size-lg);
            cursor: pointer;
            padding: var(--spacing-sm);
            transition: var(--transition-fast);
        }

        .notification-modal-close:hover {
            color: var(--danger-red);
        }

        .notification-modal-body {
            padding: var(--spacing-md);
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition-fast);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background-color: var(--primary-blue-light);
        }

        .notification-icon {
            color: var(--danger-red);
            font-size: var(--font-size-lg);
            margin-top: var(--spacing-xs);
        }

        .notification-details {
            flex: 1;
        }

        .notification-message {
            font-size: var(--font-size-sm);
            color: var(--dark-gray);
            margin-bottom: var(--spacing-xs);
        }

        .notification-meta {
            font-size: 0.75rem;
            color: var(--medium-gray);
        }

        /* Responsive Design */
        @media (max-width: 1023px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .header {
                left: 0;
            }

            .profile-info {
                display: none;
            }

            .datetime-widget {
                display: none;
            }
        }

        @media (max-width: 767px) {
            .header {
                padding: 0 var(--spacing-md);
            }

            .system-title span {
                display: none;
            }

            .main-content {
                padding: var(--spacing-lg);
            }

            .header-center {
                display: none;
            }

            .notification-modal-content {
                max-width: 90%;
            }
        }

        /* Mobile Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-fast);
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Tooltip for collapsed sidebar */
        .nav-tooltip {
            position: absolute;
            left: calc(100% + var(--spacing-sm));
            top: 50%;
            transform: translateY(-50%);
            background-color: var(--dark-gray);
            color: var(--white);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-fast);
            z-index: 1001;
        }

        .nav-tooltip::before {
            content: '';
            position: absolute;
            left: -4px;
            top: 50%;
            transform: translateY(-50%);
            border: 4px solid transparent;
            border-right-color: var(--dark-gray);
        }

        .sidebar.collapsed .nav-link:hover .nav-tooltip {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="sidebar-brand">SAMS</div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <div class="nav-item">
                    <a href="#" class="nav-link menu-item active">
                        <i class="fas fa-tachometer-alt nav-icon"></i>
                        <span class="nav-text">Dashboard</span>
                        <span class="nav-tooltip">Dashboard</span>
                    </a>
                </div>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Management</div>
                <div class="nav-item">
                    <a href="#" class="nav-link menu-item">
                        <i class="fas fa-chalkboard-teacher nav-icon"></i>
                        <span class="nav-text">Manage Classes</span>
                        <span class="nav-tooltip">Manage Classes</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="#" class="nav-link menu-item">
                        <i class="fas fa-users nav-icon"></i>
                        <span class="nav-text">Manage Students</span>
                        <span class="nav-tooltip">Manage Students</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="#" class="nav-link menu-item">
                        <i class="fas fa-user-check nav-icon"></i>
                        <span class="nav-text">Attendance Tracking</span>
                        <span class="nav-tooltip">Attendance Tracking</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link menu-item">
                        <i class="fas fa-clipboard-list nav-icon"></i>
                        <span class="nav-text">Overall Attendance</span>
                        <span class="nav-tooltip">Overall Attendance</span>
                    </a>
                </div>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Analytics</div>
                <div class="nav-item">
                    <a href="#" class="nav-link menu-item">
                        <i class="fas fa-chart-bar nav-icon"></i>
                        <span class="nav-text">Analytics & Predictions</span>
                        <span class="nav-tooltip">Analytics & Predictions</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="#" class="nav-link menu-item">
                        <i class="fas fa-file-export nav-icon"></i>
                        <span class="nav-text">Reports & Export</span>
                        <span class="nav-tooltip">Reports & Export</span>
                    </a>
                </div>
            </div>
        </nav>
    </aside>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Header -->
    <header class="header" id="header">
        <div class="header-left">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="system-title">
                <i class="fas fa-users"></i>
                <span>Student Attendance Monitoring System</span>
            </div>
        </div>

        <div class="header-center">
            <div class="datetime-widget">
                <div class="datetime-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="datetime-text" id="currentDate">Loading...</span>
                </div>
                <div class="datetime-separator"></div>
                <div class="datetime-item">
                    <i class="fas fa-clock"></i>
                    <span class="datetime-text" id="currentTime">Loading...</span>
                </div>
                <div class="datetime-separator"></div>
                <div class="datetime-item">
                    <i class="fas fa-sun"></i>
                    <span class="datetime-text" id="currentDay">Loading...</span>
                </div>
            </div>
        </div>

        <div class="header-right">
            <button class="notification-btn" id="notificationBtn">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationCount">0</span>
            </button>

            <div class="profile-dropdown" id="profileDropdown">
                <button class="profile-btn" onclick="toggleProfileDropdown()">
                    <img src="uploads/<?php echo htmlspecialchars($currentUser['picture'] ?? 'uploads/no-icon.png'); ?>" alt="Profile" class="profile-avatar">
                    <div class="profile-info">
                        <div class="profile-name"><?php echo htmlspecialchars($profileName); ?></div>
                        <div class="profile-role">Teacher</div>
                    </div>
                    <i class="fas fa-chevron-down" style="color: var(--medium-gray); font-size: 0.875rem;"></i>
                </button>

                <div class="profile-dropdown-menu">
                    <a href="#" class="profile-dropdown-item menu-item">
                        <i class="fas fa-user"></i>
                        <span>View Profile</span>
                    </a>

                    <a href="#" class="profile-dropdown-item menu-item">
                        <i class="fas fa-question-circle"></i>
                        <span>Help & Support</span>
                    </a>

                    <a href="#" class="profile-dropdown-item logout menu-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Notification Modal -->
    <div class="notification-modal" id="notificationModal">
        <div class="notification-modal-content">
            <div class="notification-modal-header">
                <h2 class="notification-modal-title">Notifications</h2>
                <button class="notification-modal-close" id="notificationModalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="notification-modal-body" id="notificationList">
                <!-- Notifications will be populated here -->
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="dashboard-body">
            <iframe id="dashboard-frame" src="./teacher/dashboard.php" frameborder="0"></iframe>
        </div>
    </main>

    <script>
        // Global state
        let sidebarCollapsed = false;
        let isMobile = window.innerWidth < 1024;

        // Data from Class Management
        let classes = [{
                id: 1,
                code: 'MATH-101-A',
                sectionName: 'Diamond Section',
                subject: 'Mathematics',
                gradeLevel: 'Grade 7',
                room: 'Room 201',
                attendancePercentage: 10,
                schedule: {
                    monday: {
                        start: '08:00',
                        end: '09:30'
                    },
                    wednesday: {
                        start: '08:00',
                        end: '09:30'
                    },
                    friday: {
                        start: '08:00',
                        end: '09:30'
                    }
                },
                status: 'active',
                students: [{
                        id: 1,
                        firstName: 'John',
                        lastName: 'Doe',
                        email: 'john.doe@email.com'
                    },
                    {
                        id: 2,
                        firstName: 'Jane',
                        lastName: 'Smith',
                        email: 'jane.smith@email.com'
                    },
                    {
                        id: 3,
                        firstName: 'Mike',
                        lastName: 'Johnson',
                        email: 'mike.johnson@email.com'
                    }
                ]
            },
            {
                id: 2,
                code: 'SCI-201-B',
                sectionName: 'Einstein Section',
                subject: 'Science',
                gradeLevel: 'Grade 10',
                room: 'Lab 1',
                attendancePercentage: 15,
                schedule: {
                    tuesday: {
                        start: '10:00',
                        end: '11:30'
                    },
                    thursday: {
                        start: '10:00',
                        end: '11:30'
                    }
                },
                status: 'active',
                students: [{
                        id: 4,
                        firstName: 'Alice',
                        lastName: 'Brown',
                        email: 'alice.brown@email.com'
                    },
                    {
                        id: 5,
                        firstName: 'Bob',
                        lastName: 'Wilson',
                        email: 'bob.wilson@email.com'
                    }
                ]
            },
            {
                id: 3,
                code: 'ENG-301-C',
                sectionName: 'Shakespeare Section',
                subject: 'English Literature',
                gradeLevel: 'Grade 12',
                room: 'Room 305',
                attendancePercentage: 20,
                schedule: {
                    monday: {
                        start: '14:00',
                        end: '15:30'
                    },
                    wednesday: {
                        start: '14:00',
                        end: '15:30'
                    }
                },
                status: 'inactive',
                students: [{
                        id: 6,
                        firstName: 'Carol',
                        lastName: 'Davis',
                        email: 'carol.davis@email.com'
                    },
                    {
                        id: 7,
                        firstName: 'David',
                        lastName: 'Miller',
                        email: 'david.miller@email.com'
                    },
                    {
                        id: 8,
                        firstName: 'Emma',
                        lastName: 'Garcia',
                        email: 'emma.garcia@email.com'
                    },
                    {
                        id: 9,
                        firstName: 'Frank',
                        lastName: 'Rodriguez',
                        email: 'frank.rodriguez@email.com'
                    }
                ]
            }
        ];

        // Student data
        let students = classes.flatMap(cls => cls.students.map(student => ({
            id: student.id,
            firstName: student.firstName,
            lastName: student.lastName,
            email: student.email || '',
            fullName: `${student.firstName} ${student.lastName}`,
            gender: student.gender || 'Male',
            dob: student.dob || '2010-01-01',
            gradeLevel: cls.gradeLevel,
            class: cls.subject,
            section: cls.sectionName,
            address: student.address || '123 Sample St',
            parentName: student.parentName || 'Parent Name',
            emergencyContact: student.emergencyContact || '09234567890',
            attendanceRate: student.attendanceRate || Math.floor(Math.random() * 20) + 70, // Random attendance rate for demo
            dateAdded: student.dateAdded || '2024-09-01',
            photo: student.photo || 'https://via.placeholder.com/100'
        })));

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            updateLayout();
            updateDateTime();
            updateNotifications();
            window.addEventListener('resize', handleResize);

            // Update time every second
            setInterval(updateDateTime, 1000);

            // Close modals/dropdowns on outside click
            document.addEventListener('click', (e) => {
                const profileDropdown = document.getElementById('profileDropdown');
                const notificationModal = document.getElementById('notificationModal');
                const notificationBtn = document.getElementById('notificationBtn');

                if (profileDropdown && !profileDropdown.contains(e.target)) {
                    profileDropdown.classList.remove('active');
                }

                if (notificationModal && !notificationModal.contains(e.target) && !notificationBtn.contains(e.target)) {
                    notificationModal.classList.remove('active');
                }
            });

            // Menu item click handlers
            document.querySelectorAll('.menu-item').forEach((menuItem, index) => {
                menuItem.addEventListener('click', (e) => {
                    e.preventDefault();
                    setActiveMenuItem(menuItem);

                    let pageFile;
                    switch (index) {
                        case 0:
                            pageFile = 'dashboard.php';
                            break;
                        case 1:
                            pageFile = 'manage-classes.php';
                            break;
                        case 2:
                            pageFile = 'manage-students.php';
                            break;
                        case 3:
                            pageFile = 'attendance.php';
                            break;
                        case 4:
                            pageFile = 'overall-attendance.php';
                            break;
                        case 5:
                            pageFile = 'analytics.php';
                            break;
                        case 6:
                            pageFile = 'reports.php';
                            break;
                        case 7:
                            pageFile = 'profile.php';
                            break;
                        case 8:
                            pageFile = 'help-support.php';
                            break;
                        case 9:
                            if (confirm('Are you sure you want to log out?')) {
                                window.location.href = 'destroyer.php';
                            }
                            return;
                        default:
                            pageFile = '404.html';
                    }
                    loadPage(pageFile);
                });
            });

            // Notification button handler
            const notificationBtn = document.getElementById('notificationBtn');
            if (notificationBtn) {
                notificationBtn.addEventListener('click', toggleNotificationModal);
            }

            // Notification close button handler
            const closeBtn = document.getElementById('notificationModalClose');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    document.getElementById('notificationModal').classList.remove('active');
                });
            }

            // Load default page
            loadPage('dashboard.php');
        });

        // Load page into iframe
        function loadPage(pageFile) {
            const iframe = document.getElementById('dashboard-frame');
            if (iframe) {
                iframe.src = `${pageFile}`;
            }
        }

        // Set active menu item
        function setActiveMenuItem(clickedItem) {
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            clickedItem.classList.add('active');
        }

        // Update date and time
        function updateDateTime() {
            const now = new Date();
            const dateOptions = {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            };
            const timeOptions = {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const dayOptions = {
                weekday: 'long'
            };

            const currentDate = document.getElementById('currentDate');
            const currentTime = document.getElementById('currentTime');
            const currentDay = document.getElementById('currentDay');

            if (currentDate) currentDate.textContent = now.toLocaleDateString('en-US', dateOptions);
            if (currentTime) currentTime.textContent = now.toLocaleTimeString('en-US', timeOptions);
            if (currentDay) currentDay.textContent = now.toLocaleDateString('en-US', dayOptions);
        }

        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const header = document.getElementById('header');
            const mainContent = document.getElementById('mainContent');
            const overlay = document.getElementById('sidebarOverlay');

            if (isMobile) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
            } else {
                sidebarCollapsed = !sidebarCollapsed;
                sidebar.classList.toggle('collapsed');
                header.classList.toggle('sidebar-collapsed');
                mainContent.classList.toggle('sidebar-collapsed');
            }
        }

        // Toggle profile dropdown
        function toggleProfileDropdown() {
            const profileDropdown = document.getElementById('profileDropdown');
            if (profileDropdown) {
                profileDropdown.classList.toggle('active');
            }
        }

        // Toggle notification modal
        function toggleNotificationModal() {
            const notificationModal = document.getElementById('notificationModal');
            if (notificationModal) {
                notificationModal.classList.toggle('active');
                updateNotifications(); // Refresh notifications when opening
            }
        }

        // Update notifications
        function updateNotifications() {
            const notificationList = document.getElementById('notificationList');
            const notificationCount = document.getElementById('notificationCount');

            if (!notificationList || !notificationCount) return;

            // Filter students with low attendance (e.g., < 80%) and select up to 3
            const atRiskStudents = students
                .filter(student => student.attendanceRate < 80)
                .sort(() => Math.random() - 0.5)
                .slice(0, Math.min(3, students.length))
                .map(student => ({
                    id: student.id,
                    fullName: student.fullName,
                    gradeLevel: student.gradeLevel,
                    subject: student.class,
                    section: student.section,
                    message: `${student.fullName} is at risk due to excessive absences (Attendance: ${student.attendanceRate}%).`,
                    timestamp: new Date().toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    })
                }));

            notificationCount.textContent = atRiskStudents.length;

            notificationList.innerHTML = atRiskStudents.length === 0 ?
                '<div class="notification-item"><div class="notification-details"><div class="notification-message">No new notifications.</div></div></div>' :
                atRiskStudents.map(student => `
                    <div class="notification-item">
                        <i class="fas fa-exclamation-triangle notification-icon"></i>
                        <div class="notification-details">
                            <div class="notification-message">${student.message}</div>
                            <div class="notification-meta">
                                Grade: ${student.gradeLevel} | Subject: ${student.subject} | Section: ${student.section} | ${student.timestamp}
                            </div>
                        </div>
                    </div>
                `).join('');
        }

        // Handle window resize
        function handleResize() {
            const wasMobile = isMobile;
            isMobile = window.innerWidth < 1024;
            if (wasMobile !== isMobile) {
                updateLayout();
            }
        }

        // Update layout based on screen size
        function updateLayout() {
            const sidebar = document.getElementById('sidebar');
            const header = document.getElementById('header');
            const mainContent = document.getElementById('mainContent');
            const overlay = document.getElementById('sidebarOverlay');

            if (isMobile) {
                sidebar.classList.remove('collapsed');
                sidebar.classList.remove('mobile-open');
                header.classList.remove('sidebar-collapsed');
                mainContent.classList.remove('sidebar-collapsed');
                overlay.classList.remove('active');
                sidebarCollapsed = false;
            } else {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                if (sidebarCollapsed) {
                    sidebar.classList.add('collapsed');
                    header.classList.add('sidebar-collapsed');
                    mainContent.classList.add('sidebar-collapsed');
                } else {
                    sidebar.classList.remove('collapsed');
                    header.classList.remove('sidebar-collapsed');
                    mainContent.classList.remove('sidebar-collapsed');
                }
            }
        }

        // Adjust iframe height
        const iframe = document.getElementById('dashboard-frame');
        if (iframe) {
            iframe.onload = () => {
    const contentHeight = iframe.contentWindow.document.body.scrollHeight;
    iframe.style.height = Math.max(contentHeight, window.innerHeight - 70) + 'px';
};
        }
    </script>
</body>

</html>