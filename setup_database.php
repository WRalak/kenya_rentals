<?php
// setup_database.php - Run this once to set up the database

require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    // Read and execute the SQL schema
    $sql = file_get_contents('database/schema.sql');
    $conn->exec($sql);
    
    echo "Database setup completed successfully!";
} catch (PDOException $e) {
    echo "Database setup failed: " . $e->getMessage();
}
?>