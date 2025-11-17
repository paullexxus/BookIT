<?php
/**
 * GeolocationValidation Class
 * Detects duplicate listings based on GPS coordinates
 * 
 * Checks:
 * - Exact coordinate matches
 * - Proximity matches (configurable radius)
 * - Coordinate validation
 * 
 * This is Airbnb's #2 duplicate detection method
 */

class GeolocationValidation {
    public $conn;
    public $default_proximity_radius = 50;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Set proximity radius for duplicate detection
     */
    public function setProximityRadius($meters) {
        $this->default_proximity_radius = max(10, min($meters, 500)); // 10-500m range
    }
    
    /**
     * Validate GPS coordinates format
     */
    public function validateCoordinates($latitude, $longitude) {
        $lat = floatval($latitude);
        $lon = floatval($longitude);
        
        // Valid GPS ranges: lat -90 to 90, lon -180 to 180
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return [
                'valid' => false,
                'error' => 'Invalid coordinate ranges',
                'latitude' => null,
                'longitude' => null
            ];
        }
        
        if ($lat == 0 && $lon == 0) {
            return [
                'valid' => false,
                'error' => 'Coordinates appear to be default (0, 0)',
                'latitude' => null,
                'longitude' => null
            ];
        }
        
        return [
            'valid' => true,
            'latitude' => $lat,
            'longitude' => $lon
        ];
    }
    
    /**
     * Generate coordinate hash for grouping similar locations
     */
    public function generateCoordinateHash($latitude, $longitude, $precision = 4) {
        // Round to precision decimal places to group nearby coordinates
        $rounded_lat = round($latitude, $precision);
        $rounded_lon = round($longitude, $precision);
        
        return hash('sha256', $rounded_lat . ',' . $rounded_lon);
    }
    
    /**
     * Calculate distance between two coordinates (Haversine formula)
     * Returns distance in meters
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius_m = 6371000; // Earth radius in meters
        
        $delta_lat = deg2rad($lat2 - $lat1);
        $delta_lon = deg2rad($lon2 - $lon1);
        
        $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($delta_lon / 2) * sin($delta_lon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earth_radius_m * $c;
    }
    
    /**
     * Check for nearby listings (within proximity radius)
     */
    public function checkNearbyListings($latitude, $longitude, $exclude_unit_id = null, $radius_meters = null) {
        $radius = $radius_meters ?? $this->default_proximity_radius;
        
        // Validate coordinates
        $validation = $this->validateCoordinates($latitude, $longitude);
        if (!$validation['valid']) {
            return [
                'nearby' => [],
                'error' => $validation['error']
            ];
        }
        
        // Get all units with geolocation data
        $sql = "SELECT u.unit_id, u.building_name, u.street_address, u.city,
                       u.latitude, u.longitude
                FROM units u
                WHERE u.latitude IS NOT NULL AND u.longitude IS NOT NULL";
        
        if ($exclude_unit_id) {
            $sql .= " AND u.unit_id != " . intval($exclude_unit_id);
        }
        
        $result = $this->conn->query($sql);
        
        $nearby = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $distance = $this->calculateDistance(
                    $validation['latitude'], 
                    $validation['longitude'],
                    $row['latitude'],
                    $row['longitude']
                );
                
                if ($distance <= $radius) {
                    $row['distance_meters'] = round($distance, 2);
                    $row['distance_km'] = round($distance / 1000, 3);
                    $nearby[] = $row;
                }
            }
        }
        
        // Sort by distance
        usort($nearby, fn($a, $b) => $a['distance_meters'] <=> $b['distance_meters']);
        
        return [
            'nearby' => $nearby,
            'error' => null,
            'search_radius' => $radius,
            'search_latitude' => $validation['latitude'],
            'search_longitude' => $validation['longitude']
        ];
    }
    
    /**
     * Register geolocation for a unit
     */
    public function registerGeolocation($unit_id, $latitude, $longitude) {
        // Validate coordinates
        $validation = $this->validateCoordinates($latitude, $longitude);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error']
            ];
        }

        $coordinate_hash = $this->generateCoordinateHash($validation['latitude'], $validation['longitude']);
        
        // Update the units table directly with coordinates
        $sql = "UPDATE units 
                SET latitude = ?, 
                    longitude = ?, 
                    address_hash = ?
                WHERE unit_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in registerGeolocation: " . $this->conn->error);
            return [
                'success' => false,
                'error' => 'Database error'
            ];
        }
        
        $stmt->bind_param(
            'dssi',
            $validation['latitude'],
            $validation['longitude'],
            $coordinate_hash,
            $unit_id
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return [
            'success' => $result,
            'latitude' => $validation['latitude'],
            'longitude' => $validation['longitude'],
            'coordinate_hash' => $coordinate_hash
        ];
    }
    
    /**
     * Detect exact coordinate match (same GPS pin)
     */
    public function checkExactCoordinateMatch($latitude, $longitude, $exclude_unit_id = null) {
        $sql = "SELECT u.unit_id, u.building_name, u.street_address, u.city,
                       u.latitude, u.longitude
                FROM units u
                WHERE u.latitude = ? AND u.longitude = ?";
        
        if ($exclude_unit_id) {
            $sql .= " AND u.unit_id != ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in checkExactCoordinateMatch: " . $this->conn->error);
            return [];
        }
        
        if ($exclude_unit_id) {
            $stmt->bind_param('ddi', $latitude, $longitude, $exclude_unit_id);
        } else {
            $stmt->bind_param('dd', $latitude, $longitude);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $matches = [];
        while ($row = $result->fetch_assoc()) {
            $matches[] = $row;
        }
        $stmt->close();
        
        return $matches;
    }
    
    /**
     * Log geolocation duplicate detection
     */
    public function logGeolocationDuplicate($unit_id, $duplicate_unit_id, $distance_meters, $details = []) {
        $sql = "INSERT INTO duplicate_detection_logs 
                (unit_id, duplicate_unit_id, detection_type, severity, confidence_score, details)
                VALUES (?, ?, 'geolocation', 'high', ?, ?)";
        
        // Confidence score based on distance (closer = more confident)
        $confidence = 100 - min(($distance_meters / 50) * 20, 40); // 100% at exact location, down to 60% at 50m
        
        $details['distance_meters'] = $distance_meters;
        $details_json = json_encode($details);
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in logGeolocationDuplicate: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('iids', $unit_id, $duplicate_unit_id, $confidence, $details_json);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get geolocation records for a unit
     */
    public function getGeolocationRecords($unit_id) {
        $sql = "SELECT ddl.*, u_dup.unit_number as duplicate_unit_number,
                       h.full_name as host_name, h.email as host_email
                FROM duplicate_detection_logs ddl
                LEFT JOIN units u_dup ON ddl.duplicate_unit_id = u_dup.unit_id
                LEFT JOIN users h ON u_dup.host_id = h.user_id
                WHERE ddl.unit_id = ? AND ddl.detection_type = 'geolocation'
                ORDER BY ddl.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in getGeolocationRecords: " . $this->conn->error);
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
    
    /**
     * Get units in specific geographic area
     */
    public function getUnitsInArea($center_lat, $center_lon, $radius_meters = null) {
        $radius = $radius_meters ?? $this->default_proximity_radius;
        
        // Use approximate bounding box first for efficiency
        $lat_delta = $radius / 111000; // 1 degree latitude ≈ 111km
        $lon_delta = $radius / (111000 * cos(deg2rad($center_lat))); // adjust by latitude
        
        $sql = "SELECT u.unit_id, u.building_name, u.street_address, u.city,
                       u.latitude, u.longitude
                FROM units u
                WHERE u.latitude IS NOT NULL 
                  AND u.longitude IS NOT NULL
                  AND u.latitude BETWEEN (? - ?) AND (? + ?)
                  AND u.longitude BETWEEN (? - ?) AND (? + ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in getUnitsInArea: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param('dddddddd', 
            $center_lat, $lat_delta,
            $center_lat, $lat_delta,
            $center_lon, $lon_delta,
            $center_lon, $lon_delta
        );
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $units = [];
        while ($row = $result->fetch_assoc()) {
            $distance = $this->calculateDistance(
                $center_lat, $center_lon,
                $row['latitude'], $row['longitude']
            );
            
            if ($distance <= $radius) {
                $row['distance_meters'] = round($distance, 2);
                $units[] = $row;
            }
        }
        $stmt->close();
        
        usort($units, fn($a, $b) => $a['distance_meters'] <=> $b['distance_meters']);
        
        return $units;
    }
}
?>
