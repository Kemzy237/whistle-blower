<?php
// dashboard.php
session_start();
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/app/model/report.php';

// Pagination settings
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Category filter
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Get total count for pagination
$totalReports = countPublicReports($conn, $categoryFilter);
$totalPages = ceil($totalReports / $itemsPerPage);

// Get public reports
$publicReports = getPublicReports($conn, $itemsPerPage, $offset, $categoryFilter);

// Get all categories for filter
$categories = getAllCategories($conn);

// Decrypt report descriptions for display
$decryptedReports = [];
foreach ($publicReports as $report) {
    $decryptedReports[] = [
        'id' => $report['id'],
        'tracking_code' => substr($report['tracking_code'], 0, 8) . '...',
        'category_name' => $report['category_name'],
        'description' => decryptData($report['encrypted_description'], ENCRYPTION_KEY),
        'status' => $report['status'],
        'priority' => $report['priority'],
        'created_at' => $report['created_at'],
        'has_evidence' => false // Will be checked later
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Reports Dashboard - WhistleGuard</title>
    
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
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .glass-card:hover {
            transform: translateY(-2px);
            background: rgba(26, 31, 45, 0.9);
            border-color: rgba(255, 255, 255, 0.12);
        }
        
        .report-card {
            background: rgba(20, 24, 36, 0.75);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .report-card:hover {
            transform: translateY(-2px);
            background: rgba(26, 31, 45, 0.85);
            border-color: rgba(79, 70, 229, 0.3);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .btn-primary-glass {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.9), rgba(124, 58, 237, 0.9));
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
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
            padding: 8px 20px;
            border-radius: 10px;
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
        
        .category-badge {
            background: rgba(79, 70, 229, 0.2);
            border: 1px solid rgba(79, 70, 229, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        
        .filter-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 10px;
            color: #e0e0e0;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            margin: 0 4px 8px 0;
        }
        
        .filter-btn.active, .filter-btn:hover {
            background: rgba(79, 70, 229, 0.3);
            border-color: rgba(79, 70, 229, 0.5);
            color: white;
        }
        
        .navbar-glass {
            background: rgba(15, 19, 34, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 16px 0;
            position: relative;
            z-index: 100;
        }
        
        .content-wrapper {
            position: relative;
            z-index: 2;
        }
        
        .text-muted-custom {
            color: #8b92b0;
        }
        
        .evidence-item {
            background: rgba(26, 31, 45, 0.5);
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }
        
        .evidence-item:hover {
            background: rgba(79, 70, 229, 0.15);
        }
        
        .modal-content-glass {
            background: rgba(15, 19, 34, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
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
        
        .description-text {
            max-height: 120px;
            overflow-y: auto;
            padding-right: 8px;
        }
        
        .description-text::-webkit-scrollbar {
            width: 4px;
        }
        
        hr {
            border-color: rgba(255, 255, 255, 0.05);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade {
            animation: fadeIn 0.4s ease-out;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #4f46e5;
            margin-bottom: 20px;
            opacity: 0.5;
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
            <div>
                <a href="index.php" class="btn-outline-glass" style="padding: 6px 16px;">
                    <i class="fas fa-home me-1"></i>Home
                </a>
                <a href="index.php#trackReportModal" class="btn-outline-glass ms-2" style="padding: 6px 16px;">
                    <i class="fas fa-search me-1"></i>Track Report
                </a>
            </div>
        </div>
    </nav>
    
    <div class="content-wrapper" style="margin-top: 30px;">
        <div class="container">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="display-5 fw-bold mb-3">
                    <i class="fas fa-shield-alt me-2" style="color: #4f46e5;"></i>
                    WhistleGuard
                </h1>
                <p class="lead text-muted-custom">
                    View publicly approved whistleblower reports. Identities remain completely anonymous.
                </p>
            </div>
            
            <!-- Category Filters -->
            <div class="glass-card p-4 mb-5">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-filter me-2" style="color: #4f46e5;"></i>Filter by Category
                </h6>
                <div class="d-flex flex-wrap">
                    <a href="dashboard.php?category=all&page=1" 
                       class="filter-btn <?php echo $categoryFilter == 'all' ? 'active' : ''; ?>">
                        All Reports
                    </a>
                    <?php foreach ($categories as $category): ?>
                    <a href="dashboard.php?category=<?php echo urlencode($category['name']); ?>&page=1" 
                       class="filter-btn <?php echo $categoryFilter == $category['name'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Reports List -->
            <?php if (empty($decryptedReports)): ?>
            <div class="glass-card empty-state">
                <i class="fas fa-inbox"></i>
                <h4 class="fw-bold mb-2">No Public Reports Available</h4>
                <p class="text-muted-custom">There are no approved public reports at this time.</p>
                <a href="index.php" class="btn-primary-glass mt-3">
                    <i class="fas fa-arrow-left me-2"></i>Return Home
                </a>
            </div>
            <?php else: ?>
            
            <?php foreach ($decryptedReports as $index => $report): ?>
            <div class="report-card p-4 animate-fade" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                <div class="row">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                            <span class="category-badge">
                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($report['category_name']); ?>
                            </span>
                            <span class="status-badge status-<?php echo $report['status']; ?>">
                                <?php echo ucfirst($report['status']); ?>
                            </span>
                            <span class="priority-badge priority-<?php echo $report['priority']; ?>">
                                <i class="fas fa-flag me-1"></i><?php echo ucfirst($report['priority']); ?>
                            </span>
                            <span class="text-muted-custom small">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo date('M j, Y', strtotime($report['created_at'])); ?>
                            </span>
                        </div>
                        
                        <div class="description-text">
                            <p class="mb-0" style="line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars(substr($report['description'], 0, 500))); ?>
                                <?php if (strlen($report['description']) > 500): ?>
                                <span class="text-muted-custom">...</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4 mt-3 mt-md-0">
                        <div class="text-md-end">
                            <button class="btn-primary-glass view-report-btn" 
                                    data-report-id="<?php echo $report['id']; ?>"
                                    data-report-title="<?php echo htmlspecialchars($report['category_name']); ?> Report"
                                    data-report-description="<?php echo htmlspecialchars($report['description']); ?>"
                                    data-report-category="<?php echo htmlspecialchars($report['category_name']); ?>"
                                    data-report-status="<?php echo $report['status']; ?>"
                                    data-report-priority="<?php echo $report['priority']; ?>"
                                    data-report-date="<?php echo date('F j, Y g:i A', strtotime($report['created_at'])); ?>">
                                <i class="fas fa-eye me-2"></i>View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav>
                    <ul class="pagination">
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?category=<?php echo urlencode($categoryFilter); ?>&page=<?php echo $currentPage - 1; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?category=<?php echo urlencode($categoryFilter); ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?category=<?php echo urlencode($categoryFilter); ?>&page=<?php echo $currentPage + 1; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
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
    
    <!-- Report Detail Modal -->
    <div class="modal fade" id="reportDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-content-glass">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-file-alt me-2" style="color: #4f46e5;"></i>Report Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Dynamic content loaded via JS -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn-outline-glass" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Evidence View Modal -->
    <div class="modal fade" id="evidenceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-glass">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="fas fa-file-alt me-2" style="color: #4f46e5;"></i>View Evidence
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-shield-alt fa-3x mb-3" style="color: #4f46e5;"></i>
                    <p>You are about to view evidence from a public report.</p>
                    <div id="evidenceUrlContainer" class="mt-3"></div>
                    <div id="evidenceLoading" class="d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Creating secure access...</p>
                    </div>
                    <button id="proceedViewBtn" class="btn-primary-glass mt-3" style="display: none;">
                        <i class="fas fa-eye me-2"></i>Proceed to View
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View report details
        document.querySelectorAll('.view-report-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const reportId = this.dataset.reportId;
                const modal = new bootstrap.Modal(document.getElementById('reportDetailModal'));
                const modalBody = document.getElementById('modalBody');
                
                modalBody.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                
                modal.show();
                
                // Fetch report details including evidence
                try {
                    const response = await fetch(`app/get-report-details.php?report_id=${reportId}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        let evidencesHtml = '';
                        if (data.evidences && data.evidences.length > 0) {
                            evidencesHtml = `
                                <div class="mt-4">
                                    <h6 class="fw-bold mb-3">
                                        <i class="fas fa-paperclip me-2" style="color: #4f46e5;"></i>Evidence Files
                                    </h6>
                                    <div class="list-group">
                                        ${data.evidences.map(ev => `
                                            <div class="evidence-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-file-alt me-2" style="color: #4f46e5;"></i>
                                                    <small>Evidence #${ev.id}</small>
                                                    <br>
                                                    <small class="text-muted-custom">
                                                        ${Math.round(ev.size_bytes / 1024)} KB • ${ev.mime_type.split('/')[1].toUpperCase()}
                                                    </small>
                                                </div>
                                                <button class="btn-primary-glass view-evidence-btn" 
                                                        data-report-id="${reportId}"
                                                        data-evidence-id="${ev.id}"
                                                        style="padding: 5px 12px; font-size: 0.8rem;">
                                                    <i class="fas fa-download me-1"></i>Download
                                                </button>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            `;
                        } else {
                            evidencesHtml = `
                                <div class="mt-4 text-center text-muted-custom">
                                    <i class="fas fa-info-circle me-1"></i>No evidence files attached to this report.
                                </div>
                            `;
                        }
                        
                        modalBody.innerHTML = `
                            <div class="mb-3">
                                <div class="d-flex gap-2 flex-wrap mb-3">
                                    <span class="category-badge">
                                        <i class="fas fa-tag me-1"></i>${escapeHtml(data.category)}
                                    </span>
                                    <span class="status-badge status-${data.status}">
                                        ${data.status.charAt(0).toUpperCase() + data.status.slice(1)}
                                    </span>
                                    <span class="priority-badge priority-${data.priority}">
                                        <i class="fas fa-flag me-1"></i>${data.priority.charAt(0).toUpperCase() + data.priority.slice(1)}
                                    </span>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted-custom">
                                        <i class="fas fa-calendar-alt me-1"></i>Submitted: ${data.created_at}
                                    </small>
                                </div>
                                <div class="glass-card-light p-3 mb-3">
                                    <strong class="d-block mb-2">Description:</strong>
                                    <p class="mb-0" style="line-height: 1.6;">${escapeHtml(data.description).replace(/\n/g, '<br>')}</p>
                                </div>
                                ${evidencesHtml}
                            </div>
                        `;
                        
                        // Attach evidence view handlers
                        document.querySelectorAll('.view-evidence-btn').forEach(evBtn => {
                            evBtn.addEventListener('click', function() {
                                const evidenceId = this.dataset.evidenceId;
                                const reportId = this.dataset.reportId;
                                viewEvidence(reportId, evidenceId);
                            });
                        });
                    } else {
                        modalBody.innerHTML = `
                            <div class="text-center py-4">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color: #f59e0b;"></i>
                                <p>${data.error || 'Failed to load report details'}</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    modalBody.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color: #ef4444;"></i>
                            <p>An error occurred while loading report details.</p>
                        </div>
                    `;
                }
            });
        });
        
        // View evidence function
        async function viewEvidence(reportId, evidenceId) {
            const modal = new bootstrap.Modal(document.getElementById('evidenceModal'));
            const proceedBtn = document.getElementById('proceedViewBtn');
            const container = document.getElementById('evidenceUrlContainer');
            const loadingDiv = document.getElementById('evidenceLoading');
            
            container.innerHTML = '';
            proceedBtn.style.display = 'none';
            loadingDiv.classList.remove('d-none');
            
            modal.show();
            
            try {
                const response = await fetch('app/create-public-access-token.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `report_id=${reportId}&evidence_id=${evidenceId}`
                });
                const data = await response.json();
                
                loadingDiv.classList.add('d-none');
                
                if (data.success) {
                    container.innerHTML = `
                        <div class="alert alert-warning small">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This access link will expire in 1 hour and can only be used once.
                        </div>
                    `;
                    
                    proceedBtn.style.display = 'inline-block';
                    proceedBtn.onclick = () => {
                        window.open(data.view_url, '_blank');
                        modal.hide();
                    };
                } else {
                    container.innerHTML = `
                        <div class="alert alert-danger small">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            ${data.error || 'Failed to create access token'}
                        </div>
                    `;
                }
            } catch (error) {
                loadingDiv.classList.add('d-none');
                container.innerHTML = `
                    <div class="alert alert-danger small">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        An error occurred. Please try again.
                    </div>
                `;
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        console.log('%c🔒 PUBLIC DASHBOARD: Viewing approved anonymous reports.', 'color: #4f46e5; font-size: 12px; font-weight: bold;');
    </script>

    <script src="inc/background.js"></script>
</body>
</html>