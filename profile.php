<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Student Attendance System</title>
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

        h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            margin-bottom: 20px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-size: 14px;
            color: var(--grayfont-color);
            margin-bottom: 5px;
        }

        input, select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            background: var(--primary-color);
            color: var(--whitefont-color);
            margin: 5px;
            transition: var(--transition-normal);
        }

        .action-btn:hover {
            background: var(--primary-hover);
        }

        .danger-btn {
            background: var(--danger-red);
        }

        .danger-btn:hover {
            background: #b91c1c;
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: var(--blackfont-color);
        }

        .checkbox-group input {
            width: auto;
            margin-right: 10px;
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
            border-radius: 12px;
            padding: 20px;
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-lg);
        }

        .modal-content h3 {
            margin-bottom: 15px;
        }

        .close-btn {
            float: right;
            font-size: 20px;
            cursor: pointer;
            color: var(--grayfont-color);
        }

        .close-btn:hover {
            color: var(--primary-color);
        }

        .error {
            color: var(--danger-red);
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        @media (max-width: 1024px) {
            .form-group input, .form-group select {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .card {
                padding: 15px;
            }
        }

        @media (max-width: 576px) {
            .card, .modal-content {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <h1>Account Settings</h1>

    <h2>Profile Information</h2>
    <div class="card">
        <h3>Update Profile</h3>
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" value="John Doe" required>
            <div class="error" id="name-error">Name is required</div>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" value="john.doe@example.com" required>
            <div class="error" id="email-error">Invalid email format</div>
        </div>
        <div class="form-group">
            <label for="role">Role</label>
            <select id="role">
                <option value="teacher" selected>Teacher</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="form-group">
            <label for="profile-picture">Profile Picture</label>
            <input type="file" id="profile-picture" accept="image/*">
        </div>
        <button class="action-btn" id="save-profile">Save Changes</button>
    </div>

    <h2>Password Management</h2>
    <div class="card">
        <h3>Change Password</h3>
        <div class="form-group">
            <label for="current-password">Current Password</label>
            <input type="password" id="current-password" required>
            <div class="error" id="current-password-error">Current password is required</div>
        </div>
        <div class="form-group">
            <label for="new-password">New Password</label>
            <input type="password" id="new-password" required>
            <div class="error" id="new-password-error">Password must be at least 8 characters</div>
        </div>
        <div class="form-group">
            <label for="confirm-password">Confirm New Password</label>
            <input type="password" id="confirm-password" required>
            <div class="error" id="confirm-password-error">Passwords do not match</div>
        </div>
        <button class="action-btn" id="change-password">Change Password</button>
    </div>

    <h2>Account Actions</h2>
    <div class="card">
        <h3>Manage Account</h3>
        <button class="action-btn danger-btn" id="delete-account">Delete Account</button>
    </div>

    <div class="modal" id="confirmation-modal">
        <div class="modal-content">
            <span class="close-btn" id="close-modal">×</span>
            <h3 id="modal-title">Confirm Action</h3>
            <p id="modal-message"></p>
            <button class="action-btn" id="confirm-action">Confirm</button>
            <button class="action-btn" id="cancel-action">Cancel</button>
        </div>
    </div>

    <script>
        const user = {
            name: 'John Doe',
            email: 'john.doe@example.com',
            role: 'teacher'
        };

        const saveProfile = document.getElementById('save-profile');
        const changePassword = document.getElementById('change-password');
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

        saveProfile.addEventListener('click', () => {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const role = document.getElementById('role').value;

            let valid = true;
            showError('name-error', !name);
            showError('email-error', !validateEmail(email));

            if (!name || !validateEmail(email)) {
                valid = false;
            }

            if (valid) {
                user.name = name;
                user.email = email;
                user.role = role;
                localStorage.setItem('user', JSON.stringify(user));
                showModal('Profile Updated', 'Your profile has been successfully updated.');
            }
        });

        changePassword.addEventListener('click', () => {
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
                showModal('Password Changed', 'Your password has been successfully changed.');
            }
        });

        deleteAccount.addEventListener('click', () => {
            showModal('Confirm Account Deletion', 'Are you sure you want to delete your account? This action cannot be undone.', () => {
                localStorage.removeItem('user');
                alert('Account deleted successfully.');
            });
        });

        function showModal(title, message, confirmCallback) {
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            confirmationModal.style.display = 'flex';
            confirmAction.onclick = () => {
                confirmationModal.style.display = 'none';
                if (confirmCallback) confirmCallback();
            };
        }

        cancelAction.addEventListener('click', () => {
            confirmationModal.style.display = 'none';
        });

        closeModal.addEventListener('click', () => {
            confirmationModal.style.display = 'none';
        });
    </script>
</body>
</html>