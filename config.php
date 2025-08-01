<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'attendance');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        die("Connection failed. Please try again later.");
    }
}

// Utility functions
function generateSessionToken($length = 64) {
    try {
        return bin2hex(random_bytes($length / 2));
    } catch (Exception $e) {
        error_log("Token generation error: " . $e->getMessage());
        die("Failed to generate session token.");
    }
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    // At least 8 characters, one uppercase, one lowercase, one number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Session management
function createUserSession($userId) {
    $pdo = getDBConnection();
    $sessionToken = generateSessionToken();
    $expiresAt = date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60)); // 7 days
    $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    // $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $lastActivity = date('Y-m-d H:i:s'); // Set initial last activity to now

    try {
        // Clean up expired sessions for the user
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND expires_at < NOW()");
        $stmt->execute([$userId]);

        // Create new session
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_token, device_info, ip_address, expires_at, created_at, last_activity)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$userId, $sessionToken, $deviceInfo, $ipAddress, $expiresAt, $lastActivity]);

        // Set secure cookie
        $cookieOptions = [
            'expires' => time() + (7 * 24 * 60 * 60),
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        setcookie('attendance_session', $sessionToken, $cookieOptions);

        // Store session token in session for validation
        $_SESSION['session_token'] = $sessionToken;

        return $sessionToken;
    } catch (PDOException $e) {
        error_log("Session creation error: " . $e->getMessage());
        throw new Exception("Failed to create user session.");
    }
}
function validateSession() {
    if (!isset($_COOKIE['attendance_session']) || !isset($_SESSION['session_token']) || $_COOKIE['attendance_session'] !== $_SESSION['session_token']) {
        return false;
    }

    $pdo = getDBConnection();
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, s.expires_at, s.session_token
            FROM users u 
            JOIN user_sessions s ON u.id = s.user_id 
            WHERE s.session_token = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$_COOKIE['attendance_session']]);
        $user = $stmt->fetch();

        if ($user) {
            // Update last_activity
            $stmt = $pdo->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_token = ?");
            $stmt->execute([$_COOKIE['attendance_session']]);
            return $user;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Session validation error: " . $e->getMessage());
        return false;
    }
}
function destroySession() {
    if (isset($_COOKIE['attendance_session'])) {
        $pdo = getDBConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$_COOKIE['attendance_session']]);
        } catch (PDOException $e) {
            error_log("Session destruction error: " . $e->getMessage());
        }

        // Clear cookie
        $cookieOptions = [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        setcookie('attendance_session', '', $cookieOptions);
    }

    // Clear session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
?>