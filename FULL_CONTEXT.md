# BookIT - Complete System Context & Architecture Documentation

**Version:** 2.1 | **Updated:** November 18, 2025 | **Status:** Production Ready

---

## 📑 Table of Contents
1. [Project Overview](#project-overview)
2. [Directory Structure](#directory-structure)
3. [Core Configuration Files](#core-configuration-files)
4. [Application Architecture](#application-architecture)
5. [Database Schema](#database-schema)
6. [Key Features & Implementation](#key-features--implementation)
7. [Security Implementation](#security-implementation)
8. [File-by-File Documentation](#file-by-file-documentation)
9. [Development & Testing](#development--testing)
10. [Troubleshooting & Logs](#troubleshooting--logs)

---

## Project Overview

### What is BookIT?
BookIT is a comprehensive Condo Rental Reservation System built with PHP and MySQL. It manages multi-property bookings, amenity reservations, payments, and user management with role-based access control.

### Core Technologies
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Payment Gateway:** PayMongo (GCash, Card, Grab Pay)
- **Email Service:** SMTP/Gmail
- **Server:** Apache (WAMP)

### System Roles
1. **Admin** - System administration, user management, branch management
2. **Host/Manager** - Property owner, manages units and reservations
3. **Renter** - Books units and amenities, makes payments

---

## Directory Structure

```
BookIT/
├── .git/                          # Git version control
├── .vscode/                       # VS Code settings
├── admin/                         # Admin panel (12 files)
│   ├── admin_dashboard.php        # Dashboard with statistics
│   ├── admin_profile.php          # Admin profile management
│   ├── manage_branch.php          # Branch CRUD operations
│   ├── user_management.php        # User CRUD operations
│   ├── unit_management.php        # Unit CRUD operations
│   ├── reports.php                # Analytics & reports
│   ├── settings.php               # System settings
│   ├── get_edit_unit_form.php     # AJAX form retrieval
│   ├── get_unit_details.php       # AJAX unit data
│   ├── update_unit.php            # AJAX unit updates
│   ├── update_admin.php           # AJAX profile updates
│   └── admin.txt                  # Documentation notes
│
├── ajax/                          # AJAX endpoints (6 files)
│   ├── check_duplicate.php        # Duplicate detection
│   ├── get_unit.php               # Unit data retrieval
│   ├── get_unit_view.php          # Formatted unit view
│   ├── get_branch_stats.php       # Branch statistics
│   ├── get_host_location.php      # Host location data
│   └── register_image_fingerprint.php  # Image tracking
│
├── assets/                        # Static files
│   ├── css/                       # Stylesheets (organized by role)
│   │   ├── admin/                 # Admin styles
│   │   ├── host/                  # Host styles
│   │   ├── manager/               # Manager styles
│   │   ├── modules/               # Module styles
│   │   ├── public/                # Public page styles
│   │   ├── renter/                # Renter styles
│   │   ├── sidebar.css            # Common sidebar
│   │   └── modals.css             # Modal components
│   ├── js/                        # JavaScript files
│   │   ├── admin/                 # Admin scripts
│   │   ├── host/                  # Host scripts
│   │   ├── manager/               # Manager scripts
│   │   ├── modules/               # Module scripts
│   │   ├── public/                # Public page scripts
│   │   └── renter/                # Renter scripts
│   └── images/                    # Image assets
│       └── branches/              # Branch images
│
├── config/                        # Configuration files (6 files)
│   ├── constants.php              # System constants & URLs
│   ├── db.php                     # Database connection & helpers
│   ├── paymongo.php               # PayMongo API configuration
│   ├── email.php                  # Email configuration
│   ├── file_paths.php             # File path constants
│   └── OAuth.php                  # OAuth configuration
│
├── docs/                          # Documentation
│   ├── context_diagram.md         # System context
│   ├── context_diagram.puml       # PlantUML context
│   ├── data_flow_diagram.puml     # PlantUML DFD
│   ├── dfd_level0.puml            # Context-level DFD
│   ├── dfd_level1.puml            # Detailed DFD
│   └── user_flowchart.puml        # User flow diagram
│
├── host/                          # Host/Manager panel (11 files)
│   ├── host_dashboard.php         # Dashboard with bookings
│   ├── manager_profile.php        # Profile management
│   ├── unit_management.php        # Property management
│   ├── amenities.php              # Amenity management
│   ├── reservations.php           # Reservation management
│   ├── booking_approvals.php      # Approval workflows
│   ├── payment_management.php     # Payment tracking
│   ├── notifications.php          # Notification center
│   ├── reviews.php                # Review management
│   ├── profile.php                # Alternative profile
│   └── get_unit_data.php          # AJAX data retrieval
│
├── includes/                      # PHP includes & functions (18 files)
│   ├── auth.php                   # Authentication functions
│   ├── session.php                # Session management
│   ├── public_session.php         # Public session handling
│   ├── functions.php              # Core business logic (1100+ lines)
│   ├── renter_functions.php       # Renter-specific functions
│   ├── email_integration.php      # Email functionality
│   ├── email_functions.php        # Alternative email helpers
│   ├── security.php               # Security functions
│   ├── sidebar.php                # Sidebar navigation
│   ├── DuplicateDetectionEngine.php    # Duplicate checking
│   ├── ImageFingerprinting.php        # Image tracking
│   ├── AddressVerification.php        # Address validation
│   ├── GeolocationValidation.php      # Geolocation checks
│   ├── HostContactVerification.php    # Contact verification
│   ├── HostIdentityVerification.php   # Identity verification
│   ├── api/                       # API endpoints
│   │   ├── get_user_details.php
│   │   ├── get_host_notifications.php
│   │   ├── payment/               # Payment APIs
│   │   │   ├── process_payment.php
│   │   │   ├── get_payment_details.php
│   │   │   └── export_payments.php
│   │   └── amenity/               # Amenity APIs
│   │       ├── add_amenity.php
│   │       └── amenity_modal.php
│   └── components/                # Reusable components
│       ├── validation.php
│       ├── form_errors.php
│       └── database.php
│
├── logs/                          # Application logs
│   └── php_errors.log             # PHP error log
│
├── migrations/                    # Database migrations
│   ├── add_payment_tables.php
│   ├── add_duplicate_detection_tables.php
│   └── add_address_fields_to_units.php
│
├── modules/                       # Feature modules (11 files)
│   ├── amenities.php              # Amenity module
│   ├── branches.php               # Branch module
│   ├── notifications.php          # Notification module
│   ├── payments.php               # Payment module
│   ├── payment_management.php     # Payment management
│   ├── reservations.php           # Reservation module
│   ├── manager_reservations.php   # Manager reservations
│   ├── reviews.php                # Review module
│   ├── units.php                  # Unit/property module
│   ├── users.php                  # User module
│   ├── view_unit.php              # Unit detail view
│   └── api/                       # Module APIs
│       ├── get_calendar_events.php
│       ├── approve_reservation.php
│       ├── reject_reservation.php
│       ├── get_notes.php
│       ├── save_note.php
│       └── update_status.php
│
├── public/                        # Public pages (18 files)
│   ├── index.php                  # Home page
│   ├── login.php                  # Login page
│   ├── register.php               # Renter registration
│   ├── host_register.php          # Host registration
│   ├── manager_register.php       # Manager registration
│   ├── reset_password.php         # Password reset
│   ├── browse_units.php           # Unit listing
│   ├── be_host.php                # Host signup page
│   ├── logout.php                 # Logout handler
│   ├── simple.php                 # Simple test page
│   └── test_*.php                 # Various test pages
│
├── renter/                        # Renter panel (9 files)
│   ├── profile.php                # Renter profile
│   ├── my_bookings.php            # Booking history
│   ├── reserve_unit.php           # Unit reservation
│   ├── booking_details.php        # Booking details view
│   ├── book_amenity.php           # Amenity booking
│   ├── checkout.php               # Checkout page
│   ├── payment.php                # Payment page
│   ├── payment_gateway.php        # PayMongo integration
│   └── payment_success.php        # Payment confirmation
│
├── uploads/                       # User uploaded files
│
├── .envexample.txt                # Environment variables template
├── .gitignore                     # Git ignore rules
├── .htaccess.simple               # Apache rewrite rules
├── .htaccess.txt                  # Alternative Apache config
│
├── index.php                      # Root index (redirects)
├── setup_database.php             # Database initialization
├── populate_test_data.php         # Test data generation
├── verify_data.php                # Data verification tool
├── verify_system.php              # System verification
│
├── AUDIT_EXECUTIVE_SUMMARY.txt    # Security audit
├── README.md                      # Project documentation
├── MAP_INTEGRATION_COMPLETION.md  # Map integration notes
├── FULL_CONTEXT.md                # This file
│
├── Various test files:
│   ├── test.php, test_connection.php, test_css.php
│   ├── test_login.php, test_error.php, test_fix.php
│   ├── test_db_connection.php, test_payment_query.php
│   ├── test_access_control.php, test_components.php
│   ├── simple_test.php, minimal_test.php
│   ├── system_test.php, verify_fix.php, verify_security.php
│   ├── debug_login.php, debug_includes.php
│   ├── quick_debug.php, quick_reset_password.php
│   ├── create_admin.php, setup_juan.php
│   ├── reset_manager_password.php, fix_duplicates.php
│   └── check_password.php, check_data.php
│
└── SQL & Database:
    └── condo_rental_reservation_db (3).sql  # Database backup
```

---

## Core Configuration Files

### 1. `config/constants.php`
Defines system-wide constants:
- `SITE_URL` - Base application URL
- `SITE_NAME` - Application name
- `SITE_DESCRIPTION` - Meta description
- Role constants (ADMIN, HOST, RENTER, MANAGER)
- File upload paths and limits

### 2. `config/db.php`
Database connection and helper functions:
- `$dbHost`, `$dbUser`, `$dbPassword`, `$dbName` - Connection credentials
- `$conn` - MySQLi connection object
- Helper functions:
  - `execute_query($query, $params)` - Execute with parameter binding
  - `get_single_result($query, $params)` - Fetch one row
  - `get_multiple_results($query, $params)` - Fetch all rows
  - `getLastInsertId()` - Get last insert ID
  - `escapeString($string)` - Escape unsafe strings

### 3. `config/paymongo.php`
PayMongo payment gateway configuration:
- `PAYMONGO_SECRET_KEY` - API secret key
- `PAYMONGO_PUBLIC_KEY` - API public key
- PayMongo API endpoints
- Test vs Live mode configuration

### 4. `config/email.php`
Email configuration:
- SMTP server settings
- Sender email and name
- Authentication credentials
- Email templates

### 5. `config/file_paths.php`
File path constants:
- Upload directories
- Log file locations
- Document storage paths

### 6. `config/OAuth.php`
OAuth configuration (if implemented):
- Google OAuth settings
- Facebook OAuth settings
- Other identity provider configs

---

## Application Architecture

### Request Flow
```
User Request
    ↓
Front Controller (index.php / public/[page].php)
    ↓
Session Check (includes/session.php)
    ↓
Role-Based Access Control (includes/auth.php)
    ↓
Business Logic (includes/functions.php, modules/)
    ↓
Database Query (config/db.php)
    ↓
Response (JSON/HTML)
```

### File Organization by Role

#### Admin Panel (`admin/`)
- Full system management
- User management
- Branch management
- Unit management
- Reports & analytics
- System settings

#### Host Panel (`host/`)
- Property management
- Reservation management
- Payment tracking
- Amenity management
- Review management
- Notification center

#### Renter Panel (`renter/`)
- Browse units
- Make reservations
- Book amenities
- Process payments
- View booking history
- Manage profile

#### Public Pages (`public/`)
- Authentication (login, register)
- Home page
- Unit browsing
- Password reset

---

## Database Schema

### 11 Core Tables

#### 1. `users`
```sql
id (PK)
email (UNIQUE)
password (hashed)
first_name
last_name
phone_number
role (admin, host, manager, renter)
profile_picture
address
city
state
zip_code
country
created_at
updated_at
is_active
last_login
```

#### 2. `branches`
```sql
id (PK)
name
address
city
state
zip_code
country
phone_number
email
manager_id (FK: users.id)
amenities (JSON)
created_at
updated_at
```

#### 3. `units`
```sql
id (PK)
branch_id (FK: branches.id)
host_id (FK: users.id)
unit_number
name
description
price_per_night
capacity
bedrooms
bathrooms
amenities (JSON)
images (JSON)
status (available, unavailable, maintenance)
created_at
updated_at
```

#### 4. `amenities`
```sql
id (PK)
branch_id (FK: branches.id)
name
description
type (pool, gym, parking, etc.)
available_from
available_to
max_capacity
status
created_at
updated_at
```

#### 5. `amenity_bookings`
```sql
id (PK)
amenity_id (FK: amenities.id)
user_id (FK: users.id)
booking_date
start_time
end_time
status (pending, confirmed, cancelled)
created_at
updated_at
```

#### 6. `reservations`
```sql
id (PK)
unit_id (FK: units.id)
renter_id (FK: users.id)
check_in_date
check_out_date
number_of_guests
special_requests
status (pending, confirmed, completed, cancelled)
total_price
created_at
updated_at
```

#### 7. `reservation_notes`
```sql
id (PK)
reservation_id (FK: reservations.id)
note_type (check-in, check-out, general)
note_text
created_by (FK: users.id)
created_at
```

#### 8. `payments`
```sql
id (PK)
reservation_id (FK: reservations.id)
user_id (FK: users.id)
amount
payment_method (paymongo, credit_card, cash)
status (pending, completed, failed, refunded)
transaction_id
created_at
updated_at
```

#### 9. `payment_sources`
```sql
id (PK)
user_id (FK: users.id)
paymongo_source_id
payment_type (card, gcash, grab_pay)
status (active, inactive)
created_at
updated_at
```

#### 10. `notifications`
```sql
id (PK)
user_id (FK: users.id)
title
message
type (booking, payment, alert, admin)
is_read
created_at
```

#### 11. `reviews`
```sql
id (PK)
unit_id (FK: units.id)
renter_id (FK: users.id)
host_id (FK: users.id)
rating (1-5)
comment
created_at
updated_at
```

---

## Key Features & Implementation

### 1. Multi-Branch Management
**Files:** `admin/manage_branch.php`, `modules/branches.php`, `includes/functions.php`

Features:
- Create, read, update, delete branches
- Assign managers to branches
- Track branch amenities
- Branch statistics and occupancy

### 2. Unit Reservations
**Files:** `renter/reserve_unit.php`, `modules/reservations.php`, `includes/functions.php`

Features:
- Browse available units
- Check-in/check-out date selection
- Guest count specification
- Special requests
- Status tracking (pending → confirmed → completed)
- Automatic email notifications

### 3. Amenity Booking with Double-Booking Prevention
**Files:** `renter/book_amenity.php`, `modules/amenities.php`, `includes/functions.php`

Features:
```php
checkAmenityAvailability($amenity_id, $booking_date, $start_time, $end_time)
// Returns: true (available) or false (occupied)
```
- Prevents double-booking of amenities
- Time slot validation
- Capacity limits
- Status tracking

### 4. PayMongo Payment Integration
**Files:** `config/paymongo.php`, `renter/payment_gateway.php`, `includes/api/payment/process_payment.php`

Features:
- GCash payments
- Card payments (Visa/Mastercard)
- Grab Pay
- Secure checkout flow
- Payment confirmation
- Automatic email receipts
- Transaction logging

Payment Flow:
```
1. User initiates payment
2. System creates PayMongo source
3. User redirected to PayMongo checkout
4. User enters payment details (PayMongo handles security)
5. Payment processed and confirmed
6. Reservation auto-confirmed
7. Email confirmations sent
8. Admin notified
```

### 5. Email Integration
**Files:** `includes/email_integration.php`, `config/email.php`

Features:
- SMTP/Gmail integration
- Reservation confirmations
- Payment receipts
- Cancellation notifications
- Admin alerts
- System notifications

### 6. Admin Dashboard & Analytics
**Files:** `admin/admin_dashboard.php`, `admin/reports.php`

Features:
- User statistics
- Revenue tracking
- Occupancy rates
- Booking metrics
- Payment analysis
- System health monitoring

### 7. Role-Based Access Control (RBAC)
**Files:** `includes/auth.php`, `includes/session.php`

Roles:
- **Admin**: Full system access
- **Host**: Property management
- **Manager**: Branch operations
- **Renter**: Booking and payments

### 8. Duplicate Detection Engine
**Files:** `includes/DuplicateDetectionEngine.php`

Features:
- Detects duplicate user registrations
- Prevents multi-account abuse
- Email and phone validation

### 9. Image Fingerprinting
**Files:** `includes/ImageFingerprinting.php`

Features:
- Tracks user profile images
- Prevents image spoofing
- Digital fingerprints

### 10. Address & Geolocation Validation
**Files:** `includes/AddressVerification.php`, `includes/GeolocationValidation.php`

Features:
- Address verification
- Geolocation validation
- Location-based restrictions

### 11. Identity Verification
**Files:** `includes/HostContactVerification.php`, `includes/HostIdentityVerification.php`

Features:
- Host contact verification
- Identity validation
- Document verification

---

## Security Implementation

### Password Security
- Bcrypt hashing via `password_hash()`
- Secure password comparison with `password_verify()`
- Password strength validation in registration

### SQL Injection Prevention
- Prepared statements in `config/db.php`
- Parameter type detection (string, int, float, null)
- Input escaping via `mysqli_real_escape_string()`

### CSRF Protection
- Token generation and validation
- Session-based token storage
- Token verification on form submission

### Session Security
- Secure session initialization
- Role-based access control
- Session timeout handling
- Secure session data storage

### Data Validation
- Input validation via `includes/components/validation.php`
- Email validation
- Phone number validation
- Date/time validation
- File upload validation

### Data Privacy
- User data encryption where needed
- Secure file uploads
- Access logging via `audit_access_control.php`
- Data deletion on account termination

---

## File-by-File Documentation

### Configuration Files

#### `config/constants.php`
```php
define('SITE_URL', 'http://localhost/BookIT/');
define('SITE_NAME', 'BookIT');
define('SITE_DESCRIPTION', 'Condo Rental Reservation System');
define('ADMIN_ROLE', 'admin');
define('HOST_ROLE', 'host');
define('RENTER_ROLE', 'renter');
define('MANAGER_ROLE', 'manager');
// ... more constants
```

#### `config/db.php`
Provides:
- Database connection
- Query execution with type-safe parameter binding
- Result retrieval functions
- Connection error handling

#### `config/paymongo.php`
```php
define('PAYMONGO_SECRET_KEY', 'sk_test_YOUR_KEY');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_YOUR_KEY');
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1/');
```

### Include Files

#### `includes/functions.php` (1100+ lines)
Core business logic functions:
- User management: `createUser()`, `updateUser()`, `getUserById()`
- Reservation management: `createReservation()`, `getReservations()`, `cancelReservation()`
- Amenity management: `createAmenity()`, `checkAmenityAvailability()`
- Payment processing: `processPayment()`, `recordPayment()`
- Email notifications: `sendReservationConfirmation()`, `sendPaymentReceipt()`
- Branch management: `createBranch()`, `updateBranch()`, `getBranches()`
- Unit management: `createUnit()`, `updateUnit()`, `getUnits()`, `getUnitById()`
- Analytics: `getDashboardStats()`, `getRevenueReport()`

#### `includes/session.php`
Session management:
- `startSession()` - Initialize secure session
- `checkLogin()` - Verify user is logged in
- `getUserRole()` - Get current user's role
- `getCurrentUserId()` - Get logged-in user ID
- `logout()` - Destroy session

#### `includes/auth.php`
Authentication:
- `authenticateUser($email, $password)` - Login handler
- `registerUser($data)` - Registration handler
- `hashPassword($password)` - Bcrypt hashing
- `verifyPassword($password, $hash)` - Password verification
- `requireLogin()` - Redirect if not logged in
- `requireRole($role)` - Restrict by role

#### `includes/email_integration.php`
Email functions:
- `sendReservationConfirmationEmail($email, $reservation)` - Confirmation
- `sendPaymentReceiptEmail($email, $payment)` - Receipt
- `sendCancellationEmail($email, $reservation)` - Cancellation
- `sendAdminAlert($message)` - Admin notification
- `sendWelcomeEmail($email, $name)` - Welcome message

### Admin Pages

#### `admin/admin_dashboard.php`
Displays:
- Total users count
- Active reservations
- Revenue summary
- Occupancy rates
- Recent bookings
- Payment status
- System health

#### `admin/user_management.php`
Features:
- List all users
- Create new user
- Edit user details
- Delete user
- Change user role
- View user history

#### `admin/manage_branch.php`
Features:
- List branches
- Create branch
- Edit branch details
- Assign manager
- View amenities
- Branch statistics

#### `admin/unit_management.php`
Features:
- List units
- Create unit
- Edit unit details
- Update pricing
- Manage availability
- View bookings

#### `admin/reports.php`
Reports:
- Revenue by period
- Occupancy analysis
- Guest statistics
- Payment methods distribution
- Top units
- Cancellation rates

### Host Panel Pages

#### `host/host_dashboard.php`
Features:
- Property overview
- Upcoming check-ins
- Recent reservations
- Payment summary
- Guest reviews
- Notifications

#### `host/unit_management.php`
Features:
- List host's units
- Add new unit
- Edit unit details
- Upload images
- Manage amenities
- Set pricing

#### `host/reservations.php`
Features:
- View all reservations
- Approve/reject bookings
- Add check-in/check-out notes
- View guest details
- Communication tools
- Booking timeline

#### `host/payment_management.php`
Features:
- Payment history
- Payout tracking
- Commission calculation
- Payment details
- Export reports

#### `host/amenities.php`
Features:
- Manage amenities
- Set availability
- Track bookings
- Update pricing
- Capacity management

### Renter Pages

#### `renter/profile.php`
Features:
- View profile
- Edit details
- Change password
- Manage preferences
- View payment history
- Communication settings

#### `renter/reserve_unit.php`
Features:
- Search units
- View details
- Check availability
- Select dates
- Specify guests
- Add special requests
- Confirm reservation

#### `renter/book_amenity.php`
Features:
- List amenities
- Check availability
- Select time slot
- Confirm booking
- Payment options
- Confirmation page

#### `renter/payment_gateway.php`
Features:
- Payment method selection
- PayMongo integration
- Secure checkout
- Payment confirmation
- Receipt generation

#### `renter/my_bookings.php`
Features:
- Booking history
- Upcoming bookings
- Past bookings
- Booking details
- Review option
- Cancellation option

### AJAX Endpoints

#### `ajax/check_duplicate.php`
Checks for:
- Duplicate emails
- Duplicate phone numbers
- Prevents multi-account abuse

#### `ajax/get_unit.php`
Returns:
- Unit details (JSON)
- Pricing information
- Availability status
- Images

#### `ajax/get_branch_stats.php`
Returns:
- Branch statistics
- Occupancy data
- Revenue metrics

### Modules

#### `modules/amenities.php`
Functions:
- `getAmenities($branch_id)`
- `getAmenityById($amenity_id)`
- `createAmenity($data)`
- `updateAmenity($amenity_id, $data)`
- `deleteAmenity($amenity_id)`
- `checkAmenityAvailability($amenity_id, $date, $start, $end)`

#### `modules/reservations.php`
Functions:
- `getReservations($filters)`
- `getReservationById($reservation_id)`
- `createReservation($data)`
- `updateReservationStatus($reservation_id, $status)`
- `cancelReservation($reservation_id)`
- `getUpcomingCheckIns()`
- `getReservationTimeline($reservation_id)`

#### `modules/payments.php`
Functions:
- `getPayments($filters)`
- `getPaymentById($payment_id)`
- `recordPayment($data)`
- `getPaymentStats()`
- `exportPaymentReport($params)`

#### `modules/users.php`
Functions:
- `getUsers($filters)`
- `getUserById($user_id)`
- `getUserByEmail($email)`
- `createUser($data)`
- `updateUser($user_id, $data)`
- `deleteUser($user_id)`
- `getUserRole($user_id)`

#### `modules/units.php`
Functions:
- `getUnits($filters)`
- `getUnitById($unit_id)`
- `createUnit($data)`
- `updateUnit($unit_id, $data)`
- `deleteUnit($unit_id)`
- `getUnitsByBranch($branch_id)`
- `getAvailableUnits($check_in, $check_out)`

---

## Development & Testing

### Test Files
Located in root directory:

- `test_connection.php` - Database connection test
- `test_login.php` - Authentication test
- `test_payment_query.php` - Payment system test
- `test_access_control.php` - RBAC test
- `simple_test.php` - Basic functionality test
- `system_test.php` - Full system test
- `verify_data.php` - Data verification
- `verify_system.php` - System health check

### Database Setup & Population

#### `setup_database.php`
- Creates all 11 tables
- Defines relationships
- Sets up indexes
- Initializes default data

#### `populate_test_data.php`
Creates:
- Test users (admin, hosts, renters)
- Test branches
- Test units
- Sample reservations
- Sample amenities
- Sample reviews
- Test data for payment flows

Usage:
```
http://localhost/BookIT/populate_test_data.php
```

### Test Credentials
```
Admin: admin@bookit.com / Password123!
Host: host1@bookit.com / Password123!
Renter: renter1@bookit.com / Password123!
```

---

## Troubleshooting & Logs

### Log File Location
```
C:\wamp64\logs\php_error.log
C:\wamp64\logs\apache_error.log
C:\wamp64\www\BookIT\logs\php_errors.log (application log)
```

### Common Issues

#### HTTP 500 Error
1. Check PHP error log
2. Verify database connection
3. Check file permissions
4. Review recent code changes

#### Payment Not Processing
1. Verify PayMongo API keys
2. Check payment_sources table
3. Review PayMongo response
4. Check error logs

#### Email Not Sending
1. Verify SMTP configuration
2. Check firewall/port 587
3. Verify Gmail 2FA setup
4. Test with `test_email.php`

#### Database Connection Failed
1. Check MySQL server status
2. Verify credentials in `config/db.php`
3. Ensure database exists
4. Check user permissions

---

## Recent Bug Fixes (November 15, 2025)

### Issue 1: Database Parameter Binding
**Status:** ✅ FIXED
- **Problem:** NULL values in database queries failed
- **Root Cause:** All parameters treated as strings
- **Solution:** Implemented type detection system
- **Files:** `config/db.php`, `includes/functions.php`

### Issue 2: Branch Management
**Status:** ✅ FIXED
- **Problem:** Adding branches showed success but no data appeared
- **Solution:** Implemented session-based message handling
- **Files:** `admin/manage_branch.php`

### Issue 3: Admin Dashboard 500 Error
**Status:** ✅ FIXED
- **Problem:** Pages returned HTTP 500
- **Solution:** Fixed database helper functions
- **Files:** All admin and database files

---

## Deployment Checklist

### Pre-Launch
- [x] Database created and populated
- [x] PayMongo API keys configured
- [x] Email/SMTP setup completed
- [x] Test payment flow works
- [x] Admin dashboard loads
- [x] User management works
- [x] Amenity booking prevents double-booking
- [x] Error logs reviewed

### Production Deployment
- [ ] Switch PayMongo to live mode
- [ ] Update live API keys
- [ ] Enable HTTPS
- [ ] Setup SendGrid/Mailgun
- [ ] Enable strong admin passwords
- [ ] Two-factor authentication
- [ ] Database automated backups
- [ ] Error monitoring setup
- [ ] Security audit complete

### Post-Launch
- [ ] Monitor error logs daily
- [ ] Track payment success rates
- [ ] Verify email delivery
- [ ] Monitor server performance
- [ ] Regular security scans

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 2.1 | Nov 15, 2025 | Database binding fixes, improved error handling |
| 2.0 | Nov 14, 2025 | PayMongo integration, email automation |
| 1.0 | Nov 1, 2025 | Initial release |

---

**Last Updated:** November 18, 2025  
**Status:** ✅ Production Ready  
**Maintained By:** Development Team
