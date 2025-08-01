<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'attendance');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
function getDBConnection()
{
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        die("Connection failed. Please try again later. $dsn");
    }
}

// Utility functions
function generateSessionToken($length = 64)
{
    try {
        return bin2hex(random_bytes($length / 2));
    } catch (Exception $e) {
        error_log("Token generation error: " . $e->getMessage());
        die("Failed to generate session token.");
    }
}

// Session management
function createUserSession($teacherId)
{
    $pdo = getDBConnection();
    $token = generateSessionToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days')); // Session expires in 30 days

    try {
        // Delete any existing sessions for the user
        $stmt = $pdo->prepare("DELETE FROM teacher_sessions WHERE teacher_id = ?");
        $stmt->execute([$teacherId]);

        // Create new session
        $stmt = $pdo->prepare("
            INSERT INTO teacher_sessions (teacher_id, session_token, expires_at, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$teacherId, $token, $expiresAt]);

        // Store session data
        session_start(); // Ensure session is started
        $_SESSION['teacher_id'] = $teacherId;
        $_SESSION['session_token'] = $token;

        return $token;
    } catch (PDOException $e) {
        error_log("Session creation error: " . $e->getMessage());
        return false;
    }
}

function validateSession()
{
    // session_start();
    if (!isset($_SESSION['teacher_id']) || !isset($_SESSION['session_token'])) {
        error_log("Session validation failed: Missing teacher_id or session_token");
        return false;
    }

    $pdo = getDBConnection();
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, s.expires_at, s.session_token
            FROM teachers u 
            JOIN teacher_sessions s ON u.teacher_id = s.teacher_id 
            WHERE s.teacher_id = ? AND s.session_token = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$_SESSION['teacher_id'], $_SESSION['session_token']]);
        $teacher = $stmt->fetch();

        if ($teacher) {
            error_log("Session validated successfully for teacher_id: {$_SESSION['teacher_id']}");
            return $teacher;
        } else {
            error_log("Session validation failed: No matching session for teacher_id: {$_SESSION['teacher_id']}, token: {$_SESSION['session_token']}");
            destroySession();
            return false;
        }
    } catch (PDOException $e) {
        error_log("Session validation error: " . $e->getMessage());
        return false;
    }
}

function destroySession()
{
    $pdo = getDBConnection(); // Use getDBConnection instead of global $pdo
    session_start(); // Ensure session is started
    if (isset($_SESSION['teacher_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM teacher_sessions WHERE teacher_id = ?");
            $stmt->execute([$_SESSION['teacher_id']]);
        } catch (PDOException $e) {
            error_log("Session deletion error: " . $e->getMessage());
        }
    }
    // Clear session data
    $_SESSION = array(); // Clear all session variables
}