<?php
require_once 'config.php';
require_once 'functions.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$material = isset($_GET['material']) ? $_GET['material'] : '';
$prepared_by = isset($_GET['prepared_by']) ? $_GET['prepared_by'] : '';

$reports = getReports($pdo, 1, 10000, $search, $from_date, $to_date, $material, $prepared_by); // Large limit

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="reports.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Certificate No', 'Date', 'Customer Name', 'Part Name', 'Material', 'Prepared By']);

foreach ($reports as $report) {
    fputcsv($output, [
        $report['certificate_no'],
        $report['date'],
        $report['customer_name'],
        $report['part_name'],
        $report['material'],
        $report['prepared_by']
    ]);
}

fclose($output);
exit;