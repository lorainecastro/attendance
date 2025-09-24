<?php
ob_start();
require 'config.php';
session_start(); // Start session at the top

require 'PHPMailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Validate session
$user = validateSession();
if (!$user) {
    destroySession();
    header("Location: index.php");
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
                $username = trim($_POST['username'] ?? '');

                // Handle file upload
                $picture = $user['picture'] ?? 'no-icon.png';
                $upload_dir = 'uploads/';
                // Ensure upload directory exists with proper permissions
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        throw new Exception('Failed to create upload directory.');
                    }
                }
                // Verify default image exists
                if (!file_exists($upload_dir . 'no-icon.png')) {
                    error_log("Default image 'no-icon.png' not found in uploads/ folder.");
                }

                if (isset($_FILES['profile-picture']) && $_FILES['profile-picture']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['profile-picture']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($ext, $allowed_extensions)) {
                        throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
                    }
                    $picture = 'profile_' . $user['teacher_id'] . '_' . time() . '.' . $ext;
                    $upload_path = $upload_dir . $picture;
                    // Read and write the file content instead of moving
                    $fileContent = file_get_contents($_FILES['profile-picture']['tmp_name']);
                    if (file_put_contents($upload_path, $fileContent) === false) {
                        error_log("Failed to save profile picture to $upload_path");
                        throw new Exception('Failed to save profile picture.');
                    }
                    chmod($upload_path, 0644); // Set file permissions to readable
                }

                if (empty($firstname) || empty($lastname) || empty($username)) {
                    throw new Exception('First name, last name, and username are required.');
                }

                // Check for duplicate username
                $stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE username = ? AND teacher_id != ?");
                $stmt->execute([$username, $user['teacher_id']]);
                if ($stmt->fetch()) {
                    throw new Exception('Username is already taken.');
                }

                $stmt = $pdo->prepare("
                    UPDATE teachers 
                    SET firstname = ?, lastname = ?, institution = ?, picture = ?, username = ?
                    WHERE teacher_id = ?
                ");
                $stmt->execute([$firstname, $lastname, $institution, $picture, $username, $user['teacher_id']]);
                if ($stmt->rowCount() === 0 && !isset($_FILES['profile-picture'])) {
                    throw new Exception('No changes made to profile.');
                }
                $notification = ['message' => 'Profile updated successfully.', 'type' => 'success'];
                $user = array_merge($user, [
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'institution' => $institution,
                    'picture' => $picture,
                    'username' => $username
                ]);
                $_SESSION['teacher_id'] = $user['teacher_id'];
                $_SESSION['session_token'] = $user['session_token'];

            } elseif ($action === 'change_password') {
                $current_password = $_POST['current-password'] ?? '';
                $new_password = $_POST['new-password'] ?? '';
                $confirm_password = $_POST['confirm-password'] ?? '';

                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    throw new Exception('Please fill in all fields.');
                }
                if ($new_password !== $confirm_password) {
                    throw new Exception('Passwords do not match.');
                }
                if (strlen($new_password) < 8) {
                    throw new Exception('Password must be at least 8 characters.');
                }

                $stmt = $pdo->prepare("SELECT password FROM teachers WHERE teacher_id = ?");
                $stmt->execute([$user['teacher_id']]);
                $stored_password = $stmt->fetchColumn();

                if (!$stored_password || !password_verify($current_password, $stored_password)) {
                    throw new Exception('Current password is incorrect.');
                }

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE teachers SET password = ? WHERE teacher_id = ?");
                $stmt->execute([$hashed_password, $user['teacher_id']]);
                if ($stmt->rowCount() === 0) {
                    throw new Exception('No changes made to password.');
                }
                $notification = ['message' => 'Password changed successfully.', 'type' => 'success'];
            }

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode($notification);
                exit;
            }
        } catch (Exception $e) {
            error_log("Action error: " . $e->getMessage());
            $notification = ['message' => $e->getMessage(), 'type' => 'error'];
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
    <title>Profile - Student Attendance System</title>
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
            --blackfont-color: #1e293b;
            --whitefont-color: #ffffff;
            --grayfont-color: #6b7280;
            --primary-gradient: linear-gradient(135deg, #3b82f6, #3b82f6);
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
            --radius-sm: 0.375rem;
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
            min-height: 100vh;
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

        .container {
            width: 100%;
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: var(--spacing-lg);
            gap: var(--spacing-sm);
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
            /* width: 100%; */
        }

        .action-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
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
            padding: var(--spacing-md);
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
            flex-wrap: wrap;
        }

        .form-row {
            display: flex;
            gap: var(--spacing-lg);
            flex-wrap: wrap;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 220px;
        }

        .profile-header {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            width: 100%;
        }

        .profile-header-content {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .profile-image-section {
            text-align: center;
            flex: 0 0 auto;
        }

        .profile-image-container {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #2d2d2d;
            margin: 0 auto 15px;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            flex: 1;
            min-width: 200px;
            text-align: left;
        }

        .profile-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--blackfont-color);
        }

        .profile-role {
            font-size: 16px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .profile-email {
            font-size: 14px;
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
        }

        @media (max-width: 768px) {
            body { 
                padding: var(--spacing-sm); 
            }

            .container {
                padding: var(--spacing-xs);
            }

            .tabs {
                flex-direction: column;
                align-items: stretch;
            }

            .tab {
                padding: 15px;
                min-width: 100%;
            }

            .card {
                padding: 20px;
            }

            .profile-header-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-info {
                text-align: center;
            }

            .form-row .form-group {
                min-width: 100%;
            }
        }

        @media (max-width: 576px) {
            .card,
            .modal-content {
                padding: 20px;
            }

            .action-btn {
                max-width: 100%;
            }

            .modal {
                padding: var(--spacing-sm);
            }
        }
    </style>
</head>
<body>
    <h1>Profile</h1>

    <div class="container">
        <div class="profile-header">
            <div class="profile-header-content">
                <div class="profile-image-section">
                    <div class="profile-image-container">
                        <img src="uploads/<?php echo htmlspecialchars($user['picture'] ?? 'no-icon.png'); ?>" alt="Profile" class="profile-image" id="profilePreview">
                    </div>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h1>
                    <p class="profile-role">Teacher</p>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
        </div>

        <div class="tabs">
            <div class="tab active" data-tab="profile">Profile Information</div>
            <div class="tab" data-tab="password">Password Management</div>
        </div>

        <div class="tab-content active" id="profile">
            <div class="card">
                <h3>Update Profile</h3>
                <form id="profile-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
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
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Your email address" readonly>
                            <div class="error" id="email-error">Invalid email format</div>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" placeholder="Enter your username" required>
                            <div class="error" id="username-error">Username is required</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="institution">Institution</label>
                            <input type="text" id="institution" name="institution" value="<?php echo htmlspecialchars($user['institution'] ?? ''); ?>" placeholder="Enter your institution">
                        </div>
                        <div class="form-group">
                            <label for="profile-picture">Profile Picture</label>
                            <input type="file" id="profile-picture" name="profile-picture" accept="image/*">
                            <div class="error" id="profile-picture-error">Invalid file type</div>
                        </div>
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

        <div class="modal" id="confirmation-modal">
            <div class="modal-content">
                <span class="close-btn" id="close-modal">×</span>
                <h3 id="modal-title">Confirm Action</h3>
                <p id="modal-message"></p>
                <div class="modal-actions" id="modal-actions">
                    <!-- Buttons will be dynamically set by JavaScript -->
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
            const confirmationModal = document.getElementById('confirmation-modal');
            const modalTitle = document.getElementById('modal-title');
            const modalMessage = document.getElementById('modal-message');
            const modalActions = document.getElementById('modal-actions');
            const closeModal = document.getElementById('close-modal');
            const profilePictureInput = document.getElementById('profile-picture');
            const profilePreview = document.getElementById('profilePreview');

            function showError(id, show) {
                document.getElementById(id).style.display = show ? 'block' : 'none';
            }

            function showModal(title, message, actionType) {
                modalTitle.textContent = title;
                modalMessage.textContent = message;
                confirmationModal.style.display = 'flex';

                modalActions.innerHTML = `
                    <button class="action-btn" id="ok-action">Ok</button>
                `;
                const okAction = document.getElementById('ok-action');
                okAction.onclick = () => {
                    confirmationModal.style.display = 'none';
                };
            }

            profilePictureInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

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
                const username = document.getElementById('username').value;
                const profilePicture = document.getElementById('profile-picture').files[0];

                let valid = true;
                showError('firstname-error', !firstname);
                showError('lastname-error', !lastname);
                showError('username-error', !username);
                if (profilePicture) {
                    const allowedExtensions = ['image/jpeg', 'image/png', 'image/gif'];
                    showError('profile-picture-error', !allowedExtensions.includes(profilePicture.type));
                    valid = valid && allowedExtensions.includes(profilePicture.type);
                }

                if (!firstname || !lastname || !username) {
                    valid = false;
                }

                if (valid) {
                    const formData = new FormData(this);
                    fetch('profile.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        showModal(data.type === 'success' ? 'Success' : 'Error', data.message, 'notification');
                        if (data.type === 'success') {
                            if (profilePicture) {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    profilePreview.src = e.target.result;
                                };
                                reader.readAsDataURL(profilePicture);
                            }
                            document.querySelector('.profile-name').textContent = `${firstname} ${lastname}`;
                        }
                    })
                    .catch(error => {
                        showModal('Error', `An error occurred: ${error.message}`, 'notification');
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
                    fetch('profile.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        showModal(data.type === 'success' ? 'Success' : 'Error', data.message, 'notification');
                        if (data.type === 'success') {
                            passwordForm.reset();
                        }
                    })
                    .catch(error => {
                        showModal('Error', `An error occurred: ${error.message}`, 'notification');
                        console.error('Error:', error);
                    });
                }
            });

            closeModal.addEventListener('click', () => {
                confirmationModal.style.display = 'none';
            });
        });
    </script>
</body>
</html>