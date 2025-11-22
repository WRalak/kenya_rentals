<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$landlord_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    $property_id = $_GET['id'];
    
    // Verify the property belongs to the landlord
    $stmt = $conn->prepare("SELECT * FROM properties WHERE id = ? AND landlord_id = ?");
    $stmt->execute([$property_id, $landlord_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($property) {
        echo json_encode($property);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Property not found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>