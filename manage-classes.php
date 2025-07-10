<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        :root {
            /* Primary Colors */
            --primary-blue: #3b82f6;
            --primary-blue-hover: #2563eb;
            --primary-blue-light: #dbeafe;
            
            /* Status Colors */
            --success-green: #22c55e;
            --warning-yellow: #f59e0b;
            --danger-red: #ef4444;
            --info-cyan: #06b6d4;
            
            /* Neutral Colors */
            --dark-gray: #1f2937;
            --medium-gray: #6b7280;
            --light-gray: #e5e7eb;
            --background: #f9fafb;
            --white: #ffffff;
            --border-color: #e2e8f0;
    
            /* Additional Colors */
            --card-bg: #ffffff;
            --blackfont-color: #1e293b;
            --whitefont-color: #ffffff;
            --grayfont-color: #64748b;
            --primary-gradient: linear-gradient(135deg, #3b82f6, #8b5cf6);
            --secondary-gradient: linear-gradient(135deg, #ec4899, #f472b6);
            --inputfield-color: #f8fafc;
            --inputfieldhover-color: #f1f5f9;
            
            /* Typography */
            --font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.875rem;
    
            /* Spacing */
            --spacing-xs: 0.5rem;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            
            /* Shadows */
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.1);
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
    
            /* Transitions */
            --transition-fast: 0.2s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
            --transition-slow: 0.5s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }

        body {
            background-color: var(--card-bg);
            color: var(--blackfont-color);
            /* min-height: 100vh; */
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

        /* Controls */
        .controls {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-2xl);
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--spacing-lg);
            align-items: center;
            border: 1px solid var(--border-color);
        }

        .controls-left {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .controls-right {
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }

        .search-container {
            position: relative;
            min-width: 280px;
        }

        .search-input {
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-md) var(--spacing-sm) 2.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .search-icon {
            position: absolute;
            left: var(--spacing-md);
            top: 50%;
            transform: translateY(-50%);
            color: var(--grayfont-color);
            font-size: 1rem;
        }

        .form-input, .form-select {
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .form-input:disabled, .form-select:disabled {
            background: var(--light-gray);
            opacity: 0.7;
            cursor: not-allowed;
        }

        .filter-select {
            min-width: 140px;
        }

        /* Buttons */
        .btn {
            padding: var(--spacing-sm) var(--spacing-lg);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: var(--whitefont-color);
        }

        .btn-primary:hover {
            background: var(--primary-blue-hover);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--medium-gray);
            color: var(--whitefont-color);
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, #22c55e, #4ade80);
            color: var(--whitefont-color);
        }

        .btn-success:hover {
            background: var(--success-green);
            transform: translateY(-2px);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: var(--whitefont-color);
        }

        .btn-warning:hover {
            background: var(--warning-yellow);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: var(--whitefont-color);
        }

        .btn-danger:hover {
            background: var(--danger-red);
            transform: translateY(-2px);
        }

        .btn-info {
            background: linear-gradient(135deg, #06b6d4, #22d3ee);
            color: var(--whitefont-color);
        }

        .btn-info:hover {
            background: var(--info-cyan);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: var(--spacing-xs) var(--spacing-sm);
            font-size: 0.75rem;
        }

        /* View Toggle */
        .view-toggle {
            display: flex;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
            background: var(--inputfield-color);
        }

        .view-btn {
            padding: var(--spacing-sm) var(--spacing-md);
            background: transparent;
            border: none;
            cursor: pointer;
            transition: var(--transition-normal);
            color: var(--grayfont-color);
            font-size: 1rem;
        }

        .view-btn:hover {
            background: var(--inputfieldhover-color);
        }

        .view-btn.active {
            background: var(--primary-gradient);
            color: var(--whitefont-color);
        }

        /* Grid View */
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: var(--spacing-lg);
        }

        .class-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .class-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }

        .class-header h3 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--blackfont-color);
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge.active {
            background-color: rgba(34, 197, 94, 0.1);
            color: var(--success-green);
        }

        .status-badge.inactive {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-red);
        }

        .class-info {
            margin-bottom: var(--spacing-md);
        }

        .class-info h4 {
            font-size: var(--font-size-base);
            font-weight: 500;
            margin-bottom: var(--spacing-sm);
            color: var(--blackfont-color);
        }

        .class-info p {
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
            font-size: var(--font-size-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .class-info i {
            width: 16px;
            color: var(--primary-blue);
        }

        Avast logo

        .class-schedule {
            margin-bottom: var(--spacing-md);
        }

        .class-schedule h5 {
            font-size: var(--font-size-base);
            font-weight: 500;
            margin-bottom: var(--spacing-sm);
            color: var(--blackfont-color);
        }

        .schedule-item {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
        }

        .no-schedule {
            color: var(--grayfont-color);
            font-style: italic;
        }

        .class-actions {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        /* Table View */
        .table-container {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            overflow-x: auto;
            border: 1px solid var(--border-color);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th,
        .table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            font-weight: 600;
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
        }

        .table tr:hover {
            background: var(--inputfieldhover-color);
        }

        .actions {
            display: flex;
            gap: var(--spacing-xs);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--card-bg);
            margin: 0 auto;
            padding: 0;
            border-radius: var(--radius-lg);
            max-width: 640px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .modal-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--inputfield-color);
        }

        .modal-title {
            margin: 0;
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: var(--blackfont-color);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--grayfont-color);
            padding: var(--spacing-xs);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            transition: var(--transition-normal);
        }

        .close-btn:hover {
            background: var(--inputfieldhover-color);
            color: var(--blackfont-color);
        }

        .modal form {
            padding: var(--spacing-lg);
        }

        .form-group {
            margin-bottom: var(--spacing-md);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-weight: 500;
            color: var(--blackfont-color);
            font-size: var(--font-size-sm);
        }

        .schedule-inputs {
            display: grid;
            gap: var(--spacing-sm);
        }

        .schedule-day-input {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .schedule-day-input:hover {
            background: var(--inputfieldhover-color);
        }

        .schedule-day-input input[type="checkbox"] {
            margin-right: var(--spacing-sm);
            accent-color: var(--primary-blue);
        }

        .schedule-day-input label {
            min-width: 90px;
            margin: 0;
            font-weight: 500;
            color: var(--blackfont-color);
        }

        .schedule-day-input input[type="time"] {
            padding: var(--spacing-xs) var(--spacing-sm);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--white);
            transition: var(--transition-normal);
        }

        .schedule-day-input input[type="time"]:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px var(--primary-blue-light);
        }

        .schedule-day-input input[type="time"]:disabled {
            background: var(--light-gray);
            opacity: 0.7;
        }

        .schedule-day-input span {
            color: var(--grayfont-color);
            font-size12: var(--font-size-sm);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--border-color);
        }

        /* File Upload */
        .file-upload {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-btn {
            cursor: pointer;
        }

        /* Import Preview */
        .import-preview {
            margin-top: var(--spacing-lg);
            padding: var(--spacing-md);
            background: var(--inputfield-color);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .import-preview h3 {
            margin-bottom: var(--spacing-md);
            color: var(--blackfont-color);
            font-size: var(--font-size-base);
            font-weight: 500;
        }

        #previewTable, #studentPreviewTable {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--font-size-sm);
        }

        #previewTable th,
        #previewTable td,
        #studentPreviewTable th,
        #studentPreviewTable td {
            padding: var(--spacing-sm);
            border: 1px solid var(--border-color);
            text-align: left;
        }

        #previewTable th,
        #studentPreviewTable th {
            background: var(--inputfield-color);
            font-weight: 600;
            color: var(--grayfont-color);
        }

        /* View Details */
        .view-details {
            padding: var(--spacing-lg);
        }

        .detail-row {
            display: flex;
            margin-bottom: var(--spacing-md);
            align-items: flex-start;
            gap: var(--spacing-sm);
        }

        .detail-row strong {
            min-width: 160px;
            color: var(--blackfont-color);
            font-weight: 500;
        }

        .schedule-details {
            margin-left: 160px;
        }

        /* Utility Classes */
        .hidden {
            display: none !important;
        }

        .no-classes {
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--grayfont-color);
            font-style: italic;
            font-size: var(--font-size-base);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .controls {
                grid-template-columns: 1fr;
            }

            .controls-right {
                justify-content: flex-start;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: var(--spacing-md);
            }

            .controls-left {
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            .controls-right {
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            .search-container {
                min-width: auto;
            }

            .class-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-md);
            }

            .class-card {
                padding: var(--spacing-md);
            }

            .class-actions {
                flex-direction: column;
            }

            .class-actions .btn {
                justify-content: center;
            }

            .modal-content {
                width: 95%;
                max-height: 95vh;
            }

            .modal-header {
                padding: var(--spacing-md);
            }

            .modal form {
                padding: var(--spacing-md);
            }

            .schedule-day-input {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-xs);
            }

            .schedule-day-input input[type="time"] {
                width: 100%;
            }

            .form-actions {
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .detail-row {
                flex-direction: column;
                gap: var(--spacing-xs);
            }

            .detail-row strong {
                min-width: auto;
            }

            .schedule-details {
                margin-left: 0;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: var(--spacing-sm);
            }

            h1 {
                font-size: var(--font-size-xl);
            }

            .class-card {
                padding: var(--spacing-sm);
            }

            .modal-content {
                width: 98%;
                max-height: 98vh;
            }

            .btn {
                padding: var(--spacing-md);
                justify-content: center;
            }

            .view-toggle {
                width: 100%;
            }

            .view-btn {
                flex: 1;
                justify-content: center;
            }
        }

        /* Table Responsive Improvements */
        @media (max-width: 768px) {
            .table th:nth-child(n+6),
            .table td:nth-child(n+6) {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .table th:nth-child(n+4),
            .table td:nth-child(n+4) {
                display: none;
            }
        }

        /* Print Styles */
        @media print {
            .controls,
            .class-actions,
            .actions,
            .modal {
                display: none !important;
            }

            body {
                padding: 0;
            }

            .class-card {
                box-shadow: none;
                border: 1px solid var(--border-color);
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <h1>Class Management</h1>
    <div class="container">
        <div class="controls">
            <div class="controls-left">
                <div class="search-container">
                    <input type="text" class="form-input search-input" placeholder="Search classes..." id="searchInput">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <select class="form-select filter-select" id="gradeFilter">
                    <option value="">All Grades</option>
                    <option value="Grade 7">Grade 7</option>
                    <option value="Grade 8">Grade 8</option>
                    <option value="Grade 9">Grade 9</option>
                    <option value="Grade 10">Grade 10</option>
                    <option value="Grade 11">Grade 11</option>
                    <option value="Grade 12">Grade 12</option>
                </select>
                <select class="form-select filter-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select class="form-select filter-select" id="subjectFilter">
                    <option value="">All Subjects</option>
                </select>
                <select class="form-select filter-select" id="sectionFilter">
                    <option value="">All Sections</option>
                </select>
            </div>
            <div class="controls-right">
                <div class="view-toggle">
                    <button class="view-btn active" onclick="switchView('grid')">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button class="view-btn" onclick="switchView('table')">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Add Class
                </button>
            </div>
        </div>

        <!-- Grid View -->
        <div id="gridView" class="class-grid">
            <!-- Classes will be rendered here -->
        </div>

        <!-- Table View -->
        <div id="tableView" class="table-container hidden">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th>Class Code & Section</th>
                        <th>Grade Level</th>
                        <th>Subject</th>
                        <th>Schedule</th>
                        <th>Room</th>
                        <th>Students</th>
                        <th>Attendance %</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Table rows will be rendered here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Class Modal -->
    <div id="classModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add New Class</h2>
                <button class="close-btn" onclick="closeModal()">×</button>
            </div>
            <form id="classForm">
                <div class="form-group">
                    <label class="form-label" for="classCode">Class Code</label>
                    <input type="text" class="form-input" id="classCode" required placeholder="e.g., MATH-101-A">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sectionName">Section Name</label>
                    <input type="text" class="form-input" id="sectionName" required placeholder="e.g., Section A, Diamond, Einstein">
                </div>
                <div class="form-group">
                    <label class="form-label" for="subject">Subject</label>
                    <input type="text" class="form-input" id="subject" required placeholder="e.g., Mathematics, Science, English">
                </div>
                <div class="form-group">
                    <label class="form-label" for="gradeLevel">Grade Level</label>
                    <select class="form-select" id="gradeLevel" required>
                        <option value="">Select Grade</option>
                        <option value="Grade 7">Grade 7</option>
                        <option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option>
                        <option value="Grade 10">Grade 10</option>
                        <option value="Grade 11">Grade 11</option>
                        <option value="Grade 12">Grade 12</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="room">Room</label>
                    <input type="text" class="form-input" id="room" placeholder="e.g., Room 201, Lab 1">
                </div>
                <div class="form-group">
                    <label class="form-label">Schedule</label>
                    <div class="schedule-inputs">
                        <div class="schedule-day-input">
                            <input type="checkbox" id="monday" name="scheduleDays">
                            <label for="monday">Monday</label>
                            <input type="time" id="mondayStart" name="mondayStart" disabled>
                            <span>to</span>
                            <input type="time" id="mondayEnd" name="mondayEnd" disabled>
                        </div>
                        <div class="schedule-day-input">
                            <input type="checkbox" id="tuesday" name="scheduleDays">
                            <label for="tuesday">Tuesday</label>
                            <input type="time" id="tuesdayStart" name="tuesdayStart" disabled>
                            <span>to</span>
                            <input type="time" id="tuesdayEnd" name="tuesdayEnd" disabled>
                        </div>
                        <div class="schedule-day-input">
                            <input type="checkbox" id="wednesday" name="scheduleDays">
                            <label for="wednesday">Wednesday</label>
                            <input type="time" id="wednesdayStart" name="wednesdayStart" disabled>
                            <span>to</span>
                            <input type="time" id="wednesdayEnd" name="wednesdayEnd" disabled>
                        </div>
                        <div class="schedule-day-input">
                            <input type="checkbox" id="thursday" name="scheduleDays">
                            <label for="thursday">Thursday</label>
                            <input type="time" id="thursdayStart" name="thursdayStart" disabled>
                            <span>to</span>
                            <input type="time" id="thursdayEnd" name="thursdayEnd" disabled>
                        </div>
                        <div class="schedule-day-input">
                            <input type="checkbox" id="friday" name="scheduleDays">
                            <label for="friday">Friday</label>
                            <input type="time" id="fridayStart" name="fridayStart" disabled>
                            <span>to</span>
                            <input type="time" id="fridayEnd" name="fridayEnd" disabled>
                        </div>
                        <div class="schedule-day-input">
                            <input type="checkbox" id="saturday" name="scheduleDays">
                            <label for="saturday">Saturday</label>
                            <input type="time" id="saturdayStart" name="saturdayStart" disabled>
                            <span>to</span>
                            <input type="time" id="saturdayEnd" name="saturdayEnd" disabled>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" required>
                        <option value="">Select Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Class</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student List Modal -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Import Student List</h2>
                <button class="close-btn" onclick="closeStudentModal()">×</button>
            </div>
            <div class="form-group">
                <label class="form-label">Choose Excel File with Student List</label>
                <div class="file-upload">
                    <input type="file" id="studentFile" accept=".xlsx,.xls" onchange="handleStudentFileUpload(event)">
                    <button type="button" class="btn btn-primary file-upload-btn" onclick="document.getElementById('studentFile').click()">
                        <i class="fas fa-upload"></i> Choose File
                    </button>
                </div>
                <small style="color: var(--grayfont-color); margin-top: var(--spacing-sm); display: block;">
                    Upload an Excel file with columns: Student ID, First Name, Last Name, Email (optional)
                </small>
            </div>
            <div id="studentPreview" class="import-preview hidden">
                <h3>Preview Students:</h3>
                <table id="studentPreviewTable">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeStudentModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="importStudentsBtn" onclick="importStudents()" disabled>
                    <i class="fas fa-user-plus"></i> Import Students
                </button>
            </div>
        </div>
    </div>

    <!-- View Class Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Class Details</h2>
                <button class="close-btn" onclick="closeViewModal()">×</button>
            </div>
            <div id="viewContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Enhanced class data structure with students
        let classes = [
            {
                id: 1,
                code: 'MATH-101-A',
                sectionName: 'Diamond Section',
                subject: 'Mathematics',
                gradeLevel: 'Grade 7',
                room: 'Room 201',
                attendancePercentage: 10,
                schedule: {
                    monday: { start: '08:00', end: '09:30' },
                    wednesday: { start: '08:00', end: '09:30' },
                    friday: { start: '08:00', end: '09:30' }
                },
                status: 'active',
                students: [
                    { id: 1, firstName: 'John', lastName: 'Doe', email: 'john.doe@email.com' },
                    { id: 2, firstName: 'Jane', lastName: 'Smith', email: 'jane.smith@email.com' },
                    { id: 3, firstName: 'Mike', lastName: 'Johnson', email: 'mike.johnson@email.com' }
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
                    tuesday: { start: '10:00', end: '11:30' },
                    thursday: { start: '10:00', end: '11:30' }
                },
                status: 'active',
                students: [
                    { id: 4, firstName: 'Alice', lastName: 'Brown', email: 'alice.brown@email.com' },
                    { id: 5, firstName: 'Bob', lastName: 'Wilson', email: 'bob.wilson@email.com' }
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
                    monday: { start: '14:00', end: '15:30' },
                    wednesday: { start: '14:00', end: '15:30' }
                },
                status: 'inactive',
                students: [
                    { id: 6, firstName: 'Carol', lastName: 'Davis', email: 'carol.davis@email.com' },
                    { id: 7, firstName: 'David', lastName: 'Miller', email: 'david.miller@email.com' },
                    { id: 8, firstName: 'Emma', lastName: 'Garcia', email: 'emma.garcia@email.com' },
                    { id: 9, firstName: 'Frank', lastName: 'Rodriguez', email: 'frank.rodriguez@email.com' }
                ]
            }
        ];

        let currentView = 'grid';
        let editingClassId = null;
        let currentClassForStudents = null;
        let importedStudentData = [];

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            renderClasses();
            populateFilters();
            setupEventListeners();
            clearScheduleInputs();
        });

        // Setup event listeners
        function setupEventListeners() {
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', handleSearch);
            
            // Filter functionality
            document.getElementById('gradeFilter').addEventListener('change', handleFilter);
            document.getElementById('statusFilter').addEventListener('change', handleFilter);
            document.getElementById('subjectFilter').addEventListener('change', handleFilter);
            document.getElementById('sectionFilter').addEventListener('change', handleFilter);
            
            // Form submission
            document.getElementById('classForm').addEventListener('submit', handleFormSubmit);
            
            // Schedule checkboxes
            const scheduleCheckboxes = document.querySelectorAll('input[name="scheduleDays"]');
            scheduleCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', handleScheduleToggle);
            });
            
            // Modal close on outside click
            window.addEventListener('click', function(event) {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (event.target === modal) {
                        modal.classList.remove('show');
                    }
                });
            });
        }

        // Render classes based on current view
        function renderClasses() {
            if (currentView === 'grid') {
                renderGridView();
            } else {
                renderTableView();
            }
        }

        // Render grid view
        function renderGridView() {
            const container = document.getElementById('gridView');
            const filteredClasses = getFilteredClasses();
            
            container.innerHTML = '';
            
            if (filteredClasses.length === 0) {
                container.innerHTML = '<div class="no-classes">No classes found</div>';
                return;
            }

            filteredClasses.forEach(classItem => {
                const scheduleText = formatSchedule(classItem.schedule);
                const attendancePercentage = calculateAttendancePercentage(classItem);
                
                const card = document.createElement('div');
                card.className = 'class-card';
                card.innerHTML = `
                    <div class="class-header">
                        <h3>${classItem.code}</h3>
                        <span class="status-badge ${classItem.status}">${classItem.status}</span>
                    </div>
                    <div class="class-info">
                        <h4>${classItem.sectionName}</h4>
                        <p><i class="fas fa-book"></i> ${classItem.subject}</p>
                        <p><i class="fas fa-graduation-cap"></i> ${classItem.gradeLevel}</p>
                        <p><i class="fas fa-map-marker-alt"></i> ${classItem.room}</p>
                        <p><i class="fas fa-users"></i> ${classItem.students.length} students</p>
                        <p><i class="fas fa-percentage"></i> ${attendancePercentage}% attendance</p>
                    </div>
                    <div class="class-schedule">
                        <h5>Schedule:</h5>
                        ${scheduleText}
                    </div>
                    <div class="class-actions">
                        <button class="btn btn-sm btn-info" onclick="viewClass(${classItem.id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="editClass(${classItem.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-success" onclick="openStudentModal(${classItem.id})">
                            <i class="fas fa-users"></i> Students
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteClass(${classItem.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        // Render table view
        function renderTableView() {
            const tbody = document.querySelector('#tableView tbody');
            const filteredClasses = getFilteredClasses();
            
            tbody.innerHTML = '';
            
            if (filteredClasses.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="no-classes">No classes found</td></tr>';
                return;
            }

            filteredClasses.forEach(classItem => {
                const scheduleText = formatScheduleShort(classItem.schedule);
                const attendancePercentage = calculateAttendancePercentage(classItem);
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox" class="row-checkbox" data-class-id="${classItem.id}"></td>
                    <td>
                        <strong>${classItem.code}</strong><br>
                        <small>${classItem.sectionName}</small>
                    </td>
                    <td>${classItem.gradeLevel}</td>
                    <td>${classItem.subject}</td>
                    <td>${scheduleText}</td>
                    <td>${classItem.room}</td>
                    <td>${classItem.students.length}</td>
                    <td>${attendancePercentage}%</td>
                    <td><span class="status-badge ${classItem.status}">${classItem.status}</span></td>
                    <td class="actions">
                        <button class="btn btn-sm btn-info" onclick="viewClass(${classItem.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="editClass(${classItem.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-success" onclick="openStudentModal(${classItem.id})">
                            <i class="fas fa-users"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteClass(${classItem.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Get filtered classes based on search and filters
        function getFilteredClasses() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const gradeFilter = document.getElementById('gradeFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const subjectFilter = document.getElementById('subjectFilter').value;
            const sectionFilter = document.getElementById('sectionFilter').value;
            
            return classes.filter(classItem => {
                const matchesSearch = searchTerm === '' || 
                    classItem.code.toLowerCase().includes(searchTerm) ||
                    classItem.sectionName.toLowerCase().includes(searchTerm) ||
                    classItem.subject.toLowerCase().includes(searchTerm);
                
                const matchesGrade = gradeFilter === '' || classItem.gradeLevel === gradeFilter;
                const matchesStatus = statusFilter === '' || classItem.status === statusFilter;
                const matchesSubject = subjectFilter === '' || classItem.subject === subjectFilter;
                const matchesSection = sectionFilter === '' || classItem.sectionName === sectionFilter;
                
                return matchesSearch && matchesGrade && matchesStatus && matchesSubject && matchesSection;
            });
        }

        // Populate filter options
        function populateFilters() {
            const subjects = [...new Set(classes.map(c => c.subject))];
            const sections = [...new Set(classes.map(c => c.sectionName))];
            
            const subjectFilter = document.getElementById('subjectFilter');
            const sectionFilter = document.getElementById('sectionFilter');
            
            subjectFilter.innerHTML = '<option value="">All Subjects</option>';
            sectionFilter.innerHTML = '<option value="">All Sections</option>';
            
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                subjectFilter.appendChild(option);
            });
            
            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionFilter.appendChild(option);
            });
        }

        // Handle search
        function handleSearch() {
            renderClasses();
        }

        // Handle filter changes
        function handleFilter() {
            renderClasses();
        }

        // Switch between grid and table views
        function switchView(view) {
            currentView = view;
            
            document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.view-btn').classList.add('active');
            
            const gridView = document.getElementById('gridView');
            const tableView = document.getElementById('tableView');
            
            if (view === 'grid') {
                gridView.classList.remove('hidden');
                tableView.classList.add('hidden');
            } else {
                gridView.classList.add('hidden');
                tableView.classList.remove('hidden');
            }
            
            renderClasses();
        }

        // Open modal for adding new class
        function openModal() {
            editingClassId = null;
            document.getElementById('modalTitle').textContent = 'Add New Class';
            document.getElementById('classForm').reset();
            clearScheduleInputs();
            document.getElementById('classModal').classList.add('show');
        }

        // Close modal
        function closeModal() {
            document.getElementById('classModal').classList.remove('show');
            editingClassId = null;
        }

        // Edit class
        function editClass(classId) {
            const classItem = classes.find(c => c.id === classId);
            if (!classItem) return;
            
            editingClassId = classId;
            document.getElementById('modalTitle').textContent = 'Edit Class';
            
            document.getElementById('classCode').value = classItem.code;
            document.getElementById('sectionName').value = classItem.sectionName;
            document.getElementById('subject').value = classItem.subject;
            document.getElementById('gradeLevel').value = classItem.gradeLevel;
            document.getElementById('room').value = classItem.room;
            document.getElementById('status').value = classItem.status;
            
            clearScheduleInputs();
            Object.keys(classItem.schedule).forEach(day => {
                const checkbox = document.getElementById(day);
                const startInput = document.getElementById(day + 'Start');
                const endInput = document.getElementById(day + 'End');
                
                if (checkbox && startInput && endInput) {
                    checkbox.checked = true;
                    startInput.disabled = false;
                    endInput.disabled = false;
                    startInput.value = classItem.schedule[day].start;
                    endInput.value = classItem.schedule[day].end;
                }
            });
            
            document.getElementById('classModal').classList.add('show');
        }

        // View class details
        function viewClass(classId) {
            const classItem = classes.find(c => c.id === classId);
            if (!classItem) return;
            
            const scheduleText = formatSchedule(classItem.schedule);
            const attendancePercentage = calculateAttendancePercentage(classItem);
            
            const content = document.getElementById('viewContent');
            content.innerHTML = `
                <div class="view-details">
                    <div class="detail-row">
                        <strong>Class Code:</strong> ${classItem.code}
                    </div>
                    <div class="detail-row">
                        <strong>Section Name:</strong> ${classItem.sectionName}
                    </div>
                    <div class="detail-row">
                        <strong>Subject:</strong> ${classItem.subject}
                    </div>
                    <div class="detail-row">
                        <strong>Grade Level:</strong> ${classItem.gradeLevel}
                    </div>
                    <div class="detail-row">
                        <strong>Room:</strong> ${classItem.room}
                    </div>
                    <div class="detail-row">
                        <strong>Students:</strong> ${classItem.students.length}
                    </div>
                    <div class="detail-row">
                        <strong>Attendance Percentage:</strong> ${attendancePercentage}%
                    </div>
                    <div class="detail-row">
                        <strong>Schedule:</strong>
                        <div class="schedule-details">
                            ${scheduleText}
                        </div>
                    </div>
                    <div class="detail-row">
                        <strong>Status:</strong> <span class="status-badge ${classItem.status}">${classItem.status}</span>
                    </div>
                    <div class="detail-row">
                        <strong>Students List:</strong>
                        <div class="schedule-details">
                            ${classItem.students.map(student => `
                                <div>${student.firstName} ${student.lastName} (${student.email || 'No email'})</div>
                            `).join('') || 'No students enrolled'}
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('viewModal').classList.add('show');
        }

        // Close view modal
        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('show');
        }

        // Delete class
        function deleteClass(classId) {
            if (confirm('Are you sure you want to delete this class?')) {
                classes = classes.filter(c => c.id !== classId);
                renderClasses();
                populateFilters();
            }
        }

        // Handle form submission
        function handleFormSubmit(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const schedule = getScheduleFromForm();
            
            const classData = {
                id: editingClassId || Date.now(),
                code: document.getElementById('classCode').value,
                sectionName: document.getElementById('sectionName').value,
                subject: document.getElementById('subject').value,
                gradeLevel: document.getElementById('gradeLevel').value,
                room: document.getElementById('room').value,
                attendancePercentage: editingClassId ? classes.find(c => c.id === editingClassId).attendancePercentage : 10,
                schedule: schedule,
                status: document.getElementById('status').value,
                students: editingClassId ? classes.find(c => c.id === editingClassId).students : []
            };
            
            if (editingClassId) {
                const index = classes.findIndex(c => c.id === editingClassId);
                classes[index] = classData;
            } else {
                classes.push(classData);
            }
            
            renderClasses();
            populateFilters();
            closeModal();
        }

        // Get schedule from form
        function getScheduleFromForm() {
            const schedule = {};
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            
            days.forEach(day => {
                const checkbox = document.getElementById(day);
                const startInput = document.getElementById(day + 'Start');
                const endInput = document.getElementById(day + 'End');
                
                if (checkbox && checkbox.checked && startInput.value && endInput.value) {
                    schedule[day] = {
                        start: startInput.value,
                        end: endInput.value
                    };
                }
            });
            
            return schedule;
        }

        // Handle schedule checkbox toggle
        function handleScheduleToggle(event) {
            const day = event.target.id;
            const startInput = document.getElementById(day + 'Start');
            const endInput = document.getElementById(day + 'End');
            
            if (event.target.checked) {
                startInput.disabled = false;
                endInput.disabled = false;
            } else {
                startInput.disabled = true;
                endInput.disabled = true;
                startInput.value = '';
                endInput.value = '';
            }
        }

        // Clear schedule inputs
        function clearScheduleInputs() {
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            days.forEach(day => {
                const checkbox = document.getElementById(day);
                const startInput = document.getElementById(day + 'Start');
                const endInput = document.getElementById(day + 'End');
                
                if (checkbox) checkbox.checked = false;
                if (startInput) {
                    startInput.value = '';
                    startInput.disabled = true;
                }
                if (endInput) {
                    endInput.value = '';
                    endInput.disabled = true;
                }
            });
        }

        // Format schedule for display
        function formatSchedule(schedule) {
            if (!schedule || Object.keys(schedule).length === 0) {
                return '<span class="no-schedule">No schedule set</span>';
            }
            
            return Object.entries(schedule).map(([day, times]) => {
                const dayName = capitalizeFirst(day);
                return `<div class="schedule-item">${dayName}: ${formatTime(times.start)} - ${formatTime(times.end)}</div>`;
            }).join('');
        }

        // Format schedule for table view (short format)
        function formatScheduleShort(schedule) {
            if (!schedule || Object.keys(schedule).length === 0) {
                return 'No schedule';
            }
            
            const days = Object.keys(schedule).map(day => capitalizeFirst(day).substring(0, 3));
            return days.join(', ');
        }

        // Format time (convert 24-hour to 12-hour format)
        function formatTime(time) {
            if (!time) return '';
            const [hours, minutes] = time.split(':');
            const hourNum = parseInt(hours);
            const period = hourNum >= 12 ? 'PM' : 'AM';
            const displayHour = hourNum % 12 || 12;
            return `${displayHour}:${minutes} ${period}`;
        }

        // Calculate attendance percentage (mock calculation)
        function calculateAttendancePercentage(classItem) {
            return Math.floor(Math.random() * 20) + 80;
        }

        // Toggle select all checkbox
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // Open student modal
        function openStudentModal(classId) {
            currentClassForStudents = classId;
            document.getElementById('studentModal').classList.add('show');
        }

        // Close student modal
        function closeStudentModal() {
            document.getElementById('studentModal').classList.remove('show');
            document.getElementById('studentFile').value = '';
            document.getElementById('studentPreview').classList.add('hidden');
            document.getElementById('importStudentsBtn').disabled = true;
            currentClassForStudents = null;
        }

        // Handle student file upload
        function handleStudentFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    const jsonData = XLSX.utils.sheet_to_json(worksheet);
                    
                    if (jsonData.length > 0) {
                        importedStudentData = jsonData;
                        displayStudentPreview(jsonData);
                        document.getElementById('importStudentsBtn').disabled = false;
                    } else {
                        alert('The Excel file appears to be empty or invalid.');
                    }
                } catch (error) {
                    alert('Error reading the Excel file. Please make sure it\'s a valid Excel file.');
                }
            };
            reader.readAsArrayBuffer(file);
        }

        // Display student preview
        function displayStudentPreview(data) {
            const preview = document.getElementById('studentPreview');
            const table = document.getElementById('studentPreviewTable');
            
            if (data.length === 0) {
                preview.classList.add('hidden');
                return;
            }
            
            const headers = Object.keys(data[0]);
            
            table.innerHTML = `
                <thead>
                    <tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>
                </thead>
                <tbody>
                    ${data.slice(0, 5).map(row => `<tr>${headers.map(h => `<td>${row[h] || ''}</td>`).join('')}</tr>`).join('')}
                    ${data.length > 5 ? `<tr><td colspan="${headers.length}" style="text-align: center; font-style: italic;">... and ${data.length - 5} more rows</td></tr>` : ''}
                </tbody>
            `;
            
            preview.classList.remove('hidden');
        }

        // Import students
        function importStudents() {
            if (!importedStudentData || importedStudentData.length === 0 || !currentClassForStudents) return;
            
            const classIndex = classes.findIndex(c => c.id === currentClassForStudents);
            if (classIndex === -1) return;
            
            const newStudents = importedStudentData.map(row => ({
                id: row['Student ID'] || Date.now() + Math.random(),
                firstName: row['First Name'],
                lastName: row['Last Name'],
                email: row['Email'] || ''
            }));
            
            classes[classIndex].students.push(...newStudents);
            renderClasses();
            closeStudentModal();
            alert(`Successfully imported ${newStudents.length} students!`);
        }

        // Utility function to capitalize first letter
        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    </script>
</body>
</html>