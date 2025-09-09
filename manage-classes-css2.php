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

        * {
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

        .bg-purple {
            background: var(--primary-gradient);
        }

        .bg-pink {
            background: var(--secondary-gradient);
        }

        .bg-blue {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
        }

        .bg-green {
            background: linear-gradient(135deg, #10b981, #34d399);
        }

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

        .form-input,
        .form-select {
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
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
            cursor: not-allowed;
        }

        .filter-select {
            min-width: 140px;
        }

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

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: var(--whitefont-color);
        }

        .btn-warning:hover {
            background: var(--warning-yellow);
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

        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 15px;
        }

        .class-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 20px;
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
            /* padding-top: var(--spacing-sm); */
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

        .actions {
            display: flex;
            gap: var(--spacing-xs);
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
            max-width: 1100px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .modal-header {
            padding: var(--spacing-xl);
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
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--whitefont-color);
            padding: var(--spacing-sm);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            transition: var(--transition-normal);
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--whitefont-color);
        }

        .modal form {
            padding: var(--spacing-xl);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-weight: 600;
            color: var(--blackfont-color);
            font-size: var(--font-size-base);
        }

        .schedule-inputs {
            display: grid;
            gap: var(--spacing-md);
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
            font-size: var(--font-size-sm);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            padding-top: var(--spacing-xl);
            border-top: 1px solid var(--border-color);
        }

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

        .student-table-container {
            margin-top: var(--spacing-lg);
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .student-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .student-table th,
        .student-table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .student-table th {
            font-weight: 600;
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
        }

        .student-table tr:hover {
            background: var(--inputfieldhover-color);
        }

        .import-section {
            margin-top: var(--spacing-lg);
            padding: var(--spacing-lg);
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .import-section .btn {
            margin-right: var(--spacing-sm);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 1024px) {
            .controls {
                grid-template-columns: 1fr;
            }

            .controls-right {
                justify-content: flex-start;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
                flex-direction: row; /* Keep horizontal */
                justify-content: center; /* Center buttons */
                gap: var(--spacing-sm);
            }

            .class-actions .btn {
                flex: 1; /* Equal width for buttons */
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

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

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

        @media print {
            .controls,
            .class-actions,
            .actions,
            .modal,
            .student-table-container,
            .import-section {
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

        /* Controls Section Styles */
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
            gap: var(--spacing-md); /* Increased gap for clarity */
            align-items: center;
            justify-content: flex-end; /* Right-align buttons */
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
            min-width: 140px;
            padding: var(--spacing-xs) var(--spacing-sm);
        }

        /* .btn {
            padding: var(--spacing-xs) var(--spacing-md);
            font-size: var(--font-size-sm);
        } */

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
                justify-content: flex-end; /* Maintain right-alignment */
                margin-top: var(--spacing-sm);
            }
        }

        @media (max-width: 768px) {
            .controls-left {
                flex-direction: column;
                gap: var(--spacing-xs);
            }

            .controls-right {
                flex-direction: row;
                gap: var(--spacing-sm);
                justify-content: center; /* Center for better mobile alignment */
            }

            .search-container {
                min-width: auto;
                width: 100%;
            }

            .filter-select {
                width: 100%;
            }

            .btn {
                width: auto; /* Allow buttons to fit content */
                justify-content: center;
            }

            .view-toggle {
                width: 100%;
                justify-content: space-between;
            }
        }

        /* Class Modal Specific Styles */
        .class-modal {
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

        .class-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .class-modal-content {
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
            position: relative;
        }

        .class-modal-header {
            padding: var(--spacing-xl) var(--spacing-2xl);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary-gradient);
            color: var(--whitefont-color);
            border-top-left-radius: var(--radius-xl);
            border-top-right-radius: var(--radius-xl);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .class-modal-title {
            margin: 0;
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--whitefont-color);
            letter-spacing: 0.02em;
        }

        .class-close-btn {
            background: none;
            border: none;
            font-size: 1.75rem;
            cursor: pointer;
            color: var(--whitefont-color);
            padding: var(--spacing-sm);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
        }

        .class-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .class-modal-form {
            padding: var(--spacing-2xl);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-xl);
            background: linear-gradient(180deg, #f9fafb, #ffffff);
        }

        .class-form-group {
            margin-bottom: var(--spacing-lg);
        }

        .class-form-label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
            color: var(--blackfont-color);
            font-size: var(--font-size-base);
            letter-spacing: 0.01em;
        }

        .class-form-input,
        .class-form-select {
            padding: var(--spacing-md) var(--spacing-lg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
            width: 100%;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .class-form-input:focus,
        .class-form-select:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .class-form-input:disabled,
        .class-form-select:disabled {
            background: var(--light-gray);
            opacity: 0.7;
            cursor: not-allowed;
        }

        .class-schedule-inputs {
            display: grid;
            gap: var(--spacing-md);
            background: var(--white);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .class-schedule-day-input {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            border-radius: var(--radius-sm);
            transition: var(--transition-normal);
            background: var(--inputfield-color);
        }

        .class-schedule-day-input:hover {
            background: var(--inputfieldhover-color);
            box-shadow: var(--shadow-sm);
        }

        .class-schedule-day-input input[type="checkbox"] {
            margin-right: var(--spacing-sm);
            accent-color: var(--primary-blue);
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .class-schedule-day-input label {
            min-width: 90px;
            margin: 0;
            font-weight: 500;
            color: var(--blackfont-color);
            font-size: var(--font-size-sm);
        }

        .class-schedule-day-input input[type="time"] {
            padding: var(--spacing-xs) var(--spacing-sm);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--white);
            transition: var(--transition-normal);
            width: 100px;
        }

        .class-schedule-day-input input[type="time"]:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px var(--primary-blue-light);
        }

        .class-schedule-day-input input[type="time"]:disabled {
            background: var(--light-gray);
            opacity: 0.7;
            cursor: not-allowed;
        }

        .class-schedule-day-input span {
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            margin: 0 var(--spacing-xs);
        }

        .class-form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            padding-top: var(--spacing-xl);
            border-top: 1px solid var(--border-color);
            background: var(--card-bg);
        }

        .class-modal .btn {
            padding: var(--spacing-md) var(--spacing-xl);
            font-size: var(--font-size-base);
            font-weight: 600;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .class-modal .btn-primary {
            background: var(--primary-gradient);
            color: var(--whitefont-color);
            box-shadow: var(--shadow-sm);
        }

        .class-modal .btn-primary:hover {
            background: var(--primary-blue-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .class-modal .btn-secondary {
            background: var(--medium-gray);
            color: var(--whitefont-color);
            box-shadow: var(--shadow-sm);
        }

        .class-modal .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        @media (max-width: 768px) {
            .class-modal-content {
                width: 98%;
                max-height: 95vh;
            }

            .class-modal-form {
                grid-template-columns: 1fr;
                padding: var(--spacing-lg);
            }

            .class-schedule-inputs {
                padding: var(--spacing-sm);
            }

            .class-schedule-day-input {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-xs);
            }

            .class-schedule-day-input input[type="time"] {
                width: 100%;
            }

            .class-form-actions {
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            .class-form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Class Details and Student List Styles */
        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--inputfield-color);
            border-radius: var(--radius-sm);
            transition: var(--transition-normal);
        }

        .detail-row:hover {
            background: var(--inputfieldhover-color);
            box-shadow: var(--shadow-sm);
        }

        .detail-row strong {
            flex: 0 0 150px;
            font-weight: 600;
            color: var(--blackfont-color);
            font-size: var(--font-size-sm);
        }

        .detail-row span,
        .detail-row div {
            flex: 1;
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
        }

        .schedule-details {
            padding-left: var(--spacing-md);
            color: var(--grayfont-color);
        }

        .schedule-details .schedule-item {
            margin-bottom: var(--spacing-xs);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .schedule-details .schedule-item::before {
            content: "•";
            color: var(--primary-blue);
            margin-right: var(--spacing-xs);
        }

        .student-table-container {
            margin-top: var(--spacing-xl);
            padding: var(--spacing-xl);
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            width: 100%;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .student-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .student-table th,
        .student-table td {
            padding: var(--spacing-lg) var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: var(--font-size-sm);
        }

        .student-table th {
            font-weight: 600;
            color: var(--grayfont-color);
            background: var(--inputfield-color);
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .student-table td {
            color: var(--blackfont-color);
        }

        .student-table tr:hover {
            background: var(--inputfieldhover-color);
            transition: var(--transition-fast);
        }

        .student-table img {
            border-radius: var(--radius-sm);
            object-fit: cover;
        }

        @media (max-width: 1024px) {
            .student-table-container {
                max-width: 100%;
                padding: var(--spacing-md);
            }

            .student-table th,
            .student-table td {
                padding: var(--spacing-md) var(--spacing-sm);
            }

            .student-table th:nth-child(n+8),
            .student-table td:nth-child(n+8) {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .student-table th:nth-child(n+6),
            .student-table td:nth-child(n+6) {
                display: none;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-xs);
                padding: var(--spacing-sm);
            }

            .detail-row strong {
                flex: none;
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .student-table th:nth-child(n+4),
            .student-table td:nth-child(n+4) {
                display: none;
            }

            .student-table-container {
                padding: var(--spacing-sm);
            }

            .student-table th,
            .student-table td {
                padding: var(--spacing-sm) var(--spacing-xs);
                font-size: 0.75rem;
            }

            .detail-row {
                padding: var(--spacing-xs);
            }
        }

        .modal-body {
            padding: 1.5rem 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .import-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .file-input {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
            max-width: 250px;
            flex: 1;
        }

        .import-note {
            display: block;
            color: var(--grayfont-color);
            font-size: 0.85rem;
            line-height: 1.2;
            margin-top: 0.5rem;
        }

        .preview-table-container {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .preview-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--blackfont-color);
            margin-bottom: 1rem;
        }

        .table-wrapper {
            overflow-x: auto;
            max-width: 100%;
        }

        .preview-table,
        .student-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1200px;
            table-layout: auto;
        }

        .preview-table th,
        .student-table th,
        .preview-table td,
        .student-table td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
            overflow: hidden;
            max-width: 200px;
        }

        .preview-table th,
        .student-table th {
            font-weight: 600;
            color: var(--grayfont-color);
            background: var(--inputfield-color);
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .preview-table tr:hover,
        .student-table tr:hover {
            background: var(--inputfieldhover-color);
            transition: var(--transition-fast);
        }

        .student-table img {
            border-radius: var(--radius-sm);
            object-fit: cover;
            max-width: 60px;
            max-height: 60px;
        }

        .form-actions {
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        @media (max-width: 1024px) {
            .modal-body {
                padding: 1rem;
            }

            .import-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .file-input {
                max-width: 100%;
            }

            .preview-table th:nth-child(n+9),
            .preview-table td:nth-child(n+9),
            .student-table th:nth-child(n+9),
            .student-table td:nth-child(n+9) {
                display: none;
            }

            .preview-table th,
            .preview-table td,
            .student-table th,
            .student-table td {
                padding: 0.75rem 1rem;
                max-width: 120px;
            }
        }

        @media (max-width: 768px) {
            .preview-table th:nth-child(n+7),
            .preview-table td:nth-child(n+7),
            .student-table th:nth-child(n+7),
            .student-table td:nth-child(n+7) {
                display: none;
            }

            .modal-content {
                width: 98%;
                max-height: 95vh;
            }

            .import-section {
                padding: 0.75rem;
            }

            .preview-table th,
            .preview-table td,
            .student-table th,
            .student-table td {
                padding: 0.5rem 0.75rem;
                max-width: 100px;
                font-size: 0.875rem;
            }

            .form-actions {
                padding: 1rem;
                flex-direction: column;
                gap: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .preview-table th:nth-child(n+5),
            .preview-table td:nth-child(n+5),
            .student-table th:nth-child(n+5),
            .student-table td:nth-child(n+5) {
                display: none;
            }

            .modal-body {
                padding: 0.75rem;
            }

            .preview-table-container,
            .student-table-container {
                padding: 0.75rem;
            }

            .preview-table th,
            .preview-table td,
            .student-table th,
            .student-table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.75rem;
                max-width: 80px;
            }

            .form-actions {
                padding: 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .import-controls {
                gap: 0.5rem;
            }

            .import-note {
                font-size: 0.65rem;
            }
        }

        /* View Modal Styles */
        #viewModal .modal-content {
            max-width: 900px;
            width: 90%;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            background: var(--card-bg);
        }

        #viewModal .modal-header {
            padding: var(--spacing-xl) var(--spacing-2xl);
            background: var(--primary-gradient);
            border-top-left-radius: var(--radius-xl);
            border-top-right-radius: var(--radius-xl);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        #viewModal .modal-title {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--whitefont-color);
            letter-spacing: 0.02em;
        }

        #viewModal .close-btn {
            background: none;
            border: none;
            font-size: 1.75rem;
            cursor: pointer;
            color: var(--whitefont-color);
            padding: var(--spacing-sm);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
        }

        #viewModal .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        #viewModal .modal-body {
            padding: var(--spacing-2xl);
            max-height: 70vh;
            overflow-y: auto;
            background: linear-gradient(180deg, #f9fafb, #ffffff);
        }

        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--inputfield-color);
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .detail-row:hover {
            background: var(--inputfieldhover-color);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .detail-row strong {
            flex: 0 0 180px;
            font-weight: 600;
            color: var(--blackfont-color);
            font-size: var(--font-size-base);
            letter-spacing: 0.01em;
        }

        .detail-row span,
        .detail-row div {
            flex: 1;
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            line-height: 1.5;
        }

        .schedule-details {
            padding-left: var(--spacing-lg);
            color: var(--grayfont-color);
            width: 100%;
        }

        .schedule-details .schedule-item {
            margin-bottom: var(--spacing-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: var(--font-size-sm);
            line-height: 1.4;
        }

        .schedule-details .schedule-item::before {
            content: "•";
            color: var(--primary-blue);
            font-size: 1.2rem;
            margin-right: var(--spacing-sm);
        }

        .detail-row .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: var(--font-size-sm);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .detail-row .status-badge.active {
            background-color: rgba(34, 197, 94, 0.15);
            color: var(--success-green);
        }

        .detail-row .status-badge.inactive {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger-red);
        }

        #viewModal .form-actions {
            padding: var(--spacing-xl) var(--spacing-2xl);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            background: var(--card-bg);
        }

        #viewModal .btn {
            padding: var(--spacing-md) var(--spacing-xl);
            font-size: var(--font-size-base);
            font-weight: 600;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
        }

        #viewModal .btn-secondary {
            background: var(--medium-gray);
            color: var(--whitefont-color);
            box-shadow: var(--shadow-sm);
        }

        #viewModal .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        #viewContent {
            padding: 30px;
        }

        @media (max-width: 768px) {
            #viewModal .modal-content {
                width: 98%;
                max-height: 95vh;
            }

            #viewModal .modal-header {
                padding: var(--spacing-lg) var(--spacing-xl);
            }

            #viewModal .modal-body {
                padding: var(--spacing-lg);
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-sm);
                padding: var(--spacing-md);
                margin-bottom: var(--spacing-md);
            }

            .detail-row strong {
                flex: none;
                width: 100%;
                font-size: var(--font-size-sm);
            }

            .detail-row span,
            .detail-row div {
                font-size: 0.875rem;
            }

            .schedule-details {
                padding-left: var(--spacing-sm);
            }

            .schedule-details .schedule-item {
                font-size: 0.875rem;
                margin-bottom: var(--spacing-xs);
            }

            #viewModal .form-actions {
                padding: var(--spacing-md) var(--spacing-lg);
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            #viewModal .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            #viewModal .modal-header {
                padding: var(--spacing-md);
            }

            #viewModal .modal-body {
                padding: var(--spacing-md);
            }

            .detail-row {
                padding: var(--spacing-sm);
                margin-bottom: var(--spacing-sm);
            }

            .detail-row strong {
                font-size: 0.875rem;
            }

            .detail-row span,
            .detail-row div {
                font-size: 0.75rem;
            }

            .schedule-details .schedule-item {
                font-size: 0.75rem;
            }

            #viewModal .form-actions {
                padding: var(--spacing-sm);
            }
        }

        .required-asterisk {
            color: red;
            font-size: 1.2em;
            vertical-align: top;
        }
    </style>