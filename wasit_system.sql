-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: 11 مايو 2026
-- إصدار الخادم: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wasit_system`
--

DROP TABLE IF EXISTS `settlementresponses`;
DROP TABLE IF EXISTS `session_parties`;
DROP TABLE IF EXISTS `finaldecisions`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `casehandlers`;
DROP TABLE IF EXISTS `caseparties`;
DROP TABLE IF EXISTS `cases`;
DROP TABLE IF EXISTS `employees`;

-- --------------------------------------------------------
-- بنية الجدول `employees`
-- --------------------------------------------------------

CREATE TABLE `employees` (
  `employee_id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_status` enum('PENDING','APPROVED','REJECTED') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  PRIMARY KEY (`employee_id`),
  UNIQUE KEY `email` (`email`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- حساب الأدمن فقط - كلمة المرور: password
INSERT INTO `employees` (`full_name`, `department`, `email`, `username`, `password_hash`, `account_status`)
VALUES ('مدير النظام', 'مسؤول النظام', 'admin@admin.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'APPROVED');

-- --------------------------------------------------------
-- بنية الجدول `cases`
-- --------------------------------------------------------

CREATE TABLE `cases` (
  `case_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `priority` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('OPEN','UNDER_REVIEW','IN_MEDIATION','ESCALATED','CLOSED_SETTLED','CLOSED_DECIDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by_employee_id` int NOT NULL,
  `external_party_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `party_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`case_id`),
  KEY `created_by_employee_id` (`created_by_employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- بنية الجدول `casehandlers`
-- --------------------------------------------------------

CREATE TABLE `casehandlers` (
  `handler_id` int NOT NULL AUTO_INCREMENT,
  `case_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `handler_role` enum('LEGAL_DEPT','UPPER_MGMT') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `assigned_at` datetime DEFAULT NULL,
  PRIMARY KEY (`handler_id`),
  KEY `case_id` (`case_id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- بنية الجدول `caseparties`
-- --------------------------------------------------------

CREATE TABLE `caseparties` (
  `case_party_id` int NOT NULL AUTO_INCREMENT,
  `case_id` int NOT NULL,
  `employee_id` int DEFAULT NULL,
  `party_role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `party_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`case_party_id`),
  UNIQUE KEY `unique_case_employee` (`case_id`,`employee_id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- بنية الجدول `finaldecisions`
-- --------------------------------------------------------

CREATE TABLE `finaldecisions` (
  `decision_id` int NOT NULL AUTO_INCREMENT,
  `case_id` int NOT NULL,
  `decided_by_employee_id` int NOT NULL,
  `decision_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`decision_id`),
  UNIQUE KEY `unique_case` (`case_id`),
  KEY `decided_by_employee_id` (`decided_by_employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- بنية الجدول `sessions`
-- --------------------------------------------------------

CREATE TABLE `sessions` (
  `session_id` int NOT NULL AUTO_INCREMENT,
  `case_id` int NOT NULL,
  `session_type` enum('MEDIATION','ARBITRATION') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `session_date` datetime DEFAULT NULL,
  `session_time` time DEFAULT NULL,
  `settlement_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `settlement_status` enum('PENDING','ACCEPTED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location_details` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `case_id` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- بنية الجدول `session_parties`
-- --------------------------------------------------------

CREATE TABLE `session_parties` (
  `session_party_id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `employee_id` int NOT NULL,
  PRIMARY KEY (`session_party_id`),
  KEY `session_id` (`session_id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- بنية الجدول `settlementresponses`
-- --------------------------------------------------------

CREATE TABLE `settlementresponses` (
  `response_id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `response` enum('ACCEPT','REJECT') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `response_date` datetime DEFAULT NULL,
  `reject_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`response_id`),
  UNIQUE KEY `unique_session_employee` (`session_id`,`employee_id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- قيود الجداول
-- --------------------------------------------------------

ALTER TABLE `cases`
  ADD CONSTRAINT `cases_ibfk_1` FOREIGN KEY (`created_by_employee_id`) REFERENCES `employees` (`employee_id`);

ALTER TABLE `casehandlers`
  ADD CONSTRAINT `casehandlers_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`),
  ADD CONSTRAINT `casehandlers_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

ALTER TABLE `caseparties`
  ADD CONSTRAINT `caseparties_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`),
  ADD CONSTRAINT `caseparties_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

ALTER TABLE `finaldecisions`
  ADD CONSTRAINT `finaldecisions_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`),
  ADD CONSTRAINT `finaldecisions_ibfk_2` FOREIGN KEY (`decided_by_employee_id`) REFERENCES `employees` (`employee_id`);

ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`);

ALTER TABLE `settlementresponses`
  ADD CONSTRAINT `settlementresponses_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`),
  ADD CONSTRAINT `settlementresponses_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
