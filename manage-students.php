<?php
ob_start();
require 'config.php';
session_start();
// Validate session
$user = validateSession();
if (!$user) {
    destroySession();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Student Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
        }

        /* * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }

        html {
            height: 100%;
            margin: 0;
        }

        body {
            background-color: var(--card-bg);
            color: var(--blackfont-color);
            height: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
        } */
            
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--whitefont-color);
        }
        .bg-purple { background: var(--primary-gradient); }
        .bg-pink { background: var(--secondary-gradient); }
        .bg-blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .bg-green { background: linear-gradient(135deg, #10b981, #34d399); }
        .card-title {
            font-size: 14px;
            color: var(--grayfont-color);
            margin-bottom: 5px;
        }
        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--blackfont-color);
        }
        .controls {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            align-items: center;
            border: 1px solid var(--border-color);
        }
        .controls-left {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            flex: 1;
            align-items: center;
        }
        .controls-right {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            align-items: center;
        }
        .controls-right .btn.btn-primary {
            order: 1;
        }
        .controls-right .view-toggle {
            order: 2;
        }
        .controls-right .btn.btn-primary,
        .controls-right .view-btn {
            height: 36px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .controls-right .view-btn {
            width: 38px;
            padding: 0;
        }
        .controls-right .view-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .search-container {
            position: relative;
            min-width: 200px;
            flex: 1;
        }
        .search-input {
            width: 100%;
            padding: var(--spacing-xs) var(--spacing-md) var(--spacing-xs) 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
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
            left: var(--spacing-sm);
            top: 55%;
            transform: translateY(-50%);
            color: var(--grayfont-color);
            font-size: 0.875rem;
        }
        .filter-select {
            min-width: 180px;
            padding: var(--spacing-xs) var(--spacing-sm);
            width: 180px;
            height: 38px;
            box-sizing: border-box;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }
        .filter-select:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }
        .btn {
            padding: var(--spacing-xs) var(--spacing-md);
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
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: var(--whitefont-color);
        }
        .btn-danger:hover {
            background: var(--danger-red);
            transform: translateY(-2px);
        }
        .btn-sm {
            padding: var(--spacing-xs) var(--spacing-sm);
            font-size: 0.75rem;
        }
        .view-toggle {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            overflow: hidden;
            background: var(--inputfield-color);
            display: flex;
        }
        .view-btn {
            padding: var(--spacing-xs) var(--spacing-sm);
            background: transparent;
            border: none;
            cursor: pointer;
            transition: var(--transition-normal);
            color: var(--grayfont-color);
            font-size: 0.875rem;
        }
        .view-btn:hover {
            background: var(--inputfieldhover-color);
        }
        .view-btn.active {
            background: var(--primary-gradient);
            color: var(--whitefont-color);
        }
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: var(--spacing-lg);
        }
        .student-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .student-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
        }
        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }
        .student-header h3 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--blackfont-color);
        }
        .student-info {
            margin-bottom: var(--spacing-md);
        }
        .student-info p {
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
            font-size: var(--font-size-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        .student-info i {
            width: 16px;
            color: var(--primary-blue);
        }
        .student-actions {
            display: flex;
            gap: 10px;
            flex-wrap: nowrap;
            align-items: center;
        }
        .student-actions .btn {
            white-space: nowrap;
            min-width: auto;
        }
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
        .table td .actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .table td .actions .btn {
            white-space: nowrap;
            padding: var(--spacing-xs) var(--spacing-sm);
        }
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
            border-radius: var(--radius-xl);
            max-width: 1000px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
            border: 1px solid var(--border-color);
        }
        .modal-header {
            padding: var(--spacing-xl) var(--spacing-2xl);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary-gradient);
            color: var(--whitefont-color);
            border-top-left-radius: var(--radius-xl);
            border-top-right-radius: var(--radius-xl);
        }
        .modal-title {
            margin: 0;
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--whitefont-color);
        }
        .close-btn {
            padding: var(--spacing-sm);
            background: none;
            border: none;
            font-size: 1.75rem;
            cursor: pointer;
            color: var(--whitefont-color);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
        }
        .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .modal-form {
            padding: var(--spacing-2xl);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-xl);
            background: linear-gradient(180deg, #f9fafb, #ffffff);
        }
        .form-group {
            margin-bottom: var(--spacing-lg);
        }
        .form-label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
            color: var(--blackfont-color);
            font-size: var(--font-size-base);
        }
        .form-input,
        .form-select {
            padding: var(--spacing-md) var(--spacing-lg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
            width: 100%;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }
        .form-input:disabled,
        .form-select:disabled {
            background: var(--light-gray);
            opacity: 0.7;
            cursor: not-allowed;
        }
        .photo-upload {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }
        .photo-upload input[type="file"] {
            display: none;
        }
        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-md);
            object-fit: cover;
            border: 1px solid var(--border-color);
        }
        .qr-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: var(--spacing-sm);
        }
        .qr-code {
            width: 100px;
            height: 100px;
        }
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            padding-top: var(--spacing-xl);
            border-top: 1px solid var(--border-color);
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-lg);
        }
        .pagination-btn {
            padding: var(--spacing-xs) var(--spacing-md);
            border: none;
            background: var(--inputfield-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition-normal);
            font-size: var(--font-size-sm);
            min-width: 60px;
        }
        .pagination-btn.active {
            background: var(--primary-gradient);
            color: var(--whitefont-color);
        }
        .pagination-btn:hover:not(.active) {
            background: var(--inputfieldhover-color);
        }
        .bulk-actions {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
            border: 1px solid var(--border-color);
        }
        .selected-count {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
        }
        .hidden {
            display: none;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @media (max-width: 1024px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            .controls-right {
                justify-content: flex-start;
                margin-top: var(--spacing-sm);
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }
        @media (max-width: 768px) {
            body {
                padding: var(--spacing-sm);
            }
            .controls-left {
                flex-direction: column;
                gap: var(--spacing-xs);
            }
            .controls-right {
                flex-direction: column;
                gap: var(--spacing-xs);
            }
            .search-container {
                min-width: auto;
                width: 100%;
            }
            .filter-select {
                width: 100%;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
            .view-toggle {
                width: 100%;
                justify-content: space-between;
            }
            .student-grid {
                grid-template-columns: 1fr;
            }
            .modal-content {
                width: 98%;
                max-height: 95vh;
            }
            .modal-form {
                grid-template-columns: 1fr;
                padding: var(--spacing-lg);
            }
            .form-actions {
                flex-direction: column;
                gap: var(--spacing-sm);
            }
            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
            .table th:nth-child(n+6),
            .table td:nth-child(n+6) {
                display: none;
            }
        }
        @media (max-width: 576px) {
            h1 {
                font-size: var(--font-size-xl);
            }
            .table th:nth-child(n+4),
            .table td:nth-child(n+4) {
                display: none;
            }
            .student-card {
                padding: var(--spacing-sm);
            }
            .student-actions {
                flex-direction: column;
            }
            .student-actions .btn {
                width: 100%;
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
        @media print {
            .controls,
            .bulk-actions,
            .student-actions,
            .modal {
                display: none !important;
            }
            body {
                padding: 0;
            }
            .student-card {
                box-shadow: none;
                border: 1px solid var(--border-color);
                page-break-inside: avoid;
            }
        }
    </style>

         <style>
        .controls {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            align-items: center;
            border: 1px solid var(--border-color);
        }

        .controls-left {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            flex: 1;
            align-items: center;
        }

        .controls-right {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-sm);
    align-items: center;
}

.controls-right .btn.btn-primary {
    order: 1; /* Places "Add Student" button first */
}

.controls-right .view-toggle {
    order: 2; /* Places view-toggle after the button */
}


.controls-right .btn.btn-primary,
.controls-right .view-btn {
    height: 36px; /* Uniform height */
    padding: 8px 12px; /* Consistent padding */
    display: flex;
    align-items: center;
    justify-content: center;
}

.controls-right .view-btn {
    width: 38px; /* Square buttons for view toggle */
    padding: 0; /* Remove padding for icon-only buttons */
}

.controls-right .view-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.controls-right {
    display: flex;
    flex-direction: column;
    align-items: flex-start; /* Aligns items to the start */
    gap: var(--spacing-sm);
}

        .search-container {
            position: relative;
            min-width: 200px;
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: var(--spacing-xs) var(--spacing-md) var(--spacing-xs) 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .search-icon {
            position: absolute;
            left: var(--spacing-sm);
            top: 55%;
            transform: translateY(-50%);
            color: var(--grayfont-color);
            font-size: 0.875rem;
        }

        .filter-select {
            min-width: 180px;
            padding: var(--spacing-xs) var(--spacing-sm);
            width: 180px; 
  height: 38px; 
  box-sizing: border-box;
        }

        .btn {
            padding: var(--spacing-xs) var(--spacing-md);
            font-size: var(--font-size-sm);
        }

        .view-toggle {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            overflow: hidden;
            background: var(--inputfield-color);
            display: flex;
        }

        .view-btn {
            padding: var(--spacing-xs) var(--spacing-sm);
            font-size: 0.875rem;
        }

        @media (max-width: 1024px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .controls-right {
                justify-content: flex-start;
                margin-top: var(--spacing-sm);
            }
        }

        @media (max-width: 768px) {
            .controls-left {
                flex-direction: column;
                gap: var(--spacing-xs);
            }

            .controls-right {
                flex-direction: column;
                gap: var(--spacing-xs);
            }

            .search-container {
                min-width: auto;
                width: 100%;
            }

            .filter-select {
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .view-toggle {
                width: 100%;
                justify-content: space-between;
            }
        }

        .preview-table-container {
            margin-top: var(--spacing-lg);
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .preview-table-container h3 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--blackfont-color);
            margin-bottom: var(--spacing-md);
        }
    </style>

    <style>
        .student-actions {
    display: flex;
    gap: 10px;
    flex-wrap: nowrap; /* Prevents wrapping to new lines */
    align-items: center; /* Vertically centers the buttons */
}

.student-actions .btn {
    white-space: nowrap; /* Prevents text wrapping within buttons */
    min-width: auto; /* Allows buttons to size naturally */
}

.table td .actions {
    display: flex;
    gap: 10px; /* Consistent spacing between buttons */
    align-items: center; /* Vertically centers the buttons */
}

.table td .actions .btn {
    white-space: nowrap; /* Prevents text wrapping within buttons */
    padding: var(--spacing-xs) var(--spacing-sm); /* Adjusted padding for consistency */
}
    </style>

</head>
<body>
    <div class="container">
        <h1>Student Management</h1>
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Total Students</div>
                        <div class="card-value" id="total-students">0</div>
                    </div>
                    <div class="card-icon bg-purple">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                            <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                            <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Active Students</div>
                        <div class="card-value" id="active-students">0</div>
                    </div>
                    <div class="card-icon bg-green">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Classes Enrolled</div>
                        <div class="card-value" id="classes-enrolled">0</div>
                    </div>
                    <div class="card-icon bg-pink">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                            <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                            <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <!-- Controls -->
        <div class="controls">
            <div class="controls-left">
                <div class="search-container">
                    <input type="text" class="form-input search-input" id="searchInput" placeholder="Search by LRN or Name">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <select class="form-select filter-select" id="genderFilter">
                    <option value="">All Genders</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <select class="form-select filter-select" id="gradeLevelFilter">
                    <option value="">All Grade Levels</option>
                </select>
                <select class="form-select filter-select" id="classFilter">
                    <option value="">All Subjects</option>
                </select>
                <select class="form-select filter-select" id="sectionFilter">
                    <option value="">All Sections</option>
                </select>
                <select class="form-select filter-select" id="sortSelect">
                    <option value="name-asc">Name (A-Z)</option>
                    <option value="name-desc">Name (Z-A)</option>
                    <option value="id">LRN</option>
                </select>
                <button class="btn btn-secondary" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Clear Filters
                </button>
            </div>
            <div class="controls-right">
                <button class="btn btn-primary" onclick="openProfileModal('add')">
                    <i class="fas fa-plus"></i> Add Student
                </button>
                <div class="view-toggle">
                    <button class="view-btn active" onclick="switchView('table')">
                        <i class="fas fa-list"></i>
                    </button>
                    <button class="view-btn" onclick="switchView('grid')">
                        <i class="fas fa-th-large"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
            <label for="selectAll">Select All</label>
            <span class="selected-count" id="selectedCount">0 selected</span>
            <button class="btn btn-primary" id="bulkExportBtn" disabled onclick="bulkExport()">
                <i class="fas fa-file-export"></i> Export Selected
            </button>
            <button class="btn btn-danger" id="bulkDeleteBtn" disabled onclick="bulkDelete()">
                <i class="fas fa-trash"></i> Delete Selected
            </button>
        </div>
        <!-- Student List -->
        <div id="gridView" class="student-grid hidden"></div>
        <div id="tableView" class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="tableSelectAll" onchange="toggleSelectAll()"></th>
                        <th>Photo</th>
                        <th>LRN</th>
                        <th>Full Name</th>
                        <th>Grade Level</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="pagination" id="pagination"></div>
        <!-- Student Profile Modal -->
        <div class="modal" id="profile-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="profile-modal-title">Student Profile</h2>
                    <button class="close-btn" onclick="closeModal('profile')">×</button>
                </div>
                <form id="studentForm" class="modal-form">
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">LRN</label>
                            <input type="text" class="form-input" id="student-id" maxlength="12" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" pattern="[0-9]{12}" title="Please enter exactly 12 digits">
                        </div>
                        <div class="form-group photo-upload">
                            <div>
                                <label class="form-label">Photo</label>
                                <img id="student-photo-preview" src="https://via.placeholder.com/100" alt="Student Photo" class="photo-preview">
                            </div>
                            <div id="qr-container" class="qr-container" style="display: none;">
                                <label class="form-label">QR Code</label>
                                <div id="qr-code" class="qr-code"></div>
                                <button type="button" class="btn btn-primary" onclick="printQRCode()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                            <input type="file" id="student-photo" accept="image/*" onchange="previewPhoto(event)">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('student-photo').click()">Change Photo</button>
                        </div>
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-input" id="first-name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-input" id="last-name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email (Optional)</label>
                            <input type="email" class="form-input" id="email">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select class="form-select" id="gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-input" id="dob">
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">Grade Level</label>
                            <select class="form-select" id="grade-level">
                                <option value="">Select Grade Level</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subject</label>
                            <select class="form-select" id="class">
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Section</label>
                            <select class="form-select" id="section">
                                <option value="">Select Section</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-input" id="address">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Parent/Guardian Name</label>
                            <input type="text" class="form-input" id="parent-name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Emergency Contact</label>
                            <input type="text" class="form-input" id="emergency-contact">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('profile')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Simulated class data (replace with AJAX call in production)
        let classes = [
            {
                id: 1,
                code: 'MATH-101-A',
                sectionName: 'Diamond Section',
                subject: 'Mathematics',
                gradeLevel: 'Grade 7',
                room: 'Room 201',
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
            dateAdded: student.dateAdded || '2024-09-01',
            photo: student.photo || 'https://via.placeholder.com/100'
        })));
        let currentPage = 1;
        const rowsPerPage = 10;
        let currentView = 'table';
        // DOM Elements
        const studentTableBody = document.querySelector('#tableView tbody');
        const gridView = document.getElementById('gridView');
        const tableView = document.getElementById('tableView');
        const pagination = document.getElementById('pagination');
        const searchInput = document.getElementById('searchInput');
        const genderFilter = document.getElementById('genderFilter');
        const gradeLevelFilter = document.getElementById('gradeLevelFilter');
        const classFilter = document.getElementById('classFilter');
        const sectionFilter = document.getElementById('sectionFilter');
        const sortSelect = document.getElementById('sortSelect');
        const profileModal = document.getElementById('profile-modal');
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            updateStats();
            populateFilters();
            applyFilters();
            setupEventListeners();
            document.querySelector('#studentForm').addEventListener('submit', saveStudent);
        });
        // Update stats for cards
        function updateStats() {
            const totalStudents = students.length;
            const activeStudents = students.filter(s => classes.find(c => c.subject === s.class && c.sectionName === s.section)?.status === 'active').length;
            const classesEnrolled = [...new Set(students.map(s => `${s.class}-${s.section}`))].length;
            document.getElementById('total-students').textContent = totalStudents;
            document.getElementById('active-students').textContent = activeStudents;
            document.getElementById('classes-enrolled').textContent = classesEnrolled;
        }
        // Populate filters
        function populateFilters() {
            const subjects = [...new Set(classes.map(c => c.subject).filter(s => s))];
            const sections = [...new Set(classes.map(c => c.sectionName).filter(s => s))];
            const gradeLevels = [...new Set(classes.map(c => c.gradeLevel).filter(g => g))];
            classFilter.innerHTML = '<option value="">All Subjects</option>';
            sectionFilter.innerHTML = '<option value="">All Sections</option>';
            gradeLevelFilter.innerHTML = '<option value="">All Grade Levels</option>';
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                classFilter.appendChild(option);
            });
            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionFilter.appendChild(option);
            });
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                option.textContent = grade;
                gradeLevelFilter.appendChild(option);
            });
            // Populate modal selects
            const modalClassSelect = document.getElementById('class');
            const modalSectionSelect = document.getElementById('section');
            const modalGradeSelect = document.getElementById('grade-level');
            modalClassSelect.innerHTML = '<option value="">Select Subject</option>';
            modalSectionSelect.innerHTML = '<option value="">Select Section</option>';
            modalGradeSelect.innerHTML = '<option value="">Select Grade Level</option>';
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                modalClassSelect.appendChild(option);
            });
            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                modalSectionSelect.appendChild(option);
            });
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                option.textContent = grade;
                modalGradeSelect.appendChild(option);
            });
        }
        // Setup event listeners
        function setupEventListeners() {
            searchInput.addEventListener('input', applyFilters);
            genderFilter.addEventListener('change', applyFilters);
            gradeLevelFilter.addEventListener('change', applyFilters);
            classFilter.addEventListener('change', applyFilters);
            sectionFilter.addEventListener('change', applyFilters);
            sortSelect.addEventListener('change', applyFilters);
        }
        // Apply filters and sorting
        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            const gender = genderFilter.value;
            const gradeLevel = gradeLevelFilter.value;
            const className = classFilter.value;
            const section = sectionFilter.value;
            let filteredStudents = students.filter(student => {
                const matchesSearch = student.fullName.toLowerCase().includes(searchTerm) ||
                    student.id.toString().includes(searchTerm);
                const matchesGender = gender ? student.gender === gender : true;
                const matchesGradeLevel = gradeLevel ? student.gradeLevel === gradeLevel : true;
                const matchesClass = className ? student.class === className : true;
                const matchesSection = section ? student.section === section : true;
                return matchesSearch && matchesGender && matchesGradeLevel && matchesClass && matchesSection;
            });
            filteredStudents.sort((a, b) => {
                if (sortSelect.value === 'name-asc') return a.fullName.localeCompare(b.fullName);
                if (sortSelect.value === 'name-desc') return b.fullName.localeCompare(a.fullName);
                if (sortSelect.value === 'id') return a.id.toString().localeCompare(b.id.toString());
                return 0;
            });
            renderViews(filteredStudents);
        }
        // Render views
        function renderViews(data) {
            gridView.classList.add('hidden');
            tableView.classList.add('hidden');
            if (currentView === 'grid') {
                renderGridView(data);
                gridView.classList.remove('hidden');
            } else {
                renderTableView(data);
                tableView.classList.remove('hidden');
            }
            renderPagination(data.length);
        }
        // Render grid view
        function renderGridView(data) {
            gridView.innerHTML = '';
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedData = data.slice(start, end);
            if (paginatedData.length === 0) {
                gridView.innerHTML = '<div class="no-students">No students found.</div>';
                return;
            }
            paginatedData.forEach(student => {
                const card = document.createElement('div');
                card.className = 'student-card';
                card.innerHTML = `
                    <div class="student-header">
                        <h3>${student.fullName}</h3>
                    </div>
                    <div class="student-info">
                        <p><i class="fas fa-id-card"></i> LRN: ${student.id}</p>
                        <p><i class="fas fa-envelope"></i> ${student.emergencyContact}</p>
                        <p><i class="fas fa-graduation-cap"></i> ${student.gradeLevel}</p>
                        <p><i class="fas fa-book"></i> ${student.class}</p>
                        <p><i class="fas fa-layer-group"></i> ${student.section}</p>
                    </div>
                    <div class="student-actions">
                        <button class="btn btn-primary btn-sm" onclick="openProfileModal('view', '${student.id}')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="openProfileModal('edit', '${student.id}')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteStudent('${student.id}')">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                `;
                gridView.appendChild(card);
            });
        }
        // Render table view
        function renderTableView(data) {
            studentTableBody.innerHTML = '';
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedData = data.slice(start, end);
            paginatedData.forEach(student => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox" class="row-checkbox" data-id="${student.id}"></td>
                    <td><img src="${student.photo}" alt="${student.fullName}" style="width: 40px; height: 40px; border-radius: var(--radius-sm);"></td>
                    <td>${student.id}</td>
                    <td>${student.fullName}</td>
                    <td>${student.gradeLevel}</td>
                    <td>${student.class}</td>
                    <td>${student.section}</td>
                    <td>
                        <div class="actions">
                            <button class="btn btn-primary btn-sm" onclick="openProfileModal('view', '${student.id}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="openProfileModal('edit', '${student.id}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteStudent('${student.id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                studentTableBody.appendChild(row);
            });
            updateBulkActions();
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.addEventListener('change', updateBulkActions));
        }
        // Render pagination
        function renderPagination(totalRows) {
            const pageCount = Math.ceil(totalRows / rowsPerPage);
            pagination.innerHTML = `
                <button class="pagination-btn" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>Previous</button>
                ${Array.from({ length: pageCount }, (_, i) => `
                    <button class="pagination-btn ${currentPage === i + 1 ? 'active' : ''}" onclick="changePage(${i + 1})">${i + 1}</button>
                `).join('')}
                <button class="pagination-btn" onclick="changePage(${currentPage + 1})" ${currentPage === pageCount ? 'disabled' : ''}>Next</button>
            `;
        }
        // Change page
        function changePage(page) {
            currentPage = page;
            applyFilters();
        }
        // Switch view
        function switchView(view) {
            currentView = view;
            document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`.view-btn[onclick="switchView('${view}')"]`).classList.add('active');
            applyFilters();
        }
        // Toggle select all
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = selectAll.checked);
            updateBulkActions();
        }
        // Update bulk actions
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const selectedCount = document.getElementById('selectedCount');
            const bulkButtons = document.querySelectorAll('.bulk-actions .btn');
            selectedCount.textContent = `${checkboxes.length} selected`;
            bulkButtons.forEach(btn => btn.disabled = checkboxes.length === 0);
        }
        // Bulk actions
        function bulkExport() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const selectedStudents = Array.from(checkboxes).map(cb =>
                students.find(s => s.id == cb.dataset.id)
            );
            const csv = [
                'LRN,First Name,Last Name,Email,Gender,Grade Level,Subject,Section,Address,Emergency Contact',
                ...selectedStudents.map(s =>
                    `${s.id},${s.firstName},${s.lastName},${s.email},${s.gender},${s.gradeLevel},${s.class},${s.section},${s.address},${s.emergencyContact}`
                )
            ].join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'selected_students.csv';
            a.click();
            URL.revokeObjectURL(url);
        }
        function bulkDelete() {
            if (!confirm('Are you sure you want to delete selected students?')) return;
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.dataset.id);
            classes.forEach(cls => {
                cls.students = cls.students.filter(s => !ids.includes(s.id.toString()));
            });
            students = students.filter(s => !ids.includes(s.id.toString()));
            applyFilters();
        }
        // Open profile modal
        function openProfileModal(mode, id) {
            const form = {
                studentId: document.getElementById('student-id'),
                firstName: document.getElementById('first-name'),
                lastName: document.getElementById('last-name'),
                email: document.getElementById('email'),
                gender: document.getElementById('gender'),
                dob: document.getElementById('dob'),
                gradeLevel: document.getElementById('grade-level'),
                class: document.getElementById('class'),
                section: document.getElementById('section'),
                address: document.getElementById('address'),
                parentName: document.getElementById('parent-name'),
                emergencyContact: document.getElementById('emergency-contact'),
                photoPreview: document.getElementById('student-photo-preview')
            };
            // Reset form
            Object.values(form).forEach(input => {
                if (input.tagName === 'IMG') input.src = 'https://via.placeholder.com/100';
                else if (input.tagName === 'SELECT') input.value = '';
                else input.value = '';
            });
            // Clear and hide QR code container
            const qrContainer = document.getElementById('qr-container');
            const qrCodeDiv = document.getElementById('qr-code');
            qrCodeDiv.innerHTML = '';
            qrContainer.style.display = 'none';
            if (mode !== 'add' && id) {
                const student = students.find(s => s.id == id);
                document.getElementById('profile-modal-title').textContent = `${student.fullName}'s Profile`;
                form.studentId.value = student.id;
                form.firstName.value = student.firstName;
                form.lastName.value = student.lastName;
                form.email.value = student.email;
                form.gender.value = student.gender;
                form.dob.value = student.dob;
                form.gradeLevel.value = student.gradeLevel;
                form.class.value = student.class;
                form.section.value = student.section;
                form.address.value = student.address;
                form.parentName.value = student.parentName;
                form.emergencyContact.value = student.emergencyContact;
                form.photoPreview.src = student.photo;
                if (mode === 'view') {
                    qrContainer.style.display = 'flex';
                    const qrData = JSON.stringify({
                        id: student.id,
                        fullName: student.fullName,
                        gradeLevel: student.gradeLevel,
                        class: student.class,
                        section: student.section
                    });
                    new QRCode(qrCodeDiv, {
                        text: qrData,
                        width: 100,
                        height: 100
                    });
                }
            } else {
                document.getElementById('profile-modal-title').textContent = 'Add New Student';
            }
            // Disable inputs for view mode
            Object.values(form).forEach(input => {
                if (input.tagName !== 'IMG') input.disabled = mode === 'view';
            });
            document.querySelector('.photo-upload .btn').style.display = mode === 'view' ? 'none' : 'inline-flex';
            document.querySelector('.form-actions .btn-primary').style.display = mode === 'view' ? 'none' : 'inline-flex';
            profileModal.classList.add('show');
        }
        // Preview photo
        function previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('student-photo-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
        // Save student
        function saveStudent(event) {
            event.preventDefault();
            const form = {
                studentId: document.getElementById('student-id').value.trim(),
                firstName: document.getElementById('first-name').value.trim(),
                lastName: document.getElementById('last-name').value.trim(),
                email: document.getElementById('email').value.trim(),
                gender: document.getElementById('gender').value,
                dob: document.getElementById('dob').value,
                gradeLevel: document.getElementById('grade-level').value,
                class: document.getElementById('class').value,
                section: document.getElementById('section').value,
                address: document.getElementById('address').value.trim(),
                parentName: document.getElementById('parent-name').value.trim(),
                emergencyContact: document.getElementById('emergency-contact').value.trim(),
                photo: document.getElementById('student-photo-preview').src
            };
            if (!form.firstName || !form.lastName || !form.gender || !form.dob || !form.gradeLevel ||
                !form.class || !form.section || !form.address || !form.parentName || !form.emergencyContact) {
                alert('Please fill in all required fields.');
                return;
            }
            const newStudent = {
                id: form.studentId || Date.now(),
                firstName: form.firstName,
                lastName: form.lastName,
                email: form.email,
                fullName: `${form.firstName} ${form.lastName}`,
                gender: form.gender,
                dob: form.dob,
                gradeLevel: form.gradeLevel,
                class: form.class,
                section: form.section,
                address: form.address,
                parentName: form.parentName,
                emergencyContact: form.emergencyContact,
                dateAdded: new Date().toISOString().split('T')[0],
                photo: form.photo
            };
            // Update class students
            const classItem = classes.find(c => c.subject === form.class && c.sectionName === form.section);
            if (classItem) {
                const studentIndex = classItem.students.findIndex(s => s.id == newStudent.id);
                if (studentIndex >= 0) {
                    classItem.students[studentIndex] = {
                        id: newStudent.id,
                        firstName: newStudent.firstName,
                        lastName: newStudent.lastName,
                        email: newStudent.email
                    };
                } else {
                    classItem.students.push({
                        id: newStudent.id,
                        firstName: newStudent.firstName,
                        lastName: newStudent.lastName,
                        email: newStudent.email
                    });
                }
            }
            // Update students array
            if (students.some(s => s.id == newStudent.id)) {
                const index = students.findIndex(s => s.id == newStudent.id);
                students[index] = newStudent;
            } else {
                students.push(newStudent);
            }
            applyFilters();
            closeModal('profile');
        }
        // Delete student
        function deleteStudent(id) {
            if (!confirm('Are you sure you want to delete this student?')) return;
            classes.forEach(cls => {
                cls.students = cls.students.filter(s => s.id != id);
            });
            students = students.filter(s => s.id != id);
            applyFilters();
        }
        // Clear filters
        function clearFilters() {
            searchInput.value = '';
            genderFilter.value = '';
            gradeLevelFilter.value = '';
            classFilter.value = '';
            sectionFilter.value = '';
            sortSelect.value = 'name-asc';
            applyFilters();
        }
        // Close modal
        function closeModal(type) {
            if (type === 'profile') {
                profileModal.classList.remove('show');
            }
        }
        // Print QR code
        function printQRCode() {
            const qrCanvas = document.querySelector('#qr-code canvas');
            if (!qrCanvas) return;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print QR Code</title>
                    <style>
                        body { margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
                        img { max-width: 100%; }
                    </style>
                </head>
                <body>
                    <img src="${qrCanvas.toDataURL('image/png')}" alt="QR Code">
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        }
    </script>
</body>
</html>