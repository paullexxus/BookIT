<?php
/**
 * Payment API Endpoint - PayMongo Processing (consolidated)
 *
 * This file was cleaned up to remove duplicated blocks. It exposes actions:
 *  - create_source
 *  - process_payment
 *  - verify_payment
 *  - handle_webhook
 */

session_start();
require_once '../../../config/db.php';
require_once '../../../config/paymongo.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/email_integration.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create_source':
            createSource();
            break;

        case 'process_payment':
            processPaymentWithPayMongo();
            break;

        case 'verify_payment':
            verifyPaymentStatus();
            break;

        case 'handle_webhook':
            handlePaymongoWebhook();
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}


/**
 * Create payment source and return checkout url + source id
 */
function createSource() {
    $amount = (int)($_POST['amount'] ?? 0);
    $type = $_POST['payment_method'] ?? 'card';
    $reservation_id = $_POST['reservation_id'] ?? null;
    $amenity_booking_id = $_POST['amenity_booking_id'] ?? null;

    // Build redirect URLs
    $success_url = SITE_URL . '/includes/api/payment/handle_payment.php?status=success&reservation=' . urlencode($reservation_id) . '&amenity=' . urlencode($amenity_booking_id);
    $failed_url = SITE_URL . '/includes/api/payment/handle_payment.php?status=failed&reservation=' . urlencode($reservation_id) . '&amenity=' . urlencode($amenity_booking_id);

    $response = createPaymongoSource($type, [
        'amount' => $amount,
        'success_url' => $success_url,
        'failed_url' => $failed_url
    ]);

    if (empty($response) || !isset($response['data']['attributes']['checkout_url'])) {
        throw new Exception('Failed to create payment source');
    }

    $source_id = $response['data']['id'];
    $checkout_url = $response['data']['attributes']['checkout_url'];

    $sql = "INSERT INTO payment_sources (user_id, reservation_id, amenity_booking_id, source_id_paymongo, payment_method, amount, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')";
    execute_query($sql, [$_SESSION['user_id'], $reservation_id, $amenity_booking_id, $source_id, $type, $amount]);

    echo json_encode([
        'success' => true,
        'checkout_url' => $checkout_url,
        'source_id' => $source_id
    ]);
}


/**
 * Process a payment using a PayMongo source
 */
function processPaymentWithPayMongo() {
    global $conn;

    $source_id = $_POST['source_id'] ?? '';
    $amount = (int)($_POST['amount'] ?? 0);
    $reservation_id = $_POST['reservation_id'] ?? null;
    $amenity_booking_id = $_POST['amenity_booking_id'] ?? null;

    $response = createPaymongoPayment($source_id, [
        'amount' => $amount,
        'description' => 'BookIT ' . ($reservation_id ? 'Reservation #' . $reservation_id : 'Amenity Booking #' . $amenity_booking_id)
    ]);

    if (empty($response) || !isset($response['data']['id'])) {
        throw new Exception('Payment processing failed');
    }

    $payment_id = $response['data']['id'];
    $payment_status = $response['data']['attributes']['status'] ?? 'pending';

    $sql = "INSERT INTO payments (reservation_id, amenity_booking_id, user_id, amount, payment_method, payment_status, transaction_reference) 
            VALUES (?, ?, ?, ?, 'paymongo', ?, ?)";

    $db_payment_id = null;
    if (execute_query($sql, [$reservation_id, $amenity_booking_id, $_SESSION['user_id'], $amount, $payment_status, $payment_id])) {
        $db_payment_id = $conn->insert_id;
    }

    if ($payment_status === 'paid') {
        if ($reservation_id) {
            $res_sql = "UPDATE reservations SET payment_status = 'paid', status = 'confirmed' WHERE reservation_id = ? AND user_id = ?";
            execute_query($res_sql, [$reservation_id, $_SESSION['user_id']]);

            $reservation = get_single_result("SELECT r.*, u.unit_number, b.branch_name FROM reservations r 
                                            JOIN units u ON r.unit_id = u.unit_id 
                                            JOIN branches b ON r.branch_id = b.branch_id 
                                            WHERE r.reservation_id = ?", [$reservation_id]);

            if ($reservation) {
                $user = get_single_result("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
                if (function_exists('sendReservationConfirmationEmail')) {
                    sendReservationConfirmationEmail($user['email'], $user['full_name'], $reservation);
                }
                sendNotification($_SESSION['user_id'], 'Payment Confirmed', 'Your payment for Unit ' . $reservation['unit_number'] . ' has been received. Reservation confirmed!', 'payment', 'email');
            }
        } elseif ($amenity_booking_id) {
            $amen_sql = "UPDATE amenity_bookings SET status = 'confirmed' WHERE booking_id = ? AND user_id = ?";
            execute_query($amen_sql, [$amenity_booking_id, $_SESSION['user_id']]);
            sendNotification($_SESSION['user_id'], 'Amenity Booking Confirmed', 'Your payment for amenity booking has been received. Booking confirmed!', 'payment', 'email');
        }
    }

    echo json_encode([
        'success' => $payment_status === 'paid',
        'payment_id' => $payment_id,
        'status' => $payment_status,
        'db_payment_id' => $db_payment_id,
        'message' => $payment_status === 'paid' ? 'Payment successful!' : 'Payment pending'
    ]);
}


/**
 * Verify payment status by fetching from PayMongo
 */
function verifyPaymentStatus() {
    $payment_id = $_GET['payment_id'] ?? '';

    $response = getPaymongoPayment($payment_id);

    if (empty($response) || !isset($response['data'])) {
        throw new Exception('Payment not found');
    }

    $status = $response['data']['attributes']['status'] ?? 'unknown';

    $sql = "UPDATE payments SET payment_status = ? WHERE transaction_reference = ?";
    execute_query($sql, [$status, $payment_id]);

    echo json_encode([
        'success' => true,
        'status' => $status,
        'data' => $response['data']['attributes'] ?? null
    ]);
}


/**
 * Handle PayMongo webhook
 */
function handlePaymongoWebhook() {
    $payload = json_decode(file_get_contents('php://input'), true);

    if (!isset($payload['data']['attributes']['data']['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false]);
        return;
    }

    $payment_id = $payload['data']['attributes']['data']['id'];
    $payment_status = $payload['data']['attributes']['data']['attributes']['status'] ?? null;

    $sql = "UPDATE payments SET payment_status = ? WHERE transaction_reference = ?";
    execute_query($sql, [$payment_status, $payment_id]);

    $payment = get_single_result("SELECT * FROM payments WHERE transaction_reference = ?", [$payment_id]);

    if ($payment && $payment_status === 'paid') {
        if (!empty($payment['reservation_id'])) {
            $res_sql = "UPDATE reservations SET payment_status = 'paid', status = 'confirmed' WHERE reservation_id = ?";
            execute_query($res_sql, [$payment['reservation_id']]);
            sendNotification($payment['user_id'], 'Payment Confirmed via Webhook', 'Your payment has been confirmed.', 'payment', 'system');
        }
    }

    http_response_code(200);
    echo json_encode(['success' => true]);
}

?>
