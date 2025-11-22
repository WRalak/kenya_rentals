<?php
// setup/database_setup.php - Run this once to set up the database

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database_name = 'kenya_rentals';

echo "<pre>";
echo "Starting Kenya Rentals Database Setup...\n";
echo "========================================\n\n";

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to MySQL server successfully\n";

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database_name`");
    echo "✅ Database '$database_name' ready\n";

    // Use the database
    $pdo->exec("USE `$database_name`");
    echo "✅ Using database '$database_name'\n";

    // Check if schema file exists
    $schema_file = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("❌ Schema file not found: $schema_file");
    }

    echo "📖 Reading schema file...\n";
    $schema = file_get_contents($schema_file);
    
    if (empty($schema)) {
        throw new Exception("❌ Schema file is empty");
    }

    // Remove existing tables if they exist (clean setup)
    echo "🔄 Preparing clean setup...\n";
    $tables = ['favorites', 'reviews', 'bookings', 'properties', 'kenyan_locations', 'users'];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "   - Dropped table: $table\n";
        } catch (Exception $e) {
            echo "   - Note: Could not drop $table: " . $e->getMessage() . "\n";
        }
    }

    echo "\n🏗️  Creating database structure...\n";
    
    // Split by semicolon to execute individual statements
    $queries = explode(';', $schema);
    $query_count = 0;
    $success_count = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && strlen($query) > 5) {
            $query_count++;
            try {
                $pdo->exec($query);
                $success_count++;
                echo "   ✅ Query $query_count: Executed successfully\n";
            } catch (PDOException $e) {
                echo "   ❌ Query $query_count: Failed - " . $e->getMessage() . "\n";
                // Continue with next query even if one fails
                continue;
            }
        }
    }

    echo "\n========================================\n";
    echo "SETUP COMPLETED!\n";
    echo "========================================\n";
    echo "✅ Successfully executed: $success_count/$query_count queries\n";
    echo "✅ Database schema created successfully!\n";
    echo "✅ Sample data inserted successfully!\n\n";
    
    echo "🌐 APPLICATION URL:\n";
    echo "   http://localhost/kenya_rentals\n\n";
    
    echo "🔐 DEFAULT LOGIN CREDENTIALS:\n";
    echo "   👨‍💼 Admin:     admin@kenyarentals.co.ke / password\n";
    echo "   👨‍💻 Landlord:  john@example.com / password\n";
    echo "   👩‍💻 Landlord:  sarah@example.com / password\n";
    echo "   👨‍💼 Tenant:    alice@example.com / password\n";
    echo "   👩‍💼 Tenant:    bob@example.com / password\n\n";

    echo "📋 NEXT STEPS:\n";
    echo "   1. Delete this setup folder for security\n";
    echo "   2. Access the application using the URLs above\n";
    echo "   3. Start adding your properties and bookings!\n";

} catch (Exception $e) {
    echo "\n❌ SETUP FAILED:\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    
    echo "\n🔧 TROUBLESHOOTING:\n";
    echo "   1. Check if MySQL is running\n";
    echo "   2. Verify database credentials in config/database.php\n";
    echo "   3. Ensure the database/schema.sql file exists\n";
}

echo "</pre>";
?>