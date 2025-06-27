<?php
// Common functions for Test Report Management System

// Sync in-memory data with database
function syncMemory($pdo) {
    $_SESSION['reports'] = [];
    $stmt = $pdo->query("SELECT * FROM test_reports");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['reports'][$row['id']] = $row;
    }
}

// Validate and sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Create or update a report
function saveReport($pdo, $data, $parts, $parameters, $traverse, $samples, $report_id = null) {
    try {
        $pdo->beginTransaction();

        // Save or update main report
        if ($report_id) {
            $stmt = $pdo->prepare("UPDATE test_reports SET certificate_no = ?, date = ?, customer_name = ?, part_name = ?, material = ?, punching_no = ?, batch_no = ?, remarks = ?, prepared_by = ?, additional_note = ? WHERE id = ?");
            $stmt->execute([
                $data['certificate_no'],
                $data['date'],
                $data['customer_name'],
                $data['part_name'],
                $data['material'],
                $data['punching_no'] ?? null,
                $data['batch_no'] ?? null,
                $data['remarks'] ?? null,
                $data['prepared_by'],
                $data['additional_note'] ?? null,
                $report_id
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO test_reports (certificate_no, date, customer_name, part_name, material, punching_no, batch_no, remarks, prepared_by, additional_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['certificate_no'],
                $data['date'],
                $data['customer_name'],
                $data['part_name'],
                $data['material'],
                $data['punching_no'] ?? null,
                $data['batch_no'] ?? null,
                $data['remarks'] ?? null,
                $data['prepared_by'],
                $data['additional_note'] ?? null
            ]);
            $report_id = $pdo->lastInsertId();
        }

        // Delete existing related data
        $pdo->prepare("DELETE FROM report_parts WHERE report_id = ?")->execute([$report_id]);
        $pdo->prepare("DELETE FROM report_parameters WHERE report_id = ?")->execute([$report_id]);
        $pdo->prepare("DELETE FROM hardness_traverse WHERE report_id = ?")->execute([$report_id]);
        $pdo->prepare("DELETE FROM surface_hardness_samples WHERE report_id = ?")->execute([$report_id]);

        // Save parts
        if (!empty($parts)) {
            $stmt = $pdo->prepare("INSERT INTO report_parts (report_id, part_no, quantity) VALUES (?, ?, ?)");
            foreach ($parts as $part) {
                if (!empty($part['part_no'])) {
                    $stmt->execute([$report_id, $part['part_no'], $part['quantity'] ?? null]);
                }
            }
        }

        // Save parameters
        if (!empty($parameters)) {
            $stmt = $pdo->prepare("INSERT INTO report_parameters (report_id, parameter, specified, actual) VALUES (?, ?, ?, ?)");
            foreach ($parameters as $param) {
                if (!empty($param['parameter'])) {
                    $stmt->execute([$report_id, $param['parameter'], $param['specified'] ?? null, $param['actual'] ?? null]);
                }
            }
        }

        // Save traverse
        if (!empty($traverse)) {
            $stmt = $pdo->prepare("INSERT INTO hardness_traverse (report_id, location, distance_mm, hardness_hv1, hardness_hrc) VALUES (?, ?, ?, ?, ?)");
            foreach ($traverse as $entry) {
                if (!empty($entry['location'])) {
                    $stmt->execute([
                        $report_id,
                        $entry['location'],
                        $entry['distance_mm'] ?? null,
                        $entry['hardness_hv1'] ?? null,
                        $entry['hardness_hrc'] ?? null
                    ]);
                }
            }
        }

        // Save surface hardness samples
        if (!empty($samples)) {
            $stmt = $pdo->prepare("INSERT INTO surface_hardness_samples (report_id, sample_no, surface_hardness_hrc) VALUES (?, ?, ?)");
            foreach ($samples as $index => $hrc) {
                if ($hrc !== '') {
                    $stmt->execute([$report_id, $index + 1, $hrc]);
                }
            }
        }

        $pdo->commit();
        syncMemory($pdo);
        return $report_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Delete a report
function deleteReport($pdo, $report_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM test_reports WHERE id = ?");
        $stmt->execute([$report_id]);
        syncMemory($pdo);
        return true;
    } catch (PDOException $e) {
        throw new Exception("Error deleting report: " . $e->getMessage());
    }
}

// Get report details with related data
function getReportDetails($pdo, $report_id) {
    $report = $pdo->prepare("SELECT * FROM test_reports WHERE id = ?");
    $report->execute([$report_id]);
    $report_data = $report->fetch(PDO::FETCH_ASSOC);

    if (!$report_data) return null;

    $parts = $pdo->prepare("SELECT * FROM report_parts WHERE report_id = ?");
    $parts->execute([$report_id]);
    $report_data['parts'] = $parts->fetchAll(PDO::FETCH_ASSOC);

    $params = $pdo->prepare("SELECT * FROM report_parameters WHERE report_id = ?");
    $params->execute([$report_id]);
    $report_data['parameters'] = $params->fetchAll(PDO::FETCH_ASSOC);

    $traverse = $pdo->prepare("SELECT * FROM hardness_traverse WHERE report_id = ?");
    $traverse->execute([$report_id]);
    $report_data['traverse'] = $traverse->fetchAll(PDO::FETCH_ASSOC);

    $samples = $pdo->prepare("SELECT * FROM surface_hardness_samples WHERE report_id = ? ORDER BY sample_no");
    $samples->execute([$report_id]);
    $report_data['samples'] = $samples->fetchAll(PDO::FETCH_ASSOC);

    return $report_data;
}

// Get reports with pagination, search, and filters
function getReports($pdo, $page, $per_page, $search = '', $from_date = '', $to_date = '', $material = '', $prepared_by = '') {
    $offset = ($page - 1) * $per_page;
    $sql = "SELECT * FROM test_reports WHERE 1=1";
    $params = [];

    // Search
    if ($search) {
        $sql .= " AND (certificate_no LIKE ? OR customer_name LIKE ? OR part_name LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    // Filters
    if ($from_date) {
        $sql .= " AND date >= ?";
        $params[] = $from_date;
    }
    if ($to_date) {
        $sql .= " AND date <= ?";
        $params[] = $to_date;
    }
    if ($material) {
        $sql .= " AND material = ?";
        $params[] = $material;
    }
    if ($prepared_by) {
        $sql .= " AND prepared_by = ?";
        $params[] = $prepared_by;
    }

    // Directly append LIMIT and OFFSET as integers
    $sql .= " ORDER BY date DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get total number of reports for pagination
function getTotalReports($pdo, $search = '', $from_date = '', $to_date = '', $material = '', $prepared_by = '') {
    $sql = "SELECT COUNT(*) FROM test_reports WHERE 1=1";
    $params = [];

    if ($search) {
        $sql .= " AND (certificate_no LIKE ? OR customer_name LIKE ? OR part_name LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if ($from_date) {
        $sql .= " AND date >= ?";
        $params[] = $from_date;
    }
    if ($to_date) {
        $sql .= " AND date <= ?";
        $params[] = $to_date;
    }
    if ($material) {
        $sql .= " AND material = ?";
        $params[] = $material;
    }
    if ($prepared_by) {
        $sql .= " AND prepared_by = ?";
        $params[] = $prepared_by;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// Get unique values for filter dropdowns
function getUniqueValues($pdo, $column) {
    $sql = "SELECT DISTINCT $column FROM test_reports WHERE $column IS NOT NULL ORDER BY $column";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Process AI query


function updateReportFilePath($pdo, $report_id, $file_path) {
    $stmt = $pdo->prepare("UPDATE test_reports SET file_path = ? WHERE id = ?");
    $stmt->execute([$file_path, $report_id]);
    syncMemory($pdo);
}

// AJAX handler for report details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_report_details' && !empty($_POST['report_id'])) {
    $report = getReportDetails($pdo, (int)$_POST['report_id']);
    if ($report) {
        $html = '<h5>General Info</h5>';
        $html .= '<p><strong>Certificate No:</strong> ' . htmlspecialchars($report['certificate_no']) . '</p>';
        $html .= '<p><strong>Date:</strong> ' . $report['date'] . '</p>';
        $html .= '<p><strong>Customer:</strong> ' . htmlspecialchars($report['customer_name']) . '</p>';
        $html .= '<p><strong>Part Name:</strong> ' . htmlspecialchars($report['part_name']) . '</p>';
        $html .= '<p><strong>Material:</strong> ' . htmlspecialchars($report['material']) . '</p>';
        if ($report['punching_no']) {
            $html .= '<p><strong>Punching No:</strong> ' . htmlspecialchars($report['punching_no']) . '</p>';
        }
        if ($report['batch_no']) {
            $html .= '<p><strong>Batch No:</strong> ' . htmlspecialchars($report['batch_no']) . '</p>';
        }
        if ($report['remarks']) {
            $html .= '<p><strong>Remarks:</strong> ' . htmlspecialchars($report['remarks']) . '</p>';
        }
        $html .= '<p><strong>Prepared By:</strong> ' . htmlspecialchars($report['prepared_by']) . '</p>';

        if ($report['parts']) {
            $html .= '<h5>Parts</h5>';
            $html .= '<table class="table table-bordered"><tr><th>Part No</th><th>Quantity</th></tr>';
            foreach ($report['parts'] as $part) {
                $html .= '<tr><td>' . htmlspecialchars($part['part_no']) . '</td><td>' . $part['quantity'] . '</td></tr>';
            }
            $html .= '</table>';
        }

        if ($report['parameters']) {
            $html .= '<h5>Parameters</h5>';
            $html .= '<table class="table table-bordered"><tr><th>Parameter</th><th>Specified</th><th>Actual</th></tr>';
            foreach ($report['parameters'] as $param) {
                $html .= '<tr><td>' . htmlspecialchars($param['parameter']) . '</td><td>' . 
                         htmlspecialchars($param['specified']) . '</td><td>' . 
                         htmlspecialchars($param['actual']) . '</td></tr>';
            }
            $html .= '</table>';
        }

        if ($report['traverse']) {
            $html .= '<h5>Hardness Traverse</h5>';
            $html .= '<table class="table table-bordered"><tr><th>Location</th><th>Distance (mm)</th><th>Hardness (HV1)</th><th>Hardness (HRC)</th></tr>';
            foreach ($report['traverse'] as $entry) {
                $html .= '<tr><td>' . htmlspecialchars($entry['location']) . '</td><td>' . 
                         $entry['distance_mm'] . '</td><td>' . ($entry['hardness_hv1'] ?? '-') . '</td><td>' . 
                         ($entry['hardness_hrc'] ?? '-') . '</td></tr>';
            }
            $html .= '</table>';
        }

        if ($report['samples']) {
            $html .= '<h5>Surface Hardness Samples</h5>';
            $html .= '<table class="table table-bordered"><tr>';
            foreach ($report['samples'] as $sample) {
                $html .= '<th>Sample ' . $sample['sample_no'] . '</th>';
            }
            $html .= '</tr><tr>';
            foreach ($report['samples'] as $sample) {
                $html .= '<td>' . $sample['surface_hardness_hrc'] . ' HRC</td>';
            }
            $html .= '</tr></table>';
        }

        echo $html;
    } else {
        echo '<p>Report not found.</p>';
    }
    exit;
}
?>