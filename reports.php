<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle report deletion
if (isset($_GET['delete'])) {
    $report_id = (int)$_GET['delete'];
    try {
        deleteReport($pdo, $report_id);
        header('Location: reports.php?success=deleted');
        exit;
    } catch (Exception $e) {
        header('Location: reports.php?error=delete_failed');
        exit;
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15; // Show more reports per page

// Get filter and search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$material = isset($_GET['material']) ? $_GET['material'] : '';
$prepared_by = isset($_GET['prepared_by']) ? $_GET['prepared_by'] : '';

// Fetch reports with filters
$reports = getReports($pdo, $page, $per_page, $search, $from_date, $to_date, $material, $prepared_by);
$total_reports = getTotalReports($pdo, $search, $from_date, $to_date, $material, $prepared_by);
$total_pages = ceil($total_reports / $per_page);

// Fetch unique filter options
$materials = getUniqueValues($pdo, 'material');
$prepared_bys = getUniqueValues($pdo, 'prepared_by');
$customers = getUniqueValues($pdo, 'customer_name');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Reports - Test Report System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { min-height: 100vh; }
        .sidebar {
            width: 240px;
            background: #23272f;
            color: #fff;
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .sidebar .sidebar-header {
            padding: 1.5rem 1rem 1rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            background: #f59e0b;
            color: #fff;
            letter-spacing: 1px;
        }
        .sidebar .nav-link {
            color: #cbd5e1;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-left: 4px solid transparent;
            transition: background 0.2s, border-color 0.2s;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #1e293b;
            color: #fff;
            border-left: 4px solid #f59e0b;
        }
        .sidebar .sidebar-footer {
            margin-top: auto;
            padding: 1rem 1.5rem;
            font-size: 0.9rem;
            color: #94a3b8;
        }
        .main-content {
            margin-left: 240px;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background: #f59e0b;
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
        }
        .reports-header {
            background: #fff;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .reports-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #f59e0b;
        }
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .action-buttons .btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .table th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            color: #475569;
        }
        .table td {
            vertical-align: middle;
        }
        .footer {
            background: #23272f;
            color: #cbd5e1;
            text-align: center;
            padding: 1rem 0 0.5rem 0;
            font-size: 0.95rem;
            margin-top: auto;
        }
        @media (max-width: 900px) {
            .main-content { margin-left: 0; }
            .sidebar { position: static; width: 100%; min-height: auto; }
            .reports-stats { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-header">Test Report System</div>
        <div class="flex-grow-1">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="reports.php" class="nav-link active"><i class="bi bi-file-earmark-text"></i> All Reports</a>
            <a href="create_report.php" class="nav-link"><i class="bi bi-file-earmark-plus"></i> Create Report</a>
            <a href="ai.php" class="nav-link"><i class="bi bi-robot"></i> Talk to AI</a>
            <a href="includes/export_csv.php" class="nav-link"><i class="bi bi-download"></i> Export CSV</a>
        </div>
        <div class="sidebar-footer">&copy; <?php echo date('Y'); ?> Test Report System</div>
    </nav>
    
    <div class="main-content">
        <div class="topbar">
            <div><i class="bi bi-file-earmark-text"></i> All Reports</div>
            <div>
                <i class="bi bi-person-circle me-2"></i>Hi, Admin
                <a href="?logout=1" class="btn btn-outline-light btn-sm ms-3">Logout</a>
            </div>
        </div>
        
        <div class="container-fluid">
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php if ($_GET['success'] === 'deleted'): ?>
                        Report deleted successfully!
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php if ($_GET['error'] === 'delete_failed'): ?>
                        Failed to delete report. Please try again.
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Reports Header with Stats -->
            <div class="reports-header">
                <div class="reports-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_reports; ?></div>
                        <div class="stat-label">Total Reports</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($materials); ?></div>
                        <div class="stat-label">Materials</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($customers); ?></div>
                        <div class="stat-label">Customers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($prepared_bys); ?></div>
                        <div class="stat-label">Prepared By</div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Test Reports</h4>
                    <a href="create_report.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Create New Report
                    </a>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-12 col-md-4">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Certificate No, Customer, Part">
                                </div>
                            </div>
                            <!-- Date Range -->
                            <div class="col-12 col-md-2">
                                <label class="form-label">From Date</label>
                                <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>">
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label">To Date</label>
                                <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>">
                            </div>
                            <!-- Material -->
                            <div class="col-12 col-md-2">
                                <label class="form-label">Material</label>
                                <select name="material" class="form-select">
                                    <option value="">All Materials</option>
                                    <?php foreach ($materials as $mat): ?>
                                        <option value="<?php echo htmlspecialchars($mat); ?>" <?php echo $material === $mat ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Prepared By -->
                            <div class="col-12 col-md-2">
                                <label class="form-label">Prepared By</label>
                                <select name="prepared_by" class="form-select">
                                    <option value="">All Users</option>
                                    <?php foreach ($prepared_bys as $prep): ?>
                                        <option value="<?php echo htmlspecialchars($prep); ?>" <?php echo $prepared_by === $prep ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($prep); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i>Apply Filters
                            </button>
                            <a href="reports.php" class="btn btn-secondary">
                                <i class="bi bi-x-lg me-1"></i>Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Export Button -->
            <div class="mb-4">
                <form action="includes/export_csv.php" method="GET">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                    <input type="hidden" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
                    <input type="hidden" name="material" value="<?php echo htmlspecialchars($material); ?>">
                    <input type="hidden" name="prepared_by" value="<?php echo htmlspecialchars($prepared_by); ?>">
                    <button type="submit" class="btn btn-secondary">
                        <i class="bi bi-download me-1"></i>Export to CSV
                    </button>
                </form>
            </div>

            <!-- Reports Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Certificate No</th>
                                    <th>Date</th>
                                    <th>Customer Name</th>
                                    <th>Part Name</th>
                                    <th>Material</th>
                                    <th>Prepared By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reports)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                                <h5>No reports found</h5>
                                                <p>Try adjusting your search criteria or create a new report.</p>
                                                <a href="create_report.php" class="btn btn-primary">
                                                    <i class="bi bi-plus-lg me-1"></i>Create First Report
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td>
                                                <a href="view_report.php?id=<?php echo $report['id']; ?>" class="text-decoration-none">
                                                    <strong class="text-primary"><?php echo htmlspecialchars($report['certificate_no']); ?></strong>
                                                    <i class="bi bi-arrow-right-circle ms-1 text-muted"></i>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($report['date']); ?></td>
                                            <td><?php echo htmlspecialchars($report['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($report['part_name']); ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo htmlspecialchars($report['material']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($report['prepared_by']); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view_report.php?id=<?php echo $report['id']; ?>" 
                                                       class="btn btn-outline-info" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="create_report.php?step=1&edit=<?php echo $report['id']; ?>" 
                                                       class="btn btn-outline-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $report['id']; ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this report?')" 
                                                       title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                    <a href="includes/pdf_generator.php?generate=<?php echo $report['id']; ?>" 
                                                       class="btn btn-outline-primary" title="Generate PDF">
                                                        <i class="bi bi-file-pdf"></i>
                                                    </a>
                                                    <?php if ($report['file_path']): ?>
                                                        <a href="includes/pdf_generator.php?preview=<?php echo $report['id']; ?>" 
                                                           class="btn btn-outline-secondary" target="_blank" title="Preview PDF">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="<?php echo htmlspecialchars($report['file_path']); ?>" 
                                                           class="btn btn-outline-success" download title="Download PDF">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                        <a href="https://wa.me/?text=Test%20Report%20<?php echo urlencode($report['certificate_no']); ?>%20-%20View%20at%20<?php echo urlencode('http://yourdomain.com/' . $report['file_path']); ?>" 
                                                           class="btn btn-outline-info" target="_blank" title="Share on WhatsApp">
                                                            <i class="bi bi-whatsapp"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="reports.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&material=<?php echo urlencode($material); ?>&prepared_by=<?php echo urlencode($prepared_by); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                <a class="page-link" href="reports.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&material=<?php echo urlencode($material); ?>&prepared_by=<?php echo urlencode($prepared_by); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="reports.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&material=<?php echo urlencode($material); ?>&prepared_by=<?php echo urlencode($prepared_by); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
        
        <footer class="footer">
            &copy; <?php echo date('Y'); ?> Developed by Mahesh Pawar. All rights reserved.
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 