<?php
session_start();

// Include database connection
require_once '../db_connection.php';

// Handle login form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Check if user exists and is admin
        $stmt = $conn->prepare("
            SELECT id, name, email, password, role, is_active, two_factor_secret 
            FROM users 
            WHERE email = ? AND role IN ('admin', 'super_admin')
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active'] != 1) {
                $error = 'Your account has been deactivated. Please contact the system administrator.';
            } else {
                // Login successful
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_role'] = $user['role'];

                                
                // Log the login attempt
                $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
                $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
                
                $stmt = $conn->prepare("
                    INSERT INTO audit_logs (admin_user_id, action, ip_hash, user_agent_hash, metadata, created_at)
                    VALUES (?, 'admin_login', ?, ?, ?, NOW())
                ");
                $metadata = json_encode(['email' => $email, 'role' => $user['role']]);
                $stmt->execute([$user['id'], $ipHash, $userAgentHash, $metadata]);
                
                // Check if 2FA is enabled
                if (!empty($user['two_factor_secret'])) {
                    $_SESSION['2fa_pending'] = true;
                    $_SESSION['2fa_user_id'] = $user['id'];
                    header('Location: admin-2fa.php');
                    exit;
                }
                header("Location: ../admin-dashboard.php");
                exit;
            }
        } else {
            $error = 'Invalid email or password';
            
            // Log failed login attempt
            $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            
            $stmt = $conn->prepare("
                INSERT INTO audit_logs (action, ip_hash, user_agent_hash, metadata, created_at)
                VALUES ('admin_login_failed', ?, ?, ?, NOW())
            ");
            $metadata = json_encode(['email' => $email]);
            $stmt->execute([$ipHash, $userAgentHash, $metadata]);

            $state='error';
                
            header("Location: ../admin-login.php?state=$state");
            exit;
        }
    }
}