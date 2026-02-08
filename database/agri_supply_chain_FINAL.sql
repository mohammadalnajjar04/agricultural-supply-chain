-- =========================================================
-- Agri Supply Chain - FINAL DATABASE (Schema + Seed + Dataset)
-- Date: 2026-01-04
-- Includes:
--  - Fresh database schema (all required tables)
--  - Seed: 1 admin + 5 farmers + 5 stores + 5 transporters
--  - Products: 25 (balanced: 5 of each product)
--  - Market Prices Dataset: 300 records (2023-2026) for AI module
--
-- Default passwords:
--   Admin: admin@agri.local  / admin123
--   Farmers/Stores/Transporters: (email listed below) / 123456
-- =========================================================

SET FOREIGN_KEY_CHECKS=0;

CREATE DATABASE IF NOT EXISTS agri_supply_chain
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE agri_supply_chain;

-- Drop child tables first
DROP TABLE IF EXISTS ai_recommendations;
DROP TABLE IF EXISTS transport_requests;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS transporters;
DROP TABLE IF EXISTS stores;
DROP TABLE IF EXISTS farmers;
DROP TABLE IF EXISTS market_prices;
DROP TABLE IF EXISTS admins;

-- --------------------------
-- admins
-- --------------------------
CREATE TABLE admins (
  admin_id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  password VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (admin_id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO admins (admin_id,name,email,password) VALUES
(1,'System Admin','admin@agri.local','$2b$10$yr2Q85gJ2/XUCdfmGWESaO0VwLydqRqLfHnaqM7PUMda7zKzhxCzK');

-- --------------------------
-- farmers
-- --------------------------
CREATE TABLE farmers (
  farmer_id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  location VARCHAR(255) DEFAULT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'approved',
  phone VARCHAR(20) DEFAULT NULL,
  national_id VARCHAR(20) DEFAULT NULL,
  verification_doc VARCHAR(255) DEFAULT NULL,
  farm_name VARCHAR(120) DEFAULT NULL,
  land_area DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (farmer_id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO farmers (farmer_id,name,email,password,location,status,phone,national_id,verification_doc,farm_name,land_area) VALUES
(1,'محمد العزام','farmer1@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Irbid','approved','0791111111','2001000001',NULL,'مزرعة العزام',45.0),(2,'يوسف الشريدة','farmer2@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Ajloun','approved','0792222222','2001000002',NULL,'مزرعة الشريدة',30.0),(3,'أحمد الزعبي','farmer3@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Amman','approved','0793333333','2001000003',NULL,'مزرعة الزعبي',55.5),(4,'خالد العرموطي','farmer4@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Irbid','approved','0794444444','2001000004',NULL,'مزرعة العرموطي',22.0),(5,'رامي الحسن','farmer5@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Amman','approved','0795555555','2001000005',NULL,'مزرعة الحسن',40.0);

-- --------------------------
-- stores
-- --------------------------
CREATE TABLE stores (
  store_id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  location VARCHAR(255) DEFAULT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'approved',
  phone VARCHAR(20) DEFAULT NULL,
  license_no VARCHAR(60) DEFAULT NULL,
  verification_doc VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (store_id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO stores (store_id,name,email,password,location,status,phone,license_no,verification_doc) VALUES
(1,'متجر الخيرات','store1@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Irbid','approved','0796666666','LIC-IRB-1001',NULL),(2,'سوبرماركت الندى','store2@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Ajloun','approved','0797777777','LIC-AJL-1002',NULL),(3,'متجر العاصمة','store3@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Amman','approved','0798888888','LIC-AMM-1003',NULL),(4,'متجر الريف','store4@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Irbid','approved','0799999999','LIC-IRB-1004',NULL),(5,'متجر المزارع','store5@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Amman','approved','0790000000','LIC-AMM-1005',NULL);

-- --------------------------
-- transporters
-- --------------------------
CREATE TABLE transporters (
  transporter_id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  location VARCHAR(255) DEFAULT NULL,
  vehicle_type VARCHAR(100) NOT NULL,
  plate_number VARCHAR(50) DEFAULT NULL,
  vehicle_number VARCHAR(50) DEFAULT NULL,
  vehicle_model VARCHAR(50) DEFAULT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'approved',
  national_id VARCHAR(30) DEFAULT NULL,
  license_no VARCHAR(60) DEFAULT NULL,
  verification_doc VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (transporter_id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO transporters (transporter_id,name,email,password,location,vehicle_type,plate_number,vehicle_number,vehicle_model,phone,status,national_id,license_no,verification_doc) VALUES
(1,'زيد السرحان','trans1@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Irbid','Van','16-12345',NULL,NULL,'0791234500','approved','2002000001','DRV-IRB-2001',NULL),(2,'سامي أبو زيد','trans2@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Ajloun','Small Truck','16-22345',NULL,NULL,'0791234501','approved','2002000002','DRV-AJL-2002',NULL),(3,'ليث الخطيب','trans3@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Amman','Truck','16-32345',NULL,NULL,'0791234502','approved','2002000003','DRV-AMM-2003',NULL),(4,'معاذ الرواشدة','trans4@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Irbid','Pickup','16-42345',NULL,NULL,'0791234503','approved','2002000004','DRV-IRB-2004',NULL),(5,'عمر العساف','trans5@agri.local','$2b$10$A57dTTtttxSozxjCwHRNbeH.u1E9JgCRCotaaPQLDxhfCB5roQGDO','Amman','Refrigerated Truck','16-52345',NULL,NULL,'0791234504','approved','2002000005','DRV-AMM-2005',NULL);

-- --------------------------
-- products (price stored as JOD)
-- --------------------------
CREATE TABLE products (
  product_id INT NOT NULL AUTO_INCREMENT,
  farmer_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  harvest_date DATE DEFAULT NULL,
  farm_location VARCHAR(255) DEFAULT NULL,
  location VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (product_id),
  KEY farmer_id (farmer_id),
  CONSTRAINT products_ibfk_1 FOREIGN KEY (farmer_id) REFERENCES farmers (farmer_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO products (product_id,farmer_id,name,quantity,price,harvest_date,farm_location,location) VALUES
(1,1,'طماطم',750,0.45,'2026-01-03',NULL,'Irbid'),(2,1,'خيار',1148,0.65,'2026-01-03',NULL,'Irbid'),(3,1,'بطاطا',476,0.34,'2026-01-03',NULL,'Irbid'),(4,1,'بصل',1191,0.29,'2026-01-03',NULL,'Irbid'),(5,1,'ليمون',791,0.87,'2026-01-03',NULL,'Irbid'),(6,2,'طماطم',449,0.49,'2026-01-03',NULL,'Ajloun'),(7,2,'خيار',982,0.58,'2026-01-03',NULL,'Ajloun'),(8,2,'بطاطا',658,0.32,'2026-01-03',NULL,'Ajloun'),(9,2,'بصل',683,0.3,'2026-01-03',NULL,'Ajloun'),(10,2,'ليمون',853,0.82,'2026-01-03',NULL,'Ajloun'),(11,3,'طماطم',1108,0.42,'2026-01-03',NULL,'Amman'),(12,3,'خيار',787,0.6,'2026-01-03',NULL,'Amman'),(13,3,'بطاطا',568,0.35,'2026-01-03',NULL,'Amman'),(14,3,'بصل',1196,0.28,'2026-01-03',NULL,'Amman'),(15,3,'ليمون',672,0.79,'2026-01-03',NULL,'Amman'),(16,4,'طماطم',876,0.41,'2026-01-03',NULL,'Irbid'),(17,4,'خيار',963,0.6,'2026-01-03',NULL,'Irbid'),(18,4,'بطاطا',364,0.37,'2026-01-03',NULL,'Irbid'),(19,4,'بصل',333,0.25,'2026-01-03',NULL,'Irbid'),(20,4,'ليمون',377,0.79,'2026-01-03',NULL,'Irbid'),(21,5,'طماطم',1191,0.47,'2026-01-03',NULL,'Amman'),(22,5,'خيار',1174,0.61,'2026-01-03',NULL,'Amman'),(23,5,'بطاطا',1145,0.36,'2026-01-03',NULL,'Amman'),(24,5,'بصل',813,0.31,'2026-01-03',NULL,'Amman'),(25,5,'ليمون',586,0.83,'2026-01-03',NULL,'Amman');

-- --------------------------
-- orders
-- --------------------------
CREATE TABLE orders (
  order_id INT NOT NULL AUTO_INCREMENT,
  store_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  order_date DATE NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  delivery_fee DECIMAL(10,2) DEFAULT NULL,
  platform_fee DECIMAL(10,2) DEFAULT NULL,
  driver_earning DECIMAL(10,2) DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'pending',
  rating INT DEFAULT NULL,
  PRIMARY KEY (order_id),
  KEY store_id (store_id),
  KEY product_id (product_id),
  CONSTRAINT orders_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (store_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT orders_ibfk_2 FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- No default orders (clean start)

-- --------------------------
-- transport_requests
-- --------------------------
CREATE TABLE transport_requests (
  request_id INT NOT NULL AUTO_INCREMENT,
  order_id INT DEFAULT NULL,
  farmer_id INT NOT NULL,
  product_id INT NOT NULL,
  transporter_id INT DEFAULT NULL,
  store_id INT DEFAULT NULL,
  quantity INT NOT NULL,
  total_weight DECIMAL(10,2) DEFAULT NULL,
  transport_date DATE NOT NULL,
  status ENUM('pending','accepted','in_progress','delivered') DEFAULT 'pending',
  request_date DATE NOT NULL DEFAULT (curdate()),
  delivery_date DATE DEFAULT NULL,
  delivery_fee DECIMAL(10,2) DEFAULT NULL,
  platform_fee DECIMAL(10,2) DEFAULT NULL,
  driver_earning DECIMAL(10,2) DEFAULT NULL,
  distance_type ENUM('same','near','far') DEFAULT NULL,
  notes TEXT,
  PRIMARY KEY (request_id),
  KEY order_id (order_id),
  KEY product_id (product_id),
  KEY transporter_id (transporter_id),
  KEY store_id (store_id),
  KEY fk_request_farmer (farmer_id),
  CONSTRAINT fk_transport_order FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_request_farmer FOREIGN KEY (farmer_id) REFERENCES farmers (farmer_id),
  CONSTRAINT transport_requests_ibfk_1 FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT transport_requests_ibfk_2 FOREIGN KEY (transporter_id) REFERENCES transporters (transporter_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT transport_requests_ibfk_3 FOREIGN KEY (store_id) REFERENCES stores (store_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------
-- ai_recommendations (optional log)
-- --------------------------
CREATE TABLE ai_recommendations (
  rec_id INT NOT NULL AUTO_INCREMENT,
  farmer_id INT DEFAULT NULL,
  transporter_id INT DEFAULT NULL,
  store_id INT DEFAULT NULL,
  product_id INT DEFAULT NULL,
  recommendation_type VARCHAR(100) NOT NULL,
  target_price DECIMAL(10,2) DEFAULT NULL,
  date_generated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  content TEXT,
  vehicle_type VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (rec_id),
  KEY farmer_id (farmer_id),
  KEY transporter_id (transporter_id),
  KEY store_id (store_id),
  KEY product_id (product_id),
  CONSTRAINT ai_recommendations_ibfk_1 FOREIGN KEY (farmer_id) REFERENCES farmers (farmer_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT ai_recommendations_ibfk_2 FOREIGN KEY (transporter_id) REFERENCES transporters (transporter_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT ai_recommendations_ibfk_3 FOREIGN KEY (store_id) REFERENCES stores (store_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT ai_recommendations_ibfk_4 FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- Market Prices Dataset (for AI) - qirsh/kg in DB
-- =========================================================

-- Sample Market Prices Dataset (300 records)
-- Years: 2023-2026
-- Products: طماطم, خيار, بطاطا, بصل, ليمون
-- Markets: Irbid, Amman, Ajloun

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS market_prices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_name VARCHAR(100) NOT NULL,
  market VARCHAR(50) NOT NULL,
  date DATE NOT NULL,
  price_qirsh_per_kg INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_prod_market_date (product_name, market, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

TRUNCATE TABLE market_prices;

INSERT INTO market_prices (product_name, market, date, price_qirsh_per_kg) VALUES
  ('طماطم','Irbid','2023-01-05',48),
  ('طماطم','Amman','2023-01-05',48),
  ('طماطم','Ajloun','2023-01-05',48),
  ('خيار','Irbid','2023-01-05',63),
  ('خيار','Amman','2023-01-05',72),
  ('خيار','Ajloun','2023-01-05',69),
  ('بطاطا','Irbid','2023-01-05',35),
  ('بطاطا','Amman','2023-01-05',34),
  ('بطاطا','Ajloun','2023-01-05',34),
  ('بصل','Irbid','2023-01-05',25),
  ('بصل','Amman','2023-01-05',27),
  ('بصل','Ajloun','2023-01-05',27),
  ('ليمون','Irbid','2023-01-05',88),
  ('ليمون','Amman','2023-01-05',98),
  ('ليمون','Ajloun','2023-01-05',99),
  ('طماطم','Irbid','2023-03-05',40),
  ('طماطم','Amman','2023-03-05',42),
  ('طماطم','Ajloun','2023-03-05',42),
  ('خيار','Irbid','2023-03-05',57),
  ('خيار','Amman','2023-03-05',56),
  ('خيار','Ajloun','2023-03-05',59),
  ('بطاطا','Irbid','2023-03-05',31),
  ('بطاطا','Amman','2023-03-05',32),
  ('بطاطا','Ajloun','2023-03-05',30),
  ('بصل','Irbid','2023-03-05',25),
  ('بصل','Amman','2023-03-05',25),
  ('بصل','Ajloun','2023-03-05',23),
  ('ليمون','Irbid','2023-03-05',79),
  ('ليمون','Amman','2023-03-05',93),
  ('ليمون','Ajloun','2023-03-05',87),
  ('طماطم','Irbid','2023-06-05',29),
  ('طماطم','Amman','2023-06-05',31),
  ('طماطم','Ajloun','2023-06-05',29),
  ('خيار','Irbid','2023-06-05',45),
  ('خيار','Amman','2023-06-05',45),
  ('خيار','Ajloun','2023-06-05',44),
  ('بطاطا','Irbid','2023-06-05',30),
  ('بطاطا','Amman','2023-06-05',31),
  ('بطاطا','Ajloun','2023-06-05',31),
  ('بصل','Irbid','2023-06-05',23),
  ('بصل','Amman','2023-06-05',25),
  ('بصل','Ajloun','2023-06-05',22),
  ('ليمون','Irbid','2023-06-05',69),
  ('ليمون','Amman','2023-06-05',75),
  ('ليمون','Ajloun','2023-06-05',70),
  ('طماطم','Irbid','2023-09-05',32),
  ('طماطم','Amman','2023-09-05',34),
  ('طماطم','Ajloun','2023-09-05',34),
  ('خيار','Irbid','2023-09-05',48),
  ('خيار','Amman','2023-09-05',50),
  ('خيار','Ajloun','2023-09-05',49),
  ('بطاطا','Irbid','2023-09-05',30),
  ('بطاطا','Amman','2023-09-05',33),
  ('بطاطا','Ajloun','2023-09-05',34),
  ('بصل','Irbid','2023-09-05',25),
  ('بصل','Amman','2023-09-05',27),
  ('بصل','Ajloun','2023-09-05',25),
  ('ليمون','Irbid','2023-09-05',81),
  ('ليمون','Amman','2023-09-05',82),
  ('ليمون','Ajloun','2023-09-05',81),
  ('طماطم','Irbid','2023-12-05',48),
  ('طماطم','Amman','2023-12-05',50),
  ('طماطم','Ajloun','2023-12-05',48),
  ('خيار','Irbid','2023-12-05',64),
  ('خيار','Amman','2023-12-05',70),
  ('خيار','Ajloun','2023-12-05',67),
  ('بطاطا','Irbid','2023-12-05',33),
  ('بطاطا','Amman','2023-12-05',35),
  ('بطاطا','Ajloun','2023-12-05',35),
  ('بصل','Irbid','2023-12-05',27),
  ('بصل','Amman','2023-12-05',29),
  ('بصل','Ajloun','2023-12-05',30),
  ('ليمون','Irbid','2023-12-05',106),
  ('ليمون','Amman','2023-12-05',107),
  ('ليمون','Ajloun','2023-12-05',107),
  ('طماطم','Irbid','2024-01-05',48),
  ('طماطم','Amman','2024-01-05',55),
  ('طماطم','Ajloun','2024-01-05',50),
  ('خيار','Irbid','2024-01-05',65),
  ('خيار','Amman','2024-01-05',70),
  ('خيار','Ajloun','2024-01-05',70),
  ('بطاطا','Irbid','2024-01-05',33),
  ('بطاطا','Amman','2024-01-05',37),
  ('بطاطا','Ajloun','2024-01-05',37),
  ('بصل','Irbid','2024-01-05',27),
  ('بصل','Amman','2024-01-05',28),
  ('بصل','Ajloun','2024-01-05',30),
  ('ليمون','Irbid','2024-01-05',97),
  ('ليمون','Amman','2024-01-05',99),
  ('ليمون','Ajloun','2024-01-05',95),
  ('طماطم','Irbid','2024-03-05',40),
  ('طماطم','Amman','2024-03-05',45),
  ('طماطم','Ajloun','2024-03-05',45),
  ('خيار','Irbid','2024-03-05',56),
  ('خيار','Amman','2024-03-05',58),
  ('خيار','Ajloun','2024-03-05',58),
  ('بطاطا','Irbid','2024-03-05',33),
  ('بطاطا','Amman','2024-03-05',33),
  ('بطاطا','Ajloun','2024-03-05',34),
  ('بصل','Irbid','2024-03-05',26),
  ('بصل','Amman','2024-03-05',25),
  ('بصل','Ajloun','2024-03-05',26),
  ('ليمون','Irbid','2024-03-05',87),
  ('ليمون','Amman','2024-03-05',92),
  ('ليمون','Ajloun','2024-03-05',86),
  ('طماطم','Irbid','2024-06-05',29),
  ('طماطم','Amman','2024-06-05',30),
  ('طماطم','Ajloun','2024-06-05',30),
  ('خيار','Irbid','2024-06-05',44),
  ('خيار','Amman','2024-06-05',50),
  ('خيار','Ajloun','2024-06-05',48),
  ('بطاطا','Irbid','2024-06-05',29),
  ('بطاطا','Amman','2024-06-05',32),
  ('بطاطا','Ajloun','2024-06-05',29),
  ('بصل','Irbid','2024-06-05',24),
  ('بصل','Amman','2024-06-05',26),
  ('بصل','Ajloun','2024-06-05',24),
  ('ليمون','Irbid','2024-06-05',75),
  ('ليمون','Amman','2024-06-05',80),
  ('ليمون','Ajloun','2024-06-05',73),
  ('طماطم','Irbid','2024-09-05',35),
  ('طماطم','Amman','2024-09-05',37),
  ('طماطم','Ajloun','2024-09-05',37),
  ('خيار','Irbid','2024-09-05',49),
  ('خيار','Amman','2024-09-05',50),
  ('خيار','Ajloun','2024-09-05',50),
  ('بطاطا','Irbid','2024-09-05',31),
  ('بطاطا','Amman','2024-09-05',37),
  ('بطاطا','Ajloun','2024-09-05',35),
  ('بصل','Irbid','2024-09-05',27),
  ('بصل','Amman','2024-09-05',27),
  ('بصل','Ajloun','2024-09-05',25),
  ('ليمون','Irbid','2024-09-05',85),
  ('ليمون','Amman','2024-09-05',92),
  ('ليمون','Ajloun','2024-09-05',80),
  ('طماطم','Irbid','2024-12-05',47),
  ('طماطم','Amman','2024-12-05',48),
  ('طماطم','Ajloun','2024-12-05',50),
  ('خيار','Irbid','2024-12-05',66),
  ('خيار','Amman','2024-12-05',66),
  ('خيار','Ajloun','2024-12-05',67),
  ('بطاطا','Irbid','2024-12-05',36),
  ('بطاطا','Amman','2024-12-05',37),
  ('بطاطا','Ajloun','2024-12-05',39),
  ('بصل','Irbid','2024-12-05',28),
  ('بصل','Amman','2024-12-05',30),
  ('بصل','Ajloun','2024-12-05',30),
  ('ليمون','Irbid','2024-12-05',107),
  ('ليمون','Amman','2024-12-05',109),
  ('ليمون','Ajloun','2024-12-05',106),
  ('طماطم','Irbid','2025-01-05',53),
  ('طماطم','Amman','2025-01-05',55),
  ('طماطم','Ajloun','2025-01-05',52),
  ('خيار','Irbid','2025-01-05',69),
  ('خيار','Amman','2025-01-05',71),
  ('خيار','Ajloun','2025-01-05',69),
  ('بطاطا','Irbid','2025-01-05',34),
  ('بطاطا','Amman','2025-01-05',38),
  ('بطاطا','Ajloun','2025-01-05',35),
  ('بصل','Irbid','2025-01-05',27),
  ('بصل','Amman','2025-01-05',29),
  ('بصل','Ajloun','2025-01-05',29),
  ('ليمون','Irbid','2025-01-05',96),
  ('ليمون','Amman','2025-01-05',113),
  ('ليمون','Ajloun','2025-01-05',108),
  ('طماطم','Irbid','2025-03-05',40),
  ('طماطم','Amman','2025-03-05',45),
  ('طماطم','Ajloun','2025-03-05',45),
  ('خيار','Irbid','2025-03-05',56),
  ('خيار','Amman','2025-03-05',60),
  ('خيار','Ajloun','2025-03-05',64),
  ('بطاطا','Irbid','2025-03-05',32),
  ('بطاطا','Amman','2025-03-05',34),
  ('بطاطا','Ajloun','2025-03-05',34),
  ('بصل','Irbid','2025-03-05',26),
  ('بصل','Amman','2025-03-05',26),
  ('بصل','Ajloun','2025-03-05',25),
  ('ليمون','Irbid','2025-03-05',87),
  ('ليمون','Amman','2025-03-05',94),
  ('ليمون','Ajloun','2025-03-05',91),
  ('طماطم','Irbid','2025-06-05',30),
  ('طماطم','Amman','2025-06-05',33),
  ('طماطم','Ajloun','2025-06-05',33),
  ('خيار','Irbid','2025-06-05',43),
  ('خيار','Amman','2025-06-05',48),
  ('خيار','Ajloun','2025-06-05',46),
  ('بطاطا','Irbid','2025-06-05',32),
  ('بطاطا','Amman','2025-06-05',32),
  ('بطاطا','Ajloun','2025-06-05',30),
  ('بصل','Irbid','2025-06-05',24),
  ('بصل','Amman','2025-06-05',26),
  ('بصل','Ajloun','2025-06-05',24),
  ('ليمون','Irbid','2025-06-05',73),
  ('ليمون','Amman','2025-06-05',86),
  ('ليمون','Ajloun','2025-06-05',78),
  ('طماطم','Irbid','2025-09-05',37),
  ('طماطم','Amman','2025-09-05',38),
  ('طماطم','Ajloun','2025-09-05',35),
  ('خيار','Irbid','2025-09-05',53),
  ('خيار','Amman','2025-09-05',57),
  ('خيار','Ajloun','2025-09-05',55),
  ('بطاطا','Irbid','2025-09-05',35),
  ('بطاطا','Amman','2025-09-05',38),
  ('بطاطا','Ajloun','2025-09-05',33),
  ('بصل','Irbid','2025-09-05',26),
  ('بصل','Amman','2025-09-05',28),
  ('بصل','Ajloun','2025-09-05',27),
  ('ليمون','Irbid','2025-09-05',79),
  ('ليمون','Amman','2025-09-05',89),
  ('ليمون','Ajloun','2025-09-05',92),
  ('طماطم','Irbid','2025-12-05',47),
  ('طماطم','Amman','2025-12-05',54),
  ('طماطم','Ajloun','2025-12-05',50),
  ('خيار','Irbid','2025-12-05',66),
  ('خيار','Amman','2025-12-05',75),
  ('خيار','Ajloun','2025-12-05',73),
  ('بطاطا','Irbid','2025-12-05',37),
  ('بطاطا','Amman','2025-12-05',41),
  ('بطاطا','Ajloun','2025-12-05',36),
  ('بصل','Irbid','2025-12-05',28),
  ('بصل','Amman','2025-12-05',33),
  ('بصل','Ajloun','2025-12-05',31),
  ('ليمون','Irbid','2025-12-05',108),
  ('ليمون','Amman','2025-12-05',119),
  ('ليمون','Ajloun','2025-12-05',106),
  ('طماطم','Irbid','2026-01-05',52),
  ('طماطم','Amman','2026-01-05',56),
  ('طماطم','Ajloun','2026-01-05',56),
  ('خيار','Irbid','2026-01-05',68),
  ('خيار','Amman','2026-01-05',81),
  ('خيار','Ajloun','2026-01-05',70),
  ('بطاطا','Irbid','2026-01-05',35),
  ('بطاطا','Amman','2026-01-05',39),
  ('بطاطا','Ajloun','2026-01-05',38),
  ('بصل','Irbid','2026-01-05',28),
  ('بصل','Amman','2026-01-05',29),
  ('بصل','Ajloun','2026-01-05',31),
  ('ليمون','Irbid','2026-01-05',99),
  ('ليمون','Amman','2026-01-05',112),
  ('ليمون','Ajloun','2026-01-05',108),
  ('طماطم','Irbid','2026-03-05',43),
  ('طماطم','Amman','2026-03-05',48),
  ('طماطم','Ajloun','2026-03-05',46),
  ('خيار','Irbid','2026-03-05',63),
  ('خيار','Amman','2026-03-05',62),
  ('خيار','Ajloun','2026-03-05',64),
  ('بطاطا','Irbid','2026-03-05',32),
  ('بطاطا','Amman','2026-03-05',35),
  ('بطاطا','Ajloun','2026-03-05',35),
  ('بصل','Irbid','2026-03-05',25),
  ('بصل','Amman','2026-03-05',27),
  ('بصل','Ajloun','2026-03-05',28),
  ('ليمون','Irbid','2026-03-05',85),
  ('ليمون','Amman','2026-03-05',97),
  ('ليمون','Ajloun','2026-03-05',99),
  ('طماطم','Irbid','2026-06-05',32),
  ('طماطم','Amman','2026-06-05',31),
  ('طماطم','Ajloun','2026-06-05',31),
  ('خيار','Irbid','2026-06-05',45),
  ('خيار','Amman','2026-06-05',53),
  ('خيار','Ajloun','2026-06-05',50),
  ('بطاطا','Irbid','2026-06-05',32),
  ('بطاطا','Amman','2026-06-05',33),
  ('بطاطا','Ajloun','2026-06-05',31),
  ('بصل','Irbid','2026-06-05',26),
  ('بصل','Amman','2026-06-05',27),
  ('بصل','Ajloun','2026-06-05',26),
  ('ليمون','Irbid','2026-06-05',82),
  ('ليمون','Amman','2026-06-05',85),
  ('ليمون','Ajloun','2026-06-05',76),
  ('طماطم','Irbid','2026-09-05',38),
  ('طماطم','Amman','2026-09-05',38),
  ('طماطم','Ajloun','2026-09-05',38),
  ('خيار','Irbid','2026-09-05',55),
  ('خيار','Amman','2026-09-05',53),
  ('خيار','Ajloun','2026-09-05',51),
  ('بطاطا','Irbid','2026-09-05',33),
  ('بطاطا','Amman','2026-09-05',37),
  ('بطاطا','Ajloun','2026-09-05',35),
  ('بصل','Irbid','2026-09-05',28),
  ('بصل','Amman','2026-09-05',30),
  ('بصل','Ajloun','2026-09-05',27),
  ('ليمون','Irbid','2026-09-05',87),
  ('ليمون','Amman','2026-09-05',90),
  ('ليمون','Ajloun','2026-09-05',89),
  ('طماطم','Irbid','2026-12-05',52),
  ('طماطم','Amman','2026-12-05',56),
  ('طماطم','Ajloun','2026-12-05',49),
  ('خيار','Irbid','2026-12-05',68),
  ('خيار','Amman','2026-12-05',72),
  ('خيار','Ajloun','2026-12-05',67),
  ('بطاطا','Irbid','2026-12-05',39),
  ('بطاطا','Amman','2026-12-05',41),
  ('بطاطا','Ajloun','2026-12-05',38),
  ('بصل','Irbid','2026-12-05',31),
  ('بصل','Amman','2026-12-05',33),
  ('بصل','Ajloun','2026-12-05',31),
  ('ليمون','Irbid','2026-12-05',104),
  ('ليمون','Amman','2026-12-05',113),
  ('ليمون','Ajloun','2026-12-05',120);

SET FOREIGN_KEY_CHECKS = 1;


SET FOREIGN_KEY_CHECKS=1;
