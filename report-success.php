<?php
// report-success.php
session_start();

// Check if we have a successful submission
if (!isset($_SESSION['last_report_success']) || !isset($_SESSION['last_tracking_code'])) {
    header('Location: index.php');
    exit;
}

$trackingCode = $_SESSION['last_tracking_code'];

// Clear the session variables to prevent showing the same success again
unset($_SESSION['last_report_success']);
unset($_SESSION['last_tracking_code']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Submitted - WhistleGuard</title>
    
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
            position: relative;
        }
        
        .glass-card {
            background: rgba(20, 24, 36, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 20px;
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
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
            color: white;
        }
        
        .tracking-code {
            font-family: monospace;
            font-size: 2rem;
            letter-spacing: 2px;
            background: rgba(79, 70, 229, 0.2);
            padding: 15px 30px;
            border-radius: 12px;
            border: 1px solid rgba(79, 70, 229, 0.3);
            display: inline-block;
        }
        
        .warning-box {
            background: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 8px;
        }
        
        .content-wrapper {
            position: relative;
            z-index: 2;
        }
    </style>
</head>
<body>

    <?php include "inc/background.html" ?>
    
    <div class="content-wrapper container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="glass-card p-5 text-center">
                    <div class="mb-4">
                        <div class="display-1 text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    
                    <h1 class="fw-bold mb-3">Report Submitted Successfully</h1>
                    <p class="lead mb-4 text-muted-custom">Your anonymous report has been securely encrypted and submitted.</p>
                    
                    <div class="mb-4">
                        <h5 class="mb-2">Your Tracking Code</h5>
                        <div class="tracking-code"><?php echo htmlspecialchars($trackingCode); ?></div>
                        <p class="small text-muted-custom mt-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Save this code immediately. You'll need it to track your report.
                        </p>
                    </div>
                    
                    <div class="warning-box mb-4">
                        <i class="fas fa-lock me-2"></i>
                        <strong>Important Security Notice:</strong><br>
                        This page will not be saved in your browser history. 
                        Please write down your tracking code and close this window.
                    </div>
                    
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <button onclick="window.print()" class="btn-primary-glass">
                            <i class="fas fa-print me-2"></i>Print Tracking Code
                        </button>
                        <a href="index.php" class="btn-primary-glass">
                            <i class="fas fa-home me-2"></i>Return Home
                        </a>
                        <button onclick="copyTrackingCode()" class="btn-primary-glass">
                            <i class="fas fa-copy me-2"></i>Copy Code
                        </button>
                    </div>
                </div>
                
                <div class="glass-card p-4 mt-4 text-center">
                    <h6 class="fw-bold mb-2">What's Next?</h6>
                    <p class="small text-muted-custom mb-3">
                        Use your tracking code to check the status of your report and communicate securely with administrators.
                    </p>
                    <a href="index.php#trackReportModal" class="btn-outline-glass" style="padding: 8px 20px; text-decoration: none;">
                        <i class="fas fa-search me-2"></i>Track Your Report
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyTrackingCode() {
            const code = '<?php echo htmlspecialchars($trackingCode); ?>';
            navigator.clipboard.writeText(code).then(() => {
                alert('Tracking code copied to clipboard!');
            }).catch(() => {
                alert('Please copy your tracking code manually: ' + code);
            });
        }
        
        // Warn before leaving/closing
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = 'Make sure you have saved your tracking code before leaving.';
            return 'Make sure you have saved your tracking code before leaving.';
        });
        
        // Remove the beforeunload warning after a few seconds (user had time to copy)
        setTimeout(() => {
            window.removeEventListener('beforeunload', function() {});
        }, 10000);
    </script>

    <script src="inc/background.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>