<?php
// admin-users.php (Profile Page with Notification Implementation)
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: admin-login.php");
    exit();
}

// Include database connection
require_once 'db_connection.php';
require_once 'app/model/report.php';

// Get admin info from session
$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$adminEmail = $_SESSION['admin_email'] ?? '';

// Fetch complete user data from database
$user = get_user_by_id($conn, $adminId);

if (!$user) {
    header('Location: admin-logout.php');
    exit;
}

// Fetch user notification settings
$userSettings = get_user_notification_settings($conn, $adminId);

$emailNotifications = $userSettings['notification_enabled'] ?? true;
$pushToken = $userSettings['push_notification_token'] ?? '';
$deviceType = $userSettings['device_type'] ?? 'web';

// Handle profile update
$successMessage = '';
$errorMessage = '';

// Update profile information
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        
        if (empty($name) || empty($email)) {
            $errorMessage = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Please enter a valid email address.';
        } else {
            $data = array($email, $adminId);

            if (check_if_email_exists($conn, $data)) {
                $errorMessage = 'Email already exists for another user.';
            } else {
                $data = array($name, $email, $adminId);
                if (update_user($conn, $data)) {
                    $_SESSION['admin_name'] = $name;
                    $_SESSION['admin_email'] = $email;
                    $adminName = $name;
                    $adminEmail = $email;
                    $successMessage = 'Profile updated successfully!';
                    
                    $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
                    $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

                    $categoryLogData = [
                        'admin_user_id'   => $adminId,
                        'action'          => 'profile_updated',
                        'ip_hash'         => $ipHash,
                        'user_agent_hash' => $userAgentHash,
                        'metadata'        => json_encode(['updated_fields' => ['name', 'email']]),
                        'created_at'      => date('Y-m-d H:i:s') // Replacing NOW() with a PHP timestamp
                    ];
                    log_system_action($conn, 'audit_logs', $categoryLogData);
                } else {
                    $errorMessage = 'Failed to update profile. Please try again.';
                }
            }
        }
    }
    
    // Change password
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = 'All password fields are required.';
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = 'New password must be at least 6 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'New passwords do not match.';
        } else {
            if (password_verify($currentPassword, $user['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $data = array($hashedPassword, $adminId);
                if (update_user_password($conn, $data)) {
                    $successMessage = 'Password changed successfully!';
                    
                    $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
                    $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

                    $categoryLogData = [
                        'admin_user_id'   => $adminId,
                        'action'          => 'password_changed',
                        'ip_hash'         => $ipHash,
                        'user_agent_hash' => $userAgentHash,
                        'created_at'      => date('Y-m-d H:i:s') // Replacing NOW() with a PHP timestamp
                    ];
                    log_system_action($conn, 'audit_logs', $categoryLogData);
                    
                    // Send notification about password change
                    sendNotification($adminId, 'Password Changed', 'Your admin password was successfully changed.', 'security');
                } else {
                    $errorMessage = 'Failed to change password. Please try again.';
                }
            } else {
                $errorMessage = 'Current password is incorrect.';
            }
        }
    }
    
    // Update notification preferences
    if (isset($_POST['update_preferences'])) {
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $browserNotifications = isset($_POST['browser_notifications']) ? 1 : 0;
        $pushToken = trim($_POST['push_token'] ?? '');
        $deviceType = $_POST['device_type'] ?? 'web';
        
        // Insert or update user settings
        $data = array($adminId, $emailNotifications, $pushToken, $deviceType);
        
        if (insert_update_user_settings($conn, $data)) {
            $successMessage = 'Notification preferences updated successfully!';
            
            // Send test notification if enabled
            if ($emailNotifications && !empty($pushToken)) {
                sendTestNotification($adminId, $pushToken, $deviceType);
            }
            
            // Log the action
            $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

            $categoryLogData = [
                'admin_user_id'   => $adminId,
                'action'          => 'preferences_updated',
                'ip_hash'         => $ipHash,
                'user_agent_hash' => $userAgentHash,
                'metadata'        => json_encode(['email_notifications' => $emailNotifications, 'device_type' => $deviceType]),
                'created_at'      => date('Y-m-d H:i:s') // Replacing NOW() with a PHP timestamp
            ];
            log_system_action($conn, 'audit_logs', $categoryLogData);
        } else {
            $errorMessage = 'Failed to update preferences. Please try again.';
        }
    }
    
    // Register push notification token
    if (isset($_POST['register_push_token'])) {
        $pushToken = trim($_POST['push_token']);
        $deviceType = $_POST['device_type'];
        
        if (!empty($pushToken)) {
            $stmt = $conn->prepare("
                INSERT INTO user_settings (user_id, push_notification_token, device_type, updated_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE  
                    push_notification_token = VALUES(push_notification_token),
                    device_type = VALUES(device_type),
                    updated_at = NOW()
            ");

            $data = array($adminId, $pushToken, $deviceType);
            
            if (push_notification_token($conn, $data)) {
                echo json_encode(['success' => true, 'message' => 'Push token registered successfully']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to register push token']);
                exit;
            }
        }
    }
}

// Get user activity statistics
$totalActions = count_user_activity($conn, $adminId);

$recentActions = get_recent_activity($conn, $adminId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - WhistleGuard</title>
    
    <!-- OneSignal SDK -->
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="inc/background.css">
    <link rel="stylesheet" href="inc/sidebar.css">
    <link rel="icon" type="image/svg+xml" href="https://raw.githubusercontent.com/fortawesome/Font-Awesome/6.x/svgs/solid/shield-halved.svg">
    
    <style>
        /* Same styles as before */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0e1a;
            color: #ffffff;
            overflow-x: hidden;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px 30px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        
        .header-section {
            background: rgba(20, 24, 36, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .welcome-text h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .glass-card {
            background: rgba(20, 24, 36, 0.75);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow: hidden;
            margin-bottom: 25px;
        }
        
        .card-header-custom {
            background: rgba(26, 31, 45, 0.6);
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .form-control-glass {
            background: rgba(26, 31, 45, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: white;
            border-radius: 12px;
            padding: 12px 16px;
        }
        
        .form-control-glass:focus {
            background: rgba(31, 37, 53, 0.9);
            border-color: #4f46e5;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.15);
            outline: none;
        }
        
        .form-label {
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 8px;
            color: #8b92b0;
        }
        
        .btn-primary-glass {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.9), rgba(124, 58, 237, 0.9));
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .btn-primary-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
            color: white;
        }
        
        .btn-secondary-glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: #e0e0e0;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .btn-secondary-glass:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }
        
        .alert-custom {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.2), rgba(124, 58, 237, 0.2));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(79, 70, 229, 0.5);
            margin: 0 auto 20px;
        }
        
        .profile-avatar i {
            font-size: 3rem;
            color: #4f46e5;
        }
        
        .stat-mini-card {
            background: rgba(26, 31, 45, 0.5);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-mini-card:hover {
            background: rgba(79, 70, 229, 0.15);
            transform: translateY(-2px);
        }
        
        .stat-mini-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: #4f46e5;
        }
        
        .role-badge {
            background: rgba(79, 70, 229, 0.2);
            border: 1px solid rgba(79, 70, 229, 0.3);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            display: inline-block;
        }
        
        .text-muted-custom {
            color: #8b92b0;
        }
        
        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.25em;
            cursor: pointer;
        }
        
        .form-switch .form-check-input:checked {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
        
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 70px 15px 15px;
            }
        }
        
        @media (max-width: 768px) {
            .btn-primary-glass, .btn-secondary-glass {
                width: 100%;
                justify-content: center;
            }
            .profile-avatar {
                width: 80px;
                height: 80px;
            }
            .profile-avatar i {
                font-size: 2.5rem;
            }
        }
        
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(79, 70, 229, 0.5);
            border-radius: 10px;
        }
    </style>
</head>
<body>

    <?php 
        include "inc/background.html";
        include "inc/sidebar.php";
    ?>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="welcome-text">
                    <h2 class="fw-bold">
                        <i class="fas fa-user-circle me-2" style="color: #4f46e5;"></i>
                        My Profile
                    </h2>
                    <p class="mb-0">Manage your account settings and notification preferences</p>
                </div>
            </div>
        </div>
        
        <?php if ($successMessage): ?>
        <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $errorMessage; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Profile Information Column -->
            <div class="col-lg-4 mb-4">
                <div class="glass-card">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-id-card me-2" style="color: #4f46e5;"></i>Profile Overview
                        </h5>
                    </div>
                    <div class="p-4 text-center">
                        <div class="profile-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                        <p class="text-muted-custom small mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <span class="role-badge">
                                <i class="fas fa-tag me-1"></i><?php echo ucfirst($user['role']); ?>
                            </span>
                            <span class="role-badge">
                                <i class="fas fa-calendar-alt me-1"></i>Joined <?php echo date('M Y', strtotime($user['created_at'])); ?>
                            </span>
                        </div>
                        <hr class="my-3" style="border-color: rgba(255,255,255,0.05);">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="stat-mini-card">
                                    <div class="stat-mini-number"><?php echo $totalActions; ?></div>
                                    <small class="text-muted-custom">Total Actions</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-mini-card">
                                    <div class="stat-mini-number"><?php echo $user['is_active'] ? '✓' : '✗'; ?></div>
                                    <small class="text-muted-custom">Account Status</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card mt-4">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-history me-2" style="color: #4f46e5;"></i>Recent Activity
                        </h5>
                    </div>
                    <div class="p-3">
                        <?php if (empty($recentActions)): ?>
                        <div class="text-center text-muted-custom py-3">
                            <i class="fas fa-chart-line fa-2x mb-2 d-block"></i>
                            <small>No recent activity recorded</small>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recentActions as $action): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-white border-opacity-10">
                            <div>
                                <i class="fas fa-circle me-2" style="font-size: 0.5rem; color: #4f46e5;"></i>
                                <span class="small"><?php echo str_replace('_', ' ', ucfirst($action['action'])); ?></span>
                            </div>
                            <span class="badge bg-primary-glass"><?php echo $action['count']; ?>x</span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Edit Forms Column -->
            <div class="col-lg-8">
                <!-- Edit Profile Form -->
                <div class="glass-card">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-edit me-2" style="color: #4f46e5;"></i>Edit Profile Information
                        </h5>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control-glass w-100" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control-glass w-100" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update_profile" class="btn-primary-glass">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Change Password Form -->
                <div class="glass-card mt-4">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-key me-2" style="color: #4f46e5;"></i>Change Password
                        </h5>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control-glass w-100" 
                                       placeholder="Enter your current password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control-glass w-100" 
                                       placeholder="Enter new password (min. 6 characters)" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control-glass w-100" 
                                       placeholder="Confirm your new password" required>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="change_password" class="btn-primary-glass">
                                    <i class="fas fa-lock me-2"></i>Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Notification Preferences -->
                <div class="glass-card mt-4">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-bell me-2" style="color: #4f46e5;"></i>Notification Preferences
                        </h5>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="" id="notificationForm">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" 
                                           name="email_notifications" value="1" 
                                           <?php echo $emailNotifications ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="emailNotifications">
                                        Email notifications for new messages
                                    </label>
                                </div>
                                <small class="text-muted-custom d-block mt-1">Receive email alerts when whistleblowers send new messages</small>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="browserNotifications" 
                                           name="browser_notifications" value="1"
                                           <?php echo !empty($pushToken) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="browserNotifications">
                                        Push Notifications (Mobile & Desktop)
                                    </label>
                                </div>
                                <small class="text-muted-custom d-block mt-1">Receive real-time push notifications on your phone and computer</small>
                            </div>
                            <div class="mb-3" id="deviceTypeSection" style="display: <?php echo !empty($pushToken) ? 'block' : 'none'; ?>">
                                <label class="form-label">Device Type</label>
                                <select name="device_type" class="form-control-glass w-100">
                                    <option value="web" <?php echo $deviceType == 'web' ? 'selected' : ''; ?>>Web Browser</option>
                                    <option value="android" <?php echo $deviceType == 'android' ? 'selected' : ''; ?>>Android Phone</option>
                                    <option value="ios" <?php echo $deviceType == 'ios' ? 'selected' : ''; ?>>iOS Phone</option>
                                </select>
                            </div>
                            <input type="hidden" name="push_token" id="pushToken" value="<?php echo htmlspecialchars($pushToken); ?>">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <button type="button" id="testNotificationBtn" class="btn-secondary-glass" style="<?php echo empty($pushToken) ? 'display:none;' : ''; ?>">
                                    <i class="fas fa-bell me-2"></i>Send Test Notification
                                </button>
                                <button type="submit" name="update_preferences" class="btn-primary-glass">
                                    <i class="fas fa-save me-2"></i>Save Preferences
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-3 p-3" style="background: rgba(79, 70, 229, 0.1); border-radius: 12px;">
                            <small class="d-block text-muted-custom">
                                <i class="fas fa-mobile-alt me-2"></i>
                                <strong>How to get notifications on your phone:</strong>
                            </small>
                            <small class="d-block text-muted-custom mt-2">
                                1. Visit this page on your phone's browser<br>
                                2. Allow notifications when prompted<br>
                                3. Enable "Push Notifications" above and save<br>
                                4. You'll receive real-time alerts for new messages
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Account Information -->
                <div class="glass-card mt-4">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-info-circle me-2" style="color: #4f46e5;"></i>Account Information
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            <div class="col-sm-4 mb-2">
                                <small class="text-muted-custom d-block">Account Created</small>
                                <strong><?php echo date('F j, Y g:i A', strtotime($user['created_at'])); ?></strong>
                            </div>
                            <div class="col-sm-4 mb-2">
                                <small class="text-muted-custom d-block">Last Updated</small>
                                <strong><?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></strong>
                            </div>
                            <div class="col-sm-4 mb-2">
                                <small class="text-muted-custom d-block">Account Status</small>
                                <strong class="text-success"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="inc/background.js"></script>
    <script src="inc/sidebar.js"></script>
    <script>
        // OneSignal initialization
        window.OneSignal = window.OneSignal || [];
        OneSignal.push(function() {
            OneSignal.init({
                appId: "YOUR_ONESIGNAL_APP_ID",
                safari_web_id: "YOUR_SAFARI_WEB_ID",
                notifyButton: {
                    enable: true,
                },
                allowLocalhostAsSecureOrigin: true
            });
            
            // Check if user is subscribed
            OneSignal.isPushNotificationsEnabled(function(isEnabled) {
                if (isEnabled) {
                    OneSignal.getUserId(function(userId) {
                        if (userId) {
                            document.getElementById('pushToken').value = userId;
                            // Register the token with your server
                            registerPushToken(userId);
                        }
                    });
                }
            });
            
            // Listen for subscription changes
            OneSignal.on('subscriptionChange', function(isSubscribed) {
                if (isSubscribed) {
                    OneSignal.getUserId(function(userId) {
                        if (userId) {
                            document.getElementById('pushToken').value = userId;
                            registerPushToken(userId);
                        }
                    });
                } else {
                    document.getElementById('pushToken').value = '';
                    registerPushToken('');
                }
            });
        });
        
        function registerPushToken(token) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'register_push_token=1&push_token=' + encodeURIComponent(token) + '&device_type=web'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Push token registered:', token);
                }
            })
            .catch(error => console.error('Error registering push token:', error));
        }
        
        // Show/hide device type section based on browser notifications toggle
        const browserNotifications = document.getElementById('browserNotifications');
        const deviceTypeSection = document.getElementById('deviceTypeSection');
        const testNotificationBtn = document.getElementById('testNotificationBtn');
        
        if (browserNotifications) {
            browserNotifications.addEventListener('change', function() {
                if (this.checked) {
                    deviceTypeSection.style.display = 'block';
                    // Trigger OneSignal to ask for permission
                    OneSignal.push(function() {
                        OneSignal.registerForPushNotifications();
                    });
                } else {
                    deviceTypeSection.style.display = 'none';
                    testNotificationBtn.style.display = 'none';
                }
            });
        }
        
        // Test notification button
        if (testNotificationBtn) {
            testNotificationBtn.addEventListener('click', function() {
                const pushToken = document.getElementById('pushToken').value;
                if (pushToken) {
                    fetch('app/send-test-notification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'push_token=' + encodeURIComponent(pushToken)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Test notification sent! Check your phone.');
                        } else {
                            alert('Failed to send test notification.');
                        }
                    });
                } else {
                    alert('Please enable notifications first.');
                }
            });
        }
        
        // Set active sidebar item
        document.addEventListener('DOMContentLoaded', function() {
            var sidebarItems = document.querySelectorAll('.sidebar-nav li');
            if (sidebarItems[2]) {
                sidebarItems[2].classList.add('active');
            }
        });
        
        console.log('%c🔔 NOTIFICATIONS ENABLED - You will receive alerts on your phone', 'color: #10b981; font-size: 12px; font-weight: bold;');
    </script>
</body>
</html>