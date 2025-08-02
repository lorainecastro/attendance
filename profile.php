<?php
ob_start();
require 'config.php';
session_start();

require 'PHPMailer/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if user is logged in using validateSession()
$user = validateSession();
if (!$user) {
    destroySession();
    header("Location: sign-in.php");
    exit();
}

$notification = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        try {
            $pdo = getDBConnection();

            if ($action === 'update_profile') {
                $firstname = trim($_POST['firstname'] ?? '');
                $lastname = trim($_POST['lastname'] ?? '');
                $institution = trim($_POST['institution'] ?? '');

                // Handle file upload
                $picture = $user['picture'];
                if (isset($_FILES['profile-picture']) && $_FILES['profile-picture']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'Uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $ext = pathinfo($_FILES['profile-picture']['name'], PATHINFO_EXTENSION);
                    $picture = 'profile_' . $user['teacher_id'] . '_' . time() . '.' . $ext;
                    move_uploaded_file($_FILES['profile-picture']['tmp_name'], $upload_dir . $picture);
                }

                if (empty($firstname) || empty($lastname)) {
                    $notification = ['message' => 'First name and last name are required.', 'type' => 'error'];
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE teachers 
                        SET firstname = ?, lastname = ?, institution = ?, picture = ?
                        WHERE teacher_id = ?
                    ");
                    $stmt->execute([$firstname, $lastname, $institution, $picture, $user['teacher_id']]);
                    $notification = ['message' => 'Profile updated successfully.', 'type' => 'success'];
                    $user['firstname'] = $firstname;
                    $user['lastname'] = $lastname;
                    $user['institution'] = $institution;
                    $user['picture'] = $picture;
                }
            } elseif ($action === 'change_password') {
                $current_password = $_POST['current-password'] ?? '';
                $new_password = $_POST['new-password'] ?? '';
                $confirm_password = $_POST['confirm-password'] ?? '';

                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $notification = ['message' => 'Please fill in all fields.', 'type' => 'error'];
                } elseif ($new_password !== $confirm_password) {
                    $notification = ['message' => 'Passwords do not match.', 'type' => 'error'];
                } elseif (strlen($new_password) < 8) {
                    $notification = ['message' => 'Password must be at least 8 characters.', 'type' => 'error'];
                } else {
                    $stmt = $pdo->prepare("SELECT password FROM teachers WHERE teacher_id = ?");
                    $stmt->execute([$user['teacher_id']]);
                    $stored_password = $stmt->fetchColumn();

                    if (!password_verify($current_password, $stored_password)) {
                        $notification = ['message' => 'Current password is incorrect.', 'type' => 'error'];
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE teachers SET password = ? WHERE teacher_id = ?");
                        $stmt->execute([$hashed_password, $user['teacher_id']]);
                        $notification = ['message' => 'Password changed successfully.', 'type' => 'success'];
                    }
                }
            } elseif ($action === 'delete_account') {
                $stmt = $pdo->prepare("UPDATE teachers SET isDeleted = 1 WHERE teacher_id = ?");
                $stmt->execute([$user['teacher_id']]);
                $stmt = $pdo->prepare("DELETE FROM teacher_sessions WHERE teacher_id = ?");
                $stmt->execute([$user['teacher_id']]);
                destroySession();
                $notification = ['message' => 'Account deleted successfully.', 'type' => 'success', 'redirect' => 'sign-in.php'];
            }

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode($notification);
                exit;
            } elseif (isset($notification['redirect'])) {
                header("Location: " . $notification['redirect']);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Action error: " . $e->getMessage());
            $notification = ['message' => 'An error occurred. Please try again.', 'type' => 'error'];
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode($notification);
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Student Attendance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-blue-hover: #1d4ed8;
            --primary-blue-light: #dbeafe;
            --success-green: #16a34a;
            --warning-yellow: #ca8a04;
            --danger-red: #dc2626;
            --info-cyan: #0891b2;
            --dark-gray: #374151;
            --medium-gray: #6b7280;
            --light-gray: #d1d5db;
            --background: #f9fafb;
            --white: #ffffff;
            --border-color: #e5e7eb;
            --card-bg: #ffffff;
            --blackfont-color: #111827;
            --whitefont-color: #ffffff;
            --grayfont-color: #6b7280;
            --primary-gradient: linear-gradient(135deg, #2563eb, #a855f7);
            --secondary-gradient: linear-gradient(135deg, #ec4899, #f472b6);
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --inputfield-color: #f3f4f6;
            --inputfieldhover-color: #e5e7eb;
            --font-family: 'Inter', sans-serif;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --transition-fast: 0.15s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
            --transition-slow: 0.5s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
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
            height: 3px;
            width: 60px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .container {
            width: 100%;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: var(--spacing-lg);
        }

        .tab {
            flex: 1;
            padding: var(--spacing-md) var(--spacing-lg);
            text-align: center;
            font-size: var(--font-size-base);
            font-weight: 600;
            color: var(--medium-gray);
            cursor: pointer;
            transition: var(--transition-normal);
            border-bottom: 3px solid transparent;
        }

        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab:hover {
            color: var(--primary-color);
            background-color: var(--primary-blue-light);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            margin-bottom: var(--spacing-lg);
        }

        .card h3 {
            font-size: var(--font-size-lg);
            margin-bottom: var(--spacing-md);
            color: var(--blackfont-color);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        label {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
            font-weight: 600;
        }

        input,
        select {
            width: 100%;
            padding: var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        input[readonly] {
            cursor: not-allowed;
        }

        .action-btn {
            padding: var(--spacing-md) var(--spacing-lg);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            font-weight: 600;
            cursor: pointer;
            background: var(--primary-color);
            color: var(--whitefont-color);
            transition: var(--transition-normal);
        }

        .action-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .danger-btn {
            background: var(--danger-red);
        }

        .danger-btn:hover {
            background: #b91c1c;
        }

        .error {
            color: var(--danger-red);
            font-size: var(--font-size-sm);
            margin-top: var(--spacing-xs);
            display: none;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-lg);
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: var(--spacing-md);
            right: var(--spacing-md);
            font-size: var(--font-size-lg);
            cursor: pointer;
            color: var(--grayfont-color);
        }

        .close-btn:hover {
            color: var(--primary-color);
        }

        .modal-actions {
            display: flex;
            gap: var(--spacing-md);
            justify-content: flex-end;
            margin-top: var(--spacing-lg);
        }

        @media (max-width: 768px) {
            .container {
                padding: var(--spacing-md);
            }

            .tabs {
                flex-direction: column;
            }

            .tab {
                padding: var(--spacing-sm) var(--spacing-md);
            }

            .card {
                padding: var(--spacing-lg);
            }
        }

        @media (max-width: 576px) {
            .card,
            .modal-content {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <h1>Account Settings</h1>
    <div class="container">
        <div class="tabs">
            <div class="tab active" data-tab="profile">Profile Information</div>
            <div class="tab" data-tab="password">Password Management</div>
            <div class="tab" data-tab="actions">Account Actions</div>
        </div>

        <div class="tab-content active" id="profile">
            <div class="card">
                <h3>Update Profile</h3>
                <form id="profile-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" placeholder="Enter your first name" required>
                        <div class="error" id="firstname-error">First name is required</div>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" placeholder="Enter your last name" required>
                        <div class="error" id="lastname-error">Last name is required</div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Your email address" readonly>
                        <div class="error" id="email-error">Invalid email format</div>
                    </div>
                    <div class="form-group">
                        <label for="institution">Institution</label>
                        <input type="text" id="institution" name="institution" value="<?php echo htmlspecialchars($user['institution'] ?? ''); ?>" placeholder="Enter your institution">
                    </div>
                    <div class="form-group">
                        <label for="profile-picture">Profile Picture</label>
                        <input type="file" id="profile-picture" name="profile-picture" accept="image/*">
                        <?php if ($user['picture'] !== 'no-icon.png'): ?>
                            <div>Current: <img src="Uploads/<?php echo htmlspecialchars($user['picture']); ?>" alt="Profile Picture" style="max-width: 100px; margin-top: var(--spacing-sm);"></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="action-btn">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="tab-content" id="password">
            <div class="card">
                <h3>Change Password</h3>
                <form id="password-form">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label for="current-password">Current Password</label>
                        <input type="password" id="current-password" name="current-password" placeholder="Enter current password" required>
                        <div class="error" id="current-password-error">Current password is required</div>
                    </div>
                    <div class="form-group">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" name="new-password" placeholder="Enter new password" required>
                        <div class="error" id="new-password-error">Password must be at least 8 characters</div>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm new password" required>
                        <div class="error" id="confirm-password-error">Passwords do not match</div>
                    </div>
                    <button type="submit" class="action-btn">Change Password</button>
                </form>
            </div>
        </div>

        <div class="tab-content" id="actions">
            <div class="card">
                <h3>Manage Account</h3>
                <button class="action-btn danger-btn" id="delete-account">Delete Account</button>
            </div>
        </div>

        <div class="modal" id="confirmation-modal">
            <div class="modal-content">
                <span class="close-btn" id="close-modal">×</span>
                <h3 id="modal-title">Confirm Action</h3>
                <p id="modal-message"></p>
                <div class="modal-actions">
                    <button class="action-btn" id="confirm-action">Confirm</button>
                    <button class="action-btn" id="cancel-action">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            const profileForm = document.getElementById('profile-form');
            const passwordForm = document.getElementById('password-form');
            const deleteAccount = document.getElementById('delete-account');
            const confirmationModal = document.getElementById('confirmation-modal');
            const modalTitle = document.getElementById('modal-title');
            const modalMessage = document.getElementById('modal-message');
            const confirmAction = document.getElementById('confirm-action');
            const cancelAction = document.getElementById('cancel-action');
            const closeModal = document.getElementById('close-modal');

            function validateEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }

            function showError(id, show) {
                document.getElementById(id).style.display = show ? 'block' : 'none';
            }

            function showModal(title, message, confirmCallback) {
                modalTitle.textContent = title;
                modalMessage.textContent = message;
                confirmationModal.style.display = 'flex';
                confirmAction.onclick = () => {
                    if (confirmCallback) confirmCallback();
                    confirmationModal.style.display = 'none';
                };
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    tab.classList.add('active');
                    document.getElementById(tab.dataset.tab).classList.add('active');
                });
            });

            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const firstname = document.getElementById('firstname').value;
                const lastname = document.getElementById('lastname').value;
                const institution = document.getElementById('institution').value;

                let valid = true;
                showError('firstname-error', !firstname);
                showError('lastname-error', !lastname);

                if (!firstname || !lastname) {
                    valid = false;
                }

                if (valid) {
                    const formData = new FormData(this);
                    fetch('account-settings.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        showModal(data.type === 'success' ? 'Success' : 'Error', data.message);
                        if (data.redirect) {
                            setTimeout(() => window.location.href = data.redirect, 1000);
                        }
                    })
                    .catch(error => {
                        showModal('Error', 'An error occurred. Please try again.');
                        console.error('Error:', error);
                    });
                }
            });

            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const currentPassword = document.getElementById('current-password').value;
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;

                let valid = true;
                showError('current-password-error', !currentPassword);
                showError('new-password-error', newPassword.length < 8);
                showError('confirm-password-error', newPassword !== confirmPassword);

                if (!currentPassword || newPassword.length < 8 || newPassword !== confirmPassword) {
                    valid = false;
                }

                if (valid) {
                    const formData = new FormData(this);
                    fetch('account-settings.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        showModal(data.type === 'success' ? 'Success' : 'Error', data.message);
                        if (data.redirect) {
                            setTimeout(() => window.location.href = data.redirect, 1000);
                        }
                    })
                    .catch(error => {
                        showModal('Error', 'An error occurred. Please try again.');
                        console.error('Error:', error);
                    });
                }
            });

            deleteAccount.addEventListener('click', () => {
                showModal('Confirm Account Deletion', 'Are you sure you want to delete your account? This action cannot be undone.', () => {
                    const formData = new FormData();
                    formData.append('action', 'delete_account');
                    fetch('account-settings.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        showModal(data.type === 'success' ? 'Success' : 'Error', data.message);
                        if (data.redirect) {
                            setTimeout(() => window.location.href = data.redirect, 1000);
                        }
                    })
                    .catch(error => {
                        showModal('Error', 'An error occurred. Please try again.');
                        console.error('Error:', error);
                    });
                });
            });

            cancelAction.addEventListener('click', () => {
                confirmationModal.style.display = 'none';
            });

            closeModal.addEventListener('click', () => {
                confirmationModal.style.display = 'none';
            });
        });
    </script>
</body>
</html>