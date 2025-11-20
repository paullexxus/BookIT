<?php
// Host Unit Management
// Add, edit, delete, and manage host's condo units

include '../includes/session.php';
include '../includes/functions.php';
include_once '../config/db.php';
checkRole(['host']);

$host_id = $_SESSION['user_id'];
$action_message = '';
$action_success = false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = sanitize_input($_POST['action']);
        
        // Add New Unit
        if ($action === 'add_unit') {
            $unit_name = sanitize_input($_POST['unit_name']);
            $branch_id = sanitize_input($_POST['branch_id']);
            $description = sanitize_input($_POST['description'] ?? '');
            $price = sanitize_input($_POST['price']);
            $capacity = sanitize_input($_POST['capacity']);
            $status = sanitize_input($_POST['status'] ?? 'available');
            
            // Verify branch belongs to host
            $branch = get_single_result("
                SELECT b.branch_id FROM branches b 
                INNER JOIN units u ON b.branch_id = u.branch_id 
                WHERE b.branch_id = ? AND u.host_id = ?
                LIMIT 1
            ", [$branch_id, $host_id]);
            
            if (!$branch) {
                $branch = get_single_result("SELECT * FROM branches WHERE branch_id = ?", [$branch_id]);
            }
            
            // Prepare unit data for duplicate detection
            $unit_data = [
                'building_name' => $unit_name,
                'street_address' => $_POST['street_address'] ?? '',
                'unit_number' => $_POST['unit_number'] ?? '',
                'city' => $_POST['city'] ?? '',
                'latitude' => isset($_POST['latitude']) ? (float)$_POST['latitude'] : null,
                'longitude' => isset($_POST['longitude']) ? (float)$_POST['longitude'] : null
            ];
            
            // Check for duplicates
            include_once '../includes/DuplicateDetectionEngine.php';
            $engine = new DuplicateDetectionEngine($conn);
            $analysis = $engine->analyzeUnitForDuplicates(null, $unit_data);
            
            // Block if high risk
            if ($analysis['overall_risk'] >= 70) {
                $action_message = "⚠️ High Risk: This listing appears to be a duplicate. Risk Score: " . round($analysis['overall_risk']) . "/100";
                $action_success = false;
            } else {
                // Insert unit with prepared statement
                $stmt = $conn->prepare("
                    INSERT INTO units (unit_name, host_id, branch_id, description, price, max_occupancy, is_available, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $is_available = ($status === 'available' ? 1 : 0);
                $stmt->bind_param("siisiis", $unit_name, $host_id, $branch_id, $description, $price, $capacity, $is_available);
                
                if ($stmt->execute()) {
                    $unit_id = $stmt->insert_id;
                    
                    // Handle photo uploads
                    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
                        $photos_dir = '../uploads/unit_images/';
                        if (!is_dir($photos_dir)) mkdir($photos_dir, 0755, true);
                        
                        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                            if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                                $ext = strtolower(pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                                    $filename = 'unit_' . $unit_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                                    $filepath = $photos_dir . $filename;
                                    
                                    if (move_uploaded_file($tmp_name, $filepath)) {
                                        // Compute image fingerprint
                                        include_once '../includes/ImageFingerprinting.php';
                                        $img_fp = new ImageFingerprinting($conn);
                                        $img_fp->registerImage($unit_id, $filepath, 'unit_photo');
                                    }
                                }
                            }
                        }
                    }
                    
                    $action_message = "Unit added successfully!";
                    $action_success = true;
                } else {
                    $action_message = "Failed to add unit: " . $stmt->error;
                    $action_success = false;
                }
            }
            
            $action_message = "Unit added successfully!";
            $action_success = true;
        }
        
        // Edit Unit
        else if ($action === 'edit_unit') {
            $unit_id = sanitize_input($_POST['unit_id']);
            $unit_name = sanitize_input($_POST['unit_name']);
            $description = sanitize_input($_POST['description'] ?? '');
            $price = sanitize_input($_POST['price']);
            $capacity = sanitize_input($_POST['capacity']);
            $status = sanitize_input($_POST['status']);
            $street_address = sanitize_input($_POST['street_address'] ?? '');
            $unit_number = sanitize_input($_POST['unit_number'] ?? '');
            $city = sanitize_input($_POST['city'] ?? '');
            $latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
            $longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;
            
            // Verify unit belongs to host
            $unit = get_single_result("SELECT * FROM units WHERE unit_id = ? AND host_id = ?", [$unit_id, $host_id]);
            
            if ($unit) {
                $stmt = $conn->prepare("
                    UPDATE units 
                    SET unit_name = ?, 
                        description = ?, 
                        price = ?, 
                        max_occupancy = ?,
                        is_available = ?
                    WHERE unit_id = ?
                ");
                $is_available = ($status === 'available' ? 1 : 0);
                $stmt->bind_param("siiiii", $unit_name, $description, $price, $capacity, $is_available, $unit_id);
                
                if ($stmt->execute()) {
                    // Update geolocation if provided
                    if ($latitude && $longitude) {
                        include_once '../includes/GeolocationValidation.php';
                        $geo = new GeolocationValidation($conn);
                        $geo->registerGeolocation($unit_id, $latitude, $longitude);
                    }
                    
                    $action_message = "Unit updated successfully!";
                    $action_success = true;
                } else {
                    $action_message = "Failed to update unit: " . $stmt->error;
                    $action_success = false;
                }
            } else {
                $action_message = "Unit not found or you don't have permission to edit it";
                $action_success = false;
            }
        }
        
        // Delete Unit
        else if ($action === 'delete_unit') {
            $unit_id = sanitize_input($_POST['unit_id']);
            
            // Verify unit belongs to host
            $unit = get_single_result("SELECT * FROM units WHERE unit_id = ? AND host_id = ?", [$unit_id, $host_id]);
            
            if ($unit) {
                $conn->query("DELETE FROM units WHERE unit_id = $unit_id");
                
                $action_message = "Unit deleted successfully!";
                $action_success = true;
            } else {
                $action_message = "Unit not found or you don't have permission to delete it";
            }
        }
    }
}

// Fetch all units for this host
$units = get_multiple_results("
    SELECT u.*, b.branch_name, COUNT(r.reservation_id) as total_bookings
    FROM units u
    INNER JOIN branches b ON u.branch_id = b.branch_id
    LEFT JOIN reservations r ON u.unit_id = r.unit_id AND r.status IN ('confirmed', 'checked_in')
    WHERE u.host_id = ?
    GROUP BY u.unit_id
    ORDER BY u.created_at DESC
", [$host_id]);

// Fetch branches for dropdown
$branches = get_multiple_results("
    SELECT DISTINCT b.* FROM branches b
    INNER JOIN units u ON b.branch_id = u.branch_id
    WHERE u.host_id = ?
", [$host_id]);

// If no branches from existing units, fetch all branches for the host
if (empty($branches)) {
    $branches = get_multiple_results("SELECT * FROM branches WHERE is_active = 1");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit Management - BookIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/sidebar-common.css">
    <link rel="stylesheet" href="../assets/css/host/unit_management.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
        }
        
        .main-container {
            display: flex;
            min-height: 100vh;
        }
        
        .content {
            flex: 1;
            padding: 30px;
            max-width: 100%;
            width: 100%;
            margin-left: 280px;
        }
    </style>
</head>
<body>
        <?php include_once __DIR__ . '/../includes/sidebar_init.php'; ?>
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <h1><i class="fas fa-building"></i> Unit Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openAddUnitModal()">
                        <i class="fas fa-plus"></i> Add New Unit
                    </button>
                </div>
            </div>
            
            <!-- Success/Error Alert -->
            <?php if ($action_message): ?>
            <div class="alert alert-<?php echo $action_success ? 'success' : 'danger'; ?>">
                <i class="fas fa-<?php echo $action_success ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($action_message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filters">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchInput" placeholder="Unit name or ID...">
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="statusFilter">
                        <option value="">All Status</option>
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Under Maintenance</option>
                    </select>
                </div>
                <div class="view-toggle" style="margin-left: auto;">
                    <button class="view-btn active" data-view="grid">
                        <i class="fas fa-th"></i> Grid
                    </button>
                    <button class="view-btn" data-view="list">
                        <i class="fas fa-list"></i> List
                    </button>
                </div>
            </div>
            
            <!-- Units Container -->
            <?php if (!empty($units)): ?>
            <div class="units-container" id="unitsContainer">
                <?php foreach ($units as $unit): 
                    $status = $unit['is_available'] ? 'available' : 'maintenance';
                    if ($unit['total_bookings'] > 0) {
                        $status = 'occupied';
                    }
                    $status_label = ucfirst(str_replace('_', ' ', $status));
                ?>
                <div class="unit-card" data-unit-id="<?php echo $unit['unit_id']; ?>" data-status="<?php echo $status; ?>" data-name="<?php echo strtolower($unit['unit_name']); ?>">
                    <div class="unit-image">
                        <i class="fas fa-image"></i>
                        <span class="unit-status status-<?php echo $status; ?>">
                            <?php echo $status_label; ?>
                        </span>
                    </div>
                    
                    <div class="unit-content">
                        <div class="unit-header">
                            <div class="unit-name"><?php echo htmlspecialchars($unit['unit_name']); ?></div>
                            <div class="unit-branch"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($unit['branch_name']); ?></div>
                        </div>
                        
                        <?php if ($unit['description']): ?>
                        <div style="font-size: 13px; color: #666; margin-bottom: 12px; line-height: 1.4;">
                            <?php echo htmlspecialchars(substr($unit['description'], 0, 80)) . (strlen($unit['description']) > 80 ? '...' : ''); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="unit-info">
                            <div class="info-item">
                                <div class="info-label">Rate per Night</div>
                                <div class="info-value">₱<?php echo number_format($unit['price']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Capacity</div>
                                <div class="info-value"><?php echo $unit['max_occupancy']; ?> Guests</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Total Bookings</div>
                                <div class="info-value"><?php echo $unit['total_bookings']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Last Updated</div>
                                <div class="info-value"><?php echo date('M d', strtotime($unit['created_at'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="unit-actions">
                            <button class="btn btn-info btn-sm" onclick="openViewModal(<?php echo $unit['unit_id']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="openEditModal(<?php echo $unit['unit_id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="openDeleteConfirm(<?php echo $unit['unit_id']; ?>, '<?php echo htmlspecialchars($unit['unit_name']); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-home"></i>
                <h2>No Units Yet</h2>
                <p>Start by adding your first condo unit to manage bookings and reservations.</p>
                <button class="btn btn-primary" onclick="openAddUnitModal()">
                </i> Add Your First Unit
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add/Edit Unit Modal -->
    <div id="unitModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Unit</h5>
                    <button type="button" class="btn-close" onclick="closeUnitModal()"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="unitForm">
                <input type="hidden" name="action" id="formAction" value="add_unit">
                <input type="hidden" name="unit_id" id="unitId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Branch</label>
                        <select name="branch_id" id="branchSelect" required>
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>">
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unit Name / No.</label>
                        <input type="text" name="unit_name" id="unitName" placeholder="e.g., Unit 101 or Penthouse" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Street Address</label>
                        <input type="text" name="street_address" id="streetAddress" placeholder="e.g., 123 Main Street" required>
                    </div>
                    <div class="form-group">
                        <label>Unit Number</label>
                        <input type="text" name="unit_number" id="unitNumber" placeholder="e.g., 101, 201, Suite A" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" id="city" placeholder="e.g., Manila, Makati" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-map"></i> Property Location</label>
                    <div id="unitMap" style="width: 100%; height: 300px; border-radius: 8px; border: 2px solid #dee2e6; margin-bottom: 10px; background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%); display: flex; align-items: center; justify-content: center;">
                        <div style="text-align: center; color: #666;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #3498db; margin-bottom: 10px; display: block;"></i>
                            <p>Loading map...</p>
                        </div>
                    </div>
                    <small class="form-text text-muted d-block mb-2">Click on map to select property location</small>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="text" class="form-control" id="latDisplay" placeholder="Latitude" readonly>
                        </div>
                        <div class="col-6">
                            <input type="hidden" name="longitude" id="longitude">
                            <input type="text" class="form-control" id="lngDisplay" placeholder="Longitude" readonly>
                        </div>
                    </div>
                </div>
                
                
                <div class="form-group">
                    <label><i class="fas fa-image"></i> Unit Photos</label>
                    <div style="border: 2px dashed #ddd; border-radius: 6px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s;" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #3498db; margin-bottom: 10px; display: block;"></i>
                        <div style="font-weight: 500; color: #2c3e50; margin-bottom: 5px;">Drag photos here or click to browse</div>
                        <div style="font-size: 12px; color: #999;">Support: JPG, PNG (Max 5MB per image)</div>
                        <input type="file" name="photos" id="photoInput" multiple accept="image/*" style="display: none;">
                    </div>
                    <div id="photoPreview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 15px;"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Rate per Night (₱)</label>
                        <input type="number" name="price" id="price" placeholder="e.g., 2500" required>
                    </div>
                    <div class="form-group">
                        <label>Capacity (Guests)</label>
                        <input type="number" name="capacity" id="capacity" placeholder="e.g., 4" min="1" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status" required>
                        <option value="available">Available</option>
                        <option value="maintenance">Under Maintenance</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Amenities</label>
                    <div class="amenities-list">
                        <div class="amenity-checkbox">
                            <input type="checkbox" name="amenities" value="WiFi" id="amenity-wifi">
                            <label for="amenity-wifi">WiFi</label>
                        </div>
                        <div class="amenity-checkbox">
                            <input type="checkbox" name="amenities" value="Air Conditioning" id="amenity-ac">
                            <label for="amenity-ac">Air Conditioning</label>
                        </div>
                        <div class="amenity-checkbox">
                            <input type="checkbox" name="amenities" value="Kitchen" id="amenity-kitchen">
                            <label for="amenity-kitchen">Kitchen</label>
                        </div>
                        <div class="amenity-checkbox">
                            <input type="checkbox" name="amenities" value="TV" id="amenity-tv">
                            <label for="amenity-tv">TV</label>
                        </div>
                        <div class="amenity-checkbox">
                            <input type="checkbox" name="amenities" value="Washing Machine" id="amenity-washer">
                            <label for="amenity-washer">Washing Machine</label>
                        </div>
                        <div class="amenity-checkbox">
                            <input type="checkbox" name="amenities" value="Pool" id="amenity-pool">
                            <label for="amenity-pool">Pool</label>
                        </div>
                        <div class="amenity-checkbox">
                            <input type="checkbox" name="amenities" value="Gym" id="amenity-gym">
                            <label for="amenity-gym">Gym</label>
                        </div>
                        <div class="amenity-checkbox">
                            <input type="checkbox" name="amenities" value="Parking" id="amenity-parking">
                            <label for="amenity-parking">Parking</label>
                        </div>
                    </div>
                </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeUnitModal()">Cancel</button>
                    <button type="submit" form="unitForm" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Unit
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Unit Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span>Unit Details</span>
                <button class="modal-close" onclick="closeViewModal()">&times;</button>
            </div>
            <div id="viewContent"></div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content confirmation-modal">
            <div class="modal-header" style="justify-content: center; margin-bottom: 30px;">
                <span><i class="fas fa-exclamation-triangle" style="color: #e74c3c; margin-right: 10px;"></i> Confirm Delete</span>
            </div>
            <p>Are you sure you want to delete <strong id="deleteUnitName"></strong>?</p>
            <p style="color: #999; font-size: 13px;">This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete_unit">
                <input type="hidden" name="unit_id" id="deleteUnitId">
                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteConfirm()" style="min-width: 120px;">Cancel</button>
                    <button type="submit" class="btn btn-danger" style="min-width: 120px;">Delete Unit</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/host/unit_management.js"></script>
    <script>
        // Declare callback before loading Maps
        function mapsCallback() {
            console.log('Google Maps loaded successfully');
        }
    </script>
    <!-- Google Maps disabled due to API key restrictions - using fallback -->
    <script>
        // Simulate Maps API for development - stub only
        window.google = window.google || {};
        window.google.maps = window.google.maps || null;
    </script>
    <script>
        function handleMapError() {
            console.warn('Google Maps unavailable. Form will work without map visualization.');
            // Don't replace the map element - let it stay as is for manual coordinate entry
        }
        
        // Call on page load
        document.addEventListener('DOMContentLoaded', function() {
            handleMapError();
        });
    </script>
    <script>
        // Modal Management Functions
        function openAddUnitModal() {
            document.getElementById('unitForm').reset();
            document.getElementById('formAction').value = 'add_unit';
            document.getElementById('modalTitle').textContent = 'Add New Unit';
            document.getElementById('unitId').value = '';
            mapInitialized = false;
            const unitModal = new bootstrap.Modal(document.getElementById('unitModal'));
            unitModal.show();
        }

        function openEditModal(unitId) {
            // Fetch unit data and populate form
            fetch(`../ajax/get_unit.php?unit_id=${unitId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const unit = data.unit;
                        document.getElementById('unitForm').reset();
                        document.getElementById('formAction').value = 'edit_unit';
                        document.getElementById('modalTitle').textContent = 'Edit Unit';
                        document.getElementById('unitId').value = unitId;
                        document.getElementById('unitName').value = unit.unit_name;
                        document.getElementById('branchSelect').value = unit.branch_id;
                        document.getElementById('streetAddress').value = unit.street_address || '';
                        document.getElementById('unitNumber').value = unit.unit_number || '';
                        document.getElementById('city').value = unit.city || '';
                        document.getElementById('latitude').value = unit.latitude || '';
                        document.getElementById('longitude').value = unit.longitude || '';
                        document.getElementById('latDisplay').value = unit.latitude || '';
                        document.getElementById('lngDisplay').value = unit.longitude || '';
                        document.getElementById('price').value = unit.price;
                        document.getElementById('capacity').value = unit.max_occupancy;
                        document.getElementById('status').value = unit.is_available ? 'available' : 'maintenance';
                        
                        mapInitialized = false;
                        const unitModal = new bootstrap.Modal(document.getElementById('unitModal'));
                        unitModal.show();
                    }
                });
        }

        function closeUnitModal() {
            const unitModal = bootstrap.Modal.getInstance(document.getElementById('unitModal'));
            if (unitModal) unitModal.hide();
        }

        function openViewModal(unitId) {
            fetch(`../ajax/get_unit_view.php?unit_id=${unitId}`)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('viewContent').innerHTML = html;
                    const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
                    viewModal.show();
                });
        }

        function closeViewModal() {
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewModal'));
            if (viewModal) viewModal.hide();
        }

        function openDeleteConfirm(unitId, unitName) {
            document.getElementById('deleteUnitName').textContent = unitName;
            document.getElementById('deleteUnitId').value = unitId;
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
    <script>
        // Wait for Google Maps to be loaded before initializing
        function waitForMaps() {
            if (typeof google !== 'undefined' && google.maps) {
                // Maps loaded, continue
                return;
            }
            setTimeout(waitForMaps, 100);
        }
        
        // Initialize map when modal opens
        let unitMap;
        let unitMarker;
        const defaultCenter = { lat: 14.5995, lng: 121.0855 }; // Manila, PH
        let mapInitialized = false;

        function initUnitMap() {
            if (mapInitialized || !document.getElementById('unitMap')) return;
            
            const mapElement = document.getElementById('unitMap');
            
            // Check if Maps is available
            if (!window.google || !window.google.maps) {
                console.warn('Google Maps not available - displaying placeholder');
                mapElement.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; flex-direction: column;"><i class="fas fa-map" style="font-size: 40px; color: #bbb; margin-bottom: 10px;"></i><p style="color: #666; margin: 0;">Map not available</p><p style="font-size: 12px; color: #999; margin: 5px 0 0 0;">Enter latitude and longitude manually</p></div>';
                mapInitialized = true;
                return;
            }
            
            try {
                // Verify Map constructor exists and is a function
                if (typeof window.google.maps.Map !== 'function') {
                    throw new Error('Google Maps Map is not available');
                }

                unitMap = new google.maps.Map(mapElement, {
                    zoom: 13,
                    center: defaultCenter,
                    mapTypeControl: true,
                    fullscreenControl: true,
                    streetViewControl: true
                });

                // Load saved coordinates if editing
                const savedLat = document.getElementById('latitude').value;
                const savedLng = document.getElementById('longitude').value;
                if (savedLat && savedLng) {
                    const savedPosition = { lat: parseFloat(savedLat), lng: parseFloat(savedLng) };
                    unitMap.setCenter(savedPosition);
                    placeUnitMarker(savedPosition);
                }

                // Click map to place marker
                unitMap.addListener('click', (e) => {
                    placeUnitMarker(e.latLng);
                });

                // Address autocomplete with error handling
                const addressInput = document.getElementById('streetAddress');
                if (addressInput && window.google && window.google.maps && window.google.maps.places) {
                    try {
                        const autocomplete = new google.maps.places.Autocomplete(addressInput, {
                            types: ['address'],
                            componentRestrictions: { country: 'ph' }
                        });

                        autocomplete.addListener('place_changed', () => {
                            const place = autocomplete.getPlace();
                            if (place.geometry && place.geometry.location) {
                                unitMap.setCenter(place.geometry.location);
                                unitMap.setZoom(17);
                                placeUnitMarker(place.geometry.location);
                            }
                        });
                    } catch (error) {
                        console.warn('Autocomplete initialization failed:', error);
                    }
                }
                
                mapInitialized = true;
            } catch (error) {
                console.error('Map initialization failed:', error);
                mapElement.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; flex-direction: column;"><i class="fas fa-exclamation-circle" style="font-size: 40px; color: #e74c3c; margin-bottom: 10px;"></i><p style="color: #666; margin: 0;">Map error</p><p style="font-size: 12px; color: #999; margin: 5px 0 0 0;">Please enter coordinates manually</p></div>';
                mapInitialized = true;
            }
        }

        function placeUnitMarker(location) {
            if (unitMarker) unitMarker.setMap(null);

            unitMarker = new google.maps.Marker({
                position: location,
                map: unitMap,
                title: 'Property Location',
                icon: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
            });

            // Update fields
            document.getElementById('latitude').value = location.lat().toFixed(6);
            document.getElementById('longitude').value = location.lng().toFixed(6);
            document.getElementById('latDisplay').value = location.lat().toFixed(6);
            document.getElementById('lngDisplay').value = location.lng().toFixed(6);
        }

        // Initialize map when modal opens
        document.addEventListener('DOMContentLoaded', function() {
            const unitModal = document.getElementById('unitModal');
            if (unitModal) {
                unitModal.addEventListener('shown.bs.modal', initUnitMap);
            }
        });
    </script>
</body>
</html>
