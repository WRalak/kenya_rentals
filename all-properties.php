<?php
require_once 'config/database.php';
session_start();
$db = new Database();
$conn = $db->getConnection();

// Get search filters
$location = $_GET['location'] ?? '';
$type = $_GET['type'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// Build query
$query = "
    SELECT p.*, u.full_name as landlord_name 
    FROM properties p 
    JOIN users u ON p.landlord_id = u.id 
    WHERE p.is_available = 1
";
$params = [];

if (!empty($location)) {
    $query .= " AND p.location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($type)) {
    $query .= " AND p.type = ?";
    $params[] = $type;
}

if (!empty($max_price)) {
    $query .= " AND p.price_per_day <= ?";
    $params[] = $max_price;
}

$query .= " ORDER BY p.created_at DESC";

$properties = $conn->prepare($query);
$properties->execute($params);
$properties = $properties->fetchAll(PDO::FETCH_ASSOC);

// Function to get first property image or placeholder
function getPropertyImage($property) {
    $images = json_decode($property['images'] ?? '[]', true);
    if (!empty($images) && !empty($images[0])) {
        return $images[0];
    }
    return '/kenya_rentals/assets/images/properties/placeholders/default.jpg';
}
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">All Available Properties</h1>
    
    <!-- Search Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <input type="text" name="location" value="<?= htmlspecialchars($location) ?>" placeholder="e.g., Nairobi" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Property Type</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Any Type</option>
                    <option value="office" <?= $type === 'office' ? 'selected' : '' ?>>Office Space</option>
                    <option value="commercial" <?= $type === 'commercial' ? 'selected' : '' ?>>Commercial</option>
                    <option value="residential" <?= $type === 'residential' ? 'selected' : '' ?>>Residential</option>
                    <option value="garden" <?= $type === 'garden' ? 'selected' : '' ?>>Garden</option>
                    <option value="park" <?= $type === 'park' ? 'selected' : '' ?>>Park</option>
                    <option value="storage" <?= $type === 'storage' ? 'selected' : '' ?>>Storage</option>
                    <option value="event_space" <?= $type === 'event_space' ? 'selected' : '' ?>>Event Space</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Max Price (KSh/day)</label>
                <input type="number" name="max_price" value="<?= htmlspecialchars($max_price) ?>" placeholder="Maximum price" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-secondary transition duration-300">
                    <i class="fas fa-search mr-2"></i> Search
                </button>
            </div>
        </form>
    </div>

    <!-- Properties Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach($properties as $property): 
            $property_image = getPropertyImage($property);
        ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
                <div class="h-48 bg-gray-200 relative cursor-pointer" onclick="window.location.href='/kenya_rentals/property-details.php?id=<?= $property['id'] ?>'">
                    <img src="<?= $property_image ?>" alt="<?= htmlspecialchars($property['title']) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='/kenya_rentals/assets/images/properties/placeholders/default.jpg';">
                    <span class="absolute top-4 right-4 bg-white px-3 py-1 rounded-full text-sm font-semibold text-primary">
                        KSh <?= number_format($property['price_per_day']) ?>/day
                    </span>
                </div>
                
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2 cursor-pointer" onclick="window.location.href='/kenya_rentals/property-details.php?id=<?= $property['id'] ?>'">
                        <?= htmlspecialchars($property['title']) ?>
                    </h3>
                    
                    <p class="text-gray-600 mb-4">
                        <?= substr($property['description'] ?? 'No description available', 0, 100) ?>
                        <?= strlen($property['description'] ?? '') > 100 ? '...' : '' ?>
                    </p>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            <?= htmlspecialchars($property['location']) ?>
                        </div>
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-user mr-2"></i>
                            <?= htmlspecialchars($property['landlord_name']) ?>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="capitalize px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                            <?= htmlspecialchars($property['type']) ?>
                        </span>
                        <a href="/kenya_rentals/property-details.php?id=<?= $property['id'] ?>" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($properties)): ?>
        <div class="text-center py-12">
            <i class="fas fa-building text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900">No properties found</h3>
            <p class="text-gray-500 mt-2">Try adjusting your search filters.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>