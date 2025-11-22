<?php
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatPrice($price) {
    return 'KSh ' . number_format($price, 2);
}

function calculateBookingTotal($price_per_day, $start_date, $end_date) {
    $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
    return $price_per_day * max(1, $days);
}

function getPropertyTypes() {
    return [
        'office' => 'Office Space',
        'commercial' => 'Commercial',
        'residential' => 'Residential',
        'garden' => 'Garden',
        'park' => 'Park',
        'storage' => 'Storage',
        'event_space' => 'Event Space'
    ];
}

function getBookingStatusBadge($status) {
    $badges = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-gray-100 text-gray-800',
        'completed' => 'bg-blue-100 text-blue-800'
    ];
    return $badges[$status] ?? 'bg-gray-100 text-gray-800';
}

function redirect($url) {
    header("Location: $url");
    exit();
}
?>