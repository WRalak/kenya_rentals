<?php
require_once 'config/database.php';

// Start session
session_start();

$db = new Database();
$conn = $db->getConnection();

// Get property ID from URL
$property_id = $_GET['id'] ?? 0;

// Fetch property details
$stmt = $conn->prepare("
    SELECT p.*, u.full_name as landlord_name, u.phone as landlord_phone 
    FROM properties p 
    JOIN users u ON p.landlord_id = u.id 
    WHERE p.id = ? AND p.is_available = 1
");
$stmt->execute([$property_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header("Location: /kenya_rentals/");
    exit();
}

// Function to get property images
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

<div class="max-w-7xl mx-auto py-6 px-4">
    <!-- Breadcrumb -->
    <nav class="mb-6">
        <a href="/kenya_rentals/" class="text-primary hover:text-secondary">Home</a>
        <span class="mx-2 text-gray-400">/</span>
        <a href="/kenya_rentals/dashboard/tenant/search.php" class="text-primary hover:text-secondary">Properties</a>
        <span class="mx-2 text-gray-400">/</span>
        <span class="text-gray-600"><?= htmlspecialchars($property['title']) ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Property Images -->
        <div>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <img src="<?= $property_images[0] ?>" 
                     alt="<?= htmlspecialchars($property['title']) ?>" 
                     class="w-full h-96 object-cover"
                     onerror="this.src='/kenya_rentals/assets/images/properties/placeholders/default.jpg'">
            </div>
            
            <?php if (count($property_images) > 1): ?>
            <div class="grid grid-cols-4 gap-2 mt-4">
                <?php foreach(array_slice($property_images, 1, 4) as $image): ?>
                <img src="<?= $image ?>" 
                     alt="<?= htmlspecialchars($property['title']) ?>" 
                     class="w-full h-20 object-cover rounded-lg cursor-pointer hover:opacity-80 transition duration-300"
                     onerror="this.src='/kenya_rentals/assets/images/properties/placeholders/default.jpg'">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Property Details -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($property['title']) ?></h1>
            
            <div class="flex items-center mb-4">
                <span class="text-2xl font-bold text-primary mr-4">
                    KSh <?= number_format($property['price_per_day']) ?>/day
                </span>
                <span class="capitalize px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                    <?= $property['type'] ?>
                </span>
            </div>

            <div class="space-y-3 mb-6">
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-map-marker-alt mr-3 text-primary"></i>
                    <span><?= htmlspecialchars($property['location']) ?></span>
                </div>
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-user mr-3 text-primary"></i>
                    <span>Listed by <?= htmlspecialchars($property['landlord_name']) ?></span>
                </div>
                <?php if ($property['size_sqft']): ?>
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-arrows-alt mr-3 text-primary"></i>
                    <span><?= number_format($property['size_sqft']) ?> sqft</span>
                </div>
                <?php endif; ?>
                <?php if ($property['bedrooms'] > 0): ?>
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-bed mr-3 text-primary"></i>
                    <span><?= $property['bedrooms'] ?> bedroom<?= $property['bedrooms'] > 1 ? 's' : '' ?></span>
                </div>
                <?php endif; ?>
                <?php if ($property['bathrooms'] > 0): ?>
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-bath mr-3 text-primary"></i>
                    <span><?= $property['bathrooms'] ?> bathroom<?= $property['bathrooms'] > 1 ? 's' : '' ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">Description</h3>
                <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($property['description'] ?? 'No description available')) ?></p>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-4">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'tenant'): ?>
                    <a href="/kenya_rentals/dashboard/tenant/search.php?property_id=<?= $property['id'] ?>" 
                       class="w-full bg-primary text-white py-3 px-6 rounded-lg hover:bg-secondary transition duration-300 text-center block font-semibold">
                        <i class="fas fa-calendar-check mr-2"></i>Book This Property
                    </a>
                <?php else: ?>
                    <div class="space-y-3">
                        <a href="/kenya_rentals/auth/register.php?type=tenant" 
                           class="w-full bg-primary text-white py-3 px-6 rounded-lg hover:bg-secondary transition duration-300 text-center block font-semibold">
                            <i class="fas fa-user-plus mr-2"></i>Sign Up to Book
                        </a>
                        <a href="/kenya_rentals/auth/login.php" 
                           class="w-full border border-primary text-primary py-3 px-6 rounded-lg hover:bg-primary hover:text-white transition duration-300 text-center block">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login to Book
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>