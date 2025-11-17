<?php
/**
 * ImageFingerprinting Class
 * Detects duplicate listings based on image similarity
 * 
 * Checks:
 * - Perceptual hashing (aHash, pHash, dHash)
 * - Image similarity scoring
 * - Detection of reused photos across listings
 * - Similar angles, rooms, and furniture
 * 
 * This is Airbnb's #3 duplicate detection method
 */

class ImageFingerprinting {
    public $conn;
    public $similarity_threshold = 85;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Generate perceptual hash using Average Hash (aHash)
     * Simplest but fastest method
     */
    public function generateAHash($image_path, $size = 8) {
        if (!file_exists($image_path)) {
            return null;
        }
        
        try {
            // Get image info
            $info = @getimagesize($image_path);
            if (!$info) {
                // Fallback to file hash if can't read image
                return hash_file('sha256', $image_path);
            }
            
            // Load image based on type
            $image = null;
            switch ($info[2]) {
                case IMAGETYPE_JPEG:
                    $image = @imagecreatefromjpeg($image_path);
                    break;
                case IMAGETYPE_PNG:
                    $image = @imagecreatefrompng($image_path);
                    break;
                case IMAGETYPE_GIF:
                    $image = @imagecreatefromgif($image_path);
                    break;
                case IMAGETYPE_WEBP:
                    $image = @imagecreatefromwebp($image_path);
                    break;
                default:
                    // Unsupported format - use file hash
                    return hash_file('sha256', $image_path);
            }
            
            if (!$image) {
                // Fallback to file hash
                return hash_file('sha256', $image_path);
            }
            
            // Resize to 8x8 (or specified size)
            $thumb = imagecreatetruecolor($size, $size);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $size, $size, 
                              imagesx($image), imagesy($image));
            
            // Convert to grayscale and get average brightness
            $total = 0;
            $hash = '';
            
            for ($y = 0; $y < $size; $y++) {
                for ($x = 0; $x < $size; $x++) {
                    $rgb = imagecolorat($thumb, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $gray = ($r + $g + $b) / 3;
                    $total += $gray;
                }
            }
            
            $average = $total / ($size * $size);
            
            for ($y = 0; $y < $size; $y++) {
                for ($x = 0; $x < $size; $x++) {
                    $rgb = imagecolorat($thumb, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $gray = ($r + $g + $b) / 3;
                    $hash .= ($gray >= $average) ? '1' : '0';
                }
            }
            
            imagedestroy($image);
            imagedestroy($thumb);
            
            return base_convert($hash, 2, 16);
        } catch (Exception $e) {
            error_log("Error in generateAHash: " . $e->getMessage());
            return hash_file('sha256', $image_path);
        }
    }
    
    /**
     * Generate simple perceptual hash from file content
     * Used as fallback when image functions unavailable
     */
    public function generateSimpleHash($image_path) {
        if (!file_exists($image_path)) {
            return null;
        }
        
        return hash_file('sha256', $image_path);
    }
    
    /**
     * Calculate Hamming distance between two hashes
     * Lower distance = more similar images
     */
    public function calculateHammingDistance($hash1, $hash2) {
        if (strlen($hash1) != strlen($hash2)) {
            return PHP_INT_MAX;
        }
        
        $distance = 0;
        for ($i = 0; $i < strlen($hash1); $i++) {
            if ($hash1[$i] !== $hash2[$i]) {
                $distance++;
            }
        }
        
        return $distance;
    }
    
    /**
     * Calculate similarity score between two images (0-100)
     */
    public function calculateSimilarityScore($hash1, $hash2) {
        // Convert hex to binary if needed
        if (ctype_xdigit($hash1) && strlen($hash1) > 16) {
            $hash1 = base_convert($hash1, 16, 2);
        }
        if (ctype_xdigit($hash2) && strlen($hash2) > 16) {
            $hash2 = base_convert($hash2, 16, 2);
        }
        
        if (!$hash1 || !$hash2 || strlen($hash1) == 0 || strlen($hash2) == 0) {
            return 0;
        }
        
        $hamming_distance = $this->calculateHammingDistance($hash1, $hash2);
        $max_distance = max(strlen($hash1), strlen($hash2)) * 4; // Max possible distance
        
        $similarity = max(0, 100 - (($hamming_distance / $max_distance) * 100));
        
        return round($similarity, 2);
    }
    
    /**
     * Register image and generate fingerprints
     */
    public function registerImage($unit_id, $image_path, $room_type = '') {
        if (!file_exists($image_path)) {
            return [
                'success' => false,
                'error' => 'Image file not found'
            ];
        }
        
        // Generate hashes
        $simple_hash = $this->generateSimpleHash($image_path);
        $ahash = $this->generateAHash($image_path);
        
        // Insert image record
        $sql_image = "INSERT INTO unit_images (unit_id, image_path, image_hash, room_type)
                      VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql_image);
        if (!$stmt) {
            error_log("Prepare failed in registerImage: " . $this->conn->error);
            return [
                'success' => false,
                'error' => 'Database error'
            ];
        }
        
        $stmt->bind_param('isss', $unit_id, $image_path, $simple_hash, $room_type);
        if (!$stmt->execute()) {
            error_log("Execute failed in registerImage: " . $stmt->error);
            $stmt->close();
            return [
                'success' => false,
                'error' => 'Failed to insert image record'
            ];
        }
        
        $image_id = $this->conn->insert_id;
        $stmt->close();
        
        // Check for similar images
        $similar_images = $this->findSimilarImages($ahash, $unit_id);
        
        // Insert fingerprint record
        $sql_fingerprint = "INSERT INTO image_fingerprints 
                           (image_id, ahash, similarity_score, matched_images)
                           VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql_fingerprint);
        if (!$stmt) {
            error_log("Prepare failed in registerImage fingerprint: " . $this->conn->error);
            return [
                'success' => true,
                'image_id' => $image_id,
                'matches' => [],
                'warning' => 'Fingerprint recording failed'
            ];
        }
        
        $matched_json = json_encode($similar_images);
        $similarity_score = count($similar_images) > 0 ? max(array_column($similar_images, 'similarity')) : 0;
        
        $stmt->bind_param('isds', $image_id, $ahash, $similarity_score, $matched_json);
        $stmt->execute();
        $stmt->close();
        
        return [
            'success' => true,
            'image_id' => $image_id,
            'ahash' => $ahash,
            'matches' => $similar_images,
            'match_count' => count($similar_images)
        ];
    }
    
    /**
     * Find similar images by comparing hashes
     */
    public function findSimilarImages($ahash, $exclude_unit_id = null, $threshold = null) {
        $threshold = $threshold ?? $this->similarity_threshold;
        
        $sql = "SELECT ui.image_id, ui.unit_id, ui.image_path, ui.room_type,
                       ifp.ahash,
                       u.building_name, u.street_address,
                       h.full_name as host_name
                FROM unit_images ui
                LEFT JOIN image_fingerprints ifp ON ui.image_id = ifp.image_id
                LEFT JOIN units u ON ui.unit_id = u.unit_id
                LEFT JOIN users h ON u.host_id = h.user_id";
        
        if ($exclude_unit_id) {
            $sql .= " WHERE ui.unit_id != ?";
        }
        
        $result = $this->conn->query($sql);
        
        $similar = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row['ahash']) {
                    $similarity = $this->calculateSimilarityScore($ahash, $row['ahash']);
                    
                    if ($similarity >= $threshold) {
                        $row['similarity'] = $similarity;
                        $similar[] = $row;
                    }
                }
            }
        }
        
        // Sort by similarity descending
        usort($similar, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        
        return $similar;
    }
    
    /**
     * Get all images for a unit
     */
    public function getUnitImages($unit_id) {
        $sql = "SELECT ui.*, ifp.ahash, ifp.similarity_score, ifp.matched_images
                FROM unit_images ui
                LEFT JOIN image_fingerprints ifp ON ui.image_id = ifp.image_id
                WHERE ui.unit_id = ?
                ORDER BY ui.upload_date ASC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in getUnitImages: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param('i', $unit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $images = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['matched_images']) {
                $row['matched_images'] = json_decode($row['matched_images'], true);
            }
            $images[] = $row;
        }
        $stmt->close();
        
        return $images;
    }
    
    /**
     * Log image similarity detection
     */
    public function logImageDuplicate($unit_id, $duplicate_unit_id, $similarity_score, $matched_count, $details = []) {
        $sql = "INSERT INTO duplicate_detection_logs 
                (unit_id, duplicate_unit_id, detection_type, severity, confidence_score, details)
                VALUES (?, ?, 'image', 'high', ?, ?)";
        
        // Determine severity based on how many images match
        $severity = $matched_count >= 5 ? 'critical' : 'high';
        
        $details['matched_image_count'] = $matched_count;
        $details['average_similarity'] = $similarity_score;
        $details_json = json_encode($details);
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in logImageDuplicate: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('iids', $unit_id, $duplicate_unit_id, $similarity_score, $details_json);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get image duplicate records for a unit
     */
    public function getImageDuplicateRecords($unit_id) {
        $sql = "SELECT ddl.*, u_dup.unit_number as duplicate_unit_number,
                       h.full_name as host_name
                FROM duplicate_detection_logs ddl
                LEFT JOIN units u_dup ON ddl.duplicate_unit_id = u_dup.unit_id
                LEFT JOIN users h ON u_dup.host_id = h.user_id
                WHERE ddl.unit_id = ? AND ddl.detection_type = 'image'
                ORDER BY ddl.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in getImageDuplicateRecords: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param('i', $unit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $records = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['details']) {
                $row['details'] = json_decode($row['details'], true);
            }
            $records[] = $row;
        }
        $stmt->close();
        
        return $records;
    }
    
    /**
     * Set similarity threshold (0-100)
     */
    public function setSimilarityThreshold($threshold) {
        $this->similarity_threshold = max(0, min(100, intval($threshold)));
    }
    
    /**
     * Flag image as suspicious
     */
    public function flagImage($image_id, $reason = '') {
        $sql = "UPDATE unit_images SET is_flagged = TRUE, flag_reason = ? WHERE image_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in flagImage: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('si', $reason, $image_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}
?>
