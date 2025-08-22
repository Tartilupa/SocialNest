<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug function
function debug_log($message) {
    error_log("[PROFILE DEBUG] " . $message);
    if (isset($_GET['debug'])) {
        echo "<div class='debug-message'><small><strong>DEBUG:</strong> " . htmlspecialchars($message) . "</small></div>";
    }
}

debug_log("Profile page loaded");

require_once __DIR__ . '/db.php';

// Check if database connection exists
if (!isset($pdo)) {
    die("Database connection not found. Check your db.php file.");
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    debug_log("User not logged in, redirecting to login.php");
    header('Location: index.html');
    exit;
}

debug_log("User ID from session: " . $_SESSION['user_id']);

// Fetch user info with error handling
try {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        debug_log("User not found in database for ID: " . $user_id);
        die("User not found.");
    }
    
    debug_log("User found: " . $user['username']);
} catch (PDOException $e) {
    debug_log("Database error fetching user: " . $e->getMessage());
    die("Database error: " . $e->getMessage());
}

$success = $error = "";

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate CSRF token for POST requests
function validate_csrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        global $error;
        $error = "Invalid security token. Please try again.";
        return false;
    }
    return true;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    debug_log("Processing profile update");
    
    if (!validate_csrf()) {
        debug_log("CSRF validation failed for profile update");
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        
        debug_log("Profile update data - Name: $name, Email: $email");

        if ($name === "" || $email === "") {
            $error = "Name and email cannot be empty.";
            debug_log("Validation failed: empty fields");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
            debug_log("Validation failed: invalid email");
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
                $stmt->execute(['name' => $name, 'email' => $email, 'id' => $user_id]);
                $success = "Profile updated successfully.";
                debug_log("Profile updated successfully");
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->execute(['id' => $user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
                debug_log("Database error updating profile: " . $e->getMessage());
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    debug_log("Processing password change");
    
    if (!validate_csrf()) {
        debug_log("CSRF validation failed for password change");
    } else {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if ($new !== $confirm) {
            $error = "New passwords do not match.";
            debug_log("Password change failed: passwords don't match");
        } elseif (strlen($new) < 6) {
            $error = "New password must be at least 6 characters.";
            debug_log("Password change failed: too short");
        } elseif (!password_verify($current, $user['password'])) {
            $error = "Current password is incorrect.";
            debug_log("Password change failed: incorrect current password");
        } else {
            try {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = :pw WHERE id = :id");
                $stmt->execute(['pw' => $hash, 'id' => $user_id]);
                $success = "Password changed successfully.";
                debug_log("Password changed successfully");
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
                debug_log("Database error changing password: " . $e->getMessage());
            }
        }
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    debug_log("Processing profile picture upload");
    
    if (!validate_csrf()) {
        debug_log("CSRF validation failed for picture upload");
    } else {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            debug_log("File upload - Name: " . $file['name'] . ", Size: " . $file['size'] . ", Type: " . $file['type']);
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = "Only JPG, PNG, GIF, and WebP files are allowed.";
                debug_log("Upload failed: invalid MIME type");
            } elseif (!in_array($file_extension, $allowed_extensions)) {
                $error = "Only JPG, PNG, GIF, and WebP file extensions are allowed.";
                debug_log("Upload failed: invalid file extension");
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = "File is too large (max 5MB).";
                debug_log("Upload failed: file too large");
            } else {
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_dir = __DIR__ . '/uploads/profiles/';
                
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        $error = "Failed to create upload directory.";
                        debug_log("Failed to create upload directory");
                    }
                }
                
                if (!isset($error)) {
                    $dest = $upload_dir . $filename;
                    debug_log("Attempting to move file to: $dest");
                    
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        // Delete old picture if exists
                        if ($user['profile_picture'] && file_exists($upload_dir . $user['profile_picture'])) {
                            unlink($upload_dir . $user['profile_picture']);
                            debug_log("Deleted old profile picture");
                        }
                        
                        try {
                            $stmt = $pdo->prepare("UPDATE users SET profile_picture = :pic WHERE id = :id");
                            $stmt->execute(['pic' => $filename, 'id' => $user_id]);
                            $success = "Profile picture updated successfully.";
                            debug_log("Profile picture updated successfully");
                            
                            // Refresh user data
                            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
                            $stmt->execute(['id' => $user_id]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            $error = "Database error: " . $e->getMessage();
                            debug_log("Database error updating profile picture: " . $e->getMessage());
                        }
                    } else {
                        $error = "Failed to upload file.";
                        debug_log("Failed to move uploaded file");
                    }
                }
            }
        } else {
            $upload_error = $_FILES['profile_picture']['error'] ?? 'No file';
            $error = "No file uploaded or upload error: " . $upload_error;
            debug_log("Upload error: " . $upload_error);
        }
    }
}

// Account statistics with error handling
try {
    debug_log("Fetching post count for user: " . $user['username']);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author = :username");
    $stmt->execute(['username' => $user['username']]);
    $post_count = $stmt->fetchColumn();
    debug_log("Post count: " . $post_count);
} catch (PDOException $e) {
    debug_log("Error fetching post count: " . $e->getMessage());
    $post_count = 0;
}

try {
    $reg_date = new DateTime($user['created_at']);
    $now = new DateTime();
    $days_active = $reg_date->diff($now)->days + 1;
    debug_log("Days active: " . $days_active);
} catch (Exception $e) {
    debug_log("Error calculating days active: " . $e->getMessage());
    $days_active = 0;
}

// Helper functions
function get_user_initials($username) {
    $words = explode(' ', trim($username));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($username, 0, 2));
}

function profile_picture_url($user) {
    if ($user['profile_picture'] && file_exists(__DIR__ . '/uploads/profiles/' . $user['profile_picture'])) {
        return 'uploads/profiles/' . htmlspecialchars($user['profile_picture']);
    }
    return null;
}

function format_date($date_string) {
    try {
        $date = new DateTime($date_string);
        return $date->format('M j, Y');
    } catch (Exception $e) {
        return 'Unknown';
    }
}

debug_log("Rendering HTML");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            position: relative;
            border: 4px solid rgba(255,255,255,0.3);
            overflow: hidden;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 300;
        }

        .profile-info p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .form-section h3 {
            color: #495057;
            margin-bottom: 25px;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        }

        .btn-danger:hover {
            box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .danger-zone {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
        }

        .danger-zone h3 {
            color: #721c24;
            margin-bottom: 15px;
        }

        .danger-zone p {
            color: #856404;
            margin-bottom: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .modal-content h3 {
            margin-bottom: 20px;
            color: #495057;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .debug-message {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
        }

        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .file-upload-area.dragover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .upload-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .profile-header {
                padding: 30px 20px;
            }
            
            .content {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-info h1 {
                font-size: 2rem;
            }
        }

        .password-strength {
            margin-top: 5px;
            font-size: 0.8rem;
        }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['debug'])): ?>
            <div style="padding: 20px; background: #f8f9fa;">
                <h4>Debug Information</h4>
                <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Profile Picture:</strong> <?php echo htmlspecialchars($user['profile_picture'] ?? 'None'); ?></p>
                <p><strong>Upload Directory:</strong> <?php echo __DIR__ . '/uploads/profiles/'; ?></p>
                <p><strong>Directory Exists:</strong> <?php echo is_dir(__DIR__ . '/uploads/profiles/') ? 'Yes' : 'No'; ?></p>
                <p><strong>Directory Writable:</strong> <?php echo is_writable(__DIR__ . '/uploads/profiles/') ? 'Yes' : 'No'; ?></p>
            </div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-avatar">
                <?php 
                $profile_pic = profile_picture_url($user);
                if ($profile_pic): ?>
                    <img src="<?php echo $profile_pic; ?>" alt="Profile Picture">
                <?php else: ?>
                    <?php echo get_user_initials($user['username']); ?>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>

        <div class="content">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-edit"></i>
                    <div class="stat-number"><?php echo $post_count; ?></div>
                    <div class="stat-label">Posts</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="stat-number"><?php echo format_date($user['created_at'] ?? ''); ?></div>
                    <div class="stat-label">Joined</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="stat-number"><?php echo $days_active; ?></div>
                    <div class="stat-label">Days Active</div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-user-edit"></i> Edit Profile</h3>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-camera"></i> Profile Picture</h3>
                <form method="post" enctype="multipart/form-data" id="uploadForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p>Click to select or drag and drop your image here</p>
                        <small>JPG, PNG, GIF, WebP up to 5MB</small>
                    </div>
                    <input type="file" id="fileInput" name="profile_picture" accept="image/*" style="display: none;">
                    <button type="submit" name="upload_picture" class="btn" style="margin-top: 15px;">
                        <i class="fas fa-upload"></i> Upload Picture
                    </button>
                </form>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-lock"></i> Change Password</h3>
                <form method="post" id="passwordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                            <div id="passwordStrength" class="password-strength"></div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                            <div id="passwordMatch" class="password-strength"></div>
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>

            <div class="danger-zone">
                <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                <p>Once you delete your account, there is no going back. Please be certain.</p>
                <button class="btn btn-danger" onclick="document.getElementById('deleteModal').style.display='flex'">
                    <i class="fas fa-trash-alt"></i> Delete Account
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <h3><i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i> Delete Account</h3>
            <p>This action cannot be undone. Type your username to confirm:</p>
            <input type="text" id="confirmUsername" class="form-control" placeholder="Enter your username" style="margin: 20px 0;">
            <div class="modal-buttons">
                <button class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
                <button class="btn" onclick="document.getElementById('deleteModal').style.display='none'">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
            <div id="deleteError" style="color: #dc3545; margin-top: 15px;"></div>
        </div>
    </div>

    <script>
        // Delete account confirmation
        function confirmDelete() {
            const input = document.getElementById('confirmUsername').value;
            const username = <?php echo json_encode($user['username']); ?>;
            if (input === username) {
                if (confirm('Are you absolutely sure? This cannot be undone!')) {
                    window.location = 'delete_account.php';
                }
            } else {
                document.getElementById('deleteError').textContent = "Username does not match.";
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            const strengthElement = document.getElementById('passwordStrength');
            if (password.length === 0) {
                strengthElement.textContent = '';
                return;
            }

            if (strength < 3) {
                strengthElement.textContent = 'Weak password';
                strengthElement.className = 'password-strength strength-weak';
            } else if (strength < 4) {
                strengthElement.textContent = 'Medium password';
                strengthElement.className = 'password-strength strength-medium';
            } else {
                strengthElement.textContent = 'Strong password';
                strengthElement.className = 'password-strength strength-strong';
            }
        }

        // Password confirmation validation
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchElement = document.getElementById('passwordMatch');

            if (confirmPassword.length === 0) {
                matchElement.textContent = '';
                return;
            }

            if (newPassword === confirmPassword) {
                matchElement.textContent = 'Passwords match';
                matchElement.className = 'password-strength strength-strong';
            } else {
                matchElement.textContent = 'Passwords do not match';
                matchElement.className = 'password-strength strength-weak';
            }
        }

        // File upload drag and drop
        const fileUploadArea = document.querySelector('.file-upload-area');
        const fileInput = document.getElementById('fileInput');

        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileUploadText(files[0].name);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                updateFileUploadText(e.target.files[0].name);
            }
        });

        function updateFileUploadText(filename) {
            const uploadArea = document.querySelector('.file-upload-area p');
            uploadArea.textContent = `Selected: ${filename}`;
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');

            if (newPasswordInput) {
                newPasswordInput.addEventListener('input', (e) => {
                    checkPasswordStrength(e.target.value);
                });
            }

            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            }

            if (newPasswordInput) {
                newPasswordInput.addEventListener('input', checkPasswordMatch);
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
