<?php
// destroyer.php
require 'config.php'; // Adjust path if necessary

try {
    // Start the session
    session_start();

    // Call the destroySession function to clear database session
    destroySession();

    // Clear all session data
    session_unset();
    session_destroy();

    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Redirect to landing page
    header('Location: index.php');
    exit();
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    header('Location: index.php?error=logout_failed');
    exit();
}
?>