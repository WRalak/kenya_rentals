<?php
require_once 'config/database.php';
session_start();
$db = new Database();
$conn = $db->getConnection();

// Get property ID from URL
$property_id = $_GET['id'] ?? 0;

// Fetch property details
$property = $conn->prepare("
    SELECT p.*, u.full_name as landlord_name, u.phone as landlord_phone, u.email as landlord_email
    FROM properties p 
    JOIN users u ON p.landlord_id = u.id 
    WHERE p.id = ? AND p.is_available = 1
");
$property->execute([$property_id]);
$property = $property->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header("Location: /kenya_rentals/");
    exit;
}

// Function to get all property images
function getPropertyImages($property) {
    $images = json_decode($property['images'] ?? '[]', true);
    if (empty($images)) {
        return ['/kenya_rentals/assets/images/properties/placeholders/default.jpg'];
    }
    return $images;
}

$property_images = getPropertyImages($property);
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Property Images -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div>
            <img src="<?= $property_images[0] ?>" alt="<?= htmlspecialchars($property['title']) ?>" class="w-full h-96 object-cover rounded-lg">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <?php for($i = 1; $i < min(4, count($property_images)); $i++): ?>
                <img src="<?= $property_images[$i] ?>" alt="<?= htmlspecialchars($property['title']) ?>" class="w-full h-44 object-cover rounded-lg">
            <?php endfor; ?>
        </div>
    </div>

    <!-- Property Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <h1 class="text-3xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($property['title']) ?></h1>
            <div class="flex items-center gap-4 mb-6">
                <span class="text-2xl font-bold text-primary">KSh <?= number_format($property['price_per_day']) ?>/day</span>
                <span class="capitalize px-3 py-1 bg-gray-100 text-gray-700 rounded-full"><?= htmlspecialchars($property['type']) ?></span>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Description</h2>
                <p class="text-gray-700"><?= nl2br(htmlspecialchars($property['description'] ?? 'No description available')) ?></p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Property Details</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="flex items-center">
                        <i class="fas fa-map-marker-alt text-primary mr-2"></i>
                        <span><?= htmlspecialchars($property['location']) ?></span>
                    </div>
                    <?php if ($property['size_sqft']): ?>
                    <div class="flex items-center">
                        <i class="fas fa-arrows-alt text-primary mr-2"></i>
                        <span><?= number_format($property['size_sqft']) ?> sqft</span>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center">
                        <i class="fas fa-user text-primary mr-2"></i>
                        <span><?= htmlspecialchars($property['landlord_name']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact & Action Section -->
        <div class="bg-white rounded-lg shadow-md p-6 h-fit">
            <h3 class="text-lg font-semibold mb-4">Interested in this property?</h3>
            
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'tenant'): ?>
                <!-- Contact form for logged-in tenants -->
                <form action="/kenya_rentals/contact-landlord.php" method="POST">
                    <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Message</label>
                            <textarea name="message" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" placeholder="I'm interested in this property..."></textarea>
                        </div>
                        <button type="submit" class="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-secondary transition duration-300">
                            Contact Landlord
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Call to action for non-logged-in users -->
                <div class="text-center">
                    <p class="text-gray-600 mb-4">Sign up or login to contact the landlord and book this property</p>
                    <div class="space-y-3">
                        <a href="/kenya_rentals/auth/register.php?type=tenant" class="block w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-secondary transition duration-300">
                            Sign Up as Tenant
                        </a>
                        <a href="/kenya_rentals/auth/login.php" class="block w-full bg-gray-200 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-300 transition duration-300">
                            Login
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>