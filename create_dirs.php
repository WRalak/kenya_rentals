<?php
// create_dirs.php - Run this once to create required directories

$base_path = __DIR__;

$directories = [
    '/assets/images/properties/uploads',
    '/assets/images/properties/placeholders',
    '/assets/css',
    '/assets/js'
];

echo "<pre>";
echo "Creating directories for Kenya Rentals...\n";
echo "=========================================\n\n";

foreach ($directories as $dir) {
    $full_path = $base_path . $dir;
    
    if (!file_exists($full_path)) {
        if (mkdir($full_path, 0755, true)) {
            echo "✅ Created: $dir\n";
        } else {
            echo "❌ Failed to create: $dir\n";
            echo "   Please create this directory manually.\n";
        }
    } else {
        echo "✅ Already exists: $dir\n";
    }
}

echo "\n=========================================\n";
echo "Directory creation complete!\n";
echo "You can now upload property images.\n";
echo "</pre>";
?>