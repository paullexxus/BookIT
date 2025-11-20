<?php
// Public session management - allows viewing without login
// Ensure consistent session settings
session_start();
require_once __DIR__ . '/../config/constants.php';
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// No login requirement for public pages
// Users can browse without being logged in
?>
