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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        // Create new booking
        $property_id = $_POST['property_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $tenant_id = $_SESSION['user_id'];
        
        // Validate dates
        if (strtotime($start_date) >= strtotime($end_date)) {
            echo json_encode(['error' => 'End date must be after start date']);
            exit();
        }
        
        if (strtotime($start_date) < strtotime('today')) {
            echo json_encode(['error' => 'Start date cannot be in the past']);
            exit();
        }
        
        // Check property availability
        if (!isAvailable($property_id, $start_date, $end_date, $conn)) {
            echo json_encode(['error' => 'Property is not available for the selected dates']);
            exit();
        }
        
        // Get property price
        $stmt = $conn->prepare("SELECT price_per_day FROM properties WHERE id = ?");
        $stmt->execute([$property_id]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$property) {
            echo json_encode(['error' => 'Property not found']);
            exit();
        }
        
        $total_amount = calculateBookingTotal($property['price_per_day'], $start_date, $end_date);
        
        // Create booking
        $stmt = $conn->prepare("INSERT INTO bookings (tenant_id, property_id, start_date, end_date, total_amount, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        
        try {
            $stmt->execute([$tenant_id, $property_id, $start_date, $end_date, $total_amount]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Booking request submitted successfully',
                'booking_id' => $conn->lastInsertId()
            ]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Failed to create booking: ' . $e->getMessage()]);
        }
        
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>