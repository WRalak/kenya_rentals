<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isLandlord() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'landlord';
}

function isTenant() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'tenant';
}

function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: /auth/login.php");
        exit();
    }
}

function requireLandlord() {
    requireAuth();
    if (!isLandlord()) {
        header("Location: /dashboard/tenant/");
        exit();
    }
}

function requireTenant() {
    requireAuth();
    if (!isTenant()) {
        header("Location: /dashboard/landlord/");
        exit();
    }
}
?>