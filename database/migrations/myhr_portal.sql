-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db:3306
-- Generation Time: Jan 29, 2026 at 09:28 AM
-- Server version: 8.0.44
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `myhr_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `cb_audit_logs`
--

CREATE TABLE `cb_audit_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `old_values` text,
  `new_values` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cb_audit_logs`
--

INSERT INTO `cb_audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 6, 'ประเมศวร์ บัวศรี', 'create_booking', 'booking', 19, NULL, '{\"destination\":\"test\",\"purpose\":\"test\",\"start_time\":\"2025-12-15T14:05\",\"end_time\":\"2025-12-17T14:05\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-16 14:05:58'),
(2, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve', 'booking', 19, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-16 14:06:22'),
(3, 6, 'ประเมศวร์ บัวศรี', 'manager_approve', 'booking', 19, '{\"status\":\"pending_manager\"}', '{\"status\":\"approved\",\"assigned_car_id\":null,\"fleet_card_id\":\"3\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-16 14:08:56'),
(4, 6, 'ประเมศวร์ บัวศรี', 'update_booking', 'booking', 19, '{\"id\":19,\"user_id\":6,\"driver_user_id\":null,\"driver_name\":\"Napat Ninprapa\",\"driver_email\":\"napat_nin@inteqc.com\",\"approver_email\":\"porames_bua@inteqc.com\",\"approver_name\":null,\"approver_user_id\":null,\"start_time\":\"2025-12-15 14:05:00\",\"end_time\":\"2025-12-17 14:05:00\",\"destination\":\"test\",\"purpose\":\"test\",\"passengers\":1,\"passengers_detail\":\"[{\\\"user_id\\\":null,\\\"name\\\":\\\"Natthamon Sonklin\\\",\\\"email\\\":\\\"natthamon_son@inteqc.com\\\"}]\",\"passenger_user_ids\":null,\"type\":null,\"status\":\"approved\",\"assigned_car_id\":null,\"fleet_card_id\":3,\"fleet_amount\":\"5000.00\",\"assigned_car\":null,\"assignment_note\":null,\"approval_token\":\"7eead8e1f9f88e76af7f461b7ea325dc\",\"token_expires_at\":\"2025-12-23 14:05:55\",\"approved_at\":null,\"rejected_at\":null,\"rejection_reason\":null,\"rejected_by\":null,\"supervisor_approved_at\":\"2025-12-16 14:06:22\",\"supervisor_approved_by\":\"porames_bua@inteqc.com\",\"supervisor_approved_user_id\":null,\"manager_approved_at\":\"2025-12-16 14:08:56\",\"manager_approved_by\":\"porames_bua@inteqc.com\",\"manager_approved_user_id\":6,\"created_at\":\"2025-12-16 14:05:55\",\"updated_at\":\"2025-12-16 14:08:56\",\"user_name\":\"porames.buasri\",\"user_fullname\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"user_email\":\"porames_bua@inteqc.com\",\"brand\":null,\"model\":null,\"license_plate\":null,\"fleet_card_number\":\"FC-003\"}', '{\"id\":19,\"start_time\":\"2025-12-15T14:05\",\"end_time\":\"2025-12-17T14:05\",\"driver_name\":\"Napat Ninprapa\",\"assigned_car_id\":null,\"fleet_card_id\":\"3\",\"fleet_amount\":\"5000.00\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-16 15:20:42'),
(5, 6, 'ประเมศวร์ บัวศรี', 'update_booking', 'booking', 19, '{\"id\":19,\"user_id\":6,\"driver_user_id\":null,\"driver_name\":\"Napat Ninprapa\",\"driver_email\":\"napat_nin@inteqc.com\",\"approver_email\":\"porames_bua@inteqc.com\",\"approver_name\":null,\"approver_user_id\":null,\"start_time\":\"2025-12-15 14:05:00\",\"end_time\":\"2025-12-17 14:05:00\",\"destination\":\"test\",\"purpose\":\"test\",\"passengers\":1,\"passengers_detail\":\"[{\\\"user_id\\\":null,\\\"name\\\":\\\"Natthamon Sonklin\\\",\\\"email\\\":\\\"natthamon_son@inteqc.com\\\"}]\",\"passenger_user_ids\":null,\"type\":null,\"status\":\"approved\",\"assigned_car_id\":null,\"fleet_card_id\":3,\"fleet_amount\":\"5000.00\",\"assigned_car\":null,\"assignment_note\":null,\"approval_token\":\"7eead8e1f9f88e76af7f461b7ea325dc\",\"token_expires_at\":\"2025-12-23 14:05:55\",\"approved_at\":null,\"rejected_at\":null,\"rejection_reason\":null,\"rejected_by\":null,\"supervisor_approved_at\":\"2025-12-16 14:06:22\",\"supervisor_approved_by\":\"porames_bua@inteqc.com\",\"supervisor_approved_user_id\":null,\"manager_approved_at\":\"2025-12-16 14:08:56\",\"manager_approved_by\":\"porames_bua@inteqc.com\",\"manager_approved_user_id\":6,\"created_at\":\"2025-12-16 14:05:55\",\"updated_at\":\"2025-12-16 15:20:42\",\"user_name\":\"porames.buasri\",\"user_fullname\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"user_email\":\"porames_bua@inteqc.com\",\"brand\":null,\"model\":null,\"license_plate\":null,\"fleet_card_number\":\"FC-003\"}', '{\"id\":19,\"start_time\":\"2025-12-15T14:05\",\"end_time\":\"2025-12-17T14:05\",\"driver_name\":\"Napat Ninprapa\",\"assigned_car_id\":null,\"fleet_card_id\":\"3\",\"fleet_amount\":\"50\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-16 15:21:02'),
(6, 8, 'Teerapon ngamakeam', 'create_booking', 'booking', 20, NULL, '{\"destination\":\"\\u0e02\\u0e2d\\u0e08\\u0e2d\\u0e14\\u0e23\\u0e16\\u0e1e\\u0e34\\u0e40\\u0e28\\u0e29\",\"purpose\":\"\\u0e40\\u0e19\\u0e37\\u0e48\\u0e2d\\u0e07\\u0e08\\u0e32\\u0e01\\u0e15\\u0e49\\u0e2d\\u0e07\\u0e01\\u0e32\\u0e23\\u0e08\\u0e2d\\u0e14\\u0e1e\\u0e34\\u0e40\\u0e28\\u0e29\",\"start_time\":\"2025-12-17T09:00\",\"end_time\":\"2025-12-17T17:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 11:30:45'),
(7, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve_token', 'booking', 20, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 11:34:44'),
(8, 6, 'ประเมศวร์ บัวศรี', 'manager_approve', 'booking', 20, '{\"status\":\"pending_manager\"}', '{\"status\":\"approved\",\"assigned_car_id\":null,\"fleet_card_id\":\"1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 11:57:13'),
(9, 8, 'Teerapon ngamakeam', 'create_car', 'car', 8, NULL, '{\"controller\":\"cars\",\"action\":\"create\",\"id\":\"\",\"name\":\"Supra\",\"brand\":\"Honda\",\"model\":\"Commuter\",\"type\":\"van\",\"license_plate\":\"\\u0e2b\\u0e011234\",\"capacity\":\"2\",\"status\":\"maintenance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 11:59:20'),
(10, 6, 'ประเมศวร์ บัวศรี', 'update_fleet_card', 'fleet_card', 1, '{\"id\":1,\"card_number\":\"FC-001\",\"department\":\"Sales\",\"credit_limit\":\"0.00\",\"current_balance\":\"0.00\",\"status\":\"active\",\"notes\":\"\",\"created_at\":\"2025-12-02 15:04:25\",\"updated_at\":\"2025-12-04 10:40:06\"}', '{\"controller\":\"fleetcards\",\"action\":\"update\",\"id\":\"1\",\"card_number\":\"FC-001\",\"department\":\"Sales\",\"credit_limit\":\"0.00\",\"current_balance\":\"0.00\",\"status\":\"active\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-18 11:08:46'),
(11, 6, 'ประเมศวร์ บัวศรี', 'update_car', 'car', 8, '{\"id\":8,\"name\":\"Supra\",\"brand\":\"Honda\",\"model\":\"Commuter\",\"license_plate\":\"\\u0e2b\\u0e011234\",\"type\":\"van\",\"capacity\":2,\"status\":\"maintenance\",\"created_at\":\"2025-12-17 11:59:20\",\"updated_at\":\"2025-12-17 11:59:20\"}', '{\"controller\":\"cars\",\"action\":\"update\",\"id\":\"8\",\"name\":\"Supra\",\"brand\":\"Honda\",\"model\":\"Commuter\",\"type\":\"van\",\"license_plate\":\"\\u0e2b\\u0e011234\",\"capacity\":\"2\",\"status\":\"maintenance\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-18 11:08:50'),
(12, 6, 'ประเมศวร์ บัวศรี', 'confirm_return', 'booking', 19, '{\"status\":\"approved\"}', '{\"status\":\"completed\",\"confirmed_by\":\"porames_bua@inteqc.com\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 08:57:33'),
(13, 6, 'ประเมศวร์ บัวศรี', 'manager_approve', 'booking', 20, '{\"status\":\"pending_manager\"}', '{\"status\":\"approved\",\"assigned_car_id\":\"7\",\"fleet_card_id\":null}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 08:57:55'),
(14, 4, 'Kittipong User', 'create_booking', 'booking', 21, NULL, '{\"destination\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"purpose\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"start_time\":\"2025-12-22T09:40\",\"end_time\":\"2025-12-22T17:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-22 09:37:39'),
(15, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve', 'booking', 21, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 09:48:44'),
(16, 6, 'ประเมศวร์ บัวศรี', 'manager_approve', 'booking', 21, '{\"status\":\"pending_manager\"}', '{\"status\":\"approved\",\"assigned_car_id\":\"5\",\"fleet_card_id\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 09:49:22'),
(17, 6, 'ประเมศวร์ บัวศรี', 'update_booking', 'booking', 21, '{\"id\":21,\"user_id\":4,\"driver_user_id\":6,\"driver_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"driver_email\":\"porames_bua@inteqc.com\",\"approver_email\":\"porames_bua@inteqc.com\",\"approver_name\":null,\"approver_user_id\":6,\"start_time\":\"2025-12-22 09:40:00\",\"end_time\":\"2025-12-22 17:00:00\",\"destination\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"purpose\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"passengers\":1,\"passengers_detail\":\"[{\\\"user_id\\\":6,\\\"name\\\":\\\"\\\\u0e1b\\\\u0e23\\\\u0e30\\\\u0e40\\\\u0e21\\\\u0e28\\\\u0e27\\\\u0e23\\\\u0e4c \\\\u0e1a\\\\u0e31\\\\u0e27\\\\u0e28\\\\u0e23\\\\u0e35\\\",\\\"email\\\":\\\"porames_bua@inteqc.com\\\"}]\",\"passenger_user_ids\":\"[6]\",\"type\":null,\"status\":\"approved\",\"assigned_car_id\":5,\"fleet_card_id\":null,\"fleet_amount\":null,\"assigned_car\":null,\"assignment_note\":null,\"approval_token\":\"e46205961bd01ad0bb162788c017d738\",\"token_expires_at\":\"2025-12-29 09:37:35\",\"approved_at\":null,\"rejected_at\":null,\"rejection_reason\":null,\"rejected_by\":null,\"supervisor_approved_at\":\"2025-12-22 09:48:44\",\"supervisor_approved_by\":\"porames_bua@inteqc.com\",\"supervisor_approved_user_id\":null,\"manager_approved_at\":\"2025-12-22 09:49:22\",\"manager_approved_by\":\"porames_bua@inteqc.com\",\"manager_approved_user_id\":6,\"created_at\":\"2025-12-22 09:37:35\",\"updated_at\":\"2025-12-22 09:49:22\",\"returned_at\":null,\"returned_confirmed_by\":null,\"user_reported_return_at\":null,\"actual_return_time\":null,\"return_notes\":null,\"in_use_at\":null,\"user_name\":\"user\",\"user_fullname\":\"Kittipong User\",\"user_email\":\"user@example.com\",\"brand\":\"Nissan\",\"model\":\"GTR R35-Nismo\",\"license_plate\":\"GTR-35\",\"fleet_card_number\":null}', '{\"id\":\"21\",\"start_time\":\"2025-12-22T09:55\",\"end_time\":\"2025-12-22T17:00\",\"assigned_car_id\":null,\"fleet_card_id\":\"2\",\"fleet_amount\":\"5000\",\"driver_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 09:50:00'),
(18, 6, 'ประเมศวร์ บัวศรี', 'update_booking', 'booking', 21, '{\"id\":21,\"user_id\":4,\"driver_user_id\":6,\"driver_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"driver_email\":\"porames_bua@inteqc.com\",\"approver_email\":\"porames_bua@inteqc.com\",\"approver_name\":null,\"approver_user_id\":6,\"start_time\":\"2025-12-22 09:55:00\",\"end_time\":\"2025-12-22 17:00:00\",\"destination\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"purpose\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"passengers\":1,\"passengers_detail\":\"[{\\\"user_id\\\":6,\\\"name\\\":\\\"\\\\u0e1b\\\\u0e23\\\\u0e30\\\\u0e40\\\\u0e21\\\\u0e28\\\\u0e27\\\\u0e23\\\\u0e4c \\\\u0e1a\\\\u0e31\\\\u0e27\\\\u0e28\\\\u0e23\\\\u0e35\\\",\\\"email\\\":\\\"porames_bua@inteqc.com\\\"}]\",\"passenger_user_ids\":\"[6]\",\"type\":null,\"status\":\"approved\",\"assigned_car_id\":5,\"fleet_card_id\":2,\"fleet_amount\":\"5000.00\",\"assigned_car\":null,\"assignment_note\":null,\"approval_token\":\"e46205961bd01ad0bb162788c017d738\",\"token_expires_at\":\"2025-12-29 09:37:35\",\"approved_at\":null,\"rejected_at\":null,\"rejection_reason\":null,\"rejected_by\":null,\"supervisor_approved_at\":\"2025-12-22 09:48:44\",\"supervisor_approved_by\":\"porames_bua@inteqc.com\",\"supervisor_approved_user_id\":null,\"manager_approved_at\":\"2025-12-22 09:49:22\",\"manager_approved_by\":\"porames_bua@inteqc.com\",\"manager_approved_user_id\":6,\"created_at\":\"2025-12-22 09:37:35\",\"updated_at\":\"2025-12-22 10:00:20\",\"returned_at\":null,\"returned_confirmed_by\":null,\"user_reported_return_at\":null,\"actual_return_time\":null,\"return_notes\":null,\"in_use_at\":\"2025-12-22 10:00:03\",\"user_name\":\"user\",\"user_fullname\":\"Kittipong User\",\"user_email\":\"user@example.com\",\"brand\":\"Nissan\",\"model\":\"GTR R35-Nismo\",\"license_plate\":\"GTR-35\",\"fleet_card_number\":\"FC-002\"}', '{\"id\":\"21\",\"start_time\":\"2025-12-22T09:55\",\"end_time\":\"2025-12-22T17:00\",\"assigned_car_id\":null,\"fleet_card_id\":\"2\",\"fleet_amount\":\"5000.00\",\"driver_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 10:00:29'),
(19, 6, 'ประเมศวร์ บัวศรี', 'update_booking', 'booking', 21, '{\"id\":21,\"user_id\":4,\"driver_user_id\":6,\"driver_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"driver_email\":\"porames_bua@inteqc.com\",\"approver_email\":\"porames_bua@inteqc.com\",\"approver_name\":null,\"approver_user_id\":6,\"start_time\":\"2025-12-22 09:55:00\",\"end_time\":\"2025-12-22 17:00:00\",\"destination\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"purpose\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"passengers\":1,\"passengers_detail\":\"[{\\\"user_id\\\":6,\\\"name\\\":\\\"\\\\u0e1b\\\\u0e23\\\\u0e30\\\\u0e40\\\\u0e21\\\\u0e28\\\\u0e27\\\\u0e23\\\\u0e4c \\\\u0e1a\\\\u0e31\\\\u0e27\\\\u0e28\\\\u0e23\\\\u0e35\\\",\\\"email\\\":\\\"porames_bua@inteqc.com\\\"}]\",\"passenger_user_ids\":\"[6]\",\"type\":null,\"status\":\"approved\",\"assigned_car_id\":null,\"fleet_card_id\":2,\"fleet_amount\":\"5000.00\",\"assigned_car\":null,\"assignment_note\":null,\"approval_token\":\"e46205961bd01ad0bb162788c017d738\",\"token_expires_at\":\"2025-12-29 09:37:35\",\"approved_at\":null,\"rejected_at\":null,\"rejection_reason\":null,\"rejected_by\":null,\"supervisor_approved_at\":\"2025-12-22 09:48:44\",\"supervisor_approved_by\":\"porames_bua@inteqc.com\",\"supervisor_approved_user_id\":null,\"manager_approved_at\":\"2025-12-22 09:49:22\",\"manager_approved_by\":\"porames_bua@inteqc.com\",\"manager_approved_user_id\":6,\"created_at\":\"2025-12-22 09:37:35\",\"updated_at\":\"2025-12-22 10:02:10\",\"returned_at\":null,\"returned_confirmed_by\":null,\"user_reported_return_at\":null,\"actual_return_time\":null,\"return_notes\":null,\"in_use_at\":\"2025-12-22 10:01:51\",\"user_name\":\"user\",\"user_fullname\":\"Kittipong User\",\"user_email\":\"user@example.com\",\"brand\":null,\"model\":null,\"license_plate\":null,\"fleet_card_number\":\"FC-002\"}', '{\"id\":\"21\",\"start_time\":\"2025-12-22T09:55\",\"end_time\":\"2025-12-22T17:00\",\"assigned_car_id\":\"5\",\"fleet_card_id\":null,\"fleet_amount\":null,\"driver_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 10:02:21'),
(20, 6, 'ประเมศวร์ บัวศรี', 'create_booking', 'booking', 22, NULL, '{\"destination\":\"test\",\"purpose\":\"test\",\"start_time\":\"2025-12-22T10:02\",\"end_time\":\"2025-12-22T04:02\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 10:03:02'),
(21, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve_token', 'booking', 22, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 10:03:16'),
(22, 6, 'ประเมศวร์ บัวศรี', 'manager_approve', 'booking', 22, '{\"status\":\"pending_manager\"}', '{\"status\":\"approved\",\"assigned_car_id\":\"5\",\"fleet_card_id\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 10:03:55'),
(23, 6, 'ประเมศวร์ บัวศรี', 'create_booking', 'booking', 23, NULL, '{\"destination\":\"test\",\"purpose\":\"test\",\"start_time\":\"2025-12-22T09:00\",\"end_time\":\"2025-12-22T17:00\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 10:20:17'),
(24, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve_token', 'booking', 23, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 10:20:28'),
(25, 6, 'ประเมศวร์ บัวศรี', 'reject_booking', 'booking', 22, '{\"status\":\"pending_manager\"}', '{\"status\":\"rejected_manager\",\"rejection_reason\":\"\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e23\\u0e16\\u0e27\\u0e48\\u0e32\\u0e07\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 10:21:51'),
(26, 6, 'ประเมศวร์ บัวศรี', 'confirm_return', 'booking', 21, '{\"status\":\"in_use\"}', '{\"status\":\"completed\",\"confirmed_by\":\"porames_bua@inteqc.com\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 10:23:57'),
(27, 6, 'ประเมศวร์ บัวศรี', 'manager_approve', 'booking', 22, '{\"status\":\"pending_manager\"}', '{\"status\":\"approved\",\"assigned_car_id\":\"5\",\"fleet_card_id\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 10:24:11'),
(28, 6, 'ประเมศวร์ บัวศรี', 'confirm_return', 'booking', 22, '{\"status\":\"in_use\"}', '{\"status\":\"completed\",\"confirmed_by\":\"porames_bua@inteqc.com\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 10:24:31'),
(29, 6, 'ประเมศวร์ บัวศรี', 'create_booking', 'booking', 24, NULL, '{\"destination\":\"case1\",\"purpose\":\"test\",\"start_time\":\"2025-12-24T09:00\",\"end_time\":\"2025-12-24T17:00\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 10:25:36'),
(30, 6, 'ประเมศวร์ บัวศรี', 'create_booking', 'booking', 25, NULL, '{\"destination\":\"case2\",\"purpose\":\"test\",\"start_time\":\"2025-12-24T09:00\",\"end_time\":\"2025-12-24T17:00\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 10:25:52'),
(31, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve', 'booking', 25, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 10:25:58'),
(32, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve', 'booking', 24, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 10:26:11'),
(33, 6, 'ประเมศวร์ บัวศรี', 'manager_approve', 'booking', 24, '{\"status\":\"pending_manager\"}', '{\"status\":\"approved\",\"assigned_car_id\":\"5\",\"fleet_card_id\":null}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 10:26:37'),
(34, 6, 'ประเมศวร์ บัวศรี', 'update_booking', 'booking', 24, '{\"id\":24,\"user_id\":6,\"driver_user_id\":6,\"driver_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"driver_email\":\"porames_bua@inteqc.com\",\"approver_email\":\"porames_bua@inteqc.com\",\"approver_name\":null,\"approver_user_id\":null,\"start_time\":\"2025-12-24 09:00:00\",\"end_time\":\"2025-12-24 17:00:00\",\"destination\":\"case1\",\"purpose\":\"test\",\"passengers\":0,\"passengers_detail\":null,\"passenger_user_ids\":null,\"type\":null,\"status\":\"approved\",\"assigned_car_id\":5,\"fleet_card_id\":null,\"fleet_amount\":null,\"assigned_car\":null,\"assignment_note\":null,\"approval_token\":\"349cb52b393742c410b00ac973a742c1\",\"token_expires_at\":\"2025-12-29 10:25:33\",\"approved_at\":null,\"rejected_at\":null,\"rejection_reason\":null,\"rejected_by\":null,\"supervisor_approved_at\":\"2025-12-22 10:26:11\",\"supervisor_approved_by\":\"porames_bua@inteqc.com\",\"supervisor_approved_user_id\":null,\"manager_approved_at\":\"2025-12-22 10:26:37\",\"manager_approved_by\":\"porames_bua@inteqc.com\",\"manager_approved_user_id\":6,\"created_at\":\"2025-12-22 10:25:33\",\"updated_at\":\"2025-12-22 10:26:37\",\"returned_at\":null,\"returned_confirmed_by\":null,\"user_reported_return_at\":null,\"actual_return_time\":null,\"return_notes\":null,\"in_use_at\":null,\"user_name\":\"porames.buasri\",\"user_fullname\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"user_email\":\"porames_bua@inteqc.com\",\"brand\":\"Nissan\",\"model\":\"GTR R35-Nismo\",\"license_plate\":\"GTR-35\",\"fleet_card_number\":null}', '{\"id\":\"24\",\"start_time\":\"2025-12-24T09:00\",\"end_time\":\"2025-12-24T17:00\",\"assigned_car_id\":null,\"fleet_card_id\":\"1\",\"fleet_amount\":\"500\",\"driver_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 10:27:31'),
(35, 6, 'ประเมศวร์ บัวศรี', 'reject_booking', 'booking', 23, '{\"status\":\"pending_manager\"}', '{\"status\":\"rejected_manager\",\"rejection_reason\":\"test\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 10:27:54'),
(36, 6, 'ประเมศวร์ บัวศรี', 'cancel_booking', 'booking', 25, NULL, '{\"status\":\"cancelled\",\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 10:28:18'),
(37, 6, 'ประเมศวร์ บัวศรี', 'update_booking', 'booking', 24, '{\"id\":24,\"user_id\":6,\"driver_user_id\":6,\"driver_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"driver_email\":\"porames_bua@inteqc.com\",\"approver_email\":\"porames_bua@inteqc.com\",\"approver_name\":null,\"approver_user_id\":null,\"start_time\":\"2025-12-24 09:00:00\",\"end_time\":\"2025-12-24 17:00:00\",\"destination\":\"case1\",\"purpose\":\"test\",\"passengers\":0,\"passengers_detail\":null,\"passenger_user_ids\":null,\"type\":null,\"status\":\"approved\",\"assigned_car_id\":null,\"fleet_card_id\":1,\"fleet_amount\":\"500.00\",\"assigned_car\":null,\"assignment_note\":null,\"approval_token\":\"349cb52b393742c410b00ac973a742c1\",\"token_expires_at\":\"2025-12-29 10:25:33\",\"approved_at\":null,\"rejected_at\":null,\"rejection_reason\":null,\"rejected_by\":null,\"supervisor_approved_at\":\"2025-12-22 10:26:11\",\"supervisor_approved_by\":\"porames_bua@inteqc.com\",\"supervisor_approved_user_id\":null,\"manager_approved_at\":\"2025-12-22 10:26:37\",\"manager_approved_by\":\"porames_bua@inteqc.com\",\"manager_approved_user_id\":6,\"created_at\":\"2025-12-22 10:25:33\",\"updated_at\":\"2025-12-22 10:27:31\",\"returned_at\":null,\"returned_confirmed_by\":null,\"user_reported_return_at\":null,\"actual_return_time\":null,\"return_notes\":null,\"in_use_at\":null,\"user_name\":\"porames.buasri\",\"user_fullname\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"user_email\":\"porames_bua@inteqc.com\",\"brand\":null,\"model\":null,\"license_plate\":null,\"fleet_card_number\":\"FC-001\"}', '{\"id\":\"24\",\"start_time\":\"2025-12-24T09:00\",\"end_time\":\"2025-12-24T17:00\",\"assigned_car_id\":null,\"fleet_card_id\":\"1\",\"fleet_amount\":\"500.00\",\"driver_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 11:25:46'),
(38, 6, 'ประเมศวร์ บัวศรี', 'update_settings', 'settings', 0, NULL, '{\"admin_emails\":\"porames_bua@inteqc.com\",\"cc_emails\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 11:31:12'),
(39, 4, 'Kittipong User', 'create_booking', 'booking', 26, NULL, '{\"destination\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"purpose\":\"test\",\"start_time\":\"2026-01-10T16:16\",\"end_time\":\"2026-01-10T22:16\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-22 16:16:41'),
(40, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve', 'booking', 26, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 16:16:48'),
(41, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve', 'booking', 26, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-22 16:18:09'),
(42, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve', 'booking', 26, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 16:19:04'),
(43, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve', 'booking', 26, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 16:24:23'),
(44, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve', 'booking', 26, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-22 16:32:32'),
(45, 6, 'ประเมศวร์ บัวศรี', 'create_booking', 'booking', 27, NULL, '{\"destination\":\"<script>alert(\'XSS_DESTINATION\')<\\/script>\",\"purpose\":\"<script>alert(\'XSS_PURPOSE\')<\\/script>\",\"start_time\":\"2026-01-23T09:00\",\"end_time\":\"2026-01-23T17:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 09:12:28'),
(46, 6, 'ประเมศวร์ บัวศรี', 'manager_approve', 'booking', 26, '{\"status\":\"pending_manager\"}', '{\"status\":\"approved\",\"assigned_car_id\":null,\"fleet_card_id\":\"2\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-23 09:41:54'),
(47, 4, 'Kittipong User', 'report_return', 'booking', 26, '{\"status\":\"approved\"}', '{\"status\":\"pending_return\",\"notes\":\"\\u0e02\\u0e2d\\u0e22\\u0e01\\u0e40\\u0e25\\u0e34\\u0e01\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 10:48:04'),
(48, 6, 'ประเมศวร์ บัวศรี', 'confirm_return', 'booking', 26, '{\"status\":\"pending_return\"}', '{\"status\":\"completed\",\"confirmed_by\":\"porames_bua@inteqc.com\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 10:48:51'),
(49, 6, 'ประเมศวร์ บัวศรี', 'confirm_return', 'booking', 26, '{\"status\":\"pending_return\"}', '{\"status\":\"completed\",\"confirmed_by\":\"porames_bua@inteqc.com\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 10:54:55'),
(50, 4, 'Kittipong User', 'create_booking', 'booking', 28, NULL, '{\"destination\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"purpose\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"start_time\":\"2025-12-25T09:00\",\"end_time\":\"2025-12-25T17:00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 11:02:09'),
(51, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve_token', 'booking', 28, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 11:02:47'),
(52, 6, 'ประเมศวร์ บัวศรี', 'manager_approve', 'booking', 28, '{\"status\":\"pending_manager\"}', '{\"status\":\"approved\",\"assigned_car_id\":\"5\",\"fleet_card_id\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 11:03:39'),
(53, 6, 'ประเมศวร์ บัวศรี', 'revoke_booking', 'booking', 28, '{\"status\":\"approved\"}', '{\"status\":\"revoked\",\"reason\":\"test\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 11:06:19'),
(54, 6, 'ประเมศวร์ บัวศรี', 'revoke_booking', 'booking', 24, '{\"status\":\"approved\"}', '{\"status\":\"revoked\",\"reason\":\"\\u0e22\\u0e01\\u0e40\\u0e25\\u0e34\\u0e01\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-23 11:09:22'),
(55, 6, 'ประเมศวร์ บัวศรี', 'confirm_return', 'booking', 20, '{\"status\":\"in_use\"}', '{\"status\":\"completed\",\"confirmed_by\":\"porames_bua@inteqc.com\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 13:24:17'),
(56, 6, 'ประเมศวร์ บัวศรี', 'update_fleet_card', 'fleet_card', 3, '{\"id\":3,\"card_number\":\"FC-003\",\"department\":\"Operations\",\"credit_limit\":\"0.00\",\"current_balance\":\"0.00\",\"status\":\"active\",\"notes\":\"\",\"created_at\":\"2025-12-02 15:04:25\",\"updated_at\":\"2025-12-16 10:34:14\"}', '{\"controller\":\"fleetcards\",\"action\":\"update\",\"id\":\"3\",\"card_number\":\"FC-003\",\"department\":\"Operations\",\"credit_limit\":\"0.00\",\"current_balance\":\"0.00\",\"status\":\"active\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 13:24:44'),
(57, 6, 'ประเมศวร์ บัวศรี', 'update_settings', 'settings', 0, NULL, '{\"admin_emails\":\"porames_bua@inteqc.com\",\"cc_emails\":\"\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-23 13:26:09'),
(58, 4, 'Kittipong User', 'create_booking', 'booking', 29, NULL, '{\"destination\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"purpose\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"start_time\":\"2025-12-31T09:00\",\"end_time\":\"2025-12-31T17:00\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 14:28:34'),
(59, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve', 'booking', 29, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 14:29:28'),
(60, 6, 'ประเมศวร์ บัวศรี', 'manager_approve', 'booking', 29, '{\"status\":\"pending_manager\"}', '{\"status\":\"approved\",\"assigned_car_id\":null,\"fleet_card_id\":\"2\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 14:30:23'),
(61, 4, 'Kittipong User', 'report_return', 'booking', 29, '{\"status\":\"approved\"}', '{\"status\":\"pending_return\",\"notes\":\"tttttvfdfds\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-26 14:41:27'),
(62, 6, 'ประเมศวร์ บัวศรี', 'confirm_return', 'booking', 29, '{\"status\":\"pending_return\"}', '{\"status\":\"completed\",\"confirmed_by\":\"porames_bua@inteqc.com\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 14:48:59'),
(63, 6, 'ประเมศวร์ บัวศรี', 'create_booking', 'booking', 30, NULL, '{\"destination\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"purpose\":\"\\u0e17\\u0e14\\u0e2a\\u0e2d\\u0e1a\",\"start_time\":\"2026-01-13T09:00\",\"end_time\":\"2026-01-13T17:00\"}', '192.168.130.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:12:01'),
(68, 6, 'ประเมศวร์ บัวศรี', 'supervisor_approve', 'booking', 30, '{\"status\":\"pending_supervisor\"}', '{\"status\":\"pending_manager\"}', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 07:42:22'),
(69, 6, 'ประเมศวร์ บัวศรี', 'manager_approve', 'booking', 30, '{\"status\":\"pending_manager\"}', '{\"status\":\"approved\",\"assigned_car_id\":\"7\",\"fleet_card_id\":null}', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 07:42:38'),
(70, 6, 'ประเมศวร์ บัวศรี', 'create_booking', 'booking', 31, NULL, '{\"destination\":\"\\u0e14\\u0e40\\u0e14\\u0e01\\u0e40\\u0e14\",\"purpose\":\"\\u0e40\\u0e14\\u0e01\\u0e40\\u0e14\\u0e01\\u0e40\",\"start_time\":\"2026-01-29T09:00\",\"end_time\":\"2026-01-29T17:00\"}', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 07:47:52');

-- --------------------------------------------------------

--
-- Table structure for table `cb_bookings`
--

CREATE TABLE `cb_bookings` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `driver_user_id` int UNSIGNED DEFAULT NULL,
  `driver_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approver_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `approver_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approver_user_id` int UNSIGNED DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `destination` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `purpose` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `passengers` int DEFAULT '1',
  `passengers_detail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `passenger_user_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending_supervisor','pending_manager','approved','in_use','pending_return','completed','rejected_supervisor','rejected_manager','cancelled','revoked') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_supervisor',
  `assigned_car_id` int UNSIGNED DEFAULT NULL,
  `fleet_card_id` int UNSIGNED DEFAULT NULL,
  `fleet_amount` decimal(12,2) DEFAULT NULL,
  `assigned_car` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assignment_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `approval_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rejected_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor_approved_at` datetime DEFAULT NULL,
  `supervisor_approved_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor_approved_user_id` int UNSIGNED DEFAULT NULL,
  `manager_approved_at` datetime DEFAULT NULL,
  `manager_approved_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_approved_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `returned_at` datetime DEFAULT NULL COMMENT 'เวลาที่ยืนยันคืนรถ (โดย IPCD)',
  `returned_confirmed_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ผู้ยืนยันการคืนรถ',
  `user_reported_return_at` datetime DEFAULT NULL COMMENT 'เวลาที่ผู้ยืมแจ้งคืน',
  `actual_return_time` datetime DEFAULT NULL COMMENT 'เวลาคืนจริง (ถ้าระบุ)',
  `return_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'หมายเหตุการคืนรถ',
  `in_use_at` datetime DEFAULT NULL COMMENT 'เวลาที่เปลี่ยนเป็น in_use'
) ;

--
-- Dumping data for table `cb_bookings`
--

INSERT INTO `cb_bookings` (`id`, `user_id`, `driver_user_id`, `driver_name`, `driver_email`, `approver_email`, `approver_name`, `approver_user_id`, `start_time`, `end_time`, `destination`, `purpose`, `passengers`, `passengers_detail`, `passenger_user_ids`, `type`, `status`, `assigned_car_id`, `fleet_card_id`, `fleet_amount`, `assigned_car`, `assignment_note`, `approval_token`, `token_expires_at`, `approved_at`, `rejected_at`, `rejection_reason`, `rejected_by`, `supervisor_approved_at`, `supervisor_approved_by`, `supervisor_approved_user_id`, `manager_approved_at`, `manager_approved_by`, `manager_approved_user_id`, `created_at`, `updated_at`, `returned_at`, `returned_confirmed_by`, `user_reported_return_at`, `actual_return_time`, `return_notes`, `in_use_at`) VALUES
(19, 6, NULL, 'Napat Ninprapa', 'napat_nin@inteqc.com', 'porames_bua@inteqc.com', NULL, NULL, '2025-12-15 14:05:00', '2025-12-17 14:05:00', 'test', 'test', 1, '[{\"user_id\":null,\"name\":\"Natthamon Sonklin\",\"email\":\"natthamon_son@inteqc.com\"}]', NULL, NULL, 'completed', NULL, 3, 50.00, NULL, NULL, '7eead8e1f9f88e76af7f461b7ea325dc', '2025-12-23 14:05:55', NULL, NULL, NULL, NULL, '2025-12-16 14:06:22', 'porames_bua@inteqc.com', NULL, '2025-12-16 14:08:56', 'porames_bua@inteqc.com', 6, '2025-12-16 14:05:55', '2025-12-22 08:57:33', '2025-12-22 08:57:33', 'porames_bua@inteqc.com', NULL, '2025-12-24 08:57:00', '\n[IPCD] ยางแตก', NULL),
(20, 8, 6, 'ประเมศวร์ บัวศรี', 'porames_bua@inteqc.com', 'porames_bua@inteqc.com', NULL, 6, '2025-12-17 09:00:00', '2025-12-17 17:00:00', 'ขอจอดรถพิเศษ', 'เนื่องจากต้องการจอดพิเศษ', 1, '[{\"user_id\":8,\"name\":\"Teerapon ngamakeam\",\"email\":\"teerapon.nga@mail.pbru.ac.th\"}]', '[8]', NULL, 'completed', 7, NULL, NULL, NULL, NULL, '1eeb5c736226841af36493a4af21bf35', '2025-12-24 11:30:39', NULL, NULL, NULL, NULL, '2025-12-17 11:34:44', 'porames_bua@inteqc.com', 6, '2025-12-22 08:57:55', 'porames_bua@inteqc.com', 6, '2025-12-17 11:30:39', '2025-12-23 13:24:17', '2025-12-23 13:24:17', 'porames_bua@inteqc.com', NULL, '2025-12-27 13:24:00', '', '2025-12-22 09:21:04'),
(21, 4, 6, 'ประเมศวร์ บัวศรี', 'porames_bua@inteqc.com', 'porames_bua@inteqc.com', NULL, 6, '2025-12-22 09:55:00', '2025-12-22 17:00:00', 'ทดสอบ', 'ทดสอบ', 1, '[{\"user_id\":6,\"name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"email\":\"porames_bua@inteqc.com\"}]', '[6]', NULL, 'completed', 5, NULL, NULL, NULL, NULL, 'e46205961bd01ad0bb162788c017d738', '2025-12-29 09:37:35', NULL, NULL, NULL, NULL, '2025-12-22 09:48:44', 'porames_bua@inteqc.com', NULL, '2025-12-22 09:49:22', 'porames_bua@inteqc.com', 6, '2025-12-22 09:37:35', '2025-12-22 10:23:57', '2025-12-22 10:23:57', 'porames_bua@inteqc.com', NULL, '2025-12-22 04:23:57', '', '2025-12-22 10:03:03'),
(22, 6, 6, 'ประเมศวร์ บัวศรี', 'porames_bua@inteqc.com', 'porames_bua@inteqc.com', NULL, NULL, '2025-12-22 10:02:00', '2025-12-22 04:02:00', 'test', 'test', 1, NULL, NULL, NULL, 'completed', 5, NULL, NULL, NULL, NULL, '79acf8751cc65e3d0f9a0e71d6df4f64', '2025-12-29 10:02:59', NULL, '2025-12-22 10:21:51', 'ไม่มีรถว่าง', 'porames_bua@inteqc.com', '2025-12-22 10:03:16', 'porames_bua@inteqc.com', NULL, '2025-12-22 10:24:11', 'porames_bua@inteqc.com', 6, '2025-12-22 10:02:59', '2025-12-22 10:24:31', '2025-12-22 10:24:31', 'porames_bua@inteqc.com', NULL, '2025-12-22 04:24:31', '', '2025-12-22 10:24:14'),
(23, 6, 6, 'ประเมศวร์ บัวศรี', 'porames_bua@inteqc.com', 'porames_bua@inteqc.com', NULL, NULL, '2025-12-22 09:00:00', '2025-12-22 17:00:00', 'test', 'test', 0, NULL, NULL, NULL, 'rejected_manager', NULL, NULL, NULL, NULL, NULL, '6d8806437016ac46f06df5b39a40b11b', '2025-12-29 10:20:13', NULL, '2025-12-22 10:27:54', 'test', 'porames_bua@inteqc.com', '2025-12-22 10:20:28', 'porames_bua@inteqc.com', NULL, NULL, NULL, NULL, '2025-12-22 10:20:13', '2025-12-22 10:27:54', NULL, NULL, NULL, NULL, NULL, NULL),
(24, 6, 6, 'ประเมศวร์ บัวศรี', 'porames_bua@inteqc.com', 'porames_bua@inteqc.com', NULL, NULL, '2025-12-24 09:00:00', '2025-12-24 17:00:00', 'case1', 'test', 0, NULL, NULL, 'fleet', 'revoked', NULL, 1, 500.00, NULL, NULL, '349cb52b393742c410b00ac973a742c1', '2025-12-29 10:25:33', NULL, '2025-12-23 11:09:18', 'ยกเลิก', 'porames_bua@inteqc.com', '2025-12-22 10:26:11', 'porames_bua@inteqc.com', NULL, '2025-12-22 10:26:37', 'porames_bua@inteqc.com', 6, '2025-12-22 10:25:33', '2025-12-23 11:09:18', NULL, NULL, NULL, NULL, NULL, NULL),
(25, 6, 6, 'ประเมศวร์ บัวศรี', 'porames_bua@inteqc.com', 'porames_bua@inteqc.com', NULL, NULL, '2025-12-24 09:00:00', '2025-12-24 17:00:00', 'case2', 'test', 0, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL, NULL, '7121a4b0e3bf6d18eeade13d1bf10cb0', '2025-12-29 10:25:48', NULL, NULL, '', NULL, '2025-12-22 10:25:58', 'porames_bua@inteqc.com', NULL, NULL, NULL, NULL, '2025-12-22 10:25:48', '2025-12-22 10:28:18', NULL, NULL, NULL, NULL, NULL, NULL),
(26, 4, 4, 'Kittipong User', 'user@example.com', 'porames_bua@inteqc.com', NULL, 6, '2026-01-10 16:16:00', '2026-01-10 22:16:00', 'ทดสอบ', 'test', 0, NULL, NULL, 'fleet', 'completed', NULL, 2, 500.00, NULL, NULL, 'c1b9b7e1e42997cfb3bd72fdcbfd6f22', '2025-12-29 16:16:37', NULL, NULL, NULL, NULL, '2025-12-22 16:32:32', 'porames_bua@inteqc.com', NULL, '2025-12-23 09:41:54', 'porames_bua@inteqc.com', 6, '2025-12-22 16:16:37', '2025-12-23 10:54:55', '2025-12-23 10:54:55', 'porames_bua@inteqc.com', '2025-12-23 10:48:04', '2025-12-23 04:48:04', 'ขอยกเลิก', NULL),
(28, 4, 4, 'Kittipong User', 'user@example.com', 'porames_bua@inteqc.com', NULL, 6, '2025-12-25 09:00:00', '2025-12-25 17:00:00', 'ทดสอบ', 'ทดสอบ', 0, NULL, NULL, 'car', 'revoked', 5, NULL, NULL, NULL, NULL, 'e7f4bda06e2691750b2a260f0c11d1aa', '2025-12-30 11:02:06', NULL, '2025-12-23 11:06:16', 'test', 'porames_bua@inteqc.com', '2025-12-23 11:02:47', 'porames_bua@inteqc.com', 6, '2025-12-23 11:03:39', 'porames_bua@inteqc.com', 6, '2025-12-23 11:02:06', '2025-12-23 11:06:16', NULL, NULL, NULL, NULL, NULL, NULL),
(29, 4, 4, 'Kittipong User', 'user@example.com', 'porames_bua@inteqc.com', NULL, 6, '2025-12-31 09:00:00', '2025-12-31 17:00:00', 'ทดสอบ', 'ทดสอบ', 2, '[{\"user_id\":6,\"name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"email\":\"porames_bua@inteqc.com\"},{\"user_id\":4,\"name\":\"Kittipong User\",\"email\":\"user@example.com\"}]', '[6,4]', 'fleet', 'completed', NULL, 2, 500.00, NULL, NULL, '37b7de061926bbd8c140356bcb5db5f1', '2026-01-02 14:28:30', NULL, NULL, NULL, NULL, '2025-12-26 14:29:28', 'porames_bua@inteqc.com', NULL, '2025-12-26 14:30:23', 'porames_bua@inteqc.com', 6, '2025-12-26 14:28:30', '2025-12-26 14:48:59', '2025-12-26 14:48:59', 'porames_bua@inteqc.com', '2025-12-26 14:41:27', '2025-12-26 08:41:27', 'tttttvfdfds', NULL),
(30, 6, 6, 'ประเมศวร์ บัวศรี', 'porames_bua@inteqc.com', 'porames_bua@inteqc.com', NULL, NULL, '2026-01-13 09:00:00', '2026-01-13 17:00:00', 'ทดสอบ', 'ทดสอบ', 0, NULL, NULL, 'car', 'in_use', 7, NULL, NULL, NULL, NULL, '45a5d9029022f6fe67170bc1f6d3503a', '2026-01-19 15:11:57', NULL, NULL, NULL, NULL, '2026-01-23 07:42:22', 'porames_bua@inteqc.com', NULL, '2026-01-23 07:42:38', 'porames_bua@inteqc.com', 6, '2026-01-12 15:11:57', '2026-01-23 07:42:43', NULL, NULL, NULL, NULL, NULL, '2026-01-23 07:42:43'),
(31, 6, 6, 'ประเมศวร์ บัวศรี', 'porames_bua@inteqc.com', 'porames_bua@inteqc.com', NULL, NULL, '2026-01-29 09:00:00', '2026-01-29 17:00:00', 'ดเดกเด', 'เดกเดกเ', 0, NULL, NULL, NULL, 'pending_supervisor', NULL, NULL, NULL, NULL, NULL, '2812fb73a913ed1a07e8e286263b6bad', '2026-01-30 07:47:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-23 07:47:48', '2026-01-23 07:47:48', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cb_cars`
--

CREATE TABLE `cb_cars` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `license_plate` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'sedan',
  `capacity` int DEFAULT NULL,
  `status` enum('available','maintenance','inactive','retired') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cb_cars`
--

INSERT INTO `cb_cars` (`id`, `name`, `brand`, `model`, `license_plate`, `type`, `capacity`, `status`, `created_at`, `updated_at`) VALUES
(5, 'Gotzilla', 'Nissan', 'GTR R35-Nismo', 'GTR-35', 'sedan', 4, 'available', '2025-12-01 14:55:17', '2025-12-16 10:40:35'),
(7, 'CIVIC FK', 'HONDA', 'CIVIC  FK', 'กข 1234', 'sedan', 4, 'available', '2025-12-16 10:36:25', '2025-12-16 10:38:58'),
(8, 'Supra', 'Honda', 'Commuter', 'หก1234', 'van', 2, 'maintenance', '2025-12-17 11:59:20', '2025-12-17 11:59:20');

-- --------------------------------------------------------

--
-- Table structure for table `cb_fleet_cards`
--

CREATE TABLE `cb_fleet_cards` (
  `id` int NOT NULL,
  `card_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_limit` decimal(10,2) DEFAULT NULL,
  `current_balance` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cb_fleet_cards`
--

INSERT INTO `cb_fleet_cards` (`id`, `card_number`, `department`, `credit_limit`, `current_balance`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'FC-001', 'Sales', 0.00, 0.00, 'active', '', '2025-12-02 08:04:25', '2025-12-18 04:08:46'),
(2, 'FC-002', 'Marketing', 0.00, 0.00, 'active', '', '2025-12-02 08:04:25', '2025-12-04 03:40:17'),
(3, 'FC-003', 'Operations', 0.00, 0.00, 'active', '', '2025-12-02 08:04:25', '2025-12-23 06:24:44');

-- --------------------------------------------------------

--
-- Table structure for table `core_modules`
--

CREATE TABLE `core_modules` (
  `id` int NOT NULL,
  `code` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Stable key, e.g. HR_PORTAL, CAR_BOOKING',
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Entry URL for the module',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `core_modules`
--

INSERT INTO `core_modules` (`id`, `code`, `name`, `description`, `path`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'HR_SERVICES', 'My HR Services', 'ศูนย์รวมบริการ HR', '/Modules/HRServices/', 1, '2025-11-27 06:11:36', '2025-12-23 03:43:37'),
(2, 'CAR_BOOKING', 'Car Booking', 'จองรถองค์กร', '/Modules/CarBooking/', 1, '2025-11-27 06:11:36', '2025-12-23 03:43:37'),
(3, 'PERMISSION_MANAGEMENT', 'Permission', 'การตั้งค่าสิทธิ์', '/Modules/PermissionManagement/', 1, '2025-11-27 06:24:01', '2025-12-23 03:43:37'),
(4, 'ICT_SUPPORT', 'ICT Support', 'ICT Support Sharepoint', NULL, 1, '2025-11-27 09:49:51', '2025-11-27 09:49:51'),
(5, 'IGA', 'Integrity Global Assessment', NULL, '/Modules/IGA/', 1, '2025-11-27 09:51:16', '2025-12-24 01:29:49'),
(6, 'HR_NEWS', 'HR News', 'ประกาศข่าวสาร HR สำหรับหน้า Login', '/Modules/HRNews/', 1, '2025-11-28 01:23:47', '2025-12-23 03:43:37'),
(20, 'DORMITORY', 'DOMITORY', NULL, '/Modules/Dormitory/', 1, '2025-12-05 04:47:50', '2025-12-23 03:43:37'),
(22, 'ACTIVITY_DASHBOARD', 'Activity Dashboard', 'ดูประวัติการใช้งานของผู้ใช้ในระบบ', '/Modules/ActivityLog/public/index.php', 1, '2026-01-13 04:37:41', '2026-01-13 04:37:41'),
(23, 'EMAIL_LOGS', 'Email Logs', 'ดูประวัติการส่งอีเมลในระบบ', '/Modules/EmailLogs/public/index.php', 1, '2026-01-13 04:37:41', '2026-01-13 04:37:41'),
(24, 'SCHEDULED_REPORTS', 'Scheduled Reports', 'ตั้งรายงานอัตโนมัติส่ง Email', '/Modules/ScheduledReports/public/index.php', 1, '2026-01-13 04:41:24', '2026-01-13 04:41:24'),
(26, 'YEARLY_ACTIVITY', 'Yearly Activity', NULL, 'Modules/YearlyActivity', 1, '2026-01-27 03:20:53', '2026-01-27 03:20:53');

-- --------------------------------------------------------

--
-- Table structure for table `core_module_permissions`
--

CREATE TABLE `core_module_permissions` (
  `id` int NOT NULL,
  `module_id` int NOT NULL,
  `role_id` int NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '1',
  `can_edit` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'For admin-level settings of the module',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `core_module_permissions`
--

INSERT INTO `core_module_permissions` (`id`, `module_id`, `role_id`, `can_view`, `can_edit`, `can_delete`, `can_manage`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 1, '2025-11-27 06:14:02', '2025-11-28 01:24:18'),
(4, 2, 1, 1, 1, 1, 1, '2025-11-27 06:14:35', '2025-12-05 04:50:58'),
(9, 3, 1, 1, 1, 1, 1, '2025-11-27 06:24:01', '2025-11-28 01:28:43'),
(10, 3, 3, 0, 0, 0, 0, '2025-11-27 06:24:49', '2025-12-22 07:12:24'),
(13, 1, 2, 0, 0, 0, 0, '2025-11-27 06:25:21', '2025-11-27 06:25:21'),
(26, 1, 3, 1, 0, 0, 0, '2025-11-27 07:53:03', '2025-12-22 07:12:02'),
(42, 2, 3, 1, 1, 0, 0, '2025-11-27 08:31:34', '2026-01-23 02:37:43'),
(61, 3, 2, 0, 0, 0, 0, '2025-11-27 08:45:19', '2025-11-27 08:45:21'),
(88, 6, 1, 1, 1, 1, 1, '2025-11-28 01:29:37', '2025-11-28 01:45:39'),
(92, 5, 1, 1, 1, 1, 1, '2025-11-28 01:29:40', '2025-11-28 01:29:43'),
(95, 4, 1, 1, 1, 1, 1, '2025-11-28 01:29:43', '2025-11-28 01:29:45'),
(110, 20, 1, 1, 1, 1, 1, '2025-12-05 04:50:59', '2025-12-05 04:51:01'),
(115, 4, 3, 1, 0, 0, 0, '2025-12-12 06:07:59', '2025-12-12 06:07:59'),
(116, 5, 3, 1, 0, 0, 0, '2025-12-12 06:08:00', '2025-12-12 06:08:00'),
(117, 6, 3, 1, 0, 0, 0, '2025-12-12 06:08:01', '2025-12-12 06:08:01'),
(118, 20, 3, 1, 1, 0, 0, '2025-12-12 06:08:06', '2025-12-22 07:24:24'),
(124, 1, 4, 1, 0, 0, 0, '2025-12-17 04:27:40', '2025-12-17 04:27:40'),
(125, 2, 4, 1, 1, 0, 0, '2025-12-17 04:28:16', '2025-12-23 04:27:38'),
(127, 6, 4, 1, 0, 0, 0, '2025-12-17 04:31:55', '2025-12-17 05:02:15'),
(133, 20, 4, 1, 1, 0, 0, '2025-12-17 04:40:34', '2025-12-17 05:02:18'),
(164, 1, 5, 1, 0, 0, 0, '2025-12-22 07:32:04', '2025-12-22 07:32:04'),
(165, 2, 5, 1, 1, 0, 0, '2025-12-22 07:32:22', '2025-12-26 07:25:48'),
(166, 20, 5, 1, 1, 0, 0, '2025-12-22 07:32:44', '2025-12-23 03:56:28'),
(170, 5, 4, 1, 0, 0, 0, '2025-12-24 01:53:33', '2025-12-24 01:53:33'),
(171, 5, 5, 1, 0, 0, 0, '2025-12-24 01:53:51', '2025-12-24 01:53:51'),
(176, 22, 1, 1, 1, 1, 1, '2026-01-13 04:37:54', '2026-01-23 02:03:23'),
(177, 23, 1, 1, 1, 1, 1, '2026-01-13 04:37:54', '2026-01-23 02:02:57'),
(178, 24, 1, 1, 1, 1, 1, '2026-01-13 04:48:32', '2026-01-13 05:02:09'),
(192, 26, 1, 1, 1, 1, 1, '2026-01-27 03:21:09', '2026-01-27 03:21:11'),
(196, 26, 3, 1, 1, 1, 1, '2026-01-27 08:28:57', '2026-01-27 08:29:10'),
(200, 26, 5, 1, 1, 1, 1, '2026-01-27 08:29:24', '2026-01-27 08:35:45');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_assets`
--

CREATE TABLE `dorm_assets` (
  `id` int NOT NULL,
  `room_id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `serial` varchar(150) DEFAULT NULL,
  `state` varchar(50) DEFAULT 'ok',
  `last_checked_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dorm_audit_logs`
--

CREATE TABLE `dorm_audit_logs` (
  `id` int NOT NULL,
  `user_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `dorm_audit_logs`
--

INSERT INTO `dorm_audit_logs` (`id`, `user_id`, `user_name`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, '6', 'System', 'check_in', 'occupancy', 1, NULL, '{\"room_id\":\"11\",\"employee_id\":\"1416809014\",\"employee_name\":\"Porames\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HRIS\",\"check_in_date\":\"2025-12-12\",\"notes\":\"TEST\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 04:44:10'),
(2, '6', 'System', 'save_meter', 'meter_reading', 11, NULL, '{\"room_id\":11,\"month_cycle\":\"2025-12\",\"prev_electricity\":0,\"curr_electricity\":1245,\"prev_water\":0,\"curr_water\":123}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 04:44:34'),
(3, '6', 'System', 'generate_invoices', 'invoice', NULL, NULL, '{\"month_cycle\":\"2025-12\",\"count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 04:44:39'),
(4, '6', 'System', 'save_meter', 'meter_reading', 11, NULL, '{\"room_id\":11,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":10,\"prev_water\":\"0.00\",\"curr_water\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 04:44:56'),
(5, '6', 'System', 'record_payment', 'payment', 1, NULL, '{\"invoice_id\":\"1\",\"amount\":\"10929.00\",\"payment_date\":\"2025-12-12\",\"payment_method\":\"transfer\",\"reference_number\":\"\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 04:46:21'),
(6, '6', 'System', 'save_meter', 'meter_reading', 11, NULL, '{\"room_id\":11,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":14,\"prev_water\":\"0.00\",\"curr_water\":7}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 04:47:24'),
(7, '6', 'System', 'create_maintenance', 'maintenance', 1, NULL, '{\"requester_name\":\"Porames\",\"requester_email\":\"porames_bua@inteqc.com\",\"requester_phone\":\"0644179612\",\"room_id\":\"11\",\"location_detail\":\"Toilet\",\"category_id\":\"6\",\"priority\":\"critical\",\"title\":\"test\",\"description\":\"test\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 04:48:54'),
(8, '6', 'System', 'check_in', 'occupancy', 2, NULL, '{\"room_id\":\"13\",\"employee_id\":\"1416709013\",\"employee_name\":\"Kittipong\",\"employee_email\":\"test@gmail.com\",\"department\":\"TESTER\",\"check_in_date\":\"2025-12-12\",\"notes\":\"1231232\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 06:13:42'),
(9, '6', 'System', 'check_out', 'occupancy', 2, '{\"id\":2,\"room_id\":13,\"employee_id\":\"1416709013\",\"employee_name\":\"Kittipong\",\"employee_email\":\"test@gmail.com\",\"department\":\"TESTER\",\"check_in_date\":\"2025-12-12\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"1231232\",\"created_by\":6,\"created_at\":\"2025-12-12 13:13:42\",\"updated_at\":\"2025-12-12 13:13:42\"}', '{\"check_out_date\":\"2025-12-12\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 06:14:34'),
(10, '6', 'System', 'update_maintenance_status', 'maintenance', 1, '{\"status\":\"open\"}', '{\"status\":\"assigned\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-12 06:23:50'),
(11, '6', 'System', 'update_maintenance_status', 'maintenance', 1, '{\"status\":\"assigned\"}', '{\"status\":\"in_progress\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 06:52:24'),
(12, '6', 'System', 'update_maintenance_status', 'maintenance', 1, '{\"status\":\"in_progress\"}', '{\"status\":\"closed\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 06:52:40'),
(13, '6', 'System', 'save_meter', 'meter_reading', 11, NULL, '{\"room_id\":11,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":0,\"prev_water\":\"0.00\",\"curr_water\":\"7.00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 07:08:37'),
(14, '6', 'System', 'save_meter', 'meter_reading', 11, NULL, '{\"room_id\":11,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":\"0.00\",\"prev_water\":\"0.00\",\"curr_water\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 07:08:44'),
(15, '6', 'System', 'save_meter', 'meter_reading', 11, NULL, '{\"room_id\":11,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":\"0.00\",\"prev_water\":\"0.00\",\"curr_water\":\"0.00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 08:20:02'),
(16, '6', 'System', 'save_meter', 'meter_reading', 11, NULL, '{\"room_id\":11,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":\"0.00\",\"prev_water\":\"0.00\",\"curr_water\":\"0.00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 08:20:02'),
(17, '6', 'System', 'save_meter', 'meter_reading', 11, NULL, '{\"room_id\":11,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":\"0.00\",\"prev_water\":\"0.00\",\"curr_water\":\"0.00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 08:20:03'),
(18, '6', 'System', 'save_meter', 'meter_reading', 11, NULL, '{\"room_id\":11,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":\"0.00\",\"prev_water\":\"0.00\",\"curr_water\":\"0.00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-12 08:20:03'),
(19, '6', 'System', 'update_maintenance_status', 'maintenance', 1, '{\"status\":\"closed\"}', '{\"status\":\"resolved\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-12 09:33:57'),
(20, '6', 'System', 'update_maintenance_status', 'maintenance', 1, '{\"status\":\"resolved\"}', '{\"status\":\"pending_parts\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-12 09:34:05'),
(21, '6', 'System', 'check_in', 'occupancy', 3, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"1\",\"occupant_type\":\"employee\",\"employee_id\":\"user\",\"employee_name\":\"Kittipong User\",\"employee_email\":\"user@example.com\",\"department\":\"Operations\",\"check_in_date\":\"2025-12-13\",\"notes\":\"TEST\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 04:17:26'),
(22, '6', 'System', 'check_out', 'occupancy', 1, '{\"id\":1,\"room_id\":11,\"employee_id\":\"1416809014\",\"employee_name\":\"Porames\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HRIS\",\"check_in_date\":\"2025-12-12\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"TEST\",\"created_by\":6,\"created_at\":\"2025-12-12 11:44:10\",\"updated_at\":\"2025-12-12 11:44:10\"}', '{\"check_out_date\":\"2025-12-13\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 04:26:23'),
(23, '6', 'System', 'check_in', 'occupancy', 4, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"11\",\"occupant_type\":\"employee\",\"employee_id\":\"porames.buasri\",\"employee_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HR\",\"check_in_date\":\"2025-12-13\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 04:26:33'),
(24, '6', 'System', 'check_out', 'occupancy', 3, '{\"id\":3,\"room_id\":1,\"employee_id\":\"user\",\"employee_name\":\"Kittipong User\",\"employee_email\":\"user@example.com\",\"department\":\"Operations\",\"check_in_date\":\"2025-12-13\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"TEST\",\"created_by\":6,\"created_at\":\"2025-12-13 11:17:26\",\"updated_at\":\"2025-12-13 11:17:26\"}', '{\"check_out_date\":\"2025-12-13\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 04:29:07'),
(25, '6', 'System', 'check_in', 'occupancy', 5, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"2\",\"occupant_type\":\"employee\",\"employee_id\":\"user\",\"employee_name\":\"Kittipong User\",\"employee_email\":\"user@example.com\",\"department\":\"Operations\",\"check_in_date\":\"2025-12-13\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 04:29:58'),
(26, '6', 'System', 'check_out', 'occupancy', 4, '{\"id\":4,\"room_id\":11,\"employee_id\":\"porames.buasri\",\"employee_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HR\",\"check_in_date\":\"2025-12-13\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"\",\"created_by\":6,\"created_at\":\"2025-12-13 11:26:33\",\"updated_at\":\"2025-12-13 11:26:33\"}', '{\"check_out_date\":\"2025-12-13\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 04:30:05'),
(27, '6', 'System', 'check_in', 'occupancy', 6, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"11\",\"occupant_type\":\"employee\",\"employee_id\":\"porames.buasri\",\"employee_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HR\",\"check_in_date\":\"2025-12-13\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 04:30:18'),
(28, '6', 'System', 'check_out', 'occupancy', 6, '{\"id\":6,\"room_id\":11,\"employee_id\":\"porames.buasri\",\"employee_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HR\",\"check_in_date\":\"2025-12-13\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"\",\"created_by\":6,\"created_at\":\"2025-12-13 11:30:18\",\"updated_at\":\"2025-12-13 11:30:18\"}', '{\"check_out_date\":\"2025-12-13\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 04:30:23'),
(29, '6', 'System', 'check_in', 'occupancy', 7, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"2\",\"occupant_type\":\"employee\",\"employee_id\":\"porames.buasri\",\"employee_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HR\",\"check_in_date\":\"2025-12-13\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 04:30:28'),
(30, '6', 'System', 'save_meter', 'meter_reading', 2, NULL, '{\"room_id\":2,\"month_cycle\":\"2025-12\",\"prev_electricity\":0,\"curr_electricity\":500,\"prev_water\":0,\"curr_water\":500}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 04:49:44'),
(31, '6', 'System', 'generate_invoices', 'invoice', NULL, NULL, '{\"month_cycle\":\"2025-12\",\"count\":2}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 04:49:50'),
(32, '6', 'System', 'cancel_invoice', 'invoice', 3, '{\"id\":3,\"invoice_number\":\"INV2025120003\",\"room_id\":2,\"occupancy_id\":7,\"month_cycle\":\"2025-12\",\"electricity_units\":\"500.00\",\"electricity_rate\":\"7.00\",\"electricity_amount\":\"3500.00\",\"water_units\":\"500.00\",\"water_rate\":\"18.00\",\"water_amount\":\"9000.00\",\"room_rent\":\"0.00\",\"other_charges\":\"0.00\",\"total_amount\":\"12500.00\",\"status\":\"pending\",\"due_date\":\"2025-12-28\",\"paid_date\":null,\"paid_amount\":\"0.00\",\"notes\":null,\"created_by\":6,\"created_at\":\"2025-12-13 11:49:50\",\"updated_at\":\"2025-12-13 11:49:50\"}', '{\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 04:53:26'),
(33, '6', 'System', 'cancel_invoice', 'invoice', 2, '{\"id\":2,\"invoice_number\":\"INV2025120002\",\"room_id\":2,\"occupancy_id\":5,\"month_cycle\":\"2025-12\",\"electricity_units\":\"500.00\",\"electricity_rate\":\"7.00\",\"electricity_amount\":\"3500.00\",\"water_units\":\"500.00\",\"water_rate\":\"18.00\",\"water_amount\":\"9000.00\",\"room_rent\":\"0.00\",\"other_charges\":\"0.00\",\"total_amount\":\"12500.00\",\"status\":\"pending\",\"due_date\":\"2025-12-28\",\"paid_date\":null,\"paid_amount\":\"0.00\",\"notes\":null,\"created_by\":6,\"created_at\":\"2025-12-13 11:49:50\",\"updated_at\":\"2025-12-13 11:49:50\"}', '{\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 04:53:28'),
(34, '6', 'System', 'save_meter', 'meter_reading', 2, NULL, '{\"room_id\":2,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":\"500.00\",\"prev_water\":\"0.00\",\"curr_water\":\"500.00\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 04:53:34'),
(35, '6', 'System', 'save_meter', 'meter_reading', 2, NULL, '{\"room_id\":2,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":\"500.00\",\"prev_water\":\"0.00\",\"curr_water\":\"500.00\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 04:54:03'),
(36, '6', 'System', 'generate_invoices', 'invoice', NULL, NULL, '{\"month_cycle\":\"2025-12\",\"count\":1}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 04:54:43'),
(37, '6', 'System', 'cancel_invoice', 'invoice', 4, '{\"id\":4,\"invoice_number\":\"INV2025120004\",\"room_id\":2,\"occupancy_id\":5,\"month_cycle\":\"2025-12\",\"electricity_units\":\"500.00\",\"electricity_rate\":\"7.00\",\"electricity_amount\":\"3500.00\",\"water_units\":\"500.00\",\"water_rate\":\"18.00\",\"water_amount\":\"9000.00\",\"room_rent\":\"0.00\",\"other_charges\":\"0.00\",\"total_amount\":\"12500.00\",\"status\":\"pending\",\"payment_id\":null,\"due_date\":\"2025-12-28\",\"paid_date\":null,\"paid_amount\":\"0.00\",\"notes\":null,\"created_by\":6,\"created_at\":\"2025-12-13 11:54:43\",\"updated_at\":\"2025-12-13 13:55:22\"}', '{\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 06:55:43'),
(38, '6', 'System', 'generate_invoices', 'invoice', NULL, NULL, '{\"month_cycle\":\"2025-12\",\"count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 06:55:46'),
(39, '4', 'System', 'create_maintenance', 'maintenance', 2, NULL, '{\"controller\":\"maintenance\",\"action\":\"create\",\"requester_name\":\"Kittipong User\",\"requester_email\":\"user@example.com\",\"requester_phone\":\"0644179612\",\"room_id\":\"2\",\"location_detail\":\"TEST\",\"category_id\":\"4\",\"priority\":\"medium\",\"title\":\"TEST\",\"description\":\"TEST\"}', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-12-13 07:43:15'),
(40, '6', 'System', 'update_maintenance_status', 'maintenance', 2, '{\"status\":\"open\"}', '{\"status\":\"closed\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 07:44:37'),
(41, '6', 'System', 'update_maintenance_status', 'maintenance', 1, '{\"status\":\"pending_parts\"}', '{\"status\":\"closed\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 07:44:40'),
(42, '4', 'System', 'create_maintenance', 'maintenance', 3, NULL, '{\"controller\":\"maintenance\",\"action\":\"create\",\"requester_name\":\"Kittipong User\",\"requester_email\":\"user@example.com\",\"requester_phone\":\"0644179612\",\"room_id\":\"2\",\"location_detail\":\"TEST\",\"category_id\":\"5\",\"priority\":\"low\",\"title\":\"\\u0e30\\u0e31\\u0e01\\u0e49\\u0e14\\u0e37\",\"description\":\"\\u0e17\\u0e48\\u0e01\\u0e14\\u0e2d\\u0e17\\u0e01\\u0e32\\u0e2d\\u0e01\\u0e14\\u0e2d\\u0e01\\u0e14\"}', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-12-13 07:46:30'),
(43, '6', 'System', 'check_out', 'occupancy', 5, '{\"id\":5,\"room_id\":2,\"employee_id\":\"user\",\"employee_name\":\"Kittipong User\",\"employee_email\":\"user@example.com\",\"department\":\"Operations\",\"check_in_date\":\"2025-12-13\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"\",\"created_by\":6,\"created_at\":\"2025-12-13 11:29:58\",\"updated_at\":\"2025-12-13 11:29:58\"}', '{\"check_out_date\":\"2025-12-13\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:04:05'),
(44, '6', 'System', 'check_in', 'occupancy', 8, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"8\",\"occupant_type\":\"employee\",\"employee_id\":\"user\",\"employee_name\":\"Kittipong User\",\"employee_email\":\"user@example.com\",\"department\":\"Operations\",\"check_in_date\":\"2025-12-13\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:04:47'),
(45, '6', 'System', 'save_meter', 'meter_reading', 8, NULL, '{\"room_id\":8,\"month_cycle\":\"2025-12\",\"prev_electricity\":0,\"curr_electricity\":100,\"prev_water\":0,\"curr_water\":100}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:05:13'),
(46, '6', 'System', 'generate_invoices', 'invoice', NULL, NULL, '{\"month_cycle\":\"2025-12\",\"count\":1}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:05:19'),
(47, '4', 'System', 'create_maintenance', 'maintenance', 4, NULL, '{\"controller\":\"maintenance\",\"action\":\"create\",\"requester_name\":\"Kittipong User\",\"requester_email\":\"user@example.com\",\"requester_phone\":\"0644179612\",\"room_id\":\"8\",\"location_detail\":\"\\u0e2d\\u0e40\\u0e48\\u0e40\\u0e32\\u0e48\\u0e49\\u0e49\",\"category_id\":\"4\",\"priority\":\"medium\",\"title\":\"\\u0e34\\u0e34\\u0e49\\u0e34\\u0e48\\u0e1b\\u0e41\\u0e37\\u0e37\\u0e37\\u0e01\\u0e48\\u0e17\\u0e41\\u0e34\\u0e1b\",\"description\":\"\\u0e41\\u0e1c\\u0e1b\\u0e34\\u0e41\\u0e1c\\u0e48\\u0e32\\u0e1b\\u0e41\"}', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-12-13 08:06:55'),
(48, '6', 'System', 'update_maintenance_status', 'maintenance', 1, '{\"status\":\"closed\"}', '{\"status\":\"in_progress\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:07:28'),
(49, '6', 'System', 'update_maintenance_status', 'maintenance', 1, '{\"status\":\"in_progress\"}', '{\"status\":\"closed\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:07:35'),
(50, '6', 'System', 'update_maintenance_status', 'maintenance', 3, '{\"status\":\"open\"}', '{\"status\":\"closed\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:07:53'),
(51, '6', 'System', 'update_maintenance_status', 'maintenance', 4, '{\"status\":\"open\"}', '{\"status\":\"closed\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:08:02'),
(52, '6', 'System', 'save_meter', 'meter_reading', 8, NULL, '{\"room_id\":8,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":\"100.00\",\"prev_water\":\"0.00\",\"curr_water\":\"100.00\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:15:40'),
(53, '6', 'System', 'check_out', 'occupancy', 8, '{\"id\":8,\"room_id\":8,\"employee_id\":\"user\",\"employee_name\":\"Kittipong User\",\"employee_email\":\"user@example.com\",\"department\":\"Operations\",\"check_in_date\":\"2025-12-13\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"\",\"created_by\":6,\"created_at\":\"2025-12-13 15:04:47\",\"updated_at\":\"2025-12-13 15:04:47\"}', '{\"check_out_date\":\"2025-12-13\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:18:46'),
(54, '6', 'System', 'check_in', 'occupancy', 9, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"2\",\"occupant_type\":\"employee\",\"employee_id\":\"user\",\"employee_name\":\"Kittipong User\",\"employee_email\":\"user@example.com\",\"department\":\"Operations\",\"check_in_date\":\"2025-12-13\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:18:53'),
(55, '6', 'System', 'check_out', 'occupancy', 7, '{\"id\":7,\"room_id\":2,\"employee_id\":\"porames.buasri\",\"employee_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HR\",\"check_in_date\":\"2025-12-13\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"\",\"created_by\":6,\"created_at\":\"2025-12-13 11:30:28\",\"updated_at\":\"2025-12-13 11:30:28\"}', '{\"check_out_date\":\"2025-12-13\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:19:08'),
(56, '6', 'System', 'check_in', 'occupancy', 10, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"8\",\"occupant_type\":\"employee\",\"employee_id\":\"porames.buasri\",\"employee_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HR\",\"check_in_date\":\"2025-12-13\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:19:22'),
(57, '6', 'System', 'check_out', 'occupancy', 10, '{\"id\":10,\"room_id\":8,\"employee_id\":\"porames.buasri\",\"employee_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HR\",\"check_in_date\":\"2025-12-13\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"\",\"created_by\":6,\"created_at\":\"2025-12-13 15:19:22\",\"updated_at\":\"2025-12-13 15:19:22\"}', '{\"check_out_date\":\"2025-12-13\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:19:41'),
(58, '6', 'System', 'check_out', 'occupancy', 9, '{\"id\":9,\"room_id\":2,\"employee_id\":\"user\",\"employee_name\":\"Kittipong User\",\"employee_email\":\"user@example.com\",\"department\":\"Operations\",\"check_in_date\":\"2025-12-13\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"\",\"created_by\":6,\"created_at\":\"2025-12-13 15:18:53\",\"updated_at\":\"2025-12-13 15:18:53\"}', '{\"check_out_date\":\"2025-12-13\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:22:19'),
(59, '6', 'System', 'check_in', 'occupancy', 11, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"2\",\"occupant_type\":\"employee\",\"employee_id\":\"porames.buasri\",\"employee_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HR\",\"check_in_date\":\"2025-12-13\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:22:26'),
(60, '6', 'System', 'check_in', 'occupancy', 12, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"11\",\"occupant_type\":\"employee\",\"employee_id\":\"user\",\"employee_name\":\"Kittipong User\",\"employee_email\":\"user@example.com\",\"department\":\"Operations\",\"check_in_date\":\"2025-12-13\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:22:36'),
(61, '6', 'System', 'check_out', 'occupancy', 11, '{\"id\":11,\"room_id\":2,\"employee_id\":\"porames.buasri\",\"employee_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HR\",\"check_in_date\":\"2025-12-13\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"\",\"created_by\":6,\"created_at\":\"2025-12-13 15:22:26\",\"updated_at\":\"2025-12-13 15:22:26\"}', '{\"check_out_date\":\"2025-12-13\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:24:17'),
(62, '6', 'System', 'check_in', 'occupancy', 13, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"11\",\"occupant_type\":\"employee\",\"employee_id\":\"porames.buasri\",\"employee_name\":\"\\u0e1b\\u0e23\\u0e30\\u0e40\\u0e21\\u0e28\\u0e27\\u0e23\\u0e4c \\u0e1a\\u0e31\\u0e27\\u0e28\\u0e23\\u0e35\",\"employee_email\":\"porames_bua@inteqc.com\",\"department\":\"HR\",\"check_in_date\":\"2025-12-13\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-13 08:27:41'),
(63, '6', 'System', 'save_meter', 'meter_reading', 11, NULL, '{\"room_id\":11,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":15,\"prev_water\":\"0.00\",\"curr_water\":21}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 09:29:11'),
(64, '6', 'System', 'cancel_invoice', 'invoice', 1, '{\"id\":1,\"invoice_number\":\"INV2025120001\",\"room_id\":11,\"occupancy_id\":1,\"month_cycle\":\"2025-12\",\"electricity_units\":\"1245.00\",\"electricity_rate\":\"7.00\",\"electricity_amount\":\"8715.00\",\"water_units\":\"123.00\",\"water_rate\":\"18.00\",\"water_amount\":\"2214.00\",\"room_rent\":\"0.00\",\"other_charges\":\"0.00\",\"total_amount\":\"10929.00\",\"status\":\"paid\",\"payment_id\":null,\"due_date\":\"2025-12-27\",\"paid_date\":\"2025-12-12\",\"paid_amount\":\"10929.00\",\"notes\":null,\"created_by\":6,\"created_at\":\"2025-12-12 11:44:39\",\"updated_at\":\"2025-12-12 11:46:21\"}', '{\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 09:31:02'),
(65, '6', 'System', 'save_meter', 'meter_reading', 11, NULL, '{\"room_id\":11,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":\"15.00\",\"prev_water\":\"0.00\",\"curr_water\":\"21.00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 09:31:28'),
(66, '6', 'System', 'generate_invoices', 'invoice', NULL, NULL, '{\"month_cycle\":\"2025-12\",\"count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-13 09:31:29'),
(67, '6', 'System', 'cancel_invoice', 'invoice', 7, '{\"id\":7,\"invoice_number\":\"INV2025120007\",\"room_id\":11,\"occupancy_id\":12,\"month_cycle\":\"2025-12\",\"electricity_units\":\"15.00\",\"electricity_rate\":\"7.00\",\"electricity_amount\":\"105.00\",\"water_units\":\"21.00\",\"water_rate\":\"18.00\",\"water_amount\":\"378.00\",\"room_rent\":\"0.00\",\"other_charges\":\"0.00\",\"total_amount\":\"483.00\",\"status\":\"pending\",\"payment_id\":null,\"due_date\":\"2025-12-28\",\"paid_date\":null,\"paid_amount\":\"0.00\",\"notes\":null,\"created_by\":6,\"created_at\":\"2025-12-13 16:31:29\",\"updated_at\":\"2025-12-13 16:31:29\"}', '{\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 02:23:36'),
(68, '6', 'System', 'check_out', 'occupancy', 12, '{\"id\":12,\"room_id\":11,\"employee_id\":\"user\",\"employee_name\":\"Kittipong User\",\"employee_email\":\"user@example.com\",\"department\":\"Operations\",\"check_in_date\":\"2025-12-13\",\"check_out_date\":null,\"status\":\"active\",\"notes\":\"\",\"created_by\":6,\"created_at\":\"2025-12-13 15:22:36\",\"updated_at\":\"2025-12-13 15:22:36\"}', '{\"check_out_date\":\"2025-12-17\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 02:23:43'),
(69, '4', 'System', 'create_request', 'dorm_reservation', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 02:45:30'),
(70, '6', 'System', 'approve_request', 'dorm_reservation', 2, '{\"status\":\"pending\"}', '{\"status\":\"approved\",\"room_id\":\"11\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 03:06:32'),
(71, '8', 'System', 'create_request', 'dorm_reservation', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 04:42:27'),
(72, '6', 'System', 'approve_request', 'dorm_reservation', 3, '{\"status\":\"pending\"}', '{\"status\":\"approved\",\"room_id\":\"2\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 04:44:16'),
(73, '6', 'System', 'approve_request', 'dorm_reservation', 3, '{\"status\":\"pending\"}', '{\"status\":\"approved\",\"room_id\":\"2\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 04:44:19'),
(74, '6', 'System', 'check_out', 'occupancy', 16, '{\"id\":16,\"room_id\":2,\"employee_id\":\"8\",\"employee_name\":\"Teerapon ngamakeam\",\"employee_email\":\"teerapon.nga@mail.pbru.ac.th\",\"department\":null,\"check_in_date\":\"2025-12-18\",\"check_out_date\":null,\"status\":\"active\",\"notes\":null,\"created_by\":null,\"created_at\":\"2025-12-17 11:44:16\",\"updated_at\":\"2025-12-17 11:44:16\"}', '{\"check_out_date\":\"2025-12-17\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 04:46:12'),
(75, '6', 'System', 'check_in', 'occupancy', 17, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"2\",\"occupant_type\":\"employee\",\"employee_id\":\"user\",\"employee_name\":\"Kittipong User\",\"employee_email\":\"user@example.com\",\"department\":\"Operations\",\"check_in_date\":\"2025-12-17\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 04:46:45'),
(76, '6', 'System', 'save_meter', 'meter_reading', 2, NULL, '{\"room_id\":2,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":200,\"prev_water\":\"0.00\",\"curr_water\":200}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 04:47:43'),
(77, '6', 'System', 'generate_invoices', 'invoice', NULL, NULL, '{\"month_cycle\":\"2025-12\",\"count\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 04:47:47'),
(78, '8', 'System', 'create_maintenance', 'maintenance', 5, NULL, '{\"controller\":\"maintenance\",\"action\":\"create\",\"requester_name\":\"Teerapon ngamakeam\",\"requester_email\":\"teerapon.nga@mail.pbru.ac.th\",\"requester_phone\":\"0918756723\",\"room_id\":\"2\",\"location_detail\":\"\\u0e2b\\u0e49\\u0e2d\\u0e07\\u0e19\\u0e49\\u0e33\",\"category_id\":\"5\",\"priority\":\"high\",\"title\":\"\\u0e1b\\u0e23\\u0e30\\u0e15\\u0e39\\u0e25\\u0e39\\u0e01\\u0e1a\\u0e34\\u0e14\\u0e0a\\u0e33\\u0e23\\u0e38\\u0e14\",\"description\":\"\\u0e1b\\u0e23\\u0e30\\u0e15\\u0e39\\u0e2b\\u0e49\\u0e2d\\u0e07\\u0e19\\u0e49\\u0e33\\u0e25\\u0e39\\u0e01\\u0e1a\\u0e34\\u0e14\\u0e0a\\u0e33\\u0e23\\u0e38\\u0e14 \\u0e08\\u0e33\\u0e40\\u0e1b\\u0e47\\u0e19\\u0e15\\u0e49\\u0e2d\\u0e07\\u0e0b\\u0e48\\u0e2d\\u0e21\\u0e41\\u0e0b\\u0e21\\u0e40\\u0e1e\\u0e37\\u0e48\\u0e2d\\u0e43\\u0e2b\\u0e49\\u0e44\\u0e21\\u0e48\\u0e23\\u0e1a\\u0e01\\u0e27\\u0e19\\u0e40\\u0e23\\u0e37\\u0e48\\u0e2d\\u0e07\\u0e01\\u0e25\\u0e34\\u0e48\\u0e19\\u0e41\\u0e01\\u0e48\\u0e20\\u0e32\\u0e22\\u0e19\\u0e2d\\u0e01\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 04:51:07'),
(79, '6', 'System', 'update_maintenance_status', 'maintenance', 5, '{\"status\":\"open\"}', '{\"status\":\"assigned\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 04:52:10'),
(80, '6', 'System', 'update_maintenance_status', 'maintenance', 5, '{\"status\":\"assigned\"}', '{\"status\":\"pending_parts\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 04:52:35'),
(81, '6', 'System', 'update_maintenance_status', 'maintenance', 5, '{\"status\":\"pending_parts\"}', '{\"status\":\"resolved\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 04:52:55'),
(82, '6', 'System', 'update_maintenance_status', 'maintenance', 5, '{\"status\":\"resolved\"}', '{\"status\":\"closed\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-17 04:53:14'),
(83, '4', 'System', 'create_maintenance', 'maintenance', 6, NULL, '{\"controller\":\"maintenance\",\"action\":\"create\",\"requester_name\":\"Kittipong User\",\"requester_email\":\"user@example.com\",\"room_id\":\"2\",\"category_id\":\"6\",\"priority\":\"medium\",\"title\":\"test\",\"description\":\"test\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 03:56:28'),
(84, '4', 'System', 'create_request', 'dorm_reservation', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36 Edg/143.0.0.0', '2025-12-25 02:43:58'),
(85, '6', 'System', 'update_room', 'room', 15, '{\"id\":15,\"building_id\":1,\"room_number\":\"A303\",\"floor\":3,\"room_type\":\"single\",\"capacity\":1,\"monthly_rent\":\"0.00\",\"status\":\"available\",\"description\":null,\"created_at\":\"2025-12-12 11:36:22\",\"updated_at\":\"2025-12-12 11:36:22\"}', '{\"controller\":\"rooms\",\"action\":\"update\",\"id\":\"15\",\"floor\":\"3\",\"room_number\":\"A303\",\"room_type\":\"family\",\"capacity\":\"4\",\"status\":\"available\",\"monthly_rent\":\"0.00\",\"description\":\"\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-25 02:51:51'),
(86, '6', 'System', 'check_out', 'occupancy', 18, NULL, '{\"check_out_date\":\"2025-12-25\"}', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-25 02:53:59'),
(87, '6', 'System', 'check_out', 'occupancy', 19, NULL, '{\"check_out_date\":\"2025-12-25\"}', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-25 02:55:23'),
(88, '6', 'System', 'update_room', 'room', 2, '{\"id\":2,\"building_id\":1,\"room_number\":\"A102\",\"floor\":1,\"room_type\":\"double\",\"capacity\":2,\"monthly_rent\":\"0.00\",\"status\":\"occupied\",\"description\":null,\"created_at\":\"2025-12-12 11:36:22\",\"updated_at\":\"2025-12-17 11:44:12\"}', '{\"controller\":\"rooms\",\"action\":\"update\",\"id\":\"2\",\"floor\":\"1\",\"room_number\":\"A102\",\"room_type\":\"double\",\"capacity\":\"2\",\"status\":\"available\",\"monthly_rent\":\"0.00\",\"description\":\"\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-25 03:22:46'),
(89, '6', 'System', 'check_out', 'occupancy', 20, NULL, '{\"check_out_date\":\"2025-12-25\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-25 03:23:10'),
(90, '6', 'System', 'check_out', 'occupancy', 21, NULL, '{\"check_out_date\":\"2025-12-25\"}', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-25 03:24:52'),
(91, '6', 'System', 'reject_request', 'dorm_reservation', 4, '{\"status\":\"pending\"}', '{\"status\":\"rejected\",\"reason\":\"fdsf\"}', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-25 03:28:35'),
(92, '6', 'System', 'check_in', 'occupancy', 23, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"21\",\"occupant_type\":\"temporary\",\"employee_id\":\"TEMP_1766636135209\",\"employee_name\":\"\\u0e04\\u0e19\\u0e19\\u0e2d\\u0e01\",\"employee_email\":\"\",\"department\":\"\",\"check_in_date\":\"2025-12-25\",\"notes\":\"\\n[\\u0e02\\u0e49\\u0e2d\\u0e21\\u0e39\\u0e25\\u0e1c\\u0e39\\u0e49\\u0e1e\\u0e31\\u0e01\\u0e0a\\u0e31\\u0e48\\u0e27\\u0e04\\u0e23\\u0e32\\u0e27]\\n\\u0e42\\u0e17\\u0e23: 043943\\n\\u0e2b\\u0e19\\u0e48\\u0e27\\u0e22\\u0e07\\u0e32\\u0e19: dsfdf\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-25 04:15:35'),
(93, '6', 'System', 'check_in', 'occupancy', 24, NULL, '{\"controller\":\"rooms\",\"action\":\"checkIn\",\"room_id\":\"15\",\"occupant_type\":\"employee\",\"employee_id\":\"teerapon\",\"employee_name\":\"Teerapon ngamakeam\",\"employee_email\":\"teerapon.nga@mail.pbru.ac.th\",\"department\":\"\",\"check_in_date\":\"2025-12-25\",\"notes\":\"\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-25 07:29:04'),
(94, '6', 'System', 'save_meter', 'meter_reading', 21, NULL, '{\"room_id\":21,\"month_cycle\":\"2025-12\",\"prev_electricity\":0,\"curr_electricity\":20,\"prev_water\":0,\"curr_water\":20}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 07:56:12'),
(95, '6', 'System', 'save_meter', 'meter_reading', 15, NULL, '{\"room_id\":15,\"month_cycle\":\"2025-12\",\"prev_electricity\":0,\"curr_electricity\":54,\"prev_water\":0,\"curr_water\":54}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 07:56:17'),
(96, '6', 'System', 'save_meter', 'meter_reading', 21, NULL, '{\"room_id\":21,\"month_cycle\":\"2025-12\",\"prev_electricity\":\"0.00\",\"curr_electricity\":\"20.00\",\"prev_water\":\"0.00\",\"curr_water\":\"20.00\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 07:56:17'),
(97, '6', 'System', 'generate_invoices', 'invoice', NULL, NULL, '{\"month_cycle\":\"2025-12\",\"count\":2}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 07:56:19'),
(98, '6', 'System', 'check_out', 'occupancy', 24, NULL, '{\"check_out_date\":\"2025-12-26\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 08:09:15');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_buildings`
--

CREATE TABLE `dorm_buildings` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `total_floors` int DEFAULT '1',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dorm_buildings`
--

INSERT INTO `dorm_buildings` (`id`, `name`, `code`, `description`, `address`, `total_floors`, `status`, `created_at`, `updated_at`) VALUES
(1, 'อาคาร A', 'A', 'อาคารหอพักพนักงาน A', NULL, 4, 'active', '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(2, 'อาคาร B', 'B', 'อาคารหอพักพนักงาน B', NULL, 4, 'active', '2025-12-12 04:36:22', '2025-12-12 04:36:22');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_employee_types`
--

CREATE TABLE `dorm_employee_types` (
  `id` int NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `discount_percent` decimal(5,2) DEFAULT '0.00',
  `privileges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `dorm_floors`
--

CREATE TABLE `dorm_floors` (
  `id` int NOT NULL,
  `building_id` int NOT NULL,
  `level` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dorm_floors`
--

INSERT INTO `dorm_floors` (`id`, `building_id`, `level`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '1', 'active', '2025-12-05 07:58:21', '2025-12-05 07:58:21'),
(2, 1, 2, '2', 'active', '2025-12-05 08:12:32', '2025-12-05 08:12:32'),
(3, 1, 3, '3', 'active', '2025-12-08 01:51:48', '2025-12-08 01:51:48');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_invoices`
--

CREATE TABLE `dorm_invoices` (
  `id` int NOT NULL,
  `invoice_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `room_id` int NOT NULL,
  `occupancy_id` int NOT NULL,
  `month_cycle` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `electricity_units` decimal(10,2) DEFAULT '0.00',
  `electricity_rate` decimal(10,2) DEFAULT '0.00',
  `electricity_amount` decimal(10,2) DEFAULT '0.00',
  `water_units` decimal(10,2) DEFAULT '0.00',
  `water_rate` decimal(10,2) DEFAULT '0.00',
  `water_amount` decimal(10,2) DEFAULT '0.00',
  `room_rent` decimal(10,2) DEFAULT '0.00',
  `other_charges` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_id` int DEFAULT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dorm_invoices`
--

INSERT INTO `dorm_invoices` (`id`, `invoice_number`, `room_id`, `occupancy_id`, `month_cycle`, `electricity_units`, `electricity_rate`, `electricity_amount`, `water_units`, `water_rate`, `water_amount`, `room_rent`, `other_charges`, `total_amount`, `status`, `payment_id`, `due_date`, `paid_date`, `paid_amount`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(8, 'INV2025120001', 2, 15, '2025-12', 200.00, 7.00, 1400.00, 200.00, 18.00, 3600.00, 0.00, 0.00, 5000.00, 'paid', 13, '2026-01-01', NULL, 5000.00, NULL, 6, '2025-12-17 04:47:47', '2025-12-17 04:48:55'),
(9, 'INV2025120002', 21, 23, '2025-12', 20.00, 7.00, 140.00, 20.00, 18.00, 360.00, 0.00, 0.00, 500.00, 'pending', NULL, '2026-01-10', NULL, 0.00, NULL, 6, '2025-12-26 07:56:19', '2025-12-26 07:56:19'),
(10, 'INV2025120003', 15, 22, '2025-12', 54.00, 7.00, 378.00, 54.00, 18.00, 972.00, 0.00, 0.00, 1350.00, 'pending', NULL, '2026-01-10', NULL, 0.00, NULL, 6, '2025-12-26 07:56:19', '2025-12-26 07:56:19');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_invoice_items`
--

CREATE TABLE `dorm_invoice_items` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `item_type` enum('electricity','water','room','service','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `unit_price` decimal(10,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dorm_invoice_items`
--

INSERT INTO `dorm_invoice_items` (`id`, `invoice_id`, `item_type`, `description`, `quantity`, `unit_price`, `amount`, `created_at`) VALUES
(15, 8, 'electricity', 'ค่าไฟฟ้า', 200.00, 7.00, 1400.00, '2025-12-17 04:47:47'),
(16, 8, 'water', 'ค่าน้ำประปา', 200.00, 18.00, 3600.00, '2025-12-17 04:47:47'),
(17, 9, 'electricity', 'ค่าไฟฟ้า', 20.00, 7.00, 140.00, '2025-12-26 07:56:19'),
(18, 9, 'water', 'ค่าน้ำประปา', 20.00, 18.00, 360.00, '2025-12-26 07:56:19'),
(19, 10, 'electricity', 'ค่าไฟฟ้า', 54.00, 7.00, 378.00, '2025-12-26 07:56:19'),
(20, 10, 'water', 'ค่าน้ำประปา', 54.00, 18.00, 972.00, '2025-12-26 07:56:19');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_maintenance_categories`
--

CREATE TABLE `dorm_maintenance_categories` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `priority_level` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dorm_maintenance_categories`
--

INSERT INTO `dorm_maintenance_categories` (`id`, `name`, `description`, `priority_level`, `icon`, `status`, `created_at`) VALUES
(1, 'ไฟฟ้า', 'ปัญหาเกี่ยวกับระบบไฟฟ้า ปลั๊ก สวิตช์', 'high', 'bolt', 'active', '2025-12-12 04:36:22'),
(2, 'ประปา', 'ปัญหาเกี่ยวกับน้ำ ท่อตัน ก๊อกน้ำ', 'high', 'droplet', 'active', '2025-12-12 04:36:22'),
(3, 'แอร์', 'ปัญหาเกี่ยวกับเครื่องปรับอากาศ', 'medium', 'snowflake', 'active', '2025-12-12 04:36:22'),
(4, 'เฟอร์นิเจอร์', 'ปัญหาเกี่ยวกับเฟอร์นิเจอร์ เตียง ตู้', 'low', 'couch', 'active', '2025-12-12 04:36:22'),
(5, 'ประตู/หน้าต่าง', 'ปัญหาเกี่ยวกับประตู หน้าต่าง กุญแจ', 'medium', 'door-open', 'active', '2025-12-12 04:36:22'),
(6, 'ทั่วไป', 'ปัญหาอื่นๆ ทั่วไป', 'low', 'tools', 'active', '2025-12-12 04:36:22');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_maintenance_requests`
--

CREATE TABLE `dorm_maintenance_requests` (
  `id` int NOT NULL,
  `ticket_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `room_id` int DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `requester_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requester_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `requester_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requester_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_detail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `status` enum('open','assigned','in_progress','pending_parts','resolved','closed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `assigned_to` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dorm_maintenance_requests`
--

INSERT INTO `dorm_maintenance_requests` (`id`, `ticket_number`, `room_id`, `category_id`, `requester_id`, `requester_name`, `requester_email`, `requester_phone`, `title`, `description`, `location_detail`, `priority`, `status`, `assigned_to`, `estimated_cost`, `actual_cost`, `resolved_at`, `closed_at`, `created_at`, `updated_at`) VALUES
(1, 'MT2025120001', 11, 6, '6', 'Porames', 'porames_bua@inteqc.com', '0644179612', 'test', 'test', 'Toilet', 'critical', 'closed', 'grgret', NULL, NULL, '2025-12-12 09:33:57', '2025-12-13 08:07:35', '2025-12-12 04:48:54', '2025-12-13 08:07:35'),
(2, 'MT2025120002', 2, 4, '4', 'Kittipong User', 'user@example.com', '0644179612', 'TEST', 'TEST', 'TEST', 'medium', 'closed', NULL, NULL, NULL, NULL, '2025-12-13 07:44:37', '2025-12-13 07:43:15', '2025-12-13 07:44:37'),
(3, 'MT2025120003', 2, 5, '4', 'Kittipong User', 'user@example.com', '0644179612', 'ะัก้ดื', 'ท่กดอทกาอกดอกด', 'TEST', 'low', 'closed', NULL, NULL, NULL, NULL, '2025-12-13 08:07:53', '2025-12-13 07:46:30', '2025-12-13 08:07:53'),
(4, 'MT2025120004', 8, 4, '4', 'Kittipong User', 'user@example.com', '0644179612', 'ิิ้ิ่ปแืืืก่ทแิป', 'แผปิแผ่าปแ', 'อเ่เา่้้', 'medium', 'closed', NULL, NULL, NULL, NULL, '2025-12-13 08:08:02', '2025-12-13 08:06:55', '2025-12-13 08:08:02'),
(5, 'MT2025120005', 2, 5, '8', 'Teerapon ngamakeam', 'teerapon.nga@mail.pbru.ac.th', '0918756723', 'ประตูลูกบิดชำรุด', 'ประตูห้องน้ำลูกบิดชำรุด จำเป็นต้องซ่อมแซมเพื่อให้ไม่รบกวนเรื่องกลิ่นแก่ภายนอก', 'ห้องน้ำ', 'high', 'closed', 'TEST', NULL, NULL, '2025-12-17 04:52:55', '2025-12-17 04:53:14', '2025-12-17 04:51:07', '2025-12-17 04:53:14'),
(6, 'MT2025120006', 2, 6, '4', 'Kittipong User', 'user@example.com', NULL, 'test', 'test', NULL, 'medium', 'open', NULL, NULL, NULL, NULL, NULL, '2025-12-23 03:56:28', '2025-12-23 03:56:28');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_maintenance_updates`
--

CREATE TABLE `dorm_maintenance_updates` (
  `id` int NOT NULL,
  `request_id` int NOT NULL,
  `update_type` enum('status_change','comment','assignment','cost_update') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'comment',
  `status_from` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_to` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dorm_maintenance_updates`
--

INSERT INTO `dorm_maintenance_updates` (`id`, `request_id`, `update_type`, `status_from`, `status_to`, `comment`, `updated_by`, `created_at`) VALUES
(1, 1, 'status_change', NULL, 'open', 'สร้างคำขอแจ้งซ่อมใหม่', 'Porames', '2025-12-12 04:48:54'),
(2, 1, 'status_change', 'open', 'assigned', '', 'System', '2025-12-12 06:23:50'),
(3, 1, 'status_change', 'assigned', 'in_progress', '', 'System', '2025-12-12 06:52:24'),
(4, 1, 'status_change', 'in_progress', 'closed', '', 'System', '2025-12-12 06:52:40'),
(5, 1, 'status_change', 'closed', 'resolved', '', 'System', '2025-12-12 09:33:57'),
(6, 1, 'status_change', 'resolved', 'pending_parts', '', 'System', '2025-12-12 09:34:05'),
(7, 2, 'status_change', NULL, 'open', 'สร้างคำขอแจ้งซ่อมใหม่', 'Kittipong User', '2025-12-13 07:43:15'),
(8, 2, 'status_change', 'open', 'closed', '', 'System', '2025-12-13 07:44:37'),
(9, 1, 'status_change', 'pending_parts', 'closed', '', 'System', '2025-12-13 07:44:40'),
(10, 3, 'status_change', NULL, 'open', 'สร้างคำขอแจ้งซ่อมใหม่', 'Kittipong User', '2025-12-13 07:46:30'),
(11, 4, 'status_change', NULL, 'open', 'สร้างคำขอแจ้งซ่อมใหม่', 'Kittipong User', '2025-12-13 08:06:55'),
(12, 1, 'status_change', 'closed', 'in_progress', '', 'System', '2025-12-13 08:07:28'),
(13, 1, 'status_change', 'in_progress', 'closed', '', 'System', '2025-12-13 08:07:35'),
(14, 3, 'status_change', 'open', 'closed', '', 'System', '2025-12-13 08:07:53'),
(15, 4, 'status_change', 'open', 'closed', '', 'System', '2025-12-13 08:08:02'),
(16, 5, 'status_change', NULL, 'open', 'สร้างคำขอแจ้งซ่อมใหม่', 'Teerapon ngamakeam', '2025-12-17 04:51:07'),
(17, 5, 'status_change', 'open', 'assigned', '', 'System', '2025-12-17 04:52:10'),
(18, 5, 'status_change', 'assigned', 'pending_parts', '', 'System', '2025-12-17 04:52:35'),
(19, 5, 'status_change', 'pending_parts', 'resolved', '', 'System', '2025-12-17 04:52:55'),
(20, 5, 'status_change', 'resolved', 'closed', '', 'System', '2025-12-17 04:53:14'),
(21, 6, 'status_change', NULL, 'open', 'สร้างคำขอแจ้งซ่อมใหม่', 'Kittipong User', '2025-12-23 03:56:28');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_meter_readings`
--

CREATE TABLE `dorm_meter_readings` (
  `id` int NOT NULL,
  `room_id` int NOT NULL,
  `reading_date` date NOT NULL,
  `month_cycle` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prev_electricity` decimal(10,2) DEFAULT '0.00',
  `curr_electricity` decimal(10,2) DEFAULT '0.00',
  `electricity_usage` decimal(10,2) GENERATED ALWAYS AS ((`curr_electricity` - `prev_electricity`)) STORED,
  `prev_water` decimal(10,2) DEFAULT '0.00',
  `curr_water` decimal(10,2) DEFAULT '0.00',
  `water_usage` decimal(10,2) GENERATED ALWAYS AS ((`curr_water` - `prev_water`)) STORED,
  `recorded_by` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dorm_meter_readings`
--

INSERT INTO `dorm_meter_readings` (`id`, `room_id`, `reading_date`, `month_cycle`, `prev_electricity`, `curr_electricity`, `prev_water`, `curr_water`, `recorded_by`, `notes`, `created_at`) VALUES
(1, 11, '2025-12-13', '2025-12', 0.00, 15.00, 0.00, 21.00, 6, NULL, '2025-12-12 04:44:34'),
(10, 2, '2025-12-17', '2025-12', 0.00, 200.00, 0.00, 200.00, 6, NULL, '2025-12-13 04:49:44'),
(13, 8, '2025-12-13', '2025-12', 0.00, 100.00, 0.00, 100.00, 6, NULL, '2025-12-13 08:05:13'),
(18, 21, '2025-12-26', '2025-12', 0.00, 20.00, 0.00, 20.00, 6, NULL, '2025-12-26 07:56:12'),
(19, 15, '2025-12-26', '2025-12', 0.00, 54.00, 0.00, 54.00, 6, NULL, '2025-12-26 07:56:17');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_occupancies`
--

CREATE TABLE `dorm_occupancies` (
  `id` int NOT NULL,
  `room_id` int NOT NULL,
  `employee_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date DEFAULT NULL,
  `status` enum('active','checked_out','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `accompanying_persons` int DEFAULT '0',
  `accompanying_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
) ;

--
-- Dumping data for table `dorm_occupancies`
--

INSERT INTO `dorm_occupancies` (`id`, `room_id`, `employee_id`, `employee_name`, `employee_email`, `department`, `check_in_date`, `check_out_date`, `status`, `notes`, `created_by`, `created_at`, `updated_at`, `accompanying_persons`, `accompanying_details`) VALUES
(15, 2, '8', 'Teerapon ngamakeam', 'teerapon.nga@mail.pbru.ac.th', NULL, '2025-12-18', '2025-12-25', 'checked_out', NULL, NULL, '2025-12-17 04:44:12', '2025-12-25 02:42:57', 0, NULL),
(16, 2, '8', 'Teerapon ngamakeam', 'teerapon.nga@mail.pbru.ac.th', NULL, '2025-12-18', '2025-12-17', 'checked_out', NULL, NULL, '2025-12-17 04:44:16', '2025-12-17 04:46:12', 0, NULL),
(17, 2, 'user', 'Kittipong User', 'user@example.com', 'Operations', '2025-12-17', '2025-12-25', 'checked_out', '', 6, '2025-12-17 04:46:45', '2025-12-25 02:42:44', 0, NULL),
(18, 15, '4', 'Kittipong User', 'user@example.com', NULL, '2025-12-25', '2025-12-25', 'checked_out', NULL, 6, '2025-12-25 02:52:03', '2025-12-25 02:53:59', 2, '[{\"name\":\"ทดสอบ\",\"age\":30,\"relation\":\"ทดสอบ\"},{\"name\":\"ทดสอบ\",\"age\":35,\"relation\":\"ทดสอบ\"}]'),
(19, 15, '4', 'Kittipong User', 'user@example.com', NULL, '2025-12-25', '2025-12-25', 'checked_out', NULL, 6, '2025-12-25 02:54:07', '2025-12-25 02:55:23', 2, '[{\"name\":\"ทดสอบ\",\"age\":30,\"relation\":\"ทดสอบ\"},{\"name\":\"ทดสอบ\",\"age\":35,\"relation\":\"ทดสอบ\"}]'),
(20, 15, '4', 'Kittipong User', 'user@example.com', NULL, '2025-12-25', '2025-12-25', 'checked_out', NULL, 6, '2025-12-25 02:56:14', '2025-12-25 03:23:10', 2, '[{\"name\":\"ทดสอบ\",\"age\":30,\"relation\":\"ทดสอบ\"},{\"name\":\"ทดสอบ\",\"age\":35,\"relation\":\"ทดสอบ\"}]'),
(21, 15, '4', 'Kittipong User', 'user@example.com', NULL, '2025-12-25', '2025-12-25', 'checked_out', NULL, 6, '2025-12-25 03:23:16', '2025-12-25 03:24:52', 2, '[{\"name\":\"ทดสอบ\",\"age\":30,\"relation\":\"ทดสอบ\"},{\"name\":\"ทดสอบ\",\"age\":35,\"relation\":\"ทดสอบ\"}]'),
(22, 15, '4', 'Kittipong User', 'user@example.com', NULL, '2025-12-25', NULL, 'active', NULL, 6, '2025-12-25 03:25:04', '2025-12-25 03:25:04', 2, '[{\"name\":\"ทดสอบ\",\"age\":30,\"relation\":\"ทดสอบ\"},{\"name\":\"ทดสอบ\",\"age\":35,\"relation\":\"ทดสอบ\"}]'),
(23, 21, 'TEMP_1766636135209', 'คนนอก', '', '', '2025-12-25', NULL, 'active', '\n[ข้อมูลผู้พักชั่วคราว]\nโทร: 043943\nหน่วยงาน: dsfdf', 6, '2025-12-25 04:15:35', '2025-12-25 04:15:35', 0, NULL),
(24, 15, 'teerapon', 'Teerapon ngamakeam', 'teerapon.nga@mail.pbru.ac.th', '', '2025-12-25', '2025-12-26', 'checked_out', '', 6, '2025-12-25 07:29:04', '2025-12-26 08:09:15', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dorm_payments`
--

CREATE TABLE `dorm_payments` (
  `id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_time` time DEFAULT NULL,
  `proof_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `paid_by` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','transfer','payroll_deduct','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'transfer',
  `reference_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dorm_payments`
--

INSERT INTO `dorm_payments` (`id`, `total_amount`, `payment_date`, `payment_time`, `proof_file`, `status`, `paid_by`, `remark`, `amount`, `payment_method`, `reference_number`, `receipt_number`, `notes`, `recorded_by`, `created_at`, `updated_at`) VALUES
(1, 0.00, '2025-12-12', NULL, NULL, 'pending', NULL, NULL, 10929.00, 'transfer', '', NULL, '', 6, '2025-12-12 04:46:21', '2025-12-13 06:21:50'),
(4, 12500.00, '2025-12-13', '13:20:00', '2025/12/slip_693d065d54d6b.png', 'rejected', '4', '', 0.00, 'transfer', NULL, NULL, NULL, NULL, '2025-12-13 06:23:25', '2025-12-24 09:12:35'),
(5, 12500.00, '2025-12-13', '13:56:00', '2025/12/slip_693d0e067b2c3.png', 'approved', '4', '', 0.00, 'transfer', NULL, NULL, NULL, NULL, '2025-12-13 06:56:06', '2025-12-24 09:12:35'),
(6, 2500.00, '2025-12-13', '15:05:00', '2025/12/slip_693d1e603dae8.png', 'approved', '4', '', 0.00, 'transfer', NULL, NULL, NULL, NULL, '2025-12-13 08:05:52', '2025-12-24 09:12:35'),
(7, 5000.00, '2025-12-20', '23:48:00', '2025/12/slip_69423616e6885.png', 'pending', '8', NULL, 0.00, 'transfer', NULL, NULL, NULL, NULL, '2025-12-17 04:48:22', '2025-12-24 09:12:35'),
(8, 5000.00, '2025-12-20', '23:48:00', '2025/12/slip_6942361a85d6b.png', 'pending', '8', NULL, 0.00, 'transfer', NULL, NULL, NULL, NULL, '2025-12-17 04:48:26', '2025-12-24 09:12:35'),
(9, 5000.00, '2025-12-20', '23:48:00', '2025/12/slip_6942361ae7405.png', 'pending', '8', NULL, 0.00, 'transfer', NULL, NULL, NULL, NULL, '2025-12-17 04:48:26', '2025-12-24 09:12:35'),
(10, 5000.00, '2025-12-20', '23:48:00', '2025/12/slip_6942361b01fd8.png', 'pending', '8', NULL, 0.00, 'transfer', NULL, NULL, NULL, NULL, '2025-12-17 04:48:27', '2025-12-24 09:12:35'),
(11, 5000.00, '2025-12-20', '23:48:00', '2025/12/slip_6942361b0b9cf.png', 'pending', '8', NULL, 0.00, 'transfer', NULL, NULL, NULL, NULL, '2025-12-17 04:48:27', '2025-12-24 09:12:35'),
(12, 5000.00, '2025-12-20', '23:48:00', '2025/12/slip_6942361b3a2ea.png', 'pending', '8', NULL, 0.00, 'transfer', NULL, NULL, NULL, NULL, '2025-12-17 04:48:27', '2025-12-24 09:12:35'),
(13, 5000.00, '2025-12-20', '23:48:00', '2025/12/slip_6942361b3e30a.png', 'approved', '8', '', 0.00, 'transfer', NULL, NULL, NULL, NULL, '2025-12-17 04:48:27', '2025-12-24 09:12:35');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_rate_rules`
--

CREATE TABLE `dorm_rate_rules` (
  `id` int NOT NULL,
  `employee_type_id` int NOT NULL,
  `room_type_id` int NOT NULL,
  `price_month` decimal(10,2) DEFAULT '0.00',
  `price_night` decimal(10,2) DEFAULT '0.00',
  `discount_percent` decimal(5,2) DEFAULT '0.00',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dorm_reservations`
--

CREATE TABLE `dorm_reservations` (
  `id` int NOT NULL,
  `request_type` enum('move_in','move_out','change_room') NOT NULL DEFAULT 'move_in',
  `requester_id` int NOT NULL,
  `room_id` int DEFAULT NULL,
  `room_type_preference` int DEFAULT NULL,
  `current_room_id` int DEFAULT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `reason` text,
  `has_relative` tinyint(1) DEFAULT '0',
  `relative_details` longtext,
  `document_paths` longtext,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `approver_id` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `key_pickup_date` datetime DEFAULT NULL,
  `cancel_reason` text,
  `admin_remark` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dorm_reservations`
--

INSERT INTO `dorm_reservations` (`id`, `request_type`, `requester_id`, `room_id`, `room_type_preference`, `current_room_id`, `check_in`, `check_out`, `reason`, `has_relative`, `relative_details`, `document_paths`, `status`, `approver_id`, `approved_at`, `key_pickup_date`, `cancel_reason`, `admin_remark`, `created_at`, `updated_at`) VALUES
(3, 'move_in', 8, 2, 1, NULL, '2025-12-17', '0000-00-00', 'เนื่องจากอยู่ต่างจังหวัดจำเป็นต้องขอเข้าหอพักเพื่อพักอาศัย', 0, NULL, '[]', 'approved', 6, '2025-12-17 11:44:16', '2025-12-18 11:43:00', NULL, '', '2025-12-17 04:42:27', '2025-12-17 04:44:16'),
(4, 'move_in', 4, NULL, 2, NULL, '2025-12-26', '0000-00-00', 'ทดสอบ', 1, '[{\"name\":\"ทดสอบ\",\"age\":30,\"relation\":\"ทดสอบ\"},{\"name\":\"ทดสอบ\",\"age\":35,\"relation\":\"ทดสอบ\"}]', '[]', 'rejected', 6, '2025-12-25 10:28:31', NULL, 'fdsf', NULL, '2025-12-25 02:43:58', '2025-12-25 03:28:31');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_rooms`
--

CREATE TABLE `dorm_rooms` (
  `id` int NOT NULL,
  `building_id` int NOT NULL,
  `room_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `floor` int DEFAULT '1',
  `room_type` enum('single','double','suite') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'single',
  `capacity` int DEFAULT '1',
  `monthly_rent` decimal(10,2) DEFAULT '0.00',
  `status` enum('available','occupied','maintenance','reserved') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dorm_rooms`
--

INSERT INTO `dorm_rooms` (`id`, `building_id`, `room_number`, `floor`, `room_type`, `capacity`, `monthly_rent`, `status`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'A101', 1, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-13 04:29:07'),
(2, 1, 'A102', 1, 'double', 2, 0.00, 'available', '', '2025-12-12 04:36:22', '2025-12-25 03:22:46'),
(3, 1, 'A103', 1, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(4, 1, 'A104', 1, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(5, 1, 'A105', 1, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(6, 1, 'A106', 1, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(7, 1, 'A201', 2, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(8, 1, 'A202', 2, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-13 08:19:41'),
(9, 1, 'A203', 2, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(10, 1, 'A204', 2, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(11, 1, 'A205', 2, 'double', 2, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-17 03:28:52'),
(12, 1, 'A206', 2, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(13, 1, 'A301', 3, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 06:14:34'),
(14, 1, 'A302', 3, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(15, 1, 'A303', 3, '', 4, 0.00, 'occupied', '', '2025-12-12 04:36:22', '2025-12-25 07:29:04'),
(16, 1, 'A304', 3, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(17, 1, 'A305', 3, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(18, 1, 'A306', 3, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(19, 1, 'A401', 4, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(20, 1, 'A402', 4, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(21, 1, 'A403', 4, 'single', 1, 0.00, 'occupied', NULL, '2025-12-12 04:36:22', '2025-12-25 04:15:35'),
(22, 1, 'A404', 4, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(23, 1, 'A405', 4, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(24, 1, 'A406', 4, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(25, 2, 'B101', 1, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(26, 2, 'B102', 1, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(27, 2, 'B103', 1, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(28, 2, 'B104', 1, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(29, 2, 'B105', 1, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(30, 2, 'B106', 1, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(31, 2, 'B201', 2, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(32, 2, 'B202', 2, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(33, 2, 'B203', 2, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(34, 2, 'B204', 2, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(35, 2, 'B205', 2, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(36, 2, 'B206', 2, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(37, 2, 'B301', 3, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(38, 2, 'B302', 3, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(39, 2, 'B303', 3, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(40, 2, 'B304', 3, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(41, 2, 'B305', 3, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(42, 2, 'B306', 3, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(43, 2, 'B401', 4, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(44, 2, 'B402', 4, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(45, 2, 'B403', 4, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(46, 2, 'B404', 4, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(47, 2, 'B405', 4, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(48, 2, 'B406', 4, 'single', 1, 0.00, 'available', NULL, '2025-12-12 04:36:22', '2025-12-12 04:36:22');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_room_types`
--

CREATE TABLE `dorm_room_types` (
  `id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `max_person` tinyint DEFAULT '1',
  `price_month` decimal(10,2) DEFAULT '0.00',
  `price_night` decimal(10,2) DEFAULT '0.00',
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `allowed_employee_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `dorm_room_types`
--

INSERT INTO `dorm_room_types` (`id`, `name`, `max_person`, `price_month`, `price_night`, `amenities`, `allowed_employee_types`, `status`, `created_at`, `updated_at`) VALUES
(1, 'tes', 1, 0.00, 0.00, NULL, NULL, 'active', '2025-12-05 07:58:29', '2025-12-05 07:58:29');

-- --------------------------------------------------------

--
-- Table structure for table `dorm_utility_rates`
--

CREATE TABLE `dorm_utility_rates` (
  `id` int NOT NULL,
  `rate_type` enum('electricity','water','room_service','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate_per_unit` decimal(10,2) NOT NULL,
  `unit_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'หน่วย',
  `effective_date` date NOT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dorm_utility_rates`
--

INSERT INTO `dorm_utility_rates` (`id`, `rate_type`, `rate_name`, `rate_per_unit`, `unit_name`, `effective_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'electricity', 'ค่าไฟฟ้า', 7.00, 'หน่วย', '2024-01-01', 'active', '2025-12-12 04:36:22', '2025-12-12 04:36:22'),
(2, 'water', 'ค่าน้ำประปา', 18.00, 'หน่วย', '2024-01-01', 'active', '2025-12-12 04:36:22', '2025-12-12 04:36:22');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int NOT NULL,
  `recipient_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body_preview` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `recipient_email`, `subject`, `body_preview`, `status`, `error_message`, `created_at`) VALUES
(1, 'porames_bua@inteqc.com', 'การขออนุมัติการจองรถ - ประเมศวร์ บัวศรี', '\n\n\n\n    \n    \n    \n        body {\n            font-family: Arial, sans-serif;\n            line-height: 1.6;\n            color: #333;\n        }\n\n        .container {\n            max-width: 600px;\n     ', 'sent', NULL, '2026-01-13 04:29:45'),
(2, 'porames_bua@inteqc.com', 'รีเซ็ตรหัสผ่าน - MyHR Portal', '\n        \n        \n        \n            \n            \n                body { font-family: \'Kanit\', Arial, sans-serif; line-height: 1.6; color: #333; }\n                .container { max-width: 600px; ma', 'sent', NULL, '2026-01-13 04:30:44'),
(3, 'porames_bua@inteqc.com', '[MyHR Portal] test', '\r\n        \r\n        \r\n        \r\n            \r\n            \r\n                body { font-family: \'Kanit\', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f5', 'sent', NULL, '2026-01-13 04:49:32'),
(4, 'porames_bua@inteqc.com', '[MyHR Portal] test', '\r\n        \r\n        \r\n        \r\n            \r\n            \r\n                body { font-family: \'Kanit\', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f5', 'sent', NULL, '2026-01-13 06:07:41'),
(5, 'porames_bua@inteqc.com', '[MyHR Portal] test', '\r\n        \r\n        \r\n        \r\n            \r\n            \r\n                body { font-family: \'Kanit\', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f5', 'sent', NULL, '2026-01-13 06:09:58'),
(6, 'porames_bua@inteqc.com', '[MyHR Portal] test', '\r\n        \r\n        \r\n        \r\n            \r\n            \r\n                body { font-family: \'Kanit\', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f5', 'sent', NULL, '2026-01-13 06:11:15'),
(7, 'porames_bua@inteqc.com', '[MyHR Portal] test', '\r\n        \r\n        \r\n        \r\n            \r\n            \r\n                body { font-family: \'Kanit\', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f5', 'sent', NULL, '2026-01-13 06:12:13'),
(8, 'porames_bua@inteqc.com', '[MyHR Portal] test', '\r\n        \r\n        \r\n        \r\n            \r\n            \r\n                body { font-family: \'Kanit\', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f5', 'sent', NULL, '2026-01-13 06:15:13'),
(9, 'porames_bua@inteqc.com', '[MyHR Portal] test', '\r\n        \r\n        \r\n        \r\n            \r\n            \r\n                body { font-family: \'Kanit\', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f5', 'sent', NULL, '2026-01-13 06:18:26'),
(10, 'porames_bua@inteqc.com', '[MyHR Portal] test', '\r\n        \r\n        \r\n        \r\n            \r\n            \r\n                body { font-family: \'Kanit\', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f5', 'sent', NULL, '2026-01-13 06:18:50'),
(11, 'porames_bua@inteqc.com', '[MyHR Portal] test', '\r\n        \r\n        \r\n        \r\n            \r\n            \r\n                body { font-family: \'Kanit\', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f5', 'sent', NULL, '2026-01-13 06:23:49'),
(12, 'porames_bua@inteqc.com', 'ผู้บังคับบัญชาอนุมัติแล้ว - รอสายงาน IPCD พิจารณา', '\n\n\n\n    \n    \n    \n        body {\n            font-family: Arial, sans-serif;\n            line-height: 1.6;\n            color: #333;\n        }\n\n        .container {\n            max-width: 600px;\n     ', 'sent', NULL, '2026-01-23 07:42:26'),
(13, 'porames_bua@inteqc.com', 'การจองรถของคุณได้รับการอนุมัติ', '\n\n\n\n    \n    \n    \n        body {\n            font-family: Arial, sans-serif;\n            line-height: 1.6;\n            color: #333;\n        }\n\n        .container {\n            max-width: 600px;\n     ', 'sent', NULL, '2026-01-23 07:42:42'),
(14, 'porames_bua@inteqc.com', 'การขออนุมัติการจองรถ - ประเมศวร์ บัวศรี', '\n\n\n\n    \n    \n    \n        body {\n            font-family: Arial, sans-serif;\n            line-height: 1.6;\n            color: #333;\n        }\n\n        .container {\n            max-width: 600px;\n     ', 'sent', NULL, '2026-01-23 07:47:52');

-- --------------------------------------------------------

--
-- Table structure for table `hr_news`
--

CREATE TABLE `hr_news` (
  `id` int NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_translations` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `summary` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `summary_translations` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content_translations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','scheduled','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `publish_at` datetime DEFAULT NULL,
  `expire_at` datetime DEFAULT NULL,
  `hero_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_news`
--

INSERT INTO `hr_news` (`id`, `title`, `title_translations`, `summary`, `summary_translations`, `content`, `content_translations`, `status`, `is_pinned`, `publish_at`, `expire_at`, `hero_image`, `link_url`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(3, 'ทดสอบ', NULL, 'Hello World', NULL, 'ทดสอบแสดงเนื้อหาข่าวประชาสัมพันธ์', NULL, 'published', 0, '2025-11-29 10:43:00', NULL, '/Modules/HRNews/uploads/hero/1764304214_2e54b380_Red.logo.png', 'http://inteqc-intouch/', 6, 6, '2025-11-28 03:44:37', '2025-12-18 07:13:00');

-- --------------------------------------------------------

--
-- Table structure for table `hr_news_attachments`
--

CREATE TABLE `hr_news_attachments` (
  `id` int NOT NULL,
  `news_id` int NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `attachment_type` enum('file','link','body_image') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'file'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_news_attachments`
--

INSERT INTO `hr_news_attachments` (`id`, `news_id`, `file_name`, `file_path`, `file_url`, `mime_type`, `file_size`, `created_at`, `attachment_type`) VALUES
(60, 3, 'user (1).png', 'C:\\xampp\\htdocs\\myhr_services\\public\\assets\\uploads\\hr_news\\1764304239_10418455_user__1_.png', '/Modules/HRNews/uploads/attachments/1764304239_10418455_user__1_.png', 'image/png', 24499, '2025-11-28 04:30:39', 'file'),
(61, 3, 'add.png', 'C:\\xampp\\htdocs\\myhr_services\\public\\assets\\uploads\\hr_news\\body\\1764304239_a6887d5f_add.png', '/Modules/HRNews/uploads/body/1764304239_a6887d5f_add.png', 'image/png', 21455, '2025-11-28 04:30:39', 'body_image'),
(62, 3, 'angle-left.png', 'C:\\xampp\\htdocs\\myhr_services\\public\\assets\\uploads\\hr_news\\body\\1764304239_3f8d3f26_angle-left.png', '/Modules/HRNews/uploads/body/1764304239_3f8d3f26_angle-left.png', 'image/png', 5809, '2025-11-28 04:30:39', 'body_image'),
(63, 3, 'angle-right.png', 'C:\\xampp\\htdocs\\myhr_services\\public\\assets\\uploads\\hr_news\\body\\1764304239_1a6ad0b4_angle-right.png', '/Modules/HRNews/uploads/body/1764304239_1a6ad0b4_angle-right.png', 'image/png', 5591, '2025-11-28 04:30:39', 'body_image'),
(64, 3, 'biometric-data.png', 'C:\\xampp\\htdocs\\myhr_services\\public\\assets\\uploads\\hr_news\\body\\1764304239_f303b667_biometric-data.png', '/Modules/HRNews/uploads/body/1764304239_f303b667_biometric-data.png', 'image/png', 33384, '2025-11-28 04:30:39', 'body_image'),
(65, 3, 'building.png', 'C:\\xampp\\htdocs\\myhr_services\\public\\assets\\uploads\\hr_news\\body\\1764304239_0ac30031_building.png', '/Modules/HRNews/uploads/body/1764304239_0ac30031_building.png', 'image/png', 14208, '2025-11-28 04:30:39', 'body_image'),
(66, 3, 'communication.png', 'C:\\xampp\\htdocs\\myhr_services\\public\\assets\\uploads\\hr_news\\body\\1764304239_e5235671_communication.png', '/Modules/HRNews/uploads/body/1764304239_e5235671_communication.png', 'image/png', 14275, '2025-11-28 04:30:39', 'body_image'),
(67, 3, 'ทดสอบแนบลิ้งค์', NULL, 'http://inteqc-intouch/', 'link', NULL, '2025-11-28 04:30:39', 'link');

-- --------------------------------------------------------

--
-- Table structure for table `hr_services`
--

CREATE TABLE `hr_services` (
  `id` int NOT NULL,
  `module_id` int DEFAULT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_translations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '#3B82F6',
  `custom_icon_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ready','soon','maintenance') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'soon',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'relative link to module (optional)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_services`
--

INSERT INTO `hr_services` (`id`, `module_id`, `name`, `name_translations`, `category`, `icon`, `icon_color`, `custom_icon_path`, `status`, `path`, `created_at`, `updated_at`) VALUES
(17, NULL, 'Request for Uniforms', '{\"en\":\"Request for Uniforms\",\"th\":\"Request for Uniforms\"}', 'Facilities', 'ri-shirt-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(18, 20, 'Request for Domitory', '{\"en\":\"Request for Domitory\",\"th\":\"ขอให้บริการหอพัก\",\"mm\":\"Request for Domitory\"}', 'Facilities', 'ri-hotel-bed-line', '#3B82F6', NULL, 'ready', '../../Dormitory', '2025-11-27 05:54:10', '2025-12-19 01:55:14'),
(19, NULL, 'Request For Name Card', '{\"en\":\"Request For Name Card\",\"th\":\"Request For Name Card\",\"mm\":\"Request For Name Card\"}', 'Facilities', 'ri-contacts-book-2-line', '#3B82F6', NULL, 'soon', 'javascript:void(0)', '2025-11-27 05:54:10', '2025-12-19 02:08:40'),
(20, NULL, 'Request for Working Space', '{\"en\":\"Request for Working Space\",\"th\":\"Request for Working Space\"}', 'Facilities', 'ri-macbook-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(21, NULL, 'Request for Vehicle Passing / Parking Sticker', '{\"en\":\"Request for Vehicle Passing \\/ Parking Sticker\",\"th\":\"Request for Vehicle Passing \\/ Parking Sticker\"}', 'Facilities', 'ri-parking-box-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(22, NULL, 'Request for Stationery', '{\"en\":\"Request for Stationery\",\"th\":\"Request for Stationery\"}', 'Facilities', 'ri-pencil-ruler-2-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(23, NULL, 'Request for HR tools / equipment Borrowing - Return', '{\"en\":\"Request for HR tools \\/ equipment Borrowing - Return\",\"th\":\"Request for HR tools \\/ equipment Borrowing - Return\"}', 'Facilities', 'ri-tools-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(24, NULL, 'Request for Labor, Gardeners, Cleaners', '{\"en\":\"Request for Labor, Gardeners, Cleaners\",\"th\":\"Request for Labor, Gardeners, Cleaners\"}', 'Facilities', 'ri-community-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(25, 2, 'Request for Organization\'s Vehicles', '{\"en\":\"Request for Organization\'s Vehicles\",\"th\":\"ขอใช้บริการรถยนต์บริษัท\",\"mm\":\"Request for Organization\'s Vehicles\"}', 'Facilities', 'Modules/HRServices/public/assets/images/icons/icon_6972fe8dcf5c1.png', '', NULL, 'ready', '../../CarBooking', '2025-11-27 05:54:10', '2026-01-23 04:52:29'),
(26, NULL, 'Request for Fleet Card', '{\"en\":\"Request for Fleet Card\",\"th\":\"Request for Fleet Card\"}', 'Facilities', 'ri-bank-card-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(27, NULL, 'Request for Maintenance of tools, equipment, or premises', '{\"en\":\"Request for Maintenance of tools, equipment, or premises\",\"th\":\"Request for Maintenance of tools, equipment, or premises\"}', 'Facilities', 'ri-hammer-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(28, 4, 'Request for ICT Support', '{\"en\":\"Request for ICT Support\",\"th\":\"Request for ICT Support\",\"mm\":\"Request for ICT Support\"}', 'Other Services', 'ri-cpu-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2026-01-29 06:05:24'),
(29, NULL, 'Request for INTEQC Site Visit', '{\"en\":\"Request for INTEQC Site Visit\",\"th\":\"Request for INTEQC Site Visit\"}', 'Other Services', 'ri-map-pin-user-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(30, NULL, 'Request for Petty Cash', '{\"en\":\"Request for Petty Cash\",\"th\":\"Request for Petty Cash\"}', 'Other Services', 'ri-money-dollar-circle-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(31, NULL, 'Request for Book Borrowing - Return', '{\"en\":\"Request for Book Borrowing - Return\",\"th\":\"Request for Book Borrowing - Return\"}', 'Other Services', 'ri-book-read-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(32, NULL, 'Voice of Associate', '{\"en\":\"Voice of Associate\",\"th\":\"Voice of Associate\"}', 'Other Services', 'ri-voiceprint-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(33, NULL, 'Workforce Management', '{\"en\":\"Workforce Management\",\"th\":\"Workforce Management\"}', 'Workforce', 'ri-team-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(34, NULL, 'Request for Adhoc HR Report', '{\"en\":\"Request for Adhoc HR Report\",\"th\":\"Request for Adhoc HR Report\"}', 'Information Management', 'ri-bar-chart-2-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(35, NULL, 'View/Request Personal Data', '{\"en\":\"View\\/Request Personal Data\",\"th\":\"View\\/Request Personal Data\"}', 'Information Management', 'ri-profile-line', '#3B82F6', NULL, 'soon', '#', '2025-11-27 05:54:10', '2025-12-19 01:35:08'),
(37, 5, 'INTEQC GLOBAL ASSESMENT', '{\"en\":\"INTEQC GLOBAL ASSESMENT\",\"th\":\"INTEQC GLOBAL ASSESMENT\",\"mm\":\"INTEQC GLOBAL ASSESMENT\"}', 'TEST', 'ri-book-line', '#3B82F6', NULL, 'soon', '../../IGA', '2025-11-27 08:02:05', '2026-01-29 06:04:53'),
(39, 26, 'Yearly Activities Tracking System', '{\"en\":\"Yearly Activities Tracking System\",\"th\":\"Yearly Activities Tracking System\",\"mm\":\"Yearly Activities Tracking System\"}', 'Other Services', 'ri-calendar-event-line', '#F59E0B', NULL, 'ready', '../../YearlyActivity', '2025-12-19 06:12:05', '2026-01-27 03:27:00');

-- --------------------------------------------------------

--
-- Table structure for table `iga_applicants`
--

CREATE TABLE `iga_applicants` (
  `applicant_id` int NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organization` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Company applying to',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verification_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `iga_questions`
--

CREATE TABLE `iga_questions` (
  `question_id` int NOT NULL,
  `section_id` int NOT NULL,
  `question_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_type` enum('single_choice','multiple_choice','short_answer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single_choice',
  `question_order` int NOT NULL DEFAULT '1',
  `points` decimal(6,2) NOT NULL DEFAULT '1.00',
  `explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Shown after answering',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `iga_questions`
--

INSERT INTO `iga_questions` (`question_id`, `section_id`, `question_text`, `question_type`, `question_order`, `points`, `explanation`, `created_at`) VALUES
(1, 1, 'ดหกดก', 'multiple_choice', 1, 1.00, NULL, '2025-12-24 11:46:43');

-- --------------------------------------------------------

--
-- Table structure for table `iga_question_options`
--

CREATE TABLE `iga_question_options` (
  `option_id` int NOT NULL,
  `question_id` int NOT NULL,
  `option_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `option_order` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `iga_remember_me_tokens`
--

CREATE TABLE `iga_remember_me_tokens` (
  `token_id` int NOT NULL,
  `applicant_id` int NOT NULL,
  `token_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `iga_sections`
--

CREATE TABLE `iga_sections` (
  `section_id` int NOT NULL,
  `test_id` int NOT NULL,
  `section_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `section_order` int NOT NULL DEFAULT '1',
  `time_limit_seconds` int DEFAULT NULL COMMENT 'NULL = use test duration',
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `iga_sections`
--

INSERT INTO `iga_sections` (`section_id`, `test_id`, `section_title`, `section_order`, `time_limit_seconds`, `instructions`, `created_at`) VALUES
(1, 1, 'ดกดกหด', 1, NULL, 'กดกห', '2025-12-24 11:46:37');

-- --------------------------------------------------------

--
-- Table structure for table `iga_tests`
--

CREATE TABLE `iga_tests` (
  `test_id` int NOT NULL,
  `test_no` int DEFAULT NULL,
  `test_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `duration_minutes` int NOT NULL DEFAULT '0' COMMENT '0 = unlimited',
  `show_result_immediately` tinyint(1) NOT NULL DEFAULT '1',
  `min_passing_score` decimal(5,2) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `created_by_user_id` int DEFAULT NULL COMMENT 'References main users table',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `iga_tests`
--

INSERT INTO `iga_tests` (`test_id`, `test_no`, `test_name`, `description`, `duration_minutes`, `show_result_immediately`, `min_passing_score`, `is_published`, `created_by_user_id`, `created_at`, `updated_at`) VALUES
(1, NULL, 'แบบทดสอบความถนัด - English Test', 'ะะะ', 60, 1, 60.00, 1, NULL, '2025-12-24 08:58:07', '2025-12-24 11:49:55');

-- --------------------------------------------------------

--
-- Table structure for table `iga_user_answers`
--

CREATE TABLE `iga_user_answers` (
  `user_answer_id` int NOT NULL,
  `attempt_id` int NOT NULL,
  `question_id` int NOT NULL,
  `selected_option_id` int DEFAULT NULL COMMENT 'For choice questions',
  `user_answer_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'For short answer questions',
  `score_earned` decimal(6,2) NOT NULL DEFAULT '0.00',
  `is_reviewed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'For short answer manual review',
  `reviewed_by` int DEFAULT NULL COMMENT 'References main users table',
  `reviewed_at` datetime DEFAULT NULL,
  `answered_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `iga_user_section_times`
--

CREATE TABLE `iga_user_section_times` (
  `section_time_id` int NOT NULL,
  `attempt_id` int NOT NULL,
  `section_id` int NOT NULL,
  `time_spent_seconds` int NOT NULL DEFAULT '0',
  `start_timestamp` datetime DEFAULT NULL,
  `last_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `iga_user_test_attempts`
--

CREATE TABLE `iga_user_test_attempts` (
  `attempt_id` int NOT NULL,
  `test_id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'References main users table',
  `start_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` datetime DEFAULT NULL,
  `time_spent_seconds` int NOT NULL DEFAULT '0',
  `total_score` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `iga_user_test_attempts`
--

INSERT INTO `iga_user_test_attempts` (`attempt_id`, `test_id`, `user_id`, `start_time`, `end_time`, `time_spent_seconds`, `total_score`, `is_completed`, `ip_address`, `user_agent`) VALUES
(1, 1, 6, '2025-12-24 11:50:06', '2025-12-24 11:50:14', 0, 0.00, 1, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36'),
(2, 1, 4, '2026-01-26 08:38:13', '2026-01-26 08:38:19', 0, 0.00, 1, '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` enum('info','success','warning','error') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `link` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `data`, `link`, `is_read`, `read_at`, `created_at`) VALUES
(1, 4, 'info', 'หัวหน้าอนุมัติแล้ว', 'คำขอจองรถ #26 หัวหน้าอนุมัติแล้ว รอ IPCD พิจารณา', '{\"booking_id\":26}', '/carbooking?view=bookings', 1, '2026-01-26 08:38:31', '2025-12-22 16:16:48'),
(2, 4, 'info', 'หัวหน้าอนุมัติแล้ว', 'คำขอจองรถ #26 หัวหน้าอนุมัติแล้ว รอ IPCD พิจารณา', '{\"booking_id\":26}', '/carbooking?view=bookings', 1, '2026-01-26 08:38:31', '2025-12-22 16:18:09'),
(3, 4, 'info', 'หัวหน้าอนุมัติแล้ว', 'คำขอจองรถ #26 หัวหน้าอนุมัติแล้ว รอ IPCD พิจารณา', '{\"booking_id\":26}', '/carbooking?view=bookings', 1, '2026-01-26 08:38:31', '2025-12-22 16:19:04'),
(4, 4, 'info', 'หัวหน้าอนุมัติแล้ว', 'คำขอจองรถ #26 หัวหน้าอนุมัติแล้ว รอ IPCD พิจารณา', '{\"booking_id\":26}', '/carbooking?view=bookings', 1, '2026-01-26 08:38:31', '2025-12-22 16:24:23'),
(5, 4, 'info', 'ทดสอบการแจ้งเตือน (Info)', 'นี่คือการทดสอบแจ้งเตือนแบบ Info 10:30:12', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:12'),
(6, 4, 'success', 'อนุมัติเรียบร้อย (Success)', 'คำขอทดสอบของคุณได้รับการอนุมัติแล้ว', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:12'),
(7, 4, 'warning', 'แจ้งเตือน (Warning)', 'กรุณาตรวจสอบข้อมูลก่อนดำเนินการต่อ', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:12'),
(8, 4, 'error', 'เกิดข้อผิดพลาด (Error)', 'การดำเนินการล้มเหลว กรุณาลองใหม่', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:12'),
(9, 4, 'info', 'ทดสอบการแจ้งเตือน (Info)', 'นี่คือการทดสอบแจ้งเตือนแบบ Info 10:30:19', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:19'),
(10, 4, 'success', 'อนุมัติเรียบร้อย (Success)', 'คำขอทดสอบของคุณได้รับการอนุมัติแล้ว', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:19'),
(11, 4, 'warning', 'แจ้งเตือน (Warning)', 'กรุณาตรวจสอบข้อมูลก่อนดำเนินการต่อ', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:19'),
(12, 4, 'error', 'เกิดข้อผิดพลาด (Error)', 'การดำเนินการล้มเหลว กรุณาลองใหม่', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:19'),
(13, 4, 'info', 'ทดสอบการแจ้งเตือน (Info)', 'นี่คือการทดสอบแจ้งเตือนแบบ Info 10:30:21', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:21'),
(14, 4, 'success', 'อนุมัติเรียบร้อย (Success)', 'คำขอทดสอบของคุณได้รับการอนุมัติแล้ว', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:21'),
(15, 4, 'warning', 'แจ้งเตือน (Warning)', 'กรุณาตรวจสอบข้อมูลก่อนดำเนินการต่อ', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:21'),
(16, 4, 'error', 'เกิดข้อผิดพลาด (Error)', 'การดำเนินการล้มเหลว กรุณาลองใหม่', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:30:21'),
(17, 4, 'info', 'หัวหน้าอนุมัติแล้ว', 'คำขอจองรถ #26 หัวหน้าอนุมัติแล้ว รอ IPCD พิจารณา', '{\"booking_id\":26}', '/carbooking?view=bookings', 1, '2026-01-26 08:38:31', '2025-12-22 16:32:32'),
(18, 4, 'info', 'ทดสอบการแจ้งเตือน (Info)', 'นี่คือการทดสอบแจ้งเตือนแบบ Info 10:42:03', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:42:04'),
(19, 4, 'success', 'อนุมัติเรียบร้อย (Success)', 'คำขอทดสอบของคุณได้รับการอนุมัติแล้ว', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:42:04'),
(20, 4, 'warning', 'แจ้งเตือน (Warning)', 'กรุณาตรวจสอบข้อมูลก่อนดำเนินการต่อ', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:42:04'),
(21, 4, 'error', 'เกิดข้อผิดพลาด (Error)', 'การดำเนินการล้มเหลว กรุณาลองใหม่', '{\"test\":true}', '#', 1, '2026-01-26 08:38:31', '2025-12-22 16:42:04'),
(22, 4, 'success', 'อนุมัติจองรถสำเร็จ', 'คำขอจองรถ #26 ได้รับอนุมัติแล้ว', '{\"booking_id\":26}', '/carbooking?view=bookings', 1, '2026-01-26 08:38:31', '2025-12-23 09:41:54'),
(23, 6, 'info', 'มีคำขอจองรถรอการอนุมัติ', 'คำขอ #28 จาก Kittipong User รอการอนุมัติ', '[]', 'Modules/CarBooking/?page=approvals', 1, '2026-01-29 06:15:48', '2025-12-23 11:02:09'),
(24, 4, 'success', 'หัวหน้างานอนุมัติแล้ว', 'คำขอ #28 ผ่านการอนุมัติจากหัวหน้า รอผู้ดูแลรถ', '[]', 'Modules/CarBooking/?page=request_history', 1, '2026-01-26 08:38:31', '2025-12-23 11:02:51'),
(25, 6, 'info', 'มีคำขอรอการอนุมัติ', 'คำขอ #28 รอการอนุมัติจากผู้ดูแล', '[]', 'Modules/CarBooking/?page=pending', 1, '2026-01-29 06:15:48', '2025-12-23 11:02:54'),
(26, 8, 'info', 'มีคำขอรอการอนุมัติ', 'คำขอ #28 รอการอนุมัติจากผู้ดูแล', '[]', 'Modules/CarBooking/?page=pending', 0, NULL, '2025-12-23 11:02:58'),
(27, 4, 'success', 'อนุมัติจองรถสำเร็จ', 'คำขอจองรถ #28 ได้รับอนุมัติแล้ว รถ: ', '{\"booking_id\":28}', '/carbooking?view=bookings', 1, '2026-01-26 08:38:31', '2025-12-23 11:03:39'),
(28, 4, 'success', 'คำขอจองรถได้รับอนุมัติ', 'คำขอ #28 ได้รับการอนุมัติแล้ว', '[]', 'Modules/CarBooking/?page=request_history', 1, '2026-01-26 08:38:31', '2025-12-23 11:03:44'),
(29, 6, 'error', 'คำขอจองรถถูกเพิกถอน', 'คำขอ #24 ถูกเพิกถอน: ยกเลิก', '[]', 'Modules/CarBooking/?page=bookings', 1, '2026-01-29 06:15:48', '2025-12-23 11:09:22'),
(30, 6, 'info', 'มีคำขอหอพักใหม่', 'คำขอ#4: Kittipong User ขอเข้าพัก', '[]', 'Modules/Dormitory/?page=requests', 1, '2026-01-28 08:44:28', '2025-12-25 09:44:05'),
(31, 4, 'error', 'คำขอหอพักถูกปฏิเสธ', 'คำขอเข้าพักถูกปฏิเสธ: fdsf', '{\"request_id\":\"4\"}', '/dormitory?view=booking_form', 1, '2026-01-26 08:38:31', '2025-12-25 10:28:35'),
(32, 6, 'info', 'มีคำขอจองรถรอการอนุมัติ', 'คำขอ #29 จาก Kittipong User รอการอนุมัติ', '[]', 'Modules/CarBooking/?page=approvals', 1, '2026-01-29 06:15:48', '2025-12-26 14:28:34'),
(33, 6, 'info', 'มีคำขอจองรถใหม่', 'คำขอ #29 ไปที่ ทดสอบ', '[]', 'Modules/CarBooking/?page=pending', 1, '2026-01-29 06:15:48', '2025-12-26 14:28:34'),
(34, 4, 'info', 'หัวหน้าอนุมัติแล้ว', 'คำขอจองรถ #29 หัวหน้าอนุมัติแล้ว รอ IPCD พิจารณา', '{\"booking_id\":29}', '/carbooking?view=bookings', 1, '2026-01-26 08:38:31', '2025-12-26 14:29:28'),
(35, 4, 'success', 'หัวหน้างานอนุมัติแล้ว', 'คำขอ #29 ผ่านการอนุมัติจากหัวหน้า รอผู้ดูแลรถ', '[]', 'Modules/CarBooking/?page=request_history', 1, '2026-01-26 08:38:31', '2025-12-26 14:29:31'),
(36, 6, 'info', 'มีคำขอรอการอนุมัติ', 'คำขอ #29 รอการอนุมัติจากผู้ดูแล', '[]', 'Modules/CarBooking/?page=pending', 1, '2026-01-29 06:15:48', '2025-12-26 14:29:35'),
(37, 4, 'success', 'อนุมัติจองรถสำเร็จ', 'คำขอจองรถ #29 ได้รับอนุมัติแล้ว', '{\"booking_id\":29}', '/carbooking?view=bookings', 1, '2026-01-26 08:38:31', '2025-12-26 14:30:23'),
(38, 4, 'success', 'คำขอจองรถได้รับอนุมัติ', 'คำขอ #29 ได้รับการอนุมัติแล้ว', '[]', 'Modules/CarBooking/?page=request_history', 1, '2026-01-26 08:38:31', '2025-12-26 14:30:27'),
(39, 6, 'info', 'มีผู้แจ้งคืนรถ', 'คำขอ #29 แจ้งคืนรถแล้ว รอยืนยัน', '[]', 'Modules/CarBooking/?page=in-use', 1, '2026-01-29 06:15:48', '2025-12-26 14:41:27'),
(40, 6, 'info', 'มีคำขอจองรถรอการอนุมัติ', 'คำขอ #30 จาก ประเมศวร์ บัวศรี รอการอนุมัติ', '[]', 'Modules/CarBooking/?page=approvals', 1, '2026-01-29 06:15:48', '2026-01-12 15:12:01'),
(41, 6, 'info', 'มีคำขอจองรถใหม่', 'คำขอ #30 ไปที่ ทดสอบ', '[]', 'Modules/CarBooking/?page=pending', 1, '2026-01-29 06:15:48', '2026-01-12 15:12:01'),
(42, 6, 'info', 'หัวหน้าอนุมัติแล้ว', 'คำขอจองรถ #30 หัวหน้าอนุมัติแล้ว รอ IPCD พิจารณา', '{\"booking_id\":\"30\"}', '/carbooking?view=bookings', 1, '2026-01-28 08:37:03', '2026-01-23 07:42:22'),
(43, 6, 'success', 'หัวหน้างานอนุมัติแล้ว', 'คำขอ #30 ผ่านการอนุมัติจากหัวหน้า รอผู้ดูแลรถ', '[]', 'Modules/CarBooking/?page=request_history', 1, '2026-01-28 08:46:37', '2026-01-23 07:42:26'),
(44, 6, 'success', 'อนุมัติจองรถสำเร็จ', 'คำขอจองรถ #30 ได้รับอนุมัติแล้ว รถ: ', '{\"booking_id\":\"30\"}', '/carbooking?view=bookings', 1, '2026-01-28 08:36:58', '2026-01-23 07:42:38'),
(45, 6, 'success', 'คำขอจองรถได้รับอนุมัติ', 'คำขอ #30 ได้รับการอนุมัติแล้ว', '[]', 'Modules/CarBooking/?page=request_history', 1, '2026-01-29 06:15:59', '2026-01-23 07:42:42'),
(46, 6, 'info', 'มีคำขอจองรถรอการอนุมัติ', 'คำขอ #31 จาก ประเมศวร์ บัวศรี รอการอนุมัติ', '[]', 'Modules/CarBooking/?page=approvals', 1, '2026-01-29 06:16:03', '2026-01-23 07:47:52');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `is_used`, `created_at`) VALUES
(2, 6, 'a819cfbb06c614469a7329e28728f2830a74ae71560eb520ff5ff1fe58b06530', '2026-01-13 05:30:40', 0, '2026-01-13 04:30:40');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `is_active` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrator with full access', 1, '2025-11-27 02:26:52', '2025-11-27 06:51:56'),
(2, 'staff', 'Staff member', 0, '2025-11-27 02:26:52', '2025-11-27 02:26:52'),
(3, 'user', 'General user', 1, '2025-11-27 02:26:52', '2025-11-27 07:50:37'),
(4, 'TEST1', 'ทดสอบ 1', 1, '2025-11-27 07:02:47', '2025-12-17 04:15:30'),
(5, 'System Tester', 'test', 1, '2025-12-22 07:26:24', '2025-12-22 07:26:24');

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_reports`
--

CREATE TABLE `scheduled_reports` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อรายงาน',
  `report_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ประเภทรายงาน',
  `schedule_type` enum('daily','weekly','monthly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ความถี่',
  `schedule_time` time NOT NULL DEFAULT '08:00:00' COMMENT 'เวลาส่ง',
  `schedule_day` int DEFAULT NULL COMMENT 'วันในสัปดาห์ (1-7) หรือวันในเดือน (1-31)',
  `recipients` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'รายชื่อ email ผู้รับ (JSON array)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'สถานะเปิด/ปิด',
  `last_sent_at` datetime DEFAULT NULL COMMENT 'วันที่ส่งล่าสุด',
  `created_by` int DEFAULT NULL COMMENT 'ผู้สร้าง',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scheduled_reports`
--

INSERT INTO `scheduled_reports` (`id`, `name`, `report_type`, `schedule_type`, `schedule_time`, `schedule_day`, `recipients`, `is_active`, `last_sent_at`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'test', 'car_booking_summary', 'monthly', '08:00:00', 1, '[\"porames_bua@inteqc.com\"]', 1, '2026-01-13 13:23:49', 6, '2026-01-13 04:49:09', '2026-01-13 06:23:49'),
(2, 'ทดสอบ', 'dormitory_summary', 'monthly', '08:00:00', 1, '[\"porames_bua@inteqc.com\"]', 1, NULL, 6, '2026-01-13 06:24:29', '2026-01-13 06:24:29');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `module_id` int NOT NULL,
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `module_id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(4, 2, 'admin_emails', 'porames_bua@inteqc.com', '2025-12-23 13:26:09'),
(5, 2, 'cc_emails', '', '2025-12-23 13:26:09'),
(6, 20, 'due_date_day', '15', '2025-12-16 13:03:28'),
(7, 20, 'invoice_prefix', 'INV-', '2025-12-16 13:03:28'),
(8, 20, 'admin_email', 'porames_bua@inteqc.com', '2025-12-16 14:10:24'),
(16, 20, 'cc_email', '', '2025-12-16 14:10:24'),
(31, 20, 'max_relatives', '2', '2025-12-25 09:36:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `microsoft_id` varchar(255) DEFAULT NULL COMMENT 'Microsoft/Azure AD user ID',
  `microsoft_email` varchar(255) DEFAULT NULL COMMENT 'Email from Microsoft account',
  `role_id` int DEFAULT NULL,
  `default_supervisor_email` varchar(255) DEFAULT NULL,
  `default_supervisor_name` varchar(255) DEFAULT NULL,
  `default_supervisor_id` int DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `is_active` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `email`, `created_at`, `microsoft_id`, `microsoft_email`, `role_id`, `default_supervisor_email`, `default_supervisor_name`, `default_supervisor_id`, `fullname`, `department`, `is_active`) VALUES
(1, 'admin', '$2y$10$2DNiwH/npACgzOl2F4xfsOswMGv0pC4CiyhEwrV/3mjpriYoxT0w.', 'admin@example.com', '2025-11-27 02:26:52', NULL, NULL, 1, NULL, NULL, NULL, 'Admin User', 'IT', 1),
(2, 'supervisor', '$2y$10$2DNiwH/npACgzOl2F4xfsOswMGv0pC4CiyhEwrV/3mjpriYoxT0w.', 'supervisor@example.com', '2025-11-27 02:26:52', NULL, NULL, 2, NULL, NULL, NULL, 'Somchai Supervisor', 'Operations', 0),
(3, 'manager', '$2y$10$2DNiwH/npACgzOl2F4xfsOswMGv0pC4CiyhEwrV/3mjpriYoxT0w.', 'manager@example.com', '2025-11-27 02:26:52', NULL, NULL, 2, NULL, NULL, NULL, 'Suda Manager', 'Operations', 0),
(4, 'user', '$2y$10$2DNiwH/npACgzOl2F4xfsOswMGv0pC4CiyhEwrV/3mjpriYoxT0w.', 'user@example.com', '2025-11-27 02:26:52', NULL, NULL, 5, 'supervisor@example.com', NULL, NULL, 'Kittipong User', 'Operations', 1),
(6, 'porames.buasri', '$2y$10$7roXNziAU93hXdhbZPhYu.7ABSzu8qJUHn1cqHGIdAQJ7ONfM9p2y', 'porames_bua@inteqc.com', '2025-11-27 04:33:04', '72158d5c-3f08-4250-97a0-66d92a045628', 'porames_bua@inteqc.com', 1, 'porames_bua@inteqc.com', NULL, NULL, 'ประเมศวร์ บัวศรี', 'HR', 1),
(7, 'dorm_admin', '$2y$10$oPEw22aywEZs1X1dAyt.Lu68XBe3N7d6Xj7pDgPyYfmFPx6Nsdlfu', 'dorm_admin@example.com', '2025-12-13 08:54:32', NULL, NULL, 4, NULL, NULL, NULL, 'Dormitory Admin', NULL, 0),
(8, 'teerapon', '$2y$10$FHSr8owpWT0CSFVUOUfUxeO7Sb7o06R6QUZlgx.cZAXSWfEFx1F8a', 'teerapon.nga@mail.pbru.ac.th', '2025-12-17 04:25:52', NULL, NULL, 4, 'porames_bua@inteqc.com', 'ประเมศวร์ บัวศรี', 6, 'Teerapon ngamakeam', NULL, 1),
(101, 'user101', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ceo101@example.com', '2026-01-29 08:48:43', NULL, NULL, 3, NULL, NULL, NULL, 'User One (CEO)', 'Executive', 1),
(102, 'user102', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr102@example.com', '2026-01-29 08:48:43', NULL, NULL, 3, NULL, NULL, NULL, 'User Two (HR)', 'HR', 1),
(103, 'user103', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'it103@example.com', '2026-01-29 08:48:43', NULL, NULL, 3, NULL, NULL, NULL, 'User Three (IT)', 'IT', 1),
(104, 'user104', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mkt104@example.com', '2026-01-29 08:48:43', NULL, NULL, 3, NULL, NULL, NULL, 'User Four (MKT)', 'Marketing', 1),
(105, 'user105', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ops105@example.com', '2026-01-29 08:48:43', NULL, NULL, 3, NULL, NULL, NULL, 'User Five (OPS)', 'Operations', 1),
(106, 'user106', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'fin106@example.com', '2026-01-29 08:48:43', NULL, NULL, 3, NULL, NULL, NULL, 'User Six (FIN)', 'Finance', 1),
(107, 'user107', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'safety107@example.com', '2026-01-29 08:48:43', NULL, NULL, 3, NULL, NULL, NULL, 'User Seven (Safety)', 'Safety', 1),
(108, 'user108', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'audit108@example.com', '2026-01-29 08:48:43', NULL, NULL, 3, NULL, NULL, NULL, 'User Eight (Audit)', 'Audit', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_logins`
--

CREATE TABLE `user_logins` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'login, logout',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_logins`
--

INSERT INTO `user_logins` (`id`, `user_id`, `user_name`, `action`, `ip_address`, `user_agent`, `created_at`) VALUES
(39, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-01-23 08:21:43'),
(40, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:23:08'),
(41, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:39:22'),
(42, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:39:49'),
(43, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:41:15'),
(44, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:42:07'),
(45, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:44:16'),
(46, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:49:14'),
(47, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:49:19'),
(48, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:49:23'),
(49, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:49:30'),
(50, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:49:34'),
(51, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:49:53'),
(52, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:49:55'),
(53, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:50:09'),
(54, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:50:12'),
(55, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:50:18'),
(56, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:50:20'),
(57, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:50:26'),
(58, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:50:30'),
(59, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:51:24'),
(60, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:51:45'),
(61, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:51:53'),
(62, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:52:05'),
(63, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:52:15'),
(64, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:52:19'),
(65, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:52:23'),
(66, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:52:34'),
(67, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:53:00'),
(68, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:53:04'),
(69, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:53:10'),
(70, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:53:23'),
(71, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:53:31'),
(72, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:53:45'),
(73, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:54:14'),
(74, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:54:22'),
(75, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:54:34'),
(76, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:54:48'),
(77, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:55:43'),
(78, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:55:47'),
(79, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:55:54'),
(80, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:57:06'),
(81, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:57:14'),
(82, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:57:27'),
(83, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:57:40'),
(84, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:57:53'),
(85, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:58:01'),
(86, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 08:58:12'),
(87, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:21:12'),
(88, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:21:27'),
(89, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:26:17'),
(90, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:29:33'),
(91, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:29:42'),
(92, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:30:44'),
(93, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:30:51'),
(94, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:31:30'),
(95, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:31:36'),
(96, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:32:55'),
(97, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:33:03'),
(98, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:36:53'),
(99, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:37:12'),
(100, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:38:27'),
(101, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:38:41'),
(102, 4, 'Kittipong User', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:38:55'),
(103, 4, 'Kittipong User', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:39:09'),
(104, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:39:19'),
(105, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:39:27'),
(106, 4, 'Kittipong User', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:39:48'),
(107, 4, 'Kittipong User', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:40:03'),
(108, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:40:07'),
(109, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:40:49'),
(110, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:40:57'),
(111, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:41:17'),
(112, 4, 'Kittipong User', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:41:26'),
(113, 4, 'Kittipong User', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:41:31'),
(114, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:41:34'),
(115, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:41:59'),
(116, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:42:13'),
(117, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:43:46'),
(118, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:44:05'),
(119, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:45:44'),
(120, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:45:47'),
(121, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:46:03'),
(122, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:47:04'),
(123, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:47:31'),
(124, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:47:44'),
(125, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:49:47'),
(126, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:50:01'),
(127, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:50:55'),
(128, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:52:36'),
(129, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-23 09:52:45'),
(130, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 01:18:37'),
(131, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 01:18:52'),
(132, 1, 'Admin User', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-26 01:24:31'),
(133, 1, 'Admin User', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-26 01:24:33'),
(134, 4, 'Kittipong User', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-26 01:25:19'),
(135, 4, 'Kittipong User', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-26 01:26:16'),
(136, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-26 01:35:20'),
(137, 6, 'ประเมศวร์ บัวศรี', 'logout', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-26 01:35:24'),
(138, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-26 02:20:43'),
(139, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 03:03:32'),
(140, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 03:41:30'),
(141, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 04:12:45'),
(142, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-26 06:02:04'),
(143, 4, 'Kittipong User', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-26 08:36:10'),
(144, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-26 09:04:06'),
(145, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:11:44'),
(146, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:52:19'),
(147, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 04:04:28'),
(148, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 04:38:24'),
(149, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 06:03:01'),
(150, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 06:05:18'),
(151, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-27 06:51:23'),
(152, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-27 07:00:37'),
(153, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-27 07:30:50'),
(154, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:01:07'),
(155, 4, 'Kittipong User', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 08:28:26'),
(156, 4, 'Kittipong User', 'logout', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 08:28:30'),
(157, 4, 'Kittipong User', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-01-27 08:28:45'),
(158, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-28 01:27:58'),
(159, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-28 01:59:26'),
(160, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-28 02:29:57'),
(161, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-28 02:59:59'),
(162, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-28 03:30:11'),
(163, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-28 04:03:19'),
(164, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-28 04:38:24'),
(165, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-28 05:58:13'),
(166, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-28 08:31:00'),
(167, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-29 01:08:14'),
(168, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-29 01:53:17'),
(169, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-29 02:38:37'),
(170, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-29 03:49:26'),
(171, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-29 04:21:01'),
(172, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-29 04:58:25'),
(173, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-29 05:47:44'),
(174, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-29 07:13:15'),
(175, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-29 07:49:55'),
(176, 6, 'ประเมศวร์ บัวศรี', 'login', '172.18.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-29 08:45:09');

-- --------------------------------------------------------

--
-- Table structure for table `ya_activities`
--

CREATE TABLE `ya_activities` (
  `id` int NOT NULL,
  `calendar_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `objective` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `scope` text COLLATE utf8mb4_unicode_ci,
  `status` enum('proposed','planned','incoming','in_progress','on_hold','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'planned',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `key_person_id` int DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_synced` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ya_activities`
--

INSERT INTO `ya_activities` (`id`, `calendar_id`, `name`, `type`, `objective`, `description`, `scope`, `status`, `start_date`, `end_date`, `location`, `key_person_id`, `created_by`, `created_at`, `updated_at`, `is_synced`) VALUES
(1001, 101, 'Annual Shareholder Meeting', 'event', 'Report annual performance', 'Official meeting with shareholders and press.', '1. Fin Reports\n2. CEO Speech\n3. Voting', 'completed', '2026-01-20 00:00:00', '2026-01-20 00:00:00', 'Grand Hyatt', 101, 101, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0),
(1002, 101, 'Quarterly Business Review Q1', 'event', 'Review Q1 KPIs', 'Management team review of Q1 targets.', 'Review Fin, Ops, HR, IT metrics', 'completed', '2026-04-05 00:00:00', '2026-04-05 00:00:00', 'Boardroom A', 101, 101, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0),
(1003, 101, 'M&A Due Diligence - Project X', 'project', 'Acquire competitor', 'Confidential due diligence process for potential acquisition.', 'Legal, Financial, Operational Audit', 'in_progress', '2026-03-01 00:00:00', '2026-06-30 00:00:00', 'Secret Room', 102, 101, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0),
(1004, 101, 'Sustainability Report Launch', 'project', 'Publish ESG report', 'Design and publish the 2026 ESG impact report.', 'Data collection, copywriting, design', 'planned', '2026-09-01 00:00:00', '2026-10-15 00:00:00', 'HQ', 106, 101, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0),
(1005, 101, 'Board Retreat', 'event', 'Strategic planning', 'Annual board of directors retreat.', 'Board members', 'planned', '2026-11-10 00:00:00', '2026-11-12 00:00:00', 'Phuket', 101, 101, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0),
(2001, 102, 'Leadership Development Program', 'project', 'Train future leaders', '6-month training course for high potentials.', 'Mentorship, Workshops', 'incoming', '2026-03-01 00:00:00', '2026-09-30 00:00:00', 'Training Center', 102, 102, '2026-01-29 08:48:43', '2026-01-29 08:49:24', 0),
(2002, 102, 'Annual Staff Party', 'event', 'Celebrate success', 'Year-end party.', 'Dinner, Games, Awards', 'incoming', '2026-12-18 00:00:00', '2026-12-18 00:00:00', 'Marriott Marquis', 106, 102, '2026-01-29 08:48:43', '2026-01-29 08:49:24', 0),
(2003, 102, 'Employee Engagement Survey 2026', 'project', 'Measure engagement', 'Annual survey and action planning.', 'All staff', 'incoming', '2026-08-01 00:00:00', '2026-10-30 00:00:00', 'Online', 102, 102, '2026-01-29 08:48:43', '2026-01-29 08:49:24', 0),
(2004, 102, 'Q3 Town Hall', 'event', 'Company update', 'Quarterly all-hands meeting.', 'Hybrid', 'incoming', '2026-07-15 00:00:00', '2026-07-15 00:00:00', 'Auditorium', 101, 102, '2026-01-29 08:48:43', '2026-01-29 08:49:24', 0),
(3001, 103, 'ERP Migration Phase 1', 'project', 'Upgrade legacy systems', 'Migrating from SAP ECC to S/4HANA.', 'Finance Module first', 'in_progress', '2026-01-10 00:00:00', '2026-08-30 00:00:00', 'Server Room', 103, 103, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0),
(3002, 103, 'Cybersecurity Audit', 'project', 'Identify vulnerabilities', 'External penetration testing.', 'Network, App, Physical Security', 'planned', '2026-05-01 00:00:00', '2026-05-30 00:00:00', 'HQ', 108, 103, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0),
(3003, 103, 'Laptop Refresh Cycle', 'project', 'Replace old hardware', 'Replace 200 laptops for staff.', 'Procurement, Setup, Distribution', 'planned', '2026-11-01 00:00:00', '2026-12-15 00:00:00', 'IT Dept', 105, 103, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0),
(4001, 104, 'Product Launch: SmartWidget v2', 'project', 'Launch new product line', 'Go-to-market strategy for SmartWidget v2.', '1. Social Media\n2. PR Event\n3. Influencers', 'incoming', '2026-02-15 00:00:00', '2026-05-20 00:00:00', 'Siam Paragon', 104, 104, '2026-01-29 08:48:43', '2026-01-29 08:49:18', 0),
(4002, 104, 'Summer Sale Campaign', 'project', 'Boost Q2 Revenue', 'Nationwide discount campaign.', 'Online & Offline ads', 'incoming', '2026-04-01 00:00:00', '2026-04-30 00:00:00', 'Nationwide', 103, 104, '2026-01-29 08:48:43', '2026-01-29 08:49:18', 0),
(4003, 104, 'Brand Refresh Workshop', 'event', 'Modernize brand identity', 'Internal workshop to brainstorm new CI.', 'Logo, Colors, Tone of Voice', 'incoming', '2026-07-10 00:00:00', '2026-07-12 00:00:00', 'Hua Hin Resort', 104, 104, '2026-01-29 08:48:43', '2026-01-29 08:49:18', 0),
(5001, 105, 'Warehouse Automation', 'project', 'Reduce labor costs', 'Install robotic sorting arms.', 'Hardware install, Software integration', 'planned', '2026-06-01 00:00:00', '2026-12-31 00:00:00', 'Warehouse A', 105, 105, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0),
(5002, 105, 'ISO 45001 Certification', 'project', 'Improve safety', 'Occupational health and safety standard.', 'Documentation, Audits', 'planned', '2026-02-01 00:00:00', '2026-07-31 00:00:00', 'Factory Floor', 107, 105, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0),
(5003, 105, 'Supply Chain Optimization', 'project', 'Reduce logistics cost', 'Review and renegotiate vendor contracts.', 'Procurement', 'in_progress', '2026-02-01 00:00:00', '2026-11-30 00:00:00', 'HQ', 105, 105, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0),
(5004, 105, 'Safety Week 2026', 'event', 'Promote safety culture', 'Week-long safety awareness campaign.', 'All factory staff', 'planned', '2026-09-20 00:00:00', '2026-09-25 00:00:00', 'Factory', 107, 105, '2026-01-29 08:48:43', '2026-01-29 08:48:43', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ya_activity_logs`
--

CREATE TABLE `ya_activity_logs` (
  `id` int NOT NULL,
  `activity_id` int NOT NULL,
  `previous_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `changed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `changed_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ya_calendars`
--

CREATE TABLE `ya_calendars` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` year NOT NULL,
  `owner_id` int NOT NULL,
  `status` enum('active','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ya_calendars`
--

INSERT INTO `ya_calendars` (`id`, `name`, `description`, `year`, `owner_id`, `status`, `created_at`, `updated_at`) VALUES
(101, 'Corporate Strategy 2026', 'Company-wide strategic initiatives and milestones', '2026', 101, 'active', '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(102, 'HR & Culture 2026', 'Employee engagement, recruitment, and training', '2026', 102, 'active', '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(103, 'IT Transformation 2026', 'Digital transformation, infra upgrades, and security', '2026', 103, 'active', '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(104, 'Marketing Campaigns 2026', 'Product launches and brand awareness', '2026', 104, 'active', '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(105, 'Operations Excellence 2026', 'Process improvement and safety audits', '2026', 105, 'active', '2026-01-29 08:48:43', '2026-01-29 08:48:43');

-- --------------------------------------------------------

--
-- Table structure for table `ya_calendar_members`
--

CREATE TABLE `ya_calendar_members` (
  `id` int NOT NULL,
  `calendar_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('owner','admin','editor','viewer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `joined_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ya_calendar_members`
--

INSERT INTO `ya_calendar_members` (`id`, `calendar_id`, `user_id`, `role`, `joined_at`) VALUES
(92, 101, 102, 'viewer', '2026-01-29 08:48:43'),
(93, 101, 103, 'viewer', '2026-01-29 08:48:43'),
(94, 101, 104, 'viewer', '2026-01-29 08:48:43'),
(95, 101, 105, 'viewer', '2026-01-29 08:48:43'),
(96, 101, 106, 'editor', '2026-01-29 08:48:43'),
(97, 101, 107, 'viewer', '2026-01-29 08:48:43'),
(98, 101, 108, 'viewer', '2026-01-29 08:48:43'),
(99, 102, 101, 'admin', '2026-01-29 08:48:43'),
(100, 102, 103, 'viewer', '2026-01-29 08:48:43'),
(101, 102, 106, 'editor', '2026-01-29 08:48:43'),
(102, 102, 107, 'editor', '2026-01-29 08:48:43'),
(103, 103, 101, 'viewer', '2026-01-29 08:48:43'),
(104, 103, 102, 'viewer', '2026-01-29 08:48:43'),
(105, 103, 104, 'viewer', '2026-01-29 08:48:43'),
(106, 103, 105, 'editor', '2026-01-29 08:48:43'),
(107, 103, 108, 'admin', '2026-01-29 08:48:43'),
(108, 104, 101, 'viewer', '2026-01-29 08:48:43'),
(109, 104, 102, 'editor', '2026-01-29 08:48:43'),
(110, 104, 103, 'admin', '2026-01-29 08:48:43'),
(111, 104, 106, 'editor', '2026-01-29 08:48:43'),
(112, 105, 101, 'admin', '2026-01-29 08:48:43'),
(113, 105, 104, 'editor', '2026-01-29 08:48:43'),
(114, 105, 105, 'editor', '2026-01-29 08:48:43'),
(115, 105, 107, 'viewer', '2026-01-29 08:48:43'),
(116, 105, 108, 'viewer', '2026-01-29 08:48:43'),
(117, 101, 6, 'viewer', '2026-01-29 08:49:08'),
(118, 102, 6, 'viewer', '2026-01-29 08:49:08'),
(119, 103, 6, 'viewer', '2026-01-29 08:49:08'),
(120, 104, 6, 'viewer', '2026-01-29 08:49:08'),
(121, 105, 6, 'viewer', '2026-01-29 08:49:08');

-- --------------------------------------------------------

--
-- Table structure for table `ya_calendar_sync`
--

CREATE TABLE `ya_calendar_sync` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token` text COLLATE utf8mb4_unicode_ci,
  `refresh_token` text COLLATE utf8mb4_unicode_ci,
  `token_expires_at` datetime DEFAULT NULL,
  `sync_enabled` tinyint(1) DEFAULT '0',
  `last_sync_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ya_calendar_sync`
--

INSERT INTO `ya_calendar_sync` (`id`, `user_id`, `provider`, `access_token`, `refresh_token`, `token_expires_at`, `sync_enabled`, `last_sync_at`, `created_at`, `updated_at`) VALUES
(2, 6, 'outlook', 'eyJ0eXAiOiJKV1QiLCJub25jZSI6IjNOd0NlMjA3VmFGLXcxNGkwbmpqdTZDN2QtUVBnUzlrb2s0SjNNdFVaeFkiLCJhbGciOiJSUzI1NiIsIng1dCI6IlBjWDk4R1g0MjBUMVg2c0JEa3poUW1xZ3dNVSIsImtpZCI6IlBjWDk4R1g0MjBUMVg2c0JEa3poUW1xZ3dNVSJ9.eyJhdWQiOiIwMDAwMDAwMy0wMDAwLTAwMDAtYzAwMC0wMDAwMDAwMDAwMDAiLCJpc3MiOiJodHRwczovL3N0cy53aW5kb3dzLm5ldC85OGQyYjNjOC03OTkxLTQ5YjEtOTVlOS0yOTY1ODMzY2UyY2IvIiwiaWF0IjoxNzY5NjY3MjE0LCJuYmYiOjE3Njk2NjcyMTQsImV4cCI6MTc2OTY3MjI0MywiYWNjdCI6MCwiYWNyIjoiMSIsImFjcnMiOlsicDEiXSwiYWlvIjoiQVpRQWEvOGJBQUFBV0RXZm9IOGZtcTk5cEZXUmpZS0Q0RGErRlF6aHlpUGRIVlZ6QkhLUmphd3ZPQWkvMEN4czgyaXkzZngvU1FJQ2ZNRWFIQWxHNzl3NmI2RGg1bFMzTkdRMXlPNHVWVUo1aENKaFkvUGZmcDFSN2FLT2h5VHdwZGEzbG5jNDAxUDcwYzFleWVRZ1lSN01vWVpPR2hYVzBEUUZnN3dhVTlUS0pUVDFYcVFoWjMxbisyaWFTNkxCaFFmajZlbkx6TmxJIiwiYW1yIjpbInB3ZCIsIm1mYSJdLCJhcHBfZGlzcGxheW5hbWUiOiJNeUhSIFBvcnRhbCIsImFwcGlkIjoiN2RiYmI5ZjQtNjMwNS00NmM4LTk0NTktZjdiN2UxMDA5MmM0IiwiYXBwaWRhY3IiOiIxIiwiZmFtaWx5X25hbWUiOiJCdWFzcmkiLCJnaXZlbl9uYW1lIjoiUG9yYW1lcyIsImlkdHlwIjoidXNlciIsImlwYWRkciI6IjIwMy4xNzAuMTY4LjI0NCIsIm5hbWUiOiJQb3JhbWVzIEJ1YXNyaSIsIm9pZCI6IjcyMTU4ZDVjLTNmMDgtNDI1MC05N2EwLTY2ZDkyYTA0NTYyOCIsIm9ucHJlbV9zaWQiOiJTLTEtNS0yMS0yMDc0MjA0MTAyLTIyMjE2OTY1NDEtOTI2MzU3Mjk4LTg4ODAiLCJwbGF0ZiI6IjEiLCJwdWlkIjoiMTAwMzIwMDUyMzYzMjlENSIsInJoIjoiMS5BWEFBeUxQU21KRjVzVW1WNlNsbGd6eml5d01BQUFBQUFBQUF3QUFBQUFBQUFBQWtBVHh3QUEuIiwic2NwIjoiQ2FsZW5kYXJzLlJlYWRXcml0ZSBlbWFpbCBvcGVuaWQgcHJvZmlsZSBVc2VyLlJlYWQgVXNlci5SZWFkLkFsbCBVc2VyLlJlYWRCYXNpYy5BbGwiLCJzaWQiOiIwMGJhZjM3OS1hZGFmLWFmM2EtNmEzOC05ODgyM2VmMDI4YzYiLCJzaWduaW5fc3RhdGUiOlsia21zaSJdLCJzdWIiOiJ5Y3BPZ1g3dWpsX0dTSDNZOGJHbVR3M2dfblBEOE45UlpCMXFYOHhEUzdjIiwidGVuYW50X3JlZ2lvbl9zY29wZSI6IkFTIiwidGlkIjoiOThkMmIzYzgtNzk5MS00OWIxLTk1ZTktMjk2NTgzM2NlMmNiIiwidW5pcXVlX25hbWUiOiJwb3JhbWVzX2J1YUBpbnRlcWMuY29tIiwidXBuIjoicG9yYW1lc19idWFAaW50ZXFjLmNvbSIsInV0aSI6IktuTndyNXZhT0U2SERxYkNCWm9YQUEiLCJ2ZXIiOiIxLjAiLCJ3aWRzIjpbImI3OWZiZjRkLTNlZjktNDY4OS04MTQzLTc2YjE5NGU4NTUwOSJdLCJ4bXNfYWNkIjoxNzYzNjk0MzM0LCJ4bXNfYWN0X2ZjdCI6IjMgOSIsInhtc19mdGQiOiJIUVRqY1FpU3VNT0lFY2Y3YmdvUDc3RjlLVHhmaWJ2QllmcDczZm9sU1Q0QmEyOXlaV0ZqWlc1MGNtRnNMV1J6YlhNIiwieG1zX2lkcmVsIjoiMSAxMiIsInhtc19zdCI6eyJzdWIiOiJqV2tWOU9yMEdXMXZWTjdrOTVsemR6Vm9IdE1iLXBnbzRiVGRQWjVBbDNJIn0sInhtc19zdWJfZmN0IjoiMyAxMiIsInhtc190Y2R0IjoxNjI2OTI3NTE1LCJ4bXNfdG50X2ZjdCI6IjMgMTQifQ.Oe2X333nOJHcpVwYa6ow3UWQxwb85lOhneBIHT6omQeDHab3nxK_I9TIxpcjc4oBTny1s6NqnLq1QjWIqPUvqae4wK0u86n3fz2kSYZQSeU1uaEpCfM9ASJ68CGnKeDgdMZUrUQ85OF9j8zor_Xll9me8Zb5gI9TLjtb8lVl98w8XClRoFjQJ6oX1yfgEcAaFX49wBQ8jgG3woyfKYPicCSDqa3QaXxzApQvyz4NukYfBDkFZd35sMnzCh3QxXREmILDZH_A6_cz19zNvnWnOylEAWWJFs0hHK2JEm_gaKFoIFm1oF1-lFlkxwX0IP5ee0wyjBn0fV3wqPMuiD9woQ', '1.AXAAyLPSmJF5sUmV6Sllgzziy_S5u30FY8hGlFn3t-EAksQkATxwAA.BQABAwEAAAADAOz_BQD0_7LLOUg-X36oIi6OOrTgL0DwgXWho73GGO9sVlvDhCRIszUgyUxHCEvS-GLTr82UeEtvaBwFr2DRB6jMj0J1PlBJqNnn263QZunx9fGjw4HtAcsOefhs67ZJsejbvPpd4qF9T8XHi08F3oo8-uK18DpH5gYMpV4FXNWfTsoUvUWPbGwfn7joFt5Cmk43GCUZ365Ce2vlTFZy7rruM98CuDav8LsH_UXmLhBBXalaWwvOk1xhm7LUnVpUbgWRS5N5JHZtcsIZ_nLIDWVFF_QmhfWPspX7RRcHyCLnamlS37dO6miVWDuNjyFiMDu8bbSNz3Jahhoa-gyZDSYXu7QhIE7qwSOuhQ2rMfCqUcPWDmoRtrKL2S0frxY8KgPz0CDokPnSvepYNVzzPCKaUiYd7d9xXu_I3DCYEcTKX4dTj2a9PTHmj_KvvaYIpsk6HAeVWHIwBj-yPT1IYRit8VoJoU6BQP1qt37tKS3NADsuAlTQRXiAwTk-N08H9wGlmwUl992wg4jubqyM6kNPrKU45OTQ2Y-1IO-1aICLlcMaDTBOr7N2gPgFNSNKAEFkzVpHqj_WfJEts4pkngiMuIhOYTaHCIlfueQWxJIrWxoWH8xh6bG-d9x1eFe1fFgMTD2fb8YJzWSxUcoyNhxnc_Vy18qf7l8qENJKUCUFDJs3DpxxPMv-bwSxFg4uiETuztKqzTmNyiIpPE-EZq3gnzIjj_72Dq9Ni3V3NMeLnFbnMP6eYQJuKWWflBhaSESQi3Hr3e81ZRVAQQguPASWymimlOMuxsHwOx46NmuL3u6tAF3JR8PhgjogTvk89-TaG1LEDbCpy_SRcs8y4sx9teyMDLn-2TIVQW9YViwnGxwhxdlVUyD6kQRAuitbcHyKl616I14v5Il11EY2XDlB_PnVqO2FFmGnKX5qSC4SgJ9uBwuUXzkLrwE1ESipI_kmXriuJMmFaJJb_rU9BaxNmZ_nEnvCk27OBL84eMQJ6WqvBgAmAATbT_BxCG17sPRTE1gzlathVV7wraBrhpNRFM51jYnoWLfx6WMTBh2TypjzB_mQ3LSG0ZuXKbLs_YO-RiEq3lEydOUX25HJd2YVqDfOfIHc6SxaKE9xzWTjyIS8zw2Lhvw32-pWdg0YtQwNGjUs4EJh_JEao0hZA_LyBKlNqo97e2-096jxwQS32YZtciz01Cg7N7N2yqKrQWfGzTmK7Lejmc5owoIle7EuaSleEjWr1IH9Vu8Szkzqb6WJDPeFI9z1fPPMXMwsWaYW6knctZ4ZGUj3nqplYX1xwxT0_6HOHZHQ4J28LBPPOruXDqFmCzOmjJffSo33opdFe8t5N9EYEYMGp_g1nnQCBRbwwRNAh82RM5mvSlTelMEcolLGBnEAtfa-6jKEwCwC6NbA1To4CllKcw6yen7D', '2026-01-29 07:37:22', 1, NULL, '2026-01-29 06:18:34', '2026-01-29 06:18:34');

-- --------------------------------------------------------

--
-- Table structure for table `ya_milestones`
--

CREATE TABLE `ya_milestones` (
  `id` int NOT NULL,
  `activity_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_date` datetime DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `status` enum('pending','in_progress','completed','on_hold','proposed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `weight_percent` int DEFAULT '0',
  `order_index` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ya_milestones`
--

INSERT INTO `ya_milestones` (`id`, `activity_id`, `name`, `description`, `start_date`, `due_date`, `status`, `weight_percent`, `order_index`, `created_at`, `updated_at`) VALUES
(119, 3001, 'Requirement Gathering', NULL, '2026-01-10 00:00:00', '2026-02-15 00:00:00', 'completed', 20, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(120, 3001, 'System Design', NULL, '2026-02-16 00:00:00', '2026-04-15 00:00:00', 'in_progress', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(121, 3001, 'Development & Config', NULL, '2026-04-16 00:00:00', '2026-07-15 00:00:00', '', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(122, 3001, 'UAT Testing', NULL, '2026-07-16 00:00:00', '2026-08-30 00:00:00', '', 20, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(123, 4001, 'Creative Concept', NULL, '2026-02-15 00:00:00', '2026-03-01 00:00:00', 'completed', 20, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(124, 4001, 'Media Buying', NULL, '2026-03-02 00:00:00', '2026-03-30 00:00:00', 'in_progress', 40, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(125, 4001, 'Launch Event', NULL, '2026-05-20 00:00:00', '2026-05-20 00:00:00', '', 40, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(126, 2001, 'Candidate Selection', NULL, '2026-03-01 00:00:00', '2026-03-15 00:00:00', 'completed', 10, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(127, 2001, 'Module 1: Strategy', NULL, '2026-03-20 00:00:00', '2026-03-22 00:00:00', 'completed', 15, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(128, 2001, 'Module 2: People Mgmt', NULL, '2026-05-15 00:00:00', '2026-05-17 00:00:00', '', 15, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(129, 2001, 'Module 3: Finance', NULL, '2026-07-10 00:00:00', '2026-07-12 00:00:00', '', 15, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(130, 2001, 'Final Project Presentation', NULL, '2026-09-25 00:00:00', '2026-09-30 00:00:00', '', 45, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(131, 5002, 'Gap Analysis', NULL, '2026-02-01 00:00:00', '2026-03-01 00:00:00', 'completed', 20, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(132, 5002, 'Documentation Phase', NULL, '2026-03-02 00:00:00', '2026-05-30 00:00:00', 'in_progress', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(133, 5002, 'Internal Audit', NULL, '2026-06-01 00:00:00', '2026-06-30 00:00:00', '', 20, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(134, 5002, 'Final Certification Audit', NULL, '2026-07-01 00:00:00', '2026-07-31 00:00:00', '', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(135, 2003, 'Survey Design', NULL, '2026-08-01 00:00:00', '2026-08-20 00:00:00', '', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(136, 2003, 'Data Collection', NULL, '2026-08-21 00:00:00', '2026-09-15 00:00:00', '', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(137, 2003, 'Analysis & Reporting', NULL, '2026-09-16 00:00:00', '2026-10-30 00:00:00', '', 40, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(138, 5003, 'Vendor Review', NULL, '2026-02-01 00:00:00', '2026-03-31 00:00:00', 'completed', 20, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(139, 5003, 'RFP Process', NULL, '2026-04-01 00:00:00', '2026-06-30 00:00:00', 'in_progress', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(140, 5003, 'Negotiation', NULL, '2026-07-01 00:00:00', '2026-09-30 00:00:00', '', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(141, 5003, 'Contract Signing', NULL, '2026-10-01 00:00:00', '2026-11-30 00:00:00', '', 20, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(142, 1003, 'Financial Audit', NULL, '2026-03-01 00:00:00', '2026-04-15 00:00:00', 'in_progress', 40, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(143, 1003, 'Legal Review', NULL, '2026-04-16 00:00:00', '2026-05-30 00:00:00', '', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(144, 1003, 'Operations Due Diligence', NULL, '2026-06-01 00:00:00', '2026-06-30 00:00:00', '', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(145, 1001, 'Preparation', NULL, '2026-01-01 00:00:00', '2026-01-19 00:00:00', 'completed', 40, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(146, 1001, 'Event Day', NULL, '2026-01-20 00:00:00', '2026-01-20 00:00:00', 'completed', 60, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(147, 1002, 'Data Prep', NULL, '2026-03-20 00:00:00', '2026-04-04 00:00:00', 'completed', 50, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(148, 1002, 'Meeting', NULL, '2026-04-05 00:00:00', '2026-04-05 00:00:00', 'completed', 50, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(149, 1004, 'Data Collection', NULL, '2026-09-01 00:00:00', '2026-09-20 00:00:00', '', 40, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(150, 1004, 'Drafting', NULL, '2026-09-21 00:00:00', '2026-10-05 00:00:00', '', 40, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(151, 1004, 'Publication', NULL, '2026-10-06 00:00:00', '2026-10-15 00:00:00', '', 20, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(152, 4002, 'Campaign Setup', NULL, '2026-04-01 00:00:00', '2026-04-10 00:00:00', '', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(153, 4002, 'Running Ads', NULL, '2026-04-11 00:00:00', '2026-04-30 00:00:00', '', 70, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(154, 4003, 'Workshop', NULL, '2026-07-10 00:00:00', '2026-07-12 00:00:00', '', 100, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(155, 3002, 'Penetration Testing', NULL, '2026-05-01 00:00:00', '2026-05-15 00:00:00', '', 60, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(156, 3002, 'Remediation', NULL, '2026-05-16 00:00:00', '2026-05-30 00:00:00', '', 40, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(157, 3003, 'Procurement', NULL, '2026-11-01 00:00:00', '2026-11-15 00:00:00', '', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(158, 3003, 'Distribution', NULL, '2026-11-16 00:00:00', '2026-12-15 00:00:00', '', 70, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(159, 2002, 'Planning', NULL, '2026-10-01 00:00:00', '2026-11-30 00:00:00', '', 20, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(160, 2002, 'Party Night', NULL, '2026-12-18 00:00:00', '2026-12-18 00:00:00', '', 80, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(161, 2004, 'Slides Prep', NULL, '2026-07-01 00:00:00', '2026-07-14 00:00:00', '', 40, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(162, 2004, 'Live Event', NULL, '2026-07-15 00:00:00', '2026-07-15 00:00:00', '', 60, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(163, 5001, 'Design', NULL, '2026-06-01 00:00:00', '2026-07-31 00:00:00', '', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(164, 5001, 'Installation', NULL, '2026-08-01 00:00:00', '2026-11-30 00:00:00', '', 50, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(165, 5001, 'Testing', NULL, '2026-12-01 00:00:00', '2026-12-31 00:00:00', '', 20, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(166, 5004, 'Campaign Prep', NULL, '2026-09-01 00:00:00', '2026-09-19 00:00:00', '', 30, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(167, 5004, 'Safety Activities', NULL, '2026-09-20 00:00:00', '2026-09-25 00:00:00', '', 70, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(168, 1005, 'Logistics', NULL, '2026-10-01 00:00:00', '2026-11-09 00:00:00', '', 20, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43'),
(169, 1005, 'Retreat', NULL, '2026-11-10 00:00:00', '2026-11-12 00:00:00', '', 80, 0, '2026-01-29 08:48:43', '2026-01-29 08:48:43');

-- --------------------------------------------------------

--
-- Table structure for table `ya_milestone_logs`
--

CREATE TABLE `ya_milestone_logs` (
  `id` int NOT NULL,
  `milestone_id` int NOT NULL,
  `previous_status` enum('pending','in_progress','completed','on_hold','cancelled','proposed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_status` enum('pending','in_progress','completed','on_hold','cancelled','proposed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `actual_start_date` datetime DEFAULT NULL,
  `actual_end_date` datetime DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `changed_by` int DEFAULT NULL,
  `changed_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ya_milestone_rasci`
--

CREATE TABLE `ya_milestone_rasci` (
  `id` int NOT NULL,
  `milestone_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('R','A','S','C','I') COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ya_milestone_rasci`
--

INSERT INTO `ya_milestone_rasci` (`id`, `milestone_id`, `user_id`, `role`) VALUES
(147, 119, 103, 'R'),
(148, 119, 101, 'A'),
(149, 119, 102, 'C'),
(150, 119, 105, 'C'),
(151, 121, 103, 'A'),
(152, 121, 108, 'R'),
(153, 124, 104, 'R'),
(154, 124, 106, 'S'),
(155, 124, 103, 'S'),
(156, 125, 104, 'R'),
(157, 125, 101, 'A'),
(158, 125, 106, 'C'),
(159, 127, 102, 'R'),
(160, 127, 101, 'A'),
(161, 142, 106, 'R'),
(162, 142, 101, 'A'),
(163, 139, 105, 'R'),
(164, 139, 106, 'C'),
(165, 135, 102, 'R'),
(166, 135, 101, 'I'),
(167, 133, 107, 'R'),
(168, 133, 105, 'A'),
(169, 146, 101, 'R'),
(170, 146, 106, 'S'),
(171, 147, 106, 'R'),
(172, 148, 101, 'A'),
(173, 155, 108, 'R'),
(174, 155, 103, 'A'),
(175, 167, 107, 'R'),
(176, 167, 105, 'A');

-- --------------------------------------------------------

--
-- Table structure for table `ya_milestone_resources`
--

CREATE TABLE `ya_milestone_resources` (
  `id` int NOT NULL,
  `milestone_id` int NOT NULL,
  `resource_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int DEFAULT '1',
  `unit_cost` decimal(10,2) DEFAULT '0.00',
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ya_milestone_resources`
--

INSERT INTO `ya_milestone_resources` (`id`, `milestone_id`, `resource_name`, `quantity`, `unit_cost`, `unit`, `created_at`) VALUES
(23, 121, 'Consultant Hours', 500, 3000.00, 'Hours', '2026-01-29 08:48:43'),
(24, 124, 'Facebook Ads', 1, 500000.00, 'Campaign', '2026-01-29 08:48:43'),
(25, 125, 'Venue', 1, 150000.00, 'Day', '2026-01-29 08:48:43'),
(26, 125, 'Catering', 200, 800.00, 'Pax', '2026-01-29 08:48:43'),
(27, 134, 'External Auditor', 5, 15000.00, 'Man-Day', '2026-01-29 08:48:43'),
(28, 136, 'Survey Platform License', 1, 25000.00, 'License', '2026-01-29 08:48:43'),
(29, 143, 'Legal Counsel', 100, 5000.00, 'Hour', '2026-01-29 08:48:43'),
(30, 146, 'Venue Rental', 1, 50000.00, 'Day', '2026-01-29 08:48:43'),
(31, 151, 'Printing', 500, 100.00, 'Copy', '2026-01-29 08:48:43'),
(32, 154, 'Facilitator', 3, 20000.00, 'Day', '2026-01-29 08:48:43'),
(33, 157, 'Laptops', 200, 30000.00, 'Unit', '2026-01-29 08:48:43'),
(34, 160, 'Hotel Banquet', 1, 300000.00, 'Package', '2026-01-29 08:48:43'),
(35, 164, 'Robotic Arms', 10, 500000.00, 'Unit', '2026-01-29 08:48:43'),
(36, 169, 'Luxury Resort Package', 1, 400000.00, 'Package', '2026-01-29 08:48:43');

-- --------------------------------------------------------

--
-- Table structure for table `ya_milestone_risks`
--

CREATE TABLE `ya_milestone_risks` (
  `id` int NOT NULL,
  `milestone_id` int NOT NULL,
  `risk_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `impact` int DEFAULT NULL COMMENT '1-5',
  `probability` int DEFAULT NULL COMMENT '1-5',
  `mitigation_plan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ya_milestone_risks`
--

INSERT INTO `ya_milestone_risks` (`id`, `milestone_id`, `risk_description`, `impact`, `probability`, `mitigation_plan`, `created_at`) VALUES
(11, 121, 'Data migration failure', 5, 4, 'Run parallel runs for 2 weeks. Daily backups.', '2026-01-29 08:48:43'),
(12, 124, 'Ad budget overspend', 3, 3, 'Weekly budget review meetings.', '2026-01-29 08:48:43'),
(13, 126, 'Low applicant quality', 4, 2, 'Use premium LinkedIn recruiter package.', '2026-01-29 08:48:43'),
(14, 142, 'Hidden Liabilities found', 5, 3, 'Detailed forensic accounting', '2026-01-29 08:48:43'),
(15, 139, 'Vendor Insolvency', 4, 2, 'Financial health check required', '2026-01-29 08:48:43'),
(16, 134, 'Non-compliance findings', 5, 2, 'Pre-audit dry run', '2026-01-29 08:48:43'),
(17, 153, 'Low Impressions', 3, 3, 'Adjust audience targeting', '2026-01-29 08:48:43');

-- --------------------------------------------------------

--
-- Table structure for table `ya_user_settings`
--

CREATE TABLE `ya_user_settings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ya_user_settings`
--

INSERT INTO `ya_user_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 6, 'email_notifications', '1', '2026-01-29 01:41:10'),
(2, 6, 'compact_view', '0', '2026-01-29 01:43:44'),
(6, 6, 'start_page', 'dashboard', '2026-01-29 01:45:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cb_audit_logs`
--
ALTER TABLE `cb_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `cb_bookings`
--
ALTER TABLE `cb_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_driver` (`driver_user_id`),
  ADD KEY `idx_approver_user` (`approver_user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_token` (`approval_token`),
  ADD KEY `idx_assigned_car` (`assigned_car_id`),
  ADD KEY `idx_fleet_card` (`fleet_card_id`),
  ADD KEY `idx_status_return` (`status`,`returned_at`),
  ADD KEY `idx_status_start` (`status`,`start_time`),
  ADD KEY `idx_car_status_date` (`assigned_car_id`,`status`,`start_time`),
  ADD KEY `idx_requester` (`user_id`);

--
-- Indexes for table `cb_cars`
--
ALTER TABLE `cb_cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_license_plate` (`license_plate`);

--
-- Indexes for table `cb_fleet_cards`
--
ALTER TABLE `cb_fleet_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `card_number` (`card_number`);

--
-- Indexes for table `core_modules`
--
ALTER TABLE `core_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `core_module_permissions`
--
ALTER TABLE `core_module_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_module_role` (`module_id`,`role_id`),
  ADD KEY `fk_core_module_permissions_role` (`role_id`);

--
-- Indexes for table `dorm_assets`
--
ALTER TABLE `dorm_assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dorm_assets_room` (`room_id`);

--
-- Indexes for table `dorm_audit_logs`
--
ALTER TABLE `dorm_audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dorm_buildings`
--
ALTER TABLE `dorm_buildings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `dorm_employee_types`
--
ALTER TABLE `dorm_employee_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `dorm_floors`
--
ALTER TABLE `dorm_floors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dorm_floors_building` (`building_id`);

--
-- Indexes for table `dorm_invoices`
--
ALTER TABLE `dorm_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `occupancy_id` (`occupancy_id`),
  ADD KEY `idx_payment_id` (`payment_id`);

--
-- Indexes for table `dorm_invoice_items`
--
ALTER TABLE `dorm_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `dorm_maintenance_categories`
--
ALTER TABLE `dorm_maintenance_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dorm_maintenance_requests`
--
ALTER TABLE `dorm_maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `dorm_maintenance_updates`
--
ALTER TABLE `dorm_maintenance_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `dorm_meter_readings`
--
ALTER TABLE `dorm_meter_readings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reading` (`room_id`,`month_cycle`);

--
-- Indexes for table `dorm_occupancies`
--
ALTER TABLE `dorm_occupancies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `dorm_payments`
--
ALTER TABLE `dorm_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dorm_rate_rules`
--
ALTER TABLE `dorm_rate_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dorm_rate_emp` (`employee_type_id`),
  ADD KEY `fk_dorm_rate_room_type` (`room_type_id`),
  ADD KEY `idx_rate_effective` (`effective_from`,`effective_to`);

--
-- Indexes for table `dorm_reservations`
--
ALTER TABLE `dorm_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dorm_resv_room` (`room_id`),
  ADD KEY `fk_dorm_resv_requester` (`requester_id`),
  ADD KEY `fk_dorm_resv_approver` (`approver_id`),
  ADD KEY `idx_resv_status` (`status`),
  ADD KEY `idx_resv_dates` (`check_in`,`check_out`),
  ADD KEY `idx_room_status` (`room_id`,`status`);

--
-- Indexes for table `dorm_rooms`
--
ALTER TABLE `dorm_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_room` (`building_id`,`room_number`);

--
-- Indexes for table `dorm_room_types`
--
ALTER TABLE `dorm_room_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dorm_utility_rates`
--
ALTER TABLE `dorm_utility_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_recipient` (`recipient_email`);

--
-- Indexes for table `hr_news`
--
ALTER TABLE `hr_news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_publish_at` (`publish_at`),
  ADD KEY `idx_expire_at` (`expire_at`),
  ADD KEY `idx_is_pinned` (`is_pinned`),
  ADD KEY `fk_hr_news_user_created` (`created_by`),
  ADD KEY `fk_hr_news_user_updated` (`updated_by`);

--
-- Indexes for table `hr_news_attachments`
--
ALTER TABLE `hr_news_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_news` (`news_id`);

--
-- Indexes for table `hr_services`
--
ALTER TABLE `hr_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `fk_hr_modules_core_module` (`module_id`);

--
-- Indexes for table `iga_applicants`
--
ALTER TABLE `iga_applicants`
  ADD PRIMARY KEY (`applicant_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `iga_questions`
--
ALTER TABLE `iga_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_section_order` (`section_id`,`question_order`);

--
-- Indexes for table `iga_question_options`
--
ALTER TABLE `iga_question_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `idx_question` (`question_id`);

--
-- Indexes for table `iga_remember_me_tokens`
--
ALTER TABLE `iga_remember_me_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `fk_iga_remember_applicant` (`applicant_id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `iga_sections`
--
ALTER TABLE `iga_sections`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `idx_test_order` (`test_id`,`section_order`);

--
-- Indexes for table `iga_tests`
--
ALTER TABLE `iga_tests`
  ADD PRIMARY KEY (`test_id`),
  ADD KEY `idx_published` (`is_published`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_created_by` (`created_by_user_id`),
  ADD KEY `idx_iga_tests_published` (`is_published`);

--
-- Indexes for table `iga_user_answers`
--
ALTER TABLE `iga_user_answers`
  ADD PRIMARY KEY (`user_answer_id`),
  ADD UNIQUE KEY `uk_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `fk_iga_answers_question` (`question_id`),
  ADD KEY `fk_iga_answers_option` (`selected_option_id`);

--
-- Indexes for table `iga_user_section_times`
--
ALTER TABLE `iga_user_section_times`
  ADD PRIMARY KEY (`section_time_id`),
  ADD UNIQUE KEY `uk_attempt_section` (`attempt_id`,`section_id`),
  ADD KEY `fk_iga_section_times_section` (`section_id`);

--
-- Indexes for table `iga_user_test_attempts`
--
ALTER TABLE `iga_user_test_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `fk_iga_attempts_test` (`test_id`),
  ADD KEY `idx_user_test` (`user_id`,`test_id`),
  ADD KEY `idx_completed` (`is_completed`),
  ADD KEY `idx_start_time` (`start_time`),
  ADD KEY `idx_iga_attempts_completed` (`is_completed`,`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_unread` (`user_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_schedule_type` (`schedule_type`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting` (`module_id`,`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `microsoft_id` (`microsoft_id`),
  ADD KEY `fk_user_role` (`role_id`),
  ADD KEY `idx_search` (`username`,`fullname`,`email`);

--
-- Indexes for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `ya_activities`
--
ALTER TABLE `ya_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ya_activities_calendar` (`calendar_id`),
  ADD KEY `fk_ya_activities_key_person` (`key_person_id`),
  ADD KEY `fk_ya_activities_creator` (`created_by`);

--
-- Indexes for table `ya_activity_logs`
--
ALTER TABLE `ya_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ya_logs_activity` (`activity_id`);

--
-- Indexes for table `ya_calendars`
--
ALTER TABLE `ya_calendars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ya_calendars_owner` (`owner_id`);

--
-- Indexes for table `ya_calendar_members`
--
ALTER TABLE `ya_calendar_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_calendar_user` (`calendar_id`,`user_id`),
  ADD KEY `fk_ya_members_user` (`user_id`);

--
-- Indexes for table `ya_calendar_sync`
--
ALTER TABLE `ya_calendar_sync`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_provider` (`user_id`,`provider`);

--
-- Indexes for table `ya_milestones`
--
ALTER TABLE `ya_milestones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ya_milestones_activity` (`activity_id`);

--
-- Indexes for table `ya_milestone_logs`
--
ALTER TABLE `ya_milestone_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `milestone_id` (`milestone_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `ya_milestone_rasci`
--
ALTER TABLE `ya_milestone_rasci`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ya_rasci_milestone` (`milestone_id`),
  ADD KEY `fk_ya_rasci_user` (`user_id`);

--
-- Indexes for table `ya_milestone_resources`
--
ALTER TABLE `ya_milestone_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ya_resources_milestone` (`milestone_id`);

--
-- Indexes for table `ya_milestone_risks`
--
ALTER TABLE `ya_milestone_risks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ya_risks_milestone` (`milestone_id`);

--
-- Indexes for table `ya_user_settings`
--
ALTER TABLE `ya_user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_setting` (`user_id`,`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cb_audit_logs`
--
ALTER TABLE `cb_audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `cb_bookings`
--
ALTER TABLE `cb_bookings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cb_cars`
--
ALTER TABLE `cb_cars`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cb_fleet_cards`
--
ALTER TABLE `cb_fleet_cards`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `core_modules`
--
ALTER TABLE `core_modules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `core_module_permissions`
--
ALTER TABLE `core_module_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=204;

--
-- AUTO_INCREMENT for table `dorm_assets`
--
ALTER TABLE `dorm_assets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dorm_audit_logs`
--
ALTER TABLE `dorm_audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dorm_buildings`
--
ALTER TABLE `dorm_buildings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `dorm_employee_types`
--
ALTER TABLE `dorm_employee_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dorm_floors`
--
ALTER TABLE `dorm_floors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dorm_invoices`
--
ALTER TABLE `dorm_invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dorm_invoice_items`
--
ALTER TABLE `dorm_invoice_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `dorm_maintenance_categories`
--
ALTER TABLE `dorm_maintenance_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `dorm_maintenance_requests`
--
ALTER TABLE `dorm_maintenance_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `dorm_maintenance_updates`
--
ALTER TABLE `dorm_maintenance_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `dorm_meter_readings`
--
ALTER TABLE `dorm_meter_readings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `dorm_occupancies`
--
ALTER TABLE `dorm_occupancies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dorm_payments`
--
ALTER TABLE `dorm_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `dorm_rate_rules`
--
ALTER TABLE `dorm_rate_rules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dorm_reservations`
--
ALTER TABLE `dorm_reservations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dorm_rooms`
--
ALTER TABLE `dorm_rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `dorm_room_types`
--
ALTER TABLE `dorm_room_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dorm_utility_rates`
--
ALTER TABLE `dorm_utility_rates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `hr_news`
--
ALTER TABLE `hr_news`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hr_news_attachments`
--
ALTER TABLE `hr_news_attachments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `hr_services`
--
ALTER TABLE `hr_services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `iga_applicants`
--
ALTER TABLE `iga_applicants`
  MODIFY `applicant_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `iga_questions`
--
ALTER TABLE `iga_questions`
  MODIFY `question_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `iga_question_options`
--
ALTER TABLE `iga_question_options`
  MODIFY `option_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `iga_remember_me_tokens`
--
ALTER TABLE `iga_remember_me_tokens`
  MODIFY `token_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `iga_sections`
--
ALTER TABLE `iga_sections`
  MODIFY `section_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `iga_tests`
--
ALTER TABLE `iga_tests`
  MODIFY `test_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `iga_user_answers`
--
ALTER TABLE `iga_user_answers`
  MODIFY `user_answer_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `iga_user_section_times`
--
ALTER TABLE `iga_user_section_times`
  MODIFY `section_time_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `iga_user_test_attempts`
--
ALTER TABLE `iga_user_test_attempts`
  MODIFY `attempt_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `user_logins`
--
ALTER TABLE `user_logins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

--
-- AUTO_INCREMENT for table `ya_activities`
--
ALTER TABLE `ya_activities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5005;

--
-- AUTO_INCREMENT for table `ya_activity_logs`
--
ALTER TABLE `ya_activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `ya_calendars`
--
ALTER TABLE `ya_calendars`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `ya_calendar_members`
--
ALTER TABLE `ya_calendar_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `ya_calendar_sync`
--
ALTER TABLE `ya_calendar_sync`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ya_milestones`
--
ALTER TABLE `ya_milestones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `ya_milestone_logs`
--
ALTER TABLE `ya_milestone_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `ya_milestone_rasci`
--
ALTER TABLE `ya_milestone_rasci`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

--
-- AUTO_INCREMENT for table `ya_milestone_resources`
--
ALTER TABLE `ya_milestone_resources`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `ya_milestone_risks`
--
ALTER TABLE `ya_milestone_risks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `ya_user_settings`
--
ALTER TABLE `ya_user_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `core_module_permissions`
--
ALTER TABLE `core_module_permissions`
  ADD CONSTRAINT `fk_core_module_permissions_module` FOREIGN KEY (`module_id`) REFERENCES `core_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_core_module_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dorm_assets`
--
ALTER TABLE `dorm_assets`
  ADD CONSTRAINT `fk_dorm_assets_room` FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dorm_floors`
--
ALTER TABLE `dorm_floors`
  ADD CONSTRAINT `fk_dorm_floors_building` FOREIGN KEY (`building_id`) REFERENCES `dorm_buildings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dorm_invoices`
--
ALTER TABLE `dorm_invoices`
  ADD CONSTRAINT `dorm_invoices_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dorm_invoices_ibfk_2` FOREIGN KEY (`occupancy_id`) REFERENCES `dorm_occupancies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dorm_invoice_items`
--
ALTER TABLE `dorm_invoice_items`
  ADD CONSTRAINT `dorm_invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `dorm_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dorm_maintenance_requests`
--
ALTER TABLE `dorm_maintenance_requests`
  ADD CONSTRAINT `dorm_maintenance_requests_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dorm_maintenance_requests_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `dorm_maintenance_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dorm_maintenance_updates`
--
ALTER TABLE `dorm_maintenance_updates`
  ADD CONSTRAINT `dorm_maintenance_updates_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `dorm_maintenance_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dorm_meter_readings`
--
ALTER TABLE `dorm_meter_readings`
  ADD CONSTRAINT `dorm_meter_readings_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dorm_occupancies`
--
ALTER TABLE `dorm_occupancies`
  ADD CONSTRAINT `dorm_occupancies_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dorm_rate_rules`
--
ALTER TABLE `dorm_rate_rules`
  ADD CONSTRAINT `fk_dorm_rate_emp` FOREIGN KEY (`employee_type_id`) REFERENCES `dorm_employee_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dorm_rate_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `dorm_room_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dorm_reservations`
--
ALTER TABLE `dorm_reservations`
  ADD CONSTRAINT `fk_dorm_resv_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dorm_resv_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_dorm_resv_room` FOREIGN KEY (`room_id`) REFERENCES `dorm_rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dorm_rooms`
--
ALTER TABLE `dorm_rooms`
  ADD CONSTRAINT `dorm_rooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `dorm_buildings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hr_news`
--
ALTER TABLE `hr_news`
  ADD CONSTRAINT `fk_hr_news_user_created` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_hr_news_user_updated` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_news_attachments`
--
ALTER TABLE `hr_news_attachments`
  ADD CONSTRAINT `fk_hr_news_attachments_news` FOREIGN KEY (`news_id`) REFERENCES `hr_news` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hr_services`
--
ALTER TABLE `hr_services`
  ADD CONSTRAINT `fk_hr_modules_core_module` FOREIGN KEY (`module_id`) REFERENCES `core_modules` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `iga_questions`
--
ALTER TABLE `iga_questions`
  ADD CONSTRAINT `fk_iga_questions_section` FOREIGN KEY (`section_id`) REFERENCES `iga_sections` (`section_id`) ON DELETE CASCADE;

--
-- Constraints for table `iga_question_options`
--
ALTER TABLE `iga_question_options`
  ADD CONSTRAINT `fk_iga_options_question` FOREIGN KEY (`question_id`) REFERENCES `iga_questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `iga_remember_me_tokens`
--
ALTER TABLE `iga_remember_me_tokens`
  ADD CONSTRAINT `fk_iga_remember_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `iga_applicants` (`applicant_id`) ON DELETE CASCADE;

--
-- Constraints for table `iga_sections`
--
ALTER TABLE `iga_sections`
  ADD CONSTRAINT `fk_iga_sections_test` FOREIGN KEY (`test_id`) REFERENCES `iga_tests` (`test_id`) ON DELETE CASCADE;

--
-- Constraints for table `iga_user_answers`
--
ALTER TABLE `iga_user_answers`
  ADD CONSTRAINT `fk_iga_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `iga_user_test_attempts` (`attempt_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_iga_answers_option` FOREIGN KEY (`selected_option_id`) REFERENCES `iga_question_options` (`option_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_iga_answers_question` FOREIGN KEY (`question_id`) REFERENCES `iga_questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `iga_user_section_times`
--
ALTER TABLE `iga_user_section_times`
  ADD CONSTRAINT `fk_iga_section_times_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `iga_user_test_attempts` (`attempt_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_iga_section_times_section` FOREIGN KEY (`section_id`) REFERENCES `iga_sections` (`section_id`) ON DELETE CASCADE;

--
-- Constraints for table `iga_user_test_attempts`
--
ALTER TABLE `iga_user_test_attempts`
  ADD CONSTRAINT `fk_iga_attempts_test` FOREIGN KEY (`test_id`) REFERENCES `iga_tests` (`test_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `core_modules` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ya_activities`
--
ALTER TABLE `ya_activities`
  ADD CONSTRAINT `fk_ya_activities_calendar` FOREIGN KEY (`calendar_id`) REFERENCES `ya_calendars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ya_activities_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ya_activities_key_person` FOREIGN KEY (`key_person_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ya_activity_logs`
--
ALTER TABLE `ya_activity_logs`
  ADD CONSTRAINT `fk_ya_logs_activity` FOREIGN KEY (`activity_id`) REFERENCES `ya_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ya_calendars`
--
ALTER TABLE `ya_calendars`
  ADD CONSTRAINT `fk_ya_calendars_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ya_calendar_members`
--
ALTER TABLE `ya_calendar_members`
  ADD CONSTRAINT `fk_ya_members_calendar` FOREIGN KEY (`calendar_id`) REFERENCES `ya_calendars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ya_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ya_calendar_sync`
--
ALTER TABLE `ya_calendar_sync`
  ADD CONSTRAINT `ya_calendar_sync_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ya_milestones`
--
ALTER TABLE `ya_milestones`
  ADD CONSTRAINT `fk_ya_milestones_activity` FOREIGN KEY (`activity_id`) REFERENCES `ya_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ya_milestone_logs`
--
ALTER TABLE `ya_milestone_logs`
  ADD CONSTRAINT `ya_milestone_logs_ibfk_1` FOREIGN KEY (`milestone_id`) REFERENCES `ya_milestones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ya_milestone_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ya_milestone_rasci`
--
ALTER TABLE `ya_milestone_rasci`
  ADD CONSTRAINT `fk_ya_rasci_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `ya_milestones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ya_rasci_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ya_milestone_resources`
--
ALTER TABLE `ya_milestone_resources`
  ADD CONSTRAINT `fk_ya_resources_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `ya_milestones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ya_milestone_risks`
--
ALTER TABLE `ya_milestone_risks`
  ADD CONSTRAINT `fk_ya_risks_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `ya_milestones` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
