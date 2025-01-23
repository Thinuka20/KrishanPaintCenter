<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
checkLogin();

// Check if user is admin
$query = "SELECT role FROM users WHERE id = " . $_SESSION['user_id'];
$result = Database::search($query);
$user = $result->fetch_assoc();
if ($user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_name = mysqli_real_escape_string(Database::$connection, $_POST['business_name']);
    $address = mysqli_real_escape_string(Database::$connection, $_POST['address']);
    $phone = mysqli_real_escape_string(Database::$connection, $_POST['phone']);
    $email = mysqli_real_escape_string(Database::$connection, $_POST['email']);

    $uploadPath = 'uploads/business/';
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    // Get current settings
    $query = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
    $result = Database::search($query);
    $current_settings = $result->fetch_assoc();

    // Initialize update fields array
    $updates = [];
    $updates[] = "business_name = '$business_name'";
    $updates[] = "address = '$address'";
    $updates[] = "phone = '$phone'";
    $updates[] = "email = '$email'";

    // Handle logo light upload
    if (isset($_FILES['logo_light']) && $_FILES['logo_light']['error'] === 0) {
        $fileName = 'logo_light_' . time() . '_' . basename($_FILES['logo_light']['name']);
        $targetFile = $uploadPath . $fileName;

        if (move_uploaded_file($_FILES['logo_light']['tmp_name'], $targetFile)) {
            // Delete old file if exists
            if (!empty($current_settings['logo_light']) && file_exists($current_settings['logo_light'])) {
                unlink($current_settings['logo_light']);
            }
            $updates[] = "logo_light = '$targetFile'";
        }
    }

    // Handle logo dark upload
    if (isset($_FILES['logo_dark']) && $_FILES['logo_dark']['error'] === 0) {
        $fileName = 'logo_dark_' . time() . '_' . basename($_FILES['logo_dark']['name']);
        $targetFile = $uploadPath . $fileName;

        if (move_uploaded_file($_FILES['logo_dark']['tmp_name'], $targetFile)) {
            // Delete old file if exists
            if (!empty($current_settings['logo_dark']) && file_exists($current_settings['logo_dark'])) {
                unlink($current_settings['logo_dark']);
            }
            $updates[] = "logo_dark = '$targetFile'";
        }
    }

    // Handle favicon upload
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === 0) {
        $fileName = 'favicon_' . time() . '_' . basename($_FILES['favicon']['name']);
        $targetFile = $uploadPath . $fileName;

        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $targetFile)) {
            // Delete old file if exists
            if (!empty($current_settings['favicon']) && file_exists($current_settings['favicon'])) {
                unlink($current_settings['favicon']);
            }
            $updates[] = "favicon = '$targetFile'";
        }
    }

    // Update database
    if (!empty($updates)) {
        $updateStr = implode(', ', $updates);
        if ($current_settings) {
            $query = "UPDATE settings SET $updateStr WHERE id = {$current_settings['id']}";
        } else {
            $query = "INSERT INTO settings SET $updateStr";
        }
        Database::iud($query);
        $_SESSION['success'] = "Settings updated successfully!";
        header('Location: settings.php');
        exit;
    }
}

// Get current settings
$query = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
$result = Database::search($query);
$settings = $result->fetch_assoc();

// Get all users
$query = "SELECT * FROM users ORDER BY username";
$users = Database::search($query);

include 'header.php';
?>

<div class="container content">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h2>System Settings</h2>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title">Business Settings</h5>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <!-- Business Information -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Business Information</h5>

                        <div class="mb-3">
                            <label class="form-label">Business Name</label>
                            <input type="text" class="form-control" name="business_name"
                                value="<?php echo htmlspecialchars($settings['business_name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone"
                                value="<?php echo htmlspecialchars($settings['phone'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email"
                                value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Logo Management -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Logo Management</h5>

                        <div class="mb-4">
                            <label class="form-label">Light Theme Logo</label>
                            <?php if (!empty($settings['logo_light'])): ?>
                                <div class="mb-2 p-2 border rounded bg-light">
                                    <img src="<?php echo htmlspecialchars($settings['logo_light']); ?>"
                                        alt="Light Logo" class="img-fluid" style="max-height: 100px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="logo_light" accept="image/*">
                            <small class="text-muted">Recommended size: 200x60 pixels</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Dark Theme Logo</label>
                            <?php if (!empty($settings['logo_dark'])): ?>
                                <div class="mb-2 p-2 border rounded bg-dark">
                                    <img src="<?php echo htmlspecialchars($settings['logo_dark']); ?>"
                                        alt="Dark Logo" class="img-fluid" style="max-height: 100px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="logo_dark" accept="image/*">
                            <small class="text-muted">Recommended size: 200x60 pixels</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Favicon</label>
                            <?php if (!empty($settings['favicon'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($settings['favicon']); ?>"
                                        alt="Favicon" style="width: 32px; height: 32px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="favicon" accept="image/*">
                            <small class="text-muted">Recommended size: 32x32 pixels</small>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-paint-brush me-2"></i>Theme Settings
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" id="themeForm">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3">Color Theme</h6>
                        <div class="d-flex flex-wrap gap-3">
                            <!-- Light Theme -->
                            <div class="theme-option">
                                <input type="radio" class="btn-check" name="theme" id="light-theme"
                                    value="light" <?php echo ($settings['theme'] ?? 'light') === 'light' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary theme-preview" for="light-theme">
                                    <div class="theme-sample bg-light">
                                        <div class="theme-sample-header bg-primary"></div>
                                        <div class="theme-sample-sidebar bg-dark"></div>
                                    </div>
                                    Light
                                </label>
                            </div>

                            <!-- Dark Theme -->
                            <div class="theme-option">
                                <input type="radio" class="btn-check" name="theme" id="dark-theme"
                                    value="dark" <?php echo ($settings['theme'] ?? '') === 'dark' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary theme-preview" for="dark-theme">
                                    <div class="theme-sample bg-dark">
                                        <div class="theme-sample-header bg-secondary"></div>
                                        <div class="theme-sample-sidebar bg-black"></div>
                                    </div>
                                    Dark
                                </label>
                            </div>

                            <!-- Blue Theme -->
                            <div class="theme-option">
                                <input type="radio" class="btn-check" name="theme" id="blue-theme"
                                    value="blue" <?php echo ($settings['theme'] ?? '') === 'blue' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary theme-preview" for="blue-theme">
                                    <div class="theme-sample bg-light">
                                        <div class="theme-sample-header bg-primary"></div>
                                        <div class="theme-sample-sidebar bg-info"></div>
                                    </div>
                                    Blue
                                </label>
                            </div>

                            <!-- Green Theme -->
                            <div class="theme-option">
                                <input type="radio" class="btn-check" name="theme" id="green-theme"
                                    value="green" <?php echo ($settings['theme'] ?? '') === 'green' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary theme-preview" for="green-theme">
                                    <div class="theme-sample bg-light">
                                        <div class="theme-sample-header bg-success"></div>
                                        <div class="theme-sample-sidebar bg-success text-white"></div>
                                    </div>
                                    Green
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary" onclick="themeUpdate(<?php echo $_SESSION['user_id']; ?>)">
                        <i class="fas fa-save me-1"></i>Save Theme
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- User Management -->
    <div class="card mb-4 mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">User Management</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="resetPassword(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-key"></i> Reset Password
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-control" name="role" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Alert Helper Function
    function showAlert(type, message) {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alertDiv.style.zIndex = "9999";
        alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(alert => alert.remove());

        // Add to body
        document.body.appendChild(alertDiv);

        // Auto dismiss after 3 seconds
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 3000);
    }

    // Reset Password Function
    function resetPassword(userId) {
        if (confirm('Are you sure you want to reset this user\'s password?')) {
            const newPassword = prompt('Enter new password:');
            if (newPassword) {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('password', newPassword);

                fetch('reset_password.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        showAlert(data.success ? 'success' : 'danger', data.message);
                        if (data.success) {
                            setTimeout(() => window.location.reload(), 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('danger', 'An error occurred. Please try again.');
                    });
            }
        }
    }

    // Delete User Function
    function deleteUser(userId) {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            const formData = new FormData();
            formData.append('user_id', userId);

            fetch('delete_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    showAlert(data.success ? 'success' : 'danger', data.message);
                    if (data.success) {
                        setTimeout(() => window.location.reload(), 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred. Please try again.');
                });
        }
    }

    function themeUpdate(userId) {
        if (confirm('Are you sure you want to update the theme? This action cannot be undone.')) {
            const theme = document.querySelector('input[name="theme"]:checked').value;
            const formData = new FormData();
            formData.append('theme', theme);
            formData.append('userId', userId);

            fetch('update_theme.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    showAlert(data.success ? 'success' : 'danger', data.message);
                    if (data.success) {
                        setTimeout(() => window.location.reload(), 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred. Please try again.');
                });
        }
    }

    // Add User Form Handler
    document.addEventListener('DOMContentLoaded', function() {
        const addUserForm = document.getElementById('addUserForm');
        const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));

        if (addUserForm) {
            addUserForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                fetch('add_user.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        showAlert(data.success ? 'success' : 'danger', data.message);
                        if (data.success) {
                            addUserModal.hide();
                            this.reset();
                            setTimeout(() => window.location.reload(), 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('danger', 'An error occurred. Please try again.');
                    });
            });
        }
    });
</script>

<?php include 'footer.php'; ?>