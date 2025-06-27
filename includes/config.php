<?php
// Database and session configuration
session_start();

// Database credentials
$host = 'localhost';
$db = 'test_report_system';
$user = 'root'; // Update with your MySQL username
$pass = '';     // Update with your MySQL password

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize in-memory storage
if (!isset($_SESSION['reports'])) {
    $_SESSION['reports'] = [];
    $stmt = $pdo->query("SELECT * FROM test_reports");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['reports'][$row['id']] = $row;
    }
}
?>