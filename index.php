<?php
require_once 'config/database.php';

// Start session
session_start();

$db = new Database();
$conn = $db->getConnection();

// Fetch featured properties
$featured_properties = $conn->query("
    SELECT p.*, u.full_name as landlord_name 
    FROM properties p 
    JOIN users u ON p.landlord_id = u.id 
    WHERE p.is_available = 1 
    ORDER BY p.created_at DESC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-primary to-secondary text-white py-20">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-5xl font-bold mb-6">Find Your Perfect Space in Kenya</h1>
        <p class="text-xl mb-8 opacity-90">Discover office spaces, commercial properties, gardens, and more across Kenya's top locations</p>
        
        <!-- Quick Search -->
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-4xl mx-auto">
            <form action="/kenya_rentals/dashboard/tenant/search.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 text-left">Location</label>
                    <input type="text" name="location" placeholder="e.g., Nairobi, Westlands" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-900">
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
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 text-left">Max Price (KSh/day)</label>
                    <input type="number" name="max_price" placeholder="Maximum price" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-900">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300">
                        <i class="fas fa-search mr-2"></i> Search
                    </button>
                </div>
            </form>
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
            <?php foreach($featured_properties as $property): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
                <div class="h-48 bg-gray-200 relative">
                    <div class="w-full h-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                        <i class="fas fa-building text-white text-6xl"></i>
                    </div>
                    <span class="absolute top-4 right-4 bg-white px-3 py-1 rounded-full text-sm font-semibold text-primary">
                        KSh <?= number_format($property['price_per_day']) ?>/day
                    </span>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2"><?= $property['title'] ?></h3>
                    <p class="text-gray-600 mb-4"><?= substr($property['description'] ?? 'No description available', 0, 100) ?>...</p>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            <?= $property['location'] ?>
                        </div>
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-user mr-2"></i>
                            <?= $property['landlord_name'] ?>
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="capitalize px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                            <?= $property['type'] ?>
                        </span>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'tenant'): ?>
                            <a href="/kenya_rentals/dashboard/tenant/search.php" 
                               class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                                Book Now
                            </a>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <a href="/kenya_rentals/auth/register.php?type=tenant" 
                               class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                                Book Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="/kenya_rentals/dashboard/tenant/search.php" 
               class="bg-primary text-white px-8 py-3 rounded-lg hover:bg-secondary transition duration-300 text-lg font-semibold">
                View All Properties
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>