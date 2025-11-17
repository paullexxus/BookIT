<?php
include_once '../config/db.php';
include_once '../includes/session.php';
include_once '../includes/ImageFingerprinting.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$unit_id = (int)($_POST['unit_id'] ?? 0);
$image_path = $_POST['image_path'] ?? '';

if (!$unit_id || !$image_path) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Verify unit belongs to user
$unit = get_single_result("SELECT * FROM units WHERE unit_id = ? AND host_id = ?", [$unit_id, $_SESSION['user_id']]);

if (!$unit) {
    echo json_encode(['success' => false, 'error' => 'Unit not found or access denied']);
    exit;
}

try {
    $img_fp = new ImageFingerprinting($conn);
    $similarity_results = $img_fp->findSimilarImages($image_path, $unit_id);
    
    // Save to database
    $stmt = $conn->prepare("
        INSERT INTO unit_images (unit_id, image_path, image_hash, room_type, created_at)
        VALUES (?, ?, ?, 'unit_photo', NOW())
    ");
    $hash = md5($image_path);
    $stmt->bind_param("iss", $unit_id, $image_path, $hash);
    $stmt->execute();
    $image_id = $stmt->insert_id;
    
    // Compute and save fingerprint
    $ahash = $img_fp->generateAHash($image_path, 8);
    $stmt = $conn->prepare("
        INSERT INTO image_fingerprints (image_id, ahash, similarity_score, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $score = 0;
    $stmt->bind_param("isi", $image_id, $ahash, $score);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'image_id' => $image_id,
        'similarity_results' => $similarity_results
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
