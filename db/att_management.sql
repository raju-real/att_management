/*
 Navicat Premium Data Transfer

 Source Server         : LOCALHOST
 Source Server Type    : MySQL
 Source Server Version : 100425
 Source Host           : localhost:3306
 Source Schema         : att_management

 Target Server Type    : MySQL
 Target Server Version : 100425
 File Encoding         : 65001

 Date: 21/09/2026 22:27:46
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for attendance_logs
-- ----------------------------
DROP TABLE IF EXISTS `attendance_logs`;
CREATE TABLE `attendance_logs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_type` enum('student','teacher') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `student_no` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `student_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `teacher_no` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `unmatched_pin` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `device_id` int(11) NULL DEFAULT NULL,
  `device_serial` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `punch_time` timestamp(0) NULL DEFAULT NULL,
  `attendance_by` enum('fingerprint','card','face','pin','manual') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fingerprint',
  `client_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `latitude` decimal(10, 7) NULL DEFAULT NULL,
  `longitude` decimal(10, 7) NULL DEFAULT NULL,
  `location_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `raw_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `verify_mode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `work_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `punch_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_by` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `updated_by` int(11) NULL DEFAULT NULL,
  `deleted_at` timestamp(0) NULL DEFAULT NULL,
  `deleted_by` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_student_attendance`(`student_no`, `punch_time`, `device_serial`) USING BTREE,
  UNIQUE INDEX `uniq_teacher_attendance`(`teacher_no`, `punch_time`, `device_serial`) USING BTREE,
  INDEX `idx_attendance_user_type`(`user_type`) USING BTREE,
  INDEX `idx_attendance_student_no`(`student_no`) USING BTREE,
  INDEX `idx_attendance_teacher_no`(`teacher_no`) USING BTREE,
  INDEX `idx_attendance_punch_time`(`punch_time`) USING BTREE,
  INDEX `idx_attendance_unmatched_pin`(`unmatched_pin`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for departments
-- ----------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `deleted_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of departments
-- ----------------------------
INSERT INTO `departments` VALUES (3, 'Accounts', 'active', '2026-09-21 21:59:32', '2026-09-21 21:59:32', NULL);
INSERT INTO `departments` VALUES (4, 'Staff', 'active', '2026-09-21 21:59:58', '2026-09-21 21:59:58', NULL);

-- ----------------------------
-- Table structure for device_commands
-- ----------------------------
DROP TABLE IF EXISTS `device_commands`;
CREATE TABLE `device_commands`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) UNSIGNED NOT NULL,
  `command_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','sent','done','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `executed_at` timestamp(0) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `device_commands_device_id_index`(`device_id`) USING BTREE,
  INDEX `device_commands_status_index`(`status`) USING BTREE,
  CONSTRAINT `device_commands_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of device_commands
-- ----------------------------
INSERT INTO `device_commands` VALUES (1, 1, 'DATA UPDATE USERINFO PIN=101	Name=MOKTADIRUL ISLAM	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'sent', '2026-09-20 22:09:38', '2026-09-19 23:52:50', '2026-09-19 23:52:50');
INSERT INTO `device_commands` VALUES (2, 1, 'DATA UPDATE USERINFO PIN=101	Name=MOKTADIRUL ISLAM	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'sent', '2026-09-20 22:10:07', '2026-09-19 23:54:05', '2026-09-19 23:54:05');
INSERT INTO `device_commands` VALUES (3, 1, 'DATA UPDATE USERINFO PIN=101	Name=MOKTADIRUL ISLAM	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-20 22:40:27', '2026-09-20 22:40:27');
INSERT INTO `device_commands` VALUES (4, 1, 'DATA UPDATE USERINFO PIN=101	Name=MOKTADIRUL ISLAM	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-20 22:41:03', '2026-09-20 22:41:03');
INSERT INTO `device_commands` VALUES (5, 1, 'DATA UPDATE USERINFO PIN=102	Name=Moktadirul Raju	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-20 22:41:03', '2026-09-20 22:41:03');
INSERT INTO `device_commands` VALUES (6, 1, 'DATA UPDATE USERINFO PIN=101	Name=MOKTADIRUL ISLAM	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-20 23:29:03', '2026-09-20 23:29:03');
INSERT INTO `device_commands` VALUES (7, 1, 'DATA UPDATE USERINFO PIN=102	Name=Moktadirul Raju	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-20 23:29:03', '2026-09-20 23:29:03');
INSERT INTO `device_commands` VALUES (8, 1, 'DATA UPDATE USERINFO PIN=Omnis ad consectetur	Name=Sasha Potts	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-21 21:20:15', '2026-09-21 21:20:15');
INSERT INTO `device_commands` VALUES (9, 1, 'DATA UPDATE USERINFO PIN=1001	Name=MOKTADIRUL ISLAM	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-21 21:20:37', '2026-09-21 21:20:37');
INSERT INTO `device_commands` VALUES (10, 1, 'DATA UPDATE USERINFO PIN=1002	Name=Moktadirul Raju	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-21 21:20:37', '2026-09-21 21:20:37');
INSERT INTO `device_commands` VALUES (11, 1, 'DATA UPDATE USERINFO PIN=6001	Name=Sasha Potts	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-21 21:20:37', '2026-09-21 21:20:37');
INSERT INTO `device_commands` VALUES (12, 1, 'DATA UPDATE USERINFO PIN=1001	Name=MOKTADIRUL ISLAM	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-21 21:21:15', '2026-09-21 21:21:15');
INSERT INTO `device_commands` VALUES (13, 1, 'DATA UPDATE USERINFO PIN=1002	Name=Moktadirul Raju	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-21 21:21:15', '2026-09-21 21:21:15');
INSERT INTO `device_commands` VALUES (14, 1, 'DATA UPDATE USERINFO PIN=6001	Name=Sasha Potts	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-21 21:21:15', '2026-09-21 21:21:15');
INSERT INTO `device_commands` VALUES (15, 1, 'DATA UPDATE USERINFO PIN=1001	Name=MOKTADIRUL ISLAM	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-21 21:26:30', '2026-09-21 21:26:30');
INSERT INTO `device_commands` VALUES (16, 1, 'DATA UPDATE USERINFO PIN=1002	Name=Moktadirul Raju	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-21 21:26:30', '2026-09-21 21:26:30');
INSERT INTO `device_commands` VALUES (17, 1, 'DATA UPDATE USERINFO PIN=6001	Name=Sasha Potts	Card=	Privilege=0	Password=	Group=1	TimeZone=0000000000000000000100000000	', 'pending', NULL, '2026-09-21 21:26:30', '2026-09-21 21:26:30');

-- ----------------------------
-- Table structure for devices
-- ----------------------------
DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `serial_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `device_port` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `subnet_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `gateway_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `location_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `comm_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Communication Key| Menu → Comm / Network → Comm Key/ Menu ->Pc Connection(Comm Key) | 0 (most devices) | Sometimes 12345',
  `device_for` enum('student_teacher','student','teacher') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `use_push_mode` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'true = iClock HTTP push mode; false = TCP/UDP direct connect',
  `last_synced_at` timestamp(0) NULL DEFAULT NULL,
  `last_seen_at` timestamp(0) NULL DEFAULT NULL,
  `created_by` int(11) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `updated_by` int(11) NULL DEFAULT NULL,
  `deleted_at` timestamp(0) NULL DEFAULT NULL,
  `deleted_by` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `devices_serial_no_unique`(`serial_no`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of devices
-- ----------------------------
INSERT INTO `devices` VALUES (1, 'Device One', 'device-one', 'COAW215260228', '192.168.0.101', '4370', NULL, '192.168.0.1', NULL, '0', 'teacher', 'active', 0, NULL, '2026-09-20 22:10:07', 1, '2026-02-24 21:21:25', '2026-09-21 22:00:58', 1, NULL, NULL);

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp(0) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `failed_jobs_uuid_unique`(`uuid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for fee_lots
-- ----------------------------
DROP TABLE IF EXISTS `fee_lots`;
CREATE TABLE `fee_lots`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED NULL DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `jobs_queue_index`(`queue`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO `migrations` VALUES (1, '2014_10_12_000000_create_users_table', 1);
INSERT INTO `migrations` VALUES (2, '2014_10_12_100000_create_password_resets_table', 1);
INSERT INTO `migrations` VALUES (3, '2019_08_19_000000_create_failed_jobs_table', 1);
INSERT INTO `migrations` VALUES (4, '2019_12_14_000001_create_personal_access_tokens_table', 1);
INSERT INTO `migrations` VALUES (5, '2025_08_17_182928_create_devices_table', 1);
INSERT INTO `migrations` VALUES (6, '2025_08_17_183130_create_attendance_logs_table', 1);
INSERT INTO `migrations` VALUES (7, '2026_01_07_203813_create_students_table', 1);
INSERT INTO `migrations` VALUES (8, '2026_01_08_001541_create_teachers_table', 1);
INSERT INTO `migrations` VALUES (9, '2026_02_09_000001_create_fee_lots_table', 1);
INSERT INTO `migrations` VALUES (10, '2026_02_10_000001_create_payment_transactions_table', 1);
INSERT INTO `migrations` VALUES (11, '2026_02_18_203500_create_jobs_table', 1);
INSERT INTO `migrations` VALUES (12, '2026_09_19_000001_add_use_push_mode_to_devices_table', 2);
INSERT INTO `migrations` VALUES (13, '2026_09_19_000002_create_device_commands_table', 3);
INSERT INTO `migrations` VALUES (14, '2026_07_01_000001_add_image_to_teachers_table', 3);
INSERT INTO `migrations` VALUES (15, '2026_09_20_233934_add_unmatched_pin_to_attendance_logs_table', 4);
INSERT INTO `migrations` VALUES (16, '2026_09_20_233945_add_network_info_to_devices_table', 4);
INSERT INTO `migrations` VALUES (17, '2026_09_21_213601_create_departments_table', 5);
INSERT INTO `migrations` VALUES (18, '2026_09_21_213602_create_shifts_table', 5);
INSERT INTO `migrations` VALUES (19, '2026_09_21_213603_add_department_shift_to_teachers_table', 5);

-- ----------------------------
-- Table structure for password_resets
-- ----------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets`  (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  INDEX `password_resets_email_index`(`email`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for payment_transactions
-- ----------------------------
DROP TABLE IF EXISTS `payment_transactions`;
CREATE TABLE `payment_transactions`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_fee_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gateway` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` enum('PENDING','SUCCESS','FAILED','CANCELLED','REFUNDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `transaction_amount` decimal(10, 2) NOT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BDT',
  `val_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `bank_tran_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `card_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `card_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `card_issuer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `card_brand` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `card_sub_brand` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `card_issuer_country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `card_issuer_country_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `store_amount` decimal(10, 2) NULL DEFAULT NULL,
  `verify_sign` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `verify_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `verify_sign_sha2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `currency_amount` decimal(10, 2) NULL DEFAULT NULL,
  `currency_rate` decimal(10, 4) NULL DEFAULT NULL,
  `risk_level` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `risk_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `refund_amount` decimal(10, 2) NULL DEFAULT NULL,
  `refund_ref_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `refund_date` timestamp(0) NULL DEFAULT NULL,
  `refund_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `refund_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `transaction_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `transaction_date` timestamp(0) NULL DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `payment_transactions_transaction_id_unique`(`transaction_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `last_used_at` timestamp(0) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `personal_access_tokens_token_unique`(`token`) USING BTREE,
  INDEX `personal_access_tokens_tokenable_type_tokenable_id_index`(`tokenable_type`, `tokenable_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for shifts
-- ----------------------------
DROP TABLE IF EXISTS `shifts`;
CREATE TABLE `shifts`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `in_time` time(0) NOT NULL,
  `out_time` time(0) NOT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `deleted_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `shifts_department_id_foreign`(`department_id`) USING BTREE,
  CONSTRAINT `shifts_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for students
-- ----------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `student_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `firstname` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `middlename` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `lastname` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `nickname` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `nationality` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `religion` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `gender` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `class` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `roll` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `section` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `shift` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `medium` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `group` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fname` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `fmobile` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `mname` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `mmobile` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `bloodgroup` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `mobile` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `session` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `school_id` int(11) NULL DEFAULT NULL,
  `votter_id` int(11) NULL DEFAULT NULL,
  `user_id` int(11) NULL DEFAULT NULL,
  `g_date` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `sms_status` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `deleted_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for teachers
-- ----------------------------
DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `mobile` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `shift_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `deleted_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `teachers_teacher_no_unique`(`teacher_no`) USING BTREE,
  INDEX `teachers_department_id_foreign`(`department_id`) USING BTREE,
  INDEX `teachers_shift_id_foreign`(`shift_id`) USING BTREE,
  CONSTRAINT `teachers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `teachers_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of teachers
-- ----------------------------
INSERT INTO `teachers` VALUES (1, '6002', 'MOKTADIRUL ISLAM', 'raju@mail.com', '01737773393', 'teacher', NULL, NULL, NULL, '2026-09-19 23:52:43', '2026-09-19 23:52:43', NULL);
INSERT INTO `teachers` VALUES (2, '6003', 'Moktadirul Raju', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-20 22:40:55', '2026-09-20 22:40:55', NULL);
INSERT INTO `teachers` VALUES (4, '6001', 'Sasha Potts', 'siriqahej@mailinator.com', 'Ducimus sed officia', 'Aperiam consequatur', NULL, NULL, NULL, '2026-09-21 21:20:15', '2026-09-21 21:20:15', NULL);

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `password_plain` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_login_at` datetime(0) NULL DEFAULT NULL,
  `last_logout_at` datetime(0) NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `deleted_at` timestamp(0) NULL DEFAULT NULL,
  `deleted_by` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `users_email_unique`(`email`) USING BTREE,
  UNIQUE INDEX `users_mobile_unique`(`mobile`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'Mr. Admin', 'admin@mail.com', '12345679810', '123456', '$2y$10$ZMVqcidmZPk9ryt9iiYjB.xNufI8Kvse18ZziR4.mCtWEJzrWarX6', NULL, NULL, 'active', '2026-09-21 22:05:32', NULL, '2026-02-24 21:20:23', '2026-09-21 22:05:32', 1, NULL, NULL);

SET FOREIGN_KEY_CHECKS = 1;
