# EstateCraft &mdash; Premium Real Estate Portal (Buy, Sell & Rent)

A modern, responsive real estate web application built with **PHP 8.x** and **MySQL**, designed to run locally with **WampServer**. It enables users to browse, search, and filter residential and commercial properties, post listings with photo galleries, view property locations on an interactive Leaflet map, hire verified brokers with transparent fee disclosures, and report suspicious listings.

---

## 🌟 Key Features

1. **Homepage & Smart Search**:
   - Hero search bar with segmented control tabs (**All Properties**, **Buy a Home**, **Rent a House**).
   - Multi-criteria filtering by Location / City, Property Type, and Price Range.
   - Quick category shortcuts for Buy, Rent, and Sell.
   - Featured properties showcase with price tags and amenities.
   - Live Leaflet / OpenStreetMap interactive map explorer.

2. **Authentication & User Profiles**:
   - Registration and Login with secure `password_hash()` and `password_verify()`.
   - **Continue with Google** (OAuth 2.0) with configurable credentials and informative fallback guides.
   - Multi-role support: **Regular Buyers/Sellers/Renters**, **Licensed Real Estate Brokers**, and **Administrators / Moderators**.
   - Verified Mobile Number workflow with **6-digit SMS OTP verification**.
   - Transparent Dev/Demo mode: If SMS gateway credentials are not yet configured, the system transparently generates and displays the OTP code on-screen for frictionless local testing.

3. **Broker Representation & Fee Disclosure (Prompt Highlight)**:
   - Brokers must provide their License Number and agency.
   - **Upfront Broker Fee Disclosure**: Every broker-represented listing explicitly calculates and displays the broker commission (e.g. `2.5% upon closing` or `$500 screening fee`) **before** a user contacts or hires them.
   - Direct owner listings are clearly highlighted with a green badge: **"Direct Owner Listing &mdash; Zero Broker Fees"**.

4. **Property Listings & Multi-Image Uploads**:
   - Support for houses, apartments, luxury villas, condominiums, townhouses, commercial offices, and land.
   - Multiple image uploads with MIME type validation (`image/jpeg`, `image/png`, `image/webp`), size enforcement (max 6MB each), and secure randomized filename generation.
   - Interactive OpenStreetMap location picker: click or drag the pin to pinpoint exact GPS coordinates.
   - Comprehensive details: bedrooms, bathrooms, square footage, address, pricing frequency (total, monthly, yearly), and public contact info.

5. **User Dashboard & Listing Management**:
   - View, edit, and delete user's own listings.
   - Listing status indicators: `Active`, `Pending Review`, `Sold`, `Rented`, `Flagged`.
   - Total views counter and verification status.

6. **Safety & Moderation Features**:
   - "Report Listing" modal on every listing page (Fraud/Scam, Incorrect Price, Fake Address, Offensive Content, etc.).
   - Automatic protection: Listings receiving 3 or more reports are automatically flagged and quarantined for review.
   - Admin / Moderator Safety Center (`user/reports.php`) to inspect flagged listings, dismiss reports, reinstate listings, or permanently delete fraudulent posts.

---

## 🗄️ Database Architecture

The application uses the `realestate_db` MySQL database containing 4 normalized tables:

### 1. `users`
- `id` (INT PK Auto Increment)
- `full_name` (VARCHAR 100)
- `email` (VARCHAR 150 UNIQUE)
- `password_hash` (VARCHAR 255)
- `phone` (VARCHAR 30)
- `phone_verified` (TINYINT 1)
- `otp_code` (VARCHAR 10)
- `otp_expires_at` (DATETIME)
- `role` (`user`, `broker`, `admin`)
- `google_id` (VARCHAR 100 UNIQUE)
- `avatar`, `bio`
- `broker_agency`, `broker_license_no`, `broker_fee_percentage`, `broker_fixed_fee`, `broker_fee_notes`

### 2. `properties`
- `id` (INT PK Auto Increment)
- `user_id` (FK to `users.id`)
- `title`, `slug`, `description`
- `purpose` (`sale`, `rent`)
- `property_type` (`house`, `apartment`, `villa`, `condo`, `townhouse`, `commercial`, `land`)
- `price` (DECIMAL 12,2), `price_frequency` (`total`, `monthly`, `yearly`)
- `bedrooms`, `bathrooms`, `area_sqft`
- `address`, `city`, `state`, `postal_code`, `country`
- `latitude`, `longitude` (DECIMAL for Leaflet GPS coordinates)
- `status` (`active`, `pending_review`, `sold`, `rented`, `flagged`)
- `featured` (TINYINT 1)
- `contact_name`, `contact_phone`, `contact_email`
- `has_broker` (TINYINT 1), `broker_fee_display` (VARCHAR 200)
- `views_count` (INT)

### 3. `property_images`
- `id` (INT PK Auto Increment)
- `property_id` (FK to `properties.id` ON DELETE CASCADE)
- `image_path` (VARCHAR 255)
- `is_primary` (TINYINT 1)
- `caption` (VARCHAR 150)

### 4. `listing_reports`
- `id` (INT PK Auto Increment)
- `property_id` (FK to `properties.id` ON DELETE CASCADE)
- `reporter_user_id` (FK to `users.id` NULLABLE)
- `reporter_email` (VARCHAR 150)
- `reason` (`fraud_scam`, `incorrect_price`, `wrong_location`, `duplicate`, `offensive_content`, `other`)
- `details` (TEXT)
- `status` (`pending`, `reviewed`, `dismissed`, `action_taken`)
- `moderator_notes` (TEXT)

---

## 🚀 Setup Instructions for WampServer

### Step 1: Start WampServer
1. Launch **WampServer** from your Start Menu or Desktop shortcut.
2. Wait until the WampServer tray icon turns **Green** (indicating Apache and MySQL are running).

### Step 2: Project Location in WampServer
A directory junction has already been pre-configured linking your desktop folder directly to your WampServer `www` directory:
- **WampServer Path**: `C:\wamp64\www\buy-and-sell`
- **Browser URL**: `http://localhost/buy-and-sell/`

*(Alternatively, if running on another computer, simply copy this entire project folder into your `c:\wamp64\www\` directory).*

### Step 3: Initialize the Database

You can initialize the database using either of the following two methods:

#### Method A: One-Click Web Installer (Recommended)
1. Open your web browser and navigate to:
   ```
   http://localhost/buy-and-sell/setup.php
   ```
2. Click **"Install Database & Seed Data Now"**.
3. The wizard will automatically create the `realestate_db` database, execute `database/schema.sql`, and populate sample properties, verified brokers, direct owners, and safety reports.

#### Method B: phpMyAdmin Import
1. Open `http://localhost/phpmyadmin` in your browser.
2. Log in with username `root` (leave password blank by default in WampServer).
3. Click the **Import** tab at the top.
4. Click **Choose File** and select `database/schema.sql`.
5. Click **Import** at the bottom.

---

## 🔑 Pre-Seeded Sample Accounts for Testing

All sample accounts use the password: `Password123!`

| Role | Name | Email | Features |
| :--- | :--- | :--- | :--- |
| **Verified Broker** | Sarah Jenkins | `sarah.broker@realestate.local` | Apex Premier Realty, BRE# 01928374, 2.5% fee policy, verified mobile |
| **Direct Owner** | Michael Chang | `michael.chang@example.com` | Zero broker fee seller, verified mobile |
| **Commercial Broker**| Elena Vance | `elena.broker@realestate.local` | Skyline Metro Properties, BRE# 08492011, commercial lease agent |
| **Safety Admin** | Admin Moderator | `admin@realestate.local` | Can access Safety Reports queue (`user/reports.php`) and moderate listings |

*(Tip: On the `auth/login.php` page, there are 1-click **Quick Fill** buttons to automatically fill credentials for testing).*

---

## ⚙️ Configuration & Third-Party APIs

All configuration constants are located in:
```
config/config.php
```

### 1. Database Connection
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306'); // If your WampServer uses port 3308 for MySQL, change this here
define('DB_NAME', 'realestate_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 2. Phone SMS OTP Verification
- **Development / Demo Mode (Default)**:
  `define('SMS_MODE', 'dev');`
  In this mode, no paid SMS gateway is needed. The 6-digit OTP code is generated, stored, and displayed in a developer banner right on `auth/verify_phone.php` so you can test phone verification instantly!
- **Live Twilio SMS**:
  To send real SMS messages to real phones:
  ```php
  define('SMS_MODE', 'twilio');
  define('TWILIO_SID', 'your_account_sid');
  define('TWILIO_TOKEN', 'your_auth_token');
  define('TWILIO_FROM', '+1234567890');
  ```

### 3. Google Sign-In (OAuth 2.0)
The Google Sign-in flow is built using OAuth 2.0:
1. Go to [Google Cloud Console Credentials](https://console.cloud.google.com/apis/credentials).
2. Create an **OAuth 2.0 Client ID** (Web application).
3. Set Authorized Redirect URI to:
   ```
   http://localhost/buy-and-sell/auth/google_callback.php
   ```
4. Paste the credentials into `config/config.php`:
   ```php
   define('GOOGLE_CLIENT_ID', 'your_client_id.apps.googleusercontent.com');
   define('GOOGLE_CLIENT_SECRET', 'your_client_secret');
   ```
*(If clicked before adding credentials, the app gracefully presents clear instructions instead of breaking).*

---

## 🔒 Security Best Practices Implemented

- **Prepared SQL Statements**: 100% of database queries use PDO prepared statements with named parameters to prevent SQL injection.
- **Password Hashing**: Passwords stored using PHP `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt).
- **CSRF Tokens**: Every state-changing form (login, register, post listing, edit, delete, report) requires and verifies a cryptographically secure CSRF token.
- **Secure File Uploads**: Image uploads are verified using MIME inspection (`finfo_file` / `mime_content_type`), extension whitelisting (`jpg`, `jpeg`, `png`, `webp`), size limits (6MB max), and randomized unique filenames to prevent path traversal or script execution.
- **XSS Sanitization**: User-generated content is sanitized using `htmlspecialchars()` before outputting into HTML templates.
- **Ownership Verification**: Editing and deleting listings validates that the session user is either the original author or an administrator.
