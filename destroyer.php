<?php
require 'config.php';

try {
    destroySession(); // Handles session and cookie cleanup
    header('Location: index.php');
    exit();
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    header('Location: index.php?error=logout_failed');
    exit();
}
