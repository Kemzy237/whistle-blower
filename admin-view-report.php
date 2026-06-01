<?php
// admin-view-report.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Include database connection and report functions
require_once 'db_connection.php';
require_once 'app/model/report.php';

// Get admin info
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';

// Get report ID from URL
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reportId <= 0) {
    header('Location: admin-reports.php');
    exit;
}

// Define the reusable query to fetch complete report data with category details
$fetchReportSql = "
    SELECT r.*, c.name as category_name, c.description as category_description
    FROM reports r
    JOIN categories c ON r.category_id = c.id
    WHERE r.id = ?
";

// Fetch report details initially
$report = complete_report_category_data($conn, $reportId);

if (!$report) {
    header('Location: admin-reports.php');
    exit;
}

// Decrypt description
$description = decryptData($report['encrypted_description'], ENCRYPTION_KEY);

// Fetch messages
$messages = fetch_messages($conn, $reportId);

// Fetch evidences
$evidences = fetch_evidences($conn, $reportId);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    $validStatuses = ['new', 'investigating', 'resolved', 'closed'];
    
    if (in_array($newStatus, $validStatuses)) {
        $stmt = $conn->prepare("UPDATE reports SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $reportId]);
        
        // Log the action
        $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $metadata = json_encode(['old_status' => $report['status'], 'new_status' => $newStatus]);
        
        $stmt = $conn->prepare("
            INSERT INTO audit_logs (report_id, admin_user_id, action, ip_hash, user_agent_hash, metadata, created_at)
            VALUES (?, ?, 'status_updated', ?, ?, ?, NOW())
        ");
        $stmt->execute([$reportId, $_SESSION['admin_id'], $ipHash, $userAgentHash, $metadata]);
        
        // REFRESH WITH JOIN QUERY TO PRESERVE CATEGORY_NAME
        $report = complete_report_category_data($conn, $reportId);
        
        $successMessage = "Report status updated successfully!";
    }
}

// Handle visibility update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_visibility'])) {
    $newVisibility = $_POST['visibility'];
    $newPublicationStatus = $_POST['publication_status'] ?? 'pending';
    $validVisibilities = ['private', 'public'];
    $validPublicationStatuses = ['pending', 'approved', 'rejected'];
    
    if (in_array($newVisibility, $validVisibilities) && in_array($newPublicationStatus, $validPublicationStatuses)) {
        $stmt = $conn->prepare("UPDATE reports SET visibility = ?, publication_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newVisibility, $newPublicationStatus, $reportId]);
        
        // Log the action
        $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $metadata = json_encode(['visibility' => $newVisibility, 'publication_status' => $newPublicationStatus]);
        
        $stmt = $conn->prepare("
            INSERT INTO audit_logs (report_id, admin_user_id, action, ip_hash, user_agent_hash, metadata, created_at)
            VALUES (?, ?, 'visibility_updated', ?, ?, ?, NOW())
        ");
        $stmt->execute([$reportId, $_SESSION['admin_id'], $ipHash, $userAgentHash, $metadata]);
        
        // REFRESH WITH JOIN QUERY TO PRESERVE CATEGORY_NAME
        $report = complete_report_category_data($conn, $reportId);
        
        $successMessage = "Report visibility updated successfully!";
    }
}

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        sendMessage($conn, $reportId, $message, 'admin');
        
        // Log the action
        $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        
        $stmt = $conn->prepare("
            INSERT INTO audit_logs (report_id, admin_user_id, action, ip_hash, user_agent_hash, created_at)
            VALUES (?, ?, 'message_sent', ?, ?, NOW())
        ");
        $stmt->execute([$reportId, $_SESSION['admin_id'], $ipHash, $userAgentHash]);
        
        // Refresh messages
        $stmt = $conn->prepare("SELECT * FROM messages WHERE report_id = ? ORDER BY created_at ASC");
        $stmt->execute([$reportId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $successMessage = "Message sent successfully!";
    }
}

// Handle evidence visibility toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_evidence'])) {
    $evidenceId = (int)$_POST['evidence_id'];
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE evidences SET is_public = ? WHERE id = ? AND report_id = ?");
    $stmt->execute([$isPublic, $evidenceId, $reportId]);
    
    // Refresh evidences
    $stmt = $conn->prepare("SELECT * FROM evidences WHERE report_id = ? ORDER BY created_at DESC");
    $stmt->execute([$reportId]);
    $evidences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $successMessage = "Evidence visibility updated successfully!";
}

// Mark all whistleblower messages as read for this report
markReportMessagesAsRead($conn, $reportId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Report #<?php echo $reportId; ?> - Admin Panel | WhistleGuard</title>
    
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
        
        /* Header Section */
        .header-section {
            background: rgba(20, 24, 36, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .role-badge {
            background: rgba(79, 70, 229, 0.2);
            border: 1px solid rgba(79, 70, 229, 0.3);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
        }
        
        /* Glass Card */
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
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
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
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        
        .priority-low { background: #10b981; }
        .priority-medium { background: #3b82f6; }
        .priority-high { background: #f59e0b; }
        .priority-critical { background: #ef4444; }
        
        .visibility-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        
        .visibility-public { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .visibility-private { background: rgba(107, 114, 128, 0.2); color: #9ca3af; border: 1px solid rgba(107, 114, 128, 0.3); }
        
        .publication-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        
        .publication-approved { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .publication-pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
        .publication-rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        
        .form-control-glass {
            background: rgba(26, 31, 45, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: white;
            border-radius: 12px;
            padding: 10px 15px;
        }
        
        .form-control-glass:focus {
            background: rgba(31, 37, 53, 0.9);
            border-color: #4f46e5;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.15);
            outline: none;
        }
        
        .form-select-glass {
            background: rgba(26, 31, 45, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: white;
            border-radius: 12px;
            padding: 10px 15px;
            cursor: pointer;
        }
        
        .form-select-glass option {
            background: #0a0e1a;
        }
        
        .btn-primary-glass {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.9), rgba(124, 58, 237, 0.9));
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
            color: white;
        }
        
        .btn-secondary-glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: #e0e0e0;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary-glass:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }
        
        .btn-sm-glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.7rem;
            color: #e0e0e0;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        
        .btn-sm-glass:hover {
            background: rgba(79, 70, 229, 0.2);
            color: white;
        }
        
        /* Chat Section Styles - Modern Chat UI */
.chat-container {
    height: 450px;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: rgba(15, 19, 34, 0.3);
}

/* Custom scrollbar for chat */
.chat-container::-webkit-scrollbar {
    width: 5px;
}

.chat-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}

.chat-container::-webkit-scrollbar-thumb {
    background: rgba(79, 70, 229, 0.4);
    border-radius: 10px;
}

/* Message Wrapper */
.message-wrapper {
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

/* Incoming Messages (Whistleblower) - Left side */
.message-incoming {
    justify-content: flex-start;
}

.message-incoming .message-bubble {
    background: linear-gradient(135deg, rgba(26, 31, 45, 0.9), rgba(20, 24, 36, 0.9));
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 20px 20px 20px 5px;
    margin-right: auto;
    max-width: 70%;
}

/* Outgoing Messages (Admin) - Right side */
.message-outgoing {
    justify-content: flex-end;
}

.message-outgoing .message-bubble {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.85), rgba(124, 58, 237, 0.85));
    border: 1px solid rgba(79, 70, 229, 0.5);
    border-radius: 20px 20px 5px 20px;
    margin-left: auto;
    max-width: 70%;
}

/* Message Bubble */
.message-bubble {
    padding: 12px 16px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.message-bubble:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Message Sender */
.message-sender {
    font-size: 0.7rem;
    font-weight: 600;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.message-incoming .message-sender {
    color: #10b981;
}

.message-outgoing .message-sender {
    color: #a5b4fc;
}

/* Message Text */
.message-text {
    font-size: 0.85rem;
    line-height: 1.5;
    word-wrap: break-word;
    word-break: break-word;
}

.message-text p {
    margin-bottom: 0;
}

/* Message Time */
.message-time {
    font-size: 0.6rem;
    color: #8b92b0;
    margin-top: 6px;
    text-align: right;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 4px;
}

.message-time i {
    font-size: 0.65rem;
}

/* Input Container */
.message-input-container {
    padding: 16px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    background: rgba(26, 31, 45, 0.4);
}

.message-form {
    width: 100%;
}

.input-wrapper {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    background: rgba(26, 31, 45, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    padding: 6px 6px 6px 18px;
    transition: all 0.3s ease;
}

.input-wrapper:focus-within {
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
    background: rgba(31, 37, 53, 0.9);
}

.message-input {
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

.message-input:focus {
    outline: none;
}

.message-input::placeholder {
    color: #5a6178;
}

.send-btn {
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

.send-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
}

.send-btn i {
    font-size: 1rem;
}

.message-footer {
    margin-top: 10px;
    text-align: center;
    font-size: 0.7rem;
    color: #5a6178;
}

/* Auto-expand textarea */
.message-input {
    overflow-y: hidden;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .chat-container {
        height: 350px;
        padding: 15px;
    }
    
    .message-incoming .message-bubble,
    .message-outgoing .message-bubble {
        max-width: 85%;
    }
    
    .message-sender {
        font-size: 0.65rem;
    }
    
    .message-text {
        font-size: 0.8rem;
    }
    
    .input-wrapper {
        padding: 5px 5px 5px 15px;
    }
    
    .send-btn {
        width: 36px;
        height: 36px;
    }
    
    .send-btn i {
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

.message-wrapper {
    animation: messagePopIn 0.2s ease-out;
}
        
        .evidence-item {
            background: rgba(26, 31, 45, 0.5);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }
        
        .evidence-item:hover {
            background: rgba(79, 70, 229, 0.15);
        }
        
        .alert-custom {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        
        .text-muted-custom {
            color: #8b92b0;
        }
        
        .tracking-code {
            font-family: monospace;
            font-size: 1rem;
            font-weight: 600;
            background: rgba(0,0,0,0.3);
            padding: 4px 10px;
            border-radius: 8px;
        }
        
        .info-row {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 70px 15px 15px;
            }
            .mobile-menu-btn {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .btn-primary-glass, .btn-secondary-glass {
                width: 100%;
                justify-content: center;
            }
            .chat-container {
                height: 300px;
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
        .red{
            background-color: red;
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
        <!-- Header -->
        <div class="header-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-file-alt me-2" style="color: #4f46e5;"></i>
                        Report #<?php echo $reportId; ?>
                    </h2>
                    <p class="text-muted-custom mb-0">
                        Tracking Code: <code class="tracking-code"><?php echo htmlspecialchars($report['tracking_code']); ?></code>
                    </p>
                </div>
            </div>
        </div>
        
        <?php if (isset($successMessage)): ?>
        <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Report Details Card -->
        <div class="glass-card">
            <div class="card-header-custom">
                <h5 class="fw-bold mb-0">
                    <i class="fas fa-info-circle me-2" style="color: #4f46e5;"></i>Report Details
                </h5>
            </div>
            <div class="p-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <small class="text-muted-custom d-block">Category</small>
                            <strong><?php echo htmlspecialchars($report['category_name']); ?></strong>
                        </div>
                        <div class="info-row">
                            <small class="text-muted-custom d-block">Priority</small>
                            <span class="priority-badge priority-<?php echo $report['priority']; ?>"><?php echo ucfirst($report['priority']); ?></span>
                        </div>
                        <div class="info-row">
                            <small class="text-muted-custom d-block">Status</small>
                            <span class="status-badge status-<?php echo $report['status']; ?>"><?php echo ucfirst($report['status']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <small class="text-muted-custom d-block">Submitted</small>
                            <strong><?php echo date('F j, Y g:i A', strtotime($report['created_at'])); ?></strong>
                        </div>
                        <div class="info-row">
                            <small class="text-muted-custom d-block">Last Updated</small>
                            <strong><?php echo date('F j, Y g:i A', strtotime($report['updated_at'])); ?></strong>
                        </div>
                        <?php if ($report['expires_at']): ?>
                        <div class="info-row">
                            <small class="text-muted-custom d-block">Expires</small>
                            <strong class="text-warning"><?php echo date('F j, Y', strtotime($report['expires_at'])); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted-custom d-block mb-2">Description</small>
                    <div class="glass-card p-3" style="background: rgba(26, 31, 45, 0.5);">
                        <?php echo nl2br(htmlspecialchars($description)); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Update Status Card -->
        <div class="glass-card">
            <div class="card-header-custom">
                <h5 class="fw-bold mb-0">
                    <i class="fas fa-toggle-on me-2" style="color: #4f46e5;"></i>Update Status & Visibility
                </h5>
            </div>
            <div class="p-4">
                <div class="row">
                    <div class="col-md-6">
                        <form method="POST" action="" class="mb-3 mb-md-0">
                            <label class="form-label">Change Report Status</label>
                            <div class="d-flex gap-2">
                                <select name="status" class="form-select-glass flex-grow-1">
                                    <option value="new" <?php echo $report['status'] == 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="investigating" <?php echo $report['status'] == 'investigating' ? 'selected' : ''; ?>>Investigating</option>
                                    <option value="resolved" <?php echo $report['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo $report['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                                <button type="submit" name="update_status" class="btn-primary-glass">Update</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" action="">
                            <label class="form-label">Public Visibility & Publication Status</label>
                            <div class="d-flex gap-2">
                                <select name="visibility" class="form-select-glass">
                                    <option value="private" <?php echo $report['visibility'] == 'private' ? 'selected' : ''; ?>>Private</option>
                                    <option value="public" <?php echo $report['visibility'] == 'public' ? 'selected' : ''; ?>>Public</option>
                                </select>
                                <select name="publication_status" class="form-select-glass">
                                    <option value="pending" <?php echo $report['publication_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $report['publication_status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $report['publication_status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                                <button type="submit" name="update_visibility" class="btn-primary-glass">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="visibility-badge visibility-<?php echo $report['visibility']; ?>">
                        <i class="fas <?php echo $report['visibility'] == 'public' ? 'fa-globe' : 'fa-lock'; ?> me-1"></i>
                        Current: <?php echo ucfirst($report['visibility']); ?>
                    </span>
                    <span class="publication-badge publication-<?php echo $report['publication_status']; ?> ms-2">
                        <i class="fas <?php echo $report['publication_status'] == 'approved' ? 'fa-check-circle' : ($report['publication_status'] == 'pending' ? 'fa-clock' : 'fa-times-circle'); ?> me-1"></i>
                        Publication: <?php echo ucfirst($report['publication_status']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Evidence Section -->
        <?php if (!empty($evidences)): ?>
        <div class="glass-card">
            <div class="card-header-custom">
                <h5 class="fw-bold mb-0">
                    <i class="fas fa-paperclip me-2" style="color: #4f46e5;"></i>Evidence Files
                </h5>
            </div>
            <div class="p-4">
                <?php foreach ($evidences as $evidence): ?>
                <div class="evidence-item d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <i class="fas fa-file-alt me-2" style="color: #4f46e5;"></i>
                        <strong>Evidence #<?php echo $evidence['id']; ?></strong>
                        <br>
                        <small class="text-muted-custom">
                            <?php echo round($evidence['size_bytes'] / 1024, 2); ?> KB • 
                            <?php echo strtoupper(pathinfo($evidence['mime_type'], PATHINFO_EXTENSION) ?: 'FILE'); ?> •
                            Viewed <?php echo $evidence['view_count']; ?> times •
                            <?php echo $evidence['is_public'] ? 'Public' : 'Private'; ?>
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="evidence_id" value="<?php echo $evidence['id']; ?>">
                            
                            <?php if ($evidence['is_public'] == 1): ?>
                                <input type="hidden" name="toggle_evidence" value="1">
                                <button type="submit" class="btn-sm-glass red">Make Private</button>
                            <?php else: ?>
                                <input type="hidden" name="toggle_evidence" value="1">
                                <input type="hidden" name="is_public" value="1">
                                <button type="submit" class="btn-sm-glass">Make Public</button>
                            <?php endif; ?>
                        </form>
                        <a href="view-evidence.php?id=<?= $evidence['id'] ?>" class="btn-sm-glass" target="_blank">
                            <i class="fas fa-download me-1"></i>Download
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Chat Section -->
<div class="glass-card">
    <div class="card-header-custom">
        <h5 class="fw-bold mb-0">
            <i class="fas fa-comments me-2" style="color: #4f46e5;"></i>Secure Communication
        </h5>
    </div>
    
    <!-- Chat Messages Container -->
    <div class="chat-container" id="chatContainer">
        <?php if (empty($messages)): ?>
        <div class="text-center text-muted-custom py-5">
            <i class="fas fa-comment-dots fa-2x mb-2 d-block"></i>
            <small>No messages yet. Send a message to communicate with the whistleblower.</small>
        </div>
        <?php else: ?>
        <?php foreach ($messages as $message): 
            $isAdmin = ($message['sender_type'] == 'admin');
            $decryptedMsg = decryptData($message['encrypted_message'], ENCRYPTION_KEY);
        ?>
        <div class="message-wrapper <?php echo $isAdmin ? 'message-outgoing' : 'message-incoming'; ?>">
            <div class="message-bubble <?php echo $isAdmin ? 'bubble-outgoing' : 'bubble-incoming'; ?>">
                <div class="message-sender">
                    <?php if ($isAdmin): ?>
                        <i class="fas fa-user-shield me-1"></i>Administrator (You)
                    <?php else: ?>
                        <i class="fas fa-user-secret me-1"></i>Whistleblower
                    <?php endif; ?>
                </div>
                <div class="message-text">
                    <?php echo nl2br(htmlspecialchars($decryptedMsg)); ?>
                </div>
                <div class="message-time">
                    <?php echo date('g:i A, M j', strtotime($message['created_at'])); ?>
                    <?php if ($isAdmin && $message['is_read']): ?>
                        <i class="fas fa-check-double ms-1" style="font-size: 10px;"></i>
                    <?php elseif ($isAdmin): ?>
                        <i class="fas fa-check ms-1" style="font-size: 10px;"></i>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Message Input -->
    <div class="message-input-container">
        <form method="POST" action="" class="message-form">
            <div class="input-wrapper">
                <textarea name="message" class="message-input" rows="1" placeholder="Type your message here..." required></textarea>
                <button type="submit" name="send_message" class="send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
        <div class="message-footer">
            <i class="fas fa-lock me-1"></i>End-to-end encrypted • Messages are secure
        </div>
    </div>
</div>
        
        <!-- Action Buttons -->
        <div class="d-flex gap-3 justify-content-end">
            <a href="admin-reports.php" class="btn-secondary-glass">
                <i class="fas fa-arrow-left me-2"></i>Back to Reports
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="inc/background.js"></script>
    <script src="inc/sidebar.js"></script>
    <script>
        // Scroll chat to bottom
        const chatContainer = document.getElementById('chatContainer');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Auto-resize textarea
        const textarea = document.querySelector('.message-input');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });
        }

        // Scroll to bottom of chat
        function scrollToBottom() {
            const chatContainer = document.getElementById('chatContainer');
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        }

        // Initial scroll to bottom
        scrollToBottom();

        // Auto-refresh chat every 10 seconds (optional)
        let lastMessageCount = <?php echo count($messages); ?>;

        setInterval(function() {
            fetch(window.location.href + '?check_messages=1&report_id=<?php echo $reportId; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.message_count > lastMessageCount) {
                        location.reload();
                    }
                    lastMessageCount = data.message_count;
                })
                .catch(err => console.log('Auto-refresh disabled'));
        }, 10000);
        
        // Console security notice
        console.log('%c⚠️ ADMIN VIEW REPORT - Authorized Access Only ⚠️', 'color: #ef4444; font-size: 12px; font-weight: bold;');
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var sidebarItems = document.querySelectorAll('.sidebar-nav li');
            if (sidebarItems[1]) {
                sidebarItems[1].classList.add('active');
            }
        });
    </script>
</body>
</html>