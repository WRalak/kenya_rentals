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
$revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings b JOIN properties p ON b.property_id = p.id WHERE p.landlord_id = $landlord_id AND b.status = 'approved'")->fetchColumn();

// Recent bookings
$recent_bookings = $conn->query("
    SELECT b.*, p.title, u.full_name as tenant_name 
    FROM bookings b 
    JOIN properties p ON b.property_id = p.id 
    JOIN users u ON b.tenant_id = u.id 
    WHERE p.landlord_id = $landlord_id 
    ORDER BY b.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include '../../includes/header.php'; ?>

<div class="max-w-7xl mx-auto py-6 px-4">
    <!-- Welcome Section -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Welcome back, <?= $_SESSION['full_name'] ?>! 👋</h1>
        <p class="text-gray-600 mt-2">Manage your properties and bookings from one place</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-building text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Properties</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= $property_count ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-calendar-check text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= $booking_count ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending Requests</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= $pending_bookings ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-money-bill-wave text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-semibold text-gray-900">KSh <?= number_format($revenue, 2) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Recent Bookings -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="properties.php?action=add" 
                   class="w-full bg-primary text-white py-2 px-4 rounded-lg flex items-center justify-center hover:bg-secondary transition duration-300">
                    <i class="fas fa-plus mr-2"></i> Add New Property
                </a>
                <a href="bookings.php" 
                   class="w-full border border-primary text-primary py-2 px-4 rounded-lg flex items-center justify-center hover:bg-primary hover:text-white transition duration-300">
                    <i class="fas fa-calendar-alt mr-2"></i> Manage Bookings
                </a>
                <a href="properties.php" 
                   class="w-full border border-gray-300 text-gray-700 py-2 px-4 rounded-lg flex items-center justify-center hover:bg-gray-50 transition duration-300">
                    <i class="fas fa-edit mr-2"></i> View All Properties
                </a>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Recent Booking Requests</h3>
                <a href="bookings.php" class="text-primary hover:text-secondary text-sm">View all</a>
            </div>
            <div class="space-y-4">
                <?php foreach($recent_bookings as $booking): ?>
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900"><?= $booking['title'] ?></p>
                        <p class="text-sm text-gray-600">by <?= $booking['tenant_name'] ?></p>
                        <p class="text-sm text-gray-500">
                            <?= date('M j, Y', strtotime($booking['start_date'])) ?> - <?= date('M j, Y', strtotime($booking['end_date'])) ?>
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 rounded-full text-xs font-medium 
                            <?= $booking['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                            <?= $booking['status'] == 'approved' ? 'bg-green-100 text-green-800' : '' ?>
                            <?= $booking['status'] == 'rejected' ? 'bg-red-100 text-red-800' : '' ?>">
                            <?= ucfirst($booking['status']) ?>
                        </span>
                        <a href="bookings.php?action=view&id=<?= $booking['id'] ?>" 
                           class="text-primary hover:text-secondary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>