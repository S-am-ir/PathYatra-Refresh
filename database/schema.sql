-- =========================================================
-- YatraPath - Relational Database Schema
-- Target: MySQL 8.x / MariaDB 10.x
-- =========================================================

CREATE DATABASE IF NOT EXISTS `yatra_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `yatra_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `itin_slots`;
DROP TABLE IF EXISTS `itin_days`;
DROP TABLE IF EXISTS `itineraries`;
DROP TABLE IF EXISTS `activities`;
DROP TABLE IF EXISTS `destinations`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('traveler', 'admin') NOT NULL DEFAULT 'traveler',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Destinations Table
CREATE TABLE `destinations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `region` ENUM('Himalayan', 'Hilly', 'Terai') NOT NULL,
    `description` TEXT NOT NULL,
    `avg_cost_per_day` DECIMAL(10,2) NOT NULL DEFAULT 3000.00,
    `suitable_seasons` VARCHAR(150) NOT NULL, -- Comma-separated: 'Spring,Summer,Autumn,Winter'
    `latitude` DECIMAL(10,7) NOT NULL,
    `longitude` DECIMAL(10,7) NOT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `avg_rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
    `total_reviews` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_dest_region` (`region`),
    INDEX `idx_dest_rating` (`avg_rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Activities Table
CREATE TABLE `activities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `destination_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `category` ENUM('adventure', 'cultural', 'nature', 'food', 'wellness', 'photography') NOT NULL,
    `duration_hours` DECIMAL(4,2) NOT NULL DEFAULT 1.50,
    `cost_npr` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `preferred_slot` ENUM('Morning', 'Afternoon', 'Evening') NOT NULL,
    `suitable_seasons` VARCHAR(150) NOT NULL, -- e.g., 'Spring,Autumn'
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_activities_destination`
        FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_act_dest` (`destination_id`),
    INDEX `idx_act_category` (`category`),
    INDEX `idx_act_slot` (`preferred_slot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Itineraries Table
CREATE TABLE `itineraries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_days` INT NOT NULL,
    `total_budget` DECIMAL(10,2) NOT NULL,
    `estimated_cost` DECIMAL(10,2) NOT NULL,
    `season` VARCHAR(50) NOT NULL,
    `status` ENUM('planned', 'completed') NOT NULL DEFAULT 'planned',
    `share_token` VARCHAR(64) DEFAULT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_itineraries_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_itin_user` (`user_id`),
    INDEX `idx_itin_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Itinerary Days Table
CREATE TABLE `itin_days` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `itinerary_id` INT NOT NULL,
    `day_number` INT NOT NULL,
    `day_date` DATE NOT NULL,
    `destination_id` INT DEFAULT NULL,
    `destination_name` VARCHAR(150) NOT NULL,
    `accommodation_name` VARCHAR(150) DEFAULT NULL,
    `accommodation_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `day_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT `fk_itin_days_itinerary`
        FOREIGN KEY (`itinerary_id`) REFERENCES `itineraries` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_itin_days_dest`
        FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_itin_days_itinerary` (`itinerary_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Itinerary Activity Slots Table
CREATE TABLE `itin_slots` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `itin_day_id` INT NOT NULL,
    `slot_name` ENUM('morning', 'afternoon', 'evening') NOT NULL,
    `activity_id` INT DEFAULT NULL,
    `activity_name` VARCHAR(200) NOT NULL,
    `duration_hours` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    `cost_npr` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT `fk_itin_slots_day`
        FOREIGN KEY (`itin_day_id`) REFERENCES `itin_days` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_itin_slots_activity`
        FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_itin_slots_day` (`itin_day_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Reviews Table
CREATE TABLE `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `destination_id` INT NOT NULL,
    `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `comment` TEXT NOT NULL,
    `trip_month_year` VARCHAR(20) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_reviews_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_destination`
        FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_reviews_destination` (`destination_id`),
    INDEX `idx_reviews_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
