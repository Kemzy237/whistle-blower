<?php
// report-error.php
session_start();

$errorMessage = $_SESSION['last_report_error'] ?? 'An unknown error occurred.';
unset($_SESSION['last_report_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Error - WhistleGuard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="inc/background.css">
    
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
        }
        
        .glass-card {
            background: rgba(20, 24, 36, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 40px;
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
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary-glass:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="d-flex align-items-center" style="min-height: 100vh;">
    <?php include "inc/background.html"; ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="glass-card text-center">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 class="fw-bold mb-3">Submission Failed</h2>
                    <p class="text-muted-custom mb-4"><?php echo htmlspecialchars($errorMessage); ?></p>
                    <a href="index.php" class="btn-primary-glass">
                        <i class="fas fa-arrow-left me-2"></i>Return to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="inc/background.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>