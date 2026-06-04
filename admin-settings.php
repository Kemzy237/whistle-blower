<?php
// admin-settings.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: admin-login.php");
    exit();
}

// Include database connection
require_once 'db_connection.php';
require_once 'app/model/index.php';

// Get admin info
$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';

// Handle form submissions
$successMessage = '';
$errorMessage = '';

// Update general settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_general_settings'])) {
    $siteName = trim($_POST['site_name']);
    $siteDescription = trim($_POST['site_description']);
    $itemsPerPage = (int)$_POST['items_per_page'];
    $autoDeleteDays = (int)$_POST['auto_delete_days'];
    
    // Save settings to a JSON file or database table
    $settings = [
        'site_name' => $siteName,
        'site_description' => $siteDescription,
        'items_per_page' => $itemsPerPage,
        'auto_delete_days' => $autoDeleteDays,
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    $settingsDir = __DIR__ . '/inc/';
    if (!is_dir($settingsDir)) {
        mkdir($settingsDir, 0755, true);
    }
    
    if (file_put_contents($settingsDir . 'settings.json', json_encode($settings, JSON_PRETTY_PRINT))) {
        $successMessage = 'General settings updated successfully!';
        
        // Log the action
        $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

        $categoryLogData = [
            'admin_user_id'   => $adminId,
            'action'          => 'settings_updated',
            'ip_hash'         => $ipHash,
            'user_agent_hash' => $userAgentHash,
            'metadata'        => json_encode(['settings_type' => 'general']),
            'created_at'      => date('Y-m-d H:i:s') // Replacing NOW() with a PHP timestamp
        ];
        log_system_action($conn, 'audit_logs', $categoryLogData);
    } else {
        $errorMessage = 'Failed to save settings. Please check folder permissions.';
    }
}

// Add new category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $categoryName = trim($_POST['category_name']);
    $categoryDescription = trim($_POST['category_description']);
    
    if (empty($categoryName)) {
        $errorMessage = 'Category name is required.';
    } else {
        $data = array($categoryName, $categoryDescription);
        if (insert_category($conn, $data)) {
            $successMessage = 'Category added successfully!';
            
            // Log the action
            $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

            $categoryLogData = [
                'admin_user_id'   => $adminId,
                'action'          => 'category_added',
                'ip_hash'         => $ipHash,
                'user_agent_hash' => $userAgentHash,
                'metadata'        => json_encode(['category_name' => $categoryName]),
                'created_at'      => date('Y-m-d H:i:s') // Replacing NOW() with a PHP timestamp
            ];
            log_system_action($conn, 'audit_logs', $categoryLogData);
        } else {
            $errorMessage = 'Failed to add category.';
        }
    }
}

// Update category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $categoryId = (int)$_POST['category_id'];
    $categoryName = trim($_POST['category_name']);
    $categoryDescription = trim($_POST['category_description']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $data = array($categoryName, $categoryDescription, $isActive, $categoryId);
    if (update_category($conn, $data)) {
        $successMessage = 'Category updated successfully!';
        
        // Log the action
        $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

        $categoryLogData = [
            'admin_user_id'   => $adminId,
            'action'          => 'category_updated',
            'ip_hash'         => $ipHash,
            'user_agent_hash' => $userAgentHash,
            'metadata'        => json_encode(['category_id' => $categoryId]),
            'created_at'      => date('Y-m-d H:i:s') // Replacing NOW() with a PHP timestamp
        ];
        log_system_action($conn, 'audit_logs', $categoryLogData);
    } else {
        $errorMessage = 'Failed to update category.';
    }
}

// Delete category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $categoryId = (int)$_POST['category_id'];
    
    // Check if category has reports
    $reportCount = count_category_reports($conn, $categoryId);
    
    if ($reportCount > 0) {
        $errorMessage = "Cannot delete category. It has $reportCount report(s) associated with it.";
    } else {
        if (delete_category($conn, $categoryId)) {
            $successMessage = 'Category deleted successfully!';
            
            // Log the action
            $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

            $categoryLogData = [
                'admin_user_id'   => $adminId,
                'action'          => 'category_deleted',
                'ip_hash'         => $ipHash,
                'user_agent_hash' => $userAgentHash,
                'metadata'        => json_encode(['category_id' => $categoryId]),
                'created_at'      => date('Y-m-d H:i:s') // Replacing NOW() with a PHP timestamp
            ];
            log_system_action($conn, 'audit_logs', $categoryLogData);
        } else {
            $errorMessage = 'Failed to delete category.';
        }
    }
}

// Run cleanup procedure
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_cleanup'])) {
    try {
        $stmt = $conn->prepare("CALL CleanupExpiredData()");
        $stmt->execute();
        $successMessage = 'Cleanup procedure executed successfully! Expired data has been removed.';
        
        // Log the action
        $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

        $categoryLogData = [
            'admin_user_id'   => $adminId,
            'action'          => 'cleanup_executed',
            'ip_hash'         => $ipHash,
            'user_agent_hash' => $userAgentHash
        ];
        log_system_action($conn, 'audit_logs', $categoryLogData);
    } catch (PDOException $e) {
        $errorMessage = 'Cleanup failed: ' . $e->getMessage();
    }
}

// Get all categories
$categories = get_ordered_categories($conn);

// Load settings
$settingsFile = __DIR__ . '/inc/settings.json';
$settings = [];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
}

// Get system statistics for info page
$systemStats = get_system_stats($conn);

// Get database size
$dbSize = get_database_size($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - WhistleGuard</title>
    
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
        
        .btn-danger-glass {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.9), rgba(200, 35, 51, 0.9));
            border: 1px solid rgba(220, 53, 69, 0.3);
            padding: 6px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .btn-danger-glass:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
            color: white;
        }
        
        .alert-custom {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        
        .category-table {
            width: 100%;
        }
        
        .category-table th {
            padding: 12px;
            font-weight: 600;
            font-size: 0.8rem;
            color: #8b92b0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .category-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            vertical-align: middle;
        }
        
        .category-table tr:hover {
            background: rgba(79, 70, 229, 0.05);
        }
        
        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .status-inactive {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .text-muted-custom {
            color: #8b92b0;
        }
        
        .info-box {
            background: rgba(26, 31, 45, 0.5);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .info-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8b92b0;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
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
            .category-table {
                font-size: 0.75rem;
            }
            .category-table td, .category-table th {
                padding: 8px;
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
                        <i class="fas fa-cog me-2" style="color: #4f46e5;"></i>
                        System Settings
                    </h2>
                    <p class="mb-0">Configure your WhistleGuard platform settings and preferences</p>
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
            <!-- Left Column - Settings -->
            <div class="col-lg-6">
                <!-- General Settings -->
                <div class="glass-card">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-globe me-2" style="color: #4f46e5;"></i>General Settings
                        </h5>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Site Name</label>
                                <input type="text" name="site_name" class="form-control-glass w-100" 
                                       value="<?php echo htmlspecialchars($settings['site_name'] ?? 'WhistleGuard'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Site Description</label>
                                <textarea name="site_description" class="form-control-glass w-100" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? 'Anonymous Whistleblower Platform'); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Items Per Page</label>
                                    <input type="number" name="items_per_page" class="form-control-glass w-100" 
                                           value="<?php echo $settings['items_per_page'] ?? 15; ?>" min="5" max="100">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Auto-Delete Reports (Days)</label>
                                    <input type="number" name="auto_delete_days" class="form-control-glass w-100" 
                                           value="<?php echo $settings['auto_delete_days'] ?? 365; ?>" min="0" max="730">
                                    <small class="text-muted-custom">0 = Never auto-delete</small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update_general_settings" class="btn-primary-glass">
                                    <i class="fas fa-save me-2"></i>Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Maintenance -->
                <div class="glass-card">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-tools me-2" style="color: #4f46e5;"></i>Maintenance
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="info-box">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div>
                                    <div class="info-label">Cleanup Expired Data</div>
                                    <div class="info-value small">Remove expired reports, messages, and evidence files</div>
                                </div>
                                <form method="POST" action="" onsubmit="return confirm('This will permanently delete all expired data. Are you sure?');">
                                    <button type="submit" name="run_cleanup" class="btn-secondary-glass">
                                        <i class="fas fa-broom me-2"></i>Run Cleanup
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-3" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); color: #f59e0b;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <small>Running cleanup will permanently delete reports that have passed their expiration date, messages older than 30 days, and expired access tokens.</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Categories & Info -->
            <div class="col-lg-6">
                <!-- Add Category -->
                <div class="glass-card">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-plus-circle me-2" style="color: #4f46e5;"></i>Add New Category
                        </h5>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="category_name" class="form-control-glass w-100" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description (Optional)</label>
                                <textarea name="category_description" class="form-control-glass w-100" rows="2"></textarea>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="add_category" class="btn-primary-glass">
                                    <i class="fas fa-plus me-2"></i>Add Category
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Manage Categories -->
                <div class="glass-card">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-list me-2" style="color: #4f46e5;"></i>Manage Categories
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="category-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td>
                                        <input type="text" class="form-control-glass" style="width: 120px; padding: 6px 10px; font-size: 0.8rem;" 
                                               value="<?php echo htmlspecialchars($category['name']); ?>" 
                                               id="name_<?php echo $category['id']; ?>">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control-glass" style="width: 180px; padding: 6px 10px; font-size: 0.8rem;" 
                                               value="<?php echo htmlspecialchars($category['description'] ?? ''); ?>" 
                                               id="desc_<?php echo $category['id']; ?>">
                                    </td>
                                    <td>
                                        <select class="form-control-glass" style="width: 100px; padding: 6px 10px; font-size: 0.8rem;" 
                                                id="status_<?php echo $category['id']; ?>">
                                            <option value="1" <?php echo $category['is_active'] ? 'selected' : ''; ?>>Active</option>
                                            <option value="0" <?php echo !$category['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn-secondary-glass" style="padding: 4px 12px; font-size: 0.7rem;" 
                                                    onclick="updateCategory(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                            <?php if ($category['id'] > 6): // Don't allow deletion of default categories ?>
                                            <button class="btn-danger-glass" style="padding: 4px 12px; font-size: 0.7rem;" 
                                                    onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="glass-card">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-server me-2" style="color: #4f46e5;"></i>System Information
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="info-box">
                                    <div class="info-label">Total Reports</div>
                                    <div class="info-value"><?php echo $systemStats['total_reports']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-box">
                                    <div class="info-label">Unique Whistleblowers</div>
                                    <div class="info-value"><?php echo $systemStats['unique_whistleblowers']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-box">
                                    <div class="info-label">Total Messages</div>
                                    <div class="info-value"><?php echo $systemStats['total_messages']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-box">
                                    <div class="info-label">Evidence Files</div>
                                    <div class="info-value"><?php echo $systemStats['total_evidences']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-box">
                                    <div class="info-label">Admin Users</div>
                                    <div class="info-value"><?php echo $systemStats['total_admins']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-box">
                                    <div class="info-label">Database Size</div>
                                    <div class="info-value"><?php echo ($dbSize['size_mb'] ?? 0) . ' MB'; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="info-box mt-2">
                            <div class="info-label">PHP Version</div>
                            <div class="info-value"><?php echo phpversion(); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Server Software</div>
                            <div class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
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
        // Update category via AJAX
        function updateCategory(categoryId) {
            const name = document.getElementById(`name_${categoryId}`).value;
            const description = document.getElementById(`desc_${categoryId}`).value;
            const isActive = document.getElementById(`status_${categoryId}`).value;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `update_category=1&category_id=${categoryId}&category_name=${encodeURIComponent(name)}&category_description=${encodeURIComponent(description)}&is_active=${isActive}`
            })
            .then(response => response.text())
            .then(() => {
                location.reload();
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Delete category
        function deleteCategory(categoryId) {
            if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_category" value="1"><input type="hidden" name="category_id" value="${categoryId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Set active sidebar item
        document.addEventListener('DOMContentLoaded', function() {
            var sidebarItems = document.querySelectorAll('.sidebar-nav li');
            if (sidebarItems[3]) {
                sidebarItems[3].classList.add('active');
            }
        });
        
        // Console security notice
        console.log('%c⚠️ ADMIN SETTINGS - Authorized Access Only ⚠️', 'color: #ef4444; font-size: 12px; font-weight: bold;');
    </script>
</body>
</html>