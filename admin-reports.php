<?php
// admin-reports.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: admin-login.php");
    exit();
}

// Include database connection and report functions
require_once 'db_connection.php';
require_once 'app/model/report.php';

// Get admin info
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';

// Pagination settings
$itemsPerPage = 15;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$priorityFilter = $_GET['priority'] ?? 'all';
$categoryFilter = $_GET['category'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');
$unreadFilter = $_GET['unread'] ?? 'all'; // New filter for unread messages

// Build the query with filters
$sql = "
    SELECT r.*, c.name as category_name 
    FROM reports r
    JOIN categories c ON r.category_id = c.id
    WHERE 1=1
";
$countSql = "
    SELECT COUNT(*) as total 
    FROM reports r
    JOIN categories c ON r.category_id = c.id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== 'all') {
    $sql .= " AND r.status = ?";
    $countSql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter !== 'all') {
    $sql .= " AND r.priority = ?";
    $countSql .= " AND r.priority = ?";
    $params[] = $priorityFilter;
}

if ($categoryFilter !== 'all') {
    $sql .= " AND c.name = ?";
    $countSql .= " AND c.name = ?";
    $params[] = $categoryFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (r.tracking_code LIKE ? OR r.id LIKE ?)";
    $countSql .= " AND (r.tracking_code LIKE ? OR r.id LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Add unread messages filter
if ($unreadFilter === 'with_unread') {
    $sql .= " AND EXISTS (
        SELECT 1 FROM messages m 
        WHERE m.report_id = r.id 
        AND m.sender_type = 'whistleblower' 
        AND m.is_read = FALSE
    )";
    $countSql .= " AND EXISTS (
        SELECT 1 FROM messages m 
        WHERE m.report_id = r.id 
        AND m.sender_type = 'whistleblower' 
        AND m.is_read = FALSE
    )";
} elseif ($unreadFilter === 'without_unread') {
    $sql .= " AND NOT EXISTS (
        SELECT 1 FROM messages m 
        WHERE m.report_id = r.id 
        AND m.sender_type = 'whistleblower' 
        AND m.is_read = FALSE
    )";
    $countSql .= " AND NOT EXISTS (
        SELECT 1 FROM messages m 
        WHERE m.report_id = r.id 
        AND m.sender_type = 'whistleblower' 
        AND m.is_read = FALSE
    )";
}

// Get total count for pagination
$stmt = $conn->prepare($countSql);
$stmt->execute($params);
$totalReports = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalReports / $itemsPerPage);

// IMPORTANT FIX: Use string concatenation for LIMIT and OFFSET (not parameters)
$sql .= " ORDER BY r.created_at DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;

// Prepare and execute without LIMIT/OFFSET as parameters
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter
$categories = categories_filters($conn);

// Get statistics for filter bar
$allCount = count_all_reports($conn);
$newCount = count_reports($conn, "new");
$investigatingCount = count_reports($conn, "investigating");
$resolvedCount = count_reports($conn, "resolved");
$closedCount = count_reports($conn, "closed");

// Get count of reports with unread messages
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT r.id) as count
    FROM reports r
    JOIN messages m ON r.id = m.report_id
    WHERE m.sender_type = 'whistleblower' 
    AND m.is_read = FALSE
    AND r.status != 'closed'
");
$stmt->execute();
$unreadReportsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Reports - Admin Panel | WhistleGuard</title>
    
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
        
        .welcome-text h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .role-badge {
            background: rgba(79, 70, 229, 0.2);
            border: 1px solid rgba(79, 70, 229, 0.3);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
        }
        
        /* Stats Cards */
        .stat-filter-card {
            background: rgba(20, 24, 36, 0.75);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 15px 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }
        
        .stat-filter-card:hover {
            transform: translateY(-3px);
            background: rgba(79, 70, 229, 0.15);
            border-color: rgba(79, 70, 229, 0.4);
        }
        
        .stat-filter-card.active {
            background: rgba(79, 70, 229, 0.2);
            border-color: rgba(79, 70, 229, 0.5);
        }
        
        .stat-filter-number {
            font-size: 1.8rem;
            font-weight: 800;
        }
        
        /* Unread Filter Card */
        .unread-filter-card {
            background: rgba(239, 68, 68, 0.15);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 15px 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }
        
        .unread-filter-card:hover {
            transform: translateY(-3px);
            background: rgba(239, 68, 68, 0.25);
            border-color: rgba(239, 68, 68, 0.5);
        }
        
        .unread-filter-card.active {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.7);
        }
        
        .unread-filter-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ef4444;
        }
        
        /* Glass Card */
        .glass-card {
            background: rgba(20, 24, 36, 0.75);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow: hidden;
        }
        
        /* Filter Bar */
        .filter-bar {
            background: rgba(26, 31, 45, 0.5);
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
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
        
        /* Table Styles */
        .reports-table {
            width: 100%;
        }
        
        .reports-table th {
            padding: 15px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8b92b0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .reports-table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        
        .reports-table tr:hover {
            background: rgba(79, 70, 229, 0.05);
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
        }
        
        .btn-sm-glass:hover {
            background: rgba(79, 70, 229, 0.2);
            color: white;
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
        
        .pagination {
            margin-top: 20px;
        }
        
        .pagination .page-link {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
            margin: 0 4px;
            border-radius: 10px;
        }
        
        .pagination .page-link:hover {
            background: rgba(79, 70, 229, 0.3);
            border-color: rgba(79, 70, 229, 0.5);
            color: white;
        }
        
        .pagination .active .page-link {
            background: #4f46e5;
            border-color: #4f46e5;
            color: white;
        }
        
        .pagination .disabled .page-link {
            opacity: 0.5;
        }
        
        .tracking-code {
            font-family: monospace;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .text-muted-custom {
            color: #8b92b0;
        }
        
        /* Unread Message Badge on Report Row */
        .unread-message-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
            gap: 4px;
        }
        
        .unread-message-badge i {
            font-size: 0.65rem;
        }
        
        .has-unread {
            border-left: 3px solid #ef4444;
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
            .stat-filter-number, .unread-filter-number {
                font-size: 1.3rem;
            }
            .reports-table th,
            .reports-table td {
                padding: 10px;
                font-size: 0.75rem;
            }
            .btn-sm-glass {
                padding: 3px 8px;
                font-size: 0.65rem;
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
        .unread-filter-note {
    display: inline-block;
    background: #ef4444;
    color: white;
    border-radius: 20px;
    padding: 2px 8px;
    font-size: 0.65rem;
    margin-left: 8px;
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
                <div class="welcome-text">
                    <h2 class="fw-bold">
                        <i class="fas fa-file-alt me-2" style="color: #4f46e5;"></i>
                        All Reports
                    </h2>
                    <p class="mb-0">Manage and review all whistleblower reports</p>
                </div>
            </div>
        </div>
        
        <!-- Stats Filter Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-2 col-6">
                <a href="?status=all&priority=all&category=all&unread=all&search=<?php echo urlencode($searchQuery); ?>" class="stat-filter-card <?php echo $statusFilter == 'all' && $priorityFilter == 'all' && $categoryFilter == 'all' && $unreadFilter == 'all' ? 'active' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-filter-number"><?php echo $allCount; ?></div>
                            <small class="text-muted-custom">Total</small>
                        </div>
                        <i class="fas fa-chart-simple fa-2x opacity-50"></i>
                    </div>
                </a>
            </div>
            <div class="col-md-2 col-6">
                <a href="?status=new&priority=all&category=all&unread=all&search=<?php echo urlencode($searchQuery); ?>" class="stat-filter-card <?php echo $statusFilter == 'new' ? 'active' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-filter-number" style="color: #3b82f6;"><?php echo $newCount; ?></div>
                            <small class="text-muted-custom">New</small>
                        </div>
                        <i class="fas fa-envelope fa-2x opacity-50" style="color: #3b82f6;"></i>
                    </div>
                </a>
            </div>
            <div class="col-md-2 col-6">
                <a href="?status=investigating&priority=all&category=all&unread=all&search=<?php echo urlencode($searchQuery); ?>" class="stat-filter-card <?php echo $statusFilter == 'investigating' ? 'active' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-filter-number" style="color: #f59e0b;"><?php echo $investigatingCount; ?></div>
                            <small class="text-muted-custom">Investigating</small>
                        </div>
                        <i class="fas fa-search fa-2x opacity-50" style="color: #f59e0b;"></i>
                    </div>
                </a>
            </div>
            <div class="col-md-2 col-6">
                <a href="?status=resolved&priority=all&category=all&unread=all&search=<?php echo urlencode($searchQuery); ?>" class="stat-filter-card <?php echo $statusFilter == 'resolved' ? 'active' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-filter-number" style="color: #10b981;"><?php echo $resolvedCount; ?></div>
                            <small class="text-muted-custom">Resolved</small>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50" style="color: #10b981;"></i>
                    </div>
                </a>
            </div>
            <div class="col-md-2 col-6">
                <a href="?status=closed&priority=all&category=all&unread=all&search=<?php echo urlencode($searchQuery); ?>" class="stat-filter-card <?php echo $statusFilter == 'closed' ? 'active' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-filter-number" style="color: #6b7280;"><?php echo $closedCount; ?></div>
                            <small class="text-muted-custom">Closed</small>
                        </div>
                        <i class="fas fa-archive fa-2x opacity-50" style="color: #6b7280;"></i>
                    </div>
                </a>
            </div>
            <div class="col-md-2 col-6">
                <a href="admin-reports.php" class="stat-filter-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-filter-number">
                                <i class="fas fa-undo-alt"></i>
                            </div>
                            <small class="text-muted-custom">Reset Filters</small>
                        </div>
                        <i class="fas fa-filter-circle-xmark fa-2x opacity-50"></i>
                    </div>
                </a>
            </div>
        </div>
        
       
        
        <!-- Reports Table -->
        <div class="glass-card">
            <!-- Filter Bar -->
                        <!-- Ultra Compact Filter Bar -->
            <div class="filter-bar">
                <form method="GET" action="" class="row g-2">
                    <div class="col-md-3 col-6">
                        <select name="priority" class="form-select-glass w-100" onchange="this.form.submit()">
                            <option value="all" <?php echo $priorityFilter == 'all' ? 'selected' : ''; ?>>📊 Priority: All</option>
                            <option value="low" <?php echo $priorityFilter == 'low' ? 'selected' : ''; ?>>🟢 Low</option>
                            <option value="medium" <?php echo $priorityFilter == 'medium' ? 'selected' : ''; ?>>🔵 Medium</option>
                            <option value="high" <?php echo $priorityFilter == 'high' ? 'selected' : ''; ?>>🟠 High</option>
                            <option value="critical" <?php echo $priorityFilter == 'critical' ? 'selected' : ''; ?>>🔴 Critical</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <select name="category" class="form-select-glass w-100" onchange="this.form.submit()">
                            <option value="all" <?php echo $categoryFilter == 'all' ? 'selected' : ''; ?>>📁 Category: All</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>" <?php echo $categoryFilter == $category['name'] ? 'selected' : ''; ?>>
                                📄 <?php echo htmlspecialchars(substr($category['name'], 0, 20)); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <select name="unread" class="form-select-glass w-100" onchange="this.form.submit()">
                            <option value="all" <?php echo $unreadFilter == 'all' ? 'selected' : ''; ?>>💬 Messages: All</option>
                            <option value="with_unread" <?php echo $unreadFilter == 'with_unread' ? 'selected' : ''; ?>>🔴 Unread (<?php echo $unreadReportsCount; ?>)</option>
                            <option value="without_unread" <?php echo $unreadFilter == 'without_unread' ? 'selected' : ''; ?>>✅ Read</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control-glass w-100" 
                                   placeholder="🔍 Search tracking code or ID..." 
                                   value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                        <button type="submit" class="btn-primary-glass w-100 py-2">
                            <i class="fas fa-filter me-2"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Table -->
            <div class="table-responsive">
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tracking Code</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Visibility</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted-custom">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                No reports found matching your criteria
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($reports as $report): 
                            $unreadCount = getReportUnreadCount($conn, $report['id']);
                            $rowClass = $unreadCount > 0 ? 'has-unread' : '';
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><code>#<?php echo $report['id']; ?></code></td>
                            <td class="tracking-code"><code><?php echo htmlspecialchars($report['tracking_code']); ?></code></td>
                            <td><?php echo htmlspecialchars($report['category_name']); ?></td>
                            <td><span class="priority-badge priority-<?php echo $report['priority']; ?>"><?php echo ucfirst($report['priority']); ?></span></td>
                            <td>
                                <span class="status-badge status-<?php echo $report['status']; ?>"><?php echo ucfirst($report['status']); ?></span>
                                <?php if ($unreadCount > 0): ?>
                                <span class="unread-message-badge" title="<?php echo $unreadCount; ?> new message<?php echo $unreadCount > 1 ? 's' : ''; ?>">
                                    <i class="fas fa-comment-dots me-1"></i><?php echo $unreadCount; ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="visibility-badge visibility-<?php echo $report['visibility']; ?>">
                                    <i class="fas <?php echo $report['visibility'] == 'public' ? 'fa-globe' : 'fa-lock'; ?> me-1"></i>
                                    <?php echo ucfirst($report['visibility']); ?>
                                </span>
                            </td>
                            <td><small><?php echo date('M j, Y g:i A', strtotime($report['created_at'])); ?></small></td>
                            <td>
                                <a href="admin-view-report.php?id=<?php echo $report['id']; ?>" class="btn-sm-glass">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="admin-edit-report.php?id=<?php echo $report['id']; ?>" class="btn-sm-glass">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center p-3 border-top border-white border-opacity-10 flex-wrap gap-3">
                <div class="text-muted-custom small">
                    Showing <?php echo count($reports); ?> of <?php echo $totalReports; ?> reports
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&status=<?php echo urlencode($statusFilter); ?>&priority=<?php echo urlencode($priorityFilter); ?>&category=<?php echo urlencode($categoryFilter); ?>&unread=<?php echo urlencode($unreadFilter); ?>&search=<?php echo urlencode($searchQuery); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php 
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                        <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&priority=<?php echo urlencode($priorityFilter); ?>&category=<?php echo urlencode($categoryFilter); ?>&unread=<?php echo urlencode($unreadFilter); ?>&search=<?php echo urlencode($searchQuery); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&status=<?php echo urlencode($statusFilter); ?>&priority=<?php echo urlencode($priorityFilter); ?>&category=<?php echo urlencode($categoryFilter); ?>&unread=<?php echo urlencode($unreadFilter); ?>&search=<?php echo urlencode($searchQuery); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="inc/background.js"></script>
    <script src="inc/sidebar.js"></script>
    <script>
        // Console security notice
        console.log('%c⚠️ ADMIN REPORTS - Authorized Access Only ⚠️', 'color: #ef4444; font-size: 12px; font-weight: bold;');
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