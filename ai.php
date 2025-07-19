// AI integreted

<?php
require_once "includes/ai_functions.php";

$query = '';
$ini = 'you are sql query generator. My tables schema as follows: ```Table: hardness_traverse
CREATE TABLE `hardness_traverse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `location` varchar(50) NOT NULL,
  `distance_mm` float NOT NULL,
  `hardness_hv1` float DEFAULT NULL,
  `hardness_hrc` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_report_id` (`report_id`),
  KEY `idx_location` (`location`),
  CONSTRAINT `hardness_traverse_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `test_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
Table: report_parameters
CREATE TABLE `report_parameters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `parameter` varchar(100) NOT NULL,
  `specified` varchar(255) NOT NULL,
  `actual` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_report_id` (`report_id`),
  KEY `idx_parameter` (`parameter`),
  CONSTRAINT `report_parameters_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `test_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
Table: report_parts
CREATE TABLE `report_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `part_no` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_report_id` (`report_id`),
  CONSTRAINT `report_parts_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `test_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
Table: surface_hardness_samples
CREATE TABLE `surface_hardness_samples` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `sample_no` int(11) NOT NULL CHECK (`sample_no` between 1 and 5),
  `surface_hardness_hrc` float NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sample` (`report_id`,`sample_no`),
  KEY `idx_report_id` (`report_id`),
  CONSTRAINT `surface_hardness_samples_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `test_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
Table: test_reports
CREATE TABLE `test_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `certificate_no` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `part_name` varchar(255) NOT NULL,
  `material` varchar(100) NOT NULL,
  `punching_no` varchar(50) DEFAULT NULL,
  `batch_no` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `prepared_by` varchar(100) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_no` (`certificate_no`),
  KEY `idx_certificate_no` (`certificate_no`),
  KEY `idx_date` (`date`),
  KEY `idx_customer_name` (`customer_name`),
  KEY `idx_search` (`certificate_no`,`customer_name`,`part_name`),
  KEY `idx_filters` (`date`,`material`,`prepared_by`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci.``` give me sql query for this question: ';

$apiKey = "AIzaSyCs1hbR-pnEKv8GWUb5XIn7MCF0jJZDS7c";
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['query'])) {
    $sqlQuery = generateSqlQuery(trim($_POST['query']), $ini, $apiKey, $apiUrl);
    $sqlQuery = cleanSqlQuery($sqlQuery);
    echo '<div class="ai-response">Generated SQL: '.htmlspecialchars($sqlQuery).'</div>';

    if (strpos(strtolower($sqlQuery), 'error') === false) {
        $results = executeSqlQuery($pdo, $sqlQuery);
        displayResults($results, trim($_POST['query']), $sqlQuery, $apiKey, $apiUrl);  //  Pass more context to displayResults
    } else {
        echo "<p>Could not generate a valid SQL query.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Talk to AI - Test Report System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Talk to AI</h1>
        <a href="index.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

        <!-- Query Form -->
        <form method="POST" id="aiForm" class="mb-5">
            <div class="mb-3">
                <label for="query" class="form-label">Ask a question about test reports</label>
                <input type="text" name="query" id="query" class="form-control" 
                       value="<?php echo htmlspecialchars($query); ?>" 
                       placeholder="e.g., give me total number of test reports" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit Query</button>
        </form>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>
</html>
