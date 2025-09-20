-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2025 at 02:06 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_final`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `wipe_all` ()   BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE t VARCHAR(128);
  DECLARE v_ai BIGINT;

  DECLARE cur CURSOR FOR
    SELECT table_name
    FROM information_schema.tables
    WHERE table_schema = DATABASE();

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO t;
    IF done THEN LEAVE read_loop; END IF;

    -- Hapus semua baris
    SET @s = CONCAT('DELETE FROM `', t, '`');
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

    -- Reset AUTO_INCREMENT kalau ada
    SELECT AUTO_INCREMENT
      INTO v_ai
      FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = t;

    IF v_ai IS NOT NULL THEN
      SET @a = CONCAT('ALTER TABLE `', t, '` AUTO_INCREMENT = 1');
      PREPARE st2 FROM @a; EXECUTE st2; DEALLOCATE PREPARE st2;
    END IF;
  END LOOP;
  CLOSE cur;

  SET FOREIGN_KEY_CHECKS = 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `wipe_all_data` ()   BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE t VARCHAR(128);

  /* Cursor daftar BASE TABLE di database aktif */
  DECLARE cur CURSOR FOR
    SELECT table_name
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_type = 'BASE TABLE';
      
  /* Lanjutkan bila NOT FOUND / ada exception kecil */
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;

  /* Simpan nilai lama, lalu matikan checks */
  SET @old_fk = @@FOREIGN_KEY_CHECKS;
  SET @old_uk = @@UNIQUE_CHECKS;
  SET FOREIGN_KEY_CHECKS = 0;
  SET UNIQUE_CHECKS = 0;
  SET SQL_SAFE_UPDATES = 0;

  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO t;
    IF done = 1 THEN
      LEAVE read_loop;
    END IF;

    /* Hapus semua baris (lebih aman dari TRUNCATE untuk FK) */
    SET @sql = CONCAT('DELETE FROM `', t, '`;');
    PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

    /* Reset AUTO_INCREMENT hanya jika tabel punya kolom AI */
    SELECT COUNT(*)
      INTO @has_ai
      FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name   = t
       AND EXTRA LIKE '%auto_increment%';

    IF @has_ai > 0 THEN
      SET @sql2 = CONCAT('ALTER TABLE `', t, '` AUTO_INCREMENT = 1;');
      PREPARE s2 FROM @sql2; EXECUTE s2; DEALLOCATE PREPARE s2;
    END IF;
  END LOOP;
  CLOSE cur;

  /* Pulihkan settings */
  SET FOREIGN_KEY_CHECKS = @old_fk;
  SET UNIQUE_CHECKS = @old_uk;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `object_type` enum('PO','TRANSFER','MR') NOT NULL,
  `object_id` bigint(20) UNSIGNED NOT NULL,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `approver_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `note` text DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `object_type` varchar(50) NOT NULL,
  `object_id` bigint(20) UNSIGNED NOT NULL,
  `kind` varchar(50) NOT NULL,
  `path` varchar(255) NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event` varchar(100) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ref_type` varchar(50) DEFAULT NULL,
  `ref_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_movements`
--

CREATE TABLE `cash_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `direction` enum('IN','OUT') NOT NULL,
  `category` varchar(30) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipts`
--

CREATE TABLE `goods_receipts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `received_by` bigint(20) UNSIGNED NOT NULL,
  `received_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('DRAFT','DONE') NOT NULL DEFAULT 'DONE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt_lines`
--

CREATE TABLE `goods_receipt_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `grn_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL,
  `qty_received` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inbox_items`
--

CREATE TABLE `inbox_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `object_type` enum('PO','TRANSFER','MR','SYSTEM') NOT NULL,
  `object_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject` varchar(160) NOT NULL,
  `status` enum('PENDING','READ','DONE','ARCHIVED') NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leftover_pieces`
--

CREATE TABLE `leftover_pieces` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `length_m` decimal(18,3) NOT NULL,
  `condition` enum('GOOD','DAMAGED') NOT NULL DEFAULT 'GOOD',
  `source_type` enum('RETURN','ADJUST','PROJECT_RETURN') NOT NULL DEFAULT 'RETURN',
  `source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reserved_project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leftover_piece_consumptions`
--

CREATE TABLE `leftover_piece_consumptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `piece_id` bigint(20) UNSIGNED NOT NULL,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `used_m` decimal(10,3) NOT NULL,
  `price_per_m` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leftover_piece_usages`
--

CREATE TABLE `leftover_piece_usages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `leftover_piece_id` bigint(20) UNSIGNED NOT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `length_used_m` decimal(18,3) NOT NULL,
  `used_at` datetime NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `material_requests`
--

CREATE TABLE `material_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `status` enum('PENDING','APPROVED','ISSUED','REJECTED','DONE') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `material_request_lines`
--

CREATE TABLE `material_request_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mr_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL,
  `qty_requested` decimal(18,2) NOT NULL,
  `qty_approved` decimal(18,2) NOT NULL DEFAULT 0.00,
  `qty_issued` decimal(18,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `material_returns`
--

CREATE TABLE `material_returns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `returned_by` bigint(20) UNSIGNED NOT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('PENDING','PROCESSED') NOT NULL DEFAULT 'PENDING',
  `returned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `material_return_lines`
--

CREATE TABLE `material_return_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `material_return_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL,
  `qty_returned` decimal(18,2) NOT NULL,
  `condition` enum('GOOD','DAMAGED','LOST') NOT NULL DEFAULT 'GOOD',
  `writeoff_qty` decimal(18,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_payments`
--

CREATE TABLE `pos_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pos_sale_id` bigint(20) UNSIGNED NOT NULL,
  `method` enum('CASH','CARD','QR','TRANSFER') NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `ref_no` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_refunds`
--

CREATE TABLE `pos_refunds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_sales`
--

CREATE TABLE `pos_sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `cashier_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sale_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('DRAFT','PAID','VOID','REFUND') NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `change_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_sale_lines`
--

CREATE TABLE `pos_sale_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pos_sale_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL,
  `qty` decimal(18,3) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_service_lines`
--

CREATE TABLE `pos_service_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pos_sale_id` bigint(20) UNSIGNED NOT NULL,
  `service_name` varchar(160) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(160) NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL,
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
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(40) NOT NULL,
  `title` varchar(160) NOT NULL,
  `status` enum('DRAFT','ALLOCATED','IN_PROGRESS','WAITING_RETURN','READY_TO_BILL','DONE') NOT NULL DEFAULT 'DRAFT',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_boms`
--

CREATE TABLE `project_boms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL,
  `qty_planned` decimal(18,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_services`
--

CREATE TABLE `project_services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('DRAFT','PENDING_APPROVAL','APPROVED','REJECTED','CLOSED') NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_lines`
--

CREATE TABLE `purchase_order_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL,
  `qty_ordered` decimal(18,2) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `discount` decimal(18,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'OWNER', 'OWNER'),
(2, 'KEPALA_CABANG', 'Kepala Cabang'),
(3, 'KASIR', 'KASIR');

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoices`
--

CREATE TABLE `sales_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `source_type` enum('PROJECT') NOT NULL,
  `source_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('DRAFT','POSTED','PAID','VOID') NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoice_lines`
--

CREATE TABLE `sales_invoice_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sales_invoice_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL,
  `qty` decimal(18,3) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('GrSyagjonp9gVCGjWjrgX6WkMYTbhWIzXAykGmQc', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiR2tUek03U1hmdkdJRFN6c0I2MzNNR3hYaFNDWFplbW1OSlpDc1FtYiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9wcm9kdWN0cy9pbXBvcnQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1758369840);

-- --------------------------------------------------------

--
-- Table structure for table `stock_locations`
--

CREATE TABLE `stock_locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_moves`
--

CREATE TABLE `stock_moves` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL,
  `qty` decimal(18,2) NOT NULL,
  `from_location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ref_type` enum('PO','GRN','TRANSFER','POS','PROJECT_ISSUE','PROJECT_RETURN','ADJUST') NOT NULL,
  `ref_id` bigint(20) UNSIGNED NOT NULL,
  `state` enum('DRAFT','DONE','CANCEL') NOT NULL DEFAULT 'DONE',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_quants`
--

CREATE TABLE `stock_quants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `qty` decimal(18,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transfer_number` varchar(50) DEFAULT NULL,
  `branch_from_id` bigint(20) UNSIGNED NOT NULL,
  `branch_to_id` bigint(20) UNSIGNED NOT NULL,
  `location_from_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_to_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('PENDING_APPROVAL','APPROVED','REJECTED','SHIPPED','RECEIVED','CLOSED') NOT NULL DEFAULT 'PENDING_APPROVAL',
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `total_items` int(11) DEFAULT 0,
  `total_qty` decimal(18,2) DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_lines`
--

CREATE TABLE `stock_transfer_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transfer_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL,
  `qty` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_invoices`
--

CREATE TABLE `supplier_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_no` varchar(60) NOT NULL,
  `invoice_date` date NOT NULL,
  `status` enum('DRAFT','MATCHED','EXCEPTION','PAID') NOT NULL DEFAULT 'DRAFT',
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_invoice_lines`
--

CREATE TABLE `supplier_invoice_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_invoice_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL,
  `qty` decimal(18,2) NOT NULL,
  `price` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer_docs`
--

CREATE TABLE `transfer_docs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transfer_id` bigint(20) UNSIGNED NOT NULL,
  `doc_no` varchar(50) NOT NULL,
  `qr_token` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `uoms`
--

CREATE TABLE `uoms` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `default_branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `role_id`, `default_branch_id`, `is_active`, `created_at`) VALUES
(1, 'owner', '$2y$12$lBJ83L8MlPwt5JDCjOIZGetL7L4tkOEooKIg/6Lk284PcKjt0cMFG', 'owner', NULL, 1, NULL, 1, '2025-09-20 19:02:57');

-- --------------------------------------------------------

--
-- Table structure for table `user_branches`
--

CREATE TABLE `user_branches` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approvals`
--
ALTER TABLE `approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_appr_req_by` (`requested_by`),
  ADD KEY `fk_appr_approver` (`approver_id`),
  ADD KEY `idx_appr_object` (`object_type`,`object_id`),
  ADD KEY `idx_appr_status` (`status`),
  ADD KEY `idx_appr_decided` (`decided_at`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_obj` (`object_type`,`object_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cash_movements`
--
ALTER TABLE `cash_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_branch_created` (`branch_id`,`created_at`),
  ADD KEY `idx_direction` (`direction`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_grn_user` (`received_by`),
  ADD KEY `idx_grn_po` (`po_id`),
  ADD KEY `idx_grn_branch` (`branch_id`),
  ADD KEY `idx_grn_status` (`status`);

--
-- Indexes for table `goods_receipt_lines`
--
ALTER TABLE `goods_receipt_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_grnl_uom` (`uom_id`),
  ADD KEY `idx_grnl_grn` (`grn_id`),
  ADD KEY `idx_grnl_product` (`product_id`);

--
-- Indexes for table `inbox_items`
--
ALTER TABLE `inbox_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inbox_user_status` (`user_id`,`status`),
  ADD KEY `idx_inbox_object` (`object_type`,`object_id`),
  ADD KEY `idx_inbox_created` (`created_at`);

--
-- Indexes for table `leftover_pieces`
--
ALTER TABLE `leftover_pieces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lp_available` (`branch_id`,`product_id`,`consumed_at`),
  ADD KEY `fk_lp_product` (`product_id`),
  ADD KEY `idx_lp_reserved` (`reserved_project_id`);

--
-- Indexes for table `leftover_piece_consumptions`
--
ALTER TABLE `leftover_piece_consumptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lpc_piece` (`piece_id`),
  ADD KEY `idx_lpc_project` (`project_id`);

--
-- Indexes for table `leftover_piece_usages`
--
ALTER TABLE `leftover_piece_usages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lpu_piece` (`leftover_piece_id`),
  ADD KEY `idx_lpu_project` (`project_id`);

--
-- Indexes for table `material_requests`
--
ALTER TABLE `material_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mr_user` (`requested_by`),
  ADD KEY `idx_mr_project` (`project_id`),
  ADD KEY `idx_mr_status` (`status`);

--
-- Indexes for table `material_request_lines`
--
ALTER TABLE `material_request_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mrl_uom` (`uom_id`),
  ADD KEY `idx_mrl_mr` (`mr_id`),
  ADD KEY `idx_mrl_product` (`product_id`);

--
-- Indexes for table `material_returns`
--
ALTER TABLE `material_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mret_returned_by` (`returned_by`),
  ADD KEY `fk_mret_processed_by` (`processed_by`),
  ADD KEY `idx_mret_project` (`project_id`),
  ADD KEY `idx_mret_status` (`status`);

--
-- Indexes for table `material_return_lines`
--
ALTER TABLE `material_return_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mretl_uom` (`uom_id`),
  ADD KEY `idx_mretl_header` (`material_return_id`),
  ADD KEY `idx_mretl_product` (`product_id`);

--
-- Indexes for table `pos_payments`
--
ALTER TABLE `pos_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pay_sale` (`pos_sale_id`),
  ADD KEY `idx_pay_method` (`method`);

--
-- Indexes for table `pos_refunds`
--
ALTER TABLE `pos_refunds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ref_approver` (`approved_by`),
  ADD KEY `idx_ref_sale` (`sale_id`);

--
-- Indexes for table `pos_sales`
--
ALTER TABLE `pos_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ps_customer` (`customer_id`),
  ADD KEY `idx_ps_branch` (`branch_id`),
  ADD KEY `idx_ps_cashier` (`cashier_id`),
  ADD KEY `idx_ps_status` (`status`),
  ADD KEY `idx_ps_datetime` (`sale_datetime`),
  ADD KEY `idx_ps_project` (`project_id`);

--
-- Indexes for table `pos_sale_lines`
--
ALTER TABLE `pos_sale_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_psl_uom` (`uom_id`),
  ADD KEY `idx_psl_sale` (`pos_sale_id`),
  ADD KEY `idx_psl_product` (`product_id`);

--
-- Indexes for table `pos_service_lines`
--
ALTER TABLE `pos_service_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_psv_sale` (`pos_sale_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_products_cat` (`category_id`),
  ADD KEY `idx_products_uom` (`uom_id`),
  ADD KEY `idx_products_track_by_meter` (`track_by_meter`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_pc_parent` (`parent_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `fk_proj_customer` (`customer_id`),
  ADD KEY `fk_proj_creator` (`created_by`),
  ADD KEY `idx_proj_branch` (`branch_id`),
  ADD KEY `idx_proj_status` (`status`);

--
-- Indexes for table `project_boms`
--
ALTER TABLE `project_boms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pb_product` (`product_id`),
  ADD KEY `fk_pb_uom` (`uom_id`),
  ADD KEY `idx_pb_project` (`project_id`);

--
-- Indexes for table `project_services`
--
ALTER TABLE `project_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_services_project` (`project_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_po_req_by` (`requested_by`),
  ADD KEY `fk_po_app_by` (`approved_by`),
  ADD KEY `idx_po_branch` (`branch_id`),
  ADD KEY `idx_po_supplier` (`supplier_id`),
  ADD KEY `idx_po_status` (`status`);

--
-- Indexes for table `purchase_order_lines`
--
ALTER TABLE `purchase_order_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pol_uom` (`uom_id`),
  ADD KEY `idx_pol_po` (`po_id`),
  ADD KEY `idx_pol_product` (`product_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_si_branch` (`branch_id`),
  ADD KEY `fk_si_customer` (`customer_id`),
  ADD KEY `idx_si_source` (`source_type`,`source_id`),
  ADD KEY `idx_si_status` (`status`);

--
-- Indexes for table `sales_invoice_lines`
--
ALTER TABLE `sales_invoice_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sil_uom` (`uom_id`),
  ADD KEY `idx_sil_header` (`sales_invoice_id`),
  ADD KEY `idx_sil_product` (`product_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `stock_locations`
--
ALTER TABLE `stock_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_loc_branch_code` (`branch_id`,`code`),
  ADD UNIQUE KEY `uniq_branch_type` (`branch_id`,`type`),
  ADD KEY `idx_loc_branch` (`branch_id`);

--
-- Indexes for table `stock_moves`
--
ALTER TABLE `stock_moves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sm_uom` (`uom_id`),
  ADD KEY `fk_sm_user` (`created_by`),
  ADD KEY `idx_sm_product` (`product_id`),
  ADD KEY `idx_sm_from` (`from_location_id`),
  ADD KEY `idx_sm_to` (`to_location_id`),
  ADD KEY `idx_sm_ref` (`ref_type`,`ref_id`),
  ADD KEY `idx_sm_created` (`created_at`);

--
-- Indexes for table `stock_quants`
--
ALTER TABLE `stock_quants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_quant_product_location` (`product_id`,`location_id`),
  ADD KEY `idx_quant_product` (`product_id`),
  ADD KEY `idx_quant_location` (`location_id`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transfer_number` (`transfer_number`),
  ADD KEY `fk_trf_req_by` (`requested_by`),
  ADD KEY `fk_trf_app_by` (`approved_by`),
  ADD KEY `idx_trf_from` (`branch_from_id`),
  ADD KEY `idx_trf_to` (`branch_to_id`),
  ADD KEY `idx_trf_status` (`status`);

--
-- Indexes for table `stock_transfer_lines`
--
ALTER TABLE `stock_transfer_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trfl_uom` (`uom_id`),
  ADD KEY `idx_trfl_transfer` (`transfer_id`),
  ADD KEY `idx_trfl_product` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_invoices`
--
ALTER TABLE `supplier_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sinv_supplier_no` (`supplier_id`,`invoice_no`),
  ADD KEY `fk_sinv_branch` (`branch_id`),
  ADD KEY `idx_sinv_po` (`po_id`),
  ADD KEY `idx_sinv_status` (`status`);

--
-- Indexes for table `supplier_invoice_lines`
--
ALTER TABLE `supplier_invoice_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sinvl_uom` (`uom_id`),
  ADD KEY `idx_sinvl_header` (`supplier_invoice_id`),
  ADD KEY `idx_sinvl_product` (`product_id`);

--
-- Indexes for table `transfer_docs`
--
ALTER TABLE `transfer_docs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transfer_id` (`transfer_id`),
  ADD UNIQUE KEY `doc_no` (`doc_no`),
  ADD UNIQUE KEY `qr_token` (`qr_token`);

--
-- Indexes for table `uoms`
--
ALTER TABLE `uoms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_role` (`role_id`),
  ADD KEY `idx_users_branch` (`default_branch_id`);

--
-- Indexes for table `user_branches`
--
ALTER TABLE `user_branches`
  ADD PRIMARY KEY (`user_id`,`branch_id`),
  ADD KEY `idx_ub_branch` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_movements`
--
ALTER TABLE `cash_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipt_lines`
--
ALTER TABLE `goods_receipt_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inbox_items`
--
ALTER TABLE `inbox_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leftover_pieces`
--
ALTER TABLE `leftover_pieces`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leftover_piece_consumptions`
--
ALTER TABLE `leftover_piece_consumptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leftover_piece_usages`
--
ALTER TABLE `leftover_piece_usages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `material_requests`
--
ALTER TABLE `material_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `material_request_lines`
--
ALTER TABLE `material_request_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `material_returns`
--
ALTER TABLE `material_returns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `material_return_lines`
--
ALTER TABLE `material_return_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_payments`
--
ALTER TABLE `pos_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_refunds`
--
ALTER TABLE `pos_refunds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_sales`
--
ALTER TABLE `pos_sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_sale_lines`
--
ALTER TABLE `pos_sale_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_service_lines`
--
ALTER TABLE `pos_service_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_boms`
--
ALTER TABLE `project_boms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_services`
--
ALTER TABLE `project_services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_lines`
--
ALTER TABLE `purchase_order_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_invoice_lines`
--
ALTER TABLE `sales_invoice_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_locations`
--
ALTER TABLE `stock_locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_moves`
--
ALTER TABLE `stock_moves`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_quants`
--
ALTER TABLE `stock_quants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfer_lines`
--
ALTER TABLE `stock_transfer_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_invoices`
--
ALTER TABLE `supplier_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_invoice_lines`
--
ALTER TABLE `supplier_invoice_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transfer_docs`
--
ALTER TABLE `transfer_docs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uoms`
--
ALTER TABLE `uoms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `approvals`
--
ALTER TABLE `approvals`
  ADD CONSTRAINT `fk_appr_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appr_req_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD CONSTRAINT `fk_grn_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grn_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grn_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `goods_receipt_lines`
--
ALTER TABLE `goods_receipt_lines`
  ADD CONSTRAINT `fk_grnl_header` FOREIGN KEY (`grn_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grnl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grnl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `inbox_items`
--
ALTER TABLE `inbox_items`
  ADD CONSTRAINT `fk_inbox_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `leftover_pieces`
--
ALTER TABLE `leftover_pieces`
  ADD CONSTRAINT `fk_lp_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lp_reserved_project` FOREIGN KEY (`reserved_project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `leftover_piece_consumptions`
--
ALTER TABLE `leftover_piece_consumptions`
  ADD CONSTRAINT `fk_lpc_piece` FOREIGN KEY (`piece_id`) REFERENCES `leftover_pieces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lpc_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `leftover_piece_usages`
--
ALTER TABLE `leftover_piece_usages`
  ADD CONSTRAINT `fk_lpu_piece` FOREIGN KEY (`leftover_piece_id`) REFERENCES `leftover_pieces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lpu_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `material_requests`
--
ALTER TABLE `material_requests`
  ADD CONSTRAINT `fk_mr_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mr_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `material_request_lines`
--
ALTER TABLE `material_request_lines`
  ADD CONSTRAINT `fk_mrl_mr` FOREIGN KEY (`mr_id`) REFERENCES `material_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mrl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mrl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `material_returns`
--
ALTER TABLE `material_returns`
  ADD CONSTRAINT `fk_mret_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mret_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mret_returned_by` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `material_return_lines`
--
ALTER TABLE `material_return_lines`
  ADD CONSTRAINT `fk_mretl_header` FOREIGN KEY (`material_return_id`) REFERENCES `material_returns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mretl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mretl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `pos_payments`
--
ALTER TABLE `pos_payments`
  ADD CONSTRAINT `fk_pay_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pos_refunds`
--
ALTER TABLE `pos_refunds`
  ADD CONSTRAINT `fk_ref_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ref_sale` FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pos_sales`
--
ALTER TABLE `pos_sales`
  ADD CONSTRAINT `fk_ps_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ps_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ps_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ps_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pos_sale_lines`
--
ALTER TABLE `pos_sale_lines`
  ADD CONSTRAINT `fk_psl_header` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_psl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_psl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `pos_service_lines`
--
ALTER TABLE `pos_service_lines`
  ADD CONSTRAINT `fk_psv_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prod_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `fk_pc_parent` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_proj_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_proj_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_proj_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `project_boms`
--
ALTER TABLE `project_boms`
  ADD CONSTRAINT `fk_pb_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pb_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pb_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `project_services`
--
ALTER TABLE `project_services`
  ADD CONSTRAINT `fk_project_services_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_app_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_po_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_po_req_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `purchase_order_lines`
--
ALTER TABLE `purchase_order_lines`
  ADD CONSTRAINT `fk_pol_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pol_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pol_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD CONSTRAINT `fk_si_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_si_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `sales_invoice_lines`
--
ALTER TABLE `sales_invoice_lines`
  ADD CONSTRAINT `fk_sil_header` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sil_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sil_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `stock_locations`
--
ALTER TABLE `stock_locations`
  ADD CONSTRAINT `fk_loc_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock_moves`
--
ALTER TABLE `stock_moves`
  ADD CONSTRAINT `fk_sm_from_loc` FOREIGN KEY (`from_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sm_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sm_to_loc` FOREIGN KEY (`to_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sm_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `stock_quants`
--
ALTER TABLE `stock_quants`
  ADD CONSTRAINT `fk_quant_location` FOREIGN KEY (`location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_quant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD CONSTRAINT `fk_trf_app_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_trf_from` FOREIGN KEY (`branch_from_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_trf_req_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_trf_to` FOREIGN KEY (`branch_to_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `stock_transfer_lines`
--
ALTER TABLE `stock_transfer_lines`
  ADD CONSTRAINT `fk_trfl_header` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_trfl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_trfl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `supplier_invoices`
--
ALTER TABLE `supplier_invoices`
  ADD CONSTRAINT `fk_sinv_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sinv_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sinv_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `supplier_invoice_lines`
--
ALTER TABLE `supplier_invoice_lines`
  ADD CONSTRAINT `fk_sinvl_header` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `supplier_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sinvl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sinvl_uom` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_default_branch` FOREIGN KEY (`default_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_branches`
--
ALTER TABLE `user_branches`
  ADD CONSTRAINT `fk_ub_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
