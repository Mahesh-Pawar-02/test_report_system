<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// session_start();
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

$edit_report = null;
$is_edit = isset($_GET['edit']) && is_numeric($_GET['edit']);

// Handle Create: Clear session data for new report
if (!$is_edit && !isset($_GET['step'])) {
    unset($_SESSION['form_data']);
}

// Handle Edit: Load report data
if ($is_edit) {
    $edit_report = getReportDetails($pdo, (int)$_GET['edit']);
    if (!$edit_report) {
        $error = 'Report not found.';
    } elseif (!isset($_SESSION['form_data']) || !isset($_GET['step'])) {
        // Initialize session data for editing only if not already set or on first edit step
        $_SESSION['form_data'] = [
            'step1' => array_intersect_key($edit_report, array_flip(['certificate_no', 'date', 'customer_name', 'part_name', 'material', 'punching_no', 'batch_no', 'remarks', 'prepared_by', 'id'])),
            'step2' => ['parts' => $edit_report['parts'] ?: [[]]],
            'step3' => ['parameters' => $edit_report['parameters'] ?: [[]]],
            'step4' => ['traverse' => $edit_report['traverse'] ?: [[]]],
            'step5' => ['samples' => array_column($edit_report['samples'], 'surface_hardness_hrc', 'sample_no') ?: array_fill(1, 5, '')]
        ];
    }
}

// Initialize form data for current step
$form_data = $_SESSION['form_data']['step' . $step] ?? ($is_edit ? [] : [
    'certificate_no' => '',
    'date' => '',
    'customer_name' => '',
    'part_name' => '',
    'material' => '',
    'punching_no' => '',
    'batch_no' => '',
    'remarks' => '',
    'prepared_by' => '',
    'additional_note' => 'Input Shaft Z - 19 BMM11C00560 is cut for micro, qty, part no.',
    'parts' => [[]],
    'parameters' => [[]],
    'traverse' => [[]],
    'samples' => array_fill(0, 5, '')
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['form_data']['step' . $step] = $_POST;
    if ($step < 5) {
        header("Location: create_report.php?step=" . ($step + 1) . ($is_edit ? '&edit=' . $_GET['edit'] : ''));
        exit;
    } else {
        try {
            $data = array_merge(
                $_SESSION['form_data']['step1'] ?? [],
                ['parts' => $_SESSION['form_data']['step2']['parts'] ?? []],
                ['parameters' => $_SESSION['form_data']['step3']['parameters'] ?? []],
                ['traverse' => $_SESSION['form_data']['step4']['traverse'] ?? []],
                ['samples' => $_POST['samples'] ?? array_fill(0, 5, '')],
                ['additional_note' => $_SESSION['form_data']['step1']['additional_note'] ?? '']
            );
            $report_id = !empty($data['id']) ? (int)$data['id'] : null;
            saveReport($pdo, $data, $data['parts'], $data['parameters'], $data['traverse'], $data['samples'], $report_id);
            $success = $report_id ? 'Report updated!' : 'Report created!';
            unset($_SESSION['form_data']);
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'Create'; ?> Test Report - Step <?php echo $step; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-4 mt-sm-5">
        <h1 class="h3"><?php echo $is_edit ? 'Edit' : 'Create'; ?> Test Report</h1>
        <a href="index.php" class="btn btn-outline-secondary btn-sm mb-3" title="Back to Dashboard">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <p>Step <?php echo $step; ?> of 5</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" id="reportForm" class="mb-5">
            <?php if ($step == 1): ?>
                <!-- Step 1: General Info -->
                <h3>General Info</h3>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($form_data['id'] ?? ($edit_report['id'] ?? '')); ?>">
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label>Certificate No</label>
                        <input type="text" name="certificate_no" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['certificate_no']); ?>" required>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['date']); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label>Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['customer_name']); ?>" required>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label>Part Name</label>
                        <input type="text" name="part_name" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['part_name']); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label>Material</label>
                        <input type="text" name="material" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['material']); ?>" required>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label>Prepared By</label>
                        <input type="text" name="prepared_by" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['prepared_by']); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <label>Punching No (Optional)</label>
                        <input type="text" name="punching_no" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['punching_no']); ?>">
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label>Batch No (Optional)</label>
                        <input type="text" name="batch_no" class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['batch_no']); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label>Remarks (Optional)</label>
                    <textarea name="remarks" class="form-control"><?php echo htmlspecialchars($form_data['remarks']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label>Additional Note (Optional)</label>
                    <textarea name="additional_note" class="form-control"><?php echo htmlspecialchars(isset($form_data['additional_note']) ? $form_data['additional_note'] : 'Input Shaft Z - 19 BMM11C00560 is cut for micro, qty, part no.'); ?></textarea>
                </div>
            <?php elseif ($step == 2): ?>
                <!-- Step 2: Parts -->
                <h3>Parts</h3>
                <div class="table-responsive">
                    <table class="table table-bordered" id="partsTable">
                        <thead>
                            <tr>
                                <th>Part No</th>
                                <th>Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $parts = $form_data['parts'] ?? [[]];
                            foreach ($parts as $index => $part):
                            ?>
                                <tr>
                                    <td><input type="text" name="parts[<?php echo $index; ?>][part_no]" class="form-control" 
                                               value="<?php echo htmlspecialchars($part['part_no'] ?? ''); ?>"></td>
                                    <td><input type="number" name="parts[<?php echo $index; ?>][quantity]" class="form-control" 
                                               value="<?php echo $part['quantity'] ?? ''; ?>"></td>
                                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-part" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addPart" title="Add Part">
                    <i class="bi bi-plus"></i> Add Part
                </button>
            <?php elseif ($step == 3): ?>
                <!-- Step 3: Parameters -->
                <h3>Parameters</h3>
                <div class="table-responsive">
                    <table class="table table-bordered" id="parametersTable">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Specified</th>
                                <th>Actual</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $parameters = $form_data['parameters'] ?? [[]];
                            foreach ($parameters as $index => $param):
                            ?>
                                <tr>
                                    <td>
                                        <select name="parameters[<?php echo $index; ?>][parameter]" class="form-control">
                                            <option value="">Select Parameter</option>
                                            <option value="Heat Treatment" <?php echo ($param['parameter'] ?? '') === 'Heat Treatment' ? 'selected' : ''; ?>>Heat Treatment</option>
                                            <option value="Surface Hardness" <?php echo ($param['parameter'] ?? '') === 'Surface Hardness' ? 'selected' : ''; ?>>Surface Hardness</option>
                                            <option value="Core hardness" <?php echo ($param['parameter'] ?? '') === 'Core hardness' ? 'selected' : ''; ?>>Core hardness</option>
                                            <option value="Core hardness @ RCD" <?php echo ($param['parameter'] ?? '') === 'Core hardness @ RCD' ? 'selected' : ''; ?>>Core hardness @ RCD</option>
                                            <option value="Core hardness @ OD" <?php echo ($param['parameter'] ?? '') === 'Core hardness @ OD' ? 'selected' : ''; ?>>Core hardness @ OD</option>
                                            <option value="Case depth" <?php echo ($param['parameter'] ?? '') === 'Case depth' ? 'selected' : ''; ?>>Case depth</option>
                                            <option value="Case depth @ PCD" <?php echo ($param['parameter'] ?? '') === 'Case depth @ PCD' ? 'selected' : ''; ?>>Case depth @ PCD</option>
                                            <option value="Case depth @ OD" <?php echo ($param['parameter'] ?? '') === 'Case depth @ OD' ? 'selected' : ''; ?>>Case depth @ OD</option>
                                            <option value="Case depth @ Spline" <?php echo ($param['parameter'] ?? '') === 'Case depth @ Spline' ? 'selected' : ''; ?>>Case depth @ Spline</option>
                                            <option value="Case depth @ Ground" <?php echo ($param['parameter'] ?? '') === 'Case depth @ Ground' ? 'selected' : ''; ?>>Case depth @ Ground</option>
                                            <option value="Grain Size 100X" <?php echo ($param['parameter'] ?? '') === 'Grain Size 100X' ? 'selected' : ''; ?>>Grain Size 100X</option>
                                            <option value="Grain Size" <?php echo ($param['parameter'] ?? '') === 'Grain Size' ? 'selected' : ''; ?>>Grain Size</option>
                                            <option value="Carbon Surface Content" <?php echo ($param['parameter'] ?? '') === 'Carbon Surface Content' ? 'selected' : ''; ?>>Carbon Surface Content</option>
                                            <option value="Root" <?php echo ($param['parameter'] ?? '') === 'Root' ? 'selected' : ''; ?>>Root</option>
                                            <option value="Thread Hardness" <?php echo ($param['parameter'] ?? '') === 'Thread Hardness' ? 'selected' : ''; ?>>Thread Hardness</option>
                                            <option value="Microstructure" <?php echo ($param['parameter'] ?? '') === 'Microstructure' ? 'selected' : ''; ?>>Microstructure</option>
                                            <option value="Surface Hardness (After Grinding)" <?php echo ($param['parameter'] ?? '') === 'Surface Hardness (After Grinding)' ? 'selected' : ''; ?>>Surface Hardness (After Grinding)</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="parameters[<?php echo $index; ?>][specified]" class="form-control" 
                                               value="<?php echo htmlspecialchars($param['specified'] ?? ''); ?>"></td>
                                    <td><input type="text" name="parameters[<?php echo $index; ?>][actual]" class="form-control" 
                                               value="<?php echo htmlspecialchars($param['actual'] ?? ''); ?>"></td>
                                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-parameter" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addParameter" title="Add Parameter">
                    <i class="bi bi-plus"></i> Add Parameter
                </button>
            <?php elseif ($step == 4): ?>
                <!-- Step 4: Hardness Traverse -->
                <h3>Hardness Traverse</h3>
                <div class="table-responsive">
                    <table class="table table-bordered" id="traverseTable">
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th>Distance (mm)</th>
                                <th>Hardness (HV1)</th>
                                <th>Hardness (HRC)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $traverse = $form_data['traverse'] ?? [[]];
                            foreach ($traverse as $index => $entry):
                            ?>
                                <tr>
                                    <td>
                                        <select name="traverse[<?php echo $index; ?>][location]" class="form-control">
                                            <option value="">Select Location</option>
                                            <option value="At PCD" <?php echo ($entry['location'] ?? '') === 'At PCD' ? 'selected' : ''; ?>>At PCD</option>
                                            <option value="At OD" <?php echo ($entry['location'] ?? '') === 'At OD' ? 'selected' : ''; ?>>At OD</option>
                                            <option value="Root location" <?php echo ($entry['location'] ?? '') === 'Root location' ? 'selected' : ''; ?>>Root location</option>
                                            <option value="Spline root" <?php echo ($entry['location'] ?? '') === 'Spline root' ? 'selected' : ''; ?>>Spline root</option>
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" name="traverse[<?php echo $index; ?>][distance_mm]" class="form-control" 
                                               value="<?php echo htmlspecialchars($entry['distance_mm'] ?? ''); ?>"></td>
                                    <td><input type="number" step="0.1" name="traverse[<?php echo $index; ?>][hardness_hv1]" class="form-control" 
                                               value="<?php echo htmlspecialchars($entry['hardness_hv1'] ?? ''); ?>"></td>
                                    <td><input type="number" step="0.1" name="traverse[<?php echo $index; ?>][hardness_hrc]" class="form-control" 
                                               value="<?php echo htmlspecialchars($entry['hardness_hrc'] ?? ''); ?>"></td>
                                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-traverse" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addTraverse" title="Add Traverse Entry">
                    <i class="bi bi-plus"></i> Add Traverse Entry
                </button>
            <?php elseif ($step == 5): ?>
                <!-- Step 5: Surface Hardness Samples -->
                <h3>Surface Hardness Samples</h3>
                <div class="row">
                    <?php
                    $samples = $form_data['samples'] ?? array_fill(0, 5, '');
                    for ($i = 0; $i < 5; $i++):
                    ?>
                        <div class="col-12 col-md-2 mb-3">
                            <label>Sample <?php echo $i + 1; ?> (HRC)</label>
                            <input type="number" step="0.1" name="samples[<?php echo $i; ?>]" class="form-control" 
                                   value="<?php echo htmlspecialchars($samples[$i] ?? ''); ?>">
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

            <div class="mt-3 d-flex gap-2">
                <?php if ($step > 1): ?>
                    <a href="create_report.php?step=<?php echo $step - 1; ?><?php echo $is_edit ? '&edit=' . $_GET['edit'] : ''; ?>" 
                       class="btn btn-outline-secondary btn-sm" title="Previous Step">
                        <i class="bi bi-arrow-left"></i> Previous
                    </a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi <?php echo $step == 5 ? 'bi-check-circle' : 'bi-arrow-right'; ?>"></i> 
                    <?php echo $step == 5 ? ($is_edit ? 'Update' : 'Create') . ' Report' : 'Next'; ?>
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add Part
            $('#addPart').click(function() {
                let index = $('#partsTable tbody tr').length;
                let row = `<tr>
                    <td><input type="text" name="parts[${index}][part_no]" class="form-control"></td>
                    <td><input type="number" name="parts[${index}][quantity]" class="form-control"></td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-part" title="Remove"><i class="bi bi-trash"></i></button></td>
                </tr>`;
                $('#partsTable tbody').append(row);
            });

            // Remove Part
            $(document).on('click', '.remove-part', function() {
                if ($('#partsTable tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                }
            });

            // Add Parameter
            $('#addParameter').click(function() {
                let index = $('#parametersTable tbody tr').length;
                let row = `<tr>
                    <td>
                        <select name="parameters[${index}][parameter]" class="form-control">
                            <option value="">Select Parameter</option>
                            <option value="Heat Treatment">Heat Treatment</option>
                            <option value="Surface Hardness">Surface Hardness</option>
                            <option value="Core hardness">Core hardness</option>
                            <option value="Core hardness @ RCD">Core hardness @ RCD</option>
                            <option value="Core hardness @ OD">Core hardness @ OD</option>
                            <option value="Case depth">Case depth</option>
                            <option value="Case depth @ PCD">Case depth @ PCD</option>
                            <option value="Case depth @ OD">Case depth @ OD</option>
                            <option value="Case depth @ Spline">Case depth @ Spline</option>
                            <option value="Case depth @ Ground">Case depth @ Ground</option>
                            <option value="Grain Size 100X">Grain Size 100X</option>
                            <option value="Grain Size">Grain Size</option>
                            <option value="Carbon Surface Content">Carbon Surface Content</option>
                            <option value="Root">Root</option>
                            <option value="Thread Hardness">Thread Hardness</option>
                            <option value="Microstructure">Microstructure</option>
                            <option value="Surface Hardness (After Grinding)">Surface Hardness (After Grinding)</option>
                        </select>
                    </td>
                    <td><input type="text" name="parameters[${index}][specified]" class="form-control"></td>
                    <td><input type="text" name="parameters[${index}][actual]" class="form-control"></td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-parameter" title="Remove"><i class="bi bi-trash"></i></button></td>
                </tr>`;
                $('#parametersTable tbody').append(row);
            });

            // Remove Parameter
            $(document).on('click', '.remove-parameter', function() {
                if ($('#parametersTable tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                }
            });

            // Add Traverse
            $('#addTraverse').click(function() {
                let index = $('#traverseTable tbody tr').length;
                let row = `<tr>
                    <td>
                        <select name="traverse[${index}][location]" class="form-control">
                            <option value="">Select Location</option>
                            <option value="At PCD">At PCD</option>
                            <option value="At OD">At OD</option>
                            <option value="Root location">Root location</option>
                            <option value="Spline root">Spline root</option>
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="traverse[${index}][distance_mm]" class="form-control"></td>
                    <td><input type="number" step="0.1" name="traverse[${index}][hardness_hv1]" class="form-control"></td>
                    <td><input type="number" step="0.1" name="traverse[${index}][hardness_hrc]" class="form-control"></td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-traverse" title="Remove"><i class="bi bi-trash"></i></button></td>
                </tr>`;
                $('#traverseTable tbody').append(row);
            });

            // Remove Traverse
            $(document).on('click', '.remove-traverse', function() {
                if ($('#traverseTable tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                }
            });
        });
    </script>
</body>
</html>