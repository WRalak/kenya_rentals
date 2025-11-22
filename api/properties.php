<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

session_start();
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get properties for search (public access)
    $filters = [];
    $params = [];
    
    if (!empty($_GET['location'])) {
        $filters[] = "p.location LIKE ?";
        $params[] = "%{$_GET['location']}%";
    }
    
    if (!empty($_GET['type'])) {
        $filters[] = "p.type = ?";
        $params[] = $_GET['type'];
    }
    
    if (!empty($_GET['max_price'])) {
        $filters[] = "p.price_per_day <= ?";
        $params[] = $_GET['max_price'];
    }
    
    $where_clause = empty($filters) ? "1" : implode(' AND ', $filters);
    $sql = "SELECT p.*, u.full_name as landlord_name 
            FROM properties p 
            JOIN users u ON p.landlord_id = u.id 
            WHERE p.is_available = 1 AND $where_clause 
            ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($properties);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isLandlord()) {
    // Landlord property management
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $type = $_POST['type'];
        $location = sanitizeInput($_POST['location']);
        $price_per_day = $_POST['price_per_day'];
        $size_sqft = $_POST['size_sqft'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO properties (landlord_id, title, description, type, location, price_per_day, size_sqft) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        try {
            $stmt->execute([$_SESSION['user_id'], $title, $description, $type, $location, $price_per_day, $size_sqft]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Property created successfully',
                'property_id' => $conn->lastInsertId()
            ]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Failed to create property: ' . $e->getMessage()]);
        }
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>