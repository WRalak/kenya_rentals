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
        
        try {
            $stmt = $conn->prepare("INSERT INTO properties (landlord_id, title, description, type, location, price_per_day, size_sqft, capacity, bedrooms, bathrooms) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$landlord_id, $title, $description, $type, $location, $price_per_day, $size_sqft, $capacity, $bedrooms, $bathrooms]);
            
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
        $check_stmt = $conn->prepare("SELECT id FROM properties WHERE id = ? AND landlord_id = ?");
        $check_stmt->execute([$property_id, $landlord_id]);
        
        if ($check_stmt->fetch()) {
            try {
                $stmt = $conn->prepare("UPDATE properties SET title = ?, description = ?, type = ?, location = ?, price_per_day = ?, size_sqft = ?, capacity = ?, bedrooms = ?, bathrooms = ?, is_available = ? WHERE id = ?");
                $stmt->execute([$title, $description, $type, $location, $price_per_day, $size_sqft, $capacity, $bedrooms, $bathrooms, $is_available, $property_id]);
                
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

// Get property for editing
$edit_property = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $property_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM properties WHERE id = ? AND landlord_id = ?");
    $stmt->execute([$property_id, $landlord_id]);
    $edit_property = $stmt->fetch(PDO::FETCH_ASSOC);
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
        <?php foreach($properties as $property): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden property-card">
            <div class="h-48 bg-gray-200 relative">
                <div class="w-full h-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                    <i class="fas fa-building text-white text-6xl"></i>
                </div>
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
                <h3 class="text-xl font-semibold text-gray-900 mb-2"><?= $property['title'] ?></h3>
                <p class="text-gray-600 mb-4"><?= substr($property['description'] ?? 'No description', 0, 100) ?>...</p>
                
                <div class="space-y-2 mb-4">
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <?= $property['location'] ?>
                    </div>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-home mr-2"></i>
                        <?= ucfirst($property['type']) ?>
                    </div>
                    <?php if ($property['size_sqft']): ?>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-arrows-alt mr-2"></i>
                        <?= $property['size_sqft'] ?> sqft
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex justify-between items-center">
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= $property['is_available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= $property['is_available'] ? 'Available' : 'Not Available' ?>
                    </span>
                    <div class="flex space-x-2">
                        <button onclick="openEditModal(<?= $property['id'] ?>)" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="confirmDelete(<?= $property['id'] ?>)" class="text-red-600 hover:text-red-800">
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
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Property Title *</label>
                    <input type="text" name="title" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea name="description" required rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
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
                    <input type="text" name="location" placeholder="e.g., Nairobi, Westlands" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price per Day (KSh) *</label>
                    <input type="number" name="price_per_day" min="0" step="0.01" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Size (sqft)</label>
                    <input type="number" name="size_sqft" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacity</label>
                    <input type="number" name="capacity" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
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
        <form method="POST" action="">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="property_id" id="edit_property_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Property Title *</label>
                    <input type="text" name="title" id="edit_title" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea name="description" id="edit_description" required rows="3"
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
        <p class="text-gray-600 mb-6">Are you sure you want to delete this property? This action cannot be undone.</p>
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
    // Fetch property data and populate form
    fetch(`/kenya_rentals/api/properties.php?action=get&id=${propertyId}`)
        .then(response => response.json())
        .then(property => {
            document.getElementById('edit_property_id').value = property.id;
            document.getElementById('edit_title').value = property.title;
            document.getElementById('edit_description').value = property.description;
            document.getElementById('edit_type').value = property.type;
            document.getElementById('edit_location').value = property.location;
            document.getElementById('edit_price_per_day').value = property.price_per_day;
            document.getElementById('edit_size_sqft').value = property.size_sqft || '';
            document.getElementById('edit_capacity').value = property.capacity || '';
            document.getElementById('edit_bedrooms').value = property.bedrooms || 0;
            document.getElementById('edit_bathrooms').value = property.bathrooms || 0;
            document.getElementById('edit_is_available').checked = property.is_available;
            
            openModal('editPropertyModal');
        })
        .catch(error => {
            console.error('Error fetching property:', error);
            alert('Error loading property data');
        });
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
</script>

<?php include '../../includes/footer.php'; ?>