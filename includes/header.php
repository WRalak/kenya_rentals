<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base path for the application
$base_url = '/kenya_rentals';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KenyaRentals - Find Your Perfect Space</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= $base_url ?>/assets/css/style.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#1E40AF',
                        accent: '#10B981'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-building text-primary text-2xl"></i>
                    <a href="<?= $base_url ?>/" class="text-xl font-bold text-gray-800">KenyaRentals</a>
                </div>
                
                <div class="flex items-center space-x-6">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?= $base_url ?>/dashboard/<?= $_SESSION['user_type'] ?>/" 
                           class="text-gray-600 hover:text-primary transition duration-300">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <span class="text-gray-400">|</span>
                        <span class="text-gray-600">Welcome, <?= $_SESSION['full_name'] ?></span>
                        <a href="<?= $base_url ?>/auth/logout.php" 
                           class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    <?php else: ?>
                        <a href="<?= $base_url ?>/auth/login.php" class="text-gray-600 hover:text-primary transition duration-300">
                            Login
                        </a>
                        <a href="<?= $base_url ?>/auth/register.php" 
                           class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                            Sign Up
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main>