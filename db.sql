-- ===================================================
-- Abuja Life - Database Schema & Initial Data
-- Version: 1.0.0
-- ===================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `character_vehicles`;
DROP TABLE IF EXISTS `character_properties`;
DROP TABLE IF EXISTS `bank_transactions`;
DROP TABLE IF EXISTS `characters`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `education_courses`;
DROP TABLE IF EXISTS `properties`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `lifestyle_activities`;
DROP TABLE IF EXISTS `random_events`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------
-- 1. Users Table
-- ---------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') DEFAULT 'user',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_active` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 2. Jobs Table
-- ---------------------------------------------------
CREATE TABLE `jobs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `daily_salary` DECIMAL(12, 2) NOT NULL,
    `required_intelligence` INT DEFAULT 10,
    `required_education` VARCHAR(50) DEFAULT 'SSCE',
    `energy_cost` INT DEFAULT 20,
    `description` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 3. Education Courses Table
-- ---------------------------------------------------
CREATE TABLE `education_courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `institution` VARCHAR(100) NOT NULL,
    `cost` DECIMAL(12, 2) NOT NULL,
    `intelligence_gain` INT NOT NULL,
    `energy_cost` INT DEFAULT 15,
    `qualification` VARCHAR(50) NOT NULL,
    `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 4. Properties Table
-- ---------------------------------------------------
CREATE TABLE `properties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `district` VARCHAR(50) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `price` DECIMAL(14, 2) NOT NULL,
    `daily_rent_yield` DECIMAL(12, 2) NOT NULL,
    `happiness_bonus` INT DEFAULT 10,
    `prestige_points` INT DEFAULT 5,
    `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 5. Vehicles Table
-- ---------------------------------------------------
CREATE TABLE `vehicles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `brand` VARCHAR(50) NOT NULL,
    `price` DECIMAL(14, 2) NOT NULL,
    `daily_upkeep` DECIMAL(10, 2) DEFAULT 500.00,
    `happiness_bonus` INT DEFAULT 5,
    `cred_bonus` INT DEFAULT 5,
    `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 6. Characters Table
-- ---------------------------------------------------
CREATE TABLE `characters` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Non-Binary') DEFAULT 'Male',
    `avatar` VARCHAR(255) DEFAULT 'default_avatar.png',
    `age` INT DEFAULT 18,
    `days_lived` INT DEFAULT 1,
    `health` INT DEFAULT 100,
    `happiness` INT DEFAULT 100,
    `energy` INT DEFAULT 100,
    `max_energy` INT DEFAULT 100,
    `intelligence` INT DEFAULT 20,
    `karma` INT DEFAULT 50,
    `street_cred` INT DEFAULT 10,
    `cash` DECIMAL(14, 2) DEFAULT 25000.00,
    `bank` DECIMAL(14, 2) DEFAULT 5000.00,
    `loan_balance` DECIMAL(14, 2) DEFAULT 0.00,
    `district` VARCHAR(50) DEFAULT 'Kubwa',
    `current_job_id` INT NULL,
    `education_level` VARCHAR(50) DEFAULT 'SSCE',
    `primary_vehicle_id` INT NULL,
    `primary_property_id` INT NULL,
    `jail_days` INT DEFAULT 0,
    `is_alive` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_char_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_char_job` FOREIGN KEY (`current_job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_char_vehicle` FOREIGN KEY (`primary_vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_char_property` FOREIGN KEY (`primary_property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 7. Character Properties (Owned real estate)
-- ---------------------------------------------------
CREATE TABLE `character_properties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `character_id` INT NOT NULL,
    `property_id` INT NOT NULL,
    `is_rented_out` TINYINT(1) DEFAULT 0,
    `purchased_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cp_character` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cp_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 8. Character Vehicles (Garage)
-- ---------------------------------------------------
CREATE TABLE `character_vehicles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `character_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `purchased_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cv_character` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cv_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 9. Activity Logs
-- ---------------------------------------------------
CREATE TABLE `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `character_id` INT NOT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `message` TEXT NOT NULL,
    `cash_change` DECIMAL(14, 2) DEFAULT 0.00,
    `energy_change` INT DEFAULT 0,
    `happiness_change` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_al_char` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 10. Random Life Events
-- ---------------------------------------------------
CREATE TABLE `random_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `option_a_label` VARCHAR(100) NOT NULL,
    `option_a_cash` DECIMAL(12, 2) DEFAULT 0.00,
    `option_a_health` INT DEFAULT 0,
    `option_a_happiness` INT DEFAULT 0,
    `option_a_cred` INT DEFAULT 0,
    `option_a_msg` VARCHAR(255) NOT NULL,
    `option_b_label` VARCHAR(100) NOT NULL,
    `option_b_cash` DECIMAL(12, 2) DEFAULT 0.00,
    `option_b_health` INT DEFAULT 0,
    `option_b_happiness` INT DEFAULT 0,
    `option_b_cred` INT DEFAULT 0,
    `option_b_msg` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- SEED DATA
-- ===================================================

-- 1. JOBS
INSERT INTO `jobs` (`id`, `title`, `category`, `daily_salary`, `required_intelligence`, `required_education`, `energy_cost`, `description`) VALUES
(1, 'Keke Napep Rider', 'Informal', 4500.00, 5, 'SSCE', 25, 'Hustle daily commuting passengers around Kubwa and Dutse junctions.'),
(2, 'Banex Plaza Phone Technician', 'Trade', 9500.00, 15, 'SSCE', 20, 'Screen repairs, unlocking iPhones, and flashing Androids in Wuse 2.'),
(3, 'Uber/Bolt Driver', 'Transportation', 14000.00, 20, 'SSCE', 25, 'Drive riders between Airport Road, Maitama, and Central Business District.'),
(4, 'Federal Ministry Clerical Officer', 'Civil Service', 18000.00, 35, 'OND', 15, 'Steady civil service job at the Federal Secretariat, Shehu Shagari Way.'),
(5, 'Junior Software Developer', 'Technology', 35000.00, 50, 'BSc', 25, 'Build web APIs and mobile apps for tech startups in Jabi and Wuse 2.'),
(6, 'Commercial Bank Relationship Manager', 'Corporate', 55000.00, 60, 'BSc', 25, 'Manage VIP HNIs and deposit mobilisation in Central Business District.'),
(7, 'Senior Tech Lead / Remote Consultant', 'Technology', 110000.00, 80, 'BSc', 30, 'Earn foreign tech contracts working from your laptop at Wuse 2 cafés.'),
(8, 'Special Assistant to Minister', 'Politics', 220000.00, 70, 'MSc', 20, 'Powerful political appointment in Aso Rock corridor with juicy allowances.'),
(9, 'Managing Director (Oil & Gas Infrastructure)', 'Executive', 500000.00, 90, 'MSc', 25, 'Supervise multi-billion Naira government and private energy contracts in Maitama.');

-- 2. EDUCATION COURSES
INSERT INTO `education_courses` (`id`, `name`, `institution`, `cost`, `intelligence_gain`, `energy_cost`, `qualification`, `description`) VALUES
(1, 'Ordinary National Diploma (OND)', 'Dorben Polytechnic, Bwari', 65000.00, 15, 20, 'OND', 'Foundation tertiary qualification in Business Administration or Computer Science.'),
(2, 'Bachelor of Science (BSc Degree)', 'University of Abuja (UniAbuja)', 180000.00, 25, 25, 'BSc', 'Full undergraduate university degree from Gwagwalada main campus.'),
(3, 'Fullstack Web & AI Engineering Bootcamp', 'Abuja Tech Hub, Jabi', 120000.00, 30, 20, 'TechCert', 'Learn modern programming, databases, cloud architecture, and AI tooling.'),
(4, 'Private University Executive BSc', 'Baze University, Jabi', 850000.00, 35, 20, 'BSc', 'Top-tier private university education with modern facilities and elite networking.'),
(5, 'Master of Science / MBA', 'Nile University of Nigeria', 1200000.00, 25, 25, 'MSc', 'Postgraduate degree to open boardroom doors and high federal appointments.');

-- 3. PROPERTIES
INSERT INTO `properties` (`id`, `name`, `district`, `type`, `price`, `daily_rent_yield`, `happiness_bonus`, `prestige_points`, `description`) VALUES
(1, 'Self-Contain Room', 'Lugbe', 'Apartment', 450000.00, 1500.00, 10, 5, 'Cozy single room along Airport Road with prepaid meter and water tank.'),
(2, '2-Bedroom Flat', 'Kubwa', 'Flat', 1200000.00, 4200.00, 18, 12, 'Spacious flat near Kubwa train station with dedicated parking.'),
(3, '3-Bedroom Apartment', 'Gwarinpa Estate', 'Apartment', 3500000.00, 11000.00, 28, 25, 'Modern flat in West Africa’s largest planned housing estate, close to 3rd Avenue.'),
(4, '4-Bedroom Semi-Detached Duplex', 'Wuse 2', 'Duplex', 15000000.00, 45000.00, 40, 55, 'Executive residence in the vibrant commercial and culinary heart of Abuja.'),
(5, 'Luxury 6-Bedroom Smart Mansion', 'Maitama', 'Mansion', 65000000.00, 180000.00, 60, 95, 'High-security diplomatic haven with swimming pool, bulletproof glass, and golf view.'),
(6, 'Ultra-Luxury Presidential Villa Estate', 'Asokoro', 'Villa', 180000000.00, 500000.00, 80, 150, 'Hilltop palatial compound overlooking Aso Rock with private helipad and CCTV security.');

-- 4. VEHICLES
INSERT INTO `vehicles` (`id`, `name`, `brand`, `price`, `daily_upkeep`, `happiness_bonus`, `cred_bonus`, `description`) VALUES
(1, 'Bajaj Boxer Motorcycle', 'Bajaj', 250000.00, 800.00, 8, 5, 'Quick navigation through Dutse Alhaji and Mararaba traffic gridlock.'),
(2, 'Toyota Corolla ("Big Daddy")', 'Toyota', 2800000.00, 2500.00, 20, 18, 'The undisputed king of Abuja reliability. Cheap spare parts, high fuel economy.'),
(3, 'Honda Accord ("Evil Spirit")', 'Honda', 3500000.00, 3200.00, 22, 22, 'Sleek Nigerian executive saloon with smooth AC for cruising through CBD.'),
(4, 'Lexus RX 350', 'Lexus', 12500000.00, 7500.00, 38, 45, 'The unofficial Abuja youth elite and tech superstar SUV. High road respect.'),
(5, 'Mercedes-Benz C300 4MATIC', 'Mercedes-Benz', 22000000.00, 12000.00, 50, 60, 'Burble exhaust, ambient interior lighting, and instant valet parking in Wuse 2 clubs.'),
(6, 'Range Rover Autobiography V8', 'Land Rover', 55000000.00, 28000.00, 70, 90, 'Imposing British luxury SUV that commands military salutes at security barriers.'),
(7, 'Convoy with Toyota Land Cruiser & Escort Siren', 'Armored Fleet', 130000000.00, 65000.00, 90, 140, 'Twin V8 Land Cruisers with flashing strobe lights and siren escorts clearing Airport Road.');

-- 5. RANDOM LIFE EVENTS
INSERT INTO `random_events` (`id`, `title`, `description`, `option_a_label`, `option_a_cash`, `option_a_health`, `option_a_happiness`, `option_a_cred`, `option_a_msg`, `option_b_label`, `option_b_cash`, `option_b_health`, `option_b_happiness`, `option_b_cred`, `option_b_msg`) VALUES
(1, 'Police Checkpoint at Airport Road', 'You are stopped by VIO and Police officers waving flashlights: "Oga park well! Anything for the boys?"', 'Give them ₦3,000 for "pure water"', -3000.00, 0, 5, 2, 'The officers hailed you: "Chairman! Safe trip o!" and cleared your passage immediately.', 'Argue and demand to see your offence', 0.00, -10, -15, 5, 'They delayed you for 2 hours checking car fire extinguisher and triangle. Your blood pressure spiked!'),

(2, 'Wuse 2 Crypto Arbitrage Tip', 'Your tech bro friend at an Aminu Kano Crescent lounge claims he found an insider triangular arbitrage on USDT.', 'Risk ₦50,000 to trade the dip', 85000.00, 0, 20, 10, 'Green candles! The trade executed smoothly and you bagged ₦85,000 clean profit.', 'Play it safe and ignore the tip', 0.00, 0, 0, 0, 'You kept your funds safe in your account. The coin later experienced volatility anyway.'),

(3, 'Urgent 2k Request from Extended Family', 'Your favorite cousin from the village sends a WhatsApp voice note: "Egbon, billing don hold me for school, abeg send urgent 10k."', 'Send ₦10,000 with love', -10000.00, 0, 15, 12, 'Your cousin called your mother and gave you glowing blessings. Good karma unlocked!', 'Tell them "Account dry right now"', 0.00, 0, -5, -5, 'You preserved your cash, but your cousin left you on read for 2 weeks.'),

(4, 'Abuja High Society Owambe Invitation', 'A prominent Senator is hosting a daughter wedding reception at the International Conference Centre (ICC).', 'Spray ₦40,000 crisp new notes on the dance floor', -40000.00, 0, 30, 35, 'The praise singers hyped your name on microphone: "Olowo Abuja!" Your street cred exploded!', 'Sit quietly and just enjoy small chops & jollof', 0.00, 0, 10, 5, 'You enjoyed delicious party jollof rice, assorted meat, and chilled chapman without spending a dime.'),

(5, 'NEPA / DisCo Power Surge', 'Sudden power grid restoration with extreme voltage surge while you were away from home.', 'Install heavy duty surge protector and inverter (₦25,000)', -25000.00, 0, 10, 5, 'Your electronics stayed intact and you now have uninterrupted power supply.', 'Manage it and hope for the best', -15000.00, 0, -20, 0, 'A small spark burnt your television adaptor! You had to spend ₦15,000 on repairs.');
