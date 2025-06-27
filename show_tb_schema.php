<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "test_report_system";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SHOW TABLES");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_row()) {
        $tableName = $row[0];
        echo "<h2>Table: " . htmlspecialchars($tableName) . "</h2>";

        $createTableResult = $conn->query("SHOW CREATE TABLE " . $tableName);
        if ($createTableResult->num_rows > 0) {
            $createTableData = $createTableResult->fetch_assoc();
            echo "<pre>" . htmlspecialchars($createTableData['Create Table']) . "</pre>";
        } else {
            echo "Could not retrieve CREATE TABLE statement.";
        }
    }
} else {
    echo "No tables found in the database.";
}

$conn->close();
?>