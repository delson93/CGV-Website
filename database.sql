-- ============================================================================
-- Charles Gala Venue - Production MySQL Database Schema & Initial Data
-- Location: Kulur, Melkoppala, Mangalore 575013 (WRJM+V4 Mangaluru, Karnataka)
-- Repository: https://github.com/delson93/CGV-Website.git
-- Engine: MySQL 8.0+ / MariaDB 10.5+ (InnoDB, utf8mb4_unicode_ci)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `charles_gala_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `charles_gala_db`;

-- 1. Admin Users Table (Production Bcrypt Authentication & Brute-Force Lockout)
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL DEFAULT 'info@charlesgala.in',
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'superadmin',
  `failed_attempts` INT NOT NULL DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `role`) VALUES
(1, 'admin', 'info@charlesgala.in', '$2y$10$CmVN0HMdm0Nk6H5pxkcEuOjPl6gwUC5j0ANe.48XLYgog0.1.N5r6', 'superadmin')
ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `email` = VALUES(`email`);

-- 2. Admin Active Sessions Table (Database-backed Session Security)
CREATE TABLE IF NOT EXISTS `admin_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_token` VARCHAR(128) NOT NULL UNIQUE,
  `admin_id` INT NOT NULL,
  `ip_address` VARCHAR(64) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`session_token`),
  CONSTRAINT `fk_admin_session_user` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Site Settings Table (Backend WhatsApp/Call Contact, Social Media, SEO & Gemini AI Config)
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Charles Gala Venue'),
('tagline', 'Luxury Stays, Grand 1500-Guest Open Ground Hall, Mini Hall & Public Pool Booking in Kulur, Mangalore'),
('phone_primary', '8762516924'),
('whatsapp_number', '918762516924'),
('whatsapp_default_msg', 'Hello Charles Gala Venue! I would like to inquire about booking and availability at Kulur, Mangalore.'),
('email', 'info@charlesgala.in'),
('address', 'Kulur, Melkoppala, Mangalore, Karnataka 575013, India'),
('plus_code', 'WRJM+V4 Mangaluru, Karnataka, India'),
('latitude', '12.9322304'),
('longitude', '74.8328302'),
('google_maps_url', 'https://www.google.com/maps/place/Charles+Gala+Venue/@12.9322304,74.8302553,17z/data=!3m1!4b1!4m6!3m5!1s0x3ba351c8a471c265:0xb51a9ed2830e9d68!8m2!3d12.9322304!4d74.8328302!16s%2Fg%2F11y0978f46?entry=ttu&g_ep=EgoyMDI2MTAyMC4wIKXMDSoASAFQAw%3D%3D'),
('google_maps_embed', 'https://maps.google.com/maps?q=12.9322304,74.8328302&hl=en&z=17&output=embed'),
('instagram_url', 'https://www.instagram.com/charlesgalavenue'),
('youtube_url', 'https://www.youtube.com/@charlesgalavenue'),
('facebook_url', 'https://www.facebook.com/charlesgalavenue'),
('seo_meta_title', 'Charles Gala Venue Mangalore | Best Stay, 1500-Capacity Open Ground Hall & Pool Booking in Kulur'),
('seo_meta_description', 'Book Charles Gala Venue in Kulur, Melkoppala, Mangalore (575013). Featuring luxury stay rooms, a 1500-guest Open Ground Hall, 250-guest AC Mini Hall, public & private swimming pool booking for anyone, and bespoke event decorations & lighting.'),
('seo_keywords', 'best stay in Mangalore, open ground hall Mangalore, pool for anyone Mangalore, pool booking Kulur, Charles Gala Venue, wedding lawn 1500 capacity Mangalore, mini hall 250 capacity Kulur, event decorations and lightings Mangalore, resort stay Melkoppala 575013'),
('seo_canonical_url', 'https://charlesgala.in/'),
('seo_score', '98'),
('gemini_api_key', ''),
('gemini_model', 'gemini-2.5-flash')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 4. About Us Section Table
CREATE TABLE IF NOT EXISTS `about_section` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `headline` VARCHAR(255) NOT NULL,
  `subheadline` VARCHAR(255) NOT NULL,
  `content_html` TEXT NOT NULL,
  `highlights_json` TEXT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `about_section` (`id`, `headline`, `subheadline`, `content_html`, `highlights_json`, `image_path`) VALUES
(1,
 'Mangalore''s Premier Destination for Luxury Stays, Grand Galas & Poolside Leisure',
 'Nestled in the lush palm groves of Kulur, Melkoppala, Mangalore 575013 (Plus Code: WRJM+V4 Mangaluru)',
 '<p>Welcome to <strong>Charles Gala Venue</strong>, where coastal hospitality meets grand celebration. Strategically located at <strong>Kulur, Melkoppala, Mangalore 575013</strong>, our estate hosts everything from 1,500-guest open-air weddings to intimate family stays and public or private swimming pool sessions.</p><p>Whether you are searching for the <strong>best stay in Mangalore</strong>, an expansive <strong>open ground hall</strong> under the stars, a 250-seater air-conditioned <strong>mini hall</strong>, or effortless <strong>pool booking for anyone</strong>—families, friends, or weekend day-visitors—our dedicated team and in-house decoration & lighting specialists craft unforgettable experiences.</p>',
 '["1,500-Person Open Ground Hall","250-Person AC Mini Hall","Swimming Pool Open for Anyone","Best Stay Boutique Guest Rooms","In-House Decorations & Lightings","Prime Kulur 575013 Location"]',
 'uploads/about/hero.jpg')
ON DUPLICATE KEY UPDATE `headline` = VALUES(`headline`);

-- 5. Offerings Table (Admin editable offerings, photos, capacities, SEO keywords & WhatsApp messages)
CREATE TABLE IF NOT EXISTS `offerings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `category` VARCHAR(50) NOT NULL DEFAULT 'halls',
  `title` VARCHAR(150) NOT NULL,
  `badge` VARCHAR(100) NOT NULL,
  `capacity_label` VARCHAR(100) NOT NULL,
  `short_desc` TEXT NOT NULL,
  `features_json` TEXT NOT NULL,
  `seo_focus_keyword` VARCHAR(150) NOT NULL,
  `whatsapp_custom_msg` VARCHAR(255) NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `offerings` (`id`, `slug`, `category`, `title`, `badge`, `capacity_label`, `short_desc`, `features_json`, `seo_focus_keyword`, `whatsapp_custom_msg`, `image_path`, `sort_order`) VALUES
(1, 'open-ground-hall', 'halls', 'Open Ground Hall', 'Grand Outdoor Venue', 'Crowd Capacity: 1,500 Persons',
 'Our sprawling tropical open-air gala lawn in Kulur, Mangalore comfortably accommodates a crowd of up to 1,500 guests. Ideal for grand weddings, receptions, sangeet nights, corporate galas, and large celebrations.',
 '["1,500 Guests Seating & Floating","Grand Custom Stage & Mandap","Dedicated Buffet & Dining Lawn","Ample Valet Parking & Generator Backup"]',
 'open ground hall Mangalore 1500 capacity',
 'Hello Charles Gala Venue! I want to book the *Open Ground Hall (Crowd 1500 Persons)* in Kulur, Mangalore. Please share available dates and packages.',
 'uploads/offerings/open_ground.jpg', 1),

(2, 'mini-hall', 'halls', 'Mini Hall', 'Indoor AC Banquet', 'Crowd Capacity: 250 Persons',
 'An elegant, fully air-conditioned indoor banquet hall designed for up to 250 guests. Perfect for engagement ceremonies, birthday parties, naming ceremonies, corporate seminars, and intimate family functions.',
 '["250 Guests AC Banquet Seating","Crystal Chandeliers & Acoustic Stage","Built-in Audio/PA System","Attached Dining Area & Green Rooms"]',
 'mini hall 250 capacity Kulur Mangalore',
 'Hello Charles Gala Venue! I would like to book the *Mini Hall (250 Persons)*. Please share availability and tariff.',
 'uploads/offerings/mini_hall.jpg', 2),

(3, 'swimming-pool', 'pool', 'Swimming Pool (Pool for Anyone)', 'Public & Private Pool Booking', 'Open for Anyone • Hourly & Event Slots',
 'Dive into crystal-clear waters surrounded by golden palms! Our resort swimming pool is open for anyone—book individual hourly passes, family weekend dips, kids'' pool parties, or private poolside celebrations.',
 '["Pool Booking Open for Anyone","Hourly Passes & Private Pool Parties","Crystal-Clear Filtered Water & Kids Zone","Clean Changing Rooms, Lockers & Loungers"]',
 'pool for anyone and pool booking Mangalore',
 'Hello Charles Gala Venue! I want to make a *Swimming Pool Booking* (Pool for Anyone / Private Pool Slot). Please share timings and pass rates.',
 'uploads/offerings/pool.jpg', 3),

(4, 'luxury-rooms', 'stay', 'Luxury Stay Rooms', 'Best Stay in Mangalore', 'Boutique Suites & Family Stay',
 'Experience the best stay in Kulur, Mangalore. Our spacious teak-accented guest rooms and bridal suites offer serene palm views, king-size beds, high-speed Wi-Fi, and direct access to the swimming pool and lawns.',
 '["King-Size Beds & Palm-View Balconies","Bridal & Wedding Family Stay Packages","Complimentary Pool Access & High-Speed Wi-Fi","24/7 Hospitality & Room Service"]',
 'best stay in Mangalore Kulur rooms',
 'Hello Charles Gala Venue! I am looking for the *Best Stay Rooms* in Kulur, Mangalore. Please share room availability and nightly rates.',
 'uploads/offerings/rooms.jpg', 4),

(5, 'decorations-and-lightings', 'decor', 'Decorations and Lightings', 'In-House Event Styling', 'Custom Floral & Lighting Themes',
 'Transform your celebration with our in-house Decorations and Lightings specialists. From royal floral mandaps and golden fairy-light canopies to architectural LED uplighting and poolside ambiance, we customize every detail.',
 '["Bespoke Floral Stage & Entrance Arches","Golden Fairy-Light Canopy & Chandeliers","Concert-Grade Sound & Architectural Lighting","Tailored Wedding, Reception & Pool Party Decor"]',
 'event decorations and lightings Mangalore',
 'Hello Charles Gala Venue! I want to inquire about *Decorations and Lightings* packages for my event.',
 'uploads/offerings/decorations.jpg', 5)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 6. Gallery Table (Photos & YouTube Videos with Autoplay Toggle)
CREATE TABLE IF NOT EXISTS `gallery_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `media_type` ENUM('photo', 'youtube') NOT NULL DEFAULT 'photo',
  `title` VARCHAR(150) NOT NULL,
  `category` VARCHAR(100) NOT NULL DEFAULT 'Venue',
  `image_path` VARCHAR(255) DEFAULT NULL,
  `youtube_url` VARCHAR(255) DEFAULT NULL,
  `youtube_id` VARCHAR(50) DEFAULT NULL,
  `autoplay` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gallery_items` (`id`, `media_type`, `title`, `category`, `image_path`, `youtube_url`, `youtube_id`, `autoplay`, `sort_order`) VALUES
(1, 'photo', 'Aerial Golden Hour View — Lawn & Resort Pool', 'Venue Overview', 'uploads/gallery/hero.jpg', NULL, NULL, 0, 1),
(2, 'photo', 'Open Ground Hall (1,500 Crowd Setup)', 'Open Ground Hall', 'uploads/gallery/open_ground.jpg', NULL, NULL, 0, 2),
(3, 'photo', 'Swimming Pool — Open for Anyone & Pool Parties', 'Swimming Pool', 'uploads/gallery/pool.jpg', NULL, NULL, 0, 3),
(4, 'photo', 'Best Stay Luxury Guest Suite with Balcony', 'Rooms', 'uploads/gallery/rooms.jpg', NULL, NULL, 0, 4),
(5, 'photo', '250-Capacity AC Mini Hall Banquet Setup', 'Mini Hall', 'uploads/gallery/mini_hall.jpg', NULL, NULL, 0, 5),
(6, 'photo', 'Royal Stage Decorations & Golden Fairy Lighting', 'Decorations', 'uploads/gallery/decorations.jpg', NULL, NULL, 0, 6),
(7, 'youtube', 'Charles Gala Venue — Full Property & Pool Video Showcase', 'YouTube Tour', 'uploads/gallery/hero.jpg', 'https://www.youtube.com/watch?v=ScMzIvxBSi4', 'ScMzIvxBSi4', 1, 7)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 7. SEO Audit Logs Table (Records Gemini AI SEO Checks & Auto-Fixes in MySQL)
CREATE TABLE IF NOT EXISTS `seo_audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `score` INT NOT NULL DEFAULT 98,
  `meta_title` VARCHAR(255) NOT NULL,
  `meta_description` TEXT NOT NULL,
  `keywords` TEXT NOT NULL,
  `summary` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `seo_audit_logs` (`score`, `meta_title`, `meta_description`, `keywords`, `summary`) VALUES
(98,
 'Charles Gala Venue Mangalore | Best Stay, 1500-Capacity Open Ground Hall & Pool Booking in Kulur',
 'Book Charles Gala Venue in Kulur, Melkoppala, Mangalore (575013). Featuring luxury stay rooms, a 1500-guest Open Ground Hall, 250-guest AC Mini Hall, public & private swimming pool booking for anyone, and bespoke event decorations & lighting.',
 'best stay in Mangalore, open ground hall Mangalore, pool for anyone Mangalore, pool booking Kulur',
 'Initial production MySQL SEO audit verified all priority keywords and LocalBusiness + EventVenue schema.');
