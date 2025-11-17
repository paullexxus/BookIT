<?php
/**
 * Email Integration Functions
 * Connects notifications to actual emails
 */

/**
 * I-send ang reservation confirmation email
 * @param string $user_email - Recipient email
 * @param string $user_name - Recipient name
 * @param array $reservation - Reservation data
 * @return bool - True if sent
 */
function sendReservationConfirmationEmail($user_email, $user_name, $reservation) {
    $subject = "Reservation Confirmed - BookIT";
    
    $check_out = date('M d, Y', strtotime($reservation['check_out_date']));
    $check_in = date('M d, Y', strtotime($reservation['check_in_date']));
    $total = number_format($reservation['total_amount'], 2);
    $unit = htmlspecialchars($reservation['unit_number']);
    $branch = htmlspecialchars($reservation['branch_name']);
    $res_id = htmlspecialchars($reservation['reservation_id']);
    
    $message = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; border-radius: 5px; text-align: center; }
            .details { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>✓ Reservation Confirmed!</h2>
            </div>
            <p>Hello $user_name,</p>
            <p>Your reservation is confirmed! Here are your booking details:</p>
            <div class='details'>
                <div class='detail-row'><span><strong>Reservation ID:</strong></span><span>#$res_id</span></div>
                <div class='detail-row'><span><strong>Unit:</strong></span><span>$unit</span></div>
                <div class='detail-row'><span><strong>Location:</strong></span><span>$branch</span></div>
                <div class='detail-row'><span><strong>Check-in:</strong></span><span>$check_in</span></div>
                <div class='detail-row'><span><strong>Check-out:</strong></span><span>$check_out</span></div>
                <div class='detail-row'><span><strong>Total:</strong></span><span>P$total</span></div>
            </div>
            <p>Thank you for choosing BookIT!</p>
        </div>
    </body>
    </html>
    HTML;
    
    return sendEmailViaPhpMail($user_email, $user_name, $subject, $message);
}

/**
 * I-send ang email gamit ang PHP mail()
 */
function sendEmailViaPhpMail($to, $to_name, $subject, $message) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: BookIT <noreply@bookit.com>\r\n";
    $headers .= "Reply-To: support@bookit.com\r\n";
    
    $result = @mail($to, $subject, $message, $headers);
    
    if ($result) {
        error_log("Email sent to: $to");
    } else {
        error_log("Email FAILED to: $to");
    }
    
    return $result;
}

/**
 * I-send ang payment confirmation email
 */
function sendPaymentConfirmationEmail($user_email, $user_name, $amount) {
    $subject = "Payment Received - BookIT";
    $total = number_format($amount, 2);
    
    $message = <<<HTML
    <!DOCTYPE html>
    <html>
    <body>
        <p>Hello $user_name,</p>
        <p>Your payment of P$total has been successfully received.</p>
        <p>Your booking is now confirmed. Check your BookIT account for details.</p>
        <p>Thank you!</p>
    </body>
    </html>
    HTML;
    
    return sendEmailViaPhpMail($user_email, $user_name, $subject, $message);
}

?>

    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; border-radius: 5px; text-align: center; }
            .details { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd; }
            .label { font-weight: bold; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>✅ Your Reservation is Confirmed!</h2>
            </div>
            
            <p>Hello <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
            
            <p>Great news! Your reservation has been confirmed. Here are your booking details:</p>
            
            <div class='details'>
                <div class='detail-row'>
                    <span class='label'>Reservation ID:</span>
                    <span>#" . htmlspecialchars($reservation['reservation_id']) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Unit:</span>
                    <span>" . htmlspecialchars($reservation['unit_number']) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Location:</span>
                    <span>" . htmlspecialchars($reservation['branch_name']) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Check-in:</span>
                    <span>" . htmlspecialchars(date('M d, Y', strtotime($reservation['check_in_date']))) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Check-out:</span>
                    <span>" . htmlspecialchars(date('M d, Y', strtotime($reservation['check_out_date']))) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Total Amount:</span>
                    <span>₱" . number_format($reservation['total_amount'], 2) . "</span>
                </div>
            </div>
            
            <p><strong>Next Steps:</strong></p>
            <ul>
                <li>Prepare your valid ID for check-in</li>
                <li>Review the house rules and policies</li>
                <li>Contact us if you have any questions</li>
            </ul>
            
            <div style='text-align: center;'>
                <a href='" . SITE_URL . "/renter/my_bookings.php' class='button'>View My Bookings</a>
            </div>
            
            <div class='footer'>
                <p>Thank you for choosing BookIT!</p>
                <p>Questions? Contact us at support@bookit.com</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmailViaPhpMail($user_email, $user_name, $subject, $message);
}

/**
 * I-send ang payment confirmation email
 * @param string $user_email - Recipient email
 * @param string $user_name - Recipient name
 * @param array $payment - Payment data
 * @return bool - True if sent
 */
function sendPaymentConfirmationEmail($user_email, $user_name, $payment) {
    $subject = "💳 Payment Received - BookIT";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; border-radius: 5px; text-align: center; }
            .details { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd; }
            .label { font-weight: bold; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>💳 Payment Received!</h2>
            </div>
            
            <p>Hello <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
            
            <p>Your payment has been successfully processed. Thank you!</p>
            
            <div class='details'>
                <div class='detail-row'>
                    <span class='label'>Payment ID:</span>
                    <span>#" . htmlspecialchars(substr($payment['transaction_reference'], 0, 12)) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Amount:</span>
                    <span>₱" . number_format($payment['amount'], 2) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Payment Method:</span>
                    <span>" . ucfirst(str_replace('_', ' ', htmlspecialchars($payment['payment_method']))) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Status:</span>
                    <span style='color: #28a745;'><strong>Confirmed</strong></span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Date:</span>
                    <span>" . htmlspecialchars(date('M d, Y H:i', strtotime($payment['payment_date']))) . "</span>
                </div>
            </div>
            
            <p>Your booking is now confirmed. You will receive check-in details 24 hours before your scheduled arrival.</p>
            
            <div class='footer'>
                <p>Thank you for choosing BookIT!</p>
                <p>Questions? Contact us at support@bookit.com</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmailViaPhpMail($user_email, $user_name, $subject, $message);
}

/**
 * I-send ang email gamit ang PHP mail()
 * Simple approach - walang external library
 * @param string $to - Recipient email
 * @param string $to_name - Recipient name
 * @param string $subject - Email subject
 * @param string $message - Email body (HTML)
 * @return bool - True if sent
 */
function sendEmailViaPhpMail($to, $to_name, $subject, $message) {
    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: BookIT <noreply@bookit.com>\r\n";
    $headers .= "Reply-To: support@bookit.com\r\n";
    
    // Send email gamit ang PHP mail()
    // Note: Kelangan mo i-configure ang mail server ng server mo (php.ini SMTP settings)
    $result = @mail($to, $subject, $message, $headers);
    
    if ($result) {
        // Log email sent
        error_log("Email sent to: $to - Subject: $subject");
    } else {
        error_log("Email FAILED to: $to - Subject: $subject");
    }
    
    return $result;
}

/**
 * I-send ang admin notification tungkol sa new booking
 * @param array $reservation - Reservation data
 * @param string $admin_email - Admin email
 * @return bool - True if sent
 */
function sendAdminBookingNotification($reservation, $admin_email = 'admin@bookit.com') {
    $subject = "🔔 New Booking - Unit " . $reservation['unit_number'];
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; border-radius: 5px; text-align: center; }
            .details { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd; }
            .label { font-weight: bold; }
            .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🔔 New Booking Received!</h2>
            </div>
            
            <p>A new reservation has been created:</p>
            
            <div class='details'>
                <div class='detail-row'>
                    <span class='label'>Reservation ID:</span>
                    <span>#" . htmlspecialchars($reservation['reservation_id']) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Unit:</span>
                    <span>" . htmlspecialchars($reservation['unit_number']) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Branch:</span>
                    <span>" . htmlspecialchars($reservation['branch_name']) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Duration:</span>
                    <span>" . htmlspecialchars(date('M d, Y', strtotime($reservation['check_in_date']))) . " - " . htmlspecialchars(date('M d, Y', strtotime($reservation['check_out_date']))) . "</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Total Amount:</span>
                    <span>₱" . number_format($reservation['total_amount'], 2) . "</span>
                </div>
            </div>
            
            <p>Login to the admin dashboard to review and approve/reject this booking.</p>
            
            <div style='text-align: center;'>
                <a href='" . SITE_URL . "/admin/admin_dashboard.php' class='button'>View in Dashboard</a>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmailViaPhpMail($admin_email, 'Admin', $subject, $message);
}

?>
