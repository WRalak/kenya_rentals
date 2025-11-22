<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Start session and check if user is tenant
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header("Location: /kenya_rentals/auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$tenant_id = $_SESSION['user_id'];

// Build search query based on filters
$where_conditions = ["p.is_available = 1"];
$params = [];

// Location filter
if (!empty($_GET['location'])) {
    $where_conditions[] = "p.location LIKE ?";
    $params[] = "%" . $_GET['location'] . "%";
}

// Property type filter
if (!empty($_GET['type'])) {
    $where_conditions[] = "p.type = ?";
    $params[] = $_GET['type'];
}

// Price filter
if (!empty($_GET['max_price'])) {
    $where_conditions[] = "p.price_per_day <= ?";
    $params[] = $_GET['max_price'];
}

// Specific property filter
if (!empty($_GET['property_id'])) {
    $where_conditions[] = "p.id = ?";
    $params[] = $_GET['property_id'];
}

// Build final query
$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT p.*, u.full_name as landlord_name, u.phone as landlord_phone 
        FROM properties p 
        JOIN users u ON p.landlord_id = u.id 
        WHERE $where_clause 
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique locations for filter suggestions
$locations = $conn->query("SELECT DISTINCT location FROM properties WHERE is_available = 1 ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);

// Function to get property image
function getPropertyImage($property) {
    $images = json_decode($property['images'] ?? '[]', true);
    if (!empty($images) && !empty($images[0])) {
        return $images[0];
    }
    return '/kenya_rentals/assets/images/properties/placeholders/default.jpg';
}
?>

<?php include '../../includes/header.php'; ?>

<div class="max-w-7xl mx-auto py-6 px-4">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Find Your Perfect Space</h1>
        <p class="text-gray-600 mt-2">Welcome back, <?= $_SESSION['full_name'] ?>! Discover amazing spaces across Kenya</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Filters Sidebar -->
        <div class="lg:w-1/4">
            <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                <h3 class="text-lg font-semibold mb-4">Filters</h3>
                
                <form method="GET" action="" class="space-y-6">
                    <!-- Location Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                        <input type="text" name="location" list="locationSuggestions" 
                               value="<?= htmlspecialchars($_GET['location'] ?? '') ?>" 
                               placeholder="e.g., Nairobi, Westlands"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <datalist id="locationSuggestions">
                            <?php foreach($locations as $location): ?>
                                <option value="<?= htmlspecialchars($location) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <!-- Property Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Property Type</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">All Types</option>
                            <option value="office" <?= ($_GET['type'] ?? '') == 'office' ? 'selected' : '' ?>>Office Space</option>
                            <option value="commercial" <?= ($_GET['type'] ?? '') == 'commercial' ? 'selected' : '' ?>>Commercial</option>
                            <option value="residential" <?= ($_GET['type'] ?? '') == 'residential' ? 'selected' : '' ?>>Residential</option>
                            <option value="garden" <?= ($_GET['type'] ?? '') == 'garden' ? 'selected' : '' ?>>Garden</option>
                            <option value="park" <?= ($_GET['type'] ?? '') == 'park' ? 'selected' : '' ?>>Park</option>
                        </select>
                    </div>

                    <!-- Price Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Price (KSh/day)</label>
                        <input type="number" name="max_price" 
                               value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>" 
                               placeholder="Maximum price"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3">
                        <button type="submit" class="flex-1 bg-primary text-white py-2 px-4 rounded-lg hover:bg-secondary transition duration-300">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>
                        <a href="search.php" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Properties Grid -->
        <div class="lg:w-3/4">
            <!-- Results Header -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?= count($properties) ?> Property<?= count($properties) !== 1 ? 's' : '' ?> Found
                        </h3>
                        <?php if (!empty($_GET)): ?>
                            <p class="text-sm text-gray-600 mt-1">Based on your search criteria</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Properties Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach($properties as $property): 
                    $property_image = getPropertyImage($property);
                ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300 property-card">
                    <div class="h-48 bg-gray-200 relative cursor-pointer" onclick="openQuickView(<?= $property['id'] ?>)">
                        <img src="<?= $property_image ?>" 
                             alt="<?= htmlspecialchars($property['title']) ?>" 
                             class="w-full h-full object-cover"
                             onerror="this.src='/kenya_rentals/assets/images/properties/placeholders/default.jpg'">
                        <span class="absolute top-4 right-4 bg-white px-3 py-1 rounded-full text-sm font-semibold text-primary">
                            KSh <?= number_format($property['price_per_day']) ?>/day
                        </span>
                    </div>
                    
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2 cursor-pointer" onclick="openQuickView(<?= $property['id'] ?>)">
                            <?= htmlspecialchars($property['title']) ?>
                        </h3>
                        <p class="text-gray-600 mb-4"><?= substr($property['description'] ?? 'No description available', 0, 100) ?><?= strlen($property['description'] ?? '') > 100 ? '...' : '' ?></p>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <?= htmlspecialchars($property['location']) ?>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-user mr-2"></i>
                                <?= htmlspecialchars($property['landlord_name']) ?>
                            </div>
                            <?php if ($property['size_sqft']): ?>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-arrows-alt mr-2"></i>
                                <?= number_format($property['size_sqft']) ?> sqft
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="capitalize px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                                <?= $property['type'] ?>
                            </span>
                            <button onclick="openBookingModal(<?= $property['id'] ?>)" 
                                    class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                                Book Now
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($properties)): ?>
                <div class="text-center py-12">
                    <div class="bg-white rounded-lg shadow p-8 max-w-md mx-auto">
                        <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No properties found</h3>
                        <p class="text-gray-500 mb-4">
                            <?php if (!empty($_GET)): ?>
                                Try adjusting your search filters to see more results.
                            <?php else: ?>
                                There are currently no properties available. Check back later!
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($_GET)): ?>
                            <a href="search.php" class="inline-block bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition duration-300">
                                Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick View Modal -->
<div id="quickViewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div id="quickViewContent">
            <!-- Content will be loaded via JavaScript -->
        </div>
        <div class="flex justify-end mt-6">
            <button onclick="closeQuickView()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div id="bookingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-semibold mb-4">Book This Property</h3>
        <form method="POST" action="/kenya_rentals/api/bookings.php" id="bookingForm">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="property_id" id="booking_property_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Check-in Date *</label>
                    <input type="date" name="start_date" id="booking_start_date" required 
                           min="<?= date('Y-m-d') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Check-out Date *</label>
                    <input type="date" name="end_date" id="booking_end_date" required 
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Special Requests</label>
                    <textarea name="special_requests" rows="3" placeholder="Any special requirements or questions..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeBookingModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Cancel
                </button>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                    Confirm Booking
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Global functions for modal handling
function openQuickView(propertyId) {
    // For now, just open the booking modal
    // In a real app, you would fetch property details here
    openBookingModal(propertyId);
}

function closeQuickView() {
    document.getElementById('quickViewModal').classList.add('hidden');
    document.getElementById('quickViewModal').classList.remove('flex');
}

function openBookingModal(propertyId) {
    document.getElementById('booking_property_id').value = propertyId;
    document.getElementById('bookingModal').classList.remove('hidden');
    document.getElementById('bookingModal').classList.add('flex');
}

function closeBookingModal() {
    document.getElementById('bookingModal').classList.add('hidden');
    document.getElementById('bookingModal').classList.remove('flex');
}

// Handle booking form submission
document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Booking...';
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Booking request submitted successfully!');
                    closeBookingModal();
                    window.location.href = 'bookings.php';
                } else {
                    alert('Error: ' + result.error);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                console.error('Booking error:', error);
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    }
    
    // Close modals when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('fixed')) {
            event.target.classList.add('hidden');
            event.target.classList.remove('flex');
        }
    });
    
    // Make property images clickable
    const propertyImages = document.querySelectorAll('.property-card img');
    propertyImages.forEach(img => {
        img.addEventListener('click', function() {
            const propertyCard = this.closest('.property-card');
            const propertyId = propertyCard.querySelector('button').getAttribute('onclick').match(/\d+/)[0];
            openQuickView(propertyId);
        });
    });
    
    // Add hover effects
    const propertyCards = document.querySelectorAll('.property-card');
    propertyCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>