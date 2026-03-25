/*
 Navicat Premium Dump SQL

 Source Server         : 办公室-Mysql
 Source Server Type    : MySQL
 Source Server Version : 50651 (5.6.51)
 Source Host           : 192.168.2.166:3306
 Source Schema         : xinkin-oss

 Target Server Type    : MySQL
 Target Server Version : 50651 (5.6.51)
 File Encoding         : 65001

 Date: 25/03/2026 14:13:44
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for file_share_access_logs
-- ----------------------------
DROP TABLE IF EXISTS `file_share_access_logs`;
CREATE TABLE `file_share_access_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `file_share_id` bigint(20) unsigned NOT NULL,
  `action` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fslog_share_created` (`file_share_id`,`created_at`),
  CONSTRAINT `fk_fslog_share` FOREIGN KEY (`file_share_id`) REFERENCES `file_shares` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of file_share_access_logs
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for file_shares
-- ----------------------------
DROP TABLE IF EXISTS `file_shares`;
CREATE TABLE `file_shares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `user_upload_id` bigint(20) unsigned NOT NULL,
  `token` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'hex(32 bytes)',
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_views` int(10) unsigned DEFAULT NULL COMMENT 'NULL 表示不限次数',
  `view_count` int(10) unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime DEFAULT NULL COMMENT 'NULL 表示不过期',
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_file_shares_token` (`token`),
  KEY `idx_file_shares_user` (`user_id`),
  KEY `idx_file_shares_upload` (`user_upload_id`),
  CONSTRAINT `fk_file_shares_upload` FOREIGN KEY (`user_upload_id`) REFERENCES `user_uploads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_shares_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of file_shares
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for membership_plan_extensions
-- ----------------------------
DROP TABLE IF EXISTS `membership_plan_extensions`;
CREATE TABLE `membership_plan_extensions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` smallint(5) unsigned NOT NULL,
  `extension` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '不含点，如 xlsx',
  `created_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_extension` (`plan_id`,`extension`),
  CONSTRAINT `fk_mpe_plan` FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员等级允许上传的扩展名';

-- ----------------------------
-- Records of membership_plan_extensions
-- ----------------------------
BEGIN;
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (1, 1, 'jpg', '2026-03-23 09:32:32.321');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (2, 1, 'jpeg', '2026-03-23 09:32:32.321');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (3, 1, 'png', '2026-03-23 09:32:32.321');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (4, 1, 'gif', '2026-03-23 09:32:32.321');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (5, 1, 'webp', '2026-03-23 09:32:32.321');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (6, 1, 'pdf', '2026-03-23 09:32:32.321');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (7, 1, 'txt', '2026-03-23 09:32:32.321');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (8, 2, 'jpg', '2026-03-23 09:38:45.903');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (9, 2, 'jpeg', '2026-03-23 09:38:45.903');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (10, 2, 'png', '2026-03-23 09:38:45.903');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (11, 2, 'gif', '2026-03-23 09:38:45.903');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (12, 2, 'webp', '2026-03-23 09:38:45.903');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (13, 2, 'pdf', '2026-03-23 09:38:45.903');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (14, 2, 'txt', '2026-03-23 09:38:45.903');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (15, 2, 'xlsx', '2026-03-23 09:38:45.903');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (16, 2, 'xls', '2026-03-23 09:38:45.903');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (17, 2, 'csv', '2026-03-23 09:38:45.903');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (18, 2, 'zip', '2026-03-23 09:38:45.903');
INSERT INTO `membership_plan_extensions` (`id`, `plan_id`, `extension`, `created_at`) VALUES (19, 2, '7z', '2026-03-23 09:38:45.903');
COMMIT;

-- ----------------------------
-- Table structure for membership_plans
-- ----------------------------
DROP TABLE IF EXISTS `membership_plans`;
CREATE TABLE `membership_plans` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '唯一编码，如 free / pro / enterprise',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '展示名称',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_uploads` int(10) unsigned DEFAULT NULL COMMENT '周期内最大上传条数；NULL 不限制',
  `quota_period` enum('lifetime','month','day') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lifetime' COMMENT '与 max_uploads 配合：终身 / 自然月 / 自然日',
  `max_file_size` bigint(20) unsigned DEFAULT NULL COMMENT '单文件最大字节；NULL 不限制',
  `sort_order` smallint(6) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_membership_plans_code` (`code`),
  KEY `idx_membership_plans_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员等级及上传配额策略';

-- ----------------------------
-- Records of membership_plans
-- ----------------------------
BEGIN;
INSERT INTO `membership_plans` (`id`, `code`, `name`, `description`, `max_uploads`, `quota_period`, `max_file_size`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'free', '免费版', '基础体验', 10, 'month', 10485760, 1, 1, '2026-03-23 09:32:32.317', '2026-03-23 09:32:32.317');
INSERT INTO `membership_plans` (`id`, `code`, `name`, `description`, `max_uploads`, `quota_period`, `max_file_size`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (2, 'pro', '专业版', '更多类型与数量', 500, 'month', 52428800, 2, 1, '2026-03-23 09:32:32.317', '2026-03-23 09:32:32.317');
INSERT INTO `membership_plans` (`id`, `code`, `name`, `description`, `max_uploads`, `quota_period`, `max_file_size`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (3, 'enterprise', '企业版', '不限量（示例）', NULL, 'lifetime', NULL, 3, 1, '2026-03-23 09:32:32.317', '2026-03-23 09:32:32.317');
COMMIT;

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime(3) NOT NULL,
  `used_at` datetime(3) DEFAULT NULL,
  `created_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pwd_reset_token_hash` (`token_hash`),
  KEY `idx_pwd_reset_user` (`user_id`),
  CONSTRAINT `fk_pwd_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='密码重置令牌（可选）';

-- ----------------------------
-- Records of password_reset_tokens
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for user_sessions
-- ----------------------------
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA256(hex) 存哈希，不存明文 token',
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime(3) NOT NULL,
  `created_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `last_seen_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_sessions_token_hash` (`token_hash`),
  KEY `idx_user_sessions_user_expires` (`user_id`,`expires_at`),
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录会话（可选）';

-- ----------------------------
-- Records of user_sessions
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for user_uploads
-- ----------------------------
DROP TABLE IF EXISTS `user_uploads`;
CREATE TABLE `user_uploads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `storage_path` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '相对 storage 的路径，与现有接口一致',
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extension` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '小写、不含点',
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `mime_type` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_user_uploads_user_created` (`user_id`,`created_at`),
  CONSTRAINT `fk_user_uploads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户上传记录，用于配额统计';

-- ----------------------------
-- Records of user_uploads
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '登录账号，唯一',
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'password_hash() 等算法结果',
  `display_name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plan_id` smallint(5) unsigned NOT NULL COMMENT '当前会员等级',
  `plan_expires_at` datetime(3) DEFAULT NULL COMMENT '会员到期；NULL 表示不过期或与 plan 无关',
  `email_verified_at` datetime(3) DEFAULT NULL,
  `status` enum('active','disabled','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_login_at` datetime(3) DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IPv6 最长 45',
  `created_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_plan_status` (`plan_id`,`status`),
  CONSTRAINT `fk_users_plan` FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户';

-- ----------------------------
-- Records of users
-- ----------------------------
BEGIN;
INSERT INTO `users` (`id`, `email`, `password_hash`, `display_name`, `plan_id`, `plan_expires_at`, `email_verified_at`, `status`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at`) VALUES (1, 'demo@qq.com', '$2y$12$36IuCfE2agi/ybB8d.0oVOzQRgaPvjQe/gIy1TUs0tJhJa.jT.aVi', 'Demo User', 3, NULL, NULL, 'active', '2026-03-25 08:50:48.000', '192.168.2.82', '2026-03-23 17:49:38.000', '2026-03-25 08:50:48.000');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
