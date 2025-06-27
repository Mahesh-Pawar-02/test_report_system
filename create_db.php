<?php
// Database configuration
$host = 'localhost';
$user = 'root'; // Update with your MySQL username
$pass = '';     // Update with your MySQL password
$db_name = 'test_report_system';

// Step 1: Connect to MySQL server (without specifying a database)
try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to MySQL server successfully.<br>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Step 2: Create the database
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name");
    echo "Database '$db_name' created successfully or already exists.<br>";
} catch (PDOException $e) {
    die("Error creating database: " . $e->getMessage());
}

// Step 3: Connect to the new database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database '$db_name' successfully.<br>";
} catch (PDOException $e) {
    die("Error connecting to database: " . $e->getMessage());
}

// Step 4: Create tables
try {
    // Table 1: test_reports
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS test_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            certificate_no VARCHAR(50) NOT NULL UNIQUE,
            date DATE NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            part_name VARCHAR(255) NOT NULL,
            material VARCHAR(100) NOT NULL,
            punching_no VARCHAR(50) DEFAULT NULL,
            batch_no VARCHAR(50) DEFAULT NULL,
            remarks TEXT DEFAULT NULL,
            prepared_by VARCHAR(100) NOT NULL,
            file_path VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_certificate_no (certificate_no),
            INDEX idx_date (date),
            INDEX idx_customer_name (customer_name)
        )
    ");
    echo "Table 'test_reports' created successfully.<br>";

    // Table 2: report_parts
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS report_parts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            part_no VARCHAR(100) NOT NULL,
            quantity INT NOT NULL,
            FOREIGN KEY (report_id) REFERENCES test_reports(id) ON DELETE CASCADE,
            INDEX idx_report_id (report_id)
        )
    ");
    echo "Table 'report_parts' created successfully.<br>";

    // Table 3: report_parameters
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS report_parameters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            parameter VARCHAR(100) NOT NULL,
            specified VARCHAR(255) NOT NULL,
            actual VARCHAR(255) NOT NULL,
            FOREIGN KEY (report_id) REFERENCES test_reports(id) ON DELETE CASCADE,
            INDEX idx_report_id (report_id),
            INDEX idx_parameter (parameter)
        )
    ");
    echo "Table 'report_parameters' created successfully.<br>";

    // Table 4: hardness_traverse
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hardness_traverse (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            location VARCHAR(50) NOT NULL,
            distance_mm FLOAT NOT NULL,
            hardness_hv1 FLOAT DEFAULT NULL,
            hardness_hrc FLOAT DEFAULT NULL,
            FOREIGN KEY (report_id) REFERENCES test_reports(id) ON DELETE CASCADE,
            INDEX idx_report_id (report_id),
            INDEX idx_location (location)
        )
    ");
    echo "Table 'hardness_traverse' created successfully.<br>";

    // Table 5: surface_hardness_samples
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS surface_hardness_samples (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            sample_no INT NOT NULL CHECK (sample_no BETWEEN 1 AND 5),
            surface_hardness_hrc FLOAT NOT NULL,
            FOREIGN KEY (report_id) REFERENCES test_reports(id) ON DELETE CASCADE,
            INDEX idx_report_id (report_id),
            UNIQUE KEY unique_sample (report_id, sample_no)
        )
    ");
    echo "Table 'surface_hardness_samples' created successfully.<br>";

    // Table 6: users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL
        )
    ");
    echo "Table 'users' created successfully.<br>";

} catch (PDOException $e) {
    die("Error creating tables: " . $e->getMessage());
}

// Step 5: Close connection
$pdo = null;
echo "Database setup completed successfully!";
?>