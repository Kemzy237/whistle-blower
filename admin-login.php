<?php
// admin-login.php
session_start();

// Check if admin is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin-dashboard.php');
    exit;
}

$error = '';
    $success = '';
if (isset($_GET['state']) && $_GET['state'] === "success") {
    $success = "success";
}else if (isset($_GET["state"]) && $_GET["state"] === "error") {
    $error = "error";
}else{
    $error = '';
    $success = '';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - WhistleGuard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="inc/background.css">
    <link rel="icon" type="image/svg+xml" href="https://raw.githubusercontent.com/fortawesome/Font-Awesome/6.x/svgs/solid/shield-halved.svg">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0e1a;
            color: #ffffff;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        .glass-card {
            background: rgba(20, 24, 36, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .btn-primary-glass {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.9), rgba(124, 58, 237, 0.9));
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
            width: 100%;
        }
        
        .btn-primary-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.95), rgba(139, 92, 246, 0.95));
            color: white;
        }
        
        .btn-outline-glass {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: #e0e0e0;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-outline-glass:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .form-control-glass {
            background: rgba(26, 31, 45, 0.8);
            backdrop-filter: blur(8px);
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
        
        .form-control-glass::placeholder {
            color: #5a6178;
        }
        
        .content-wrapper {
            position: relative;
            z-index: 2;
        }
        
        .text-muted-custom {
            color: #8b92b0;
        }
        
        .login-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.2), rgba(124, 58, 237, 0.2));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 1px solid rgba(79, 70, 229, 0.3);
        }
        
        .login-icon i {
            font-size: 2rem;
            color: #4f46e5;
        }
        
        .alert-custom {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        
        .security-notice {
            background: rgba(79, 70, 229, 0.1);
            border-left: 3px solid #4f46e5;
            padding: 12px;
            border-radius: 10px;
            font-size: 0.8rem;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .navbar-glass {
            background: rgba(15, 19, 34, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 16px 0;
            position: relative;
            z-index: 100;
        }
        
        @media (max-width: 768px) {
            .glass-card {
                margin: 0 15px;
            }
        }
    </style>
</head>
<body>
    <?php include "inc/background.html"; ?>
    
    <nav class="navbar-glass fixed-top">
        <div class="container" style="display: flex; justify-content:space-between;">
            <a class="navbar-brand text-white fw-bold fs-4" href="index.php">
                <i class="fas fa-shield-alt me-2" style="color: #4f46e5;"></i>WhistleGuard
            </a>
            <a href="index.php" class="btn-outline-glass" style="padding: 6px 16px;">
                <i class="fas fa-home me-1"></i>Back to Home
            </a>
        </div>
    </nav>
    
    <div class="content-wrapper" style="margin-top: 30px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7 col-11">
                    <div class="glass-card p-4 p-md-5 animate-fade-up">
                        <div class="login-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        
                        <h3 class="text-center fw-bold mb-2">Admin Access</h3>
                        <p class="text-center text-muted-custom small mb-4">
                            Enter your credentials to access the admin panel
                        </p>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="app/admin-login.php" autocomplete="off">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0 border-secondary" style="border: 1px solid rgba(255,255,255,0.08); border-right: none; border-radius: 12px 0 0 12px;">
                                        <i class="fas fa-envelope" style="color: #4f46e5;"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control form-control-glass" 
                                           style="border-radius: 0 12px 12px 0; border-left: none;"
                                           placeholder="admin@example.com" required autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0 border-secondary" style="border: 1px solid rgba(255,255,255,0.08); border-right: none; border-radius: 12px 0 0 12px;">
                                        <i class="fas fa-lock" style="color: #4f46e5;"></i>
                                    </span>
                                    <input type="password" name="password" class="form-control form-control-glass" 
                                           style="border-radius: 0 12px 12px 0; border-left: none;"
                                           placeholder="Enter your password" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-primary-glass">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
                            </button>
                        </form>
                        
                        <hr class="my-4" style="border-color: rgba(255,255,255,0.05);">
                        
                        <div class="security-notice">
                            <i class="fas fa-shield-alt me-2" style="color: #4f46e5;"></i>
                            <small>This area is restricted to authorized personnel only. All login attempts are logged for security purposes.</small>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted-custom">
                                <i class="fas fa-lock me-1"></i>Secure SSL Encrypted Connection
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="container py-4 mt-4">
            <div class="text-center">
                <small class="text-muted-custom">© 2026 WhistleGuard. All rights reserved.</small>
            </div>
        </footer>
    </div>

    <!-- <?php $pass = password_hash(123, PASSWORD_DEFAULT);
    echo $pass; ?> -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Console warning for unauthorized access attempts
        console.log('%c⚠️ ADMIN ACCESS AREA ⚠️', 'color: #ef4444; font-size: 14px; font-weight: bold;');
        console.log('%cThis area is restricted. All login attempts are monitored and logged.', 'color: #f59e0b; font-size: 12px;');
        
        // Prevent right-click on login form (optional security measure)
        document.querySelector('form')?.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
    </script>
    <script src="inc/background.js"></script>
</body>
</html>