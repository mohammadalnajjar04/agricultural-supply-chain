-- database_final.sql
-- Generated for project submission


CREATE DATABASE IF NOT EXISTS `agri_supply_chain` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `agri_supply_chain`;


SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `admins`;

DROP TABLE IF EXISTS `ai_recommendations`;

DROP TABLE IF EXISTS `transport_requests`;

DROP TABLE IF EXISTS `orders`;

DROP TABLE IF EXISTS `products`;

DROP TABLE IF EXISTS `stores`;

DROP TABLE IF EXISTS `transporters`;

DROP TABLE IF EXISTS `farmers`;

SET FOREIGN_KEY_CHECKS=1;


CREATE TABLE `admins` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default admin account
-- Email: admin@agri.local
-- Password: Admin@123
INSERT INTO `admins` (`name`, `email`, `password`) VALUES
('Administrator', 'admin@agri.local', '$2y$10$C5Yls/ldkuVJwg2Flaj9l.a2alwYOZ341EFl0uPg5fAnNgBc4HkwC');


CREATE TABLE `farmers` (
  `farmer_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `national_id` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `farm_name` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `land_area` decimal(10,2) DEFAULT NULL,
  `verification_doc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`farmer_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `transporters` (
  `transporter_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vehicle_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `plate_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vehicle_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vehicle_model` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `national_id` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `license_no` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `verification_doc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`transporter_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `stores` (
  `store_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `license_no` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `verification_doc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`store_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `farmer_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `harvest_date` date DEFAULT NULL,
  `farm_location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  KEY `farmer_id` (`farmer_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `store_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT 1,
  `order_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `rating` int DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `store_id` (`store_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `transport_requests` (
  `request_id` int NOT NULL AUTO_INCREMENT,
  `farmer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `transporter_id` int DEFAULT NULL,
  `store_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `transport_date` date NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `request_date` date NOT NULL DEFAULT (curdate()),
  `delivery_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`request_id`),
  KEY `product_id` (`product_id`),
  KEY `transporter_id` (`transporter_id`),
  KEY `store_id` (`store_id`),
  KEY `fk_request_farmer` (`farmer_id`),
  CONSTRAINT `fk_request_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`),
  CONSTRAINT `transport_requests_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `transport_requests_ibfk_2` FOREIGN KEY (`transporter_id`) REFERENCES `transporters` (`transporter_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `transport_requests_ibfk_3` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `ai_recommendations` (
  `rec_id` int NOT NULL AUTO_INCREMENT,
  `farmer_id` int DEFAULT NULL,
  `transporter_id` int DEFAULT NULL,
  `store_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `recommendation_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `target_price` decimal(10,2) DEFAULT NULL,
  `date_generated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `content` text COLLATE utf8mb4_general_ci,
  `vehicle_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`rec_id`),
  KEY `farmer_id` (`farmer_id`),
  KEY `transporter_id` (`transporter_id`),
  KEY `store_id` (`store_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `ai_recommendations_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `ai_recommendations_ibfk_2` FOREIGN KEY (`transporter_id`) REFERENCES `transporters` (`transporter_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `ai_recommendations_ibfk_3` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `ai_recommendations_ibfk_4` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
