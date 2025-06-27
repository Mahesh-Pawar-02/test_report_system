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

// Get report ID from URL
$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$report_id) {
    header('Location: reports.php?error=invalid_report');
    exit;
}

// Get report details
$report = getReportDetails($pdo, $report_id);

if (!$report) {
    header('Location: reports.php?error=report_not_found');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details - <?php echo htmlspecialchars($report['certificate_no']); ?></title>
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
        .report-header {
            background: #fff;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .certificate-badge {
            background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            display: inline-block;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .info-card {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .info-card-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #475569;
        }
        .info-card-body {
            padding: 1.5rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 500;
            color: #64748b;
        }
        .info-value {
            font-weight: 600;
            color: #1e293b;
        }
        .table th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            color: #475569;
        }
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
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
            .info-grid { grid-template-columns: 1fr; }
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
            <div><i class="bi bi-file-earmark-text"></i> Report Details</div>
            <div>
                <i class="bi bi-person-circle me-2"></i>Hi, Admin
                <a href="?logout=1" class="btn btn-outline-light btn-sm ms-3">Logout</a>
            </div>
        </div>
        
        <div class="container-fluid">
            <!-- Report Header -->
            <div class="report-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="mb-3">Test Report Details</h2>
                        <div class="certificate-badge">
                            Certificate No: <?php echo htmlspecialchars($report['certificate_no']); ?>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <a href="create_report.php?step=1&edit=<?php echo $report['id']; ?>" 
                           class="btn btn-warning">
                            <i class="bi bi-pencil me-1"></i>Edit Report
                        </a>
                        <a href="includes/pdf_generator.php?generate=<?php echo $report['id']; ?>" 
                           class="btn btn-primary">
                            <i class="bi bi-file-pdf me-1"></i>Generate PDF
                        </a>
                        <?php if ($report['file_path']): ?>
                            <a href="includes/pdf_generator.php?preview=<?php echo $report['id']; ?>" 
                               class="btn btn-secondary" target="_blank">
                                <i class="bi bi-eye me-1"></i>Preview PDF
                            </a>
                            <a href="<?php echo htmlspecialchars($report['file_path']); ?>" 
                               class="btn btn-success" download>
                                <i class="bi bi-download me-1"></i>Download PDF
                            </a>
                        <?php endif; ?>
                        <a href="reports.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Reports
                        </a>
                    </div>
                </div>
            </div>

            <!-- Report Information Grid -->
            <div class="info-grid">
                <!-- Basic Information -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="bi bi-info-circle me-2"></i>Basic Information
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="info-label">Certificate Number:</span>
                            <span class="info-value"><?php echo htmlspecialchars($report['certificate_no']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date:</span>
                            <span class="info-value"><?php echo htmlspecialchars($report['date']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Customer Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($report['customer_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Part Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($report['part_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Material:</span>
                            <span class="info-value">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($report['material']); ?></span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Prepared By:</span>
                            <span class="info-value"><?php echo htmlspecialchars($report['prepared_by']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Additional Details -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="bi bi-card-text me-2"></i>Additional Details
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="info-label">Punching Number:</span>
                            <span class="info-value">
                                <?php echo $report['punching_no'] ? htmlspecialchars($report['punching_no']) : '<span class="text-muted">Not specified</span>'; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Batch Number:</span>
                            <span class="info-value">
                                <?php echo $report['batch_no'] ? htmlspecialchars($report['batch_no']) : '<span class="text-muted">Not specified</span>'; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Created At:</span>
                            <span class="info-value"><?php echo htmlspecialchars($report['created_at']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Remarks:</span>
                            <span class="info-value">
                                <?php echo $report['remarks'] ? htmlspecialchars($report['remarks']) : '<span class="text-muted">No remarks</span>'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Parts -->
            <?php if (!empty($report['parts'])): ?>
            <div class="info-card mb-4">
                <div class="info-card-header">
                    <i class="bi bi-gear me-2"></i>Report Parts
                </div>
                <div class="info-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Part Number</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['parts'] as $part): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($part['part_no']); ?></td>
                                        <td><?php echo htmlspecialchars($part['quantity']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Report Parameters -->
            <?php if (!empty($report['parameters'])): ?>
            <div class="info-card mb-4">
                <div class="info-card-header">
                    <i class="bi bi-list-check me-2"></i>Test Parameters
                </div>
                <div class="info-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Specified</th>
                                    <th>Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['parameters'] as $param): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($param['parameter']); ?></td>
                                        <td><?php echo htmlspecialchars($param['specified']); ?></td>
                                        <td><?php echo htmlspecialchars($param['actual']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Hardness Traverse -->
            <?php if (!empty($report['traverse'])): ?>
            <div class="info-card mb-4">
                <div class="info-card-header">
                    <i class="bi bi-graph-up me-2"></i>Hardness Traverse
                </div>
                <div class="info-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Distance (mm)</th>
                                    <th>Hardness HV1</th>
                                    <th>Hardness HRC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['traverse'] as $entry): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($entry['location']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['distance_mm']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['hardness_hv1']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['hardness_hrc']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Surface Hardness Samples -->
            <?php if (!empty($report['samples'])): ?>
            <div class="info-card mb-4">
                <div class="info-card-header">
                    <i class="bi bi-droplet me-2"></i>Surface Hardness Samples
                </div>
                <div class="info-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Sample No</th>
                                    <th>Surface Hardness HRC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['samples'] as $sample): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sample['sample_no']); ?></td>
                                        <td><?php echo htmlspecialchars($sample['surface_hardness_hrc']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <footer class="footer">
            &copy; <?php echo date('Y'); ?> Developed by Mahesh Pawar. All rights reserved.
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 