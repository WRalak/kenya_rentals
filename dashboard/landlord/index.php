<?php
require_once '../../config/database.php';

// Start session and check if user is landlord
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header("Location: /kenya_rentals/auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get landlord stats
$landlord_id = $_SESSION['user_id'];
$property_count = $conn->query("SELECT COUNT(*) FROM properties WHERE landlord_id = $landlord_id")->fetchColumn();
$booking_count = $conn->query("SELECT COUNT(*) FROM bookings b JOIN properties p ON b.property_id = p.id WHERE p.landlord_id = $landlord_id")->fetchColumn();
$pending_bookings = $conn->query("SELECT COUNT(*) FROM bookings b JOIN properties p ON b.property_id = p.id WHERE p.landlord_id = $landlord_id AND b.status = 'pending'")->fetchColumn();

?>

<?php include '../../includes/header.php'; ?>

<div class="max-w-7xl mx-auto py-6 px-4">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Welcome back, <?= $_SESSION['full_name'] ?>! 👋</h1>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <i class="fas fa-building text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-2">Properties</h3>
                    <p class="text-3xl font-bold text-primary"><?= $property_count ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <i class="fas fa-calendar-check text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-2">Total Bookings</h3>
                    <p class="text-3xl font-bold text-primary"><?= $booking_count ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-2">Pending Requests</h3>
                    <p class="text-3xl font-bold text-primary"><?= $pending_bookings ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <h3 class="text-xl font-semibold mb-4">Quick Actions</h3>
        <div class="flex space-x-4">
            <a href="properties.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition duration-300">
                <i class="fas fa-plus mr-2"></i>Add Property
            </a>
            <a href="bookings.php" class="bg-gray-200 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-300 transition duration-300">
                <i class="fas fa-calendar-alt mr-2"></i>View Bookings
            </a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>