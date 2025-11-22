<?php
require_once '../../config/database.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header("Location: /kenya_rentals/auth/login.php");
    exit();
}
?>
<?php include '../../includes/header.php'; ?>
<div class="max-w-7xl mx-auto py-6 px-4">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Search Properties</h1>
    <div class="bg-white p-6 rounded-lg shadow">
        <p class="text-gray-600">Property search page - Coming soon!</p>
        <a href="/kenya_rentals/dashboard/tenant/" class="text-primary hover:underline mt-4 inline-block">← Back to Dashboard</a>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>