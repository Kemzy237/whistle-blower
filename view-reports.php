<?php
// track-report.php
session_start();

// Include database connection for stats
include 'db_connection.php';

// Include functions
include "app/model/report.php";

$trackedReport = $_SESSION['tracked_report'] ?? null;
$trackingCode = $_SESSION['tracking_code'] ?? '';
$error = $_SESSION['track_error'] ?? null;
$messageSuccess = $_SESSION['message_success'] ?? null;
$messageError = $_SESSION['message_error'] ?? null;
$evidenceError = $_SESSION['evidence_error'] ?? null;

// Clear session messages
unset($_SESSION['track_error']);
unset($_SESSION['message_success']);
unset($_SESSION['message_error']);
unset($_SESSION['evidence_error']);

// Get report details if tracking
$reportDetails = null;
$messages = [];
$evidences = [];
$categoryName = '';
$description = '';

if ($trackedReport) {
    $reportDetails = $trackedReport;
    $categoryName = getCategoryName($conn, $reportDetails['category_id']);
    $messages = getMessages($conn, $reportDetails['id']);
    $evidences = getEvidences($conn, $reportDetails['id']);
    
    // Mark admin messages as read
    markMessagesAsRead($conn, $reportDetails['id'], 'admin');
    
    // Decrypt description for display
    $description = decryptData($reportDetails['encrypted_description'], ENCRYPTION_KEY);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Report - WhistleGuard</title>
    
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
        }
        
        .glass-card {
            background: rgba(20, 24, 36, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .glass-card-light {
            background: rgba(26, 31, 45, 0.75);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
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
            display: inline-block;
            cursor: pointer;
        }
        
        .btn-primary-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
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
            cursor: pointer;
        }
        
        .btn-outline-glass:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-new { background: #3b82f6; }
        .status-investigating { background: #f59e0b; }
        .status-resolved { background: #10b981; }
        .status-closed { background: #6b7280; }
        
        .priority-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .priority-low { background: #10b981; }
        .priority-medium { background: #3b82f6; }
        .priority-high { background: #f59e0b; }
        .priority-critical { background: #ef4444; }
        
       /* Modern Chat Styles - WhatsApp/Signal Style */
.modern-chat-container {
    height: 450px;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: rgba(15, 19, 34, 0.3);
    border-radius: 16px;
    margin-bottom: 20px;
}

/* Custom scrollbar for chat */
.modern-chat-container::-webkit-scrollbar {
    width: 5px;
}

.modern-chat-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}

.modern-chat-container::-webkit-scrollbar-thumb {
    background: rgba(79, 70, 229, 0.4);
    border-radius: 10px;
}

/* Chat Empty State */
.chat-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #8b92b0;
}

.chat-empty-state i {
    font-size: 3rem;
    color: #4f46e5;
    margin-bottom: 15px;
    opacity: 0.5;
}

.chat-empty-state p {
    font-size: 0.85rem;
    margin-bottom: 0;
}

/* Chat Message Wrapper */
.chat-message-wrapper {
    display: flex;
    width: 100%;
    animation: fadeInMessage 0.3s ease-out;
}

@keyframes fadeInMessage {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Incoming Messages (Admin) - Left side */
.message-incoming {
    justify-content: flex-start;
}

.message-incoming .chat-message-bubble {
    background: rgba(26, 31, 45, 0.9);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 20px 20px 20px 5px;
    margin-right: auto;
    max-width: 75%;
}

/* Outgoing Messages (Whistleblower) - Right side */
.message-outgoing {
    justify-content: flex-end;
}

.message-outgoing .chat-message-bubble {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.85), rgba(124, 58, 237, 0.85));
    border: 1px solid rgba(79, 70, 229, 0.5);
    border-radius: 20px 20px 5px 20px;
    margin-left: auto;
    max-width: 75%;
}

/* Chat Message Bubble */
.chat-message-bubble {
    padding: 12px 16px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.chat-message-bubble:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Chat Message Sender */
.chat-message-sender {
    font-size: 0.7rem;
    font-weight: 600;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.message-incoming .chat-message-sender {
    color: #10b981;
}

.message-outgoing .chat-message-sender {
    color: #a5b4fc;
}

/* Chat Message Text */
.chat-message-text {
    font-size: 0.85rem;
    line-height: 1.5;
    word-wrap: break-word;
    word-break: break-word;
    color: #ffffff;
}

.chat-message-text p {
    margin-bottom: 0;
}

/* Chat Message Time */
.chat-message-time {
    font-size: 0.6rem;
    color: #8b92b0;
    margin-top: 6px;
    text-align: right;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 4px;
}

.chat-message-time i {
    font-size: 0.65rem;
}

/* Chat Input Container */
.chat-input-container {
    margin-top: 16px;
}

.chat-message-form {
    width: 100%;
}

.chat-input-wrapper {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    background: rgba(26, 31, 45, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    padding: 6px 6px 6px 18px;
    transition: all 0.3s ease;
}

.chat-input-wrapper:focus-within {
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
    background: rgba(31, 37, 53, 0.9);
}

.chat-message-input {
    flex: 1;
    background: transparent;
    border: none;
    color: white;
    font-size: 0.9rem;
    padding: 10px 0;
    resize: none;
    font-family: inherit;
    max-height: 100px;
    min-height: 40px;
}

.chat-message-input:focus {
    outline: none;
}

.chat-message-input::placeholder {
    color: #5a6178;
}

.chat-send-btn {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    color: white;
    flex-shrink: 0;
}

.chat-send-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
}

.chat-send-btn i {
    font-size: 1rem;
}

.chat-footer {
    margin-top: 10px;
    text-align: center;
    font-size: 0.7rem;
    color: #5a6178;
}

/* Auto-expand textarea */
.chat-message-input {
    overflow-y: hidden;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modern-chat-container {
        height: 350px;
        padding: 15px;
    }
    
    .message-incoming .chat-message-bubble,
    .message-outgoing .chat-message-bubble {
        max-width: 85%;
    }
    
    .chat-message-sender {
        font-size: 0.65rem;
    }
    
    .chat-message-text {
        font-size: 0.8rem;
    }
    
    .chat-input-wrapper {
        padding: 5px 5px 5px 15px;
    }
    
    .chat-send-btn {
        width: 36px;
        height: 36px;
    }
    
    .chat-send-btn i {
        font-size: 0.9rem;
    }
}

/* Animation for new messages */
@keyframes messagePopIn {
    0% {
        opacity: 0;
        transform: scale(0.95);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

.chat-message-wrapper {
    animation: messagePopIn 0.2s ease-out;
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
        
        .content-wrapper {
            position: relative;
            z-index: 2;
        }
        
        .text-muted-custom {
            color: #8b92b0;
        }
        
        .navbar-glass {
            background: rgba(15, 19, 34, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 16px 0;
            position: relative;
            z-index: 100;
        }
        
        .tracking-code-display {
            font-family: monospace;
            font-size: 1.2rem;
            letter-spacing: 1px;
            background: rgba(79, 70, 229, 0.2);
            padding: 8px 16px;
            border-radius: 10px;
            border: 1px solid rgba(79, 70, 229, 0.3);
        }
        
        .evidence-item {
            transition: all 0.2s ease;
        }
        
        .evidence-item:hover {
            background: rgba(79, 70, 229, 0.1);
            transform: translateX(5px);
        }
        
        .alert-custom {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        
        .auto-refresh-info {
            background: rgba(79, 70, 229, 0.1);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            text-align: center;
        }
        
        .refresh-btn {
            background: none;
            border: none;
            color: #4f46e5;
            cursor: pointer;
            padding: 0;
            margin-left: 8px;
        }
        
        .refresh-btn:hover {
            text-decoration: underline;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade {
            animation: fadeIn 0.3s ease-out;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #0a0e1a;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #2a2f3f;
            border-radius: 10px;
        }
        
        textarea.form-control-glass {
            resize: vertical;
            min-height: 80px;
        }
        .form-control-glass::placeholder {
            color: rgba(255, 255, 255, 0.6); /* Change this to your desired color */
            opacity: 1; /* Ensures your color isn't washed out by browser defaults */
        }
        .nav-header{
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <?php include "inc/background.html" ?>
    
    <nav class="navbar-glass fixed-top">
        <div class="container nav-header">
            <a class="navbar-brand text-white fw-bold fs-4" href="index.php">
                <i class="fas fa-shield-alt me-2" style="color: #4f46e5;"></i>WhistleGuard
            </a>
            <?php if ($trackedReport): ?>
            <div class="container-item">
                <span class="tracking-code-display">
                    <i class="fas fa-qrcode me-2"></i><?php echo htmlspecialchars($trackingCode); ?>
                </span>
                <a href="tracking-logout.php" class="btn-outline-glass ms-3" style="padding: 6px 16px;">
                    <i class="fas fa-sign-out-alt me-1"></i>Exit
                </a>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <div class="content-wrapper" style="margin-top: 90px;">
        <div class="container">
            <!-- Report Details -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    
                    <!-- Auto-refresh notice -->
                    <div class="auto-refresh-info mb-3">
                        <i class="fas fa-sync-alt me-1"></i>
                        Page automatically refreshes every 30 seconds to check for new messages.
                        <button onclick="window.location.reload();" class="refresh-btn">
                            <i class="fas fa-undo-alt"></i> Refresh now
                        </button>
                    </div>
                    
                    <!-- Status Card -->
                    <div class="glass-card p-4 mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="fw-bold mb-2">Report #<?php echo substr(htmlspecialchars($trackingCode), 0, 8); ?>...</h4>
                                <p class="text-muted-custom small mb-0">
                                    Submitted: <?php echo date('F j, Y g:i A', strtotime($reportDetails['created_at'])); ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <span class="status-badge status-<?php echo $reportDetails['status']; ?>">
                                    <?php echo ucfirst($reportDetails['status']); ?>
                                </span>
                                <span class="priority-badge priority-<?php echo $reportDetails['priority']; ?> ms-2">
                                    <i class="fas fa-flag me-1"></i><?php echo ucfirst($reportDetails['priority']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Report Details Card -->
                    <div class="glass-card p-4 mb-4">
                        <h5 class="fw-bold mb-3">
                            <i class="fas fa-info-circle me-2" style="color: #4f46e5;"></i>Report Details
                        </h5>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <strong>Category:</strong>
                            </div>
                            <div class="col-md-9">
                                <?php echo htmlspecialchars($categoryName); ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <strong>Description:</strong>
                            </div>
                            <div class="col-md-9">
                                <div class="glass-card-light p-3">
                                    <?php echo nl2br(htmlspecialchars($description)); ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($reportDetails['expires_at']): ?>
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Expires:</strong>
                            </div>
                            <div class="col-md-9">
                                <span class="text-warning">
                                    <i class="fas fa-hourglass-half me-1"></i>
                                    <?php echo date('F j, Y', strtotime($reportDetails['expires_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Evidence Section -->
                    <?php if (!empty($evidences)): ?>
                    <div class="glass-card p-4 mb-4">
                        <h5 class="fw-bold mb-3">
                            <i class="fas fa-paperclip me-2" style="color: #4f46e5;"></i>Evidence Files
                        </h5>
                        <div class="list-group list-group-flush bg-transparent">
                            <?php foreach ($evidences as $evidence): ?>
                            <div class="evidence-item glass-card-light p-3 mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-file-alt me-2" style="color: #4f46e5;"></i>
                                        <strong>Evidence #<?php echo $evidence['id']; ?></strong>
                                        <br>
                                        <small class="text-muted-custom">
                                            <?php echo round($evidence['size_bytes'] / 1024, 2); ?> KB • 
                                            <?php 
                                                $ext = pathinfo($evidence['mime_type'], PATHINFO_EXTENSION);
                                                echo $ext ? strtoupper($ext) : 'FILE';
                                            ?> •
                                            Viewed <?php echo $evidence['view_count']; ?> times
                                            <?php if ($evidence['max_views']): ?>
                                            / max <?php echo $evidence['max_views']; ?> views
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <form method="POST" action="app/track-report.php" style="display: inline;">
                                        <input type="hidden" name="action" value="view_evidence">
                                        <input type="hidden" name="tracking_code" value="<?php echo htmlspecialchars($trackingCode); ?>">
                                        <input type="hidden" name="evidence_id" value="<?php echo $evidence['id']; ?>">
                                        <button type="submit" class="btn-primary-glass" style="padding: 6px 16px; font-size: 0.85rem;">
                                            <i class="fas fa-eye me-1"></i>View
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Chat Section -->
<div class="glass-card p-4 mb-4">
    <h5 class="fw-bold mb-3">
        <i class="fas fa-comments me-2" style="color: #4f46e5;"></i>Secure Communication
    </h5>
    
    <?php if ($messageSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show alert-custom" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($messageSuccess); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($messageError): ?>
    <div class="alert alert-danger alert-dismissible fade show alert-custom" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($messageError); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($evidenceError): ?>
    <div class="alert alert-danger alert-dismissible fade show alert-custom" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($evidenceError); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Modern Chat Container -->
    <div class="modern-chat-container" id="chatContainer">
        <?php if (empty($messages)): ?>
        <div class="chat-empty-state">
            <i class="fas fa-comment-dots"></i>
            <p>No messages yet. Send a message to communicate with administrators.</p>
        </div>
        <?php else: ?>
        <?php foreach ($messages as $message): 
            $isWhistleblower = ($message['sender_type'] == 'whistleblower');
            $decryptedMsg = decryptData($message['encrypted_message'], ENCRYPTION_KEY);
        ?>
        <div class="chat-message-wrapper <?php echo $isWhistleblower ? 'message-outgoing' : 'message-incoming'; ?>">
            <div class="chat-message-bubble <?php echo $isWhistleblower ? 'bubble-outgoing' : 'bubble-incoming'; ?>">
                <div class="chat-message-sender">
                    <?php if ($isWhistleblower): ?>
                        <i class="fas fa-user-secret me-1"></i>You (Whistleblower)
                    <?php else: ?>
                        <i class="fas fa-user-shield me-1"></i>Administrator
                    <?php endif; ?>
                </div>
                <div class="chat-message-text">
                    <?php echo nl2br(htmlspecialchars($decryptedMsg)); ?>
                </div>
                <div class="chat-message-time">
                    <?php echo date('g:i A', strtotime($message['created_at'])); ?>
                    <?php if ($isWhistleblower && $message['is_read']): ?>
                        <i class="fas fa-check-double ms-1" style="font-size: 10px; color: #10b981;"></i>
                    <?php elseif ($isWhistleblower): ?>
                        <i class="fas fa-check ms-1" style="font-size: 10px; color: #8b92b0;"></i>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Message Input -->
    <div class="chat-input-container">
        <form method="POST" action="app/track-report.php" class="chat-message-form">
            <input type="hidden" name="action" value="send_message">
            <input type="hidden" name="tracking_code" value="<?php echo htmlspecialchars($trackingCode); ?>">
            <div class="chat-input-wrapper">
                <textarea name="message" class="chat-message-input" rows="1" placeholder="Type your secure message here..." required></textarea>
                <button type="submit" class="chat-send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
        <div class="chat-footer">
            <i class="fas fa-lock me-1"></i>End-to-end encrypted • Messages expire after 30 days
        </div>
    </div>
</div>
                    
                    <!-- Actions -->
                    <div class="glass-card p-4 text-center">
                        <a href="index.php" class="btn-outline-glass me-2">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                        <a href="tracking-logout.php" class="btn-outline-glass">
                            <i class="fas fa-sign-out-alt me-1"></i>Exit Tracking
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="container py-4 mt-4">
            <div class="glass-card p-4 text-center">
                <div class="row align-items-center">
                    <div class="col-md-6 text-md-start mb-3 mb-md-0">
                        <i class="fas fa-shield-alt me-2" style="color: #4f46e5;"></i>WhistleGuard - Speaking Truth Safely
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted-custom">© 2026 WhistleGuard. All rights reserved.</small>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-refresh the page every 30 seconds to check for new messages
    let refreshInterval = setInterval(function() {
        window.location.reload();
    }, 30000);
    
    // Optional: Allow user to cancel auto-refresh (useful for long typing)
    let autoRefreshEnabled = true;
    
    // Pause auto-refresh when typing in textarea
    const messageTextarea = document.querySelector('.chat-message-input');
    if (messageTextarea) {
        messageTextarea.addEventListener('focus', function() {
            if (autoRefreshEnabled) {
                clearInterval(refreshInterval);
                autoRefreshEnabled = false;
                // Show indicator
                const refreshNotice = document.querySelector('.auto-refresh-info');
                if (refreshNotice) {
                    refreshNotice.innerHTML = '<i class="fas fa-pause me-1"></i>Auto-refresh paused while typing. <button onclick="location.reload();" class="refresh-btn">Refresh manually</button>';
                    refreshNotice.style.background = 'rgba(245, 158, 11, 0.2)';
                }
            }
        });
        
        messageTextarea.addEventListener('blur', function() {
            if (!autoRefreshEnabled) {
                refreshInterval = setInterval(function() {
                    window.location.reload();
                }, 30000);
                autoRefreshEnabled = true;
                // Restore indicator
                const refreshNotice = document.querySelector('.auto-refresh-info');
                if (refreshNotice) {
                    refreshNotice.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Page automatically refreshes every 30 seconds to check for new messages. <button onclick="window.location.reload();" class="refresh-btn"><i class="fas fa-undo-alt"></i> Refresh now</button>';
                    refreshNotice.style.background = 'rgba(79, 70, 229, 0.1)';
                }
            }
        });
        
        // Auto-resize textarea
        messageTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
    }
    
    // Scroll chat to bottom on load
    const chatContainer = document.querySelector('.modern-chat-container');
    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    
    // Console warning
    console.log('%c🔒 SECURE CHANNEL: All communications are end-to-end encrypted.', 'color: #4f46e5; font-size: 12px; font-weight: bold;');
    console.log('%cPage auto-refreshes every 30 seconds to check for new messages.', 'color: #8b92b0; font-size: 11px;');
</script>

    <script src="inc/background.js"></script>
</body>
</html>