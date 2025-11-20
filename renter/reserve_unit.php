<?php
// BookIT Unit Reservation System
// Multi-branch Condo Rental Reservation System

include_once '../includes/session.php';
include_once '../includes/functions.php';
checkRole(['renter']); // Tanging renters lang ang pwede

$message = '';
$error = '';
$availableUnits = [];
$selectedBranch = '';
$checkInDate = '';
$checkOutDate = '';

// Handle search for available units
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_units'])) {
    $selectedBranch = $_POST['branch_id'];
    $checkInDate = $_POST['check_in_date'];
    $checkOutDate = $_POST['check_out_date'];
    
    // I-validate ang date range
    if (!validateDateRange($checkInDate, $checkOutDate)) {
        $error = "Invalid date range. Please select future dates.";
    } else {
        // Kumuha ng available units - FIXED: use getAvailableUnits function
        $availableUnits = getAvailableUnits($selectedBranch, $checkInDate, $checkOutDate);
        
        if (empty($availableUnits)) {
            $message = "No available units found for the selected dates.";
        }
    }
}

// Handle unit reservation with amenities
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reserve_unit'])) {
    // FIXED: Validate CSRF token first
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    }
    // ENHANCED: Check for duplicate submission (nonce protection) - 3 second window
    else if (isset($_SESSION['last_booking_submission'])) {
        $time_diff = time() - $_SESSION['last_booking_submission'];
        if ($time_diff < 3) {
            // Prevent duplicate submissions within 3 seconds
            $error = "Please wait a moment before submitting another booking.";
        } else {
            unset($_SESSION['last_booking_submission']);
        }
    }
    
    if (empty($error)) {
        // FIXED: Input validation for CRITICAL #8
        $unitId = (int)$_POST['unit_id'];
        $branchId = (int)$_POST['branch_id'];
        $checkInDate = sanitize_input($_POST['check_in_date']);
        $checkOutDate = sanitize_input($_POST['check_out_date']);
        $specialRequests = sanitize_input($_POST['special_requests'] ?? '');
        
        // Validate inputs before database queries
        if ($unitId <= 0 || $branchId <= 0) {
            $error = "Invalid unit or branch selected.";
        } else if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkInDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkOutDate)) {
            $error = "Invalid date format.";
        } else {
            // Kumuha ng unit details para sa pricing - FIXED: CRITICAL #2 SQL Injection
            $unit = get_single_result("SELECT * FROM units WHERE unit_id = ? AND branch_id = ?", [$unitId, $branchId]);
            $branch = get_single_result("SELECT * FROM branches WHERE branch_id = ?", [$branchId]);
            
            if ($unit && $branch) {
            // I-calculate ang unit amount
            $totalDays = calculateDays($checkInDate, $checkOutDate);
            $unitAmount = $unit['monthly_rate'] * $totalDays;
            $securityDeposit = $unit['security_deposit'];
            
            // I-calculate ang amenity costs - FIXED: CRITICAL #3 SQL Injection
            $amenityCosts = 0;
            $selectedAmenities = [];
            if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
                foreach ($_POST['amenities'] as $amenityId) {
                    $amenityId = (int)$amenityId;  // Validate as integer
                    if ($amenityId > 0) {
                        // FIXED: Use prepared statement instead of direct concatenation
                        $amenity = get_single_result(
                            "SELECT * FROM amenities WHERE amenity_id = ? AND branch_id = ?",
                            [$amenityId, $branchId]
                        );
                        if ($amenity) {
                            $amenityCosts += $amenity['hourly_rate'] * $totalDays; // Assuming daily rate
                            $selectedAmenities[] = $amenity;
                        }
                    }
                }
            }
            
            $totalAmount = $unitAmount + $amenityCosts;
            
            // I-create ang reservation
            $reservationId = createReservation(
                $_SESSION['user_id'], 
                $unitId, 
                $branchId, 
                $checkInDate, 
                $checkOutDate, 
                $totalAmount, 
                $securityDeposit, 
                $specialRequests
            );
            
            if ($reservationId) {
                // Mark submission time IMMEDIATELY to prevent race conditions
                $_SESSION['last_booking_submission'] = time();
                
                // I-create ang amenity bookings kung may selected amenities
                if (!empty($selectedAmenities)) {
                    foreach ($selectedAmenities as $amenity) {
                        $amenityBookingId = bookAmenity(
                            $_SESSION['user_id'],
                            $amenity['amenity_id'],
                            $branchId,
                            $checkInDate,
                            '00:00:00',
                            '23:59:59',
                            $amenity['hourly_rate'] * $totalDays
                        );
                    }
                }
                
                // Mag-send ng notification
                $amenityText = !empty($selectedAmenities) ? " with amenities: " . implode(', ', array_column($selectedAmenities, 'amenity_name')) : "";
                sendNotification(
                    $_SESSION['user_id'],
                    "Reservation Created",
                    "Your reservation for Unit " . $unit['unit_number'] . $amenityText . " has been created successfully. Reservation ID: " . $reservationId,
                    'booking',
                    'system'
                );
                
                $message = "Reservation created successfully! Reservation ID: " . $reservationId;
                $availableUnits = []; // I-clear ang search results
            } else {
                // I-check kung may existing pending reservation - FIXED: CRITICAL #4 SQL Injection
                $existingReservation = get_single_result(
                    "SELECT reservation_id FROM reservations 
                    WHERE user_id = ? 
                    AND status = 'pending' 
                    AND (
                        (check_in_date <= ? AND check_out_date > ?) OR
                        (check_in_date < ? AND check_out_date >= ?) OR
                        (check_in_date >= ? AND check_out_date <= ?)
                    )",
                    [$_SESSION['user_id'], $checkOutDate, $checkInDate, $checkOutDate, $checkInDate, $checkInDate, $checkOutDate]
                );
                
                if ($existingReservation) {
                    $error = "You already have a pending reservation for overlapping dates. Please remove your existing booking first or choose different dates.";
                } else {
                    $error = "Failed to create reservation. Unit may no longer be available.";
                }
            }
        } else {
            $error = "Invalid unit or branch selected.";
        }
        }
    }
}

// Kumuha ng lahat ng branches
$branches = mysqli_query($conn, "SELECT * FROM branches WHERE is_active = 1 ORDER BY branch_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Unit - BookIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/public/index.css">
    <link rel="stylesheet" href="../assets/css/renter/reserve_unit.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../public/index.php">
                <i class="fas fa-building"></i> BookIT
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="my_bookings.php">
                    <i class="fas fa-calendar-check"></i> My Bookings
                </a>
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a class="nav-link" href="../public/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4 text-white">
                        <i class="fas fa-calendar-plus"></i> Reserve Your Perfect Condo
                    </h1>
                    <p class="lead mb-4 text-white">
                        Find and book luxury condominium units across our premium locations
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Search Card -->
        <div class="search-card">
            <h3 class="text-center mb-4"><i class="fas fa-search"></i> Find Available Units</h3>
            <p class="text-center text-muted mb-4">Search for available condo units across our branches</p>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Select Branch</label>
                        <select class="form-select form-select-lg" name="branch_id" required>
                            <option value="">Choose Branch</option>
                            <?php while ($branch = mysqli_fetch_assoc($branches)): ?>
                                <option value="<?php echo $branch['branch_id']; ?>" 
                                        <?php echo $selectedBranch == $branch['branch_id'] ? 'selected' : ''; ?>>
                                    <?php echo $branch['branch_name']; ?> - <?php echo $branch['city']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Check-in Date</label>
                        <input type="date" class="form-control form-control-lg" name="check_in_date" 
                               value="<?php echo $checkInDate; ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Check-out Date</label>
                        <input type="date" class="form-control form-control-lg" name="check_out_date" 
                               value="<?php echo $checkOutDate; ?>" 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">&nbsp;</label>
                        <button type="submit" name="search_units" class="btn btn-luxury w-100">
                            <i class="fas fa-search"></i> Search Units
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Available Units -->
        <?php if (!empty($availableUnits)): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <h2 class="text-center mb-5"><i class="fas fa-home"></i> Available Units</h2>
                </div>
            </div>
            <div class="row">
                <?php foreach ($availableUnits as $unit): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card unit-card">
                            <div class="unit-image">
                                <i class="fas fa-home fa-3x mb-3"></i>
                                <h5 class="text-white">Unit <?php echo $unit['unit_number']; ?></h5>
                                <span class="price-tag">
                                    ₱<?php echo number_format($unit['monthly_rate'], 2); ?>/month
                                </span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $unit['unit_type']; ?></h5>
                                <div class="unit-features mb-3">
                                    <span class="feature-badge">
                                        <i class="fas fa-ruler-combined"></i> <?php echo $unit['floor_area'] ?? 'N/A'; ?> sqm
                                    </span>
                                    <span class="feature-badge">
                                        <i class="fas fa-user-friends"></i> <?php echo $unit['max_occupancy']; ?> persons
                                    </span>
                                    <span class="feature-badge">
                                        <i class="fas fa-building"></i> Floor <?php echo $unit['floor_number']; ?>
                                    </span>
                                </div>
                                
                                <?php if ($unit['description']): ?>
                                    <p class="card-text text-muted"><?php echo $unit['description']; ?></p>
                                <?php endif; ?>
                                
                                <!-- Unit Overview Button -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" 
                                            data-bs-target="#unitModal<?php echo $unit['unit_id']; ?>">
                                        <i class="fas fa-eye"></i> View Details & Reviews
                                    </button>
                                </div>
                                
                                <!-- Amenity Selection -->
                                <?php 
                                // Kumuha ng amenities sa selected branch
                                $amenities = getBranchAmenities($unit['branch_id']);
                                if ($amenities && !empty($amenities)): 
                                ?>
                                    <div class="amenity-selection">
                                        <label class="form-label fw-bold"><i class="fas fa-swimming-pool"></i> Select Amenities (Optional)</label>
                                        <div class="amenity-selection">
                                            <?php foreach ($amenities as $amenity): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input amenity-checkbox" type="checkbox" 
                                                           name="amenities[]" value="<?php echo $amenity['amenity_id']; ?>" 
                                                           id="amenity_<?php echo $amenity['amenity_id']; ?>"
                                                           data-rate="<?php echo $amenity['hourly_rate']; ?>">
                                                    <label class="form-check-label" for="amenity_<?php echo $amenity['amenity_id']; ?>">
                                                        <strong><?php echo $amenity['amenity_name']; ?></strong>
                                                        <small class="text-muted d-block">
                                                            ₱<?php echo number_format($amenity['hourly_rate'], 2); ?>/day
                                                        </small>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Reservation Form -->
                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="unit_id" value="<?php echo $unit['unit_id']; ?>">
                                    <input type="hidden" name="branch_id" value="<?php echo $unit['branch_id']; ?>">
                                    <input type="hidden" name="check_in_date" value="<?php echo $checkInDate; ?>">
                                    <input type="hidden" name="check_out_date" value="<?php echo $checkOutDate; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Special Requests</label>
                                        <textarea class="form-control" name="special_requests" rows="2" 
                                                  placeholder="Any special requirements or requests..."></textarea>
                                    </div>
                                    
                                    <div class="pricing-breakdown">
                                        <h6 class="fw-bold">Pricing Breakdown</h6>
                                        <div class="unit-cost mb-2">
                                            <span>Unit (<?php echo calculateDays($checkInDate, $checkOutDate); ?> days):</span>
                                            <span class="float-end fw-bold">₱<?php echo number_format($unit['monthly_rate'] * calculateDays($checkInDate, $checkOutDate), 2); ?></span>
                                        </div>
                                        <div class="amenity-cost mb-2" style="display: none;">
                                            <span>Amenities:</span>
                                            <span class="float-end fw-bold" id="amenity-total-<?php echo $unit['unit_id']; ?>">₱0.00</span>
                                        </div>
                                        <div class="security-deposit mb-2">
                                            <span>Security Deposit:</span>
                                            <span class="float-end">₱<?php echo number_format($unit['security_deposit'], 2); ?></span>
                                        </div>
                                        <hr>
                                        <div class="total-cost">
                                            <span class="fw-bold">Total Amount:</span>
                                            <span class="float-end h5 text-success fw-bold" id="total-cost-<?php echo $unit['unit_id']; ?>">
                                                ₱<?php echo number_format(($unit['monthly_rate'] * calculateDays($checkInDate, $checkOutDate)) + $unit['security_deposit'], 2); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="reserve_unit" class="btn btn-luxury w-100 mt-3">
                                        <i class="fas fa-calendar-plus"></i> Reserve This Unit
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Unit Overview Modals -->
            <?php foreach ($availableUnits as $unit): 
                // Get unit reviews
                $reviews = mysqli_query($conn, "
                    SELECT r.*, u.full_name 
                    FROM reviews r 
                    JOIN users u ON r.user_id = u.user_id 
                    WHERE r.unit_id = {$unit['unit_id']} AND r.is_approved = 1 
                    ORDER BY r.created_at DESC 
                    LIMIT 5
                ");
                
                // Get unit amenities for display
                $unitAmenities = getBranchAmenities($unit['branch_id']);
            ?>
                <!-- Unit Modal -->
                <div class="modal fade" id="unitModal<?php echo $unit['unit_id']; ?>" tabindex="-1" aria-labelledby="unitModalLabel<?php echo $unit['unit_id']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="unitModalLabel<?php echo $unit['unit_id']; ?>">
                                    <i class="fas fa-home"></i> Unit <?php echo $unit['unit_number']; ?> - <?php echo $unit['unit_type']; ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Unit Images -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="unit-gallery">
                                            <div class="main-image">
                                                <img src="https://via.placeholder.com/600x300/667eea/ffffff?text=Unit+<?php echo $unit['unit_number']; ?>" 
                                                     class="img-fluid rounded" alt="Unit <?php echo $unit['unit_number']; ?>">
                                            </div>
                                            <div class="image-thumbnails mt-2 text-center">
                                                <img src="https://via.placeholder.com/100x60/764ba2/ffffff?text=View+1" 
                                                     class="img-thumbnail me-2" style="width: 100px; height: 60px;">
                                                <img src="https://via.placeholder.com/100x60/28a745/ffffff?text=View+2" 
                                                     class="img-thumbnail me-2" style="width: 100px; height: 60px;">
                                                <img src="https://via.placeholder.com/100x60/dc3545/ffffff?text=View+3" 
                                                     class="img-thumbnail me-2" style="width: 100px; height: 60px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Unit Details -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-info-circle"></i> Unit Details</h6>
                                        <ul class="list-unstyled">
                                            <li><strong>Type:</strong> <?php echo $unit['unit_type']; ?></li>
                                            <li><strong>Floor:</strong> <?php echo $unit['floor_number']; ?></li>
                                            <li><strong>Max Occupancy:</strong> <?php echo $unit['max_occupancy']; ?> persons</li>
                                            <li><strong>Floor Area:</strong> <?php echo $unit['floor_area'] ?? 'N/A'; ?> sqm</li>
                                            <li><strong>Monthly Rate:</strong> ₱<?php echo number_format($unit['monthly_rate'], 2); ?></li>
                                            <li><strong>Security Deposit:</strong> ₱<?php echo number_format($unit['security_deposit'], 2); ?></li>
                                        </ul>
                                        
                                        <?php if ($unit['description']): ?>
                                            <h6><i class="fas fa-align-left"></i> Description</h6>
                                            <p class="text-muted"><?php echo $unit['description']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-swimming-pool"></i> Available Amenities</h6>
                                        <?php if ($unitAmenities && !empty($unitAmenities)): ?>
                                            <ul class="list-unstyled">
                                                <?php foreach ($unitAmenities as $amenity): ?>
                                                    <li class="mb-2">
                                                        <i class="fas fa-check text-success"></i> 
                                                        <strong><?php echo $amenity['amenity_name']; ?></strong>
                                                        <small class="text-muted d-block">₱<?php echo number_format($amenity['hourly_rate'], 2); ?>/day</small>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-muted">No amenities available for this unit.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Reviews Section -->
                                <div class="mt-4">
                                    <h6><i class="fas fa-star"></i> Reviews & Ratings</h6>
                                    <?php if ($reviews && mysqli_num_rows($reviews) > 0): ?>
                                        <div class="reviews-section">
                                            <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                                                <div class="review-item border-bottom pb-3 mb-3">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong><?php echo $review['full_name']; ?></strong>
                                                            <div class="rating">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                                <?php endfor; ?>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted"><?php echo formatDate($review['created_at']); ?></small>
                                                    </div>
                                                    <?php if ($review['comment']): ?>
                                                        <p class="mt-2 mb-0"><?php echo $review['comment']; ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-comment-slash fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No reviews yet. Be the first to review this unit!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-luxury" data-bs-dismiss="modal">
                                    <i class="fas fa-calendar-plus"></i> Book This Unit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
        <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_units'])): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h5>No Available Units</h5>
                <p class="text-muted">No units are available for the selected dates and branch.</p>
                <button class="btn btn-luxury" onclick="window.location.reload()">
                    <i class="fas fa-refresh"></i> Try Different Dates
                </button>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-home fa-3x text-muted mb-3"></i>
                <h5>Find Your Perfect Unit</h5>
                <p class="text-muted">Select a branch and dates to see available units.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-building"></i> BookIT</h5>
                    <p class="text-muted">
                        Multi-branch condo rental reservation system designed to streamline 
                        your rental management operations.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6>Quick Links</h6>
                    <div class="d-flex flex-column">
                        <a href="../public/index.php" class="text-muted text-decoration-none mb-2">Home</a>
                        <a href="my_bookings.php" class="text-muted text-decoration-none mb-2">My Bookings</a>
                        <a href="profile.php" class="text-muted text-decoration-none">Profile</a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="text-muted mb-0">&copy; 2025 BookIT. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/renter/reserve_unit.js"></script>
</body>
</html>
<?php include_once __DIR__ . '/../includes/sidebar_assets.php'; ?>