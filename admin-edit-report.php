<?php
// admin-edit-report.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: admin-login.php");
    exit();
}

// Include database connection and report functions
require_once 'db_connection.php';
require_once 'app/model/index.php';

// Get admin info
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';

// Get report ID from URL
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reportId <= 0) {
    header('Location: admin-reports.php');
    exit;
}

// Fetch report details with category
$report = get_report_without_catDescription($conn, $reportId);



if (!$report) {
    header('Location: admin-reports.php');
    exit;
}

// Decrypt description
$description = decryptData($report['encrypted_description'], ENCRYPTION_KEY);

// Get all categories
$categories = categories_filters($conn);

// Handle form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle update report details
    if (isset($_POST['update_report'])) {
        $newCategoryId = (int)$_POST['category_id'];
        $newPriority = $_POST['priority'];
        $newDescription = trim($_POST['description']);
        $newVisibility = $_POST['visibility'];
        $newPublicationStatus = $_POST['publication_status'];
        $newExpiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        
        $validPriorities = ['low', 'medium', 'high', 'critical'];
        $validVisibilities = ['private', 'public'];
        $validPublicationStatuses = ['pending', 'approved', 'rejected'];
        
        if (in_array($newPriority, $validPriorities) && 
            in_array($newVisibility, $validVisibilities) && 
            in_array($newPublicationStatus, $validPublicationStatuses)) {
            
            // Encrypt the new description
            $encryptedDescription = encryptData($newDescription, ENCRYPTION_KEY);
            
            // Update report
            $data = array($newCategoryId, $encryptedDescription, $newPriority, $newVisibility, $newPublicationStatus, $newExpiresAt, $reportId);
            update_report($conn, $data);
            
            // Log the action
            $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            $metadata = json_encode(['updated_fields' => ['category', 'description', 'priority', 'visibility', 'publication_status', 'expires_at']]);

            $categoryLogData = [
                'report_id'       => $reportId,
                'admin_user_id'   => $_SESSION['admin_id'],
                'action'          => 'report_updated',
                'ip_hash'         => $ipHash,
                'user_agent_hash' => $userAgentHash,
                'metadata'        => $metadata,
                'created_at'      => date('Y-m-d H:i:s') // Replacing NOW() with a PHP timestamp
            ];
            log_system_action($conn, 'audit_logs', $categoryLogData);
            
            // Refresh report data
            $report = get_report_without_catDescription($conn, $reportId);
            $description = decryptData($report['encrypted_description'], ENCRYPTION_KEY);
            
            $successMessage = "Report updated successfully!";
        } else {
            $errorMessage = "Invalid values provided.";
        }
    }
    
    // Handle delete evidence
    if (isset($_POST['delete_evidence'])) {
        $evidenceId = (int)$_POST['evidence_id'];
        
        // Get file path before deleting
        $data = array($evidenceId, $reportId);
        $evidence = get_file_path($conn, $data);
        
        if ($evidence) {
            // Delete the encrypted file
            $filePath = decryptData($evidence['encrypted_file_path'], ENCRYPTION_KEY);
            if (file_exists($filePath)) {
                unlink($filePath);
            }else{
                $errorMessage = 'File does not exist';
                exit();
            }
            
            // Delete from database
            $data = array($evidenceId, $reportId);
            delete_evidence($conn, $data);
            
            // Log the action
            $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            $metadata = json_encode(['evidence_id' => $evidenceId]);

            $categoryLogData = [
                'report_id'       => $reportId,
                'admin_user_id'   => $_SESSION['admin_id'],
                'action'          => 'ecidence_deleted',
                'ip_hash'         => $ipHash,
                'user_agent_hash' => $userAgentHash,
                'metadata'        => $metadata,
                'created_at'      => date('Y-m-d H:i:s') // Replacing NOW() with a PHP timestamp
            ];
            log_system_action($conn, 'audit_logs', $categoryLogData);
            
            $successMessage = "Evidence deleted successfully!";
        }
    }
    
    // Handle delete report
    if (isset($_POST['delete_report'])) {
        // Log before deletion
        $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $metadata = json_encode(['report_id' => $reportId, 'tracking_code' => $report['tracking_code']]);

        $categoryLogData = [
                'report_id'       => $reportId,
                'admin_user_id'   => $_SESSION['admin_id'],
                'action'          => 'report_deleted',
                'ip_hash'         => $ipHash,
                'user_agent_hash' => $userAgentHash,
                'metadata'        => $metadata,
                'created_at'      => date('Y-m-d H:i:s') // Replacing NOW() with a PHP timestamp
            ];
            log_system_action($conn, 'audit_logs', $categoryLogData);
        
        // Delete report (cascade will delete evidences, messages, etc.)
        delete_report($conn, $reportId);
        
        header('Location: admin-reports.php?deleted=1');
        exit;
    }
}

// Fetch evidences for this report
$evidences = fetch_report_evidence($conn, $reportId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report #<?php echo $reportId; ?> - Admin Panel | WhistleGuard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Flatpickr for date picking -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
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
            padding: 8px 20px;
            border-radius: 10px;
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
        
        .btn-danger-glass {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.9), rgba(200, 35, 51, 0.9));
            border: 1px solid rgba(220, 53, 69, 0.3);
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .btn-danger-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.4);
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
        
        .btn-sm-danger {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ef4444;
        }
        
        .btn-sm-danger:hover {
            background: rgba(220, 53, 69, 0.3);
            color: white;
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
        
        /* Modal Styles */
        .modal-content-glass {
            background: rgba(15, 19, 34, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
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
            .btn-primary-glass, .btn-secondary-glass, .btn-danger-glass {
                width: 100%;
                justify-content: center;
            }
            .form-actions {
                flex-direction: column;
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
        <!-- Header -->
        <div class="header-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-edit me-2" style="color: #4f46e5;"></i>
                        Edit Report #<?php echo $reportId; ?>
                    </h2>
                    <p class="text-muted-custom mb-0">
                        Tracking Code: <code class="tracking-code"><?php echo htmlspecialchars($report['tracking_code']); ?></code>
                    </p>
                </div>
                <div class="role-badge">
                    <i class="fas fa-user-shield me-1"></i>
                    Role: <?php echo ucfirst($adminRole); ?>
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
        
        <!-- Edit Report Form -->
        <div class="glass-card">
            <div class="card-header-custom">
                <h5 class="fw-bold mb-0">
                    <i class="fas fa-pen me-2" style="color: #4f46e5;"></i>Edit Report Details
                </h5>
            </div>
            <div class="p-4">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select-glass w-100" required>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $report['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select-glass w-100" required>
                                <option value="low" <?php echo $report['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo $report['priority'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo $report['priority'] == 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="critical" <?php echo $report['priority'] == 'critical' ? 'selected' : ''; ?>>Critical</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control-glass w-100" rows="8" required><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Visibility</label>
                            <select name="visibility" class="form-select-glass w-100" required>
                                <option value="private" <?php echo $report['visibility'] == 'private' ? 'selected' : ''; ?>>Private</option>
                                <option value="public" <?php echo $report['visibility'] == 'public' ? 'selected' : ''; ?>>Public</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Publication Status</label>
                            <select name="publication_status" class="form-select-glass w-100" required>
                                <option value="pending" <?php echo $report['publication_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $report['publication_status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $report['publication_status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Expiration Date (Optional)</label>
                        <input type="text" name="expires_at" class="form-control-glass w-100" id="expiryDate" 
                               placeholder="Select expiration date" value="<?php echo $report['expires_at'] ? date('Y-m-d', strtotime($report['expires_at'])) : ''; ?>">
                        <small class="text-muted-custom">Leave empty if the report should never expire</small>
                    </div>
                    
                    <div class="d-flex gap-3 justify-content-end form-actions">
                        <a href="admin-view-report.php?id=<?php echo $reportId; ?>" class="btn-secondary-glass">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" name="update_report" class="btn-primary-glass">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Evidence Management Section -->
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
                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this evidence? This action cannot be undone.');">
                            <input type="hidden" name="evidence_id" value="<?php echo $evidence['id']; ?>">
                            <button type="submit" name="delete_evidence" class="btn-sm-glass btn-sm-danger">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </form>
                        <a href="view-evidence.php?id=<?php echo $evidence['id']; ?>" class="btn-sm-glass" target="_blank">
                            <i class="fas fa-download me-1"></i>Download
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Danger Zone -->
        <div class="glass-card" style="border-color: rgba(220, 53, 69, 0.3);">
            <div class="card-header-custom" style="border-bottom-color: rgba(220, 53, 69, 0.2);">
                <h5 class="fw-bold mb-0" style="color: #ef4444;">
                    <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                </h5>
            </div>
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <strong>Delete this report</strong>
                        <br>
                        <small class="text-muted-custom">Once deleted, all evidence and messages will be permanently removed.</small>
                    </div>
                    <form method="POST" action="" onsubmit="return confirm('WARNING: This will permanently delete the report and all associated data. This action cannot be undone. Are you absolutely sure?');">
                        <button type="submit" name="delete_report" class="btn-danger-glass">
                            <i class="fas fa-trash-alt me-2"></i>Delete Report Permanently
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="d-flex gap-3 justify-content-end">
            <a href="admin-reports.php" class="btn-secondary-glass">
                <i class="fas fa-arrow-left me-2"></i>Back to Reports
            </a>
            <a href="admin-view-report.php?id=<?php echo $reportId; ?>" class="btn-primary-glass">
                <i class="fas fa-eye me-2"></i>View Report
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="inc/background.js"></script>
    <script src="inc/sidebar.js"></script>
    <script>
        // Initialize flatpickr for date picker
        flatpickr("#expiryDate", {
            dateFormat: "Y-m-d",
            minDate: "today",
            altInput: true,
            altFormat: "F j, Y",
            allowInput: true,
            theme: "dark"
        });
        
        // Console security notice
        console.log('%c⚠️ ADMIN EDIT REPORT - Authorized Access Only ⚠️', 'color: #ef4444; font-size: 12px; font-weight: bold;');
        console.log('%c' + new Date().toLocaleString(), 'color: #8b92b0; font-size: 10px;');
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