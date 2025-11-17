<?php
// api/get_user_details.php
include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../includes/session.php';
include_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Check if user is admin
checkRole(['admin']);

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$user_id = (int)$_GET['user_id'];

try {
    // Get user data
    $sql = "SELECT u.*, b.branch_name
            FROM users u
            LEFT JOIN branches b ON u.branch_id = b.branch_id
            WHERE u.user_id = ?";
    
    $user = get_single_result($sql, [$user_id]);
    
    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    echo json_encode($user);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>