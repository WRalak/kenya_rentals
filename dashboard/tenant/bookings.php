<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header("Location: /kenya_rentals/auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$tenant_id = $_SESSION['user_id'];

$success = '';
$error = '';

// Handle booking cancellation
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    
    // Verify the booking belongs to the tenant and is pending or approved
    $check_stmt = $conn->prepare("SELECT id, status FROM bookings WHERE id = ? AND tenant_id = ? AND status IN ('pending', 'approved')");
    $check_stmt->execute([$booking_id, $tenant_id]);
    $booking = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        $update_stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled', cancellation_reason = 'Cancelled by tenant' WHERE id = ?");
        if ($update_stmt->execute([$booking_id])) {
            $success = "Booking cancelled successfully.";
        } else {
            $error = "Failed to cancel booking. Please try again.";
        }
    } else {
        $error = "Booking not found or cannot be cancelled.";
    }
}

// Handle booking status filter
$status_filter = $_GET['status'] ?? 'all';
$where_conditions = ["b.tenant_id = $tenant_id"];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Fetch tenant's bookings with property details
$bookings = $conn->prepare("
    SELECT 
        b.*, 
        p.title as property_title, 
        p.location, 
        p.price_per_day, 
        p.images as property_images,
        p.type as property_type,
        u.full_name as landlord_name, 
        u.phone as landlord_phone,
        u.email as landlord_email
    FROM bookings b 
    JOIN properties p ON b.property_id = p.id 
    JOIN users u ON p.landlord_id = u.id 
    WHERE $where_clause 
    ORDER BY b.created_at DESC
");

$bookings->execute($params);
$bookings = $bookings->fetchAll(PDO::FETCH_ASSOC);

// Get booking statistics for the tenant
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM bookings 
    WHERE tenant_id = $tenant_id
")->fetch(PDO::FETCH_ASSOC);

// Function to get property image
function getPropertyImage($property_images) {
    $images = json_decode($property_images ?? '[]', true);
    if (!empty($images) && !empty($images[0])) {
        return $images[0];
    }
    return '/kenya_rentals/assets/images/properties/placeholders/default.jpg';
}

// Function to check if booking can be cancelled
function canCancelBooking($booking) {
    $status = $booking['status'];
    $start_date = strtotime($booking['start_date']);
    $current_time = time();
    
    // Can cancel if pending, approved, and start date is more than 24 hours away
    return in_array($status, ['pending', 'approved']) && ($start_date - $current_time) > 86400;
}

// Function to check if booking is upcoming
function isUpcomingBooking($booking) {
    return $booking['status'] === 'approved' && strtotime($booking['start_date']) > time();
}

// Function to check if booking is active (currently ongoing)
function isActiveBooking($booking) {
    $now = time();
    $start = strtotime($booking['start_date']);
    $end = strtotime($booking['end_date']);
    return $booking['status'] === 'approved' && $now >= $start && $now <= $end;
}
?>

<?php include '../../includes/header.php'; ?>

<div class="max-w-7xl mx-auto py-6 px-4">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">My Bookings</h1>
        <p class="text-gray-600 mt-2">Manage and track all your property bookings in one place</p>
    </div>

    <!-- Notifications -->
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

    <!-- Booking Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-primary"><?= $stats['total'] ?></div>
            <div class="text-sm text-gray-600">Total</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600"><?= $stats['pending'] ?></div>
            <div class="text-sm text-gray-600">Pending</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-green-600"><?= $stats['approved'] ?></div>
            <div class="text-sm text-gray-600">Approved</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-blue-600"><?= $stats['completed'] ?></div>
            <div class="text-sm text-gray-600">Completed</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-red-600"><?= $stats['cancelled'] + $stats['rejected'] ?></div>
            <div class="text-sm text-gray-600">Cancelled</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-900">Filter Bookings</h3>
            <div class="flex flex-wrap gap-2">
                <a href="?status=all" 
                   class="px-4 py-2 rounded-full text-sm font-medium <?= $status_filter === 'all' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                    All (<?= $stats['total'] ?>)
                </a>
                <a href="?status=pending" 
                   class="px-4 py-2 rounded-full text-sm font-medium <?= $status_filter === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                    Pending (<?= $stats['pending'] ?>)
                </a>
                <a href="?status=approved" 
                   class="px-4 py-2 rounded-full text-sm font-medium <?= $status_filter === 'approved' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                    Approved (<?= $stats['approved'] ?>)
                </a>
                <a href="?status=completed" 
                   class="px-4 py-2 rounded-full text-sm font-medium <?= $status_filter === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                    Completed (<?= $stats['completed'] ?>)
                </a>
                <a href="?status=cancelled" 
                   class="px-4 py-2 rounded-full text-sm font-medium <?= $status_filter === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                    Cancelled (<?= $stats['cancelled'] + $stats['rejected'] ?>)
                </a>
            </div>
        </div>
    </div>

    <!-- Bookings List -->
    <div class="space-y-6">
        <?php foreach($bookings as $booking): 
            $property_image = getPropertyImage($booking['property_images']);
            $total_amount = calculateBookingTotal($booking['price_per_day'], $booking['start_date'], $booking['end_date']);
            $can_cancel = canCancelBooking($booking);
            $is_upcoming = isUpcomingBooking($booking);
            $is_active = isActiveBooking($booking);
        ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
            <div class="md:flex">
                <!-- Property Image -->
                <div class="md:w-1/4">
                    <img src="<?= $property_image ?>" 
                         alt="<?= htmlspecialchars($booking['property_title']) ?>" 
                         class="w-full h-48 md:h-full object-cover"
                         onerror="this.src='/kenya_rentals/assets/images/properties/placeholders/default.jpg'">
                </div>
                
                <!-- Booking Details -->
                <div class="md:w-3/4 p-6">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 mb-2"><?= htmlspecialchars($booking['property_title']) ?></h3>
                                    <div class="flex items-center text-gray-600 mb-2">
                                        <i class="fas fa-map-marker-alt mr-2"></i>
                                        <?= htmlspecialchars($booking['location']) ?>
                                    </div>
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-user mr-2"></i>
                                        <?= htmlspecialchars($booking['landlord_name']) ?>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <span class="px-3 py-1 rounded-full text-sm font-medium <?= getBookingStatusBadge($booking['status']) ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                    <div class="mt-2 text-lg font-semibold text-primary">
                                        KSh <?= number_format($total_amount, 2) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Booking Dates and Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="text-sm text-gray-500">Booking Dates</p>
                                    <p class="font-medium">
                                        <?= date('M j, Y', strtotime($booking['start_date'])) ?> - 
                                        <?= date('M j, Y', strtotime($booking['end_date'])) ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?= $booking['total_days'] ?> day<?= $booking['total_days'] > 1 ? 's' : '' ?>
                                    </p>
                                </div>
                                
                                <div>
                                    <p class="text-sm text-gray-500">Property Type</p>
                                    <p class="font-medium capitalize"><?= $booking['property_type'] ?></p>
                                    <p class="text-sm text-gray-500">
                                        KSh <?= number_format($booking['price_per_day']) ?>/day
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Special Requests -->
                            <?php if (!empty($booking['special_requests'])): ?>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500">Special Requests</p>
                                <p class="text-sm text-gray-700"><?= htmlspecialchars($booking['special_requests']) ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Landlord Notes -->
                            <?php if (!empty($booking['landlord_notes'])): ?>
                            <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                                <p class="text-sm font-medium text-blue-800">Landlord Note</p>
                                <p class="text-sm text-blue-700"><?= htmlspecialchars($booking['landlord_notes']) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Actions and Additional Info -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-500 mb-2 sm:mb-0">
                            Booked on: <?= date('M j, Y g:i A', strtotime($booking['created_at'])) ?>
                            
                            <?php if ($is_upcoming): ?>
                                <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                    <i class="fas fa-clock mr-1"></i>Upcoming
                                </span>
                            <?php elseif ($is_active): ?>
                                <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                    <i class="fas fa-play mr-1"></i>Active Now
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex space-x-3">
                            <!-- Contact Landlord -->
                            <a href="tel:<?= htmlspecialchars($booking['landlord_phone']) ?>" 
                               class="inline-flex items-center text-sm text-primary hover:text-secondary">
                                <i class="fas fa-phone mr-1"></i> Call
                            </a>
                            
                            <a href="mailto:<?= htmlspecialchars($booking['landlord_email']) ?>" 
                               class="inline-flex items-center text-sm text-primary hover:text-secondary">
                                <i class="fas fa-envelope mr-1"></i> Email
                            </a>
                            
                            <!-- Cancel Booking -->
                            <?php if ($can_cancel): ?>
                                <button onclick="confirmCancel(<?= $booking['id'] ?>)" 
                                        class="inline-flex items-center text-sm text-red-600 hover:text-red-800">
                                    <i class="fas fa-times mr-1"></i> Cancel
                                </button>
                            <?php endif; ?>
                            
                            <!-- View Property -->
                            <a href="/kenya_rentals/dashboard/tenant/search.php?property_id=<?= $booking['property_id'] ?>" 
                               class="inline-flex items-center text-sm text-primary hover:text-secondary">
                                <i class="fas fa-eye mr-1"></i> View Property
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="text-center py-12">
            <div class="bg-white rounded-lg shadow p-8 max-w-md mx-auto">
                <i class="fas fa-calendar-plus text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    <?= $status_filter !== 'all' ? "No {$status_filter} bookings" : 'No bookings yet' ?>
                </h3>
                <p class="text-gray-500 mb-4">
                    <?php if ($status_filter !== 'all'): ?>
                        You don't have any <?= $status_filter ?> bookings at the moment.
                    <?php else: ?>
                        Start by exploring available properties and make your first booking.
                    <?php endif; ?>
                </p>
                <a href="search.php" class="inline-block bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition duration-300">
                    Browse Properties
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Cancel Confirmation Modal -->
<div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-semibold mb-4">Cancel Booking</h3>
        <p class="text-gray-600 mb-4">Are you sure you want to cancel this booking? This action cannot be undone.</p>
        <p class="text-sm text-yellow-600 mb-4">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            You can only cancel bookings that are more than 24 hours away from the start date.
        </p>
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeCancelModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                Keep Booking
            </button>
            <a href="" id="cancelLink" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-300">
                Yes, Cancel Booking
            </a>
        </div>
    </div>
</div>

<script>
function confirmCancel(bookingId) {
    const cancelLink = document.getElementById('cancelLink');
    cancelLink.href = `?action=cancel&id=${bookingId}`;
    document.getElementById('cancelModal').classList.remove('hidden');
    document.getElementById('cancelModal').classList.add('flex');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
    document.getElementById('cancelModal').classList.remove('flex');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.id === 'cancelModal') {
        closeCancelModal();
    }
});

// Add some interactive features
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to buttons
    const buttons = document.querySelectorAll('a[href*="action=cancel"]');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Cancelling...';
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 3000);
        });
    });
    
    // Handle image loading errors
    const bookingImages = document.querySelectorAll('.bg-white img');
    bookingImages.forEach(img => {
        img.addEventListener('error', function() {
            this.src = '/kenya_rentals/assets/images/properties/placeholders/default.jpg';
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>