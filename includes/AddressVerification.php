<?php
/**
 * AddressVerification Class
 * Detects duplicate listings based on exact address matching
 * 
 * Checks:
 * - Complete address (building + street + unit)
 * - Building name
 * - Street address
 * - Unit number
 * 
 * This is Airbnb's #1 duplicate detection method
 */

class AddressVerification {
    public $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Normalize address for comparison
     * Removes extra spaces, converts to lowercase, standardizes abbreviations
     */
    public function normalizeAddress($address) {
        // Convert to lowercase
        $normalized = strtolower(trim($address));
        
        // Remove extra spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        // Standardize common abbreviations
        $abbreviations = [
            'avenue' => 'ave',
            'street' => 'st',
            'road' => 'rd',
            'boulevard' => 'blvd',
            'drive' => 'dr',
            'lane' => 'ln',
            'court' => 'ct',
            'circle' => 'cir',
            'building' => 'bldg',
            'apartment' => 'apt',
            'suite' => 'ste',
            'unit' => 'u',
            'floor' => 'fl'
        ];
        
        foreach ($abbreviations as $full => $abbr) {
            $normalized = str_replace(' ' . $full . ' ', ' ' . $abbr . ' ', $normalized);
            $normalized = str_replace(' ' . $full, ' ' . $abbr, $normalized);
        }
        
        // Remove special characters except hyphens and numbers
        $normalized = preg_replace('/[^a-z0-9\-\s]/i', '', $normalized);
        
        return trim($normalized);
    }
    
    /**
     * Generate address hash for quick comparison
     */
    public function generateAddressHash($building, $street, $unit, $city = '') {
        $combined = $this->normalizeAddress(
            $building . ' ' . $street . ' ' . $unit . ' ' . $city
        );
        return hash('sha256', $combined);
    }
    
    /**
     * Check if address already exists in system
     */
    public function checkAddressExists($building, $street, $unit, $city, $exclude_unit_id = null) {
        $address_hash = $this->generateAddressHash($building, $street, $unit, $city);
        
        $sql = "SELECT u.unit_id, u.building_name, u.street_address, u.city, 
                       h.full_name as host_name, h.email as host_email
                FROM units u
                LEFT JOIN users h ON u.host_id = h.user_id
                WHERE u.address_hash = ?";
        
        $params = [$address_hash];
        
        if ($exclude_unit_id) {
            $sql .= " AND u.unit_id != ?";
            $params[] = $exclude_unit_id;
        }
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in checkAddressExists: " . $this->conn->error);
            return [];
        }
        
        $types = str_repeat('i', count($params));
        $types = str_replace('s', 's', 's' . str_repeat('i', count($params) - 1));
        
        $bind_params = [$types];
        foreach ($params as $val) {
            $bind_params[] = &$val;
        }
        
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $duplicates = [];
        while ($row = $result->fetch_assoc()) {
            $duplicates[] = $row;
        }
        $stmt->close();
        
        return $duplicates;
    }
    
    /**
     * Register address with verification
     */
    public function registerAddress($unit_id, $building, $street, $unit, $city, $postal_code = '') {
        $address_hash = $this->generateAddressHash($building, $street, $unit, $city);
        $normalized = $this->normalizeAddress($building . ' ' . $street . ' ' . $unit . ' ' . $city);
        
        $sql = "INSERT INTO address_verification 
                (unit_id, building_name, street_address, unit_number, city, postal_code, 
                 full_address, address_hash, normalized_address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    building_name = VALUES(building_name),
                    street_address = VALUES(street_address),
                    unit_number = VALUES(unit_number),
                    normalized_address = VALUES(normalized_address),
                    updated_at = NOW()";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in registerAddress: " . $this->conn->error);
            return false;
        }
        
        $full_address = trim($building . ' ' . $street . ' ' . $unit . ' ' . $city);
        
        $stmt->bind_param(
            'isssssss',
            $unit_id, $building, $street, $unit, $city, $postal_code,
            $full_address, $address_hash, $normalized
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Find similar addresses (fuzzy matching for typos/variations)
     */
    public function findSimilarAddresses($building, $street, $unit, $city, $exclude_unit_id = null, $threshold = 80) {
        // First get exact matches
        $exact_matches = $this->checkAddressExists($building, $street, $unit, $city, $exclude_unit_id);
        
        if (!empty($exact_matches)) {
            return [
                'exact_matches' => $exact_matches,
                'similar_matches' => [],
                'match_type' => 'exact'
            ];
        }
        
        // If no exact matches, try fuzzy matching
        $normalized = $this->normalizeAddress($building . ' ' . $street . ' ' . $unit . ' ' . $city);
        
        $sql = "SELECT u.unit_id, u.building_name, u.street_address, u.city,
                       h.full_name as host_name, h.email as host_email,
                       av.normalized_address
                FROM address_verification av
                LEFT JOIN units u ON av.unit_id = u.unit_id
                LEFT JOIN users h ON u.host_id = h.user_id";
        
        if ($exclude_unit_id) {
            $sql .= " WHERE av.unit_id != ?";
        }
        
        $result = $this->conn->query($sql);
        
        $similar = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $similarity = $this->calculateSimilarity($normalized, $row['normalized_address']);
                
                if ($similarity >= $threshold) {
                    $row['similarity_score'] = $similarity;
                    $similar[] = $row;
                }
            }
        }
        
        // Sort by similarity score descending
        usort($similar, fn($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);
        
        return [
            'exact_matches' => [],
            'similar_matches' => $similar,
            'match_type' => 'similar'
        ];
    }
    
    /**
     * Calculate similarity between two strings (0-100)
     */
    private function calculateSimilarity($str1, $str2) {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        
        if ($len1 == 0 && $len2 == 0) return 100;
        if ($len1 == 0 || $len2 == 0) return 0;
        
        $lev = levenshtein($str1, $str2);
        $max_len = max($len1, $len2);
        
        return round((1 - $lev / $max_len) * 100, 2);
    }
    
    /**
     * Get all addresses by a specific host
     */
    public function getHostAddresses($host_id) {
        $sql = "SELECT av.*, u.unit_id, u.unit_number
                FROM address_verification av
                JOIN units u ON av.unit_id = u.unit_id
                WHERE u.host_id = ?
                ORDER BY av.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in getHostAddresses: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param('i', $host_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $addresses = [];
        while ($row = $result->fetch_assoc()) {
            $addresses[] = $row;
        }
        $stmt->close();
        
        return $addresses;
    }
    
    /**
     * Log duplicate detection event
     */
    public function logDuplicateDetection($unit_id, $duplicate_unit_id, $confidence_score, $details = []) {
        $sql = "INSERT INTO duplicate_detection_logs 
                (unit_id, duplicate_unit_id, detection_type, severity, confidence_score, details, action_taken)
                VALUES (?, ?, 'address', 'critical', ?, ?, 'flagged')";
        
        // Determine severity based on confidence
        $severity = $confidence_score >= 95 ? 'critical' : ($confidence_score >= 80 ? 'high' : 'medium');
        
        $details_json = json_encode($details);
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in logDuplicateDetection: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('iids', $unit_id, $duplicate_unit_id, $confidence_score, $details_json);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get duplicate detection records for a unit
     */
    public function getDuplicateRecords($unit_id) {
        $sql = "SELECT ddl.*, u_dup.unit_number as duplicate_unit_number, 
                       h.full_name as host_name, h.email as host_email
                FROM duplicate_detection_logs ddl
                LEFT JOIN units u_dup ON ddl.duplicate_unit_id = u_dup.unit_id
                LEFT JOIN users h ON u_dup.host_id = h.user_id
                WHERE ddl.unit_id = ? AND ddl.detection_type = 'address'
                ORDER BY ddl.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in getDuplicateRecords: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param('i', $unit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        $stmt->close();
        
        return $records;
    }
}
?>
