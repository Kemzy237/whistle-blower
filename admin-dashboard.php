<?php
// admin-dashboard.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Include database connection
require_once 'db_connection.php';
include "app/model/report.php";

// Get admin info
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';

// Get statistics
$totalReports = count_all_reports($conn);
$newReports = count_reports($conn, "new");
$investigatingReports = count_reports($conn, "investigating");
$resolvedReports = count_reports($conn, "resolved");
$closedReports = count_reports($conn, "closed");

// Get priority distribution
$priorityStats = get_priority_distribution($conn);

// Get monthly report trends (last 6 months)
$monthlyTrends = get_monthly_report_trends($conn);

// Get category distribution
$categoryStats = get_category_distribution($conn);

// Get recent reports for sidebar widget (limited)
$recentReports = recent_reports($conn);

// Get weekly activity
$weeklyActivity = get_weekly_activity($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - WhistleGuard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="inc/background.css">
    <link rel="stylesheet" href="inc/sidebar.css">
    
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
            z-index: 100;
        }
        
        .main-content.full-width {
            margin-left: 0;
        }
        
        /* Header Section Styles */
        .header-section {
            background: rgba(20, 24, 36, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            animation: slideInDown 0.6s ease-out;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header-section:hover {
            background: rgba(26, 31, 45, 0.6);
            border-color: rgba(79, 70, 229, 0.2);
        }
        
        .welcome-text h2 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #ffffff, #a5b4fc);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .welcome-text p {
            color: #8b92b0;
            margin-bottom: 0;
        }
        
        .role-badge {
            background: rgba(79, 70, 229, 0.2);
            border: 1px solid rgba(79, 70, 229, 0.3);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .role-badge i {
            color: #4f46e5;
            margin-right: 8px;
        }
        
        /* Glass Card Styles */
        .glass-card {
            background: rgba(20, 24, 36, 0.75);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            height: 100%;
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
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
        
        .glass-card:nth-child(1) { animation-delay: 0.1s; }
        .glass-card:nth-child(2) { animation-delay: 0.2s; }
        
        .glass-card:hover {
            transform: translateY(-2px);
            background: rgba(26, 31, 45, 0.85);
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.2);
        }
        
        .stat-card {
            padding: 20px;
            transition: all 0.3s ease;
            background: rgba(20, 24, 36, 0.75);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(79, 70, 229, 0.3);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            height: 100%;
            animation: scaleIn 0.5s ease-out forwards;
            opacity: 0;
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        
        .stat-card:hover {
            transform: translateY(-4px);
            background: rgba(26, 31, 45, 0.85);
            border-color: rgba(79, 70, 229, 0.6);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.2);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(79, 70, 229, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #4f46e5;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: white;
        }
        
        .chart-container {
            padding: 20px;
            height: 300px;
            position: relative;
        }
        
        canvas {
            max-height: 250px;
            width: 100% !important;
        }

        .table tr,.table td, .table th{
            color: #ffffff;
        }
        
        .recent-item {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.2s ease;
        }
        
        .recent-item:last-child {
            border-bottom: none;
        }
        
        .recent-item:hover {
            background: rgba(79, 70, 229, 0.1);
        }
        
        .status-badge {
            padding: 4px 10px;
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
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        
        .priority-low { background: #10b981; }
        .priority-medium { background: #3b82f6; }
        .priority-high { background: #f59e0b; }
        .priority-critical { background: #ef4444; }
        
        .btn-sm-glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
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
            transform: translateY(-1px);
        }
        
        .text-muted-custom {
            color: #8b92b0;
        }
        
        .tracking-code {
            font-family: monospace;
            font-size: 0.8rem;
        }
        
        /* Chart animation keyframes */
        @keyframes chartPop {
            0% {
                transform: scale(0.5);
                opacity: 0;
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        /* Responsive Design */
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
            .chart-container {
                height: 250px;
            }
            .welcome-text h2 {
                font-size: 1.4rem;
            }
        }
        
        @media (max-width: 768px) {
            .stat-number {
                font-size: 1.5rem;
            }
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            .chart-container {
                height: 220px;
                padding: 15px;
            }
            .header-section {
                padding: 15px 20px;
            }
            .role-badge {
                padding: 5px 12px;
                font-size: 0.75rem;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(79, 70, 229, 0.5);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(79, 70, 229, 0.7);
        }
        
        /* Table styling */
        .table {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: rgba(255,255,255,0.02);
        }
        
        .table td {
            vertical-align: middle;
        }
        
        /* Card header styling */
        .card-header-custom {
            background: rgba(26, 31, 45, 0.6);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 15px 20px;
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
        <!-- Enhanced Header Section -->
        <div class="header-section header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="welcome-text">
                    <h2 class="fw-bold">
                        <i class="fas fa-waveform me-2" style="color: #4f46e5;"></i>
                        Welcome back, <?php echo htmlspecialchars($adminName); ?>
                    </h2>
                    <p class="mb-0">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <?php echo date('l, F j, Y'); ?> • 
                        <i class="fas fa-chart-line ms-2 me-1"></i>
                        Here's your platform overview
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards Row -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted-custom small mb-1">
                                <i class="fas fa-chart-simple me-1"></i>Total Reports
                            </p>
                            <h3 class="stat-number mb-0"><?php echo $totalReports; ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted-custom small mb-1">
                                <i class="fas fa-clock me-1"></i>New Reports
                            </p>
                            <h3 class="stat-number mb-0" style="color: #3b82f6;"><?php echo $newReports; ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted-custom small mb-1">
                                <i class="fas fa-search me-1"></i>Investigating
                            </p>
                            <h3 class="stat-number mb-0" style="color: #f59e0b;"><?php echo $investigatingReports; ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-microscope"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted-custom small mb-1">
                                <i class="fas fa-check-circle me-1"></i>Resolved
                            </p>
                            <h3 class="stat-number mb-0" style="color: #10b981;"><?php echo $resolvedReports; ?></h3>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Status Distribution Chart -->
            <div class="col-xl-6">
                <div class="glass-card">
                    <div class="p-3 border-bottom border-white border-opacity-10">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-chart-pie me-2" style="color: #4f46e5;"></i>Report Status Distribution
                        </h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Priority Distribution Chart -->
            <div class="col-xl-6">
                <div class="glass-card">
                    <div class="p-3 border-bottom border-white border-opacity-10">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-exclamation-triangle me-2" style="color: #4f46e5;"></i>Priority Distribution
                        </h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="priorityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Second Row of Charts -->
        <div class="row g-4 mb-4">
            <!-- Monthly Trends Chart -->
            <div class="col-xl-7">
                <div class="glass-card">
                    <div class="p-3 border-bottom border-white border-opacity-10">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-chart-line me-2" style="color: #4f46e5;"></i>Monthly Report Trends (Last 6 Months)
                        </h5>
                    </div>
                    <div class="chart-container" style="height: 320px;">
                        <canvas id="trendsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Category Distribution Chart -->
            <div class="col-xl-5">
                <div class="glass-card">
                    <div class="p-3 border-bottom border-white border-opacity-10">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-tags me-2" style="color: #4f46e5;"></i>Top Categories
                        </h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Reports Widget -->
        <div class="row g-4">
            <div class="col-12">
                <div class="glass-card" style="animation-delay: 0.5s;">
                    <div class="p-3 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-history me-2" style="color: #4f46e5;"></i>Recent Activity
                        </h5>
                        <a href="admin-reports.php" class="btn-sm-glass">
                            <i class="fas fa-arrow-right me-1"></i>View All Reports
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <th class="border-0">Tracking Code</th>
                                    <th class="border-0">Category</th>
                                    <th class="border-0">Priority</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Submitted</th>
                                    <th class="border-0">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentReports)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted-custom">
                                        <i class="fas fa-inbox me-2"></i>No reports found
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recentReports as $report): ?>
                                <tr>
                                    <td class="tracking-code"><code><?php echo htmlspecialchars(substr($report['tracking_code'], 0, 12)); ?></code></td>
                                    <td><?php echo htmlspecialchars($report['category_name']); ?></td>
                                    <td><span class="priority-badge priority-<?php echo $report['priority']; ?>"><?php echo ucfirst($report['priority']); ?></span></td>
                                    <td><span class="status-badge status-<?php echo $report['status']; ?>"><?php echo ucfirst($report['status']); ?></span></td>
                                    <td><small><?php echo date('M j, Y g:i A', strtotime($report['created_at'])); ?></small></td>
                                    <td>
                                        <a href="admin-view-report.php?id=<?php echo $report['id']; ?>" class="btn-sm-glass">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="inc/background.js"></script>
    <script src="inc/sidebar.js"></script>
    <script>
        // Helper function to create animated charts
        function createAnimatedChart(ctx, type, data, options, animationDelay = 500) {
            return new Chart(ctx, {
                type: type,
                data: data,
                options: {
                    ...options,
                    animation: {
                        duration: 1500,
                        easing: 'easeOutQuart',
                        delay: animationDelay
                    },
                    transitions: {
                        show: {
                            animations: {
                                scale: {
                                    from: 0,
                                    to: 1,
                                    duration: 1000,
                                    easing: 'easeOutElastic'
                                }
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: true
                }
            });
        }
        
        // Wait for page load to ensure canvas elements are ready
        document.addEventListener('DOMContentLoaded', function() {
            
            // Status Chart (Doughnut)
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            createAnimatedChart(statusCtx, 'doughnut', {
                labels: ['New', 'Investigating', 'Resolved', 'Closed'],
                datasets: [{
                    data: [<?php echo $newReports; ?>, <?php echo $investigatingReports; ?>, <?php echo $resolvedReports; ?>, <?php echo $closedReports; ?>],
                    backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#6b7280'],
                    borderWidth: 0,
                    hoverOffset: 10,
                    hoverBorderWidth: 2,
                    hoverBorderColor: '#fff'
                }]
            }, {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#e0e0e0',
                            font: { size: 11 },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#e0e0e0',
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                cutout: '60%'
            }, 100);
            
            // Priority Chart (Bar)
            const priorityData = <?php 
                $priorityMap = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
                foreach ($priorityStats as $stat) {
                    $priorityMap[$stat['priority']] = $stat['count'];
                }
                echo json_encode(array_values($priorityMap));
            ?>;
            
            const priorityCtx = document.getElementById('priorityChart').getContext('2d');
            createAnimatedChart(priorityCtx, 'bar', {
                labels: ['Low', 'Medium', 'High', 'Critical'],
                datasets: [{
                    label: 'Number of Reports',
                    data: priorityData,
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                    borderRadius: 8,
                    borderSkipped: false,
                    hoverBackgroundColor: ['#059669', '#2563eb', '#d97706', '#dc2626'],
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }]
            }, {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#e0e0e0',
                        callbacks: {
                            label: function(context) {
                                return `Reports: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#e0e0e0', stepSize: 1 },
                        title: {
                            display: true,
                            text: 'Number of Reports',
                            color: '#8b92b0'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#e0e0e0', font: { weight: 'bold' } }
                    }
                }
            }, 200);
            
            // Monthly Trends Chart (Line)
            const trendsLabels = <?php 
                $labels = array_map(function($item) { return $item['month']; }, $monthlyTrends);
                echo json_encode($labels);
            ?>;
            const trendsData = <?php 
                $data = array_map(function($item) { return $item['count']; }, $monthlyTrends);
                echo json_encode($data);
            ?>;
            
            const trendsCtx = document.getElementById('trendsChart').getContext('2d');
            createAnimatedChart(trendsCtx, 'line', {
                labels: trendsLabels,
                datasets: [{
                    label: 'Reports Submitted',
                    data: trendsData,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4f46e5'
                }]
            }, {
                plugins: {
                    legend: {
                        labels: { color: '#e0e0e0', usePointStyle: true }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#e0e0e0'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#e0e0e0', stepSize: 1 },
                        title: {
                            display: true,
                            text: 'Number of Reports',
                            color: '#8b92b0'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#e0e0e0' }
                    }
                },
                elements: {
                    line: {
                        tension: 0.4
                    }
                }
            }, 300);
            
            // Category Chart (Horizontal Bar)
            const categoryLabels = <?php 
                $labels = array_map(function($item) { return $item['name']; }, $categoryStats);
                echo json_encode($labels);
            ?>;
            const categoryData = <?php 
                $data = array_map(function($item) { return $item['count']; }, $categoryStats);
                echo json_encode($data);
            ?>;
            
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            createAnimatedChart(categoryCtx, 'bar', {
                labels: categoryLabels,
                datasets: [{
                    label: 'Reports',
                    data: categoryData,
                    backgroundColor: 'rgba(79, 70, 229, 0.8)',
                    borderRadius: 8,
                    borderSkipped: false,
                    hoverBackgroundColor: 'rgba(79, 70, 229, 1)'
                }]
            }, {
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#e0e0e0',
                        callbacks: {
                            label: function(context) {
                                return `Reports: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#e0e0e0', stepSize: 1 },
                        title: {
                            display: true,
                            text: 'Number of Reports',
                            color: '#8b92b0'
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#e0e0e0', font: { size: 11 } }
                    }
                }
            }, 400);
        });
        
        // Console security notice
        console.log('%c⚠️ ADMIN PANEL - Authorized Access Only ⚠️', 'color: #ef4444; font-size: 12px; font-weight: bold;');
        console.log('%c' + new Date().toLocaleString(), 'color: #8b92b0; font-size: 10px;');
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var sidebarItems = document.querySelectorAll('.sidebar-nav li');
            if (sidebarItems[0]) {
                sidebarItems[0].classList.add('active');
            }
        });
    </script>
</body>
</html>