<?php
// Browse Units - Public View with Luxury Design
// Allows non-logged-in users to browse available units

include '../includes/public_session.php';
include '../includes/functions.php';
include '../includes/auth.php';

// Get all active branches
$branches = getAllBranches();

// Get selected branch and available units
$selectedBranch = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
$availableUnits = [];
$branchDetails = null;

if ($selectedBranch) {
    // Get branch details
    $branchDetails = getBranchById($selectedBranch);
    
    // Get available units with REAL pricing - FIXED COLUMN NAME
    $sql = "SELECT u.*, b.branch_name, b.address, b.city 
            FROM units u 
            JOIN branches b ON u.branch_id = b.branch_id 
            WHERE u.branch_id = ? AND u.is_available = 1 
            ORDER BY u.monthly_rate ASC";
    $availableUnits = get_multiple_results($sql, [$selectedBranch]);
    
    // Get branch amenities
    $amenities = getBranchAmenities($selectedBranch);
    
    // Get branch statistics
    $branchStats = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT 
            COUNT(u.unit_id) as total_units,
            COUNT(r.reservation_id) as total_bookings,
            MIN(u.monthly_rate) as min_price,
            MAX(u.monthly_rate) as max_price
        FROM branches b
        LEFT JOIN units u ON b.branch_id = u.branch_id AND u.is_available = 1
        LEFT JOIN reservations r ON b.branch_id = r.branch_id AND r.status = 'confirmed'
        WHERE b.branch_id = $selectedBranch
    "));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Available Units - BookIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/public/index2.css">
    <style>
    .booking-form-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        padding: 2rem;
        position: sticky;
        top: 100px;
    }
    
    .unit-card {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    
    .unit-card:hover {
        border-color: var(--gold-color);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .amenity-badge {
        background: var(--accent-color);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        margin: 0.25rem;
        display: inline-block;
        font-size: 0.9rem;
    }
    
    .stat-number-sm {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }
    
    .login-prompt {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-building"></i> BookIT
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="browse_units.php">Browse Units</a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <!-- Show when user is logged in -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> <?php echo $_SESSION['fullname']; ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li class="dropdown-header">
                                    <small>Logged in as</small><br>
                                    <strong><?php echo $_SESSION['fullname']; ?></strong>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/admin_dashboard.php">
                                            <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                                        </a>
                                    </li>
                                <?php elseif ($_SESSION['role'] == 'manager'): ?>
                                    <li>
                                        <a class="dropdown-item" href="../manager/manager_dashboard.php">
                                            <i class="fas fa-tachometer-alt"></i> Manager Dashboard
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li>
                                        <a class="dropdown-item" href="../renter/my_bookings.php">
                                            <i class="fas fa-calendar-check"></i> My Bookings
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="../renter/reserve_unit.php">
                                            <i class="fas fa-home"></i> Reserve Unit
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <li>
                                    <a class="dropdown-item" href="../modules/notifications.php">
                                        <i class="fas fa-bell"></i> Notifications
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Show when user is NOT logged in -->
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4 text-white">
                        <i class="fas fa-search"></i> Find Your Perfect Condo
                    </h1>
                    <p class="lead mb-4 text-white">
                        Discover exclusive condominium rentals in prime locations with world-class amenities.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Branch Selection -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card luxury-card">
                        <div class="card-body text-center">
                            <h4 class="card-title mb-4">
                                <i class="fas fa-building"></i> Select Your Preferred Location
                            </h4>
                            <form method="GET" class="text-center">
                                <div class="mb-3">
                                    <select name="branch_id" class="form-select form-select-lg" onchange="this.form.submit()">
                                        <option value="">Choose a branch to explore...</option>
                                        <?php foreach ($branches as $branch): ?>
                                            <option value="<?php echo $branch['branch_id']; ?>" 
                                                    <?php echo ($selectedBranch == $branch['branch_id']) ? 'selected' : ''; ?>>
                                                <?php echo $branch['branch_name']; ?> - <?php echo $branch['city']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <small class="text-muted">Select a branch to view available units and pricing</small>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Prompt for Non-Logged In Users -->
    <?php if (!isLoggedIn() && $selectedBranch): ?>
    <div class="container">
        <div class="login-prompt text-center">
            <h5><i class="fas fa-info-circle"></i> Ready to Make a Reservation?</h5>
            <p class="mb-3">Please login or create an account to book your preferred unit.</p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                   class="btn btn-light btn-sm">
                    <i class="fas fa-sign-in-alt"></i> Login to Reserve
                </a>
                <a href="register.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
            </div>
        </div>
    </div>
    <?php elseif (isLoggedIn() && $_SESSION['role'] != 'renter' && $selectedBranch): ?>
    <div class="container">
        <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle"></i> 
            Only renter accounts can make reservations. 
            <a href="logout.php" class="alert-link">Switch account</a> or 
            <a href="register.php" class="alert-link">create a renter account</a>.
        </div>
    </div>
    <?php endif; ?>

    <!-- Branch Details & Available Units -->
    <?php if ($selectedBranch && $branchDetails): ?>
        <div class="container" style="margin-top: 50px;">
            <div class="row">
                <!-- Branch Details -->
                <div class="col-lg-8">
                    <div class="card luxury-card mb-4">
                        <div class="card-body">
                            <h1 class="display-5 fw-bold"><?php echo $branchDetails['branch_name']; ?></h1>
                            <p class="lead text-muted">
                                <i class="fas fa-map-marker-alt"></i> <?php echo $branchDetails['address']; ?>, <?php echo $branchDetails['city']; ?>
                            </p>
                            
                            <!-- Branch Statistics - REAL DATA -->
                            <div class="row mb-4">
                                <div class="col-md-3 text-center">
                                    <div class="stat-number-sm"><?php echo $branchStats['total_units'] ?? 0; ?></div>
                                    <small class="text-muted">Available Units</small>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="stat-number-sm"><?php echo $branchStats['total_bookings'] ?? 0; ?></div>
                                    <small class="text-muted">Total Bookings</small>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="stat-number-sm">₱<?php echo number_format($branchStats['min_price'] ?? 0, 2); ?></div>
                                    <small class="text-muted">Starting Price</small>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="stat-number-sm">₱<?php echo number_format($branchStats['max_price'] ?? 0, 2); ?></div>
                                    <small class="text-muted">Maximum Price</small>
                                </div>
                            </div>
                            
                            <!-- Branch Description -->
                            <div class="mb-4">
                                <h4>About This Location</h4>
                                <p>Luxury condominium in the heart of <?php echo $branchDetails['city']; ?> with world-class amenities and stunning views. Perfect for both short-term and long-term stays.</p>
                                <p class="text-muted">
                                    <i class="fas fa-phone"></i> <?php echo $branchDetails['contact_number']; ?> | 
                                    <i class="fas fa-envelope"></i> <?php echo $branchDetails['email']; ?>
                                </p>
                            </div>
                            
                            <!-- Available Units - REAL DATA -->
                            <div class="mb-4">
                                <h4>Available Units</h4>
                                <?php if (!empty($availableUnits)): ?>
                                    <?php foreach ($availableUnits as $unit): 
                                        // Get unit images
                                        $unit_images = get_multiple_results(
                                            "SELECT image_path FROM unit_images WHERE unit_id = ? ORDER BY created_at DESC LIMIT 1",
                                            [$unit['unit_id']]
                                        );
                                    ?>
                                        <div class="unit-card">
                                            <div class="row">
                                                <div class="col-md-4 unit-image-container" style="background: #f5f5f5; border-radius: 8px; overflow: hidden; min-height: 250px;">
                                                    <?php if (!empty($unit_images)): ?>
                                                        <img src="<?php echo htmlspecialchars($unit_images[0]['image_path']); ?>" 
                                                             alt="<?php echo htmlspecialchars($unit['unit_number']); ?>" 
                                                             style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                                                            <div style="text-align: center; color: #999;">
                                                                <i class="fas fa-image fa-3x mb-2"></i>
                                                                <p>No Image</p>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-8">
                                                    <h5><?php echo htmlspecialchars($unit['unit_number']); ?> - <?php echo htmlspecialchars($unit['unit_type'] ?? 'Unit'); ?></h5>
                                                    <p class="text-muted mb-2">
                                                        <i class="fas fa-map-marker-alt"></i> 
                                                        <?php echo htmlspecialchars($unit['address'] ?? $unit['city']); ?>
                                                    </p>
                                                    <p class="text-muted mb-2">
                                                        <i class="fas fa-ruler-combined"></i> 
                                                        <?php echo $unit['floor_area'] ?? 'N/A'; ?> sqm
                                                    </p>
                                                    <p class="text-muted mb-2">
                                                        <i class="fas fa-user-friends"></i> 
                                                        Up to <?php echo $unit['max_occupancy'] ?? 'N/A'; ?> guests
                                                    </p>
                                                    <?php if (!empty($unit['description'])): ?>
                                                        <p class="text-muted small"><?php echo htmlspecialchars(substr($unit['description'], 0, 100)); ?><?php echo strlen($unit['description']) > 100 ? '...' : ''; ?></p>
                                                    <?php endif; ?>
                                                    <div style="margin-top: 15px; display: flex; align-items: center; justify-content: space-between;">
                                                        <div class="luxury-price">
                                                            <span class="price-amount">₱<?php echo number_format(round($unit['price'] / 30), 2); ?></span>
                                                            <span class="price-period">/night</span>
                                                        </div>
                                                        <?php if (isLoggedIn() && $_SESSION['role'] == 'renter'): ?>
                                                            <a href="../renter/reserve_unit.php?unit_id=<?php echo $unit['unit_id']; ?>" 
                                                               class="btn btn-luxury btn-sm">
                                                                <i class="fas fa-calendar-check"></i> Book
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                                               class="btn btn-luxury btn-sm">
                                                                <i class="fas fa-lock"></i> Login
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-home fa-3x text-muted mb-3"></i>
                                        <h5>No Available Units</h5>
                                        <p class="text-muted">There are currently no available units in this branch.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Amenities - REAL DATA -->
                            <?php if (isset($amenities) && !empty($amenities)): ?>
                            <div class="mb-4">
                                <h4>Building Amenities</h4>
                                <div class="amenities-container">
                                    <?php foreach ($amenities as $amenity): ?>
                                        <span class="amenity-badge">
                                            <i class="fas fa-<?php echo $amenity['icon'] ?? 'star'; ?>"></i>
                                            <?php echo $amenity['amenity_name']; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Booking Info -->
                <div class="col-lg-4">
                    <div class="booking-form-container">
                        <h4 class="mb-4">Ready to Book?</h4>
                        
                        <?php if (isLoggedIn() && $_SESSION['role'] == 'renter'): ?>
                            <p class="text-success mb-3">
                                <i class="fas fa-check-circle"></i> You're logged in and ready to book!
                            </p>
                            <div class="d-grid gap-2">
                                <a href="../renter/reserve_unit.php?branch_id=<?php echo $selectedBranch; ?>" 
                                   class="btn btn-luxury">
                                    <i class="fas fa-calendar-check"></i> Reserve a Unit
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-4">
                                Login or create an account to check availability and make reservations.
                            </p>
                            <div class="d-grid gap-2">
                                <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                   class="btn btn-luxury">
                                    <i class="fas fa-sign-in-alt"></i> Login to Book
                                </a>
                                <a href="register.php" class="btn btn-outline-luxury">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Sample Pricing -->
                        <div class="price-breakdown mt-4">
                            <h6>Sample Monthly Pricing:</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Monthly Rate</span>
                                <span>₱<?php echo number_format($branchStats['min_price'] ?? 25000, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Security Deposit</span>
                                <span>₱<?php echo number_format(($branchStats['min_price'] ?? 25000) * 0.5, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Initial Payment</span>
                                <span>₱<?php echo number_format((($branchStats['min_price'] ?? 25000) * 1.5), 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($selectedBranch && !$branchDetails): ?>
        <!-- Invalid Branch Selected -->
        <section class="py-5 bg-light">
            <div class="container">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h4>Branch Not Found</h4>
                    <p class="text-muted">The selected branch does not exist or is not available.</p>
                    <a href="browse_units.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Browse
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Call to Action -->
    <?php if (!$selectedBranch): ?>
    <section class="cta-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold mb-4 text-white">Find Your Perfect Stay</h2>
                    <p class="lead mb-5 text-white">
                        Select a branch above to explore available units and start your booking journey.
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="register.php" class="btn btn-luxury">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                        <a href="login.php" class="btn btn-outline-luxury">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
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
                        <a href="index.php" class="text-muted text-decoration-none mb-2">Home</a>
                        <a href="browse_units.php" class="text-muted text-decoration-none mb-2">Browse Units</a>
                        <a href="login.php" class="text-muted text-decoration-none mb-2">Login</a>
                        <a href="register.php" class="text-muted text-decoration-none">Register</a>
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
</body>
</html>