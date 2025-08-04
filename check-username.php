<?php
require 'config.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');

    if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $response = ['status' => 'error', 'message' => 'Invalid username format'];
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE username = ?");
            $stmt->execute([$username]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $response = ['status' => 'error', 'message' => 'Username is already taken'];
            } else {
                $response = ['status' => 'available', 'message' => 'Username is available'];
            }
        } catch (PDOException $e) {
            $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

echo json_encode($response);
exit;
?>