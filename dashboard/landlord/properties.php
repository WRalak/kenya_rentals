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

// Create directories if they don't exist
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/kenya_rentals/assets/images/properties/uploads/';
$placeholders_dir = $_SERVER['DOCUMENT_ROOT'] . '/kenya_rentals/assets/images/properties/placeholders/';

if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}
if (!file_exists($placeholders_dir)) {
    @mkdir($placeholders_dir, 0777, true);
}

// Improved image upload handler with better error handling
function handleImageUpload($files) {
    $uploaded_images = [];
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/kenya_rentals/assets/images/properties/uploads/';
    
    // If directory doesn't exist, try to create it
    if (!file_exists($upload_dir)) {
        if (!@mkdir($upload_dir, 0777, true)) {
            // If we can't create directory, use placeholder
            return ['/kenya_rentals/assets/images/properties/placeholders/default.jpg'];
        }
    }
    
    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        return ['/kenya_rentals/assets/images/properties/placeholders/default.jpg'];
    }
    
    foreach ($files['name'] as $key => $name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $tmp_name = $files['tmp_name'][$key];
            $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                // Generate unique filename
                $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
                $destination = $upload_dir . $new_filename;
                
                // Check file size (max 5MB)
                if ($files['size'][$key] > 5 * 1024 * 1024) {
                    continue;
                }
                
                if (@move_uploaded_file($tmp_name, $destination)) {
                    $uploaded_images[] = '/kenya_rentals/assets/images/properties/uploads/' . $new_filename;
                }
            }
        }
    }
    
    return empty($uploaded_images) ? ['/kenya_rentals/assets/images/properties/placeholders/default.jpg'] : $uploaded_images;
}

// Handle property actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $type = $_POST['type'];
        $location = sanitizeInput($_POST['location']);
        $price_per_day = $_POST['price_per_day'];
        $size_sqft = $_POST['size_sqft'] ?? null;
        $capacity = $_POST['capacity'] ?? null;
        $bedrooms = $_POST['bedrooms'] ?? 0;
        $bathrooms = $_POST['bathrooms'] ?? 0;
        
        // Handle image upload
        $uploaded_images = [];
        if (!empty($_FILES['images']['name'][0])) {
            $uploaded_images = handleImageUpload($_FILES['images']);
        } else {
            $uploaded_images = ['/kenya_rentals/assets/images/properties/placeholders/default.jpg'];
        }
        
        $images_json = json_encode($uploaded_images);
        
        try {
            $stmt = $conn->prepare("INSERT INTO properties (landlord_id, title, description, type, location, price_per_day, size_sqft, capacity, bedrooms, bathrooms, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$landlord_id, $title, $description, $type, $location, $price_per_day, $size_sqft, $capacity, $bedrooms, $bathrooms, $images_json]);
            
            $success = "Property added successfully!";
        } catch (PDOException $e) {
            $error = "Failed to add property: " . $e->getMessage();
        }
        
    } elseif ($action === 'update') {
        $property_id = $_POST['property_id'];
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $type = $_POST['type'];
        $location = sanitizeInput($_POST['location']);
        $price_per_day = $_POST['price_per_day'];
        $size_sqft = $_POST['size_sqft'] ?? null;
        $capacity = $_POST['capacity'] ?? null;
        $bedrooms = $_POST['bedrooms'] ?? 0;
        $bathrooms = $_POST['bathrooms'] ?? 0;
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // Verify the property belongs to the landlord
        $check_stmt = $conn->prepare("SELECT id, images FROM properties WHERE id = ? AND landlord_id = ?");
        $check_stmt->execute([$property_id, $landlord_id]);
        $existing_property = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_property) {
            $images_json = $existing_property['images'];
            
            // Handle image upload for updates
            if (!empty($_FILES['images']['name'][0])) {
                $uploaded_images = handleImageUpload($_FILES['images']);
                if (!empty($uploaded_images)) {
                    $existing_images = json_decode($images_json, true) ?? [];
                    $images_json = json_encode(array_merge($existing_images, $uploaded_images));
                }
            }
            
            try {
                $stmt = $conn->prepare("UPDATE properties SET title = ?, description = ?, type = ?, location = ?, price_per_day = ?, size_sqft = ?, capacity = ?, bedrooms = ?, bathrooms = ?, is_available = ?, images = ? WHERE id = ?");
                $stmt->execute([$title, $description, $type, $location, $price_per_day, $size_sqft, $capacity, $bedrooms, $bathrooms, $is_available, $images_json, $property_id]);
                
                $success = "Property updated successfully!";
            } catch (PDOException $e) {
                $error = "Failed to update property: " . $e->getMessage();
            }
        } else {
            $error = "Property not found or access denied.";
        }
        
    } elseif ($action === 'delete') {
        $property_id = $_POST['property_id'];
        
        // Verify the property belongs to the landlord
        $check_stmt = $conn->prepare("SELECT id FROM properties WHERE id = ? AND landlord_id = ?");
        $check_stmt->execute([$property_id, $landlord_id]);
        
        if ($check_stmt->fetch()) {
            try {
                // First, delete any bookings for this property
                $delete_bookings = $conn->prepare("DELETE FROM bookings WHERE property_id = ?");
                $delete_bookings->execute([$property_id]);
                
                // Then delete the property
                $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
                $stmt->execute([$property_id]);
                
                $success = "Property deleted successfully!";
            } catch (PDOException $e) {
                $error = "Failed to delete property: " . $e->getMessage();
            }
        } else {
            $error = "Property not found or access denied.";
        }
    }
}

// Fetch landlord's properties
$properties = $conn->query("SELECT * FROM properties WHERE landlord_id = $landlord_id ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

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
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">My Properties</h1>
        <button onclick="openModal('addPropertyModal')" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
            <i class="fas fa-plus mr-2"></i> Add Property
        </button>
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

    <!-- Properties Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($properties as $property): 
            $property_image = getPropertyImage($property);
        ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden property-card hover:shadow-lg transition duration-300">
            <div class="h-48 bg-gray-200 relative">
                <img src="<?= $property_image ?>" 
                     alt="<?= htmlspecialchars($property['title']) ?>" 
                     class="w-full h-full object-cover"
                     onerror="this.src='/kenya_rentals/assets/images/properties/placeholders/default.jpg'">
                <span class="absolute top-4 right-4 bg-white px-3 py-1 rounded-full text-sm font-semibold text-primary">
                    KSh <?= number_format($property['price_per_day']) ?>/day
                </span>
                <?php if (!$property['is_available']): ?>
                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                        <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold">Not Available</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-2"><?= htmlspecialchars($property['title']) ?></h3>
                <p class="text-gray-600 mb-4"><?= substr($property['description'] ?? 'No description available', 0, 100) ?><?= strlen($property['description'] ?? '') > 100 ? '...' : '' ?></p>
                
                <div class="space-y-2 mb-4">
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <?= htmlspecialchars($property['location']) ?>
                    </div>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-home mr-2"></i>
                        <?= ucfirst($property['type']) ?>
                    </div>
                    <?php if ($property['size_sqft']): ?>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-arrows-alt mr-2"></i>
                        <?= number_format($property['size_sqft']) ?> sqft
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex justify-between items-center">
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= $property['is_available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= $property['is_available'] ? 'Available' : 'Not Available' ?>
                    </span>
                    <div class="flex space-x-2">
                        <button onclick="openEditModal(<?= $property['id'] ?>)" 
                                class="text-blue-600 hover:text-blue-800 transition duration-300"
                                title="Edit Property">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="confirmDelete(<?= $property['id'] ?>)" 
                                class="text-red-600 hover:text-red-800 transition duration-300"
                                title="Delete Property">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($properties)): ?>
        <div class="text-center py-12">
            <i class="fas fa-building text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900">No properties yet</h3>
            <p class="text-gray-500 mt-2">Get started by adding your first property.</p>
            <button onclick="openModal('addPropertyModal')" class="mt-4 bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition duration-300">
                Add Your First Property
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Add Property Modal -->
<div id="addPropertyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-semibold mb-4">Add New Property</h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Property Images</label>
                    <input type="file" name="images[]" multiple accept="image/*"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-sm text-gray-500 mt-1">Select multiple images. Max 5MB per image. Supported: JPG, PNG, GIF, WebP</p>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Property Title *</label>
                    <input type="text" name="title" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="e.g., Modern Office Space in Westlands">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea name="description" required rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                              placeholder="Describe your property..."></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Property Type *</label>
                    <select name="type" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Select Type</option>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Location *</label>
                    <input type="text" name="location" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="e.g., Nairobi, Westlands">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price per Day (KSh) *</label>
                    <input type="number" name="price_per_day" min="0" step="0.01" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="5000">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Size (sqft)</label>
                    <input type="number" name="size_sqft" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="1000">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacity</label>
                    <input type="number" name="capacity" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="10">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bedrooms</label>
                    <input type="number" name="bedrooms" min="0" value="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bathrooms</label>
                    <input type="number" name="bathrooms" min="0" value="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('addPropertyModal')" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Cancel
                </button>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                    Add Property
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Property Modal -->
<div id="editPropertyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-semibold mb-4">Edit Property</h3>
        <form method="POST" action="" enctype="multipart/form-data" id="editPropertyForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="property_id" id="edit_property_id">
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Add More Images</label>
                <input type="file" name="images[]" multiple accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                <p class="text-sm text-gray-500 mt-1">Select additional images to add to the property</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Property Title *</label>
                    <input type="text" name="title" id="edit_title" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea name="description" id="edit_description" required rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Property Type *</label>
                    <select name="type" id="edit_type" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Select Type</option>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Location *</label>
                    <input type="text" name="location" id="edit_location" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price per Day (KSh) *</label>
                    <input type="number" name="price_per_day" id="edit_price_per_day" min="0" step="0.01" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Size (sqft)</label>
                    <input type="number" name="size_sqft" id="edit_size_sqft" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacity</label>
                    <input type="number" name="capacity" id="edit_capacity" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bedrooms</label>
                    <input type="number" name="bedrooms" id="edit_bedrooms" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bathrooms</label>
                    <input type="number" name="bathrooms" id="edit_bathrooms" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_available" id="edit_is_available" class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Property is available for booking</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('editPropertyModal')" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Cancel
                </button>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition duration-300">
                    Update Property
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-semibold mb-4">Confirm Delete</h3>
        <p class="text-gray-600 mb-4">Are you sure you want to delete this property?</p>
        <p class="text-sm text-red-600 mb-4">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            This will also delete all bookings for this property. This action cannot be undone.
        </p>
        <form method="POST" action="" id="deleteForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="property_id" id="delete_property_id">
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal('deleteModal')" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Cancel
                </button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-300">
                    Delete Property
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.getElementById(modalId).classList.add('flex');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.getElementById(modalId).classList.remove('flex');
}

function openEditModal(propertyId) {
    // For now, we'll just show a message that edit is not fully implemented
    // In a real application, you would fetch the property data here
    alert('Edit functionality will be implemented in the next version. For now, you can delete and recreate the property.');
}

function confirmDelete(propertyId) {
    document.getElementById('delete_property_id').value = propertyId;
    openModal('deleteModal');
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.add('hidden');
        event.target.classList.remove('flex');
    }
});

// Add interactivity to property cards
document.addEventListener('DOMContentLoaded', function() {
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