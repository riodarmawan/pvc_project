-- PVC POS database dump
-- Generated: 2026-06-30 20:18:14 via Laravel PDO
-- Database: final_pvc (MariaDB)
-- Restore: mysql -u root -p < final_pvc.sql  (after CREATE DATABASE final_pvc)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;


-- ----------------------------
-- Table: `approvals`
-- ----------------------------
DROP TABLE IF EXISTS `approvals`;
CREATE TABLE `approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `object_type` enum('PO','TRANSFER','MR') NOT NULL,
  `object_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `approver_id` bigint(20) unsigned NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `note` text DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_appr_req_by` (`requested_by`),
  KEY `fk_appr_approver` (`approver_id`),
  KEY `idx_appr_object` (`object_type`,`object_id`),
  KEY `idx_appr_status` (`status`),
  KEY `idx_appr_decided` (`decided_at`),
  CONSTRAINT `fk_appr_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_appr_req_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `attachments`
-- ----------------------------
DROP TABLE IF EXISTS `attachments`;
CREATE TABLE `attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `object_type` varchar(50) NOT NULL,
  `object_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(50) NOT NULL,
  `path` varchar(255) NOT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_obj` (`object_type`,`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `audit_logs`
-- ----------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ref_type` varchar(50) DEFAULT NULL,
  `ref_id` bigint(20) unsigned DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `audit_logs` (`id`,`event`,`user_id`,`ref_type`,`ref_id`,`payload`,`created_at`) VALUES
(1,'STOCK_ADJUST_DIRECT',1,'PRODUCT',13,'{\"location_id\":2,\"branch_id\":1,\"reason\":\"salah hitung\",\"old_qty\":0,\"new_qty\":100}','2026-06-13 17:51:52'),
(2,'STOCK_ADJUST',1,'PRODUCT',10,'{\"location_id\":2,\"branch_id\":1,\"reason\":\"opname\",\"old_qty\":0,\"new_qty\":200,\"delta\":200,\"hpp\":36000,\"delta_value\":7200000}','2026-06-13 18:43:19'),
(3,'PROJECT_FINALIZE',3,'PROJECT',1,'{\"cart\":{\"materials\":[{\"row_id\":\"ae169c1e-e5de-47c4-a082-c798a510a5c9\",\"product_id\":13,\"name\":\"Glossy\",\"uom_id\":1,\"uom\":\"MTR\",\"qty\":1,\"price\":48000,\"from_location_id\":null}],\"leftovers\":[]},\"services\":[{\"row_id\":\"ba3b2b99-0c1b-453d-a9d6-eb8e21a3d40f\",\"name\":\"Pasang Wallpaper\",\"price\":150000}]}','2026-06-13 20:36:45'),
(4,'PRODUCT_CREATED',3,'PRODUCT',81,'{\"sku\":\"QA-TEST-001\",\"name\":\"Produk QA Test\",\"category_id\":\"6\",\"uom_id\":\"1\",\"hpp\":10000,\"selling_price\":15000,\"notes\":\"\",\"material\":null,\"series\":null,\"pattern_code\":null,\"finish\":null,\"length_cm\":null,\"width_mm\":null,\"thickness_mm\":null,\"barcode\":null,\"is_active\":1}','2026-06-13 20:41:57'),
(5,'PROJECT_FINALIZE',3,'PROJECT',2,'{\"cart\":{\"materials\":[{\"row_id\":\"629998a9-0a42-4335-8a5b-4b8b551a3c27\",\"product_id\":13,\"name\":\"Glossy\",\"uom_id\":1,\"uom\":\"MTR\",\"qty\":2,\"price\":48000,\"from_location_id\":null}],\"leftovers\":[]},\"services\":[]}','2026-06-13 20:49:24'),
(6,'PROJECT_FINALIZE',3,'PROJECT',3,'{\"cart\":{\"materials\":[],\"leftovers\":[{\"row_id\":\"3f924d46-ed83-42a4-a008-7521073eac98\",\"piece_id\":2,\"product_id\":13,\"name\":\"Glossy\",\"available_m\":5,\"used_length_m\":5,\"price_m\":62400,\"price\":0}]},\"services\":[]}','2026-06-13 20:51:30'),
(7,'PRODUCT_CREATED',3,'PRODUCT',82,'{\"sku\":\"QA-NEW-001\",\"name\":\"Produk QA Test\",\"category_id\":10,\"uom_id\":\"1\",\"hpp\":50000,\"selling_price\":75000,\"notes\":\"\",\"material\":null,\"series\":null,\"pattern_code\":null,\"finish\":null,\"length_cm\":null,\"width_mm\":null,\"thickness_mm\":null,\"barcode\":null,\"is_active\":1}','2026-06-14 01:26:39'),
(8,'PROJECT_FINALIZE',3,'PROJECT',6,'{\"cart\":{\"materials\":[],\"leftovers\":[]},\"services\":[]}','2026-06-14 01:46:44'),
(9,'PROJECT_FINALIZE',3,'PROJECT',7,'{\"cart\":{\"materials\":[],\"leftovers\":[]},\"services\":[{\"row_id\":\"f0a7f1bd-94ae-4670-a224-4eee8c6b1a11\",\"name\":\"Tukang Pasang\",\"price\":100000},{\"row_id\":\"638cab95-37c4-4d89-bfca-b86b8ab88dbb\",\"name\":\"Tukang Pasang\",\"price\":100000}]}','2026-06-14 01:49:21'),
(10,'PRODUCT_CREATED',3,'PRODUCT',83,'{\"sku\":\"KSR-TEST-001\",\"name\":\"Test Produk Kasir\",\"category_id\":\"1\",\"uom_id\":\"1\",\"hpp\":50000,\"selling_price\":75000,\"notes\":\"\",\"material\":null,\"series\":null,\"pattern_code\":null,\"finish\":null,\"length_cm\":null,\"width_mm\":null,\"thickness_mm\":null,\"barcode\":null,\"is_active\":1}','2026-06-14 02:09:29'),
(11,'PURCHASE_DIRECT_CREATED',3,'PO',1,'{\"supplier_id\":1,\"branch_id\":1,\"invoice_no\":\"INV-TEST-001\",\"items\":[{\"product_id\":1,\"qty\":10,\"price\":8000}]}','2026-06-28 16:12:02');

-- ----------------------------
-- Table: `branches`
-- ----------------------------
DROP TABLE IF EXISTS `branches`;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `branches` (`id`,`code`,`name`,`address`,`phone`,`is_active`) VALUES
(1,'DEPOK','Depok','Jl. Raya Depok, Jawa Barat',NULL,1);

-- ----------------------------
-- Table: `cache`
-- ----------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `cache_locks`
-- ----------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `cash_movements`
-- ----------------------------
DROP TABLE IF EXISTS `cash_movements`;
CREATE TABLE `cash_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `direction` enum('IN','OUT') NOT NULL,
  `category` varchar(30) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_branch_created` (`branch_id`,`created_at`),
  KEY `idx_direction` (`direction`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cash_movements` (`id`,`branch_id`,`user_id`,`direction`,`category`,`amount`,`memo`,`created_at`) VALUES
(1,1,3,'OUT','BBM','101400.00','jajan','2026-06-13 18:03:38'),
(2,1,3,'OUT','BBM','50000.00','QA Test - BBM motor delivery','2026-06-13 20:06:07'),
(3,1,3,'OUT','PARKIR','10000.00','QA Test - Parkir','2026-06-13 20:07:26'),
(4,1,3,'IN','TARIK_BANK','500000.00','QA Test - Tarik dari bank','2026-06-13 20:08:01'),
(5,1,3,'OUT','SETOR_BANK','200000.00','QA Test - Setor ke bank','2026-06-13 20:08:15'),
(6,1,3,'OUT','MAKAN','25000.00','QA Test - Makan siang','2026-06-13 20:26:51'),
(7,1,3,'IN','TARIK_BANK','300000.00','QA Test - Tarik dari bank','2026-06-13 20:27:23'),
(8,1,3,'OUT','BBM','50000.00','','2026-06-28 16:10:53');

-- ----------------------------
-- Table: `chart_of_accounts`
-- ----------------------------
DROP TABLE IF EXISTS `chart_of_accounts`;
CREATE TABLE `chart_of_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('ASSET','LIABILITY','EQUITY','REVENUE','EXPENSE') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chart_of_accounts_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `chart_of_accounts` (`id`,`code`,`name`,`type`,`is_active`,`created_at`,`updated_at`) VALUES
(1,'1100','Kas','ASSET',1,'2026-06-13 17:59:11','2026-06-13 17:59:11'),
(2,'1200','Piutang Usaha','ASSET',1,'2026-06-13 17:59:11','2026-06-13 17:59:11'),
(3,'1300','Persediaan Barang','ASSET',1,'2026-06-13 17:59:11','2026-06-13 17:59:11'),
(4,'2100','Utang Dagang','LIABILITY',1,'2026-06-13 17:59:11','2026-06-13 17:59:11'),
(5,'3100','Modal Usaha','EQUITY',1,'2026-06-13 17:59:11','2026-06-13 17:59:11'),
(6,'3200','Laba Ditahan','EQUITY',1,'2026-06-13 17:59:11','2026-06-13 17:59:11'),
(7,'4100','Penjualan Barang','REVENUE',1,'2026-06-13 17:59:11','2026-06-13 17:59:11'),
(8,'4200','Pendapatan Jasa','REVENUE',1,'2026-06-13 17:59:11','2026-06-13 17:59:11'),
(9,'4900','Retur Penjualan','REVENUE',1,'2026-06-13 17:59:11','2026-06-13 17:59:11'),
(10,'5100','Harga Pokok Penjualan','EXPENSE',1,'2026-06-13 17:59:11','2026-06-13 17:59:11'),
(11,'5200','Beban Operasional','EXPENSE',1,'2026-06-13 17:59:11','2026-06-13 17:59:11'),
(12,'1110','Bank','ASSET',1,'2026-06-13 17:59:11','2026-06-13 17:59:11');

-- ----------------------------
-- Table: `customers`
-- ----------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `customers` (`id`,`name`,`phone`,`address`) VALUES
(1,'rio','111','dasda'),
(2,'QA Test','08123456789',NULL),
(3,'Test Customer Project','08120001111',NULL),
(4,'Test Smoketest','08120002222',NULL);

-- ----------------------------
-- Table: `goods_receipt_lines`
-- ----------------------------
DROP TABLE IF EXISTS `goods_receipt_lines`;
CREATE TABLE `goods_receipt_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grn_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` int(10) unsigned NOT NULL,
  `qty_received` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_grnl_uom` (`uom_id`),
  KEY `idx_grnl_grn` (`grn_id`),
  KEY `idx_grnl_product` (`product_id`),
  CONSTRAINT `fk_grnl_header` FOREIGN KEY (`grn_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_grnl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_grnl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `goods_receipt_lines` (`id`,`grn_id`,`product_id`,`uom_id`,`qty_received`) VALUES
(1,1,1,1,'10.00');

-- ----------------------------
-- Table: `goods_receipts`
-- ----------------------------
DROP TABLE IF EXISTS `goods_receipts`;
CREATE TABLE `goods_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `po_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `received_by` bigint(20) unsigned NOT NULL,
  `received_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('DRAFT','DONE') NOT NULL DEFAULT 'DONE',
  PRIMARY KEY (`id`),
  KEY `fk_grn_user` (`received_by`),
  KEY `idx_grn_po` (`po_id`),
  KEY `idx_grn_branch` (`branch_id`),
  KEY `idx_grn_status` (`status`),
  CONSTRAINT `fk_grn_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_grn_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_grn_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `goods_receipts` (`id`,`po_id`,`branch_id`,`received_by`,`received_at`,`status`) VALUES
(1,1,1,3,'2026-06-28 16:12:02','DONE');

-- ----------------------------
-- Table: `inbox_items`
-- ----------------------------
DROP TABLE IF EXISTS `inbox_items`;
CREATE TABLE `inbox_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `object_type` enum('PO','TRANSFER','MR','SYSTEM') NOT NULL,
  `object_id` bigint(20) unsigned DEFAULT NULL,
  `subject` varchar(160) NOT NULL,
  `status` enum('PENDING','READ','DONE','ARCHIVED') NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inbox_user_status` (`user_id`,`status`),
  KEY `idx_inbox_object` (`object_type`,`object_id`),
  KEY `idx_inbox_created` (`created_at`),
  CONSTRAINT `fk_inbox_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `journal_entries`
-- ----------------------------
DROP TABLE IF EXISTS `journal_entries`;
CREATE TABLE `journal_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_no` varchar(30) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `is_posted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journal_entries_entry_no_unique` (`entry_no`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `journal_entries` (`id`,`entry_no`,`date`,`description`,`source_type`,`source_id`,`branch_id`,`created_by`,`is_posted`,`created_at`,`updated_at`) VALUES
(10,'JRN-20260613-0001','2026-06-13','fakk',NULL,NULL,NULL,1,1,'2026-06-13 16:51:45','2026-06-13 16:51:45'),
(11,'JRN-20260613-0002','2026-06-13','Penjualan POS #1','POS_SALE',1,1,3,1,'2026-06-13 17:18:38','2026-06-13 17:18:38'),
(12,'JRN-20260613-0003','2026-06-13','HPP POS #1','POS_SALE',1,1,3,1,'2026-06-13 17:18:38','2026-06-13 17:18:38'),
(13,'JRN-20260613-0004','2026-06-13','Penjualan POS #2','POS_SALE',2,1,3,1,'2026-06-13 17:53:58','2026-06-13 17:53:58'),
(14,'JRN-20260613-0005','2026-06-13','HPP POS #2','POS_SALE',2,1,3,1,'2026-06-13 17:53:58','2026-06-13 17:53:58'),
(15,'JRN-20260613-0006','2026-06-13','Beban: BBM','EXPENSE',NULL,1,3,1,'2026-06-13 18:03:38','2026-06-13 18:03:38'),
(16,'JRN-20260613-0007','2026-06-13','Penyesuaian stok naik: opname','STOCK_ADJUSTMENT',NULL,1,1,1,'2026-06-13 18:43:19','2026-06-13 18:43:19'),
(17,'JRN-20260613-0008','2026-06-13','Beban: BBM','EXPENSE',NULL,1,3,1,'2026-06-13 20:06:07','2026-06-13 20:06:07'),
(18,'JRN-20260613-0009','2026-06-13','Beban: PARKIR','EXPENSE',NULL,1,3,1,'2026-06-13 20:07:26','2026-06-13 20:07:26'),
(19,'JRN-20260613-0010','2026-06-13','Tarik dari bank: QA Test - Tarik dari bank','CASH_ADJUST',NULL,1,3,1,'2026-06-13 20:08:01','2026-06-13 20:08:01'),
(20,'JRN-20260613-0011','2026-06-13','Setor ke bank: QA Test - Setor ke bank','CASH_ADJUST',NULL,1,3,1,'2026-06-13 20:08:15','2026-06-13 20:08:15'),
(21,'JRN-20260613-0012','2026-06-13','Penjualan POS #3','POS_SALE',3,1,3,1,'2026-06-13 20:25:12','2026-06-13 20:25:12'),
(22,'JRN-20260613-0013','2026-06-13','HPP POS #3','POS_SALE',3,1,3,1,'2026-06-13 20:25:12','2026-06-13 20:25:12'),
(23,'JRN-20260613-0014','2026-06-13','Beban: MAKAN','EXPENSE',NULL,1,3,1,'2026-06-13 20:26:51','2026-06-13 20:26:51'),
(24,'JRN-20260613-0015','2026-06-13','Tarik dari bank: QA Test - Tarik dari bank','CASH_ADJUST',NULL,1,3,1,'2026-06-13 20:27:23','2026-06-13 20:27:23'),
(25,'JRN-20260613-0016','2026-06-13','Penjualan POS #4','POS_SALE',4,1,1,1,'2026-06-13 20:38:57','2026-06-13 20:38:57'),
(26,'JRN-20260613-0017','2026-06-13','HPP POS #4','POS_SALE',4,1,1,1,'2026-06-13 20:38:57','2026-06-13 20:38:57'),
(27,'JRN-20260613-0018','2026-06-13','Penjualan POS #5','POS_SALE',5,1,3,1,'2026-06-13 20:49:24','2026-06-13 20:49:24'),
(28,'JRN-20260613-0019','2026-06-13','HPP POS #5','POS_SALE',5,1,3,1,'2026-06-13 20:49:24','2026-06-13 20:49:24'),
(29,'JRN-20260613-0020','2026-06-13','Penjualan POS #6','POS_SALE',6,1,3,1,'2026-06-13 20:51:30','2026-06-13 20:51:30'),
(30,'JRN-20260613-0021','2026-06-13','Penjualan POS #7','POS_SALE',7,1,1,1,'2026-06-13 21:14:41','2026-06-13 21:14:41'),
(31,'JRN-20260613-0022','2026-06-13','HPP POS #7','POS_SALE',7,1,1,1,'2026-06-13 21:14:41','2026-06-13 21:14:41'),
(32,'JRN-20260613-0023','2026-06-13','Penjualan POS #8','POS_SALE',8,1,1,1,'2026-06-13 21:15:48','2026-06-13 21:15:48'),
(33,'JRN-20260613-0024','2026-06-13','HPP POS #8','POS_SALE',8,1,1,1,'2026-06-13 21:15:48','2026-06-13 21:15:48'),
(36,'JRN-20260614-0001','2026-06-14','Penjualan POS #10','POS_SALE',10,1,3,1,'2026-06-14 01:18:30','2026-06-14 01:18:30'),
(37,'JRN-20260614-0002','2026-06-14','HPP POS #10','POS_SALE',10,1,3,1,'2026-06-14 01:18:30','2026-06-14 01:18:30'),
(38,'JE-FIX-001','2026-06-13','HPP POS #6','POS',6,1,3,1,'2026-06-14 01:32:08','2026-06-14 01:32:08'),
(40,'JRN-20260614-0004','2026-06-14','Penjualan POS #12','POS_SALE',12,1,3,1,'2026-06-14 01:49:21','2026-06-14 01:49:21'),
(43,'JRN-20260628-0001','2026-06-28','Retur POS #1','POS_REFUND',1,1,3,1,'2026-06-28 15:11:56','2026-06-28 15:11:56'),
(44,'JRN-20260628-0002','2026-06-28','Retur ke persediaan #1','POS_REFUND',1,1,3,1,'2026-06-28 15:11:56','2026-06-28 15:11:56'),
(45,'JRN-20260628-0003','2026-06-28','Penjualan POS #13','POS_SALE',13,1,3,1,'2026-06-28 16:10:17','2026-06-28 16:10:17'),
(46,'JRN-20260628-0004','2026-06-28','HPP POS #13','POS_SALE',13,1,3,1,'2026-06-28 16:10:17','2026-06-28 16:10:17'),
(47,'JRN-20260628-0005','2026-06-28','Penjualan POS #14','POS_SALE',14,1,3,1,'2026-06-28 16:10:32','2026-06-28 16:10:32'),
(48,'JRN-20260628-0006','2026-06-28','HPP POS #14','POS_SALE',14,1,3,1,'2026-06-28 16:10:32','2026-06-28 16:10:32'),
(49,'JRN-20260628-0007','2026-06-28','Beban: BBM','EXPENSE',NULL,1,3,1,'2026-06-28 16:10:53','2026-06-28 16:10:53'),
(50,'JRN-20260628-0008','2026-06-28','Retur POS #2','POS_REFUND',2,1,3,1,'2026-06-28 16:11:06','2026-06-28 16:11:06'),
(51,'JRN-20260628-0009','2026-06-28','Retur ke persediaan #2','POS_REFUND',2,1,3,1,'2026-06-28 16:11:06','2026-06-28 16:11:06'),
(52,'JRN-20260628-0010','2026-06-28','Pembelian PO #1','PURCHASE',1,1,3,1,'2026-06-28 16:12:02','2026-06-28 16:12:02');

-- ----------------------------
-- Table: `journal_entry_lines`
-- ----------------------------
DROP TABLE IF EXISTS `journal_entry_lines`;
CREATE TABLE `journal_entry_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `debit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `memo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_entry_lines_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `journal_entry_lines_account_id_foreign` (`account_id`),
  CONSTRAINT `journal_entry_lines_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`),
  CONSTRAINT `journal_entry_lines_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `journal_entry_lines` (`id`,`journal_entry_id`,`account_id`,`debit`,`credit`,`memo`) VALUES
(19,10,1,'10000.00','0.00',NULL),
(20,10,8,'0.00','10000.00',NULL),
(21,11,1,'101400.00','0.00','Pembayaran dari customer'),
(22,11,7,'0.00','101400.00','Penjualan barang'),
(23,12,10,'78000.00','0.00','Harga pokok penjualan'),
(24,12,3,'0.00','78000.00','Pengurangan persediaan'),
(25,13,1,'811200.00','0.00','Pembayaran dari customer'),
(26,13,7,'0.00','811200.00','Penjualan barang'),
(27,14,10,'624000.00','0.00','Harga pokok penjualan'),
(28,14,3,'0.00','624000.00','Pengurangan persediaan'),
(29,15,11,'101400.00','0.00','jajan'),
(30,15,1,'0.00','101400.00','Pembayaran kas'),
(31,16,3,'7200000.00','0.00','Stok +200 item'),
(32,16,5,'0.00','7200000.00','Koreksi HPP'),
(33,17,11,'50000.00','0.00','QA Test - BBM motor delivery'),
(34,17,1,'0.00','50000.00','Pembayaran kas'),
(35,18,11,'10000.00','0.00','QA Test - Parkir'),
(36,18,1,'0.00','10000.00','Pembayaran kas'),
(37,19,1,'500000.00','0.00','Kas naik'),
(38,19,12,'0.00','500000.00','Bank turun'),
(39,20,12,'200000.00','0.00','Bank naik'),
(40,20,1,'0.00','200000.00','Kas turun'),
(41,21,1,'67600.00','0.00','Pembayaran dari customer'),
(42,21,7,'0.00','67600.00','Penjualan barang'),
(43,22,10,'52000.00','0.00','Harga pokok penjualan'),
(44,22,3,'0.00','52000.00','Pengurangan persediaan'),
(45,23,11,'25000.00','0.00','QA Test - Makan siang'),
(46,23,1,'0.00','25000.00','Pembayaran kas'),
(47,24,1,'300000.00','0.00','Kas naik'),
(48,24,12,'0.00','300000.00','Bank turun'),
(49,25,1,'198000.00','0.00','Pembayaran dari customer'),
(50,25,7,'0.00','198000.00','Penjualan barang'),
(51,26,10,'48000.00','0.00','Harga pokok penjualan'),
(52,26,3,'0.00','48000.00','Pengurangan persediaan'),
(53,27,1,'96000.00','0.00','Pembayaran dari customer'),
(54,27,7,'0.00','96000.00','Penjualan barang'),
(55,28,10,'96000.00','0.00','Harga pokok penjualan'),
(56,28,3,'0.00','96000.00','Pengurangan persediaan'),
(57,29,1,'312000.00','0.00','Pembayaran dari customer'),
(58,29,7,'0.00','312000.00','Penjualan barang'),
(59,30,1,'171000.00','0.00','Pembayaran dari customer'),
(60,30,7,'0.00','171000.00','Penjualan barang'),
(61,31,10,'96000.00','0.00','Harga pokok penjualan'),
(62,31,3,'0.00','96000.00','Pengurangan persediaan'),
(63,32,1,'124800.00','0.00','Pembayaran dari customer'),
(64,32,7,'0.00','124800.00','Penjualan barang'),
(65,33,10,'56000.00','0.00','Harga pokok penjualan'),
(66,33,3,'0.00','56000.00','Pengurangan persediaan'),
(71,36,1,'40000.00','0.00','Pembayaran CASH'),
(72,36,2,'22400.00','0.00','Pembayaran TRANSFER'),
(73,36,7,'0.00','62400.00','Penjualan barang'),
(74,37,10,'48000.00','0.00','Harga pokok penjualan'),
(75,37,3,'0.00','48000.00','Pengurangan persediaan'),
(76,38,10,'240000.00','0.00','Harga pokok penjualan'),
(77,38,3,'0.00','240000.00','Pengurangan persediaan'),
(80,40,1,'200000.00','0.00','Pembayaran dari customer'),
(81,40,7,'0.00','200000.00','Penjualan barang'),
(86,43,9,'312000.00','0.00','Retur penjualan'),
(87,43,1,'0.00','312000.00','Pengembalian kas'),
(88,44,3,'240000.00','0.00','Barang kembali ke persediaan'),
(89,44,10,'0.00','240000.00','Pembalikan HPP'),
(90,45,1,'78000.00','0.00','Pembayaran CASH'),
(91,45,7,'0.00','78000.00','Penjualan barang'),
(92,46,10,'60000.00','0.00','Harga pokok penjualan'),
(93,46,3,'0.00','60000.00','Pengurangan persediaan'),
(94,47,1,'76000.00','0.00','Pembayaran CASH'),
(95,47,7,'0.00','76000.00','Penjualan barang'),
(96,48,10,'60000.00','0.00','Harga pokok penjualan'),
(97,48,3,'0.00','60000.00','Pengurangan persediaan'),
(98,49,11,'50000.00','0.00',''),
(99,49,1,'0.00','50000.00','Pembayaran kas'),
(100,50,9,'78000.00','0.00','Retur penjualan'),
(101,50,1,'0.00','78000.00','Pengembalian kas'),
(102,51,3,'60000.00','0.00','Barang kembali ke persediaan'),
(103,51,10,'0.00','60000.00','Pembalikan HPP'),
(104,52,3,'80000.00','0.00','Penerimaan barang'),
(105,52,4,'0.00','80000.00','Utang ke supplier');

-- ----------------------------
-- Table: `leftover_piece_consumptions`
-- ----------------------------
DROP TABLE IF EXISTS `leftover_piece_consumptions`;
CREATE TABLE `leftover_piece_consumptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `piece_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `used_m` decimal(10,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lpc_piece` (`piece_id`),
  KEY `idx_lpc_project` (`project_id`),
  CONSTRAINT `fk_lpc_piece` FOREIGN KEY (`piece_id`) REFERENCES `leftover_pieces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lpc_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `leftover_piece_consumptions` (`id`,`piece_id`,`project_id`,`used_m`,`created_at`) VALUES
(1,2,3,'5.000','2026-06-13 20:51:30'),
(2,3,5,'1.000','2026-06-13 21:15:47');

-- ----------------------------
-- Table: `leftover_piece_usages`
-- ----------------------------
DROP TABLE IF EXISTS `leftover_piece_usages`;
CREATE TABLE `leftover_piece_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `leftover_piece_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `length_used_m` decimal(18,3) NOT NULL,
  `used_at` datetime NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lpu_piece` (`leftover_piece_id`),
  KEY `idx_lpu_project` (`project_id`),
  CONSTRAINT `fk_lpu_piece` FOREIGN KEY (`leftover_piece_id`) REFERENCES `leftover_pieces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lpu_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `leftover_pieces`
-- ----------------------------
DROP TABLE IF EXISTS `leftover_pieces`;
CREATE TABLE `leftover_pieces` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `length_m` decimal(18,3) NOT NULL,
  `cost_per_m` decimal(12,2) DEFAULT NULL,
  `condition` enum('GOOD','DAMAGED') NOT NULL DEFAULT 'GOOD',
  `source_type` enum('RETURN','ADJUST','PROJECT_RETURN') NOT NULL DEFAULT 'RETURN',
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `reserved_project_id` bigint(20) unsigned DEFAULT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lp_available` (`branch_id`,`product_id`,`consumed_at`),
  KEY `fk_lp_product` (`product_id`),
  KEY `idx_lp_reserved` (`reserved_project_id`),
  CONSTRAINT `fk_lp_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lp_reserved_project` FOREIGN KEY (`reserved_project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `leftover_pieces` (`id`,`branch_id`,`product_id`,`length_m`,`cost_per_m`,`condition`,`source_type`,`source_id`,`reserved_project_id`,`consumed_at`,`created_at`) VALUES
(1,1,13,'2.000','8000.00','GOOD','PROJECT_RETURN',1,NULL,NULL,'2026-06-13 20:39:44'),
(2,1,13,'5.000','8000.00','GOOD','PROJECT_RETURN',2,3,'2026-06-13 20:51:30','2026-06-13 20:50:12'),
(3,1,13,'1.000','8000.00','GOOD','PROJECT_RETURN',4,5,'2026-06-13 21:15:47','2026-06-13 21:15:21');

-- ----------------------------
-- Table: `material_request_lines`
-- ----------------------------
DROP TABLE IF EXISTS `material_request_lines`;
CREATE TABLE `material_request_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mr_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` int(10) unsigned NOT NULL,
  `qty_requested` decimal(18,2) NOT NULL,
  `qty_approved` decimal(18,2) NOT NULL DEFAULT 0.00,
  `qty_issued` decimal(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_mrl_uom` (`uom_id`),
  KEY `idx_mrl_mr` (`mr_id`),
  KEY `idx_mrl_product` (`product_id`),
  CONSTRAINT `fk_mrl_mr` FOREIGN KEY (`mr_id`) REFERENCES `material_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mrl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mrl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `material_requests`
-- ----------------------------
DROP TABLE IF EXISTS `material_requests`;
CREATE TABLE `material_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `status` enum('PENDING','APPROVED','ISSUED','REJECTED','DONE') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_mr_user` (`requested_by`),
  KEY `idx_mr_project` (`project_id`),
  KEY `idx_mr_status` (`status`),
  CONSTRAINT `fk_mr_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mr_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `material_return_lines`
-- ----------------------------
DROP TABLE IF EXISTS `material_return_lines`;
CREATE TABLE `material_return_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_return_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` int(10) unsigned NOT NULL,
  `qty_returned` decimal(18,2) NOT NULL,
  `condition` enum('GOOD','DAMAGED','LOST') NOT NULL DEFAULT 'GOOD',
  `writeoff_qty` decimal(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_mretl_uom` (`uom_id`),
  KEY `idx_mretl_header` (`material_return_id`),
  KEY `idx_mretl_product` (`product_id`),
  CONSTRAINT `fk_mretl_header` FOREIGN KEY (`material_return_id`) REFERENCES `material_returns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mretl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mretl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `material_returns`
-- ----------------------------
DROP TABLE IF EXISTS `material_returns`;
CREATE TABLE `material_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `returned_by` bigint(20) unsigned NOT NULL,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `status` enum('PENDING','PROCESSED') NOT NULL DEFAULT 'PENDING',
  `returned_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_mret_returned_by` (`returned_by`),
  KEY `fk_mret_processed_by` (`processed_by`),
  KEY `idx_mret_project` (`project_id`),
  KEY `idx_mret_status` (`status`),
  CONSTRAINT `fk_mret_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mret_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mret_returned_by` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `migrations`
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES
(1,'0001_01_01_000003_create_chart_of_accounts_table',1),
(2,'0001_01_01_000004_create_journal_entries_table',2),
(3,'0001_01_01_000005_create_journal_entry_lines_table',3),
(4,'2026_06_13_000001_add_hpp_to_products_table',4),
(5,'2026_06_13_000002_add_selling_price_to_products_table',5),
(6,'2026_06_13_205719_add_cost_per_m_to_leftover_pieces_table',6),
(7,'2026_06_28_000001_add_discount_to_pos_sales_table',7);

-- ----------------------------
-- Table: `pos_payments`
-- ----------------------------
DROP TABLE IF EXISTS `pos_payments`;
CREATE TABLE `pos_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pos_sale_id` bigint(20) unsigned NOT NULL,
  `method` enum('CASH','CARD','QR','TRANSFER') NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `ref_no` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pay_sale` (`pos_sale_id`),
  KEY `idx_pay_method` (`method`),
  CONSTRAINT `fk_pay_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pos_payments` (`id`,`pos_sale_id`,`method`,`amount`,`ref_no`) VALUES
(1,1,'CASH','101400.00','1111'),
(2,2,'CARD','811200.00',NULL),
(3,3,'CASH','152.10',NULL),
(4,3,'CASH','67447.90',NULL),
(5,4,'CASH','198000.00',NULL),
(6,5,'CASH','96000.00',NULL),
(7,6,'CASH','312000.00',NULL),
(8,7,'CASH','171000.00',NULL),
(9,8,'CASH','124800.00',NULL),
(12,10,'CASH','40000.00',NULL),
(13,10,'TRANSFER','22400.00',NULL),
(15,12,'CASH','200000.00',NULL),
(16,13,'CASH','78000.00',NULL),
(17,14,'CASH','76000.00',NULL);

-- ----------------------------
-- Table: `pos_refunds`
-- ----------------------------
DROP TABLE IF EXISTS `pos_refunds`;
CREATE TABLE `pos_refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ref_approver` (`approved_by`),
  KEY `idx_ref_sale` (`sale_id`),
  CONSTRAINT `fk_ref_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ref_sale` FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pos_refunds` (`id`,`sale_id`,`approved_by`,`reason`,`created_at`) VALUES
(1,6,3,'dasda','2026-06-28 15:11:56'),
(2,13,3,'barang rusak','2026-06-28 16:11:06');

-- ----------------------------
-- Table: `pos_sale_lines`
-- ----------------------------
DROP TABLE IF EXISTS `pos_sale_lines`;
CREATE TABLE `pos_sale_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pos_sale_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` int(10) unsigned NOT NULL,
  `qty` decimal(18,3) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_psl_uom` (`uom_id`),
  KEY `idx_psl_sale` (`pos_sale_id`),
  KEY `idx_psl_product` (`product_id`),
  CONSTRAINT `fk_psl_header` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_psl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_psl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pos_sale_lines` (`id`,`pos_sale_id`,`product_id`,`uom_id`,`qty`,`price`,`discount`,`subtotal`) VALUES
(1,1,65,1,'1.000','101400.00','0.00','101400.00'),
(2,2,75,1,'8.000','101400.00','0.00','811200.00'),
(3,3,59,1,'1.000','67600.00','0.00','67600.00'),
(6,4,80,2,'1.000','150000.00','0.00','150000.00'),
(7,4,13,1,'1.000','48000.00','0.00','48000.00'),
(8,5,13,1,'2.000','48000.00','0.00','96000.00'),
(9,6,13,1,'5.000','62400.00','0.00','312000.00'),
(10,7,13,1,'2.000','48000.00','0.00','96000.00'),
(11,7,80,2,'1.000','75000.00','0.00','75000.00'),
(12,8,13,1,'1.000','62400.00','0.00','62400.00'),
(13,8,13,1,'1.000','62400.00','0.00','62400.00'),
(15,10,13,1,'1.000','62400.00','0.00','62400.00'),
(16,12,80,2,'1.000','100000.00','0.00','100000.00'),
(17,12,80,2,'1.000','100000.00','0.00','100000.00'),
(18,13,1,1,'2.000','39000.00','0.00','78000.00'),
(19,14,1,1,'2.000','39000.00','0.00','78000.00');

-- ----------------------------
-- Table: `pos_sales`
-- ----------------------------
DROP TABLE IF EXISTS `pos_sales`;
CREATE TABLE `pos_sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `cashier_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `sale_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('DRAFT','PAID','VOID','REFUND') NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ps_customer` (`customer_id`),
  KEY `idx_ps_branch` (`branch_id`),
  KEY `idx_ps_cashier` (`cashier_id`),
  KEY `idx_ps_status` (`status`),
  KEY `idx_ps_datetime` (`sale_datetime`),
  KEY `idx_ps_project` (`project_id`),
  CONSTRAINT `fk_ps_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ps_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ps_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ps_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pos_sales` (`id`,`branch_id`,`cashier_id`,`customer_id`,`project_id`,`sale_datetime`,`status`,`total`,`discount`,`notes`) VALUES
(1,1,3,1,NULL,'2026-06-13 17:18:38','PAID','101400.00','0.00','CHANGE=98600'),
(2,1,3,NULL,NULL,'2026-06-13 17:53:58','PAID','811200.00','0.00',NULL),
(3,1,3,NULL,NULL,'2026-06-13 20:25:12','PAID','67600.00','0.00','CHANGE=152.10000000001'),
(4,1,3,1,1,'2026-06-13 20:36:45','PAID','198000.00','0.00','Billing Proyek #PRJ-260613-0001'),
(5,1,3,1,2,'2026-06-13 20:49:24','PAID','96000.00','0.00','Billing Proyek #PRJ-260613-0002'),
(6,1,3,1,3,'2026-06-13 20:51:30','REFUND','312000.00','0.00','Billing Proyek #PRJ-260613-0003'),
(7,1,3,1,4,'2026-06-13 21:14:41','PAID','171000.00','0.00','Billing Proyek #PRJ-260613-0004'),
(8,1,3,1,5,'2026-06-13 21:15:47','PAID','124800.00','0.00','Billing Proyek #PRJ-260613-0005'),
(10,1,3,1,NULL,'2026-06-14 01:18:30','PAID','62400.00','0.00',NULL),
(12,1,3,NULL,7,'2026-06-14 01:49:21','PAID','200000.00','0.00','Billing Proyek #PRJ-260614-0002'),
(13,1,3,NULL,NULL,'2026-06-28 16:10:17','REFUND','78000.00','0.00',NULL),
(14,1,3,NULL,NULL,'2026-06-28 16:10:32','PAID','76000.00','2000.00',NULL);

-- ----------------------------
-- Table: `pos_service_lines`
-- ----------------------------
DROP TABLE IF EXISTS `pos_service_lines`;
CREATE TABLE `pos_service_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pos_sale_id` bigint(20) unsigned NOT NULL,
  `service_name` varchar(160) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_psv_sale` (`pos_sale_id`),
  CONSTRAINT `fk_psv_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `product_categories`
-- ----------------------------
DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE `product_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_pc_parent` (`parent_id`),
  CONSTRAINT `fk_pc_parent` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `product_categories` (`id`,`code`,`name`,`parent_id`) VALUES
(1,'SC','SI CANTIK',NULL),
(2,'SUMA','SUMA',NULL),
(3,'TC','TIAN CHENG',NULL),
(4,'WPC','WPC',NULL),
(5,'LH','LIST & HOLO',NULL),
(6,'TACO','TACO',NULL),
(7,'KB','KANG BANG',NULL),
(8,'RIJEK','RIJEK',NULL),
(9,'SERV','Services',NULL),
(10,'QA','Kategori QA Test',NULL);

-- ----------------------------
-- Table: `products`
-- ----------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) NOT NULL,
  `name` varchar(160) NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `uom_id` int(10) unsigned NOT NULL,
  `hpp` decimal(18,2) DEFAULT NULL,
  `selling_price` decimal(18,2) DEFAULT NULL,
  `track_by_meter` tinyint(1) NOT NULL DEFAULT 0,
  `material` varchar(30) DEFAULT NULL,
  `series` varchar(60) DEFAULT NULL,
  `pattern_code` varchar(60) DEFAULT NULL,
  `finish` varchar(40) DEFAULT NULL,
  `length_cm` int(11) DEFAULT NULL,
  `width_mm` int(11) DEFAULT NULL,
  `thickness_mm` decimal(6,2) DEFAULT NULL,
  `barcode` varchar(64) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_products_cat` (`category_id`),
  KEY `idx_products_uom` (`uom_id`),
  KEY `idx_products_track_by_meter` (`track_by_meter`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prod_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`id`,`sku`,`name`,`category_id`,`uom_id`,`hpp`,`selling_price`,`track_by_meter`,`material`,`series`,`pattern_code`,`finish`,`length_cm`,`width_mm`,`thickness_mm`,`barcode`,`is_active`,`notes`) VALUES
(1,'SC 5100','SI CANTIK',1,1,'28166.67','39000.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - SI CANTIK - 4mtr'),
(2,'SC 5001','SI CANTIK',1,1,'30000.00','39000.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - SI CANTIK - 4mtr'),
(3,'SC 5008B','SI CANTIK',1,1,'30000.00','39000.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - SI CANTIK - 4mtr'),
(4,'SC 5036','SI CANTIK',1,1,'30000.00','39000.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - SI CANTIK - 4mtr'),
(5,'SC 5107','SI CANTIK',1,1,'30000.00','39000.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - SI CANTIK - 4mtr'),
(6,'SC 5048','SI CANTIK',1,1,'30000.00','39000.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - SI CANTIK - 4mtr'),
(7,'SC 5001-6','SI CANTIK',1,1,'45000.00','58500.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - SI CANTIK - 6mtr'),
(8,'SC 802','SI CANTIK',1,1,'30000.00','39000.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - SI CANTIK - 4mtr'),
(9,'D 2001','SUMA',2,1,'32000.00','41600.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - SUMA - 4mtr'),
(10,'7068','Putih Dove',3,1,'36000.00','46800.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - TIAN CHENG - 4mtr'),
(11,'EP 8060 M2','8mm',3,1,'57000.00','74100.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TIAN CHENG - 6mtr'),
(12,'SCL 733','Dove',3,1,'54000.00','70200.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TIAN CHENG - 6mtr'),
(13,'SC 702 NS','Glossy',3,1,'48000.00','62400.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TIAN CHENG - 6mtr'),
(14,'SCL 730 NAT','Dove',3,1,'54000.00','70200.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TIAN CHENG - 6mtr'),
(15,'SCL 732 NAT','Dove',3,1,'54000.00','70200.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TIAN CHENG - 6mtr'),
(16,'SCL 731','Dove',3,1,'54000.00','70200.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TIAN CHENG - 6mtr'),
(17,'SC 701 NS','Glossy',3,1,'45000.00','58500.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TIAN CHENG - 6mtr'),
(18,'160-03','WPC',4,1,'49000.00','63700.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - WPC - 3mtr'),
(19,'160-06','WPC',4,1,'49000.00','63700.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - WPC - 3mtr'),
(20,'160-07','WPC',4,1,'49000.00','63700.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - WPC - 3mtr'),
(21,'160-15','WPC',4,1,'49000.00','63700.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - WPC - 3mtr'),
(22,'160-19','WPC',4,1,'49000.00','63700.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - WPC - 3mtr'),
(23,'160-22','WPC',4,1,'49000.00','63700.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - WPC - 3mtr'),
(24,'A COKLAT','LIST',5,1,'0.00',NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(25,'TD 02 BG','LIST',5,1,'24000.00','31200.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(26,'TD 01','LIST',5,1,'24000.00','31200.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(27,'TD 03','LIST',5,1,'24000.00','31200.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(28,'TD 02','LIST',5,1,'24000.00','31200.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(29,'TD 04','LIST',5,1,'24000.00','31200.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(30,'TD 01 BG','LIST',5,1,'24000.00','31200.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(31,'TD 05','LIST',5,1,'24000.00','31200.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(32,'H 8033','HOLO',5,1,'35000.00','45500.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 3mtr'),
(33,'H 8033-6','HOLO',5,1,'65000.00','84500.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 6mtr'),
(34,'H 8030','HOLO',5,1,'35000.00','45500.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 3mtr'),
(35,'H 8030-6','HOLO',5,1,'65000.00','84500.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 6mtr'),
(36,'H 8027','HOLO',5,1,'35000.00','45500.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 3mtr'),
(37,'H 8025','HOLO',5,1,'35000.00','45500.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 3mtr'),
(38,'S 8025','SIKU',5,1,'35000.00','45500.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 3mtr'),
(39,'S 8030','SIKU',5,1,'35000.00','45500.00',1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 3mtr'),
(40,'S 8025-6','SIKU',5,1,'65000.00','84500.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 6mtr'),
(41,'S 8030-6','SIKU',5,1,'65000.00','84500.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 6mtr'),
(42,'H 8030-4','HOLO',5,1,'45000.00','58500.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 4mtr'),
(43,'H 8025-4','HOLO',5,1,'45000.00','58500.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 4mtr'),
(44,'S 8025-4','SIKU',5,1,'45000.00','58500.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 4mtr'),
(45,'S 8030-4','SIKU',5,1,'45000.00','58500.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 4mtr'),
(46,'LIST A','LIST A',5,1,'22000.00','28600.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(47,'LIST B','LIST B',5,1,'22000.00','28600.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(48,'LIST C','LIST C',5,1,'22000.00','28600.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(49,'SILEN A','SILEN',5,1,'12000.00','15600.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(50,'SILEN B','SILEN',5,1,'12000.00','15600.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(51,'SILEN C','SILEN',5,1,'12000.00','15600.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(52,'SKRUP','SKRUP',5,1,'150.00','195.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(53,'SEALANT P','Sealant Putih',5,1,'17000.00','22100.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(54,'SEALANT B','Sealant Bening',5,1,'17000.00','22100.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(55,'PAKU BETON 2','Paku Beton',5,1,'40000.00','52000.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 2in'),
(56,'PAKU BETON 1','Paku Beton',5,1,'40000.00','52000.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - 1in'),
(57,'KLIP WPC','Klip WPC',5,1,'200.00','260.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Imported from Excel - LIST & HOLO - '),
(58,'SK 001','Snow Birch',6,1,'50000.00','65000.00',1,NULL,NULL,NULL,NULL,500,NULL,NULL,NULL,1,'Imported from Excel - TACO - 5 mtr'),
(59,'HK 005','Iron Walnut',6,1,'52000.00','67600.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - TACO - 4 mtr'),
(60,'HK 005 NP','Iron Walnut Nat Polos',6,1,'52000.00','67600.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - TACO - 4 mtr'),
(61,'SK 003 NG','River Oak Nat Gold',6,1,'40000.00','52000.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - TACO - 4 mtr'),
(62,'HK 005-4','Iron Walnut',6,1,'52000.00','67600.00',1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - TACO - 4mtr'),
(63,'SK 001-6','Snow Birch',6,1,'75000.00','97500.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(64,'HK 005-6','Iron Walnut',6,1,'78000.00','101400.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(65,'HK 005NP-6','Iron Walnut Nat',6,1,'78000.00','101400.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(66,'SK 002','Ash Wood',6,1,'60000.00','78000.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(67,'SK 002NG','Ash Wood Nat Gold',6,1,'60000.00','78000.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(68,'SK 003','River Oak',6,1,'60000.00','78000.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(69,'SK 003NG','River Oak Nat Gold',6,1,'60000.00','78000.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(70,'SK 004','Sheen Teak',6,1,'60000.00','78000.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(71,'SK 005NG','Red Mahagony Nat Gold',6,1,'60000.00','78000.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(72,'HK 004 NP','Rustic Cherry',6,1,'78000.00','101400.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6mtr'),
(73,'HK 002','Retro Oak',6,1,'78000.00','101400.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(74,'HK 005-6-B','Iron Walnut',6,1,'78000.00','101400.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(75,'HK 005NP-6-B','Iron Walnut Nat',6,1,'78000.00','101400.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - TACO - 6 mtr'),
(76,'KANG BANG','KANG BANG',7,1,'48000.00','62400.00',1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - KANG BANG - 6mtr'),
(77,'PLAVON-4','Plavon',8,1,'0.00',NULL,1,NULL,NULL,NULL,NULL,400,NULL,NULL,NULL,1,'Imported from Excel - RIJEK - 4mtr'),
(78,'PLAVON-6','Plavon',8,1,'0.00',NULL,1,NULL,NULL,NULL,NULL,600,NULL,NULL,NULL,1,'Imported from Excel - RIJEK - 6mtr'),
(79,'WPC-R','WPC',8,1,'0.00',NULL,1,NULL,NULL,NULL,NULL,300,NULL,NULL,NULL,1,'Imported from Excel - RIJEK - 3mtr'),
(80,'SRV-GEN','JASA / SERVICE',9,2,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'Placeholder service line untuk billing proyek'),
(81,'QA-TEST-001','Produk QA Test',6,1,'10000.00','15000.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,''),
(82,'QA-NEW-001','Produk QA Test',10,1,'50000.00','75000.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,''),
(83,'KSR-TEST-001','Test Produk Kasir',1,1,'50000.00','75000.00',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'');

-- ----------------------------
-- Table: `project_boms`
-- ----------------------------
DROP TABLE IF EXISTS `project_boms`;
CREATE TABLE `project_boms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` int(10) unsigned NOT NULL,
  `qty_planned` decimal(18,3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pb_product` (`product_id`),
  KEY `fk_pb_uom` (`uom_id`),
  KEY `idx_pb_project` (`project_id`),
  CONSTRAINT `fk_pb_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pb_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pb_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `project_boms` (`id`,`project_id`,`product_id`,`uom_id`,`qty_planned`) VALUES
(1,1,13,1,'1.000'),
(2,2,13,1,'2.000');

-- ----------------------------
-- Table: `project_services`
-- ----------------------------
DROP TABLE IF EXISTS `project_services`;
CREATE TABLE `project_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `name` varchar(160) NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_services_project` (`project_id`),
  CONSTRAINT `fk_project_services_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `project_services` (`id`,`project_id`,`name`,`price`,`created_at`,`updated_at`) VALUES
(1,1,'Pasang Wallpaper','150000.00','2026-06-13 20:36:45',NULL),
(2,4,'Tukang Pasang','75000.00','2026-06-13 21:14:41',NULL),
(3,7,'Tukang Pasang','100000.00','2026-06-14 01:49:21',NULL),
(4,7,'Tukang Pasang','100000.00','2026-06-14 01:49:21',NULL);

-- ----------------------------
-- Table: `projects`
-- ----------------------------
DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(40) NOT NULL,
  `title` varchar(160) NOT NULL,
  `status` enum('DRAFT','ALLOCATED','IN_PROGRESS','WAITING_RETURN','READY_TO_BILL','DONE') NOT NULL DEFAULT 'DRAFT',
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `fk_proj_customer` (`customer_id`),
  KEY `fk_proj_creator` (`created_by`),
  KEY `idx_proj_branch` (`branch_id`),
  KEY `idx_proj_status` (`status`),
  CONSTRAINT `fk_proj_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_proj_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_proj_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `projects` (`id`,`branch_id`,`customer_id`,`code`,`title`,`status`,`created_by`,`created_at`) VALUES
(1,1,1,'PRJ-260613-0001','QA Test - Pasang Wallpaper','DONE',3,'2026-06-13 20:36:45'),
(2,1,1,'PRJ-260613-0002','QA Test - Leftover Lifecycle','DONE',3,'2026-06-13 20:49:24'),
(3,1,1,'PRJ-260613-0003','QA Test - Leftover Reuse','IN_PROGRESS',3,'2026-06-13 20:51:30'),
(4,1,1,'PRJ-260613-0004','QA Test Project #4','IN_PROGRESS',3,'2026-06-13 21:14:41'),
(5,1,1,'PRJ-260613-0005','QA Test #5 - Leftover Usage','IN_PROGRESS',3,'2026-06-13 21:15:47'),
(6,1,NULL,'PRJ-260614-0001','QA Test - Services Only','IN_PROGRESS',3,'2026-06-14 01:46:44'),
(7,1,NULL,'PRJ-260614-0002','QA Test - Services Only','IN_PROGRESS',3,'2026-06-14 01:49:21');

-- ----------------------------
-- Table: `purchase_order_lines`
-- ----------------------------
DROP TABLE IF EXISTS `purchase_order_lines`;
CREATE TABLE `purchase_order_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `po_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` int(10) unsigned NOT NULL,
  `qty_ordered` decimal(18,2) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_pol_uom` (`uom_id`),
  KEY `idx_pol_po` (`po_id`),
  KEY `idx_pol_product` (`product_id`),
  CONSTRAINT `fk_pol_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pol_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pol_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `purchase_order_lines` (`id`,`po_id`,`product_id`,`uom_id`,`qty_ordered`,`price`,`discount`) VALUES
(1,1,1,1,'10.00','8000.00','0.00');

-- ----------------------------
-- Table: `purchase_orders`
-- ----------------------------
DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `status` enum('DRAFT','PENDING_APPROVAL','APPROVED','REJECTED','CLOSED') NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `requested_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_po_req_by` (`requested_by`),
  KEY `fk_po_app_by` (`approved_by`),
  KEY `idx_po_branch` (`branch_id`),
  KEY `idx_po_supplier` (`supplier_id`),
  KEY `idx_po_status` (`status`),
  CONSTRAINT `fk_po_app_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_po_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_po_req_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `purchase_orders` (`id`,`branch_id`,`supplier_id`,`status`,`total`,`requested_by`,`approved_by`,`approved_at`,`created_at`) VALUES
(1,1,1,'CLOSED','80000.00',3,3,'2026-06-28 16:12:02','2026-06-28 16:12:02');

-- ----------------------------
-- Table: `roles`
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`id`,`name`,`description`) VALUES
(1,'OWNER','OWNER'),
(2,'KEPALA_CABANG','Kepala Cabang'),
(3,'KASIR','KASIR');

-- ----------------------------
-- Table: `sales_invoice_lines`
-- ----------------------------
DROP TABLE IF EXISTS `sales_invoice_lines`;
CREATE TABLE `sales_invoice_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` int(10) unsigned NOT NULL,
  `qty` decimal(18,3) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sil_uom` (`uom_id`),
  KEY `idx_sil_header` (`sales_invoice_id`),
  KEY `idx_sil_product` (`product_id`),
  CONSTRAINT `fk_sil_header` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sil_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sil_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `sales_invoices`
-- ----------------------------
DROP TABLE IF EXISTS `sales_invoices`;
CREATE TABLE `sales_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `source_type` enum('PROJECT') NOT NULL,
  `source_id` bigint(20) unsigned NOT NULL,
  `status` enum('DRAFT','POSTED','PAID','VOID') NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_si_branch` (`branch_id`),
  KEY `fk_si_customer` (`customer_id`),
  KEY `idx_si_source` (`source_type`,`source_id`),
  KEY `idx_si_status` (`status`),
  CONSTRAINT `fk_si_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_si_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `sessions`
-- ----------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sessions` (`id`,`user_id`,`ip_address`,`user_agent`,`payload`,`last_activity`) VALUES
('9dFWTDo5GfutrVIObVTnlFP3LsJGVqNyPTuYb3Zk',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiQUVSZFl2ZThsd1BVOHNGUWxkdUJYZXp5ekpadHU5NWg5VVNJYUhwNyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzI6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9rYXNpci9jYXNoIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MztzOjM6InBvcyI7YToxOntzOjQ6ImNhcnQiO2E6MTp7aTo2NjthOjc6e3M6MTA6InByb2R1Y3RfaWQiO2k6NjY7czozOiJza3UiO3M6NjoiU0sgMDAyIjtzOjQ6Im5hbWUiO3M6ODoiQXNoIFdvb2QiO3M6NToicHJpY2UiO2Q6NzgwMDA7czozOiJxdHkiO2k6MTtzOjg6InN1YnRvdGFsIjtkOjc4MDAwO3M6NjoidW9tX2lkIjtpOjE7fX19fQ==',1782639142),
('FUPTIpC9Pu3YglynPkevo2X1KA2082U6h7ZwTAUX',1,'127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiUjRBOThYRmdxem1zTlgxRWZRaVZqWW92NjB4dWVKZUtMWFZTZ1JmdCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9hY2NvdW50aW5nL2pvdXJuYWwiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1782637991);

-- ----------------------------
-- Table: `stock_locations`
-- ----------------------------
DROP TABLE IF EXISTS `stock_locations`;
CREATE TABLE `stock_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(16) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loc_branch_code` (`branch_id`,`code`),
  UNIQUE KEY `uniq_branch_type` (`branch_id`,`type`),
  KEY `idx_loc_branch` (`branch_id`),
  CONSTRAINT `fk_loc_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `stock_locations` (`id`,`branch_id`,`code`,`name`,`type`) VALUES
(1,1,'AVL','Available Stock','AVAILABLE'),
(2,1,'STR','Store / Warehouse','STORE');

-- ----------------------------
-- Table: `stock_moves`
-- ----------------------------
DROP TABLE IF EXISTS `stock_moves`;
CREATE TABLE `stock_moves` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` int(10) unsigned NOT NULL,
  `qty` decimal(18,2) NOT NULL,
  `from_location_id` bigint(20) unsigned DEFAULT NULL,
  `to_location_id` bigint(20) unsigned DEFAULT NULL,
  `ref_type` enum('PO','GRN','TRANSFER','POS','PROJECT_ISSUE','PROJECT_RETURN','ADJUST') NOT NULL,
  `ref_id` bigint(20) unsigned NOT NULL,
  `state` enum('DRAFT','DONE','CANCEL') NOT NULL DEFAULT 'DONE',
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sm_uom` (`uom_id`),
  KEY `fk_sm_user` (`created_by`),
  KEY `idx_sm_product` (`product_id`),
  KEY `idx_sm_from` (`from_location_id`),
  KEY `idx_sm_to` (`to_location_id`),
  KEY `idx_sm_ref` (`ref_type`,`ref_id`),
  KEY `idx_sm_created` (`created_at`),
  CONSTRAINT `fk_sm_from_loc` FOREIGN KEY (`from_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sm_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sm_to_loc` FOREIGN KEY (`to_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sm_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `stock_moves` (`id`,`product_id`,`uom_id`,`qty`,`from_location_id`,`to_location_id`,`ref_type`,`ref_id`,`state`,`created_by`,`created_at`) VALUES
(1,65,1,'1.00',1,NULL,'POS',1,'DONE',3,'2026-06-13 17:18:38'),
(2,75,1,'8.00',1,NULL,'POS',2,'DONE',3,'2026-06-13 17:53:58'),
(4,10,1,'200.00',NULL,2,'ADJUST',0,'DONE',1,'2026-06-13 18:43:19'),
(5,59,1,'1.00',1,NULL,'POS',3,'DONE',3,'2026-06-13 20:25:12'),
(6,13,1,'1.00',2,NULL,'PROJECT_ISSUE',1,'DONE',3,'2026-06-13 20:36:45'),
(7,13,1,'2.00',2,NULL,'PROJECT_ISSUE',2,'DONE',3,'2026-06-13 20:49:24'),
(8,13,1,'0.00',NULL,NULL,'PROJECT_ISSUE',3,'DONE',3,'2026-06-13 20:51:30'),
(9,13,1,'2.00',1,NULL,'PROJECT_ISSUE',4,'DONE',3,'2026-06-13 21:14:41'),
(10,13,1,'1.00',1,NULL,'PROJECT_ISSUE',5,'DONE',3,'2026-06-13 21:15:47'),
(12,13,1,'1.00',1,NULL,'POS',10,'DONE',3,'2026-06-14 01:18:30'),
(13,13,1,'5.00',NULL,1,'POS',1,'DONE',3,'2026-06-28 15:11:56'),
(14,1,1,'2.00',1,NULL,'POS',13,'DONE',3,'2026-06-28 16:10:17'),
(15,1,1,'2.00',1,NULL,'POS',14,'DONE',3,'2026-06-28 16:10:32'),
(16,1,1,'2.00',NULL,1,'POS',2,'DONE',3,'2026-06-28 16:11:06'),
(17,1,1,'10.00',NULL,2,'GRN',1,'DONE',3,'2026-06-28 16:12:02');

-- ----------------------------
-- Table: `stock_quants`
-- ----------------------------
DROP TABLE IF EXISTS `stock_quants`;
CREATE TABLE `stock_quants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `location_id` bigint(20) unsigned NOT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quant_product_location` (`product_id`,`location_id`),
  KEY `idx_quant_product` (`product_id`),
  KEY `idx_quant_location` (`location_id`),
  CONSTRAINT `fk_quant_location` FOREIGN KEY (`location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_quant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `stock_quants` (`id`,`product_id`,`location_id`,`qty`) VALUES
(1,1,1,'110.00'),
(2,2,1,'619.00'),
(3,3,1,'278.00'),
(4,4,1,'135.00'),
(5,5,1,'3.00'),
(6,6,1,'1.00'),
(7,7,1,'478.00'),
(8,8,1,'60.00'),
(9,9,1,'209.00'),
(10,10,1,'9.00'),
(11,11,1,'150.00'),
(12,12,1,'40.00'),
(13,13,1,'174.00'),
(14,14,1,'170.00'),
(15,15,1,'170.00'),
(16,16,1,'170.00'),
(17,18,1,'35.00'),
(18,19,1,'42.00'),
(19,20,1,'7.00'),
(20,21,1,'197.00'),
(21,22,1,'1.00'),
(22,23,1,'7.00'),
(23,24,1,'30.00'),
(24,25,1,'124.00'),
(25,26,1,'135.00'),
(26,27,1,'67.00'),
(27,28,1,'34.00'),
(28,29,1,'66.00'),
(29,30,1,'16.00'),
(30,31,1,'35.00'),
(31,32,1,'287.00'),
(32,33,1,'18.00'),
(33,34,1,'422.00'),
(34,35,1,'33.00'),
(35,36,1,'83.00'),
(36,37,1,'30.00'),
(37,38,1,'24.00'),
(38,39,1,'32.00'),
(39,40,1,'18.00'),
(40,41,1,'12.00'),
(41,42,1,'46.00'),
(42,43,1,'40.00'),
(43,44,1,'40.00'),
(44,45,1,'54.00'),
(45,46,1,'144.00'),
(46,47,1,'86.00'),
(47,48,1,'181.00'),
(48,49,1,'425.00'),
(49,50,1,'436.00'),
(50,51,1,'54.00'),
(51,52,1,'3800.00'),
(52,53,1,'165.00'),
(53,54,1,'48.00'),
(54,55,1,'3.00'),
(55,56,1,'9.50'),
(56,57,1,'1200.00'),
(57,58,1,'1.00'),
(58,59,1,'119.00'),
(59,60,1,'207.00'),
(60,61,1,'100.00'),
(61,62,1,'15.00'),
(62,63,1,'1.00'),
(63,64,1,'40.00'),
(64,65,1,'19.00'),
(65,66,1,'5.00'),
(66,67,1,'13.00'),
(67,68,1,'8.00'),
(68,69,1,'95.00'),
(69,70,1,'40.00'),
(70,71,1,'99.00'),
(71,73,1,'70.00'),
(72,74,1,'10.00'),
(73,75,1,'51.00'),
(74,76,1,'15.00'),
(75,77,1,'93.00'),
(76,78,1,'152.00'),
(77,79,1,'55.00'),
(78,13,2,'97.00'),
(81,17,1,'0.00'),
(82,72,1,'0.00'),
(84,17,2,'0.00'),
(85,10,2,'200.00'),
(86,1,2,'10.00');

-- ----------------------------
-- Table: `stock_transfer_lines`
-- ----------------------------
DROP TABLE IF EXISTS `stock_transfer_lines`;
CREATE TABLE `stock_transfer_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` int(10) unsigned NOT NULL,
  `qty` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_trfl_uom` (`uom_id`),
  KEY `idx_trfl_transfer` (`transfer_id`),
  KEY `idx_trfl_product` (`product_id`),
  CONSTRAINT `fk_trfl_header` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_trfl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_trfl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `stock_transfers`
-- ----------------------------
DROP TABLE IF EXISTS `stock_transfers`;
CREATE TABLE `stock_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_from_id` bigint(20) unsigned NOT NULL,
  `branch_to_id` bigint(20) unsigned NOT NULL,
  `status` enum('PENDING_APPROVAL','APPROVED','REJECTED','SHIPPED','RECEIVED','CLOSED') NOT NULL DEFAULT 'PENDING_APPROVAL',
  `requested_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_trf_req_by` (`requested_by`),
  KEY `fk_trf_app_by` (`approved_by`),
  KEY `idx_trf_from` (`branch_from_id`),
  KEY `idx_trf_to` (`branch_to_id`),
  KEY `idx_trf_status` (`status`),
  CONSTRAINT `fk_trf_app_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trf_from` FOREIGN KEY (`branch_from_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_trf_req_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_trf_to` FOREIGN KEY (`branch_to_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `supplier_invoice_lines`
-- ----------------------------
DROP TABLE IF EXISTS `supplier_invoice_lines`;
CREATE TABLE `supplier_invoice_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_invoice_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` int(10) unsigned NOT NULL,
  `qty` decimal(18,2) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sinvl_uom` (`uom_id`),
  KEY `idx_sinvl_header` (`supplier_invoice_id`),
  KEY `idx_sinvl_product` (`product_id`),
  CONSTRAINT `fk_sinvl_header` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `supplier_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sinvl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sinvl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `supplier_invoices`
-- ----------------------------
DROP TABLE IF EXISTS `supplier_invoices`;
CREATE TABLE `supplier_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `po_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `invoice_no` varchar(60) NOT NULL,
  `invoice_date` date NOT NULL,
  `status` enum('DRAFT','MATCHED','EXCEPTION','PAID') NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sinv_supplier_no` (`supplier_id`,`invoice_no`),
  KEY `fk_sinv_branch` (`branch_id`),
  KEY `idx_sinv_po` (`po_id`),
  KEY `idx_sinv_status` (`status`),
  CONSTRAINT `fk_sinv_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sinv_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sinv_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `suppliers`
-- ----------------------------
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `suppliers` (`id`,`name`,`phone`,`address`) VALUES
(1,'Test Supplier PVC',NULL,NULL);

-- ----------------------------
-- Table: `transfer_docs`
-- ----------------------------
DROP TABLE IF EXISTS `transfer_docs`;
CREATE TABLE `transfer_docs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint(20) unsigned NOT NULL,
  `doc_no` varchar(50) NOT NULL,
  `qr_token` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_id` (`transfer_id`),
  UNIQUE KEY `doc_no` (`doc_no`),
  UNIQUE KEY `qr_token` (`qr_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `uoms`
-- ----------------------------
DROP TABLE IF EXISTS `uoms`;
CREATE TABLE `uoms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `uoms` (`id`,`code`,`name`) VALUES
(1,'MTR','Meter'),
(2,'PCS','Piece'),
(3,'SET','Set'),
(4,'M','Meter');

-- ----------------------------
-- Table: `user_branches`
-- ----------------------------
DROP TABLE IF EXISTS `user_branches`;
CREATE TABLE `user_branches` (
  `user_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`branch_id`),
  KEY `idx_ub_branch` (`branch_id`),
  CONSTRAINT `fk_ub_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- Table: `users`
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `default_branch_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_branch` (`default_branch_id`),
  CONSTRAINT `fk_users_default_branch` FOREIGN KEY (`default_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`,`username`,`password_hash`,`full_name`,`email`,`role_id`,`default_branch_id`,`is_active`,`created_at`) VALUES
(1,'owner','$2y$12$9wVw38vLETul6SbHPpkeDuSi/zPLWf36fQsznb4TmcU3iEZLsWxiC','owner',NULL,1,1,1,'2025-09-07 22:06:55'),
(2,'kepala_cabang','$2y$12$0Eyc0gPGxUuCyQA06POHJO66l2zg9WPkYf1OKz2CVBtVE7qwTz83O','Kepala Cabang',NULL,2,1,1,'2026-06-13 11:37:54'),
(3,'kasir','$2y$12$OFEGOXX0LLpOtf2PC2.L4.NQLfeUQqTPkjUzB6lr4TLlueJxq0XnC','Kasir',NULL,3,1,1,'2026-06-13 11:37:55');

SET FOREIGN_KEY_CHECKS = 1;
