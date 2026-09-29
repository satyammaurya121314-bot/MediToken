-- MediToken: Doctor Appointment Booking & Digital Token Generation System
-- Database Schema: meditoken_db
-- Clean production schema without demo/fake data

CREATE DATABASE IF NOT EXISTS `meditoken_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `meditoken_db`;

-- Drop existing tables if needed in reverse dependency order
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `doctors`;
DROP TABLE IF EXISTS `patients`;
DROP TABLE IF EXISTS `admins`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Patients Table
CREATE TABLE `patients` (
    `patient_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `mobile_no` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Doctors Table
CREATE TABLE `doctors` (
    `doctor_id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `specialization` VARCHAR(100) NOT NULL,
    `available_time` VARCHAR(100) NOT NULL DEFAULT '09:00 AM - 01:00 PM',
    `availability_status` ENUM('Available', 'Unavailable') NOT NULL DEFAULT 'Available',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Appointments Table
CREATE TABLE `appointments` (
    `appointment_id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `appointment_date` DATE NOT NULL,
    `appointment_time` VARCHAR(20) NOT NULL,
    `token_number` VARCHAR(20) NOT NULL,
    `status` ENUM('Waiting', 'Serving', 'Completed', 'Cancelled', 'Skipped', 'No-Show') NOT NULL DEFAULT 'Waiting',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_apt_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_apt_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
    CONSTRAINT `uq_doc_date_token` UNIQUE (`doctor_id`, `appointment_date`, `token_number`),
    INDEX `idx_doc_date_status` (`doctor_id`, `appointment_date`, `status`),
    INDEX `idx_patient_date` (`patient_id`, `appointment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Admins Table
CREATE TABLE `admins` (
    `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Notifications Table
CREATE TABLE `notifications` (
    `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `doctor_id` INT DEFAULT NULL,
    `appointment_id` INT DEFAULT NULL,
    `message` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'info',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notif_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
