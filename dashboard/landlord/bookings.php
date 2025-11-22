<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header("Location: /kenya_rentals/auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$landlord_id = $_SESSION['user_id'];

$success = '';
$error = '';

// Handle booking actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    $action = $_GET['action'];
    
    // Verify the booking belongs to landlord's property
    $check_stmt = $conn->prepare("SELECT p.id FROM bookings b JOIN properties p ON b.property_id = p.id WHERE b.id = ? AND p.landlord_id = ?");
    $check_stmt->execute([$booking_id, $landlord_id]);
    
    if ($check_stmt->fetch()) {
        if ($action === 'approve') {
            $update_stmt = $conn->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
            $update_stmt->execute([$booking_id]);
            $success = "Booking approved successfully.";
        } elseif ($action === 'reject') {
            $update_stmt = $conn->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
            $update_stmt->execute([$booking_id]);
            $success = "Booking rejected successfully.";
        } elseif ($action === 'cancel') {
            $update_stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $update_stmt->execute([$booking_id]);
            $success = "Booking cancelled successfully.";
        }
    } else {
        $error = "Booking not found or access denied.";
    }
}

// Fetch all bookings for landlord's properties
$bookings = $conn->query("
    SELECT b.*, p.title as property_title, p.price_per_day, u.full_name as tenant_name, u.phone as tenant_phone, u.email as tenant_email
    FROM bookings b 
    JOIN properties p ON b.property_id = p.id 
    JOIN users u ON b.tenant_id = u.id 
    WHERE p.landlord_id = $landlord_id 
    ORDER BY b.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include '../../includes/header.php'; ?>

<div class="max-w-7xl mx-auto py-6 px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Booking Management</h1>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Bookings Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Property & Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach($bookings as $booking): 
                        $total_amount = calculateBookingTotal($booking['price_per_day'], $booking['start_date'], $booking['end_date']);
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?= $booking['property_title'] ?></div>
                                <div class="text-sm text-gray-500"><?= $booking['tenant_name'] ?></div>
                                <div class="text-sm text-gray-500"><?= $booking['tenant_email'] ?></div>
                                <div class="text-sm text-gray-500"><?= $booking['tenant_phone'] ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?= date('M j, Y', strtotime($booking['start_date'])) ?>
                            </div>
                            <div class="text-sm text-gray-500">to</div>
                            <div class="text-sm text-gray-900">
                                <?= date('M j, Y', strtotime($booking['end_date'])) ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?= $booking['total_days'] ?> days
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            KSh <?= number_format($total_amount, 2) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= getBookingStatusBadge($booking['status']) ?>">
                                <?= ucfirst($booking['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if ($booking['status'] == 'pending'): ?>
                                <a href="?action=approve&id=<?= $booking['id'] ?>" 
                                   class="text-green-600 hover:text-green-900 mr-3">Approve</a>
                                <a href="?action=reject&id=<?= $booking['id'] ?>" 
                                   class="text-red-600 hover:text-red-900">Reject</a>
                            <?php elseif ($booking['status'] == 'approved'): ?>
                                <a href="?action=cancel&id=<?= $booking['id'] ?>" 
                                   class="text-orange-600 hover:text-orange-900">Cancel</a>
                            <?php else: ?>
                                <span class="text-gray-400">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="text-center py-12">
            <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900">No bookings yet</h3>
            <p class="text-gray-500 mt-2">When tenants book your properties, they'll appear here.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>