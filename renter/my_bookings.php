<?php
// BookIT My Bookings
// Multi-branch Condo Rental Reservation System

include '../includes/session.php';
include '../includes/functions.php';
include_once '../includes/renter_functions.php';
checkRole(['renter']); // Tanging renters lang ang pwede

$message = '';
$error = '';
$csrf_token = generateCSRFToken();

// Handle booking removal (for pending bookings)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_booking'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    } else {
        $reservationId = (int)$_POST['reservation_id'];
        
        // I-check kung pwede pa i-remove (awaiting_approval status only)
        $reservation = get_single_result(
            "SELECT * FROM reservations WHERE reservation_id = ? AND user_id = ?",
            [$reservationId, $_SESSION['user_id']]
        );
        
        if ($reservation && $reservation['status'] == 'awaiting_approval') {
            // I-delete ang reservation completely
            $sql = "DELETE FROM reservations WHERE reservation_id = ?";
            if (execute_query($sql, [$reservationId])) {
                $message = "Booking removed successfully!";
            } else {
                $error = "Failed to remove booking.";
            }
        } else {
            $error = "Cannot remove booking. Only pending bookings can be removed.";
        }
    }
}

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    } else {
        $reservationId = (int)$_POST['reservation_id'];
        
        // I-check kung pwede pa i-cancel (at least 24 hours before check-in)
        $reservation = get_single_result(
            "SELECT * FROM reservations WHERE reservation_id = ? AND user_id = ?",
            [$reservationId, $_SESSION['user_id']]
        );
        
        if ($reservation) {
            $checkInDate = new DateTime($reservation['check_in_date']);
            $today = new DateTime();
            $hoursUntilCheckIn = $today->diff($checkInDate)->h + ($today->diff($checkInDate)->days * 24);
            
            if ($hoursUntilCheckIn >= 24 && $reservation['status'] == 'confirmed') {
                // I-cancel ang reservation
                $sql = "UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ?";
                if (execute_query($sql, [$reservationId])) {
                    // Mag-send ng notification
                    sendNotification(
                        $_SESSION['user_id'],
                        "Reservation Cancelled",
                        "Your reservation #" . $reservationId . " has been cancelled successfully.",
                        'booking',
                        'system'
                    );
                    
                    // Notify host
                    $unit = get_single_result("SELECT u.host_id FROM units u WHERE unit_id = ?", [$reservation['unit_id']]);
                    if ($unit && $unit['host_id']) {
                        sendNotification(
                            $unit['host_id'],
                            "Reservation Cancelled",
                            "Renter has cancelled reservation #" . $reservationId . ".",
                            'booking',
                            'system'
                        );
                    }
                    
                    $message = "Reservation cancelled successfully!";
                } else {
                    $error = "Failed to cancel reservation.";
                }
            } else {
                $error = "Cannot cancel reservation. Must be cancelled at least 24 hours before check-in.";
            }
        } else {
            $error = "Reservation not found.";
        }
    }
}

// Kumuha ng user reservations - FIXED: Use prepared statements
$reservations = get_multiple_results(
    "SELECT r.*, u.unit_number, u.unit_type, b.branch_name, b.address 
    FROM reservations r 
    JOIN units u ON r.unit_id = u.unit_id 
    JOIN branches b ON r.branch_id = b.branch_id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC",
    [$_SESSION['user_id']]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - BookIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/renter/my_bookings.css">
</head>
<body>
    <div class="container-fluid">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="../public/index.php">
                    <i class="fas fa-building"></i> BookIT
                </a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="reserve_unit.php">
                        <i class="fas fa-plus"></i> Reserve Unit
                    </a>
                    <a class="nav-link active" href="my_bookings.php">
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

        <div class="container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-calendar-check"></i> My Bookings</h2>
                <a href="reserve_unit.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Reservation
                </a>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Bookings List -->
            <?php if (!empty($reservations) && count($reservations) > 0): ?>
                <div class="row">
                    <?php foreach ($reservations as $booking): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card booking-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-home"></i> Unit <?php echo $booking['unit_number']; ?>
                                    </h6>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php 
                                            if ($booking['status'] == 'awaiting_approval') {
                                                echo '<i class="fas fa-clock me-1"></i>Pending Approval';
                                            } elseif ($booking['status'] == 'approved') {
                                                echo '<i class="fas fa-check me-1"></i>Approved';
                                            } elseif ($booking['status'] == 'confirmed') {
                                                echo '<i class="fas fa-check-double me-1"></i>Confirmed';
                                            } else {
                                                echo ucfirst(str_replace('_', ' ', $booking['status']));
                                            }
                                        ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        <strong><i class="fas fa-building"></i> Branch:</strong> <?php echo $booking['branch_name']; ?><br>
                                        <strong><i class="fas fa-map-marker-alt"></i> Address:</strong> <?php echo $booking['address']; ?><br>
                                        <strong><i class="fas fa-bed"></i> Type:</strong> <?php echo $booking['unit_type']; ?><br>
                                        <strong><i class="fas fa-calendar"></i> Check-in:</strong> <?php echo formatDate($booking['check_in_date']); ?><br>
                                        <strong><i class="fas fa-calendar"></i> Check-out:</strong> <?php echo formatDate($booking['check_out_date']); ?><br>
                                        <strong><i class="fas fa-clock"></i> Duration:</strong> <?php echo calculateDays($booking['check_in_date'], $booking['check_out_date']); ?> days<br>
                                        <strong><i class="fas fa-money-bill-wave"></i> Total Amount:</strong> <?php echo format_currency($booking['total_amount']); ?><br>
                                        <strong><i class="fas fa-shield-alt"></i> Security Deposit:</strong> <?php echo format_currency($booking['security_deposit']); ?>
                                    </p>
                                    
                                    <?php if ($booking['special_requests']): ?>
                                        <div class="alert alert-info">
                                            <strong><i class="fas fa-comment"></i> Special Requests:</strong><br>
                                            <?php echo $booking['special_requests']; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Approval Status Alert -->
                                    <?php if ($booking['status'] == 'awaiting_approval'): ?>
                                        <div class="alert alert-warning alert-sm" style="padding: 10px; margin-bottom: 15px;">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <small>Your booking is awaiting approval from the branch host. You'll be notified once they review it.</small>
                                        </div>
                                    <?php elseif ($booking['status'] == 'rejected'): ?>
                                        <div class="alert alert-danger alert-sm" style="padding: 10px; margin-bottom: 15px;">
                                            <i class="fas fa-times-circle me-1"></i>
                                            <small><?php echo !empty($booking['rejection_reason']) ? 'Reason: ' . htmlspecialchars($booking['rejection_reason']) : 'This booking has been rejected.'; ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <span class="payment-badge payment-<?php echo $booking['payment_status']; ?>">
                                            Payment: <?php echo ucfirst(str_replace('_', ' ', $booking['payment_status'])); ?>
                                        </span>
                                        <small class="text-muted">
                                            Booked: <?php echo formatDate($booking['created_at']); ?>
                                        </small>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="mt-3 d-flex gap-2 flex-wrap">
                                        <button class="btn btn-sm btn-info" 
                                                onclick="showBookingDetailsModal(<?php echo htmlspecialchars(json_encode($booking), ENT_QUOTES, 'UTF-8'); ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        
                                        <?php if ($booking['status'] == 'confirmed'): ?>
                                            <?php
                                            $checkInDate = new DateTime($booking['check_in_date']);
                                            $today = new DateTime();
                                            $hoursUntilCheckIn = $today->diff($checkInDate)->h + ($today->diff($checkInDate)->days * 24);
                                            ?>
                                            <?php if ($hoursUntilCheckIn >= 24): ?>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmCancel(<?php echo $booking['reservation_id']; ?>)">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($booking['status'] == 'approved'): ?>
                                            <form action="checkout.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $booking['reservation_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-credit-card"></i> Pay Now
                                                </button>
                                            </form>
                                        <?php elseif ($booking['status'] == 'awaiting_approval'): ?>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmRemove(<?php echo $booking['reservation_id']; ?>)">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        <?php elseif ($booking['status'] == 'rejected'): ?>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmRemove(<?php echo $booking['reservation_id']; ?>)">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h5>No Bookings Found</h5>
                    <p class="text-muted">You haven't made any reservations yet.</p>
                    <a href="reserve_unit.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Make Your First Reservation
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Remove Booking Modal -->
    <div class="modal fade" id="removeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="reservation_id" id="remove_reservation_id">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger"><i class="fas fa-trash"></i> Remove Booking</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to remove this booking?</p>
                        <p class="text-muted">This will permanently delete your booking request. You can make a new booking if needed.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                        <button type="submit" name="remove_booking" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Remove Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="reservation_id" id="cancel_reservation_id">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle"></i> Confirm Cancellation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to cancel this reservation?</p>
                        <p class="text-muted">This action cannot be undone. Any payments made may be subject to refund policies.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Reservation</button>
                        <button type="submit" name="cancel_booking" class="btn btn-danger">
                            <i class="fas fa-times"></i> Cancel Reservation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1" size="lg">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalTitle">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="bookingDetailsContent">
                        <!-- Details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/renter/my_bookings.js"></script>
</body>
</html>
