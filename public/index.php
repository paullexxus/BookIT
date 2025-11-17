<?php
// BookIT Public Homepage
// Multi-branch Condo Rental Reservation System

include '../includes/public_session.php';
include '../includes/functions.php';
include '../includes/auth.php';

// Kumuha ng featured branches with REAL pricing data
$featuredBranches = mysqli_query($conn, "
    SELECT b.*, 
           COUNT(u.unit_id) as unit_count, 
           COUNT(r.reservation_id) as booking_count,
           MIN(u.monthly_rate) as min_price,
           MAX(u.monthly_rate) as max_price
    FROM branches b 
    LEFT JOIN units u ON b.branch_id = u.branch_id AND u.is_available = 1
    LEFT JOIN reservations r ON b.branch_id = r.branch_id AND r.status = 'confirmed'
    WHERE b.is_active = 1 
    GROUP BY b.branch_id 
    ORDER BY booking_count DESC 
    LIMIT 3
");

// Kumuha ng system statistics
$totalBranches = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM branches WHERE is_active = 1"));
$totalUnits = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM units WHERE is_available = 1"));
$totalReservations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM reservations WHERE status = 'confirmed'"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookIT - Multi-Branch Condo Rental System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/public/index.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-building"></i> BookIT
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggl   er-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#branches">Branches</a>
                    </li>
                    <?php if (!isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="browse_units.php">
                                <i class="fas fa-search"></i> Browse Units
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/admin_dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                                </a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'manager'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../manager/manager_dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Manager Dashboard
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../renter/reserve_unit.php">
                                    <i class="fas fa-home"></i> Reserve Unit
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../renter/my_bookings.php">
                                    <i class="fas fa-calendar-check"></i> My Bookings
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../modules/notifications.php">
                                <i class="fas fa-bell"></i> Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
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

                    <!-- NEW: Be a Host link -->
                    <li class="nav-item">
                        <?php if (isLoggedIn() && in_array($_SESSION['role'], ['host','manager','admin'])): ?>
                            <a class="nav-link" href="manager_register.php">
                                <i class="fas fa-handshake"></i> Be a Host
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="be_host.php">
                                <i class="fas fa-handshake"></i> Be a Host
                            </a>
                        <?php endif; ?>
                    </li>
                    
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="display-4 fw-bold mb-4 text-white">
                    <i class="fas fa-building"></i> BookIT
                </h1>
                <h2 class="h3 mb-4 text-white">Multi-Branch Condo Rental Reservation and Booking Management</h2>
                <p class="lead mb-5 text-white">
                    Streamline your condo rental experience with our comprehensive booking system. 
                        Manage multiple branches, track availability in real-time, and provide seamless 
                        reservation services for your tenants.
                </p>
                
                <?php if (!isLoggedIn()): ?>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="browse_units.php" class="btn btn-luxury">
                            <i class="fas fa-search"></i> Find Your Perfect Condo
                        </a>
                        <a href="register.php" class="btn btn-outline-luxury">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                    </div>
                <?php else: ?>
                    <div class="d-flex gap-3 justify-content-center">
                        <?php if ($_SESSION['role'] == 'renter'): ?>
                            <a href="../renter/reserve_unit.php" class="btn btn-luxury">
                                <i class="fas fa-home"></i> Reserve Unit
                            </a>
                            <a href="../renter/book_amenity.php" class="btn btn-outline-luxury">
                                <i class="fas fa-swimming-pool"></i> Book Amenity
                            </a>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_dashboard.php" class="btn btn-luxury">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

    <!-- Statistics Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-building fa-3x text-primary mb-3"></i>
                        <div class="stat-number"><?php echo $totalBranches['total']; ?></div>
                        <h5>Active Branches</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-home fa-3x text-success mb-3"></i>
                        <div class="stat-number"><?php echo $totalUnits['total']; ?></div>
                        <h5>Available Units</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-calendar-check fa-3x text-warning mb-3"></i>
                        <div class="stat-number"><?php echo $totalReservations['total']; ?></div>
                        <h5>Confirmed Bookings</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-users fa-3x text-info mb-3"></i>
                        <div class="stat-number">24/7</div>
                        <h5>Customer Support</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">System Features</h2>
                <p class="lead">Comprehensive tools for modern condo rental management</p>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-building fa-3x text-primary mb-4"></i>
                            <h4>Multi-Branch Management</h4>
                            <p class="text-muted">
                                Manage multiple rental locations under one unified system. 
                                Track performance, manage staff, and maintain consistency across all branches.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-calendar-alt fa-3x text-success mb-4"></i>
                            <h4>Real-Time Availability</h4>
                            <p class="text-muted">
                                Prevent double bookings with real-time availability tracking. 
                                Automated conflict detection ensures smooth operations.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-swimming-pool fa-3x text-info mb-4"></i>
                            <h4>Amenity Management</h4>
                            <p class="text-muted">
                                Allow tenants to book additional facilities like swimming pools, 
                                gyms, and function rooms with ease.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-credit-card fa-3x text-warning mb-4"></i>
                            <h4>Online Payments</h4>
                            <p class="text-muted">
                                Secure payment processing with multiple gateway options. 
                                Support for GCash, PayMaya, bank transfers, and credit cards.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-bell fa-3x text-danger mb-4"></i>
                            <h4>Automated Notifications</h4>
                            <p class="text-muted">
                                Keep users informed with automated email and SMS notifications 
                                for bookings, payments, and important updates.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-chart-line fa-3x text-secondary mb-4"></i>
                            <h4>Analytics & Reports</h4>
                            <p class="text-muted">
                                Comprehensive reporting and analytics to track performance, 
                                revenue, and occupancy rates across all branches.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Branches -->
<section id="branches" class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">Featured Condominiums</h2>
            <p class="lead">Premium locations across Metro Manila</p>
        </div>
        
        <div class="row">
            <?php if (mysqli_num_rows($featuredBranches) > 0): ?>
                <?php while ($branch = mysqli_fetch_assoc($featuredBranches)): 
                    // Generate appropriate image based on branch name or use default
                    $branch_images = [
                        'BGC' => 'condoBGC.jpg',
                        'Makati' => 'makati-condo.jpg',
                        'Ortigas' => 'ortigas-condo.jpg',
                        'Mandaluyong' => 'mandaluyong-condo.jpg',
                        'Quezon City' => 'qc-condo.jpg',
                        'Pasig' => 'pasig-condo.jpg'
                    ];
                    
                    $branch_city = $branch['city'] ?? 'Metro Manila';
                    $image_name = 'condoBGC.jpg';
                    
                    foreach ($branch_images as $city => $img) {
                        if (stripos($branch_city, $city) !== false) {
                            $image_name = $img;
                            break;
                        }
                    }
                    
                    $image_path = "../assets/images/branches/" . $image_name;
                    $default_image = "../assets/images/branches/condoBGC.jpg";
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card luxury-card h-100">
                            <!-- Branch Image -->
                            <div class="branch-image-container">
                                <img src="<?php echo file_exists($image_path) ? $image_path : $default_image; ?>" 
                                     class="branch-image" 
                                     alt="<?php echo $branch['branch_name']; ?>"
                                     onerror="this.src='<?php echo $default_image; ?>'">
                                <div class="branch-overlay">
                                    <span class="badge bg-primary">Featured</span>
                                    <?php if ($branch['booking_count'] > 50): ?>
                                        <span class="badge bg-success">Popular</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title luxury-title">
                                    <i class="fas fa-building"></i> <?php echo $branch['branch_name']; ?>
                                </h5>
                                <p class="card-text luxury-location">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo $branch['city']; ?>
                                </p>
                                
                                <!-- Branch Description -->
                                <p class="branch-description">
                                    <?php
                                    $descriptions = [
                                        "Luxury condominium in the heart of {$branch['city']}. Features modern amenities, 24/7 security, and stunning city views.",
                                        "Premium residential tower offering spacious units with high-end finishes and exclusive resident amenities.",
                                        "Contemporary living spaces with resort-style facilities and convenient access to business districts.",
                                        "Elegant condominium complex featuring state-of-the-art facilities and panoramic cityscape views."
                                    ];
                                    echo $descriptions[array_rand($descriptions)];
                                    ?>
                                </p>
                                
                                <!-- REAL PRICING FROM DATABASE -->
                                <div class="luxury-price mb-3">
                                    <span class="price-from">Starting from</span>
                                    <span class="price-amount">₱<?php echo number_format($branch['min_price'] ?? 2500, 2); ?></span>
                                    <span class="price-period">/night</span>
                                </div>
                                
                                <!-- Key Features -->
                                <div class="key-features mb-3">
                                    <div class="feature-item">
                                        <i class="fas fa-swimming-pool text-primary"></i>
                                        <span>Swimming Pool</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="fas fa-dumbbell text-success"></i>
                                        <span>Fitness Gym</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="fas fa-wifi text-info"></i>
                                        <span>High-Speed WiFi</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="fas fa-shield-alt text-warning"></i>
                                        <span>24/7 Security</span>
                                    </div>
                                </div>
                                
                                <!-- Branch Statistics -->
                                <div class="branch-stats mt-3">
                                    <div class="stat-item">
                                        <i class="fas fa-home text-muted"></i>
                                        <small><?php echo $branch['unit_count']; ?> available units</small>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-calendar-check text-muted"></i>
                                        <small><?php echo $branch['booking_count']; ?> successful bookings</small>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-star text-warning"></i>
                                        <small>4.5+ rating</small>
                                    </div>
                                </div>
                                
                                <!-- Call to Action -->
                                <div class="luxury-actions mt-4">
                                    <button type="button" class="btn btn-luxury btn-sm w-100 book-now-btn" 
                                            data-branch-id="<?php echo $branch['branch_id']; ?>"
                                            data-branch-name="<?php echo htmlspecialchars($branch['branch_name']); ?>"
                                            data-branch-price="<?php echo $branch['min_price'] ?? 2500; ?>"
                                            data-branch-location="<?php echo htmlspecialchars($branch['city']); ?>">
                                        <i class="fas fa-calendar-check"></i> Book Now
                                    </button>
                                    <a href="branch_details.php?id=<?php echo $branch['branch_id']; ?>" 
                                       class="btn btn-outline-primary btn-sm w-100 mt-2">
                                        <i class="fas fa-info-circle"></i> More Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <h5>No Branches Available</h5>
                    <p class="text-muted">Branches will be displayed here once they are added to the system.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- View All Branches Button -->
        <div class="text-center mt-4">
            <a href="browse_units.php" class="btn btn-primary btn-lg">
                <i class="fas fa-building"></i> View All Condominiums
            </a>
        </div>
    </div>
</section>

    <!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-4 text-white">Ready to Get Started?</h2>
                <p class="lead mb-5 text-white">
                    Join thousands of satisfied customers who trust BookIT for their condo rental needs. 
                    Experience seamless booking, secure payments, and excellent customer service.
                </p>
                <?php if (!isLoggedIn()): ?>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="browse_units.php" class="btn btn-luxury">
                            <i class="fas fa-search"></i> Browse Units
                        </a>
                        <a href="register.php" class="btn btn-outline-luxury">
                            <i class="fas fa-user-plus"></i> Join Now
                        </a>
                    </div>
                <?php else: ?>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="../renter/reserve_unit.php" class="btn btn-luxury">
                            <i class="fas fa-home"></i> Reserve Unit
                        </a>
                        <a href="../renter/my_bookings.php" class="btn btn-outline-luxury">
                            <i class="fas fa-calendar-check"></i> My Bookings
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

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
                        <a href="be_host.php" class="text-muted text-decoration-none mb-2">Be a Host</a>
                        <a href="#features" class="text-muted text-decoration-none mb-2">Features</a>
                        <a href="#branches" class="text-muted text-decoration-none mb-2">Branches</a>
                        <a href="#about" class="text-muted text-decoration-none mb-2">About</a>
                        <?php if (!isLoggedIn()): ?>
                            <a href="login.php" class="text-muted text-decoration-none mb-2">Login</a>
                            <a href="register.php" class="text-muted text-decoration-none">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="text-muted mb-0">&copy; 2025 BookIT. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">
                        <i class="fas fa-calendar-check"></i> Book Your Stay
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Left Column - Branch Details -->
                        <div class="col-md-6">
                            <div class="branch-details">
                                <h4 id="modalBranchName" class="luxury-title">Oceanview Penthouse</h4>
                                <p class="luxury-location mb-3" id="modalBranchLocation">
                                    <i class="fas fa-map-marker-alt"></i> Miami Beach, FL
                                </p>
                                
                                <div class="branch-description">
                                    <p>This luxurious penthouse offers breathtaking ocean views from every room. With 3 spacious bedrooms, a modern kitchen, and a private infinity pool, this is the perfect getaway for families or groups.</p>
                                </div>
                                
                                <div class="key-features mt-4">
                                    <div class="feature-item">
                                        <i class="fas fa-bed text-primary"></i>
                                        <span>3 Bedrooms</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="fas fa-swimming-pool text-info"></i>
                                        <span>Private Pool</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="fas fa-wifi text-success"></i>
                                        <span>Free WiFi</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="fas fa-car text-warning"></i>
                                        <span>Parking</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column - Booking Form -->
                        <div class="col-md-6">
                            <div class="booking-form">
                                <form id="bookingForm">
                                    <input type="hidden" id="selectedBranchId" name="branch_id">
                                    
                                    <!-- Check-in Date -->
                                    <div class="mb-3">
                                        <label for="checkInDate" class="form-label">
                                            <i class="fas fa-calendar-alt"></i> Check-in Date
                                        </label>
                                        <input type="date" class="form-control" id="checkInDate" name="check_in_date" required>
                                    </div>
                                    
                                    <!-- Check-out Date -->
                                    <div class="mb-3">
                                        <label for="checkOutDate" class="form-label">
                                            <i class="fas fa-calendar-alt"></i> Check-out Date
                                        </label>
                                        <input type="date" class="form-control" id="checkOutDate" name="check_out_date" required>
                                    </div>
                                    
                                    <!-- Number of Guests -->
                                    <div class="mb-3">
                                        <label for="numberOfGuests" class="form-label">
                                            <i class="fas fa-users"></i> Number of Guests
                                        </label>
                                        <select class="form-select" id="numberOfGuests" name="number_of_guests" required>
                                            <option value="1">1 Guest</option>
                                            <option value="2">2 Guests</option>
                                            <option value="3">3 Guests</option>
                                            <option value="4">4 Guests</option>
                                            <option value="5">5 Guests</option>
                                            <option value="6">6 Guests</option>
                                            <option value="7">7 Guests</option>
                                            <option value="8">8 Guests</option>
                                            <option value="9">9 Guests</option>
                                            <option value="10">10 Guests</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Price Breakdown -->
                                    <div class="price-breakdown mt-4">
                                        <h6 class="mb-3">Price Breakdown</h6>
                                        
                                        <div class="d-flex justify-content-between mb-2">
                                            <span id="pricePerNightText">₱350 x 0 nights</span>
                                            <span id="basePrice">₱0</span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Cleaning fee</span>
                                            <span>₱150</span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Service fee</span>
                                            <span>₱180</span>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="d-flex justify-content-between total-price">
                                            <strong>Total</strong>
                                            <strong id="totalPrice">₱330</strong>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="booking-actions mt-4">
                                        <button type="button" class="btn btn-luxury w-100 mb-2" id="checkAvailabilityBtn">
                                            <i class="fas fa-search"></i> Check Availability
                                        </button>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-credit-card"></i> Reserve Now
                                            </button>
                                            
                                            <button type="button" class="btn btn-outline-secondary w-100">
                                                <i class="fas fa-envelope"></i> Contact Host
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/public/index.js"></script>
</body>
</html>