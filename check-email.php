<?php
require 'config.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ['status' => 'error', 'message' => 'Invalid email format'];
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT isActive, isVerified FROM teachers WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                if ($user['isActive'] == 0 || $user['isVerified'] == 0) {
                    $response = ['status' => 'unverified', 'message' => 'Email is registered but not verified'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Email is already registered and verified'];
                }
            } else {
                $response = ['status' => 'available', 'message' => 'Email is available'];
            }
        } catch (PDOException $e) {
            $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

echo json_encode($response);
exit;
?>