<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced QR Scanner</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qr-scanner/1.4.2/qr-scanner.umd.min.js"></script>
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
            font-family: var(--font-family); 
        }

        body {
            background-color: var(--background);
            color: var(--blackfont-color);
            padding: 20px;
        }

        /* QR Scanner Section */
        .qr-scanner-section {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .scanner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .scanner-title {
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: var(--blackfont-color);
        }

        .scanner-subtitle {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
            margin-top: 4px;
        }

        .scan-mode-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .scan-mode-card {
            background: var(--inputfield-color);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 18px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition-normal);
            position: relative;
        }

        .scan-mode-card:hover {
            border-color: var(--primary-blue);
            background: var(--inputfieldhover-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .scan-mode-card.active {
            border-color: var(--primary-blue);
            background: var(--primary-blue-light);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .scan-mode-card.active::before {
            content: '✓';
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--primary-blue);
            color: var(--whitefont-color);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .scan-mode-icon {
            font-size: 2.2rem;
            margin-bottom: 10px;
            color: var(--primary-blue);
        }

        .scan-mode-title {
            font-size: var(--font-size-base);
            font-weight: 600;
            color: var(--blackfont-color);
            margin-bottom: 6px;
        }

        .scan-mode-desc {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
            line-height: 1.4;
        }

        /* Scanner Area */
        .scanner-area {
            background: var(--inputfield-color);
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 30px;
            text-align: center;
            min-height: 200px;
            transition: var(--transition-normal);
        }

        .scanner-area.active {
            border-color: var(--primary-blue);
            background: var(--white);
            border-style: solid;
        }

        .scanner-area.scanning {
            border-color: var(--success-green);
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.05), rgba(34, 197, 94, 0.1));
        }

        /* Camera Scanner Styles */
        .camera-scanner {
            display: none;
        }

        .camera-scanner.active {
            display: block;
        }

        #qr-video {
            width: 100%;
            max-width: 400px;
            height: auto;
            border-radius: var(--radius-md);
            border: 2px solid var(--border-color);
        }

        .camera-controls {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Hardware Scanner Styles */
        .hardware-scanner {
            display: none;
        }

        .hardware-scanner.active {
            display: block;
        }

        .hardware-input {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            font-size: var(--font-size-base);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--white);
            text-align: center;
            margin-bottom: 15px;
            transition: var(--transition-normal);
        }

        .hardware-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .hardware-instruction {
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            margin-top: 10px;
        }

        /* Manual Scanner Styles */
        .manual-scanner {
            display: none;
        }

        .manual-scanner.active {
            display: block;
        }

        .manual-input {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            font-size: var(--font-size-base);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--white);
            text-align: center;
            margin-bottom: 15px;
            transition: var(--transition-normal);
        }

        .manual-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        /* Default Message */
        .default-message {
            color: var(--grayfont-color);
            font-size: var(--font-size-lg);
        }

        .default-message.hidden {
            display: none;
        }

        /* Buttons */
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

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Scanner Status */
        .scanner-status {
            margin-top: 15px;
            padding: 10px 15px;
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            font-weight: 500;
        }

        .scanner-status.ready {
            background: var(--status-present-bg);
            color: var(--success-green);
        }

        .scanner-status.scanning {
            background: var(--primary-blue-light);
            color: var(--primary-blue);
        }

        .scanner-status.error {
            background: var(--status-absent-bg);
            color: var(--danger-red);
        }

        /* Scan Log */
        .scan-log {
            margin-top: 20px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--white);
        }

        .scan-log-header {
            padding: 12px 15px;
            background: var(--inputfield-color);
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            font-size: var(--font-size-sm);
        }

        .scan-log-item {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideIn 0.3s ease;
        }

        .scan-log-item:last-child {
            border-bottom: none;
        }

        .scan-log-item.success {
            background: var(--status-present-bg);
        }

        .scan-log-item.error {
            background: var(--status-absent-bg);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .scan-log-time {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .scan-mode-selector {
                grid-template-columns: 1fr;
            }
            
            .scanner-area {
                padding: 20px;
            }
            
            .camera-controls {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- QR Scanner Section - Add this to your existing mark attendance page -->
    <div class="qr-scanner-section">
        <div class="scanner-header">
            <div>
                <h2 class="scanner-title">QR Code Scanner</h2>
                <p class="scanner-subtitle">Choose your preferred scanning method</p>
            </div>
        </div>

        <!-- Scanning Mode Selection -->
        <div class="scan-mode-selector">
            <div class="scan-mode-card" onclick="selectScanMode('camera')" id="camera-mode">
                <div class="scan-mode-icon">📷</div>
                <div class="scan-mode-title">Camera Scan</div>
                <div class="scan-mode-desc">Use device camera to scan QR codes</div>
            </div>
            
            <div class="scan-mode-card" onclick="selectScanMode('hardware')" id="hardware-mode">
                <div class="scan-mode-icon">🔌</div>
                <div class="scan-mode-title">Hardware Scanner</div>
                <div class="scan-mode-desc">Use wired/wireless QR scanner device</div>
            </div>
            
            <div class="scan-mode-card" onclick="selectScanMode('manual')" id="manual-mode">
                <div class="scan-mode-icon">⌨️</div>
                <div class="scan-mode-title">Manual Entry</div>
                <div class="scan-mode-desc">Type student LRN manually</div>
            </div>
        </div>

        <!-- Scanner Area -->
        <div class="scanner-area" id="scanner-area">
            <div class="default-message" id="default-message">
                <h3>Select a scanning mode above to begin</h3>
                <p>Choose the method that works best with your available equipment</p>
            </div>

            <!-- Camera Scanner -->
            <div class="camera-scanner" id="camera-scanner">
                <video id="qr-video" playsinline></video>
                <canvas id="qr-canvas" style="display: none;"></canvas>
                <div class="camera-controls">
                    <button class="btn btn-primary" onclick="startCamera()" id="start-camera-btn">Start Camera</button>
                    <button class="btn btn-secondary" onclick="stopCamera()" id="stop-camera-btn" style="display: none;">Stop Camera</button>
                </div>
                <div class="scanner-status ready" id="camera-status">Camera ready to scan</div>
            </div>

            <!-- Hardware Scanner -->
            <div class="hardware-scanner" id="hardware-scanner">
                <input type="text" class="hardware-input" id="hardware-input" placeholder="Focus here and scan QR code..." />
                <div class="scanner-status ready" id="hardware-status">Hardware scanner ready</div>
                <div class="hardware-instruction">
                    Point your scanner device at the QR code. The scanned data will automatically appear in the input field.
                </div>
            </div>

            <!-- Manual Scanner -->
            <div class="manual-scanner" id="manual-scanner">
                <input type="text" class="manual-input" id="manual-input" placeholder="Enter Student LRN..." />
                <button class="btn btn-primary" onclick="processManualEntry()">Mark Present</button>
                <div class="scanner-status ready" id="manual-status">Ready for manual entry</div>
            </div>
        </div>

        <!-- Scan Log -->
        <div class="scan-log">
            <div class="scan-log-header">Recent Scans</div>
            <div id="scan-log-items">
                <!-- Scan items will be added here -->
            </div>
        </div>
    </div>

    <script>
        let currentScanMode = null;
        let qrScanner = null;
        let videoStream = null;
        let scanLogCount = 0;
        let isProcessingScan = false;

        // Sample student data for demo - replace with your actual data
        const sampleStudents = {
            'STU001': 'Juan Dela Cruz',
            'STU002': 'Maria Santos', 
            'STU003': 'Pedro Reyes'
        };

        function selectScanMode(mode) {
            // Reset all modes
            document.querySelectorAll('.scan-mode-card').forEach(card => {
                card.classList.remove('active');
            });
            
            // Hide all scanner sections
            document.querySelectorAll('.camera-scanner, .hardware-scanner, .manual-scanner').forEach(section => {
                section.classList.remove('active');
            });
            
            // Hide default message
            document.getElementById('default-message').classList.add('hidden');
            
            // Activate selected mode
            document.getElementById(mode + '-mode').classList.add('active');
            document.getElementById(mode + '-scanner').classList.add('active');
            document.getElementById('scanner-area').classList.add('active');
            
            // Stop any existing scanning
            stopAllScanners();
            
            currentScanMode = mode;
            
            switch(mode) {
                case 'camera':
                    setupCameraMode();
                    break;
                case 'hardware':
                    setupHardwareMode();
                    break;
                case 'manual':
                    setupManualMode();
                    break;
            }
        }

        function setupCameraMode() {
            updateStatus('camera', 'Camera ready to scan', 'ready');
        }

        async function startCamera() {
            const video = document.getElementById('qr-video');
            const canvas = document.getElementById('qr-canvas');
            const ctx = canvas.getContext('2d');
            
            try {
                videoStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' } 
                });
                video.srcObject = videoStream;
                video.play();
                
                document.getElementById('start-camera-btn').style.display = 'none';
                document.getElementById('stop-camera-btn').style.display = 'inline-flex';
                document.getElementById('scanner-area').classList.add('scanning');
                updateStatus('camera', 'Scanning for QR codes...', 'scanning');
                
                // Start QR scanning
                qrScanner = new QrScanner(video, result => {
                    if (!isProcessingScan) {
                        isProcessingScan = true;
                        processScannedCode(result.data, 'Camera Scan');
                        setTimeout(() => {
                            isProcessingScan = false;
                        }, 2000);
                    }
                }, {
                    returnDetailedScanResult: true,
                    highlightScanRegion: true,
                    highlightCodeOutline: true,
                });
                
                await qrScanner.start();
                
            } catch (error) {
                updateStatus('camera', 'Camera error: ' + error.message, 'error');
                addScanLog('Camera Error: ' + error.message, 'error');
            }
        }

        function stopCamera() {
            if (qrScanner) {
                qrScanner.stop();
                qrScanner = null;
            }
            
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
                videoStream = null;
            }
            
            document.getElementById('start-camera-btn').style.display = 'inline-flex';
            document.getElementById('stop-camera-btn').style.display = 'none';
            document.getElementById('scanner-area').classList.remove('scanning');
            updateStatus('camera', 'Camera stopped', 'ready');
            isProcessingScan = false;
        }

        function setupHardwareMode() {
            const input = document.getElementById('hardware-input');
            input.focus();
            input.value = '';
            
            // Handle scanner input
            input.addEventListener('input', function(e) {
                const scannedData = e.target.value.trim();
                
                // Most scanners add newline or have specific length
                if (scannedData.includes('\n') || scannedData.length >= 6) {
                    if (!isProcessingScan) {
                        isProcessingScan = true;
                        processScannedCode(scannedData.replace('\n', ''), 'Hardware Scanner');
                        this.value = '';
                        setTimeout(() => {
                            this.focus();
                            isProcessingScan = false;
                        }, 1000);
                    }
                }
            });
            
            // Keep input focused
            input.addEventListener('blur', () => {
                setTimeout(() => input.focus(), 100);
            });
            
            updateStatus('hardware', 'Hardware scanner ready - scan a QR code', 'ready');
        }

        function setupManualMode() {
            const input = document.getElementById('manual-input');
            input.focus();
            input.value = '';
            
            // Handle Enter key
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    processManualEntry();
                }
            });
            
            updateStatus('manual', 'Ready for manual LRN entry', 'ready');
        }

        function processManualEntry() {
            const input = document.getElementById('manual-input');
            const lrn = input.value.trim();
            
            if (lrn) {
                processScannedCode(lrn, 'Manual Entry');
                input.value = '';
                input.focus();
            }
        }

        function processScannedCode(data, method) {
            // Extract LRN from QR code data (assuming format: LRN,Name or just LRN)
            const lrn = data.split(',')[0].trim();
            
            // Check if student exists in current class
            // This should integrate with your existing student validation logic
            if (validateStudentInCurrentClass(lrn)) {
                // Mark attendance - integrate with your existing attendance function
                markStudentPresent(lrn, method);
                addScanLog(`✅ ${getStudentName(lrn)} (${lrn}) marked present`, 'success', method);
                updateStatus(currentScanMode, `Successfully marked ${getStudentName(lrn)} as present`, 'ready');
            } else {
                addScanLog(`❌ Student LRN "${lrn}" not found in current class`, 'error', method);
                updateStatus(currentScanMode, `Invalid LRN: ${lrn}`, 'error');
            }
        }

        // Integration functions - replace with your actual functions
        function validateStudentInCurrentClass(lrn) {
            // Replace with your actual validation logic
            // This should check if student exists in the currently selected class
            return sampleStudents[lrn] !== undefined;
        }

        function getStudentName(lrn) {
            // Replace with your actual student data retrieval
            return sampleStudents[lrn] || 'Unknown Student';
        }

        function markStudentPresent(lrn, method) {
            // Replace with your existing attendance marking logic
            // This should update your attendanceData and call renderTable()
            console.log(`Marking ${lrn} as present via ${method}`);
            
            // Example integration with existing code:
            // if (current_class_id && attendanceData[today] && attendanceData[today][current_class_id]) {
            //     attendanceData[today][current_class_id][lrn] = {
            //         status: 'Present',
            //         notes: '',
            //         timeChecked: formatDateTime(new Date()),
            //         is_qr_scanned: method !== 'Manual Entry'
            //     };
            //     renderTable(true);
            // }
        }

        function updateStatus(mode, message, type) {
            const statusEl = document.getElementById(mode + '-status');
            if (statusEl) {
                statusEl.textContent = message;
                statusEl.className = `scanner-status ${type}`;
            }
        }

        function addScanLog(message, type, method = '') {
            const logItems = document.getElementById('scan-log-items');
            const item = document.createElement('div');
            item.className = `scan-log-item ${type}`;
            
            const time = new Date().toLocaleTimeString();
            
            item.innerHTML = `
                <div>
                    <strong>${message}</strong><br>
                    <small>Method: ${method} | ${time}</small>
                </div>
            `;
            
            logItems.insertBefore(item, logItems.firstChild);
            scanLogCount++;
            
            // Keep only last 5 entries
            if (scanLogCount > 5) {
                logItems.removeChild(logItems.lastChild);
                scanLogCount--;
            }
        }

        function stopAllScanners() {
            stopCamera();
            isProcessingScan = false;
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            addScanLog('QR Scanner system ready', 'success', 'System');
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            stopAllScanners();
        });
    </script>
</body>
</html>