-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 17, 2025 at 11:36 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `condo_rental_reservation_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `address_verification`
--

DROP TABLE IF EXISTS `address_verification`;
CREATE TABLE IF NOT EXISTS `address_verification` (
  `address_id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `building_name` varchar(255) DEFAULT NULL,
  `street_address` varchar(500) DEFAULT NULL,
  `unit_number` varchar(50) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `full_address` varchar(1000) DEFAULT NULL,
  `address_hash` varchar(128) DEFAULT NULL,
  `normalized_address` varchar(1000) DEFAULT NULL,
  `duplicate_count` int DEFAULT '0',
  `matching_units` json DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`address_id`),
  KEY `unit_id` (`unit_id`),
  KEY `address_hash` (`address_hash`),
  KEY `normalized_address` (`normalized_address`(250))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `amenities`
--

DROP TABLE IF EXISTS `amenities`;
CREATE TABLE IF NOT EXISTS `amenities` (
  `amenity_id` int NOT NULL AUTO_INCREMENT,
  `branch_id` int NOT NULL,
  `amenity_name` varchar(100) NOT NULL,
  `description` text,
  `hourly_rate` decimal(8,2) DEFAULT '0.00',
  `is_available` tinyint(1) DEFAULT '1',
  `max_capacity` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`amenity_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `amenities`
--

INSERT INTO `amenities` (`amenity_id`, `branch_id`, `amenity_name`, `description`, `hourly_rate`, `is_available`, `max_capacity`, `created_at`) VALUES
(1, 1, 'Swimming Pool', 'Olympic-size swimming pool', 200.00, 1, 20, '2025-11-15 08:10:19'),
(2, 1, 'Gym', 'Fully equipped fitness center', 150.00, 1, 15, '2025-11-15 08:10:19'),
(3, 2, 'Swimming Pool', 'Rooftop swimming pool', 250.00, 1, 25, '2025-11-15 08:10:19'),
(4, 3, 'Swimming Pool', 'Indoor swimming pool', 180.00, 1, 15, '2025-11-15 08:10:19'),
(5, 1, 'Swimming Pool', 'Olympic-size swimming pool', 200.00, 1, 20, '2025-11-15 08:10:21'),
(6, 1, 'Gym', 'Fully equipped fitness center', 150.00, 1, 15, '2025-11-15 08:10:21'),
(7, 2, 'Swimming Pool', 'Rooftop swimming pool', 250.00, 1, 25, '2025-11-15 08:10:21'),
(8, 3, 'Swimming Pool', 'Indoor swimming pool', 180.00, 1, 15, '2025-11-15 08:10:21'),
(9, 1, 'Swimming Pool', 'Olympic-size swimming pool', 200.00, 1, 20, '2025-11-15 08:11:33'),
(10, 1, 'Gym', 'Fully equipped fitness center', 150.00, 1, 15, '2025-11-15 08:11:33'),
(11, 2, 'Swimming Pool', 'Rooftop swimming pool', 250.00, 1, 25, '2025-11-15 08:11:33'),
(12, 3, 'Swimming Pool', 'Indoor swimming pool', 180.00, 1, 15, '2025-11-15 08:11:33');

-- --------------------------------------------------------

--
-- Table structure for table `amenity_bookings`
--

DROP TABLE IF EXISTS `amenity_bookings`;
CREATE TABLE IF NOT EXISTS `amenity_bookings` (
  `booking_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `amenity_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `total_amount` decimal(8,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  KEY `user_id` (`user_id`),
  KEY `amenity_id` (`amenity_id`),
  KEY `fk_amenity_bookings_branch` (`branch_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `amenity_bookings`
--

INSERT INTO `amenity_bookings` (`booking_id`, `user_id`, `amenity_id`, `branch_id`, `booking_date`, `start_time`, `end_time`, `total_amount`, `status`, `created_at`) VALUES
(1, 10, 4, 3, '2025-11-15', '06:30:00', '18:30:00', 2160.00, 'pending', '2025-11-15 09:11:25');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
CREATE TABLE IF NOT EXISTS `branches` (
  `branch_id` int NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `host_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`branch_id`),
  KEY `manager_id` (`host_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`, `address`, `city`, `contact_number`, `email`, `host_id`, `is_active`, `created_at`, `status`) VALUES
(3, 'N and M Suite Staycation', 'SMDC Trees Residences, Quezon City, Philippines, 0000', 'Quezon City', '0935 168 7175', 'nampscl@gmail.com', 11, 1, '2025-11-15 07:47:35', 'active'),
(4, 'BookIT Makati', '123 Ayala Avenue, Makati City', 'Makati', '02-8123-4567', 'makati@bookit.com', NULL, 0, '2025-11-15 08:10:19', 'active'),
(5, 'BookIT BGC', '456 BGC High Street, Taguig City', 'Taguig', '02-8123-4568', 'bgc@bookit.com', NULL, 0, '2025-11-15 08:10:19', 'active'),
(6, 'BookIT Ortigas', '789 Ortigas Center, Pasig City', 'Pasig', '02-8123-4569', 'ortigas@bookit.com', NULL, 0, '2025-11-15 08:10:19', 'active'),
(7, 'BookIT Makati', '123 Ayala Avenue, Makati City', 'Makati', '02-8123-4567', 'makati@bookit.com', NULL, 0, '2025-11-15 08:10:21', 'active'),
(8, 'BookIT BGC', '456 BGC High Street, Taguig City', 'Taguig', '02-8123-4568', 'bgc@bookit.com', NULL, 0, '2025-11-15 08:10:21', 'active'),
(9, 'BookIT Ortigas', '789 Ortigas Center, Pasig City', 'Pasig', '02-8123-4569', 'ortigas@bookit.com', NULL, 0, '2025-11-15 08:10:21', 'active'),
(10, 'BookIT Makati', '123 Ayala Avenue, Makati City', 'Makati', '02-8123-4567', 'makati@bookit.com', NULL, 0, '2025-11-15 08:11:33', 'active'),
(11, 'BookIT BGC', '456 BGC High Street, Taguig City', 'Taguig', '02-8123-4568', 'bgc@bookit.com', NULL, 0, '2025-11-15 08:11:33', 'active'),
(12, 'BookIT Ortigas', '789 Ortigas Center, Pasig City', 'Pasig', '02-8123-4569', 'ortigas@bookit.com', NULL, 0, '2025-11-15 08:11:33', 'active'),
(13, 'BookIT Manila', '123 Makati Avenue, Makati City', 'Makati', '09175555678', 'manila@bookit.com', 3, 0, '2025-11-15 08:27:52', 'active'),
(14, 'Celadon Park Condominium', '8009, 1014 Felix Huertas Rd, Santa Cruz, Manila, Metro Manila', 'Metro Manila', '(02) 8848 5100', '', 3, 1, '2025-11-15 11:24:06', 'active'),
(15, 'Robinsons Place Residences', 'Padre Faura St, Ermita, Manila, 1000 Metro Manila', 'Metro Manila', '0925 777 7777', '', 4, 1, '2025-11-15 11:30:12', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `duplicate_detection_logs`
--

DROP TABLE IF EXISTS `duplicate_detection_logs`;
CREATE TABLE IF NOT EXISTS `duplicate_detection_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `duplicate_unit_id` int DEFAULT NULL,
  `detection_type` enum('address','geolocation','image','phone_cross','host_identity','manual') NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'low',
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `details` json DEFAULT NULL,
  `action_taken` enum('flagged','warning','suspended','approved','pending_review') DEFAULT 'pending_review',
  `admin_notes` text,
  `reviewed_by` int DEFAULT NULL,
  `review_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `duplicate_unit_id` (`duplicate_unit_id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `unit_id` (`unit_id`),
  KEY `detection_type` (`detection_type`),
  KEY `severity` (`severity`),
  KEY `action_taken` (`action_taken`),
  KEY `created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `host_contact_verification`
--

DROP TABLE IF EXISTS `host_contact_verification`;
CREATE TABLE IF NOT EXISTS `host_contact_verification` (
  `contact_id` int NOT NULL AUTO_INCREMENT,
  `host_id` int NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `phone_hash` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `email_hash` varchar(255) DEFAULT NULL,
  `payout_account_id` int DEFAULT NULL,
  `linked_hosts` json DEFAULT NULL,
  `duplicate_listings` json DEFAULT NULL,
  `verification_level` int DEFAULT '0',
  `flagged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`contact_id`),
  UNIQUE KEY `unique_host_contact` (`host_id`),
  KEY `host_id` (`host_id`),
  KEY `phone_hash` (`phone_hash`(250)),
  KEY `email_hash` (`email_hash`(250))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `host_payment_methods`
--

DROP TABLE IF EXISTS `host_payment_methods`;
CREATE TABLE IF NOT EXISTS `host_payment_methods` (
  `payment_method_id` int NOT NULL AUTO_INCREMENT,
  `host_id` int NOT NULL,
  `method_type` enum('paymongo','paypal') NOT NULL,
  `method_name` varchar(100) NOT NULL,
  `account_id` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_method_id`),
  UNIQUE KEY `unique_method` (`host_id`,`method_type`,`account_id`,`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `host_verification`
--

DROP TABLE IF EXISTS `host_verification`;
CREATE TABLE IF NOT EXISTS `host_verification` (
  `verification_id` int NOT NULL AUTO_INCREMENT,
  `host_id` int NOT NULL,
  `id_document_path` varchar(500) DEFAULT NULL,
  `id_verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `id_verified_date` timestamp NULL DEFAULT NULL,
  `face_photo_path` varchar(500) DEFAULT NULL,
  `face_verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `face_verified_date` timestamp NULL DEFAULT NULL,
  `profile_verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `profile_verified_date` timestamp NULL DEFAULT NULL,
  `phone_number_verified` tinyint(1) DEFAULT '0',
  `email_verified` tinyint(1) DEFAULT '0',
  `payout_account_id` int DEFAULT NULL,
  `payout_verified` tinyint(1) DEFAULT '0',
  `verification_score` int DEFAULT '0',
  `is_verified_host` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`verification_id`),
  UNIQUE KEY `host_id` (`host_id`),
  KEY `host_id_2` (`host_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `image_fingerprints`
--

DROP TABLE IF EXISTS `image_fingerprints`;
CREATE TABLE IF NOT EXISTS `image_fingerprints` (
  `fingerprint_id` int NOT NULL AUTO_INCREMENT,
  `image_id` int NOT NULL,
  `ahash` varchar(255) DEFAULT NULL,
  `phash` varchar(255) DEFAULT NULL,
  `dhash` varchar(255) DEFAULT NULL,
  `similarity_score` decimal(5,2) DEFAULT NULL,
  `matched_images` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`fingerprint_id`),
  UNIQUE KEY `unique_image_fingerprint` (`image_id`),
  KEY `image_id` (`image_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('booking','payment','reminder','system') NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `sent_via` enum('email','sms','system') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `type`, `is_read`, `sent_via`, `created_at`) VALUES
(1, 3, 'New Booking Request - Awaiting Approval', 'New reservation #2 needs your approval. Please review in your dashboard.', 'booking', 1, 'system', '2025-11-15 09:03:23'),
(2, 10, 'Booking Submitted for Approval', 'Your reservation #2 has been submitted and is awaiting host approval.', 'booking', 1, 'system', '2025-11-15 09:03:23'),
(3, 10, 'Reservation Created', 'Your reservation for Unit C101 has been created successfully. Reservation ID: 2', 'booking', 1, 'system', '2025-11-15 09:03:23'),
(4, 3, 'New Booking Request - Awaiting Approval', 'New reservation #3 needs your approval. Please review in your dashboard.', 'booking', 1, 'system', '2025-11-15 09:07:12'),
(5, 10, 'Booking Submitted for Approval', 'Your reservation #3 has been submitted and is awaiting host approval.', 'booking', 1, 'system', '2025-11-15 09:07:12'),
(6, 10, 'Reservation Created', 'Your reservation for Unit C101 has been created successfully. Reservation ID: 3', 'booking', 1, 'system', '2025-11-15 09:07:12'),
(7, 10, 'Amenity Booking Confirmed', 'Your booking for Swimming Pool has been confirmed for Nov 15, 2025 at 06:30', 'booking', 1, 'system', '2025-11-15 09:11:25'),
(8, 11, 'New Booking Request - Awaiting Approval', 'New reservation #4 needs your approval. Please review in your dashboard.', 'booking', 0, 'system', '2025-11-15 11:33:48'),
(10, 10, 'Reservation Created', 'Your reservation for Unit C101 has been created successfully. Reservation ID: 4', 'booking', 1, 'system', '2025-11-15 11:33:48'),
(11, 11, 'New Booking Request - Awaiting Approval', 'New reservation #5 needs your approval. Please review in your dashboard.', 'booking', 0, 'system', '2025-11-15 11:39:20'),
(12, 10, 'Booking Submitted for Approval', 'Your reservation #5 has been submitted and is awaiting host approval.', 'booking', 1, 'system', '2025-11-15 11:39:20');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `reservation_id` int DEFAULT NULL,
  `amenity_booking_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','gcash','paymaya','credit_card') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_reference` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `amenity_booking_id` (`amenity_booking_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_sources`
--

DROP TABLE IF EXISTS `payment_sources`;
CREATE TABLE IF NOT EXISTS `payment_sources` (
  `source_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reservation_id` int DEFAULT NULL,
  `amenity_booking_id` int DEFAULT NULL,
  `source_id_paymongo` varchar(100) NOT NULL,
  `payment_method` varchar(50) NOT NULL COMMENT 'gcash, card, grab_pay',
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`source_id`),
  UNIQUE KEY `source_id_paymongo` (`source_id_paymongo`),
  KEY `user_id` (`user_id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `amenity_booking_id` (`amenity_booking_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
CREATE TABLE IF NOT EXISTS `refunds` (
  `refund_id` int NOT NULL AUTO_INCREMENT,
  `reservation_id` int NOT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `refund_id_paymongo` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text,
  `status` varchar(50) DEFAULT NULL,
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refund_id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `processed_by` (`processed_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE IF NOT EXISTS `reservations` (
  `reservation_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `unit_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `security_deposit` decimal(10,2) DEFAULT '0.00',
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text,
  `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
  `special_requests` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `host_notes` text,
  `admin_notes` text,
  `cancellation_reason` text,
  `checked_in_by` int DEFAULT NULL,
  `checked_out_by` int DEFAULT NULL,
  PRIMARY KEY (`reservation_id`),
  KEY `user_id` (`user_id`),
  KEY `unit_id` (`unit_id`),
  KEY `approved_by` (`approved_by`),
  KEY `rejected_by` (`rejected_by`),
  KEY `fk_reservations_branch` (`branch_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `unit_id`, `branch_id`, `check_in_date`, `check_out_date`, `total_amount`, `security_deposit`, `status`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `rejection_reason`, `payment_status`, `special_requests`, `created_at`, `updated_at`, `host_notes`, `admin_notes`, `cancellation_reason`, `checked_in_by`, `checked_out_by`) VALUES
(1, 5, 4, 3, '2025-11-16', '2025-11-21', 2666.67, 5500.00, '', NULL, NULL, NULL, NULL, NULL, '', 'Please prepare WiFi and fresh towels', '2025-11-15 08:51:45', '2025-11-15 08:51:45', NULL, NULL, NULL, NULL, NULL),
(4, 10, 4, 3, '2025-11-15', '2025-11-16', 16000.00, 5500.00, '', NULL, NULL, NULL, NULL, NULL, '', '', '2025-11-15 11:33:48', '2025-11-15 11:33:48', NULL, NULL, NULL, NULL, NULL),
(3, 10, 4, 3, '2025-11-15', '2025-11-16', 16000.00, 5500.00, '', NULL, NULL, NULL, NULL, NULL, '', '', '2025-11-15 09:07:12', '2025-11-15 09:07:12', NULL, NULL, NULL, NULL, NULL),
(5, 10, 4, 3, '2025-11-16', '2025-11-17', 16000.00, 5500.00, '', NULL, NULL, NULL, NULL, NULL, '', '', '2025-11-15 11:39:20', '2025-11-15 11:39:20', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_notes`
--

DROP TABLE IF EXISTS `reservation_notes`;
CREATE TABLE IF NOT EXISTS `reservation_notes` (
  `note_id` int NOT NULL AUTO_INCREMENT,
  `reservation_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `note_type` enum('host','admin','internal') DEFAULT NULL,
  `note_text` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`note_id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `unit_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `rating` int DEFAULT NULL,
  `comment` text,
  `is_approved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `user_id` (`user_id`),
  KEY `unit_id` (`unit_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suspicious_listings_queue`
--

DROP TABLE IF EXISTS `suspicious_listings_queue`;
CREATE TABLE IF NOT EXISTS `suspicious_listings_queue` (
  `queue_id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `host_id` int NOT NULL,
  `reason` text,
  `overall_risk_score` decimal(5,2) DEFAULT NULL,
  `address_risk` decimal(5,2) DEFAULT '0.00',
  `location_risk` decimal(5,2) DEFAULT '0.00',
  `image_risk` decimal(5,2) DEFAULT '0.00',
  `contact_risk` decimal(5,2) DEFAULT '0.00',
  `identity_risk` decimal(5,2) DEFAULT '0.00',
  `status` enum('pending','under_review','approved','rejected','resolved') DEFAULT 'pending',
  `assigned_to` int DEFAULT NULL,
  `notes` text,
  `comparison_data` json DEFAULT NULL,
  `verification_attempts` int DEFAULT '0',
  `manual_review_requested` tinyint(1) DEFAULT '0',
  `video_call_scheduled` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`queue_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `unit_id` (`unit_id`),
  KEY `host_id` (`host_id`),
  KEY `status` (`status`),
  KEY `overall_risk_score` (`overall_risk_score`),
  KEY `created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext,
  `setting_type` varchar(20) DEFAULT NULL,
  `description` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'system_name', 'BookIT', 'text', 'System name displayed in browser', '2025-11-13 07:59:41'),
(2, 'company_name', 'BookIT Management', 'text', 'Company name', '2025-11-13 07:59:41'),
(3, 'address', 'Manila, Philippines', 'text', 'Company address', '2025-11-13 07:59:41'),
(4, 'email', 'admin@bookit.com', 'email', 'Contact email', '2025-11-13 07:59:41'),
(5, 'contact_number', '+63 9XX XXXX XXXX', 'text', 'Contact phone number', '2025-11-13 07:59:41'),
(6, 'timezone', 'Asia/Manila', 'text', 'System timezone', '2025-11-13 07:59:41'),
(7, 'date_format', 'MM/DD/YYYY', 'select', 'Date format', '2025-11-13 07:59:41'),
(8, 'sender_email', 'noreply@bookit.com', 'email', 'SMTP sender email', '2025-11-13 07:59:41'),
(9, 'smtp_host', '', 'text', 'SMTP host', '2025-11-13 07:59:41'),
(10, 'smtp_port', '587', 'number', 'SMTP port', '2025-11-13 07:59:41'),
(11, 'smtp_username', '', 'text', 'SMTP username', '2025-11-13 07:59:41'),
(12, 'smtp_password', '', 'password', 'SMTP password', '2025-11-13 07:59:41'),
(13, 'smtp_encryption', 'tls', 'select', 'SMTP encryption (tls/ssl/none)', '2025-11-13 07:59:41'),
(14, 'default_currency', 'PHP', 'text', 'Default currency', '2025-11-13 07:59:41'),
(15, 'transaction_fee', '2.5', 'number', 'Transaction fee percentage', '2025-11-13 07:59:41'),
(16, 'payment_instructions', '', 'textarea', 'Manual payment instructions', '2025-11-13 07:59:41'),
(17, 'login_attempts', '5', 'number', 'Max login attempts before lockout', '2025-11-13 07:59:41'),
(18, 'lockout_duration', '30', 'number', 'Lockout duration in minutes', '2025-11-13 07:59:41'),
(19, 'password_min_length', '8', 'number', 'Minimum password length', '2025-11-13 07:59:41'),
(20, 'session_timeout', '60', 'number', 'Session timeout in minutes', '2025-11-13 07:59:41'),
(21, 'force_https', '1', 'boolean', 'Force HTTPS connection', '2025-11-13 07:59:41'),
(22, 'two_factor_auth', '0', 'boolean', 'Enable 2FA for admin', '2025-11-13 07:59:41'),
(23, 'primary_color', '#3498db', 'color', 'Primary theme color', '2025-11-13 07:59:41'),
(24, 'secondary_color', '#2c3e50', 'color', 'Secondary theme color', '2025-11-13 07:59:41'),
(25, 'success_color', '#27ae60', 'color', 'Success status color', '2025-11-13 07:59:41'),
(26, 'danger_color', '#e74c3c', 'color', 'Danger status color', '2025-11-13 07:59:41'),
(27, 'logo_path', '/assets/images/logo.png', 'text', 'Logo file path', '2025-11-13 07:59:41'),
(28, 'favicon_path', '/assets/images/favicon.ico', 'text', 'Favicon file path', '2025-11-13 07:59:41'),
(29, 'banner_path', '/assets/images/banner.jpg', 'text', 'Homepage banner path', '2025-11-13 07:59:41'),
(30, 'homepage_title', 'Welcome to BookIT Rentals', 'text', 'Homepage title', '2025-11-13 07:59:41'),
(31, 'homepage_description', 'Find your perfect rental property', 'textarea', 'Homepage description', '2025-11-13 07:59:41'),
(32, 'footer_copyright', '© 2025 BookIT. All rights reserved.', 'text', 'Footer copyright text', '2025-11-13 07:59:41'),
(33, 'payment_methods', '[\"gcash\", \"bank_transfer\"]', 'json', 'Enabled payment methods', '2025-11-13 07:59:41'),
(34, 'notification_settings', '{\"reservation\": true, \"payment\": true, \"review\": true, \"system\": true}', 'json', 'Notification preferences', '2025-11-13 07:59:41');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
CREATE TABLE IF NOT EXISTS `units` (
  `unit_id` int NOT NULL AUTO_INCREMENT,
  `unit_name` varchar(255) NOT NULL,
  `host_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `unit_number` varchar(20) NOT NULL,
  `unit_type` varchar(50) NOT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `floor_number` int DEFAULT NULL,
  `monthly_rate` decimal(10,2) NOT NULL,
  `security_deposit` decimal(10,2) DEFAULT '0.00',
  `is_available` tinyint(1) DEFAULT '1',
  `description` text,
  `max_occupancy` int DEFAULT '2',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  `building_name` varchar(255) DEFAULT NULL,
  `street_address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address_hash` varchar(64) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`unit_id`),
  KEY `fk_branch` (`branch_id`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`unit_id`, `unit_name`, `host_id`, `branch_id`, `unit_number`, `unit_type`, `price`, `floor_number`, `monthly_rate`, `security_deposit`, `is_available`, `description`, `max_occupancy`, `created_at`, `is_active`, `building_name`, `street_address`, `city`, `address_hash`, `latitude`, `longitude`) VALUES
(1, '', 0, 1, 'A101', 'Studio', 0.00, 1, 15000.00, 5000.00, 1, 'Cozy studio unit with city view', 2, '2025-11-15 08:10:19', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(2, '', 0, 1, 'A102', '1BR', 0.00, 1, 20000.00, 7000.00, 1, '1 bedroom unit with balcony', 3, '2025-11-15 08:10:19', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(3, '', 0, 2, 'B101', 'Studio', 0.00, 1, 18000.00, 6000.00, 1, 'Modern studio in BGC', 2, '2025-11-15 08:10:19', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(4, '', 0, 3, 'C101', 'Studio', 0.00, 1, 16000.00, 5500.00, 1, 'Affordable studio in Ortigas', 2, '2025-11-15 08:10:19', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(5, '', 0, 1, 'A101', 'Studio', 0.00, 1, 15000.00, 5000.00, 1, 'Cozy studio unit with city view', 2, '2025-11-15 08:10:21', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(6, '', 0, 1, 'A102', '1BR', 0.00, 1, 20000.00, 7000.00, 1, '1 bedroom unit with balcony', 3, '2025-11-15 08:10:21', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(7, '', 0, 2, 'B101', 'Studio', 0.00, 1, 18000.00, 6000.00, 1, 'Modern studio in BGC', 2, '2025-11-15 08:10:21', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(8, '', 0, 3, 'C101', 'Studio', 0.00, 1, 16000.00, 5500.00, 1, 'Affordable studio in Ortigas', 2, '2025-11-15 08:10:21', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(9, '', 0, 1, 'A101', 'Studio', 0.00, 1, 15000.00, 5000.00, 1, 'Cozy studio unit with city view', 2, '2025-11-15 08:11:33', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(10, '', 0, 1, 'A102', '1BR', 0.00, 1, 20000.00, 7000.00, 1, '1 bedroom unit with balcony', 3, '2025-11-15 08:11:33', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(11, '', 0, 2, 'B101', 'Studio', 0.00, 1, 18000.00, 6000.00, 1, 'Modern studio in BGC', 2, '2025-11-15 08:11:33', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(12, '', 0, 3, 'C101', 'Studio', 0.00, 1, 16000.00, 5500.00, 1, 'Affordable studio in Ortigas', 2, '2025-11-15 08:11:33', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'Luxury Studio Unit', 3, 1, 'A101', 'Studio', 2500.00, 1, 2500.00, 500.00, 1, 'Modern studio with city view', 2, '2025-11-15 08:27:52', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(14, '1-Bedroom Deluxe', 3, 1, 'A102', '1BR', 3500.00, 1, 3500.00, 750.00, 1, '1 bedroom unit with balcony', 3, '2025-11-15 08:27:52', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(15, '2-Bedroom Family Suite', 3, 1, 'A103', '2BR', 5000.00, 2, 5000.00, 1000.00, 1, '2 bedrooms, perfect for families', 4, '2025-11-15 08:27:52', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 'unit101', 11, 15, '', '', 1233.00, NULL, 0.00, 0.00, 0, '', 12, '2025-11-17 11:17:42', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(26, 'unit101', 11, 15, '', '', 1233.00, NULL, 0.00, 0.00, 0, '', 12, '2025-11-17 11:19:12', 1, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `unit_geolocation`
--

DROP TABLE IF EXISTS `unit_geolocation`;
CREATE TABLE IF NOT EXISTS `unit_geolocation` (
  `geolocation_id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address_latitude` decimal(10,8) DEFAULT NULL,
  `address_longitude` decimal(11,8) DEFAULT NULL,
  `coordinate_hash` varchar(128) DEFAULT NULL,
  `proximity_matches` json DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`geolocation_id`),
  UNIQUE KEY `unique_unit_geolocation` (`unit_id`),
  KEY `unit_id` (`unit_id`),
  KEY `coordinate_hash` (`coordinate_hash`),
  KEY `latitude` (`latitude`),
  KEY `longitude` (`longitude`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `unit_images`
--

DROP TABLE IF EXISTS `unit_images`;
CREATE TABLE IF NOT EXISTS `unit_images` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `image_hash` varchar(128) DEFAULT NULL,
  `room_type` varchar(100) DEFAULT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_flagged` tinyint(1) DEFAULT '0',
  `flag_reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `unit_id` (`unit_id`),
  KEY `image_hash` (`image_hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','host','renter') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `branch_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `address` text,
  `profile_picture` varchar(255) DEFAULT NULL,
  `login_method` varchar(20) DEFAULT 'email',
  `condo_name` varchar(255) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `condo_address` text,
  `social_media` varchar(500) DEFAULT NULL,
  `valid_id1` varchar(500) DEFAULT NULL,
  `valid_id2` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `email_2` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `phone`, `role`, `branch_id`, `is_active`, `created_at`, `last_login`, `updated_at`, `address`, `profile_picture`, `login_method`, `condo_name`, `branch_name`, `condo_address`, `social_media`, `valid_id1`, `valid_id2`, `status`, `reset_token`, `token_expiry`) VALUES
(1, 'System Administrator', 'admin@bookit.com', '$2y$10$Yoq16Jp2ShTh5Bb3d.dTxu5YNm4s2OHe8uEKLln5bcBzE.y/0VtEm', '09171234567', 'admin', NULL, 1, '2025-11-14 17:50:35', NULL, '2025-11-14 17:50:35', NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL),
(2, 'Maria Garcia', 'maria.garcia@bookit.com', '$2y$10$MhkF4NujezxF3G9dAL/maO4Ofmjp/iE67QVvbdX/lB.Ykox.vfzRi', '09175551234', 'host', NULL, 0, '2025-11-14 17:50:35', NULL, '2025-11-15 05:36:30', NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL),
(3, 'Juan Santos', 'juan.santos@bookit.com', '$2y$10$e54JflgCNw1jVjNuNnAgUeaqARDFU0WmoAiKwuk3IBWKBXWJgd3Z.', '09175555678', 'host', NULL, 1, '2025-11-14 17:50:35', NULL, '2025-11-14 17:50:35', NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL),
(4, 'Angela Reyes', 'angela.reyes@bookit.com', '$2y$10$oytvhbYEeMevi.prhW/UWOEUOtwY5BohTgnCEjxH0EcBxX8e4l0ba', '09175559999', 'host', NULL, 1, '2025-11-14 17:50:35', NULL, '2025-11-14 17:50:35', NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL),
(5, 'Michael Johnson', 'michael.johnson@email.com', '$2y$10$nu3Y75zo72jraq3ejwg4MuZVs1QleezQrPMIGEjVe/vbvcKTx6iNO', '09161234567', 'renter', NULL, 1, '2025-11-14 17:50:36', NULL, '2025-11-14 17:50:36', NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL),
(6, 'Sarah Chen', 'sarah.chen@email.com', '$2y$10$o0nVB0HB671qDDdRL2/jkenEifVHehwKq1v.i2lR60i4HybMwTjre', '09161234568', 'renter', NULL, 1, '2025-11-14 17:50:36', NULL, '2025-11-14 17:50:36', NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL),
(7, 'Robert Cruz', 'robert.cruz@email.com', '$2y$10$gLUQ7lk8Q5imYvx5jerGPe0lMQGVSj99Lgr4QWsh6oksLFfOi96Q2', '09161234569', 'renter', NULL, 1, '2025-11-14 17:50:36', NULL, '2025-11-14 17:50:36', NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL),
(8, 'Lisa Wong', 'lisa.wong@email.com', '$2y$10$YL1bRUJukanTXpAigwTJS.9/oe7UoJjKmqdqS7Zao6PIWIarpIgt6', '09161234570', 'renter', NULL, 1, '2025-11-14 17:50:36', NULL, '2025-11-14 17:50:36', NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL),
(9, 'Carlos Mendoza', 'carlos.mendoza@email.com', '$2y$10$N4S0c4etH41culHh4po60e8DnUcU2Asky/.HiaVk4vl61CoWo4NlK', '09161234571', 'renter', NULL, 0, '2025-11-14 17:50:36', NULL, '2025-11-15 09:55:56', NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL),
(10, 'Antonio, Paul Lexxus B.', 'paullexxusantonio@gmail.com', '$2y$10$/Ls3x6Hxy1xcaBJsXLoAbuWipicAJFuxkX18oxExd93zrUyEMWUh2', '', 'renter', NULL, 1, '2025-11-15 05:21:46', NULL, '2025-11-15 05:21:46', NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL),
(11, 'vernalynpascual', 'vernalynpascual@bookit.com', '$2y$10$2AVaf1TlGR9M1h13SvZuauBDWoFjLywVr/rTmYo4U2aW6Gno/JGIC', '', 'host', 3, 1, '2025-11-15 10:00:59', NULL, '2025-11-15 10:00:59', NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_bank_accounts`
--

DROP TABLE IF EXISTS `user_bank_accounts`;
CREATE TABLE IF NOT EXISTS `user_bank_accounts` (
  `account_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_payment_methods`
--

DROP TABLE IF EXISTS `user_payment_methods`;
CREATE TABLE IF NOT EXISTS `user_payment_methods` (
  `method_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `method` varchar(50) NOT NULL,
  `account_details` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`method_id`),
  UNIQUE KEY `user_id` (`user_id`,`method`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
