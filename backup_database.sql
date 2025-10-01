-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: final_pvc
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `approvals`
--

DROP TABLE IF EXISTS `approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `object_type` enum('PO','TRANSFER','MR') COLLATE utf8mb4_general_ci NOT NULL,
  `object_id` bigint unsigned NOT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `approver_id` bigint unsigned NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDING',
  `note` text COLLATE utf8mb4_general_ci,
  `decided_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_appr_req_by` (`requested_by`),
  KEY `fk_appr_approver` (`approver_id`),
  KEY `idx_appr_object` (`object_type`,`object_id`),
  KEY `idx_appr_status` (`status`),
  KEY `idx_appr_decided` (`decided_at`),
  CONSTRAINT `fk_appr_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_appr_req_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approvals`
--

LOCK TABLES `approvals` WRITE;
/*!40000 ALTER TABLE `approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `object_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `object_id` bigint unsigned NOT NULL,
  `kind` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_obj` (`object_type`,`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachments`
--

LOCK TABLES `attachments` WRITE;
/*!40000 ALTER TABLE `attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ref_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ref_id` bigint unsigned DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `audit_logs_chk_1` CHECK (json_valid(`payload`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'1','Syamil Plafon Majalengka','Jalan Raya Cigasong - Jatiwangi, Desa Baribis (Depan Masjid Agung Baribis)','0811-2287-2006',1);
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `value` text COLLATE utf8mb4_general_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_movements`
--

DROP TABLE IF EXISTS `cash_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `direction` enum('IN','OUT') COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `memo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_branch_created` (`branch_id`,`created_at`),
  KEY `idx_direction` (`direction`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_movements`
--

LOCK TABLES `cash_movements` WRITE;
/*!40000 ALTER TABLE `cash_movements` DISABLE KEYS */;
INSERT INTO `cash_movements` VALUES (1,1,2,'IN','OPENING',193000.00,'','2025-09-21 13:37:01'),(2,1,2,'OUT','SETOR_BANK',193000.00,'','2025-09-23 09:55:59'),(3,1,2,'OUT','LAINNYA',20500.00,'Kapur mill','2025-09-23 18:04:15'),(4,1,2,'OUT','LAINNYA',105000.00,'semen 2 sak','2025-09-23 18:04:39'),(5,1,2,'OUT','SETOR_BANK',869500.00,'','2025-09-24 10:26:33');
/*!40000 ALTER TABLE `cash_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'Ibu Ela',NULL,'Cibentar'),(2,'Bapak Didi',NULL,'Perum Alam Asri'),(3,'-',NULL,NULL),(4,'A Mamat','085220870259','Tonjong, Rt 12 Rw 04'),(5,'Sae','085795207288','Kutamanggu');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_receipt_lines`
--

DROP TABLE IF EXISTS `goods_receipt_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_receipt_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `grn_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` int unsigned NOT NULL,
  `qty_received` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_grnl_uom` (`uom_id`),
  KEY `idx_grnl_grn` (`grn_id`),
  KEY `idx_grnl_product` (`product_id`),
  CONSTRAINT `fk_grnl_header` FOREIGN KEY (`grn_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_grnl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_grnl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipt_lines`
--

LOCK TABLES `goods_receipt_lines` WRITE;
/*!40000 ALTER TABLE `goods_receipt_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `goods_receipt_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_receipts`
--

DROP TABLE IF EXISTS `goods_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `po_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `received_by` bigint unsigned NOT NULL,
  `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('DRAFT','DONE') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'DONE',
  PRIMARY KEY (`id`),
  KEY `fk_grn_user` (`received_by`),
  KEY `idx_grn_po` (`po_id`),
  KEY `idx_grn_branch` (`branch_id`),
  KEY `idx_grn_status` (`status`),
  CONSTRAINT `fk_grn_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_grn_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_grn_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipts`
--

LOCK TABLES `goods_receipts` WRITE;
/*!40000 ALTER TABLE `goods_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `goods_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inbox_items`
--

DROP TABLE IF EXISTS `inbox_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inbox_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `object_type` enum('PO','TRANSFER','MR','SYSTEM') COLLATE utf8mb4_general_ci NOT NULL,
  `object_id` bigint unsigned DEFAULT NULL,
  `subject` varchar(160) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('PENDING','READ','DONE','ARCHIVED') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inbox_user_status` (`user_id`,`status`),
  KEY `idx_inbox_object` (`object_type`,`object_id`),
  KEY `idx_inbox_created` (`created_at`),
  CONSTRAINT `fk_inbox_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inbox_items`
--

LOCK TABLES `inbox_items` WRITE;
/*!40000 ALTER TABLE `inbox_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `inbox_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leftover_piece_consumptions`
--

DROP TABLE IF EXISTS `leftover_piece_consumptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leftover_piece_consumptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `piece_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned NOT NULL,
  `used_m` decimal(10,3) NOT NULL,
  `price_per_m` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lpc_piece` (`piece_id`),
  KEY `idx_lpc_project` (`project_id`),
  CONSTRAINT `fk_lpc_piece` FOREIGN KEY (`piece_id`) REFERENCES `leftover_pieces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lpc_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leftover_piece_consumptions`
--

LOCK TABLES `leftover_piece_consumptions` WRITE;
/*!40000 ALTER TABLE `leftover_piece_consumptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `leftover_piece_consumptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leftover_piece_usages`
--

DROP TABLE IF EXISTS `leftover_piece_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leftover_piece_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `leftover_piece_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `length_used_m` decimal(18,3) NOT NULL,
  `used_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lpu_piece` (`leftover_piece_id`),
  KEY `idx_lpu_project` (`project_id`),
  CONSTRAINT `fk_lpu_piece` FOREIGN KEY (`leftover_piece_id`) REFERENCES `leftover_pieces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lpu_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leftover_piece_usages`
--

LOCK TABLES `leftover_piece_usages` WRITE;
/*!40000 ALTER TABLE `leftover_piece_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `leftover_piece_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leftover_pieces`
--

DROP TABLE IF EXISTS `leftover_pieces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leftover_pieces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `length_m` decimal(18,3) NOT NULL,
  `condition` enum('GOOD','DAMAGED') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'GOOD',
  `source_type` enum('RETURN','ADJUST','PROJECT_RETURN') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RETURN',
  `source_id` bigint unsigned DEFAULT NULL,
  `reserved_project_id` bigint unsigned DEFAULT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lp_available` (`branch_id`,`product_id`,`consumed_at`),
  KEY `fk_lp_product` (`product_id`),
  KEY `idx_lp_reserved` (`reserved_project_id`),
  CONSTRAINT `fk_lp_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lp_reserved_project` FOREIGN KEY (`reserved_project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leftover_pieces`
--

LOCK TABLES `leftover_pieces` WRITE;
/*!40000 ALTER TABLE `leftover_pieces` DISABLE KEYS */;
/*!40000 ALTER TABLE `leftover_pieces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_request_lines`
--

DROP TABLE IF EXISTS `material_request_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_request_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mr_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` int unsigned NOT NULL,
  `qty_requested` decimal(18,2) NOT NULL,
  `qty_approved` decimal(18,2) NOT NULL DEFAULT '0.00',
  `qty_issued` decimal(18,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_mrl_uom` (`uom_id`),
  KEY `idx_mrl_mr` (`mr_id`),
  KEY `idx_mrl_product` (`product_id`),
  CONSTRAINT `fk_mrl_mr` FOREIGN KEY (`mr_id`) REFERENCES `material_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mrl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mrl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_request_lines`
--

LOCK TABLES `material_request_lines` WRITE;
/*!40000 ALTER TABLE `material_request_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_request_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_requests`
--

DROP TABLE IF EXISTS `material_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `status` enum('PENDING','APPROVED','ISSUED','REJECTED','DONE') COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_mr_user` (`requested_by`),
  KEY `idx_mr_project` (`project_id`),
  KEY `idx_mr_status` (`status`),
  CONSTRAINT `fk_mr_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mr_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_requests`
--

LOCK TABLES `material_requests` WRITE;
/*!40000 ALTER TABLE `material_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_return_lines`
--

DROP TABLE IF EXISTS `material_return_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_return_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `material_return_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` int unsigned NOT NULL,
  `qty_returned` decimal(18,2) NOT NULL,
  `condition` enum('GOOD','DAMAGED','LOST') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'GOOD',
  `writeoff_qty` decimal(18,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_mretl_uom` (`uom_id`),
  KEY `idx_mretl_header` (`material_return_id`),
  KEY `idx_mretl_product` (`product_id`),
  CONSTRAINT `fk_mretl_header` FOREIGN KEY (`material_return_id`) REFERENCES `material_returns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mretl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mretl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_return_lines`
--

LOCK TABLES `material_return_lines` WRITE;
/*!40000 ALTER TABLE `material_return_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_return_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_returns`
--

DROP TABLE IF EXISTS `material_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_returns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `returned_by` bigint unsigned NOT NULL,
  `processed_by` bigint unsigned DEFAULT NULL,
  `status` enum('PENDING','PROCESSED') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDING',
  `returned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_mret_returned_by` (`returned_by`),
  KEY `fk_mret_processed_by` (`processed_by`),
  KEY `idx_mret_project` (`project_id`),
  KEY `idx_mret_status` (`status`),
  CONSTRAINT `fk_mret_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mret_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mret_returned_by` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_returns`
--

LOCK TABLES `material_returns` WRITE;
/*!40000 ALTER TABLE `material_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_payments`
--

DROP TABLE IF EXISTS `pos_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pos_sale_id` bigint unsigned NOT NULL,
  `method` enum('CASH','CARD','QR','TRANSFER') COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `ref_no` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pay_sale` (`pos_sale_id`),
  KEY `idx_pay_method` (`method`),
  CONSTRAINT `fk_pay_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_payments`
--

LOCK TABLES `pos_payments` WRITE;
/*!40000 ALTER TABLE `pos_payments` DISABLE KEYS */;
INSERT INTO `pos_payments` VALUES (1,1,'TRANSFER',1908000.00,NULL),(2,2,'CASH',960000.00,NULL),(3,3,'CASH',35000.00,NULL),(4,4,'CASH',1980000.00,NULL),(5,5,'TRANSFER',726000.00,NULL),(6,6,'CASH',972000.00,NULL);
/*!40000 ALTER TABLE `pos_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_refunds`
--

DROP TABLE IF EXISTS `pos_refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_refunds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_ref_approver` (`approved_by`),
  KEY `idx_ref_sale` (`sale_id`),
  CONSTRAINT `fk_ref_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ref_sale` FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_refunds`
--

LOCK TABLES `pos_refunds` WRITE;
/*!40000 ALTER TABLE `pos_refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `pos_refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_sale_lines`
--

DROP TABLE IF EXISTS `pos_sale_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_sale_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pos_sale_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` int unsigned NOT NULL,
  `qty` decimal(18,3) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `discount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_psl_uom` (`uom_id`),
  KEY `idx_psl_sale` (`pos_sale_id`),
  KEY `idx_psl_product` (`product_id`),
  CONSTRAINT `fk_psl_header` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_psl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_psl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_sale_lines`
--

LOCK TABLES `pos_sale_lines` WRITE;
/*!40000 ALTER TABLE `pos_sale_lines` DISABLE KEYS */;
INSERT INTO `pos_sale_lines` VALUES (1,1,41,1,14.000,85000.00,0.00,1190000.00),(2,1,52,3,56.000,1000.00,0.00,56000.00),(3,1,45,1,1.000,500000.00,0.00,500000.00),(4,1,49,4,2.000,30000.00,0.00,60000.00),(5,1,47,2,6.000,17000.00,0.00,102000.00),(6,2,46,2,80.000,12000.00,0.00,960000.00),(7,3,34,2,1.000,35000.00,0.00,35000.00),(8,4,58,1,22.000,66000.00,0.00,1452000.00),(9,4,30,2,6.000,50000.00,0.00,300000.00),(10,4,46,2,19.000,12000.00,0.00,228000.00),(11,5,55,1,8.000,60000.00,0.00,480000.00),(12,5,24,2,4.000,30000.00,0.00,120000.00),(13,5,46,2,12.000,10500.00,0.00,126000.00),(14,6,58,1,14.000,66000.00,0.00,924000.00),(15,6,46,2,4.000,12000.00,0.00,48000.00);
/*!40000 ALTER TABLE `pos_sale_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_sales`
--

DROP TABLE IF EXISTS `pos_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `cashier_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `sale_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('DRAFT','PAID','VOID','REFUND') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `change_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_general_ci,
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_sales`
--

LOCK TABLES `pos_sales` WRITE;
/*!40000 ALTER TABLE `pos_sales` DISABLE KEYS */;
INSERT INTO `pos_sales` VALUES (1,1,2,1,NULL,'2025-09-23 10:02:03','PAID',1908000.00,0.00,0.00,NULL),(2,1,2,2,NULL,'2025-09-23 10:14:18','PAID',960000.00,40000.00,0.00,NULL),(3,1,2,3,NULL,'2025-09-23 18:03:12','PAID',35000.00,15000.00,0.00,NULL),(4,1,2,4,NULL,'2025-09-26 14:18:44','PAID',1980000.00,0.00,0.00,NULL),(5,1,2,5,NULL,'2025-09-27 16:13:01','PAID',726000.00,0.00,0.00,NULL),(6,1,2,4,NULL,'2025-09-28 12:43:02','PAID',972000.00,28000.00,0.00,NULL);
/*!40000 ALTER TABLE `pos_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_service_lines`
--

DROP TABLE IF EXISTS `pos_service_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_service_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pos_sale_id` bigint unsigned NOT NULL,
  `service_name` varchar(160) COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_psv_sale` (`pos_sale_id`),
  CONSTRAINT `fk_psv_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_service_lines`
--

LOCK TABLES `pos_service_lines` WRITE;
/*!40000 ALTER TABLE `pos_service_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `pos_service_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_pc_parent` (`parent_id`),
  CONSTRAINT `fk_pc_parent` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (1,'PVC','PVC',NULL),(2,'LIS','LIS',NULL),(3,'WPC','WPC',NULL),(4,'HOLLO','HOLLO',NULL),(5,'ORNAMEN','ORNAMEN',NULL),(6,'LEM','LEM',NULL),(7,'SKRUP','SKRUP',NULL),(8,'KLIP','KLIP',NULL);
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(160) COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `uom_id` int unsigned NOT NULL,
  `track_by_meter` tinyint(1) NOT NULL DEFAULT '0',
  `material` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `series` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pattern_code` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `finish` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `length_cm` int DEFAULT NULL,
  `width_mm` int DEFAULT NULL,
  `thickness_mm` decimal(6,2) DEFAULT NULL,
  `barcode` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_products_cat` (`category_id`),
  KEY `idx_products_uom` (`uom_id`),
  KEY `idx_products_track_by_meter` (`track_by_meter`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prod_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Elok1','Wood 18 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(2,'Elok2','Wood 18 NAT 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(3,'Elok3','Wood 22 Nat 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(4,'Elok4','Solid Silk Nat 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(5,'Elok5','Solid Silk 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(6,'Elok6','Wood 9 Nat 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(7,'Elok7','Wood 9 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(8,'Elok8','Wood 13 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(9,'Elok9','Wood 10 Nat 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(10,'Elok10','Elok Wood 13 Nat 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(11,'Elok11','Solid Silk  4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(12,'Elok12','Solid silk Nat 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(13,'Elok13','Wood 10 Nat 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(14,'Elok14','Wood 10 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(15,'Elok15','Wood 39 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(16,'Elok16','Wood 38 Nat 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(17,'Elok17','Wood 18 Nat 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(18,'Elok18','Wood 13 Nat 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(19,'Elok19','Wood 9 Nat 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(20,'LISPVC1','Lis A Wood 18',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:50000'),(21,'LISPVC2','Lis A Wood 13',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:50000'),(22,'LISPVC3','Lis A Wood 10',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:50000'),(23,'LISPVC4','Lis A Wood 22',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:50000'),(24,'LISPVC5','Lis B Putih',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:35000'),(25,'LISPVC6','Lis B Wood 18',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:40000'),(26,'LISPVC7','Lis B Wood 13',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:40000'),(27,'LISPVC8','Lis E Putih Tali Air Silver',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:50000'),(28,'LISPVC9','Lis E Wood 18',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:50000'),(29,'LISPVC10','Lis E Wood 22',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:50000'),(30,'LISPVC11','Lis E Batik Gold',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:50000'),(31,'LISPVC12','Lis L Putih',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:35000'),(32,'LISPVC13','Lis L Wood 18',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:40000'),(33,'LISPVC14','Lis L Wood 13',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:40000'),(34,'LISPVC15','Lis Tutup Putih D.01',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:35000'),(35,'LISPVC16','Lis Tutup Coklat D.02',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:35000'),(36,'LISPVC17','Lis Sambung A.01',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:35000'),(37,'LISPVC18','Lis Sambung Wood 18',2,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:35000'),(38,'WPC290cm1','WPC Y08',3,1,0,NULL,NULL,NULL,NULL,290,NULL,NULL,'',1,'hpp:85000'),(39,'WPC290cm2','WPC Y121',3,1,0,NULL,NULL,NULL,NULL,290,NULL,NULL,'',1,'hpp:85000'),(40,'WPC290cm3','WPC Y56',3,1,0,NULL,NULL,NULL,NULL,290,NULL,NULL,'',1,'hpp:85000'),(41,'WPC290cm4','WPC Y05',3,1,0,NULL,NULL,NULL,NULL,290,NULL,NULL,'',1,'hpp:85000'),(42,'WPC290cm5','WPC Y01',3,1,0,NULL,NULL,NULL,NULL,290,NULL,NULL,'',1,'hpp:85000'),(43,'WPC290cm6','WPC Wall Panel AZ 05',3,1,0,NULL,NULL,NULL,NULL,290,NULL,NULL,'',1,'hpp:85000'),(44,'WPC290cm7','WPC Wall Panel AZ 10',3,1,0,NULL,NULL,NULL,NULL,290,NULL,NULL,'',1,'hpp:85000'),(45,'PVCMarmer1','Marmer PVC',1,1,0,NULL,NULL,NULL,NULL,290,NULL,NULL,'',1,'hpp:500000'),(46,'P.4m1','Hollo 2x4',4,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:12000'),(47,'P.4m2','Hollo 4x4',4,2,0,NULL,NULL,NULL,NULL,400,NULL,NULL,'',1,'hpp:17000'),(48,'Ornamen','Ornamen Lampu',5,3,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',1,'hpp:250000'),(49,'Sealent1','Sealent Putih',6,4,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',1,'hpp:30000'),(50,'Sealent2','Sealent Clear',6,4,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',1,'hpp:35000'),(51,'Skrup','Skrup',7,5,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',1,'hpp:40000'),(52,'Klip','Klip WPC',8,3,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',1,'hpp:1000'),(53,'SumaPVC1','27012 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(54,'SumaPVC2','27712-S 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(55,'SumaPVC3','27061 HL 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(56,'SumaPVC4','27076 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(57,'SumaPVC5','27078 H 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(58,'SumaPVC6','27881P-S 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(59,'SumaPVC7','27863C-G 6m',1,1,0,NULL,NULL,NULL,NULL,600,200,7.00,'',1,'hpp:66000'),(60,'SumaPVC8','27012 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(61,'SumaPVC9','27881P-S 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(62,'SumaPVC10','27091 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(63,'SumaPVC11','27021 HL 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(64,'SumaPVC12','27100 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(65,'SumaPVC13','27083i 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000'),(66,'SumaPVC14','27712-S 4m',1,1,0,NULL,NULL,NULL,NULL,400,200,7.00,'',1,'hpp:44000');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_boms`
--

DROP TABLE IF EXISTS `project_boms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_boms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` int unsigned NOT NULL,
  `qty_planned` decimal(18,3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pb_product` (`product_id`),
  KEY `fk_pb_uom` (`uom_id`),
  KEY `idx_pb_project` (`project_id`),
  CONSTRAINT `fk_pb_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pb_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pb_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_boms`
--

LOCK TABLES `project_boms` WRITE;
/*!40000 ALTER TABLE `project_boms` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_boms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_services`
--

DROP TABLE IF EXISTS `project_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `name` varchar(160) COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_services_project` (`project_id`),
  CONSTRAINT `fk_project_services_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_services`
--

LOCK TABLES `project_services` WRITE;
/*!40000 ALTER TABLE `project_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `code` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(160) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('DRAFT','ALLOCATED','IN_PROGRESS','WAITING_RETURN','READY_TO_BILL','DONE') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'DRAFT',
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `fk_proj_customer` (`customer_id`),
  KEY `fk_proj_creator` (`created_by`),
  KEY `idx_proj_branch` (`branch_id`),
  KEY `idx_proj_status` (`status`),
  CONSTRAINT `fk_proj_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_proj_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_proj_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_lines`
--

DROP TABLE IF EXISTS `purchase_order_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `po_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` int unsigned NOT NULL,
  `qty_ordered` decimal(18,2) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `discount` decimal(18,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_pol_uom` (`uom_id`),
  KEY `idx_pol_po` (`po_id`),
  KEY `idx_pol_product` (`product_id`),
  CONSTRAINT `fk_pol_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pol_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pol_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_lines`
--

LOCK TABLES `purchase_order_lines` WRITE;
/*!40000 ALTER TABLE `purchase_order_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_order_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `status` enum('DRAFT','PENDING_APPROVAL','APPROVED','REJECTED','CLOSED') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `requested_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'OWNER','OWNER'),(2,'KEPALA_CABANG','Kepala Cabang'),(3,'KASIR','KASIR');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_invoice_lines`
--

DROP TABLE IF EXISTS `sales_invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_invoice_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sales_invoice_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` int unsigned NOT NULL,
  `qty` decimal(18,3) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `discount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sil_uom` (`uom_id`),
  KEY `idx_sil_header` (`sales_invoice_id`),
  KEY `idx_sil_product` (`product_id`),
  CONSTRAINT `fk_sil_header` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sil_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sil_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoice_lines`
--

LOCK TABLES `sales_invoice_lines` WRITE;
/*!40000 ALTER TABLE `sales_invoice_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_invoice_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_invoices`
--

DROP TABLE IF EXISTS `sales_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `source_type` enum('PROJECT') COLLATE utf8mb4_general_ci NOT NULL,
  `source_id` bigint unsigned NOT NULL,
  `status` enum('DRAFT','POSTED','PAID','VOID') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_si_branch` (`branch_id`),
  KEY `fk_si_customer` (`customer_id`),
  KEY `idx_si_source` (`source_type`,`source_id`),
  KEY `idx_si_status` (`status`),
  CONSTRAINT `fk_si_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_si_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_invoices`
--

LOCK TABLES `sales_invoices` WRITE;
/*!40000 ALTER TABLE `sales_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `payload` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('06RIQF18rLfVCS9Gku2nK89Ff8zszrFiwqj0xSgf',NULL,'125.82.242.243','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ3V4T1ltc1NqN1JObjQ1QUxGdEVLVEJSblNmY292VUF5MHgxdXpYbSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271940),('0i74r61Vq41c9YaLM02LOjnrYbENFSvvVXlrVbuy',NULL,'223.199.177.10','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibVRIeTU2SnRjektlZ3hUTERWZHZmaEFXQ01id2RkYkNKMXJJT2lpVyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266674),('0KKZbqoB1QTujhxYAbGsxgTAmXYsU13A9zB5qXTy',NULL,'202.102.220.3','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVzZKcFdwbDdKYVNueXl3cjZHdkFkWTBqM2h3bmlxdUJ0WlpRamwydSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266840),('0OJfY0iee7goj0kbLrqmQuiPW5e7sdOsLKrAK3tn',NULL,'204.76.203.219','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46','YToyOntzOjY6Il90b2tlbiI7czo0MDoiMkVPQ3phQWVPQmJzR0twRXFUUHRQY2FsaklobzJmTktsaVVRTXFUNyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759285415),('0vUo2eMElKTrptW99ln380CNvSIMgs4rElKPJ2EG',NULL,'195.178.110.109','l9tcpid/v1.1.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVkZQVk5YU2pEWnF3NHB0bmhoM09DRUlYQ0I5dzVwd0RqdHlFeTJiWCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly80My4xNzMuMjkuMjQyIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759290207),('22NsxhZTmCGxO2uVF4JM0QvigIRGlg8sWBnEZCtQ',NULL,'58.59.246.114','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiS2FyMUpJZGJnVmlyaFJvNmFiMjRUUUVmYjYxNkRZNlZUdG9od0RoYyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268879),('2KQkopOpxWC9ZAF0zpOE18lcW25tE2pYis8KhWyz',NULL,'139.227.161.44','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWWZCYXhORENTdGZieGlBYm94eHFEWHg4ZHh4ZmNqVk0xdm1nNkx2dCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269321),('34GFV4swH8qpxNzIcLEr3c8N7SxNGJPunw4XqvDG',NULL,'119.4.195.167','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRHFjR0tpdHd0ZThobUNaM2MxemhlMUdQcWlPaVVvbWp1OVVtanFuayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267075),('37rN0iu1GfKFszhzw83MsL481Nq6GyPGGj0WphnQ',NULL,'124.133.210.110','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ0JQc1lZUHZJRUQxeWpSM2k1WjR2VHdodENZYnF2cW9tTHE3MzE3eCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269435),('3CyplvNJt3PIzqBXUDyOHVX6txhg45bEqpFPC5ZS',NULL,'43.248.108.26','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWW1BNUh2Q3VQYnB3MENLNGY3a0llQ3RDWkp3SENuUE9tV2lNOEFscSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272020),('5fJe3MiOrXLK2Vq57xAoF4hi2fzUUn3BMWjx9ZSu',NULL,'223.199.166.28','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiN3pkWkNvdEtnTUo1aWI2ZTBtRGRVQXNzaWdnQm45WjZnZ3NMTVN0aiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270692),('5H6W7qYEpiGKBNRjWz7qBF4eUZhoXFqbIHdukfCI',NULL,'115.205.1.25','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiOWFUb0FhZEZHMVIzMWI4R1pPOUh0N01YaWR5MkM4R0tRMEZSMVBaayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271760),('5lkRGaizi4UFAcEwLbpjra40hhYvLRdEcV8hFsYo',NULL,'121.29.178.250','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQUp6SjBEWWoxUjVDSjY3QWVFYnpPOWdYbjlrdERmTjd3WmhFZlBmSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266129),('5phuJNuRslXTXVNk4fw0fCjO0zA8ECLq3fYlpa5r',NULL,'124.31.104.157','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM3hybEc5bzFmQkdmdENYNER6TFRUSUttSFVFQUxxSlpMa0F2eXNESiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271460),('5UXJ4dvxwfxrtWbFxmUPuhvgtk2GBKOyN9ShNUe8',NULL,'111.21.35.228','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicUhoajVEYno0RjR5SW1hRmpJMlZtNHJ3d3U5VTdBc201a3FvNFF2ViI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268336),('65sGTZwgFoSxLirOfA2pkrepPdZ6OljXznWx751h',NULL,'111.113.88.251','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoib0xlTjVacGZjd2lZWlN0NmN6MklFc2hsSDdOQUQ3U2twOTc1NGFMZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269537),('6cN5sTQhiNb2XfnT2Nn5LVjJxdaeANpWXD4E7wbq',NULL,'175.30.48.80','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMUJ5T1Z0OTQ3RmRjb2p4NDV4UGttZ2tKSnN4UzZ4M1gyRG5RTG9wZyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269957),('6FbtYhzcxvrhBscw6PxmvcT6Jg3VXS7WjanhEPF7',NULL,'111.113.89.31','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMHpuV2RlZ3ViRkRLUXR2cldsY2FLMm9rR2gzczJMWUZ6N2p6TEo5UyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268172),('6GZjJsb8yPOApLWN7mdSlQDaE6JAmsm9KAiL0cZ4',NULL,'221.207.35.66','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiajBUTjBZelBGVXZrVnVBOWFsZXhVSjIyZkhNWFM2VktjU0V4T3hMSCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265333),('6Mbpy1n6uCVs5MgjE1bdBzsDVtTv28gahGgszfNy',NULL,'116.172.249.150','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibnFwTmsxVFRBRWkyM2JicExOSG9XUlFHV09UcFhubkVvTTNPUjRSdCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271100),('72mP8AaUZMx9n1Wkf2tL6YbuRxalSrm9mHqNtVgW',NULL,'218.104.149.160','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibWJ2eHQ3bVpEa0prWlVBelNqWG96cXllSHVqdm5GSDRoTWswZllSSCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269880),('7pN0Cs5uArKCIeKOHDjKzZn7RnddjLqDnlehWBge',NULL,'218.104.149.38','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNHZtT3ExSUZibUFMV0pkbUIyN2xhRTJqSHFWVnVTdFZiTWZlQkVZeSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265896),('7SozKM8SpeY91Kw3J9Rbrl6sYgMbaW2RKWfHpPOv',NULL,'110.177.176.81','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZXJhTndhVDRnS0Y2NGNjNUh3bG9yNGs1dkp0c0F0dEN1eVY1MXlWMCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268577),('80hYzzJKefoqBh0P73n4nWuswopeFUPNqaBTwIx6',NULL,'101.71.211.56','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidjlrZVhYSWFSMzU3bDFtTjJWRVNkT0plNHhhV2NiaHFDeERXN0NaMiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266778),('89EbaqMlhSkhcDPnulG7e4a9yoMKN1Yg6wWzZjIZ',NULL,'60.13.7.5','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZjZ1Smw3R0NnZUNpRERxbHRCM01tblBPTG1MYkZxckROSlk2NllONiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269120),('8au94snij1zyc8ky6KgTPjJsB7ncUmZjsITk3uRE',NULL,'121.29.178.157','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQXFlMWRFaDgxOWdUOEltczVvbnI4ZUVCalQyVVhKMHk5amZhcEwyZSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265529),('8KgFOIX7QWbX9XJzBDY6YOrfVt8JDaZ5NRcMNi8A',NULL,'125.82.243.185','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZlp4R295TUVEUDh2WHVJTzJBcndBTkJmdG5WbnQ2R1ZTeDZuY01yZyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268173),('8t3El1d4SpBrYMY2KrESj9lWozFtApV3ytq6ECaS',NULL,'221.207.35.196','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWHNxeDhiZjRRWmpvUGhDNzBjNzN1c2lsRGFBeUw5dlF0MXk4Q2xQRyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272900),('91bWO0XMoYJUSNnPVrKSFdVyItmOUlFUHMpHPwil',NULL,'43.248.108.197','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVUtHZXRPRXRmZUloV1FMV0d6OHE4bHhYNDgyVHE2ZkthaHR6MTlnSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265168),('9bxyquIrqfnO4HTOrZV05k0LnMyufsFsvPpIh9rI',NULL,'220.167.233.8','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNkpHZ2tQd0NGT29JdkFBQ090d0Q0bERRdnV5OGV5M0Zvc016ZFNXVCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269238),('9PMjo8KIL5q2YE2tYNjfZngl7tgNH4L6AnW1zaFu',NULL,'175.30.48.205','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMmJ0VkhDTjFwQXNxUDRRdFBCam9kYkxGU3M4YjlJbW9BdTdBNEpvNiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266961),('9Uu5ELV5j0iMpYSMIT7BGIziVOuI6j3YjN2f1daU',NULL,'195.178.110.15','l9tcpid/v1.1.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiejRoQ0EyODNyZzFLWFJyMm9HNzNkeWxYalByZ29yOE95bENtUjdJbiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly80My4xNzMuMjkuMjQyIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759289038),('9Z9lSvZefSuF1jerk020BsfccFJ2dBWsI4w32IP2',NULL,'117.14.148.166','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ1lMdmt3bm85Y1R2czNXa1R2Tld6ZkRMS1hJWmVwRWhRY1RtRzFQaiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267976),('A02enf9Y7C1v5tTgEgumKmKarQPgDQpoq8QAgCel',NULL,'117.146.60.3','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidmNrTGNiSnh2SWJlQ0JCRHMyUHpFcnlMVU56M2NuV3drRXdHUXNycSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268471),('ahd4r4VSWUoqBPOVMBWhutyUiPCVIfeCHqMJ41Cz',NULL,'121.29.178.41','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM0JYdnFybGxHZGM4U1FLaDJyOEtJMXNhUWZkMUJBUFMzUmNnTGRnaSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270498),('aHsFHm7UINlof8NS8Vyl0gmTg65qKxc6HsctLRW9',NULL,'195.178.110.109','l9tcpid/v1.1.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiaGJTendjTlpGdm8xNnZnOVNpUzNkNXRoQzROeTFBdUlrazV6MHozdiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly80My4xNzMuMjkuMjQyIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759267661),('aQND2F5gPUhU8jIXn95PBS3ElxLPVTlcV9BOlly7',NULL,'58.59.246.89','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNHpCTjhVVXRvSzQwck1Jb0NlQTVqTnEwY3B3R2NKMHZnWTVmM3JXdCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266675),('auXGAwyzLjh3rJFwPR6SPCBa8KWaMdSRbQ8IsujH',NULL,'103.252.89.160','Go-http-client/1.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoibDVac1Awa1F3Y3RTa0lLaFJGbkgxa2hCTnlvenNqQWRDSG5EWkJkSSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759289208),('avSk1r8nU1tIe59Jdr8M2a7Y4ZICXdLS6ivFiL9f',NULL,'135.237.126.204','Mozilla/5.0 zgrab/0.x','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVkNES3dCdWFiRElndE9tWVY4bXlHcU1aZTlXUlluNmhsZ3N2ZEliUiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759283827),('avtxVq9RDYw4x9YIA4Lgss0kUccct3mNvNt9aCLx',NULL,'45.38.44.226','Go-http-client/1.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM3JFRmF0Sk45NjVPRm8zSzZ1V1oweHFUQVhPMTZiY2x6ZkJDSldRbSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759278288),('b6XUkZPqsab3X9AQCS7EezyYsfdwHDQVMLRq5rgs',NULL,'111.113.89.240','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTk41NzNuYUVERG5FeDZWUldTREhRbDMxSExpbWZwRXRabVpTVGF2VCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272484),('BKvuYWCAqMCRQpEXCeEftm4C5W18NdLrJStNNzbZ',NULL,'223.93.189.214','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYno4SU5aNFljNWE4WnBLaksyaUlsNGk0RG1ha1dNc3VnZUV2Z214diI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269957),('BmqFsvNeKcwySIx9uyRlzAypGOTQSzelfd45rhBR',NULL,'112.94.252.68','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNVlKb1g2czM0aTN4dnF0dHpjcWlqT0Z2THhtUzFhVGcwOFJndkxZWiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270801),('c8nODkb5D9I2DbAWhY2NIEVNudRWlpGhVBCRQa6F',NULL,'45.38.44.226','Go-http-client/1.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZFh4c3JEN1pvQzhLcHJOTmgxMmFZRjhOaG4xVDlKYVlvcmtiQXhJdiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759288053),('ca3r2lxjTE0QJf0GefEbX2IZTfIFiRqkPiPvh8DM',NULL,'118.212.122.109','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRDR4dWJCM0pGNjhZNzRZZG5reDBVVjBpM3RvOWdJQURvNmg3SmlWSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272610),('CwBaQ1pMGHIPsqljLfbAH8dvGzlcbymwRqCYf0D3',NULL,'43.248.108.130','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWEE3Mm91empUbno4aGNrdTlYZzhaQVNCcDJXUTFmT2NzYlNhbndRQSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265635),('dQE436X3gYxLdW0iasZlGfI6GupEXoDzIEZuaa5j',2,'182.253.80.213','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQnZqejc1UXo2eVowZGxhNGhsSFlmYXZURDdFRmh5UXRyWEwzZ29oTyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6OTY6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAva2FzaXIvaGlzdG9yeT9icmFuY2hfaWQ9MSZlbmRfZGF0ZT0yMDI1LTEwLTAxJnE9JnN0YXJ0X2RhdGU9MjAyNS0xMC0wMSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==',1759292495),('DqnOoSGdXq8IxDpN7SD0lHMdMYCOz1LxsS3Zi7GQ',NULL,'218.104.149.128','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVno4WHEyN2FLbnlXOUZ1T29XWDJPWml0QlA5S3poRFNMeVpLcmxteSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272901),('DSEC0iYs5ajYVfDQBH12dpyqYvVZyYWDMlDD962J',NULL,'182.138.158.203','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQnJ3bUo5cnJ4dmFnOUJBdlQwU1E0OWpEYzd4MXkzN1U0YXJMOXFaQiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268577),('dtEMv3D9mWneeY0tnSJLVj00lOmbAHnciSi8UGGf',NULL,'122.96.28.122','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUEViREx0WmpwaEFvNWxqaTdjZnVTVGI4WGw3Y2lxS0lKRUlWb25aNSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271039),('DtSqP7vyXwKdnwLgUbG9vJUnhpFbXNcTxj4hbA84',NULL,'122.96.28.123','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNmZlSWpwWEpiVFR2eld1Nmo0dVl3dk5nVjMxVWNWNUlNaXp0ZnE4VCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266534),('eaAEGrLaApUwS4i3SWPRD6SXSworTXJ87kc4wEus',NULL,'171.37.46.114','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiaTd5OXdjdmgyT1RPVDZFSGlhOWJsMmMweldpNUlmQ25YcDVtNE52NiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267690),('EamxBPs6LrUcDu62aK8NSKJxYyBjPI4sIUdqmT8f',NULL,'120.0.52.157','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiR0M3dHAzckJVdElQTTFNaVIzUGRCNXFjODB4UDZ2NFBndTJTWWJXRyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272600),('eAvdeb3PpbVkG6IHuArGF6BoHQrRLbn6CQlxQmRs',NULL,'118.212.120.124','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiT2lEaEtuR0hVbm93NXpsZWZMclZWSlp5UXE3clpKOWJPUm1udEtCdyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269117),('EDI6JiPurFIbLv1mokhBf3oxAISdWsZf9FLEFupL',NULL,'221.11.51.29','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZTVwRWhrTWFiSEhPVFBIM2JGbHhvREhyZ2NyQURqcHd6QldVNWgxaCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271861),('eg1JomMclSgXXHXO9z7R40gCmRtnSy3l58Q2Vzs0',NULL,'220.197.51.248','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoid0NSVFB2S1RsWFNzTmZ5TXZEMWhUTmlOTkNleThGTXZ5dGE1cmlYYiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271278),('ePIP6vNPdFo2mKofiTD7kBl7IeMSGpSAui8IdHYr',NULL,'36.106.167.220','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicTNyWU9UVnYza3RFcElCaVFYRVRvVEZRbWFYbTZORzlkNVV0dHNVUyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271638),('et4Zjeq2lBi7eA6f0AQJ0mu1vFTXDaWp651exRht',NULL,'175.30.48.138','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoic3NDNXByWnN3dGhYbzhCUzFXNEtoVzRUTG1kcUg2REozNEFSVnBKOCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266242),('eVCXCWQ5QXd4hYMNHXb3lbiRim0FqMEtQ2CW8DtZ',NULL,'117.159.9.9','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiekVQRkZiRW1JaDQ5c3UxdnBtS3ViUGs0MDN2ZFpPdmxrS0JSeXN2bSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265115),('F9F4jh3dx9UsfyR3HUIyUAA4Vfrgy2egxqawP7Mq',NULL,'218.104.149.244','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoic3pBRTZkS2FjUTJCVERLOW9qWnJSRGI4cDYzbzdOMHJLUmVpWVd0eCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267218),('faODQ9KHZTPmcIpJntCpo8HytAY0NV8I4x5H2gSc',NULL,'58.59.233.158','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYVVYcHYwN2huRnpvaUtzZVdkQk1sbnRRMFJ6UUJ1RUZHbWNvQ1F6TSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268217),('fMnzQaoXG7w0DOn8L4EnlIDEnQzBZGPQhRaW9TYD',NULL,'219.143.197.1','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSlVLdm5rZDJWcEJIT2F2VTVzeDdaaEJ1RkRaT3NsdmQzSHM2WnpJayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266596),('fNa3dudsllbEQfWThVzRVDuEpuKvgkVL1BgJt7nL',NULL,'36.32.3.72','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiOXRFeEt6SzZmNG1LTWd6WlMyd0s0V2J2ZHdDeWJLdzc1TzBKeHpRSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267795),('fnmSuEXhVYfhPNX4ZeOL2BcECqhdVorQWYYDvcqw',NULL,'204.76.203.219','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46','YToyOntzOjY6Il90b2tlbiI7czo0MDoiZ0ZWUGE0YVRjVmlqbGx1U3dSTGEycEk3eHlhRjB5S2xnemRQTFNJMiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759270730),('FqLkqATAXmlkD9UcVIwR0mpNiKNvFs109qLnW305',NULL,'223.199.177.8','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoic2g3N3FuaGNFNkpRYVp6VU8yeHZYelVMQkxZZUcxZXlKdGN3SjdiZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265753),('FRJ1XNzLNWaFLf08MIsYXU5txhxfSkyr4YLfc4L8',NULL,'59.173.135.102','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoid0RoU0R0WGI4a3I3aTNkN1VyeENsVGY0Z3FQTWFua25uQ0RFczJ3QSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271639),('Fuk7raHzgGmo4qp53cSnbQYTMuOo0NlaJWvwlX0X',NULL,'118.212.122.75','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRnViUkNVRWpZQ1BiZlNabjg2Vklja3BGcFhjTm5IMGRaVHBudE1hVCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265123),('fxw8OuKrGuoKfFn1Rzh9hqpFdTk9PRTaIxe7Zs6R',NULL,'42.92.120.254','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMmZOWGZMSE5OREswNFVHTlU4Sk1VN2dVOEpTN1BNcWxyNFBKNVZGdSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271098),('G1qoXa6G55L0o4ci3mvKxtAurCCrY7ubaAXc864O',NULL,'111.224.218.179','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUmNFSjdIQ0NrV2pFRVJRaXZrWWMzb3F3Tjk3Z1pTOEpuTHMwTGNIdSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270392),('g3c5q7FwgoiC1LCOULXBcJz8uFkBgOM2bIwEPfIA',NULL,'1.24.16.191','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUEtDNlcydWtlbVp6bnFBQ0dncVA0bEllMldud0hRcDNpZ3N3cFR2RiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267075),('g5aBEDxAzNeeJLLtneQz6jwOHBrL9dS8fM5LxWDn',NULL,'47.237.157.180','','YTozOntzOjY6Il90b2tlbiI7czo0MDoibDdXWW5KYXNVMGRlZ3N6QnNTN3ZEeUNsYnVUNkc0Z3NIN0JsZEp3bSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly80My4xNzMuMjkuMjQyIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759266314),('gAgtlrZMpYO34RqR1R9iMtZzhnwWmUoWkKa5MOIU',NULL,'220.197.51.240','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoia1RONXl0NnZHSTdoZ0VsMndOTkpmWnNkVEZhUFUwam1OY24zdXV2MyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267330),('gc5cz6AUa5XnsCIgjugqYedKIHjVW3eMH2nuk8Iz',NULL,'223.199.181.23','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRVY2ZzB3RnEzcktEN1JVdzJKbGVPa3EwZERBMzJ4enZxUU03dDVOWCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270392),('GEGGKAnOjx5mt5kF9prdqMbgaxJ6fpehwd7sSpTE',NULL,'43.248.108.200','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoieVMyTHBacEtrbURMWEhrbVp1cEwyaHVKVnFUb2p4SXNtZW85dWNhYSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266779),('gnX3RRzEWPHQBLnSi7WdUfGDTGNSKgmTaLFJTjNC',NULL,'1.24.16.185','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiT1pSa1pRM0pENDd0a1FXSnd3NlFYRlRoeGluNzJGaEVxTHduZVRTSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266069),('GPUBPrKJvaq3gTAIOs6rHgODZrAvHTTWs40sDYzK',NULL,'42.92.120.21','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZWJmVGxJQmdCU1cxMjZDWk8wbVExZ3BQS0ZRcHpWcnczQ0dDTEpRTSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759273035),('GrSVGlr59IJD5lXSafIQwDzNJaV46XKJK5jlGhpd',NULL,'106.4.161.195','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMXJvczNsbkNOanFEQ3ZyR2V3cWxCMmYxUmtxV2hNbDVhdkNEdkpQYSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265063),('gVFGKsZEvafixtY7VZ9OklPwpCjza7A4paoTnd75',NULL,'220.197.51.44','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiakh4S2hLMGhVRGlKTDVOaFRiY0NVZ0hKMG83cXMzNXpHaDNnSlQwYSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266174),('GWn1vGAyWJDYqewjJJk3XdUcfPhgxN2H0qEg3EXO',NULL,'1.83.125.194','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiR2E5dGpQc2RkNmNRR3dUak1rajgzMHNmcVdrWllSSmJMd2VSMnNaRSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271543),('hAVawm7dCoqmsWyu88eS6DLYP6rWfyhtWDJC7tJ9',NULL,'220.197.51.194','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMzRwcHlzaFg5WDY5bFYxcUxORmQ1UkVZV3dVb1dob1R6WWVBSklxRyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266595),('HhWryr3a6Bt70vr4DsrN8braKszEfSyt7ThMgOsa',NULL,'120.0.52.89','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWnNlODhHd0tSQ3RFNUk0MXJJMXZsY01zNkgxWlVDT0ROT0RUS0xtZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268638),('HkeGwkYJg0t5mcFOnZE0MfMSajd92Wp3fVmW15nt',NULL,'139.212.68.20','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidlExREU5YkhpS050bkdpZEIyOWtnN0RiSmlLdG12bHp5Ulk3Y3gwOSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267176),('htVaxNXTqU7SvIMBStZUaJ2yINDtEIEIfAkKZzEI',NULL,'183.94.175.2','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSjJ1QkdaY2dJQ1BMNklla1k5MjF0QXFoMTRCeXVJSkd3OXVoWFVLWSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269792),('hwjQpdR7BUTeJlx8wMnJMsAqJfw2RYf070p76il2',NULL,'60.16.199.174','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicUJJY2o4ckVMamQ3SkY0bER2UjBQUjVlRDdOQ0d2WFJwS1UxYnJhYiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269880),('Ia9aPW4bef8HmFL2yzUpQxem2z9Qfw6dtsRr26qp',NULL,'1.83.125.112','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRGx2QzRnT0xnRkhHWm01dzJOZUkwWWp4cDROYTZxT2dJVjFjRXBIcyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272780),('Id8BwTeST2DACbo6GS8jJnkKsWRKd1B6bcdGoWZj',NULL,'195.178.110.109','l9tcpid/v1.1.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRGM5OEUxYWNtdDY4TGRWa1JrVXZUNWFlUGkzRk5Gb3hPM20yaG11TSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly80My4xNzMuMjkuMjQyIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759273195),('iNMLPuVoQGsQx1zkZ03l1Hfg5znoJxmjCU2GJBds',NULL,'59.173.134.14','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiOUFoQ3h4NEhwMzNQWmpHaElFcFFNZlBNYkt4Qzc0MnZBOUNHZUE5UyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269656),('IXVM0UIf43PMmxBvWIMUdl2yrXtopnlNq9qN8K6v',NULL,'182.88.163.78','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibUZ0dEQ1U2hyZGRIeER4cnBaOGpVSnQyMDZ4Zk1lenl2ZTBZak16YyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266415),('jA846HYDcPMV7RZLFErjZLyJt2qnXSlorI9O6IGB',NULL,'59.173.133.96','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoia25wMzVkQnNvU3V5UG5FZXBZT3VhRzdET2tWTVEwa2NKcUJVTjg5diI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268996),('jDnC5Jg2B9sMtGltXy7vrJzuSMOeP7JinY94tBSO',NULL,'47.237.177.222','','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYW9wNlZLMzhpVFE2a2tRcHV5SUhZQTlDZHVvSlF6ZHprdWJQM2tnUCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly9wYXNzcG9ydC5iYWlkdS5jb20iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266585),('jGdlCRBu87TaPWhOd3K7cxwBSfFetCKE6wDR41G2',NULL,'182.138.158.229','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTDZSZUhXYUVNaUhhU0FQSWpEclRIU0I0Rm1TOXMwNXE3dTY5RHhwYiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268052),('JxmLWykDq3h4k1VMekCR0D8nCta7xpgkNAQzPFl2',NULL,'182.88.236.209','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWTNEV3lMSWVjS3VFakZYUHBjcGFFVklCckU5UmtYQkZiZ1lINE5jTiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271339),('kcwOjgAg9bLI1dNj6EanRza1NdobZIzel9Tq3nJ1',NULL,'118.212.122.15','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiY3EzaEhDQk9DM1BYMEFMS09UMFZud3VmVVVZdjNQR2J0T2RXT0YwMyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272263),('kd20UD4DbQmjXgyVKtrtdUl61h3HLpyOaW6P3m8u',NULL,'1.24.16.196','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoieU5GY1lzdTZHWlhUNk9EaDZtN0YwUnBoM1YyQzlYdnBqaWFYWjJJVSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266176),('kHYaKq2E4Gko3rhla75GB55p7F13G9jbvOTErIlb',NULL,'175.152.35.25','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicndxTVVNaU5lYjhhWlI1a0toZlM3M2V1UkhFSFJvRzRaZlF4MFJTRiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270198),('kiTTofHdpaqESOTLr8ZyGMxu1GbmZdUmo6RfXf7i',NULL,'220.167.233.103','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ1IxdzdqcjNCRXRmTGZQeGx0eWdFbldZNm5mTFpCdW5Ed0VETXZ6OCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271860),('kMKgWuBrbInxqrWXxxQRTWsVb5LcQcpQOQhs55ZD',NULL,'175.30.48.171','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUDZhWHpkamJoM0VFc2tvQTdRWXlibWhDZ0NPVVpRYk1sQllVeG9iNyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271278),('koGriDsytQ5vC9zkFS9wGadNk3PDF08wRPmigqHY',NULL,'175.152.30.76','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMDg2TzZvZ2VPQ08yWFdZVXpwb0pmUHFxRHNsTDBnbk9VaXRKZ0Q0OCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268878),('kQDTH6gfNkyp1dYcBPaMt1DFnGcjGse2rKcJ3jis',NULL,'204.76.203.212','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46','YToyOntzOjY6Il90b2tlbiI7czo0MDoiMmo4ZmhCcE45M1R1djRVcU9OdUpiVFVMZTJqMnFnOEszc0ZqaFd6YyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759287701),('kSmnEGy3OfdF5npIzNhYvNCXtT0dGlWTzyJUzloK',NULL,'124.90.53.6','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMXZMNjczbTVNaGhlNWhHaHJmNEhqUDBDT3ViM2xUbjNJcVNLbXIxbyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269437),('KUKiRgUeRYdNgvmVGK7Y2yyXS2M5Hq9qvPR0iVfW',NULL,'111.113.88.33','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibG5RcVhmZUthQlo5eVNtbG10bEJneVBUQ21PR3UyRDhPS2gzbjRKVCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268637),('lJaElCdJCCkyseQuJJL5MdzTQlVaGlEaHI85PJyP',NULL,'135.237.126.204','Mozilla/5.0 zgrab/0.x','YTozOntzOjY6Il90b2tlbiI7czo0MDoielhTeUQ5anBYZkw1OUFPR2QzY29PZFQycWNEZFBOTWF5ZEhCVnlrYSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759283827),('lopvmcWGaR5i1GoWhfEgKuvTYgaOnyvV6sHvvsa0',NULL,'123.245.84.106','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoid3BJbDI1YkxnUjlvMU56azc5dWc5VGlNaEN1S1MxYWpNczRLcVA1TiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268758),('Lqjhj2sAsYhLyM3rZ3KMVpmbmPq5vdGsNeni1sRR',NULL,'118.212.123.240','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicGlscGx5WFNjTFRmekZSam1sdldpRFZKNzdoRXlTUzEyNXdmZFo0ZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265902),('LQtssJpQQel8yioMNJ8rAgep2dYLP0YxcMTzd5U3',NULL,'36.32.3.98','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZmprU3RjNzVjenVoUE5HdVd6SmlYUEhjdXNXMU5UeE1EOWxSS3oyViI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267511),('lunxi7SiGn9gBZc6Kq5AaTpMdxzjYtjVTz3i8z2k',NULL,'180.95.231.133','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ1VUQ2NEczNaeElMV2IwempseXU3M1c0S3U1SFVXeUtHTXJDZlprTiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269657),('LYj9utjK0ntqcHWGGmGStuYks4Knyzx7p5HW0FyY',NULL,'220.167.233.164','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSk9nT3FPd1JTcHZLMHp2N3VOV2ZsV0lSZXg4UnYyQlN2cmhuam5DNCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272265),('M227NU00lYA1WSHwe2ZkqbP4VMNQI9Av7Epka0B3',NULL,'218.66.32.226','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoib28zV2Fsd0pCNWNiY01kUEJxWTlyekdXSGhWRWYzbkExeUZFZDcwSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266534),('m2D3Wr1HxWyn8h7hOYIXCy25VV2nrs6BkqwcEmk6',NULL,'123.245.85.39','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiY2NBWjdCU2pyZHYyMkpReGlnNUdlejJmMU9tdnFsNk1XM21hdnpOVyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268052),('Mbu8uRgLvSd53FkPDEf9nttCh1c9rRPW8ygyxjFV',NULL,'49.113.94.5','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM1dDYTFEZWU3aktDUHVQSlJNdzVaQXFRTzE0MEZFS0E2azhJOGdXayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272364),('mc3mJOby3yBh7tbeu3gscauxTIdDU54iZY8timIr',NULL,'36.106.166.116','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVFdCN29vamZ1OU9RUkFya01KWklxWThRUzNreXByelpKSnpBRzRPOSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759273095),('MgnZHV5DeTibn5P3eAAkLU3Bi4hVOLtREsPExcNz',NULL,'220.196.160.65','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWmt3cGNoZ3pEcWt0N2FhMjF5Y0szVmhBdU1pSmRJbEhNMk0xaXhtQyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759285510),('miA84TZFBtt2SewvjuXGhGJ6D4QMf8A4jW8IkCl2',NULL,'123.178.210.251','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTE1ia2JwZllMc2U3RWJWcnhKdkZURmk1QTVET0lMRVQ3T2FESE9VaCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266962),('MkeRpT0Et1FNtWjdGOVPqocK7sixl7PDsyvdSX4E',NULL,'171.36.6.246','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiaEJhN1BNem92WFdVUTdwS3JwM0R4NTFYSG1obzE3VWhmc1pIdzhvZSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265634),('mMj1X5djhTFLcupYdRdpQqGg6YzgTeiLmOor6eCd',NULL,'61.167.255.221','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUnNteGtOYzcxczQwaktHRW9KN0Y1NWRSRktpbVBqMGYyaTZBdXZXaSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269367),('MO6DCcj8LljFO1WGMoP3Awqi2qHfzRU4m7eUylIZ',NULL,'120.0.52.107','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoieE5STktLODJ5MGJlaEpBdkV4QXp1M3ZLQWpuZ2s4UFN3aG5PcmpyMiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266415),('Nct3iZNkg7BNQmSTgMl1yzLMf9Z4eYu4fXxVKivh',NULL,'45.38.44.226','Go-http-client/1.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoieHVDYWY3bDVDM2t5aTQ0UnZud0FVYXFyY05ZTHpGV2JCUDBxQUgxMiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759284529),('nIqFDYMvr92xU8HThwVIWNU0n20ezam3xUYesHSs',NULL,'123.14.126.32','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiN1Vxa2xTNGJybW9GY3NORXR2dzJLRkdyeXNyR0w4OUFMRlc3UXRoeCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271338),('nIV9VngAEpaxTtPfb5tfP2kCYYwPD3p7QmJR1Hdk',NULL,'123.144.21.191','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ042T3JPVTMwZllXVXJ0ZHV1amNKMld3OFpQNGFESG1ZdEhFVjdNVyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266835),('NQvvjOGMG59YMPuRJvJAWrynNzxT4rpYUAbvHqeD',NULL,'116.52.141.199','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ1hLUDBvTFdnNHprWTFDZjUzYVFUNVBsalp6aGM4eEx6dUpqa0tTdyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267675),('NuLRYD4ysL0KanRhpdH7JB0LKtE9bCeRPLnM8R9p',NULL,'58.59.246.75','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRW9wWVhGTXhWZGNVUmhLcHo4UlBwbEliRnNzZldyaHBGdHRadk1TdiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271759),('NwQXTIdIpCZjrKFyLZqRtpIo4wPx0nYC7wNIbBER',NULL,'59.173.135.250','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZXdCV1hUbGRTa2RpRzVSY0V0NHQyaFhFOERhQVh4MFpRd0xEdWxBcSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271940),('O0jKFS9mtQuMEHQKvYofDcLJmk58Bq71T1Na5eN3',NULL,'220.167.232.154','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZUt2U2tHR0lXS2FNR2ZZSnNIU0JzR1VzeGJKMUpic3ZuY1d3c3dNVCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267514),('o8loaIzIIPCaaqtk0vm3DCOqlbYy0MUYUwwobSxe',NULL,'171.120.30.251','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRmxQU2Rna2dITVFQblo4aDNpVnhuMjhXNTU5QkxQOUFMQjlxM2FCRiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265335),('ObKRn1OboyZjwWj4elVKJAByM32InFF5MEvK9I66',NULL,'42.63.51.169','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidTRWdmVQSTk2NGlkanN4R2hFaExwUFQ1SmJHcW1BS1M0S21nM1FNaCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272194),('om72sG8tj0XFwllC6Fx1I1LnFj8nKgQOvI96KyMs',NULL,'204.76.203.212','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46','YToyOntzOjY6Il90b2tlbiI7czo0MDoiTWp1dmJmaENzRDZ1UHNTdXhmMGtoTWczNUhSMEV5QlN4RHZvTUprYyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759276687),('ON4isrfmTEgh79eImYiFxw2rfpLJl9IcE6smEaUA',NULL,'42.63.51.174','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZlRmSnlkaVFURVRaeHZiejhlYU5VVDV0cDhRdU4wY2t3aDNkVE9meSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270876),('OPUy0mnSIqmWHQ7JkMhV4LyvYqiWuNcY9j19QSNb',NULL,'139.212.70.84','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ0ZDNTdGUE5jdzlSSzhQZFprdmhRN0t5MTlNRW1mMDVqNnluZ1NGNiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266132),('Os9eeoxgKfcTdwEByZZeTQ59ow4GyC99VMZlWw8R',NULL,'123.245.84.20','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiOFc3WmtRTktHdVhLbXRrcm9PdzBITTBpZW95WWdDUnBUeGx3a3JEbCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269537),('ot6ysj1mQ3FV7swayBxAuiuYkVMDxFNH0aHKQgpn',NULL,'220.197.51.163','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidUx0b0E3eHkwSEJwMUN1Z1JJVkhNeGh3OGxpRFVhMlJLcWpZVWx0YSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267176),('oxm12Ad4QEbKOq92jcyI6Y81xxDuIIX1YazCrAfk',NULL,'144.86.173.9','Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNkxSUjZGdlgxTG5hc3U5T2hnbUFDamd4SjViaGdrTkQ3SWx3WnNQSCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759288364),('Pc7sk3hp5GzIHKH7wfgdyT95TG8mcPEESTrOvOM2',NULL,'60.13.138.123','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWnRhYWQ5ZFRMSlk2Vk95eHlyNG9IbVpLQzgzTDR1d0pZb3NnMUpFWiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267265),('pCHqUDxO1uezVA5Fl5znncPo2Ar37xvCe3SUYtbq',NULL,'123.245.84.114','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSk1DMzhkR2xaNXpycTE3eEw0bjZ0Nzc3WnJDWHZuN1d5MnRVelA1VSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267329),('pCPaH0ysxKwCmkZ48ZLF7uIjGGtyS87CBcZpatsU',NULL,'117.14.151.252','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMmRJaDFhUkw3bzROUnRuYVVYc1BtNlJiN0luMXR5MFBrcm9TMGtRWiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266084),('piaScVlEs5swoIk0wwxVU8gnnLrlwlhLOQqyS8qU',NULL,'36.32.3.40','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidmRmU0gwWUM0TFp4OG1sTDRMclNEZk5RVzZDektRQXVQZUZNUjN2QSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268180),('PJgjKukICWnqbRhaVjNs71rF9u277uHDNTp1Fuqo',NULL,'49.113.93.7','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoib0t0RVZ0Y1BJQVF0N003RGExdjFvRUFFZUxOd0hXZDVUQmR6NWJZeSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271159),('plmfILYgQ3MFarAJlQ7vsx8C4HG1fmxBJR8WLi9x',NULL,'47.237.157.180','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTFJKNGVOc3NoRE1jbFZVc2NDTm5XYzg5aUo0YkhicVNGZnhMS3Y1cCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266314),('ptfLatp1kqyDhHnCCfmjNFzhW9RAMjBXQe3PAjJL',NULL,'125.82.242.16','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNzlOOXhVdXNTQklQbFdVSVRNMWp2NzEzbGJUbnhHVzVvZ3VwUXNKciI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268756),('puMKBpqpUxSvacrjfk0aQbGPBZ8fLF7u8xpPVKvp',NULL,'111.40.50.150','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicHplRkVyOENGQTFyY0t1OVpPbm1QczJkVGZvRmluaHZHR0gzR1FUayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270877),('PutTI32rs7HSV00Wc5le4xPDbcNdhTUkyvGcbfNW',NULL,'1.24.16.136','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicVVWdjBjU3dMRVIxczdGTlJRd0RhcjRIVm9EMHdrVTlpWWlrODMyYyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272782),('PYKiRgXVIbPRAAGwj4RBZrgP7AGRqF7NOrYTo5ZM',NULL,'36.32.3.188','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYmtyajRWZFNXY0xrOVJCb2MzUmRmNUpTVEdpVkJweUlRckJCZjRkWCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271159),('Q0nbRWjArfqBefp6Y0XCLAlP39CE3hdQwyz6ktKx',NULL,'60.16.216.201','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidDAyWjhNdEswSVg4Y3lBbEFocmsxTVBzN2N0bVFYWDMwSUl1RkZQMiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272195),('QDPU5uillceuqnOeBFuDyhRw3TsWlvAAlv8jH75t',NULL,'58.245.27.197','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibVZ1ZmR2Z0xjY1FuS01QWGsyM21jdVZJSkdsRlp3alFzeEZ3akp6bSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267615),('qkhXDxunMfydF4s7GenpuErU1TGInvqLVQb7vADG',NULL,'120.211.145.99','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRU8xVkl1ZEJSb0ZzOEtoRTl5YWlJQnhOQk5NRWg1ZmhQUDBOWldEUCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271458),('QkPbdDgryEkpxMTThULHYJr6WA9KkayCyMV5HlFK',NULL,'153.0.85.165','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicUZCNzdmT0l0ZnViTWhRb1YxRmpaVHdWUnFYa1dpOHl6eFlkSTJHcCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268336),('qKTbrKfG3Hv89QFucED2fH2ZURIIhReZ3vvMtZ3j',NULL,'220.196.160.95','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTnhMcVJMUEJEQlZiRlJnbzVPT1NQaXYxcktMN3E2MlJvSGtYWHJEdCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759285514),('qPMLEaCHptUppvwpe5Ifhmk4DxDPtbuNtCt3qySS',NULL,'119.163.47.164','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMWh2WUNRM3A0VncxVVNWVjZHQkVkcTJmTFlOaEpaVWQ2bHZqU09uYyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272363),('qsyUDvBwwtYlCAqanHT13cZsfB4oBFmLgC5X4Tmt',NULL,'54.163.198.183','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUjVRVWFGZE9QcGFtVXRMQ1hsUHR0Q2VocFpkNlRTQWVDZ0Q0UlpReiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265041),('QUksc0h48FmzCVYZvqiv0e20tbbgpKrDi8agxjBS',NULL,'195.178.110.109','l9tcpid/v1.1.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSXBSZEhLQ2JQa3pEdUtha2tZajB3dFZVUnZSUDBuQlU1ajJJSHZVdCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly80My4xNzMuMjkuMjQyIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759276714),('RAh3eCf7vcysD7zmx87jTFhxLMA9dYDrRF5aUtSg',NULL,'204.76.203.219','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46','YToyOntzOjY6Il90b2tlbiI7czo0MDoiTmpNNHF3Q3FZNU9wUVEwVXVOWU9qYjlTUGxvakY4MmFjM0ZVZ0ZIMiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759292583),('rGkSMAxsMQxm9PbVVKssY9ciepRfKXAQD28E76Xx',NULL,'183.156.153.110','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSU1QOXB6U2Y4T2dTUGU4MlhwTVc4NWltN3ltMXVLUEVMWURQV2c4YiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272483),('riQTQnUmW9CtSTZiri9cIjwByAtTb5OQIGpjRtUw',NULL,'123.245.84.204','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNTVtU0Y0ZUhYVExlbG1FSmdnUWNkTXUxb2NqZ3AzNFBReGFyTDdlVCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268180),('rOR5C1C0GZvTf0bBvLLWHAtdhaphekxVyka04DX0',NULL,'220.197.51.105','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiT0dvRHRBUFNKMHVLQ1Y5RTdIM2d0dHBGT2Zya08zbWJVSjJ3aGZNZCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265756),('sAB4MivICx8uvv043GFgTZA5ASwTugTRDWBrDUD8',NULL,'218.104.149.211','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM3JpWFlLRm5vSU5DVVdLNVpFT056M2NSTzgyREZ3WTl4MFp2TEw2ZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270092),('sEgWgZ0i47MMpbio8IqBxADyQfTOWK2WFqnya31g',NULL,'42.63.51.137','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidFNHYzlIV3Yxd1BpQ0wyZUpYOWxBdWRlSDdDMnZlNjlHT1dVeTB0TCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270092),('sF2k7T9b9MLhLYQP6cKusImNLNwJON8LiPKrEpqo',NULL,'120.192.27.54','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYkRHU3VJOU9TcnBpeUhWYkdxY0N6bTY2U0E1WFE3b2ZkRUlKZ2V6WCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271545),('SHUnOFj16EZbe7ow5fmQYz8cTTcJatBLqN24WX2x',NULL,'101.71.210.35','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibnA1eDZIc0kyME5YQmV0Y0lPNktrclZsQ1JEeHpBWGtNWFc1NlVPWSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266238),('snEkkfH09LHwOIZ2oNXqLrUD5JPQ6SKfvtGxuTsi',NULL,'60.13.6.110','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoib0MwMG42OXlYaFdyYWJYQW9ENWZXVGVkQjlId3FXTmFCRUhUYWo2SSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269322),('SQ8DQNQNutRoGRRehXqiZ8G2hvKCkGK0rdbJObsJ',NULL,'110.177.178.227','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVThyZzVDSUdya0JzejMwVk53UlNPdlJKSnZXc09jODUzRGxSblluZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268471),('svUhwY4kHP32LC8QfOO39pnxP9JejrK9MReeXUkp',NULL,'144.86.173.9','Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWnRRaDJsdUNwdnBtbUJsaVh3VldaTmE2Uk9DWmlSdGNmaFZMdkJ2QyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759288365),('t2gwxudshKzXBVRkSzU2OmPxgtpN6CO2gwpF8jQh',NULL,'223.167.169.30','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSlUwSDRhaXg2TTJHS3RpOTMwelVLYmVJN1B4M294bmZFV1d2cDBVYiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265816),('TjNjakG4c1VtwRUPJLDJFFr4M0TxZWUuuxH3DyNg',NULL,'111.224.220.104','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidzJYRmZQM2ZLRjVaTUxxSDJNT3VhR1AyVVFCWkJDanA2S0xYeVBZZSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265814),('TnBiQHAHDt9R9ms4Dnmpy8WC041zxGZa29Zgb7Tm',NULL,'58.212.237.138','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRnd4WVBsVHpLcHlySFN1WmNCaHdvMGVSQnRpamZkSHVjc3NGWTlXNCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266294),('tsS7oEdnvvyciOmaDWvrWBMhIqvWxcuRz0P2MVW6',NULL,'116.52.141.186','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQXhlTkNWTHlxME5UM3JQejlqdWttSkZZTXpaZzkxb1M1RVRJTzVXYiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266294),('uCspZEAPnAP4B4Byy8OSVeRpY08tScko0jHcpZ5p',NULL,'116.52.141.194','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiblVEZzdWVkFmeWhucndTektoc05kTGZiRnVwbUdCbnF0ODV2ZE81UiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272119),('UGmaxuBERzNUG3QK5f5FeDPFwiCTS4kn9ghQ7g2n',NULL,'124.160.236.197','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWEc0MGpQaXpuMHVYVG5NOEh1TFNYZG1QWWxrWG95YmpuQXpQUnpldCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269793),('UsvFWrFYIBNhmnSZEk0Ag1nZYwm2vt5J9scTZlPE',NULL,'111.113.88.220','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZjdSRXQzUjBEalY0SDhKUkR2WFRlQXJQSGoyclg3QjViaWFRRUE0cyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268997),('Uxk37w47YcQJByuPQMo15rxDInkhekW0pIitDciy',NULL,'218.104.149.192','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoid01pU2hYRkdhRW9hV1RIemgycG0zeTBwbmNsYWVNTVNaWnJJT0ZxQSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270693),('uz6xQdKmW9b1e3zcuQ6HviioWihWoE6cBy8y8q3P',NULL,'117.29.44.48','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTkVFNFlHUGljenZjMXU4aklrNFFzT0FsZ2NjUzRIRkFDa1VmM2U2QSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267218),('V2M0tVnh1ZKKLrFwD7nwrdhr15y39XTKL9rDtvqi',NULL,'101.71.211.40','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTWNrSEw5amNhdHlyVHBGRU10M043WmZZQmphNzdxenZGbVVFNEI1dCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270499),('VEsijNd1K5KpoSk2MH9ybIpuLFnrS8rB5z7hmmVo',NULL,'118.212.120.187','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoib1E2YTZ0Y1FwUzZyU1ZCMXhoUWprWXlHRk9RVVl2Q0k1WjFwSXl1RCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759266071),('vpbqLpn7MUeR2hziOtssnySTSnVz5SHTrLYj3N9S',NULL,'123.145.24.165','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiU3FieGRjd0U3Z2dZT2thRERCeWlXcGtmeGh1OW1TMmtaRDc1QVgxOCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267615),('VWe3ZzKMZbwh7KWnDhnoRQf9SttE7NPKAi9YhSXQ',NULL,'60.16.220.1','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoieEo1bThQMjZDQ1NMMjc2ZzEyZXFmNE1zUVpiU05VSHZ2Qk1TTWw2cCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269236),('w8xFaMbEnculcfHLFULHylbqmI25q1YBI6WmjslD',NULL,'123.245.85.237','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUHhFRjFRTWlSd2ZLeXYxUWNHOGt4T0xEVTQzYk5QbjVpNENtRUlheSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272675),('wBwPtMFNrjPO8DftYd79AhrCkAyqAa1ExEicAJCc',NULL,'204.76.203.219','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.85 Safari/537.36 Edg/90.0.818.46','YToyOntzOjY6Il90b2tlbiI7czo0MDoiM1VRMVVyUTNyNjRPWTNwTnVGTk1SczduSVJJZnpZNHhYUmJtUXNPZyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1759278034),('WFrYnQHakx4oi5Vm1PlJXDtZvYsyUAkMWbMG3Bkg',NULL,'58.59.235.245','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQmZIaHpuc2EwMXViOG1oN3ZHMjNadzU2T2s1NTRnZnJaeW1VVzlKaiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265274),('WqBUDgtNQ31l626gw5G7JPwxQXUtmC5S5KEAWn1g',NULL,'222.95.168.58','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoib2JCYzRZTXIwUDc2TWVJWXpMc3ZnaVBERG5udGJzWUdOekNiQmVvciI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270197),('WquDyLjuqRpiv0k0fEnUvlu92X2McuR30a9tsTo7',NULL,'115.204.149.79','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSFZPMjJDME5idU9rNDdVQlRacmhwbUdwVmdGSkhJVURuTjMyQUJQMiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759269358),('wXD3UEFYICeaRbmv06tMlHUSFvddIR4zt5PH1f6z',NULL,'218.64.5.130','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiaWQ0amhBc1hWTExCV25BWURFRUJYbkNvekZONEZFZTNFZlF5aEMwNSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267870),('wy4yLQXyQilAPmMf8RnCREyJoDOwBkcVDPZIQ2b7',NULL,'139.212.71.104','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNG9BdHhEc0FIN0trTVdLUjRLc2g5d2FQNXllN0tWTzZhU3piZE13UCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272023),('WYWVMAkZoWj2xu3rHiU39UPUAOXWqrhLniy66ySj',NULL,'223.167.169.67','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRVg5WnBsaTRhR2hkOGROU0hhTkd3SWNMNWtlYXBrZEk4dDRvQXBiViI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265067),('X61KbCocARaycgB69Zqvrnu0iYXpCEm8Jl6UA7MV',NULL,'171.37.46.114','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiV0YxbmdyVUJnVE0wRlhHSlZuZ2pyc0xlZGR5TGVoVXp0UHpXWDg3UCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267690),('X8dTOxmilg2SanrOydpUJD2TxKf7RXKdLhIgc6Aw',NULL,'123.245.85.133','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoid05DSHBTMktNOHg4cHhUT3ZrOVR5dmdsWFR5Z0RiSDVmNWFGM2JydCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265273),('xj8v5lDivIeYoYMXsJAvFE1XWWAiazhbd6jYf3N9',NULL,'49.113.94.126','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUG5SMlNIWFpLVVcwaEQ1MzlEVkRxMWN3bWFjcnFBNW5FZDRweWQ5WiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759271038),('XopUTrPe144ePhCn4EP2e33fR5QizhdqJUzPcNv7',NULL,'116.52.141.156','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUnZjcVhPczFqdW9VTzc4TUpsd3dWaUVCNkY1ZFdYR3pGWXB2aVZnaSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270978),('XSpbdQELEKm676fjNVt6uoah6OsQAnfQj0sLFv0T',NULL,'119.164.101.216','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWFlVUzVqZGN5MnA4b1VNWTk0WkFzRDlzcUlhRWFBRGI0aDQycFE5aCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267682),('Y3l3mUq5IVrfVN0zeiAb7mlcSfaMyEduSV2s2wnj',NULL,'223.199.163.83','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidUlEbjZpQ0hjY1J6Y3hxMWh3Wnc4SE81emNPTDA0TnRSeGFNOEw3YSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759267870),('y7KCOE1nrDMrvMJAGHENOLt0KayrBld0me5DgIN4',NULL,'111.224.219.126','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoieUZDQmNCNWJWNHZLVTJhRUFXTUo2bnFESng5WVpBOHFldlptQm95SCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759268215),('ycmAg9a4vsElAlp1YaRptV78K1sndM70VnqpKvT6',NULL,'121.29.178.161','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWUJQTEk4dE0yQ0MwUmc0NlJHdFVsRjZiZFpCeXFsRFVSU015TmYyeSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270319),('YyNixnd1LYbH1SlXHC8skxLLO76k6XFKGXNwOHM5',NULL,'113.200.72.205','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibHN3cnNBNkd0NWxNNXNlMkVsWnlqRHZTbHp2Z0h4MkpPSWZOVml0WSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265170),('yZgl4rBNK9cOuoXJxSVSGmylFlmZRyIq0EkzAFMP',NULL,'58.59.246.124','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoielhwWTAxTXdac1ZPbHpYRm5zRmhsdGN5UEtNOHFNTFlXbm5ZSEZPYyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759265528),('z1TgXzRZ9ttdln6LsLMj4xq3gaCQaumRfhXl5EzO',NULL,'27.10.17.193','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZEkxb04xVk5MYUt5NUIxVGxWUkNnWGRWdm9LcERBMzNWUHp6QVJtdiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272674),('z2fB79VvfKY0gdVbbfmD5CQZtXA2e5l7Xd6jMhtz',NULL,'180.101.244.12','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVU5WQnhTNVI5WGxQaGQ1UHpCdkx5VGVLVFhVZkZOS1psQVE4RE1KRSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759285511),('zhpJM9KPXTCjYss4m8zuYwIc4WopXB71iWAGPcxo',NULL,'172.105.147.36','Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity','YTozOntzOjY6Il90b2tlbiI7czo0MDoialppZmo1UUZMQnlVWjNvb2F4dW5EV1Z1cVhSRnZVNXpKZ0F6bG5PMiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6ODoiaHR0cDovL18iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759279944),('ZtUMJsUiihW1cxUPKUuAjs0keCk5YFLqHHYEGw37',NULL,'123.163.114.189','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM3M0c0dieW9nZ2VES0xGRks1eURHazI0ZHN2MUdPcGIzWlFBckZITiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759272123),('ZzeFA5Vv93eLoFhciyOVu7KrQJ2zesIAiXoqkvSj',NULL,'1.24.16.63','Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibXhNNzRjRzMwckxKY25ndkk0REZYQTBBYWVOT1RneWx6SWlzSlBBZSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly80My4xNzMuMjkuMjQyOjgwODAiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1759270978);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_locations`
--

DROP TABLE IF EXISTS `stock_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loc_branch_code` (`branch_id`,`code`),
  UNIQUE KEY `uniq_branch_type` (`branch_id`,`type`),
  KEY `idx_loc_branch` (`branch_id`),
  CONSTRAINT `fk_loc_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_locations`
--

LOCK TABLES `stock_locations` WRITE;
/*!40000 ALTER TABLE `stock_locations` DISABLE KEYS */;
INSERT INTO `stock_locations` VALUES (1,1,'STORE','Main Store 1','STORE');
/*!40000 ALTER TABLE `stock_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_moves`
--

DROP TABLE IF EXISTS `stock_moves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_moves` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` int unsigned NOT NULL,
  `qty` decimal(18,2) NOT NULL,
  `from_location_id` bigint unsigned DEFAULT NULL,
  `to_location_id` bigint unsigned DEFAULT NULL,
  `ref_type` enum('PO','GRN','TRANSFER','POS','PROJECT_ISSUE','PROJECT_RETURN','ADJUST') COLLATE utf8mb4_general_ci NOT NULL,
  `ref_id` bigint unsigned NOT NULL,
  `state` enum('DRAFT','DONE','CANCEL') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'DONE',
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_moves`
--

LOCK TABLES `stock_moves` WRITE;
/*!40000 ALTER TABLE `stock_moves` DISABLE KEYS */;
INSERT INTO `stock_moves` VALUES (1,1,1,136.00,NULL,1,'ADJUST',1,'DONE',1,'2025-09-21 13:34:24'),(2,2,1,144.00,NULL,1,'ADJUST',2,'DONE',1,'2025-09-21 13:34:24'),(3,3,1,180.00,NULL,1,'ADJUST',3,'DONE',1,'2025-09-21 13:34:24'),(4,4,1,246.00,NULL,1,'ADJUST',4,'DONE',1,'2025-09-21 13:34:24'),(5,5,1,6.00,NULL,1,'ADJUST',5,'DONE',1,'2025-09-21 13:34:24'),(6,6,1,150.00,NULL,1,'ADJUST',6,'DONE',1,'2025-09-21 13:34:24'),(7,7,1,120.00,NULL,1,'ADJUST',7,'DONE',1,'2025-09-21 13:34:24'),(8,8,1,149.00,NULL,1,'ADJUST',8,'DONE',1,'2025-09-21 13:34:24'),(9,9,1,45.00,NULL,1,'ADJUST',9,'DONE',1,'2025-09-21 13:34:24'),(10,10,1,150.00,NULL,1,'ADJUST',10,'DONE',1,'2025-09-21 13:34:24'),(11,11,1,150.00,NULL,1,'ADJUST',11,'DONE',1,'2025-09-21 13:34:24'),(12,12,1,254.00,NULL,1,'ADJUST',12,'DONE',1,'2025-09-21 13:34:24'),(13,13,1,59.00,NULL,1,'ADJUST',13,'DONE',1,'2025-09-21 13:34:24'),(14,14,1,104.00,NULL,1,'ADJUST',14,'DONE',1,'2025-09-21 13:34:24'),(15,15,1,119.00,NULL,1,'ADJUST',15,'DONE',1,'2025-09-21 13:34:24'),(16,16,1,104.00,NULL,1,'ADJUST',16,'DONE',1,'2025-09-21 13:34:24'),(17,17,1,156.00,NULL,1,'ADJUST',17,'DONE',1,'2025-09-21 13:34:24'),(18,18,1,14.00,NULL,1,'ADJUST',18,'DONE',1,'2025-09-21 13:34:24'),(19,19,1,104.00,NULL,1,'ADJUST',19,'DONE',1,'2025-09-21 13:34:24'),(20,20,2,58.00,NULL,1,'ADJUST',20,'DONE',1,'2025-09-21 13:34:24'),(21,21,2,100.00,NULL,1,'ADJUST',21,'DONE',1,'2025-09-21 13:34:24'),(22,22,2,37.00,NULL,1,'ADJUST',22,'DONE',1,'2025-09-21 13:34:24'),(23,23,2,48.00,NULL,1,'ADJUST',23,'DONE',1,'2025-09-21 13:34:24'),(24,24,2,220.00,NULL,1,'ADJUST',24,'DONE',1,'2025-09-21 13:34:24'),(25,25,2,122.00,NULL,1,'ADJUST',25,'DONE',1,'2025-09-21 13:34:24'),(26,26,2,42.00,NULL,1,'ADJUST',26,'DONE',1,'2025-09-21 13:34:24'),(27,27,2,1.00,NULL,1,'ADJUST',27,'DONE',1,'2025-09-21 13:34:24'),(28,28,2,58.00,NULL,1,'ADJUST',28,'DONE',1,'2025-09-21 13:34:24'),(29,29,2,64.00,NULL,1,'ADJUST',29,'DONE',1,'2025-09-21 13:34:24'),(30,30,2,6.00,NULL,1,'ADJUST',30,'DONE',1,'2025-09-21 13:34:24'),(31,31,2,39.00,NULL,1,'ADJUST',31,'DONE',1,'2025-09-21 13:34:24'),(32,32,2,28.00,NULL,1,'ADJUST',32,'DONE',1,'2025-09-21 13:34:24'),(33,33,2,16.00,NULL,1,'ADJUST',33,'DONE',1,'2025-09-21 13:34:24'),(34,34,2,86.00,NULL,1,'ADJUST',34,'DONE',1,'2025-09-21 13:34:24'),(35,35,2,114.00,NULL,1,'ADJUST',35,'DONE',1,'2025-09-21 13:34:24'),(36,36,2,105.00,NULL,1,'ADJUST',36,'DONE',1,'2025-09-21 13:34:24'),(37,37,2,3.00,NULL,1,'ADJUST',37,'DONE',1,'2025-09-21 13:34:24'),(38,38,1,26.00,NULL,1,'ADJUST',38,'DONE',1,'2025-09-21 13:34:24'),(39,39,1,59.00,NULL,1,'ADJUST',39,'DONE',1,'2025-09-21 13:34:24'),(40,40,1,48.00,NULL,1,'ADJUST',40,'DONE',1,'2025-09-21 13:34:24'),(41,41,1,20.00,NULL,1,'ADJUST',41,'DONE',1,'2025-09-21 13:34:24'),(42,42,1,35.00,NULL,1,'ADJUST',42,'DONE',1,'2025-09-21 13:34:24'),(43,43,1,17.00,NULL,1,'ADJUST',43,'DONE',1,'2025-09-21 13:34:24'),(44,44,1,39.00,NULL,1,'ADJUST',44,'DONE',1,'2025-09-21 13:34:24'),(45,45,1,10.00,NULL,1,'ADJUST',45,'DONE',1,'2025-09-21 13:34:24'),(46,46,2,627.00,NULL,1,'ADJUST',46,'DONE',1,'2025-09-21 13:34:24'),(47,47,2,104.00,NULL,1,'ADJUST',47,'DONE',1,'2025-09-21 13:34:24'),(48,48,3,13.00,NULL,1,'ADJUST',48,'DONE',1,'2025-09-21 13:34:24'),(49,49,4,24.00,NULL,1,'ADJUST',49,'DONE',1,'2025-09-21 13:34:24'),(50,50,4,22.00,NULL,1,'ADJUST',50,'DONE',1,'2025-09-21 13:34:24'),(51,51,5,30.00,NULL,1,'ADJUST',51,'DONE',1,'2025-09-21 13:34:24'),(52,52,3,2756.00,NULL,1,'ADJUST',52,'DONE',1,'2025-09-21 13:34:24'),(53,53,1,225.00,NULL,1,'ADJUST',53,'DONE',1,'2025-09-21 13:34:24'),(54,54,1,45.00,NULL,1,'ADJUST',54,'DONE',1,'2025-09-21 13:34:24'),(55,55,1,75.00,NULL,1,'ADJUST',55,'DONE',1,'2025-09-21 13:34:24'),(56,56,1,75.00,NULL,1,'ADJUST',56,'DONE',1,'2025-09-21 13:34:24'),(57,57,1,30.00,NULL,1,'ADJUST',57,'DONE',1,'2025-09-21 13:34:24'),(58,58,1,186.00,NULL,1,'ADJUST',58,'DONE',1,'2025-09-21 13:34:24'),(59,59,1,80.00,NULL,1,'ADJUST',59,'DONE',1,'2025-09-21 13:34:24'),(60,60,1,166.00,NULL,1,'ADJUST',60,'DONE',1,'2025-09-21 13:34:24'),(61,61,1,180.00,NULL,1,'ADJUST',61,'DONE',1,'2025-09-21 13:34:24'),(62,62,1,150.00,NULL,1,'ADJUST',62,'DONE',1,'2025-09-21 13:34:24'),(63,63,1,134.00,NULL,1,'ADJUST',63,'DONE',1,'2025-09-21 13:34:24'),(64,64,1,195.00,NULL,1,'ADJUST',64,'DONE',1,'2025-09-21 13:34:24'),(65,65,1,150.00,NULL,1,'ADJUST',65,'DONE',1,'2025-09-21 13:34:24'),(66,66,1,85.00,NULL,1,'ADJUST',66,'DONE',1,'2025-09-21 13:34:24'),(67,41,1,14.00,NULL,NULL,'POS',1,'DONE',2,'2025-09-23 10:02:03'),(68,52,3,56.00,NULL,NULL,'POS',1,'DONE',2,'2025-09-23 10:02:03'),(69,45,1,1.00,NULL,NULL,'POS',1,'DONE',2,'2025-09-23 10:02:03'),(70,49,4,2.00,NULL,NULL,'POS',1,'DONE',2,'2025-09-23 10:02:03'),(71,47,2,6.00,NULL,NULL,'POS',1,'DONE',2,'2025-09-23 10:02:03'),(72,46,2,80.00,NULL,NULL,'POS',2,'DONE',2,'2025-09-23 10:14:18'),(73,34,2,1.00,NULL,NULL,'POS',3,'DONE',2,'2025-09-23 18:03:12'),(74,58,1,22.00,NULL,NULL,'POS',4,'DONE',2,'2025-09-26 14:18:44'),(75,30,2,6.00,NULL,NULL,'POS',4,'DONE',2,'2025-09-26 14:18:44'),(76,46,2,19.00,NULL,NULL,'POS',4,'DONE',2,'2025-09-26 14:18:44'),(77,55,1,8.00,NULL,NULL,'POS',5,'DONE',2,'2025-09-27 16:13:01'),(78,24,2,4.00,NULL,NULL,'POS',5,'DONE',2,'2025-09-27 16:13:01'),(79,46,2,12.00,NULL,NULL,'POS',5,'DONE',2,'2025-09-27 16:13:01'),(80,58,1,14.00,NULL,NULL,'POS',6,'DONE',2,'2025-09-28 12:43:02'),(81,46,2,4.00,NULL,NULL,'POS',6,'DONE',2,'2025-09-28 12:43:02');
/*!40000 ALTER TABLE `stock_moves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_quants`
--

DROP TABLE IF EXISTS `stock_quants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_quants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quant_product_location` (`product_id`,`location_id`),
  KEY `idx_quant_product` (`product_id`),
  KEY `idx_quant_location` (`location_id`),
  CONSTRAINT `fk_quant_location` FOREIGN KEY (`location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_quant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_quants`
--

LOCK TABLES `stock_quants` WRITE;
/*!40000 ALTER TABLE `stock_quants` DISABLE KEYS */;
INSERT INTO `stock_quants` VALUES (1,1,1,136.00),(2,2,1,144.00),(3,3,1,180.00),(4,4,1,246.00),(5,5,1,6.00),(6,6,1,150.00),(7,7,1,120.00),(8,8,1,149.00),(9,9,1,45.00),(10,10,1,150.00),(11,11,1,150.00),(12,12,1,254.00),(13,13,1,59.00),(14,14,1,104.00),(15,15,1,119.00),(16,16,1,104.00),(17,17,1,156.00),(18,18,1,14.00),(19,19,1,104.00),(20,20,1,58.00),(21,21,1,100.00),(22,22,1,37.00),(23,23,1,48.00),(24,24,1,220.00),(25,25,1,122.00),(26,26,1,42.00),(27,27,1,1.00),(28,28,1,58.00),(29,29,1,64.00),(30,30,1,6.00),(31,31,1,39.00),(32,32,1,28.00),(33,33,1,16.00),(34,34,1,86.00),(35,35,1,114.00),(36,36,1,105.00),(37,37,1,3.00),(38,38,1,26.00),(39,39,1,59.00),(40,40,1,48.00),(41,41,1,20.00),(42,42,1,35.00),(43,43,1,17.00),(44,44,1,39.00),(45,45,1,10.00),(46,46,1,627.00),(47,47,1,104.00),(48,48,1,13.00),(49,49,1,24.00),(50,50,1,22.00),(51,51,1,30.00),(52,52,1,2756.00),(53,53,1,225.00),(54,54,1,45.00),(55,55,1,75.00),(56,56,1,75.00),(57,57,1,30.00),(58,58,1,186.00),(59,59,1,80.00),(60,60,1,166.00),(61,61,1,180.00),(62,62,1,150.00),(63,63,1,134.00),(64,64,1,195.00),(65,65,1,150.00),(66,66,1,85.00);
/*!40000 ALTER TABLE `stock_quants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transfer_lines`
--

DROP TABLE IF EXISTS `stock_transfer_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfer_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` int unsigned NOT NULL,
  `qty` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_trfl_uom` (`uom_id`),
  KEY `idx_trfl_transfer` (`transfer_id`),
  KEY `idx_trfl_product` (`product_id`),
  CONSTRAINT `fk_trfl_header` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_trfl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_trfl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transfer_lines`
--

LOCK TABLES `stock_transfer_lines` WRITE;
/*!40000 ALTER TABLE `stock_transfer_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_transfer_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transfers`
--

DROP TABLE IF EXISTS `stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `branch_from_id` bigint unsigned NOT NULL,
  `branch_to_id` bigint unsigned NOT NULL,
  `location_from_id` bigint unsigned DEFAULT NULL,
  `location_to_id` bigint unsigned DEFAULT NULL,
  `status` enum('PENDING_APPROVAL','APPROVED','REJECTED','SHIPPED','RECEIVED','CLOSED') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDING_APPROVAL',
  `requested_by` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `total_items` int DEFAULT '0',
  `total_qty` decimal(18,2) DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_number` (`transfer_number`),
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transfers`
--

LOCK TABLES `stock_transfers` WRITE;
/*!40000 ALTER TABLE `stock_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_invoice_lines`
--

DROP TABLE IF EXISTS `supplier_invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_invoice_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_invoice_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` int unsigned NOT NULL,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_invoice_lines`
--

LOCK TABLES `supplier_invoice_lines` WRITE;
/*!40000 ALTER TABLE `supplier_invoice_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_invoice_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_invoices`
--

DROP TABLE IF EXISTS `supplier_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `po_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `invoice_no` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `invoice_date` date NOT NULL,
  `status` enum('DRAFT','MATCHED','EXCEPTION','PAID') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sinv_supplier_no` (`supplier_id`,`invoice_no`),
  KEY `fk_sinv_branch` (`branch_id`),
  KEY `idx_sinv_po` (`po_id`),
  KEY `idx_sinv_status` (`status`),
  CONSTRAINT `fk_sinv_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sinv_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sinv_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_invoices`
--

LOCK TABLES `supplier_invoices` WRITE;
/*!40000 ALTER TABLE `supplier_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer_docs`
--

DROP TABLE IF EXISTS `transfer_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfer_docs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint unsigned NOT NULL,
  `doc_no` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `qr_token` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_id` (`transfer_id`),
  UNIQUE KEY `doc_no` (`doc_no`),
  UNIQUE KEY `qr_token` (`qr_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_docs`
--

LOCK TABLES `transfer_docs` WRITE;
/*!40000 ALTER TABLE `transfer_docs` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfer_docs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uoms`
--

DROP TABLE IF EXISTS `uoms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `uoms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uoms`
--

LOCK TABLES `uoms` WRITE;
/*!40000 ALTER TABLE `uoms` DISABLE KEYS */;
INSERT INTO `uoms` VALUES (1,'LBR','LBR'),(2,'BTG','BTG'),(3,'PCS','PCS'),(4,'BTL','BTL'),(5,'BOX','BOX'),(6,'M','Meter');
/*!40000 ALTER TABLE `uoms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_branches`
--

DROP TABLE IF EXISTS `user_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_branches` (
  `user_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`branch_id`),
  KEY `idx_ub_branch` (`branch_id`),
  CONSTRAINT `fk_ub_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_branches`
--

LOCK TABLES `user_branches` WRITE;
/*!40000 ALTER TABLE `user_branches` DISABLE KEYS */;
INSERT INTO `user_branches` VALUES (2,1);
/*!40000 ALTER TABLE `user_branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role_id` bigint unsigned NOT NULL,
  `default_branch_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_branch` (`default_branch_id`),
  CONSTRAINT `fk_users_default_branch` FOREIGN KEY (`default_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'owner','$2y$12$lBJ83L8MlPwt5JDCjOIZGetL7L4tkOEooKIg/6Lk284PcKjt0cMFG','owner',NULL,1,NULL,1,'2025-09-20 19:02:57'),(2,'kasir1','$2y$12$d8mp3j1wNtOqtDRu/.cqq.1/jL3ct.phnQ1sF8R.XKKN96bXnyrCO','Kasir Syamil Plafon Majalengka',NULL,3,1,1,'2025-09-21 06:32:53');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-01  4:29:47
