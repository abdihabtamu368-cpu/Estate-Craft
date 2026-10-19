-- =======================================================
-- REAL ESTATE PORTAL (ETHIOPIA) DATABASE SCHEMA
-- Compatible with MySQL 5.7+ & MariaDB 10.3+ (WampServer)
-- =======================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `user_feedback`;
DROP TABLE IF EXISTS `listing_reports`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `property_images`;
DROP TABLE IF EXISTS `properties`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------
-- Table: users
-- Stores both regular buyers/renters/sellers and brokers
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NULL COMMENT 'Null if registered via Google OAuth before setting password',
  `phone` VARCHAR(30) NULL,
  `phone_verified` TINYINT(1) DEFAULT 0,
  `otp_code` VARCHAR(10) NULL,
  `otp_expires_at` DATETIME NULL,
  `role` ENUM('user', 'broker', 'admin') NOT NULL DEFAULT 'user',
  `google_id` VARCHAR(100) NULL UNIQUE,
  `avatar` VARCHAR(255) NULL,
  `bio` TEXT NULL,
  -- Broker specific fields
  `broker_agency` VARCHAR(150) NULL,
  `broker_license_no` VARCHAR(100) NULL,
  `broker_fee_percentage` DECIMAL(5,2) NULL DEFAULT 2.00 COMMENT 'Percentage commission for sales',
  `broker_fixed_fee` DECIMAL(10,2) NULL DEFAULT 0.00 COMMENT 'Fixed fee or rental placement fee',
  `broker_fee_notes` VARCHAR(255) NULL DEFAULT 'Standard 2% commission upon closing. No hidden upfront fees.',
  `preferred_language` VARCHAR(10) DEFAULT 'en',
  `theme_mode` VARCHAR(30) DEFAULT 'default' COMMENT 'light, dark, or default (system)',
  `ai_smart_alerts` TINYINT(1) DEFAULT 1,
  `ai_search_query` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: properties
-- Main listings table for Buy, Rent, Sell (Ethiopia)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `properties` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NULL,
  `description` TEXT NOT NULL,
  `purpose` ENUM('sale', 'rent') NOT NULL DEFAULT 'sale',
  `property_type` ENUM('house', 'apartment', 'villa', 'condo', 'townhouse', 'commercial', 'land') NOT NULL DEFAULT 'house',
  `price` DECIMAL(14, 2) NOT NULL,
  `price_frequency` ENUM('total', 'monthly', 'yearly') NOT NULL DEFAULT 'total',
  `bedrooms` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `bathrooms` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `area_sqft` INT UNSIGNED NOT NULL DEFAULT 0,
  `address` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL DEFAULT 'Addis Ababa',
  `state` VARCHAR(100) NULL DEFAULT 'Addis Ababa',
  `postal_code` VARCHAR(20) NULL,
  `country` VARCHAR(100) DEFAULT 'Ethiopia',
  `latitude` DECIMAL(10, 8) NOT NULL DEFAULT 9.01079300,
  `longitude` DECIMAL(11, 8) NOT NULL DEFAULT 38.76125200,
  `status` ENUM('active', 'pending_review', 'sold', 'rented', 'flagged') NOT NULL DEFAULT 'active',
  `featured` TINYINT(1) DEFAULT 0,
  `contact_name` VARCHAR(100) NOT NULL,
  `contact_phone` VARCHAR(30) NOT NULL,
  `contact_email` VARCHAR(150) NOT NULL,
  `has_broker` TINYINT(1) DEFAULT 0,
  `broker_fee_display` VARCHAR(200) NULL,
  `views_count` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_properties_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: property_images
-- Multiple photo gallery for each listing
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `property_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `property_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `caption` VARCHAR(150) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_images_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: password_resets
-- Secure password recovery tokens and OTP
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL,
  `token` VARCHAR(100) NOT NULL,
  `otp_code` VARCHAR(10) NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_token` (`token`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: listing_reports
-- Safety & Moderation reporting system
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `listing_reports` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `property_id` INT UNSIGNED NOT NULL,
  `reporter_user_id` INT UNSIGNED NULL COMMENT 'Null if anonymous reporter',
  `reporter_email` VARCHAR(150) NULL,
  `reason` ENUM('fraud_scam', 'incorrect_price', 'wrong_location', 'duplicate', 'offensive_content', 'other') NOT NULL,
  `details` TEXT NULL,
  `status` ENUM('pending', 'reviewed', 'dismissed', 'action_taken') DEFAULT 'pending',
  `moderator_notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_reports_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reports_user` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: user_feedback
-- Customer feedback, ratings, and suggestions
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_feedback` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `category` ENUM('general', 'suggestion', 'bug_report', 'broker_experience', 'feature_request') NOT NULL DEFAULT 'general',
  `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `message` TEXT NOT NULL,
  `status` ENUM('new', 'reviewed', 'resolved') DEFAULT 'new',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- SEED SAMPLE ETHIOPIAN REAL ESTATE DATA
-- Default passwords for sample accounts are: Password123!
-- =======================================================

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `phone`, `phone_verified`, `role`, `broker_agency`, `broker_license_no`, `broker_fee_percentage`, `broker_fixed_fee`, `broker_fee_notes`) VALUES
(1, 'Admin Moderator', 'admin@realestate.local', '$2y$10$VH4peRbN3Y3MmVwZSdj0aeJVzFPVh4mDnfqVC14kRTeSFSSLTruQe', '+251 91 100 0001', 1, 'admin', NULL, NULL, NULL, NULL, NULL),
(2, 'Sara Tefera (Verified Broker)', 'sara.broker@realestate.local', '$2y$10$VH4peRbN3Y3MmVwZSdj0aeJVzFPVh4mDnfqVC14kRTeSFSSLTruQe', '+251 91 123 4567', 1, 'broker', 'Addis Premier Realty', 'BRE# ET-019283', 2.00, 5000.00, '2% sales commission upon contract closing; 1-month rental tenant placement fee.'),
(3, 'Dawit Bekele (Direct Owner)', 'dawit.owner@example.com', '$2y$10$VH4peRbN3Y3MmVwZSdj0aeJVzFPVh4mDnfqVC14kRTeSFSSLTruQe', '+251 92 234 5678', 1, 'user', NULL, NULL, NULL, NULL, NULL),
(4, 'Bethlehem Tadesse (Commercial Broker)', 'bethlehem.broker@realestate.local', '$2y$10$VH4peRbN3Y3MmVwZSdj0aeJVzFPVh4mDnfqVC14kRTeSFSSLTruQe', '+251 93 345 6789', 1, 'broker', 'Skyline Addis Commercial Properties', 'BRE# ET-084920', 1.80, 0.00, '1.8% negotiable commercial broker commission payable upon lease agreement.'),
(5, 'Abdi Habtamu', 'abdihabtamu368@gmail.com', '$2y$10$VH4peRbN3Y3MmVwZSdj0aeJVzFPVh4mDnfqVC14kRTeSFSSLTruQe', '+251 90 909 0909', 1, 'user', NULL, NULL, NULL, NULL, NULL);

-- Sample Ethiopian Properties (Centered in Addis Ababa, Bishoftu, Hawassa, Bahir Dar)
INSERT INTO `properties` (`id`, `user_id`, `title`, `slug`, `description`, `purpose`, `property_type`, `price`, `price_frequency`, `bedrooms`, `bathrooms`, `area_sqft`, `address`, `city`, `state`, `postal_code`, `country`, `latitude`, `longitude`, `status`, `featured`, `contact_name`, `contact_phone`, `contact_email`, `has_broker`, `broker_fee_display`, `views_count`) VALUES
(1, 2, 'Luxury Architectural Villa with Landscaped Garden', 'luxury-architectural-villa-with-landscaped-garden', 'Stunning contemporary residence situated in the prime diplomatic neighborhood of Bole. Features modern Italian kitchen, master suite with jacuzzi, backup generator, 5000L water reserve tanks, high security compound with guardhouse, and parking for 4 vehicles.', 'sale', 'villa', 48500000.00, 'total', 5, 5, 4500, 'Bole Atlas, near Edna Mall', 'Addis Ababa', 'Bole Subcity', '1000', 'Ethiopia', 8.995000, 38.785000, 'active', 1, 'Sara Tefera', '+251 91 123 4567', 'sara.broker@realestate.local', 1, 'Broker Fee: 2% of final sale price', 480),

(2, 3, 'Modern Executive High-Rise Apartment in Kazanchis', 'modern-executive-high-rise-apartment-in-kazanchis', 'Fully furnished luxury apartment located 5 minutes from UNECA and Intercontinental Hotel. Panoramic city views, elevators with backup power generator, round-the-clock security, underground designated parking, and ultra-high-speed fiber internet.', 'rent', 'apartment', 85000.00, 'monthly', 3, 2, 1850, 'Kazanchis, Kirkos Subcity', 'Addis Ababa', 'Kirkos Subcity', '1000', 'Ethiopia', 9.018000, 38.769000, 'active', 1, 'Dawit Bekele', '+251 92 234 5678', 'dawit.owner@example.com', 0, 'Direct from Owner &mdash; Zero Broker Fee', 312),

(3, 2, 'Charming Compound Family Residence in Old Airport', 'charming-compound-family-residence-in-old-airport', 'Spacious green compound located in quiet, leafy Sarbet / Old Airport expat enclave. Large manicured lawn, traditional coffee ceremony corner, separate service quarters with kitchenette, and proximity to International Community School (ICS).', 'sale', 'house', 36000000.00, 'total', 4, 3, 3200, 'Sarbet, near ICS School, Old Airport', 'Addis Ababa', 'Nifas Silk Subcity', '1000', 'Ethiopia', 8.988000, 38.742000, 'active', 1, 'Sara Tefera', '+251 91 123 4567', 'sara.broker@realestate.local', 1, 'Broker Fee: 2% commission upon closing', 290),

(4, 3, 'Spacious Modern 3-Bedroom Condo in CMC', 'spacious-modern-3-bedroom-condo-in-cmc', 'Sunlit top-floor condo in prime CMC gated community with 24/7 security gate. Close to light rail transit, shopping centers, and top private schools. Features ceramic tiling, modern fitted wardrobes, and private balcony.', 'rent', 'condo', 45000.00, 'monthly', 3, 2, 1400, 'CMC Michael, Yeka Subcity', 'Addis Ababa', 'Yeka Subcity', '1000', 'Ethiopia', 9.025000, 38.835000, 'active', 1, 'Dawit Bekele', '+251 92 234 5678', 'dawit.owner@example.com', 0, 'Direct Owner Rental &mdash; No Broker Commission', 195),

(5, 2, 'Lakeside Vacation Resort Residence in Bishoftu', 'lakeside-vacation-resort-residence-in-bishoftu', 'Serene weekend getaway overlooking Lake Babogaya in Bishoftu. Just 45 minutes drive from Addis Ababa via expressway. Custom timber verandas, private fruit orchard, and calm crater lake panoramas.', 'sale', 'villa', 22500000.00, 'total', 3, 3, 2600, 'Lake Babogaya Viewpoint', 'Bishoftu', 'Oromia Region', '1100', 'Ethiopia', 8.752300, 38.978500, 'active', 1, 'Sara Tefera', '+251 91 123 4567', 'sara.broker@realestate.local', 1, 'Broker Fee: 2% upon final sales deed', 140),

(6, 4, 'Prime Commercial Office Floor in Meskel Square', 'prime-commercial-office-floor-in-meskel-square', 'A-Class corporate office floor suitable for multinational firms, fintech agencies, or embassy missions. Includes private elevator reception, partitioned executive offices, dedicated transformer, and underground tenant parking.', 'rent', 'commercial', 210000.00, 'monthly', 0, 4, 3800, 'Meskel Square Commercial Corridor', 'Addis Ababa', 'Kirkos Subcity', '1000', 'Ethiopia', 9.010800, 38.763600, 'active', 0, 'Bethlehem Tadesse', '+251 93 345 6789', 'bethlehem.broker@realestate.local', 1, 'Commercial Broker Fee: 1.8% of Lease Term', 115),

(7, 3, 'Lakefront Residential Villa in Hawassa', 'lakefront-residential-villa-in-hawassa', 'Beautiful modern lakeside villa in the vibrant city of Hawassa. Walking distance from Hawassa Lake promenade, featuring spacious living hall, lush outdoor lawn, and secure gated compound.', 'sale', 'villa', 19000000.00, 'total', 4, 3, 2800, 'Lake Shore Drive, Hawassa', 'Hawassa', 'Sidama Region', '1200', 'Ethiopia', 7.050400, 38.476300, 'active', 0, 'Dawit Bekele', '+251 92 234 5678', 'dawit.owner@example.com', 0, 'Direct Sale by Owner &mdash; Zero Broker Fees', 85);

-- Sample Property Images (High quality architectural real estate photography)
INSERT INTO `property_images` (`property_id`, `image_path`, `is_primary`, `caption`) VALUES
(1, 'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=1200&q=80', 1, 'Bole Luxury Villa Exterior'),
(1, 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80', 0, 'Grand Living Room'),
(1, 'https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1200&q=80', 0, 'Modern Kitchen'),

(2, 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80', 1, 'Kazanchis Executive Apartment'),
(2, 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1200&q=80', 0, 'Dining Area'),

(3, 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?auto=format&fit=crop&w=1200&q=80', 1, 'Old Airport Green Compound'),
(3, 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1200&q=80', 0, 'Kitchen and Garden View'),

(4, 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80', 1, 'CMC Condo Exterior'),

(5, 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80', 1, 'Bishoftu Lake Villa'),

(6, 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80', 1, 'Meskel Square Commercial Floor'),

(7, 'https://images.unsplash.com/photo-1570129477492-45c003edd2be?auto=format&fit=crop&w=1200&q=80', 1, 'Hawassa Villa Front');

-- Sample Report for testing moderation
INSERT INTO `listing_reports` (`property_id`, `reporter_user_id`, `reporter_email`, `reason`, `details`, `status`) VALUES
(4, 1, 'admin@realestate.local', 'incorrect_price', 'Monthly rental rate seems inconsistent with recent CMC listings. Verified with owner.', 'pending');
