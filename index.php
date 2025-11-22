<?php 
require_once 'config/database.php';

// Start session
session_start();
$db = new Database();
$conn = $db->getConnection();

// Fetch featured properties with images
$featured_properties = $conn->query("
    SELECT p.*, u.full_name as landlord_name 
    FROM properties p 
    JOIN users u ON p.landlord_id = u.id 
    WHERE p.is_available = 1 
    ORDER BY p.created_at DESC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

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

<!-- Hero Section with Image and Search -->
<section class="relative h-[600px] flex items-center justify-center text-white">
    <!-- Background Image -->
    <div class="absolute inset-0">
        <img src="/kenya_rentals/assets/images/hero/hero-image.jpg" alt="Hero Image" class="w-full h-full object-cover brightness-75">
    </div>
    
    <!-- Hero Content -->
    <div class="relative text-center px-4 z-10">
        <h1 class="text-5xl font-bold mb-6">Find Your Perfect Space in Kenya</h1>
        <p class="text-xl mb-8 opacity-90">Discover office spaces, commercial properties, gardens, and more across Kenya's top locations</p>
        
        <!-- Quick Search Form -->
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-4xl mx-auto">
            <form action="/kenya_rentals/dashboard/tenant/search.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 text-left">Location</label>
                    <input type="text" name="location" placeholder="e.g., Nairobi, Westlands" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 text-left">Property Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-900">
                        <option value="">Any Type</option>
                        <option value="office">Office Space</option>
                        <option value="commercial">Commercial</option>
                        <option value="residential">Residential</option>
                        <option value="garden">Garden</option>
                        <option value="park">Park</option>
                        <option value="storage">Storage</option>
                        <option value="event_space">Event Space</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 text-left">Max Price (KSh/day)</label>
                    <input type="number" name="max_price" placeholder="Maximum price" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-900">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300">
                        <i class="fas fa-search mr-2"></i> Search Properties
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <?php 
            $property_stats = $conn->query("
                SELECT type, COUNT(*) as count 
                FROM properties 
                WHERE is_available = 1 
                GROUP BY type
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            $total_properties = 0;
            foreach ($property_stats as $stat) {
                $total_properties += $stat['count'];
            }
            ?>
            <div>
                <div class="text-3xl font-bold text-primary mb-2"><?= $total_properties ?></div>
                <div class="text-gray-600">Total Properties</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-primary mb-2"><?= count($featured_properties) ?></div>
                <div class="text-gray-600">Featured Spaces</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-primary mb-2">50+</div>
                <div class="text-gray-600">Cities Covered</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-primary mb-2">24/7</div>
                <div class="text-gray-600">Support</div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Properties -->
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Featured Properties</h2>
            <p class="text-gray-600 text-lg">Discover some of our most popular rental spaces across Kenya</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($featured_properties as $property): 
                $property_image = getPropertyImage($property);
            ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300 property-card">
                    <!-- CLICKABLE PROPERTY IMAGE -->
                    <div class="h-48 bg-gray-200 relative cursor-pointer" onclick="openPropertyDetails(<?= $property['id'] ?>)">
                        <img src="<?= $property_image ?>" alt="<?= htmlspecialchars($property['title']) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='/kenya_rentals/assets/images/properties/placeholders/default.jpg';">
                        <span class="absolute top-4 right-4 bg-white px-3 py-1 rounded-full text-sm font-semibold text-primary">
                            KSh <?= number_format($property['price_per_day']) ?>/day
                        </span>
                        <?php if ($property['is_featured']): ?>
                            <span class="absolute top-4 left-4 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                Featured
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-6">
                        <!-- CLICKABLE TITLE -->
                        <h3 class="text-xl font-semibold text-gray-900 mb-2 cursor-pointer" onclick="openPropertyDetails(<?= $property['id'] ?>)">
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
                            <?php if ($property['size_sqft']): ?>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-arrows-alt mr-2"></i>
                                    <?= number_format($property['size_sqft']) ?> sqft
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="capitalize px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                                <?= htmlspecialchars($property['type']) ?>
                            </span>
                            <!-- FIXED: Simple login check -->
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="/kenya_rentals/dashboard/tenant/search.php?property_id=<?= $property['id'] ?>" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                                    View Details
                                </a>
                            <?php else: ?>
                                <a href="/kenya_rentals/auth/register.php?type=tenant" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                                    View Details
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($featured_properties)): ?>
            <div class="text-center py-12">
                <i class="fas fa-building text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900">No properties available</h3>
                <p class="text-gray-500 mt-2">Check back later for new property listings.</p>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-12">
            <!-- FIXED: Simple login check -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/kenya_rentals/dashboard/tenant/search.php" class="bg-primary text-white px-8 py-3 rounded-lg hover:bg-secondary transition duration-300 text-lg font-semibold">
                    View All Properties
                </a>
            <?php else: ?>
                <a href="/kenya_rentals/auth/register.php?type=tenant" class="bg-primary text-white px-8 py-3 rounded-lg hover:bg-secondary transition duration-300 text-lg font-semibold">
                    Sign Up to Browse Properties
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- JavaScript -->
<script>
function openPropertyDetails(propertyId) {
    // FIXED: Simple login check
    const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    
    if (isLoggedIn) {
        // User is logged in - go directly to property details
        window.location.href = '/kenya_rentals/dashboard/tenant/search.php?property_id=' + propertyId;
    } else {
        // Not logged in - go to register page
        window.location.href = '/kenya_rentals/auth/register.php?type=tenant';
    }
}

// Hover animation for property cards
document.addEventListener('DOMContentLoaded', function() {
    const propertyCards = document.querySelectorAll('.property-card');
    propertyCards.forEach(card => {
        card.addEventListener('mouseenter', () => card.style.transform = 'translateY(-5px)');
        card.addEventListener('mouseleave', () => card.style.transform = 'translateY(0)');
    });
});
</script>

<?php include 'includes/footer.php'; ?>