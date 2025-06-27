<?php
// Start output buffering to prevent premature output
ob_start();

require_once 'config.php';
require_once 'functions.php';
require_once '../vendor/autoload.php';

$is_preview = isset($_GET['preview']) && is_numeric($_GET['preview']);
$is_generate = isset($_GET['generate']) && is_numeric($_GET['generate']);

if (!$is_preview && !$is_generate) {
    ob_end_clean();
    die('Invalid request.');
}

$report_id = $is_preview ? (int)$_GET['preview'] : (int)$_GET['generate'];
$report = getReportDetails($pdo, $report_id);
if (!$report) {
    ob_end_clean();
    die('Report not found.');
}

// Ensure $report contains the expected structure (example mapping based on uploaded document)
$report = array_merge([
    'certificate_no' => $report['certificate_no'],
    'customer_name' => 'S R Auto Parts',
    'part_no' => 'BMM11C00560',
    'part_name' => 'Input Shaft Z-19',
    'date' => '20.01.2025',
    'material' => 'SAE 8620',
    'id_no' => '-',
    'quantity' => '86 Nos',
    'parameters' => [
        ['parameter' => 'Heat Treatment', 'specified' => 'CHT', 'actual' => 'CHT'],
        ['parameter' => 'Surface Hardness', 'specified' => '58-62 HRC', 'actual' => '61-62 HRC'],
        ['parameter' => 'Core hardness', 'specified' => '30-39 HRC', 'actual' => '37 HRC'],
        ['parameter' => 'Case depth @ PCD', 'specified' => '0.70-1.00 mm @ 513 Hv1', 'actual' => '0.83 mm'],
        ['parameter' => 'Microstructure', 'specified' => 'Case: Tempered Martensite, RA<10%, Carbides in any form are not acceptable.<br>Core: Low Carbon Tempered Martensite.', 'actual' => 'Case: Fine Tempered martensite. RA <05%.<br>Core: Low carbon tempered Martensite.']
    ],
    'traverse' => [
        ['distance_mm' => '0.10', 'hardness_hv1' => '761'],
        ['distance_mm' => '0.20', 'hardness_hv1' => '740'],
        ['distance_mm' => '0.30', 'hardness_hv1' => '718'],
        ['distance_mm' => '0.40', 'hardness_hv1' => '678'],
        ['distance_mm' => '0.50', 'hardness_hv1' => '632'],
        ['distance_mm' => '0.70', 'hardness_hv1' => '554'],
        ['distance_mm' => '0.80', 'hardness_hv1' => '536'],
        ['distance_mm' => '0.90', 'hardness_hv1' => '476'],
        ['distance_mm' => '1.00', 'hardness_hv1' => '462']
    ],
    'samples' => [
        ['sample_no' => '1', 'surface_hardness_hrc' => '61'],
        ['sample_no' => '2', 'surface_hardness_hrc' => '62'],
        ['sample_no' => '3', 'surface_hardness_hrc' => '61'],
        ['sample_no' => '4', 'surface_hardness_hrc' => '62'],
        ['sample_no' => '5', 'surface_hardness_hrc' => '61']
    ],
    'remarks' => 'Accepted.',
    'additional_note' => 'Input Shaft Z - 19 BMM11C00560 is cut for micro.',
    'prepared_by' => 'Mahesh Pawar'
], $report); // Merge with actual database data

// Delete existing PDF if generating (not previewing)
if ($is_generate && $report['file_path'] && file_exists(dirname(__DIR__) . '/' . $report['file_path'])) {
    unlink(dirname(__DIR__) . '/' . $report['file_path']);
}

// Create TCPDF instance
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Test Report System');
$pdf->SetAuthor('JYOTI HEAT TREATMENT PVT LTD.');
$pdf->SetTitle('Test Certificate - ' . $report['certificate_no']);
$pdf->SetMargins(15,15);
$pdf->SetAutoPageBreak(true, 10);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set font to dejavusans
$pdf->SetFont('dejavusans', '', 10); // Reduced font size to fit content

// Add page
$pdf->AddPage();

// Logo (new logo on left side)
$logo = 'C:\xampp\htdocs\project\includes\jyoti_logo.jpg'; // Use the uploaded logo
if (file_exists($logo)) {
    $pdf->Image($logo, 17, 17, 20, 0, 'jpg'); // Positioned at top-left
}

// Header with box and three rows per side
$header_html = '
<table border="1" width="100%" style="font-size: 11px; border-collapse: collapse;">
    <tr>
        <td width="72%" align="center" style="vertical-align: top; padding: 5px;">
            <table border="0" width="100%">
                <tr><td align="center" style="font-size: 14px;" ><b>JYOTI HEAT TREATMENT PVT LTD.</b></td></tr>
                <tr><td></td></tr>
                <tr><td align="center">Plot No.: J-199 , M.I.D.C, Bhosari , Pune-26,</td></tr>
                <tr><td align="center">Email: sales@jyotiht.com/quality@jyotiht.com</td></tr>
                <tr><td align="center">Mobile No.: 8888240351 / 8888140351</td></tr>
            </table>
        </td>
        <td width="28%" align="right" style="vertical-align: top; padding: 5px;">
            <table border="0" width="100%">
                <tr><td align="right">Doc No: JHTPL/QA/F-04</td></tr>
                <tr><td align="right">Rev No: 00/--</td></tr>
                <tr><td align="right">Rev Date: 01/01/2023</td></tr>
            </table>
        </td>
    </tr>
</table>';
$pdf->writeHTML($header_html, true, false, true, false, '');
$pdf->Ln(-3);

// Title
$pdf->SetFont('dejavusans', 'B', 14);
$pdf->Cell(0, 10, 'TEST CERTIFICATE', 0, 1, 'C');
$pdf->Ln(1);

// General Info Table
$part_nos = '-';
$quantities = '-';
if (!empty($report['parts'])) {
    $part_nos_arr = array();
    $quantities_arr = array();
    foreach ($report['parts'] as $part) {
        if (!empty($part['part_no'])) {
            $part_nos_arr[] = htmlspecialchars($part['part_no']);
            $quantities_arr[] = htmlspecialchars($part['quantity']);
        }
    }
    if ($part_nos_arr) {
        $part_nos = implode(', ', $part_nos_arr);
        $quantities = implode(', ', $quantities_arr);
    }
}
$pdf->SetFont('dejavusans', '', 10); // Set font size to 12 for the first table
$general_info = '
<table border="1" cellpadding="3" style="border-collapse: collapse; width: 100%;">
    <tr>
        <td width="25%"><b>Test certificate no:</b></td><td width="25%">' . htmlspecialchars($report['certificate_no']) . '</td>
        <td width="25%"><b>Customer Name:</b></td><td width="25%">' . htmlspecialchars($report['customer_name']) . '</td>
    </tr>
    <tr>
        <td><b>Part no.:</b></td><td>' . $part_nos . '</td>
        <td><b>Part name:</b></td><td>' . htmlspecialchars($report['part_name']) . '</td>
    </tr>
    <tr>
        <td><b>Date:</b></td><td>' . htmlspecialchars($report['date']) . '</td>
        <td><b>Material:</b></td><td>' . htmlspecialchars($report['material']) . '</td>
    </tr>
    <tr>
        <td><b>ID No.:</b></td><td>' . (isset($report['id_no']) ? htmlspecialchars($report['id_no']) : '-') . '</td>
        <td><b>Qty.:</b></td><td>' . $quantities . '</td>
    </tr>
</table>';
$pdf->writeHTML($general_info, true, false, true, false, '');
$pdf->Ln(-3);
$pdf->SetFont('dejavusans', '', 10); // Reset font size for the rest of the PDF

// Parameters Table
if (!empty($report['parameters'])) {
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(0, 6, 'Parameters', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 11);
    $html = '<table border="1" cellpadding="3" style="border-collapse: collapse; width: 100%;">
        <tr>
            <th width="30%"><b>Parameter</b></th>
            <th width="35%"><b>Specified</b></th>
            <th width="35%"><b>Actual</b></th>
        </tr>';
    foreach ($report['parameters'] as $param) {
        $html .= '
        <tr>
            <td width="30%">' . htmlspecialchars($param['parameter']) . '</td>
            <td width="35%">' . htmlspecialchars($param['specified']) . '</td>
            <td width="35%">' . htmlspecialchars($param['actual']) . '</td>
        </tr>';
    }
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(-3);
}

// Hardness Traverse Table
if (!empty($report['traverse'])) {
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(0, 6, 'Hardness Traverse:', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    $html = '<table border="1" cellpadding="3" style="border-collapse: collapse; width: 100%;">
        <tr>
            <th width="25%"><b>Distance(mm)</b></th>
            <th width="25%"><b>Hardness (HV1)</b></th>
            <th width="25%"><b>Distance(mm)</b></th>
            <th width="25%"><b>Hardness (HV1)</b></th>
        </tr>';
    $traverse = $report['traverse'];
    $half = ceil(count($traverse) / 2);
    for ($i = 0; $i < $half; $i++) {
        $left = isset($traverse[$i]) ? $traverse[$i] : ['distance_mm' => '', 'hardness_hv1' => ''];
        $right = isset($traverse[$i + $half]) ? $traverse[$i + $half] : ['distance_mm' => '', 'hardness_hv1' => ''];
        $html .= '
        <tr>
            <td width="25%">' . ($left['distance_mm'] ? $left['distance_mm'] : '-') . '</td>
            <td width="25%">' . ($left['hardness_hv1'] ? $left['hardness_hv1'] : '-') . '</td>
            <td width="25%">' . ($right['distance_mm'] ? $right['distance_mm'] : '-') . '</td>
            <td width="25%">' . ($right['hardness_hv1'] ? $right['hardness_hv1'] : '-') . '</td>
        </tr>';
    }
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(-3);
}

// Surface Hardness Samples
if (!empty($report['samples'])) {
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(0, 6, 'Surface Hardness of 05 Samples:', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    $html = '<table border="1" cellpadding="3" style="border-collapse: collapse; width: 100%;">
        <tr>
            <th width="30%"><b>Sample Nos.</b></th>';
    foreach ($report['samples'] as $sample) {
        $html .= '<th width="' . (70 / count($report['samples'])) . '%"><b>' . htmlspecialchars($sample['sample_no']) . '</b></th>';
    }
    $html .= '</tr><tr>
            <td width="30%"><b>Surface Hardness in HRC</b></td>';
    foreach ($report['samples'] as $sample) {
        $html .= '<td width="' . (70 / count($report['samples'])) . '%">' . ($sample['surface_hardness_hrc'] ? htmlspecialchars($sample['surface_hardness_hrc']) : '-') . '</td>';
    }
    $html .= '</tr></table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(-5);
}

// Remarks and Prepared By
if (!empty($report['remarks'])) {
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 6, 'Remarks: ' . htmlspecialchars($report['remarks']), 0, 1, 'L');
}
if (!empty($report['additional_note'])) {
    $pdf->Cell(0, 6, htmlspecialchars($report['additional_note']), 0, 1, 'L');
}
if (!empty($report['prepared_by'])) {
    $pdf->Ln(15);
    $pdf->Cell(0, 6, 'Prepared By: ' . htmlspecialchars($report['prepared_by']), 0, 1, 'L');
}

// Output PDF
if ($is_preview) {
    $pdf->Output('report_' . $report['certificate_no'] . '.pdf', 'I');
} else {
    $filename = 'report_' . str_replace('/', '_', $report['certificate_no']) . '_' . time() . '.pdf';
    $upload_dir = dirname(__DIR__) . '/Uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $filepath = $upload_dir . $filename;
    $pdf->Output($filepath, 'F');
    updateReportFilePath($pdo, $report_id, 'Uploads/' . $filename);
    header('Location: ../index.php');
    exit;
}
?>