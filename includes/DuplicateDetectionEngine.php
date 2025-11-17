<?php
/**
 * DuplicateDetectionEngine Class
 * Main orchestrator for all 6 duplicate listing detection methods
 * 
 * Runs all verification checks and generates comprehensive risk assessment
 */

class DuplicateDetectionEngine {
    public $conn;
    public $address_checker;
    public $geolocation_checker;
    public $image_checker;
    public $contact_checker;
    public $identity_checker;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        
        // Initialize all detection modules
        require_once __DIR__ . '/AddressVerification.php';
        require_once __DIR__ . '/GeolocationValidation.php';
        require_once __DIR__ . '/ImageFingerprinting.php';
        require_once __DIR__ . '/HostContactVerification.php';
        require_once __DIR__ . '/HostIdentityVerification.php';
        
        $this->address_checker = new AddressVerification($database_connection);
        $this->geolocation_checker = new GeolocationValidation($database_connection);
        $this->image_checker = new ImageFingerprinting($database_connection);
        $this->contact_checker = new HostContactVerification($database_connection);
        $this->identity_checker = new HostIdentityVerification($database_connection);
    }
    
    /**
     * Run complete duplicate detection for a listing
     */
    public function analyzeUnitForDuplicates($unit_id, $unit_data) {
        $analysis = [
            'unit_id' => $unit_id,
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => [],
            'overall_risk' => 0,
            'duplicates_found' => [],
            'flags' => []
        ];
        
        // ✅ 1. Address Verification Check
        if (isset($unit_data['building_name'], $unit_data['street_address'], $unit_data['city'])) {
            $address_results = $this->checkAddressVerification(
                $unit_id,
                $unit_data['building_name'],
                $unit_data['street_address'],
                $unit_data['unit_number'] ?? '',
                $unit_data['city']
            );
            $analysis['checks']['address'] = $address_results;
            
            if (!empty($address_results['matches'])) {
                $analysis['duplicates_found'] = array_merge(
                    $analysis['duplicates_found'],
                    $address_results['matches']
                );
                $analysis['flags'][] = [
                    'type' => 'address',
                    'severity' => 'CRITICAL',
                    'message' => "Someone has already listed a property at this address.",
                    'count' => count($address_results['matches'])
                ];
                $analysis['overall_risk'] += 40;
            }
        }
        
        // ✅ 2. Map Pin + Geolocation Validation
        if (isset($unit_data['latitude'], $unit_data['longitude'])) {
            $location_results = $this->checkGeolocationValidation(
                $unit_id,
                $unit_data['latitude'],
                $unit_data['longitude']
            );
            $analysis['checks']['geolocation'] = $location_results;
            
            if (!empty($location_results['exact_matches'])) {
                $analysis['flags'][] = [
                    'type' => 'geolocation_exact',
                    'severity' => 'CRITICAL',
                    'message' => "Two listings share the same GPS coordinates - likely duplicate.",
                    'count' => count($location_results['exact_matches'])
                ];
                $analysis['overall_risk'] += 35;
            } elseif (!empty($location_results['nearby'])) {
                $analysis['flags'][] = [
                    'type' => 'geolocation_nearby',
                    'severity' => 'HIGH',
                    'message' => "Multiple listings found in very close proximity.",
                    'count' => count($location_results['nearby'])
                ];
                $analysis['overall_risk'] += 20;
            }
        }
        
        // ✅ 3. Image Fingerprinting / Similarity Check
        if (isset($unit_data['image_paths'])) {
            $image_results = $this->checkImageFingerprinting($unit_id, $unit_data['image_paths']);
            $analysis['checks']['images'] = $image_results;
            
            if ($image_results['match_count'] > 0) {
                $severity = $image_results['match_count'] >= 5 ? 'CRITICAL' : 'HIGH';
                $analysis['flags'][] = [
                    'type' => 'image_similarity',
                    'severity' => $severity,
                    'message' => "Reused photos detected - same furniture, angles, or room layouts.",
                    'count' => $image_results['match_count'],
                    'similarity_score' => $image_results['average_similarity']
                ];
                $analysis['overall_risk'] += ($image_results['match_count'] >= 5 ? 25 : 15);
            }
        }
        
        // ✅ 4. Phone Number + Host Account Cross-Check
        if (isset($unit_data['host_id'])) {
            $contact_results = $this->checkPhoneEmailCrossing($unit_data['host_id']);
            $analysis['checks']['contact'] = $contact_results;
            
            if ($contact_results['linked_hosts_count'] > 0) {
                $analysis['flags'][] = [
                    'type' => 'contact_cross_check',
                    'severity' => 'CRITICAL',
                    'message' => "Host phone/email/payout linked to other accounts with multiple properties.",
                    'linked_hosts' => $contact_results['linked_hosts_count'],
                    'properties' => $contact_results['total_linked_properties']
                ];
                $analysis['overall_risk'] += 30;
            }
        }
        
        // ✅ 5. Host Identity Verification
        if (isset($unit_data['host_id'])) {
            $identity_results = $this->checkHostIdentityVerification($unit_data['host_id']);
            $analysis['checks']['identity'] = $identity_results;
            
            if (!$identity_results['is_verified']) {
                $analysis['flags'][] = [
                    'type' => 'identity_unverified',
                    'severity' => 'MEDIUM',
                    'message' => "Host has not completed all required identity verifications.",
                    'verification_score' => $identity_results['verification_score'],
                    'progress' => $identity_results['progress'] . '%'
                ];
                $analysis['overall_risk'] += 10;
            }
        }
        
        // Calculate final risk score
        $analysis['overall_risk'] = min(100, $analysis['overall_risk']);
        $analysis['risk_level'] = $this->getRiskLevel($analysis['overall_risk']);

        // Keep a copy of the submitted unit data for later use (safely pass-through)
        $analysis['unit_data'] = $unit_data;

        // ✅ 6. Auto-flag if needed (manual review will be required)
        if ($analysis['overall_risk'] >= 70) {
            $this->addToSuspiciousQueue($unit_id, $analysis);
        }
        
        return $analysis;
    }
    
    /**
     * Check address verification
     */
    private function checkAddressVerification($unit_id, $building, $street, $unit, $city) {
        $exact_matches = $this->address_checker->checkAddressExists($building, $street, $unit, $city, $unit_id);
        
        $result = [
            'checked' => true,
            'matches' => $exact_matches,
            'match_count' => count($exact_matches)
        ];
        
        if (!empty($exact_matches)) {
            foreach ($exact_matches as $match) {
                $this->address_checker->logDuplicateDetection(
                    $unit_id,
                    $match['unit_id'],
                    100,
                    [
                        'building' => $building,
                        'street' => $street,
                        'unit' => $unit,
                        'city' => $city
                    ]
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Check geolocation validation
     */
    private function checkGeolocationValidation($unit_id, $latitude, $longitude) {
        $exact_matches = $this->geolocation_checker->checkExactCoordinateMatch($latitude, $longitude, $unit_id);
        $nearby = $this->geolocation_checker->checkNearbyListings($latitude, $longitude, $unit_id);
        
        // Ensure all expected keys exist
        $nearby_list = $nearby['nearby'] ?? [];
        $search_radius = $nearby['search_radius'] ?? 50;
        
        $result = [
            'checked' => true,
            'exact_matches' => $exact_matches,
            'nearby' => $nearby_list,
            'search_radius' => $search_radius
        ];
        
        if (!empty($exact_matches)) {
            foreach ($exact_matches as $match) {
                $this->geolocation_checker->logGeolocationDuplicate($unit_id, $match['unit_id'], 0);
            }
        }
        
        return $result;
    }
    
    /**
     * Check image fingerprinting
     */
    private function checkImageFingerprinting($unit_id, $image_paths) {
        $all_matches = [];
        $total_similarity = 0;
        
        if (!is_array($image_paths)) {
            $image_paths = [$image_paths];
        }
        
        foreach ($image_paths as $image_path) {
            $matches = $this->image_checker->findSimilarImages($image_path, $unit_id);
            $all_matches = array_merge($all_matches, $matches);
            
            if (!empty($matches)) {
                $total_similarity += array_sum(array_column($matches, 'similarity'));
            }
        }
        
        $match_count = count(array_unique(array_column($all_matches, 'unit_id')));
        $avg_similarity = !empty($all_matches) ? round($total_similarity / count($all_matches), 2) : 0;
        
        return [
            'checked' => true,
            'matches' => $all_matches,
            'match_count' => $match_count,
            'average_similarity' => $avg_similarity
        ];
    }
    
    /**
     * Check phone/email cross-check
     */
    private function checkPhoneEmailCrossing($host_id) {
        $linked = $this->contact_checker->getLinkedHosts($host_id);
        $linked_listings = $this->contact_checker->getLinkedHostListings($host_id);
        
        $result = [
            'checked' => true,
            'linked_hosts_count' => count($linked),
            'linked_hosts' => $linked,
            'total_linked_properties' => count($linked_listings),
            'properties' => $linked_listings
        ];
        
        return $result;
    }
    
    /**
     * Check host identity verification
     */
    private function checkHostIdentityVerification($host_id) {
        $verification = $this->identity_checker->getVerificationStatus($host_id);
        
        if (!$verification) {
            $this->identity_checker->initializeVerification($host_id);
            $verification = $this->identity_checker->getVerificationStatus($host_id);
        }
        
        $result = [
            'checked' => true,
            'verification_score' => $verification['verification_score'] ?? 0,
            'progress' => $this->identity_checker->getVerificationProgress($host_id),
            'is_verified' => ($verification['is_verified_host'] ?? 0) === 1,
            'id_verified' => $verification['id_verification_status'] === 'verified',
            'face_verified' => $verification['face_verification_status'] === 'verified',
            'profile_verified' => $verification['profile_verification_status'] === 'verified',
            'phone_verified' => ($verification['phone_number_verified'] ?? 0) === 1,
            'email_verified' => ($verification['email_verified'] ?? 0) === 1,
            'payout_verified' => ($verification['payout_verified'] ?? 0) === 1
        ];
        
        return $result;
    }
    
    /**
     * Get risk level label
     */
    private function getRiskLevel($risk_score) {
        if ($risk_score >= 80) return 'CRITICAL';
        if ($risk_score >= 60) return 'HIGH';
        if ($risk_score >= 40) return 'MEDIUM';
        if ($risk_score >= 20) return 'LOW';
        return 'MINIMAL';
    }
    
    /**
     * Add unit to suspicious listings queue for manual review
     */
    private function addToSuspiciousQueue($unit_id, $analysis) {
        // Try to find host_id for the unit; if unit not yet created, fall back to provided unit_data
        $host_id = null;
        $res = $this->conn->query("SELECT host_id FROM units WHERE unit_id = " . intval($unit_id));
        if ($res) {
            $row = $res->fetch_assoc();
            if ($row && isset($row['host_id'])) {
                $host_id = (int)$row['host_id'];
            }
        }

        if (empty($host_id) && isset($analysis['unit_data']['host_id'])) {
            $host_id = (int)$analysis['unit_data']['host_id'];
        }

        $sql = "INSERT INTO suspicious_listings_queue 
                (unit_id, host_id, reason, overall_risk_score, comparison_data, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
                ON DUPLICATE KEY UPDATE
                    updated_at = NOW()";

        $reason = implode(' | ', array_column($analysis['flags'], 'message'));
        $comparison_data = json_encode($analysis);

        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $host_param = $host_id ?? 0;
            $risk_param = (int)($analysis['overall_risk'] ?? 0);
            $stmt->bind_param('iisss', $unit_id, $host_param, $reason, $risk_param, $comparison_data);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Get summary report for a unit
     */
    public function getAnalysisReport($unit_id) {
        $sql = "SELECT * FROM duplicate_detection_logs WHERE unit_id = ? ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param('i', $unit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['details']) {
                $row['details'] = json_decode($row['details'], true);
            }
            $logs[] = $row;
        }
        $stmt->close();
        
        return $logs;
    }
}
?>
